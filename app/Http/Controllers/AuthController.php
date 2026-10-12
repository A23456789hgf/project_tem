<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PasswordReset;
use App\Models\Role;
use App\Models\User;
use App\Scopes\DomainScope;
use App\Services\AuditLogService;
use App\Services\SmppSmsService;
use App\Services\SmsEventManager;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1. استخراج الحقل والمدخلات
        $loginField = $request->has('phone') && ! $request->has('username') ? 'phone' : 'username';
        $rawIdentifier = trim((string) ($request->input('phone') ?? $request->input('username') ?? ''));
        $password = (string) $request->input('password', '');

        // Diagnostic log: method entry & identifier
        Log::info('🔎 AUTH LOGIN ENTRY', [
            'login_field' => $loginField,
            'identifier_raw' => $rawIdentifier,
            'ip' => $request->ip(),
        ]);

        // 2. التحقق من صحة المدخلات (Validation)
        $request->validate([
            $loginField => 'required|string',
            'password' => 'required|string',
        ], [
            $loginField.'.required' => 'رقم الهاتف أو اسم المستخدم مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        // 3. تطبيع رقم الهاتف/المعرف لمنع تجاوز Rate Limiting
        $normalizedPhone = $this->normalizePhoneDigits($rawIdentifier);
        $throttleIdentifier = ! empty($normalizedPhone) ? $normalizedPhone : Str::lower($rawIdentifier);
        $throttleKey = 'login|'.$throttleIdentifier.'|'.$request->ip();

        // 4. الحماية من هجمات Brute Force عبر Rate Limiter
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $throttleMessage = "تم تجاوز عدد محاولات تسجيل الدخول المسموح بها. يرجى المحاولة مرة أخرى بعد {$seconds} ثانية.";

            AuditLogService::log(
                action: 'login_throttled',
                module: 'Authentication',
                description: 'تم تجاوز عدد محاولات تسجيل الدخول المسموح بها'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $throttleMessage,
                ], 429);
            }

            return back()->withErrors([$loginField => $throttleMessage])->withInput($request->except('password'));
        }

        // 5. تجاوز Super Admin في حال تكوينه
        $saUsername = env('SUPER_ADMIN_USERNAME');
        $saPassword = env('SUPER_ADMIN_PASSWORD');

        if ($saUsername && $saPassword && $rawIdentifier === $saUsername && $password === $saPassword) {
            $superAdmin = new class extends User
            {
                public $exists = true;

                public $incrementing = false;

                public function save(array $options = [])
                {
                    return true;
                }

                public function update(array $attributes = [], array $options = [])
                {
                    return true;
                }

                public function push()
                {
                    return true;
                }

                public function delete()
                {
                    return true;
                }

                public function getPermissionsAttribute()
                {
                    return collect([]);
                }

                public function permissions()
                {
                    return new class
                    {
                        public function pluck()
                        {
                            return collect(['*']);
                        }
                    };
                }
            };

            $superAdmin->id = 0;
            $superAdmin->user_id = 'SYSTEM_SA';
            $superAdmin->username = $saUsername;

            $adminRole = Role::where('name', 'Admin')->first();
            $superAdmin->role_id = $adminRole ? $adminRole->id : null;
            $superAdmin->status = 'Active';

            Auth::login($superAdmin, $request->filled('remember'));
            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);

            Log::info('✅ SUPER ADMIN LOGIN SUCCESS', [
                'username' => $saUsername,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);

            try {
                AuditLogService::log(
                    action: 'login',
                    module: 'Authentication',
                    description: 'Super Admin logged in via bypass'
                );
            } catch (\Exception $e) {
                // Silently ignore if DB is unavailable
            }

            session(['user_permissions' => ['*']]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل الدخول بنجاح',
                    'csrf_token' => csrf_token(),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        }

        // 6. البحث عن المستخدم برقم الهاتف أو اسم المستخدم
        $phoneCandidates = $this->phoneLoginCandidates($rawIdentifier);

        $user = User::withoutGlobalScope(DomainScope::class)->where(function ($query) use ($rawIdentifier, $phoneCandidates) {
            $query->where('username', $rawIdentifier)
                ->orWhereIn('phone', $phoneCandidates)
                ->orWhereIn($this->normalizedPhoneColumn(), $phoneCandidates);
        })
            ->first();

        $genericErrorMessage = 'رقم الهاتف أو اسم المستخدم أو كلمة المرور غير صحيحة.';

        if (! $user || $user->status !== 'Active') {
            RateLimiter::hit($throttleKey, 60);

            Log::warning('❌ LOGIN FAILED: Invalid credentials or inactive user', [
                'identifier' => $rawIdentifier,
                'login_field' => $loginField,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);

            AuditLogService::log(
                action: 'login_failed',
                module: 'Authentication',
                description: 'محاولة تسجيل دخول فاشلة - بيانات الدخول غير صحيحة أو الحساب غير نشط',
                modelType: $user ? 'User' : null,
                modelId: $user?->id
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $genericErrorMessage,
                ], 422);
            }

            return back()
                ->withErrors([$loginField => $genericErrorMessage])
                ->withInput($request->except('password'));
        }

        // التحقق من كلمة المرور بعد التأكد من وجود المستخدم وأن الحساب نشط
        if (! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            Log::warning('❌ LOGIN FAILED: Invalid password', [
                'user_id' => $user->id,
                'identifier' => $rawIdentifier,
                'password_length' => strlen($password),
                'password_hex' => bin2hex($password),
                'login_field' => $loginField,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);

            AuditLogService::log(
                action: 'login_failed',
                module: 'Authentication',
                description: 'محاولة تسجيل دخول فاشلة - كلمة المرور غير صحيحة',
                modelType: 'User',
                modelId: $user->id
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $genericErrorMessage,
                ], 422);
            }

            return back()
                ->withErrors([$loginField => $genericErrorMessage])
                ->withInput($request->except('password'));
        }

        // 8. تسجيل دخول المستخدم وتجديد Session ID لمنع Session Fixation
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        // 9. تحميل الدور والصلاحيات المحدثة من قاعدة البيانات
        try {
            $permissions = $user->getAllPermissionsSlugs();
            session(['user_permissions' => $permissions]);

            Log::info('🔑 PERMISSIONS LOADED', [
                'user_id' => $user->id,
                'permissions_count' => count($permissions),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ PERMISSIONS LOAD ERROR', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            session(['user_permissions' => []]);
        }

        // إنشاء وتخزين توكن المراقبة
        $monitoringToken = Str::uuid()->toString();
        session(['monitoring_token' => $monitoringToken]);

        // تسجيل العملية في AuditLog
        AuditLogService::log(
            action: 'login',
            module: 'Authentication',
            description: 'تسجيل الدخول بنجاح',
            modelType: 'User',
            modelId: $user->id
        );

        Log::info('✅ LOGIN SUCCESS', [
            'user_id' => $user->id,
            'username' => $user->username,
            'role_id' => $user->role_id,
            'ip' => $request->ip(),
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);

        // 10. التوجيه الإجباري لتغيير كلمة المرور إذا كانت مطلوبة
        if ($user->must_change_password) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'must_change_password' => true,
                    'redirect' => route('password.change.forced'),
                    'csrf_token' => csrf_token(),
                ]);
            }

            return redirect()
                ->route('password.change.forced')
                ->with('info', 'يجب تغيير كلمة المرور قبل المتابعة.');
        }

        // 11. التوجيه الآمن (Intended Redirect)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'redirect' => route('dashboard'),
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * @return array<int, string>
     */
    private function phoneLoginCandidates(string $identifier): array
    {
        $digits = $this->normalizePhoneDigits($identifier);
        $candidates = array_filter([
            trim($identifier),
            $digits,
        ]);

        if ($digits !== '') {
            if (str_starts_with($digits, '967')) {
                $local = substr($digits, 3);
                $candidates[] = $local;
                $candidates[] = '0'.$local;
            } elseif (str_starts_with($digits, '0')) {
                $local = substr($digits, 1);
                $candidates[] = $local;
                $candidates[] = '967'.$local;
            } else {
                $candidates[] = '0'.$digits;
                $candidates[] = '967'.$digits;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function normalizePhoneDigits(string $value): string
    {
        $westernDigits = strtr($value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);

        return preg_replace('/\D+/', '', $westernDigits) ?? '';
    }

    private function normalizedPhoneColumn(): Expression
    {
        return DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', '')");
    }

    public function register(Request $request)
    {
        $validator = $request->validate([
            'username' => 'required|string|unique:users,username|min:3|max:255',
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
        ], [
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم موجود بالفعل',
            'username.min' => 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل',
            'name.required' => 'الاسم مطلوب',
            'name.min' => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني موجود بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        $defaultRole = Role::query()
            ->where('name', config('auth.registration_role'))
            ->where('is_active', true)
            ->firstOrFail();

        $user = User::create([
            'username' => $request->username,
            'user_id' => strtoupper(substr($request->username, 0, 3).date('YmdHis')),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $defaultRole->id,
            'phone' => $request->phone,
            'department' => $request->department,
            'status' => 'Active',
        ]);

        // ✅ تسجيل عملية التسجيل
        Log::info('✅ USER REGISTERED', [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'تسجيل حساب جديد',
            'ip_address' => $request->ip(),
        ]);

        if ($user->phone && app(SmsEventManager::class)->enabled('user.registered')) {
            try {
                $smsService = app(SmppSmsService::class);
                $message = "مرحباً بك {$user->name} في نظام إدارة المشاريع. تم تفعيل حسابك بنجاح.";
                $smsService->sendSmsAsync($user->id, $user->phone, $message, 'user.registered');
            } catch (\Exception $e) {
                Log::error('Failed to send user registration welcome SMS: '.$e->getMessage());
            }
        }

        Auth::login($user);

        // ✅ التحقق من نجاح تسجيل الدخول بعد التسجيل
        if (! Auth::check()) {
            Log::error('❌ REGISTRATION AUTH FAILED', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')->with('error', 'تم التسجيل ولكن حدث خطأ في تسجيل الدخول');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showForgotPassword()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.exists' => 'هذا البريد الإلكتروني غير مسجل في النظام',
        ]);

        $user = User::where('email', $request->email)->first();
        $token = Str::random(60);

        PasswordReset::where('user_id', $user->id)->delete();

        PasswordReset::create([
            'user_id' => $user->id,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        // ✅ تسجيل طلب إعادة تعيين كلمة المرور
        Log::info('📧 PASSWORD RESET REQUESTED', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);

        return back()->with('status', 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني');
    }

    public function showResetPassword($token)
    {
        $passwordReset = PasswordReset::where('token', $token)->first();

        if (! $passwordReset) {
            Log::warning('❌ PASSWORD RESET SHOW: Invalid token', [
                'token' => $token,
            ]);

            return redirect()->route('login')->withErrors(['token' => 'الرابط غير صحيح أو انتهت صلاحيته']);
        }

        $expiresAt = Carbon::parse($passwordReset->created_at)->addHours(1);
        if (Carbon::now()->isAfter($expiresAt)) {
            $passwordReset->delete();
            Log::warning('❌ PASSWORD RESET SHOW: Token expired', [
                'user_id' => $passwordReset->user_id,
                'created_at' => $passwordReset->created_at,
            ]);

            return redirect()->route('forgot-password')->withErrors(['token' => 'انتهت صلاحية الرابط']);
        }

        return view('auth.reset-password', ['token' => $token, 'email' => $passwordReset->user->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.exists' => 'هذا البريد الإلكتروني غير مسجل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        $passwordReset = PasswordReset::where('token', $request->token)->first();

        if (! $passwordReset) {
            // ❌ تسجيل فشل إعادة تعيين كلمة المرور
            Log::warning('❌ PASSWORD RESET FAILED: Invalid token', [
                'token' => $request->token,
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);

            return back()->withErrors(['token' => 'الرابط غير صحيح']);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            // ❌ تسجيل فشل إعادة تعيين كلمة المرور
            Log::warning('❌ PASSWORD RESET FAILED: User not found', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);

            return back()->withErrors(['email' => 'المستخدم غير موجود']);
        }

        $expiresAt = Carbon::parse($passwordReset->created_at)->addHours(1);
        if (Carbon::now()->isAfter($expiresAt)) {
            $passwordReset->delete();

            // ❌ تسجيل فشل إعادة تعيين كلمة المرور - انتهت الصلاحية
            Log::warning('❌ PASSWORD RESET FAILED: Token expired', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);

            return redirect()->route('forgot-password')->withErrors(['token' => 'انتهت صلاحية الرابط']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // ✅ مزامنة كلمة المرور مع ERPNext للمستخدم المالي
        try {
            if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                app(\App\Services\ErpNextUserService::class)->syncUser($user, 'password', null, $request->password);
            }
        } catch (\Throwable $e) {
            Log::error('ERPNext sync error in AuthController resetPassword: '.$e->getMessage(), ['user_id' => $user->id]);
        }

        $passwordReset->delete();

        // ✅ تسجيل نجاح إعادة تعيين كلمة المرور
        Log::info('✅ PASSWORD RESET SUCCESS', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now()->toDateTimeString(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'reset_password',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'إعادة تعيين كلمة المرور',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('login')->with('status', 'تم إعادة تعيين كلمة المرور بنجاح');
    }

    public function logout(Request $request)
    {
        $user = auth()->user();

        // ✅ تسجيل عملية تسجيل الخروج
        if ($user) {
            Log::info('🔓 LOGOUT', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ]);
        }

        AuditLogService::log(
            action: 'logout',
            module: 'Authentication',
            description: 'تسجيل الخروج',
            modelType: 'User',
            modelId: auth()->id()
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * دالة مساعدة للتحقق من حالة الجلسة (للتشخيص)
     */
    public function checkSession()
    {
        request()->session()->save(); // Release session lock for long polling

        Log::info('🔍 SESSION STATUS CHECK', [
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
            'permissions_count' => is_array(session('user_permissions')) ? count(session('user_permissions')) : 0,
            'monitoring_token' => session('monitoring_token', null),
        ]);

        if (Auth::check()) {
            $user = Auth::user();

            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                ],
                'session_id' => session()->getId(),
                'permissions' => session('user_permissions', []),
            ]);
        }

        return response()->json([
            'authenticated' => false,
            'session_id' => session()->getId(),
        ], 401);
    }

    /**
     * إرسال رمز OTP لاستعادة كلمة المرور عبر رقم الهاتف
     */
    public function sendOtp(Request $request, SmppSmsService $smsService)
    {
        $loginField = $request->has('phone') ? 'phone' : 'username';
        $username = trim($request->input($loginField));

        $request->validate([
            $loginField => 'required|string',
        ], [
            $loginField.'.required' => 'اسم المستخدم أو رقم الهاتف مطلوب',
        ]);

        $user = User::withoutGlobalScopes()
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username)
                    ->orWhere('user_id', $username)
                    ->orWhere('phone', $username);
            })
            ->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        if (! $user->phone) {
            return response()->json(['success' => false, 'message' => 'لا يوجد رقم هاتف مسجل لهذا المستخدم لإرسال رمز التحقق'], 400);
        }

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        PasswordReset::where('user_id', $user->id)->delete();

        PasswordReset::create([
            'user_id' => $user->id,
            'token' => Hash::make($otp),
            'created_at' => Carbon::now(),
        ]);

        // Send OTP via SMS
        $message = 'رمز استعادة كلمة المرور الخاص بك في نظام إدارة المشاريع هو: '.$otp;
        $result = $smsService->sendSMS($user->id, $user->phone, $message, 'password_reset_otp');

        if (empty($result['ok'])) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر إرسال رمز التحقق عبر SMS: '.($result['reason'] ?? 'فشل الاتصال ببوابة الرسائل'),
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال رمز التحقق بنجاح إلى رقم هاتفك']);
    }

    /**
     * التحقق من صحة رمز OTP
     */
    public function verifyOtp(Request $request)
    {
        $loginField = $request->has('phone') ? 'phone' : 'username';
        $username = trim($request->input($loginField));

        $request->validate([
            $loginField => 'required|string',
            'otp' => 'required|string|size:6',
        ], [
            $loginField.'.required' => 'اسم المستخدم أو رقم الهاتف مطلوب',
            'otp.required' => 'رمز التحقق مطلوب',
            'otp.size' => 'رمز التحقق يجب أن يتكون من 6 أرقام',
        ]);

        $user = User::withoutGlobalScopes()
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username)
                    ->orWhere('user_id', $username)
                    ->orWhere('phone', $username);
            })
            ->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير مسجل'], 404);
        }

        $passwordReset = PasswordReset::where('user_id', $user->id)->first();

        if (! $passwordReset || ! Hash::check($request->otp, $passwordReset->token)) {
            return response()->json(['success' => false, 'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'], 400);
        }

        // Check expiry (e.g. 15 minutes)
        $expiresAt = Carbon::parse($passwordReset->created_at)->addMinutes(15);
        if (Carbon::now()->isAfter($expiresAt)) {
            $passwordReset->delete();

            return response()->json(['success' => false, 'message' => 'انتهت صلاحية رمز التحقق. يرجى طلب رمز جديد'], 400);
        }

        return response()->json(['success' => true, 'message' => 'تم التحقق من الرمز بنجاح']);
    }

    /**
     * تعيين كلمة المرور الجديدة
     */
    public function resetPasswordWithOtp(Request $request)
    {
        $loginField = $request->has('phone') ? 'phone' : 'username';
        $username = trim($request->input($loginField));

        $request->validate([
            $loginField => 'required|string',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            $loginField.'.required' => 'اسم المستخدم أو رقم الهاتف مطلوب',
            'otp.required' => 'رمز التحقق مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        $user = User::withoutGlobalScopes()
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username)
                    ->orWhere('user_id', $username)
                    ->orWhere('phone', $username);
            })
            ->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
        }

        $passwordReset = PasswordReset::where('user_id', $user->id)->first();

        if (! $passwordReset || ! Hash::check($request->otp, $passwordReset->token)) {
            return response()->json(['success' => false, 'message' => 'رمز التحقق غير صحيح'], 400);
        }

        $expiresAt = Carbon::parse($passwordReset->created_at)->addMinutes(15);
        if (Carbon::now()->isAfter($expiresAt)) {
            $passwordReset->delete();

            return response()->json(['success' => false, 'message' => 'انتهت صلاحية رمز التحقق'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        // ✅ مزامنة كلمة المرور مع ERPNext للمستخدم المالي
        try {
            if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                app(\App\Services\ErpNextUserService::class)->syncUser($user, 'password', null, $request->password);
            }
        } catch (\Throwable $e) {
            Log::error('ERPNext sync error in AuthController resetPasswordWithOtp: '.$e->getMessage(), ['user_id' => $user->id]);
        }

        $passwordReset->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'reset_password_otp',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'إعادة تعيين كلمة المرور عبر رمز OTP (SMS)',
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم تعيين كلمة المرور الجديدة بنجاح']);
    }

    public function showForceChangePassword()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (! Auth::user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-change-password');
    }

    public function forceChangePassword(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password|max:50',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'كلمة المرور الجديدة يجب أن تكون 8 خانات على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password.different' => 'يجب أن تكون كلمة المرور الجديدة مختلفة عن كلمة المرور الحالية',
            'password.max' => 'كلمة المرور طويلة جداً (الحد الأقصى 50 حرفاً)',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة'])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        // ✅ مزامنة كلمة المرور مع ERPNext للمستخدم المالي
        try {
            if ($user->is_financial_user || $user->isLinkedToErpNext()) {
                app(\App\Services\ErpNextUserService::class)->syncUser($user, 'password', null, $request->password);
            }
        } catch (\Throwable $e) {
            Log::error('ERPNext sync error in AuthController forceChangePassword: '.$e->getMessage(), ['user_id' => $user->id]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'change_password_forced',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'قام المستخدم بتحديث كلمة المرور الافتراضية بنجاح عند تسجيل الدخول الأول',
            'ip_address' => $request->ip(),
        ]);

        session()->flash('success', 'تم تحديث كلمة المرور بنجاح ودخول النظام.');

        return redirect()->route('dashboard');
    }
}

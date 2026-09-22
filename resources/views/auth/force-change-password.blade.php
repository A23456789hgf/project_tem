<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>تغيير كلمة المرور الافتراضية - نظام إدارة المشاريع</title>
    <!-- Bootstrap 5 RTL & Font Awesome (Local) -->
    <link rel="stylesheet" href="{{ asset('css/libs/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/libs/font-awesome.all.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary-dark: #08214c;
            --primary-light: #1a4b8c;
            --accent-gold: #d4af37;
            --accent-gold-light: #f3d772;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ========== قسم نموذج الدخول ========== */
        .form-section {
            flex: 1;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 4vw, 3rem);
            position: relative;
            order: 1;
        }

        .login-card-modern {
            width: 100%;
            max-width: 500px;
            padding: clamp(1.5rem, 4vw, 2.5rem);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: clamp(16px, 3vw, 24px);
            box-shadow: 0 20px 60px rgba(8, 33, 76, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: right;
        }

        .welcome-text {
            font-size: clamp(1.4rem, 4vw, 1.75rem);
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .instruction-text {
            color: var(--text-light);
            font-size: clamp(0.9rem, 2.5vw, 0.95rem);
            margin-top: 0.75rem;
        }

        /* ========== حقول الإدخال ========== */
        .custom-input-group {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .custom-input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
        }

        .input-wrapper {
            position: relative;
        }

        .custom-form-control {
            width: 100%;
            padding: clamp(0.75rem, 2vw, 0.9rem) clamp(1rem, 3vw, 3rem) clamp(0.75rem, 2vw, 0.9rem) clamp(1rem, 3vw, 1rem);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: clamp(0.9rem, 2.5vw, 0.95rem);
            transition: var(--transition);
            background: #f8fafc;
            font-family: inherit;
            text-align: right;
        }

        .custom-form-control:focus {
            background: white;
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.12);
            outline: none;
        }

        .btn-toggle-password {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            z-index: 10;
        }

        .submit-btn {
            width: 100%;
            padding: clamp(0.85rem, 2vw, 1rem);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 75, 140, 0.2);
        }

        .brand-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            order: 2;
        }

        @media (max-width: 991.98px) {
            .brand-section {
                display: none;
            }
        }

        .alert-warning-custom {
            background-color: #fff3cd;
            border: 1px solid #ffe69c;
            color: #664d03;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <!-- القسم الأيمن: نموذج الدخول -->
        <div class="form-section">
            <div class="login-card-modern">
                <div class="form-header">
                    <h2 class="welcome-text">تغيير كلمة المرور الافتراضية</h2>
                    <p class="instruction-text">يجب تغيير كلمة المرور الافتراضية الخاصة بك قبل الاستمرار في استخدام
                        النظام لحماية حسابك.</p>
                </div>

                <div class="alert-warning-custom">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>تنبيه:</strong>
                        هذه الخطوة إلزامية ولا يمكن تجاوزها.
                    </div>
                </div>

                <!-- نموذج تغيير كلمة المرور الإجبارية -->
                @can('password.change.edit')
                <form method="POST" action="{{ route('password.change.update') }}" id="changePasswordForm">
                @endcan
                    @csrf

                    <!-- كلمة المرور الحالية -->
                    <div class="custom-input-group">
                        <label for="current_password">كلمة المرور الحالية</label>
                        <div class="input-wrapper" dir="ltr">
                            <input id="current_password" type="password" name="current_password"
                                class="custom-form-control" required placeholder="••••••••" dir="rtl"
                                style="padding-left: 3rem;">
                            <button type="button" class="btn-toggle-password"
                                onclick="togglePasswordField('current_password', this)">
                                <i class="fas fa-eye eye-open"></i>
                                <i class="fas fa-eye-slash eye-closed d-none"></i>
                            </button>
                        </div>
                    </div>

                    <!-- كلمة المرور الجديدة -->
                    <div class="custom-input-group">
                        <label for="password">كلمة المرور الجديدة</label>
                        <div class="input-wrapper" dir="ltr">
                            <input id="password" type="password" name="password" class="custom-form-control" required
                                placeholder="••••••••" dir="rtl" minlength="8" style="padding-left: 3rem;">
                            <button type="button" class="btn-toggle-password"
                                onclick="togglePasswordField('password', this)">
                                <i class="fas fa-eye eye-open"></i>
                                <i class="fas fa-eye-slash eye-closed d-none"></i>
                            </button>
                        </div>
                        <div class="form-text mt-1 text-muted">يجب أن تتكون من 8 أحرف على الأقل.</div>
                    </div>

                    <!-- تأكيد كلمة المرور -->
                    <div class="custom-input-group">
                        <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                        <div class="input-wrapper" dir="ltr">
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                class="custom-form-control" required placeholder="••••••••" dir="rtl" minlength="8"
                                style="padding-left: 3rem;">
                            <button type="button" class="btn-toggle-password"
                                onclick="togglePasswordField('password_confirmation', this)">
                                <i class="fas fa-eye eye-open"></i>
                                <i class="fas fa-eye-slash eye-closed d-none"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        <span>تحديث كلمة المرور ومتابعة</span>
                    </button>
                </form>

                @can('logout')
                <form action="{{ route('logout') }}" method="POST" class="mt-3 text-center">
                    @csrf
                    <button type="submit" class="btn btn-link text-decoration-none text-muted" formnovalidate>
                        <i class="fas fa-sign-out-alt ms-1"></i> تسجيل الخروج بدلاً من ذلك
                    </button>
                </form>
                @endcan

            </div>
        </div>

        <!-- القسم الأيسر: الهوية البصرية -->
        <div class="brand-section" style="text-align: center;">
            <img src="{{ asset('images/logo.png') }}" alt="الشعار" style="width: 150px; margin-bottom: 2rem;">
            <h1 style="font-weight: 800; font-size: 2rem; margin-bottom: 1rem;">وزارة الزراعة والثروة السمكية والموارد
                المائية</h1>
            <div
                style="width: 60px; height: 4px; background: var(--accent-gold); margin: 0 auto 1rem auto; border-radius: 2px;">
            </div>
            <h2 style="font-weight: 500; font-size: 1.25rem; opacity: 0.9;">نظام إدارة وتمويل المشاريع ومتابعة سلاسل
                القيمة</h2>
        </div>

    </div>

    <script>
        /**
         * تبديل رؤية حقل كلمة المرور
         * @param {string} fieldId - معرف الحقل
         * @param {HTMLElement} btn - الزر الذي تم النقر عليه
         */
        function togglePasswordField(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const openEye = btn.querySelector('.eye-open');
            const closedEye = btn.querySelector('.eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                openEye.classList.add('d-none');
                closedEye.classList.remove('d-none');
            } else {
                input.type = 'password';
                openEye.classList.remove('d-none');
                closedEye.classList.add('d-none');
            }
        }
    </script>
</body>

</html>
@if(Auth::check() && Auth::user()->must_change_password)
    <!-- Force Password Change Modal -->
    <div class="modal fade" id="forcePasswordChangeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="forcePasswordChangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="forcePasswordChangeModalLabel">
                        <i class="fas fa-lock me-2"></i> تغيير كلمة المرور الافتراضية
                    </h5>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 rounded-3 mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>تنبيه:</strong> يجب تغيير كلمة المرور الافتراضية قبل الاستمرار في استخدام النظام.
                    </div>

                    @can('password.change.edit')
                    <form action="{{ route('password.change.update') }}" method="POST" id="passwordChangeForm">
                    @endcan
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">كلمة المرور الحالية</label>
                            <div class="input-group" dir="ltr">
                                <button class="btn btn-outline-secondary px-3" type="button" onclick="togglePasswordField('current_password', this)">
                                    <i class="fas fa-eye eye-open"></i>
                                    <i class="fas fa-eye-slash eye-closed d-none"></i>
                                </button>
                                <input type="password" id="current_password" name="current_password" class="form-control text-end @error('current_password') is-invalid @enderror" required dir="rtl">
                                <span class="input-group-text"><i class="fas fa-unlock-alt"></i></span>
                            </div>
                            @error('current_password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">كلمة المرور الجديدة</label>
                            <div class="input-group" dir="ltr">
                                <button class="btn btn-outline-secondary px-3" type="button" onclick="togglePasswordField('password', this)">
                                    <i class="fas fa-eye eye-open"></i>
                                    <i class="fas fa-eye-slash eye-closed d-none"></i>
                                </button>
                                <input type="password" id="password" name="password" class="form-control text-end @error('password') is-invalid @enderror" required minlength="8" dir="rtl">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <div class="form-text text-muted text-end">يجب أن تكون 8 أحرف على الأقل.</div>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">تأكيد كلمة المرور الجديدة</label>
                            <div class="input-group" dir="ltr">
                                <button class="btn btn-outline-secondary px-3" type="button" onclick="togglePasswordField('password_confirmation', this)">
                                    <i class="fas fa-eye eye-open"></i>
                                    <i class="fas fa-eye-slash eye-closed d-none"></i>
                                </button>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control text-end" required minlength="8" dir="rtl">
                                <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    @can('logout')
                    <form action="{{ route('logout') }}" method="POST" class="d-inline mb-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary rounded-pill px-4" formnovalidate>
                            <i class="fas fa-sign-out-alt me-1"></i> خروج
                        </button>
                    </form>
                    @endcan
                    <button type="submit" form="passwordChangeForm" class="btn btn-danger px-4 rounded-pill">
                        <i class="fas fa-save me-1"></i> تحديث ومتابعة
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var myModal = new bootstrap.Modal(document.getElementById('forcePasswordChangeModal'), {
                backdrop: 'static',
                keyboard: false
            });
            myModal.show();
        });

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
@endif

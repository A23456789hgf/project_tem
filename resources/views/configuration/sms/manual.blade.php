@extends('layouts.app')

@section('content')
    @include('configuration.shared_styles')

    <style>
        /* ===== الخلفية العامة ===== */
        .sms-send-wrapper {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            padding: 30px 0;
        }

        /* ===== البطاقة الرئيسية ===== */
        .sms-send-main-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 31, 63, 0.08);
            border: 1px solid rgba(0, 31, 63, 0.05);
            overflow: hidden;
        }

        /* ===== رأس الصفحة ===== */
        .sms-send-header {
            background: linear-gradient(135deg, #001f3f 0%, #003366 50%, #004080 100%);
            padding: 30px 35px;
            position: relative;
            overflow: hidden;
        }

        .sms-send-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .sms-send-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.03) 0%, transparent 70%);
            border-radius: 50%;
        }

        .sms-send-header h2 {
            color: #ffffff !important;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .sms-send-header .header-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sms-send-header .header-icon i {
            font-size: 1.3rem;
            color: #ffffff;
        }

        .sms-send-header .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-top: 6px;
            position: relative;
            z-index: 1;
        }

        .sms-send-header .title-line {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #00d4ff, #0099cc);
            border-radius: 3px;
            margin-top: 10px;
            position: relative;
            z-index: 1;
        }

        /* ===== زر العودة ===== */
        .btn-back-sms {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }

        .btn-back-sms:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        /* ===== محتوى النموذج ===== */
        .sms-send-form-body {
            padding: 35px;
        }

        /* ===== عنوان القسم ===== */
        .form-section-title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
        }

        .form-section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #001f3f, #004080);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            box-shadow: 0 4px 15px rgba(0, 31, 63, 0.2);
        }

        .form-section-icon i {
            color: #ffffff;
            font-size: 1rem;
        }

        .form-section-text h5 {
            color: #001f3f;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 2px;
        }

        .form-section-text p {
            color: #8492a6;
            font-size: 0.82rem;
            margin-bottom: 0;
        }

        /* ===== بطاقة اختيار الفئة ===== */
        .recipient-type-card {
            background: #ffffff;
            border: 2px solid #eef1f6;
            border-radius: 16px;
            padding: 20px 25px;
            transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            height: 100%;
        }

        .recipient-type-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #001f3f, #004080);
            border-radius: 0 4px 4px 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .recipient-type-card:hover {
            border-color: #001f3f;
            box-shadow: 0 8px 30px rgba(0, 31, 63, 0.1);
            transform: translateY(-3px);
        }

        .recipient-type-card:hover::before {
            opacity: 1;
        }

        .recipient-type-card.selected-type {
            border-color: #001f3f;
            background: linear-gradient(135deg, #f0f4f8 0%, #ffffff 100%);
        }

        .recipient-type-card.selected-type::before {
            opacity: 1;
        }

        .recipient-type-card.warning-type {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }

        .recipient-type-card.warning-type::before {
            background: linear-gradient(180deg, #dc3545, #c82333);
            opacity: 1;
        }

        /* ===== أزرار الراديو المخصصة ===== */
        .custom-radio-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .custom-radio-input {
            appearance: none;
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            border: 2px solid #d1d9e6;
            border-radius: 50%;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .custom-radio-input:checked {
            border-color: #001f3f;
            background: #001f3f;
            box-shadow: 0 0 0 4px rgba(0, 31, 63, 0.1);
        }

        .custom-radio-input:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: #ffffff;
            border-radius: 50%;
        }

        .custom-radio-input.warning-radio:checked {
            border-color: #dc3545;
            background: #dc3545;
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
        }

        .recipient-type-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .recipient-type-icon.icon-selected {
            background: linear-gradient(135deg, #e8f4fd, #d1ecf9);
            color: #0066cc;
        }

        .recipient-type-icon.icon-all {
            background: linear-gradient(135deg, #fde8e8, #f9d1d1);
            color: #dc3545;
        }

        .recipient-type-icon i {
            font-size: 1.1rem;
        }

        .recipient-type-info h6 {
            color: #001f3f;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }

        .recipient-type-info .type-desc {
            color: #8492a6;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .recipient-type-info .type-desc.text-danger {
            color: #dc3545 !important;
            font-weight: 600;
        }

        /* ===== بطاقة اختيار المستخدمين ===== */
        .user-selection-card {
            background: #ffffff;
            border: 2px solid #eef1f6;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .user-selection-card:hover {
            border-color: #001f3f;
            box-shadow: 0 5px 20px rgba(0, 31, 63, 0.08);
        }

        .user-selection-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #001f3f;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .user-selection-label .label-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #e8f4fd, #d1ecf9);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0066cc;
        }

        .user-selection-label .label-icon i {
            font-size: 0.85rem;
        }

        /* ===== تنسيق Select2 ===== */
        .select2-container--default .select2-selection--multiple {
            border: 2px solid #eef1f6 !important;
            border-radius: 12px !important;
            min-height: 50px !important;
            padding: 5px !important;
            transition: all 0.3s ease;
            background: #fafbfc !important;
        }

        .select2-container--default .select2-selection--multiple:focus,
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #001f3f !important;
            box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.1) !important;
            background: #ffffff !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: linear-gradient(135deg, #001f3f, #004080) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 5px 12px !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            margin: 3px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff !important;
            margin-right: 6px !important;
            border-right: 1px solid rgba(255, 255, 255, 0.3) !important;
            padding-right: 6px !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }

        .select2-dropdown {
            border: 2px solid #001f3f !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 40px rgba(0, 31, 63, 0.15) !important;
            overflow: hidden;
        }

        .select2-search--dropdown .select2-search__field {
            border: 2px solid #eef1f6 !important;
            border-radius: 10px !important;
            padding: 10px 15px !important;
            font-size: 0.9rem !important;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #001f3f !important;
            box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.1) !important;
        }

        .select2-results__option {
            padding: 12px 18px !important;
            font-size: 0.9rem !important;
            transition: all 0.2s ease;
        }

        .select2-results__option--highlighted[aria-selected] {
            background: linear-gradient(135deg, #001f3f, #004080) !important;
        }

        .select2-container--default .select2-results__option--selected {
            background: #f0f4f8 !important;
            color: #001f3f !important;
        }

        /* ===== بطاقة نص الرسالة ===== */
        .message-card {
            background: #ffffff;
            border: 2px solid #eef1f6;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .message-card:hover {
            border-color: #001f3f;
            box-shadow: 0 5px 20px rgba(0, 31, 63, 0.08);
        }

        .message-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .message-label-text {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #001f3f;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .message-label-text .label-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #e8f8f0, #d1f5e3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #28a745;
        }

        .message-label-text .label-icon i {
            font-size: 0.85rem;
        }

        .custom-textarea {
            border: 2px solid #eef1f6 !important;
            border-radius: 12px !important;
            padding: 15px 18px !important;
            font-size: 0.95rem !important;
            line-height: 1.7 !important;
            resize: vertical !important;
            min-height: 140px !important;
            transition: all 0.3s ease !important;
            background: #fafbfc !important;
        }

        .custom-textarea:focus {
            border-color: #001f3f !important;
            box-shadow: 0 0 0 3px rgba(0, 31, 63, 0.1) !important;
            background: #ffffff !important;
        }

        .custom-textarea::placeholder {
            color: #b0b8c4 !important;
        }

        /* ===== عداد الأحرف ===== */
        .char-counter-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .char-counter {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: #8492a6;
            font-weight: 600;
        }

        .char-counter i {
            color: #001f3f;
        }

        .char-counter .count-number {
            color: #001f3f;
            font-weight: 700;
        }

        .char-counter.warning .count-number {
            color: #fd7e14;
        }

        .char-counter.danger .count-number {
            color: #dc3545;
        }

        .sms-parts-info {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: #8492a6;
            font-weight: 600;
        }

        .sms-parts-info i {
            color: #28a745;
        }

        /* ===== شريط التقدم ===== */
        .char-progress-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }

        .char-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            transition: all 0.3s ease;
            width: 0%;
        }

        .char-progress-fill.warning {
            background: linear-gradient(90deg, #fd7e14, #ffc107);
        }

        .char-progress-fill.danger {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }

        /* ===== زر الإرسال ===== */
        .btn-send-sms {
            background: linear-gradient(135deg, #001f3f 0%, #004080 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px 50px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 25px rgba(0, 31, 63, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn-send-sms::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: left 0.5s ease;
        }

        .btn-send-sms:hover::before {
            left: 100%;
        }

        .btn-send-sms:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 35px rgba(0, 31, 63, 0.35);
            color: #ffffff;
        }

        .btn-send-sms:active {
            transform: translateY(-1px);
        }

        .btn-send-sms:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ===== رسالة الخطأ ===== */
        .alert-danger-custom {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 1px solid #f1b0b7;
            border-radius: 14px;
            color: #721c24;
            padding: 16px 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            animation: slideDown 0.5s ease;
        }

        .alert-danger-custom i {
            font-size: 1.3rem;
            margin-right: 12px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== تنبيه "جميع المستخدمين" ===== */
        .all-users-alert {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            border: 2px solid #ffc107;
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-top: 20px;
            animation: slideDown 0.4s ease;
        }

        .all-users-alert .alert-icon {
            width: 40px;
            height: 40px;
            background: #ffc107;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .all-users-alert .alert-icon i {
            color: #ffffff;
            font-size: 1.1rem;
        }

        .all-users-alert .alert-content h6 {
            color: #856404;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .all-users-alert .alert-content p {
            color: #856404;
            font-size: 0.85rem;
            margin-bottom: 0;
            line-height: 1.5;
        }

        /* ===== التجاوب ===== */
        @media (max-width: 768px) {
            .sms-send-header {
                padding: 20px;
            }

            .sms-send-form-body {
                padding: 20px;
            }

            .recipient-type-card,
            .user-selection-card,
            .message-card {
                padding: 18px;
            }

            .btn-send-sms {
                width: 100%;
                padding: 14px;
            }
        }
    </style>

    <div class="sms-send-wrapper">
        <div class="container">
            <div class="sms-send-main-card">

                {{-- ===== رأس الصفحة ===== --}}
                <div class="sms-send-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <div class="header-icon">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div>
                                <h2 class="mb-0">إرسال رسالة SMS يدوية</h2>
                                <div class="title-line"></div>
                                <p class="subtitle mb-0">يمكنك إرسال رسائل نصية لمستخدمين محددين أو لجميع المستخدمين</p>
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('configuration.sms.logs') }}" class="btn btn-back-sms">
                                <i class="fas fa-arrow-right me-2"></i> العودة للسجل
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ===== محتوى النموذج ===== --}}
                <div class="sms-send-form-body">


                    @can('config.view')
                    <form action="{{ route('configuration.sms.send-manual') }}" method="POST" id="smsSendForm">
                    @endcan
                        @csrf

                        {{-- ===== قسم الفئة المستهدفة ===== --}}
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="form-section-text">
                                <h5>الفئة المستهدفة</h5>
                                <p>اختر من تريد إرسال الرسالة إليه</p>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            {{-- مستخدمين محددين --}}
                            <div class="col-md-6">
                                <label class="recipient-type-card selected-type" id="card-selected" for="type_selected">
                                    <div class="custom-radio-wrapper">
                                        <input class="custom-radio-input" type="radio" name="recipient_type"
                                            id="type_selected" value="selected" checked>
                                        <div class="recipient-type-icon icon-selected">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="recipient-type-info">
                                            <h6 class="mb-0">مستخدمين محددين</h6>
                                            <span class="type-desc">إرسال الرسالة لمستخدمين تختارهم من القائمة</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {{-- جميع المستخدمين --}}
                            <div class="col-md-6">
                                <label class="recipient-type-card" id="card-all" for="type_all">
                                    <div class="custom-radio-wrapper">
                                        <input class="custom-radio-input warning-radio" type="radio" name="recipient_type"
                                            id="type_all" value="all">
                                        <div class="recipient-type-icon icon-all">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="recipient-type-info">
                                            <h6 class="mb-0">جميع المستخدمين</h6>
                                            <span class="type-desc text-danger">النشطين الذين يملكون رقم هاتف</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- ===== قسم اختيار المستخدمين ===== --}}
                        <div id="user_selection_wrapper">
                            <div class="user-selection-card mb-4">
                                <div class="user-selection-label">
                                    <div class="label-icon">
                                        <i class="fas fa-user-friends"></i>
                                    </div>
                                    <span>اختر المستخدمين <span class="text-danger">*</span></span>
                                </div>
                                <select name="user_ids[]" id="user_ids" class="form-select select2-multiple" multiple
                                    data-placeholder="ابحث واختر المستخدمين...">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone }})</option>
                                    @endforeach
                                </select>
                                @error('user_ids')
                                    <div class="text-danger small mt-2">
                                        <i class="fas fa-exclamation-circle me-1"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        {{-- تنبيه "جميع المستخدمين" --}}
                        <div id="all_users_alert" style="display: none;">
                            <div class="all-users-alert mb-4">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="alert-content">
                                    <h6>تنبيه هام!</h6>
                                    <p>سيتم إرسال الرسالة إلى <strong>جميع المستخدمين النشطين</strong> الذين يملكون رقم هاتف
                                        مسجل في النظام. تأكد من محتوى الرسالة قبل الإرسال.</p>
                                </div>
                            </div>
                        </div>

                        {{-- ===== قسم نص الرسالة ===== --}}
                        <div class="form-section-title mt-4">
                            <div class="form-section-icon">
                                <i class="fas fa-comment-dots"></i>
                            </div>
                            <div class="form-section-text">
                                <h5>محتوى الرسالة</h5>
                                <p>اكتب نص الرسالة التي تريد إرسالها</p>
                            </div>
                        </div>

                        <div class="message-card mb-4">
                            <div class="message-label">
                                <div class="message-label-text">
                                    <div class="label-icon">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <span>نص الرسالة <span class="text-danger">*</span></span>
                                </div>
                            </div>
                            <textarea name="message" id="message_textarea" class="form-control custom-textarea" rows="5"
                                placeholder="اكتب رسالتك هنا... مثال: مرحباً بك في نظامنا، نتمنى لك تجربة مميزة." required
                                maxlength="500">{{ old('message') }}</textarea>

                            {{-- شريط التقدم --}}
                            <div class="char-progress-bar">
                                <div class="char-progress-fill" id="char_progress_fill"></div>
                            </div>

                            {{-- عداد الأحرف وعدد الرسائل --}}
                            <div class="char-counter-wrapper">
                                <div class="char-counter" id="char_counter">
                                    <i class="fas fa-font"></i>
                                    <span><span class="count-number" id="char_count">0</span> / 500 حرف</span>
                                </div>
                                <div class="sms-parts-info">
                                    <i class="fas fa-sms"></i>
                                    <span>عدد الرسائل: <strong id="sms_parts">0</strong></span>
                                </div>
                            </div>

                            @error('message')
                                <div class="text-danger small mt-2">
                                    <i class="fas fa-exclamation-circle me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- ===== زر الإرسال ===== --}}
                        <div class="col-12 mt-4 text-center">
                            @can('sms.manual.send')
                            <button type="submit" class="btn btn-send-sms" id="sendBtn">
                                <i class="fas fa-paper-plane me-2"></i> إرسال الرسالة الآن
                            </button>
                            @endcan
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- ===== JavaScript للتحديث التفاعلي ===== --}}
    <script>
        $(document).ready(function () {
            // تهيئة Select2
            if ($.fn.select2) {
                $('.select2-multiple').select2({
                    width: '100%',
                    dir: 'rtl',
                    allowClear: true,
                    closeOnSelect: false
                });
            }

            // تحديث عداد الأحرف عند تحميل الصفحة
            updateCharCounter();

            // التبديل بين نوع المستلمين
            $('input[name="recipient_type"]').change(function () {
                const value = $(this).val();

                // إزالة التحديد من جميع البطاقات
                $('.recipient-type-card').removeClass('selected-type warning-type');

                if (value === 'all') {
                    $('#card-all').addClass('warning-type');
                    $('#user_selection_wrapper').slideUp(300);
                    $('#user_ids').prop('required', false);
                    $('#all_users_alert').slideDown(300);
                } else {
                    $('#card-selected').addClass('selected-type');
                    $('#user_selection_wrapper').slideDown(300);
                    $('#user_ids').prop('required', true);
                    $('#all_users_alert').slideUp(300);
                }
            });

            // عداد الأحرف
            $('#message_textarea').on('input', function () {
                updateCharCounter();
            });

            // تأكيد قبل الإرسال
            $('#smsSendForm').on('submit', function (e) {
                const recipientType = $('input[name="recipient_type"]:checked').val();
                const message = $('#message_textarea').val().trim();

                if (!message) {
                    e.preventDefault();
                    alert('يرجى كتابة نص الرسالة أولاً.');
                    return false;
                }

                let confirmMsg = 'هل أنت متأكد من رغبتك في إرسال هذه الرسالة؟';
                if (recipientType === 'all') {
                    confirmMsg = '⚠️ سيتم إرسال الرسالة إلى جميع المستخدمين!\n\nهل أنت متأكد من رغبتك في المتابعة؟';
                }

                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return false;
                }

                // تعطيل زر الإرسال
                $('#sendBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> جاري الإرسال...');
            });
        });

        function updateCharCounter() {
            const textarea = document.getElementById('message_textarea');
            const length = textarea.value.length;
            const maxLength = 500;

            // تحديث العداد
            document.getElementById('char_count').textContent = length;

            // حساب عدد الرسائل (كل 160 حرف = رسالة)
            let parts = 0;
            if (length > 0) {
                parts = Math.ceil(length / 160);
            }
            document.getElementById('sms_parts').textContent = parts;

            // تحديث شريط التقدم
            const percentage = (length / maxLength) * 100;
            const progressFill = document.getElementById('char_progress_fill');
            progressFill.style.width = percentage + '%';

            // تحديث الألوان حسب النسبة
            const counter = document.getElementById('char_counter');
            counter.classList.remove('warning', 'danger');
            progressFill.classList.remove('warning', 'danger');

            if (percentage >= 90) {
                counter.classList.add('danger');
                progressFill.classList.add('danger');
            } else if (percentage >= 70) {
                counter.classList.add('warning');
                progressFill.classList.add('warning');
            }
        }
    </script>

@endsection
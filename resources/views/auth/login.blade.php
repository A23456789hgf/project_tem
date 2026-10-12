<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>تسجيل الدخول - نظام إدارة المشاريع</title>

    <!-- إضافة Bootstrap و Font Awesome بدلاً من Vite -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
        }

        /* ========== إعادة تعيين كاملة للهوامش والارتفاع ========== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            line-height: 1.5;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* ========== قسم النموذج ========== */
        .form-section {
            flex: 1;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.75rem, 3vw, 2rem);
            position: relative;
            order: 1;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-card-modern {
            width: 100%;
            max-width: 420px;
            padding: clamp(1.25rem, 3vw, 2rem);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: clamp(12px, 2vw, 20px);
            box-shadow: 0 15px 45px rgba(8, 33, 76, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.08);
            position: relative;
            z-index: 10;
        }

        .form-header {
            margin-bottom: 1.25rem;
            text-align: right;
        }

        .welcome-text {
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.35rem;
            position: relative;
            display: inline-block;
        }

        .welcome-text::after {
            content: '';
            position: absolute;
            bottom: -6px;
            right: 0;
            width: 45px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-gold-light));
            border-radius: 2px;
            transition: var(--transition);
        }

        .instruction-text {
            color: var(--text-light);
            font-size: clamp(0.8rem, 2vw, 0.85rem);
            margin-top: 0.6rem;
            margin-bottom: 0;
        }

        /* ========== حقول الإدخال ========== */
        .custom-input-group {
            position: relative;
            margin-bottom: 0.85rem;
        }

        .custom-input-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-dark);
            text-align: right;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper>.input-icon {
            position: absolute;
            right: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: var(--transition);
            z-index: 5;
            pointer-events: none;
            font-size: 0.9rem;
        }

        #password {
            padding-left: 2.5rem;
        }

        .custom-form-control {
            width: 100%;
            padding: 0.55rem 2.2rem 0.55rem 0.7rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.85rem;
            transition: var(--transition);
            background: #f8fafc;
            font-family: inherit;
            text-align: right;
            height: 40px;
        }

        .custom-form-control:focus {
            background: white;
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        .custom-form-control:focus+.input-icon {
            color: var(--accent-gold);
        }

        .custom-form-control::placeholder {
            color: #94a3b8;
            opacity: 1;
            text-align: right;
            font-size: 0.8rem;
        }

        .password-toggle {
            position: absolute;
            left: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            z-index: 10;
            transition: var(--transition);
            padding: 0.2rem;
            border-radius: 4px;
            background: transparent;
            border: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
        }

        .password-toggle:hover {
            color: var(--primary-dark);
            background: rgba(26, 75, 140, 0.05);
        }

        /* ========== خيارات إضافية ========== */
        .actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--text-dark);
            cursor: pointer;
            user-select: none;
        }

        .custom-checkbox-wrapper {
            position: relative;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .custom-checkbox-wrapper input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .custom-checkbox {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            border: 2px solid #cbd5e1;
            border-radius: 4px;
            background: white;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox-wrapper input:checked+.custom-checkbox {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .custom-checkbox-wrapper input:checked+.custom-checkbox::after {
            content: '';
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
            margin-bottom: 1px;
        }

        .forgot-link {
            font-size: 0.78rem;
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            padding: 0.2rem 0.4rem;
            border-radius: 5px;
            min-height: 32px;
            display: inline-flex;
            align-items: center;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            background: rgba(26, 75, 140, 0.08);
            text-decoration: none;
        }

        /* ========== زر الإرسال ========== */
        .submit-btn {
            width: 100%;
            padding: 0.65rem;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            box-shadow: 0 4px 12px rgba(8, 33, 76, 0.25);
            position: relative;
            overflow: hidden;
            height: 42px;
            min-height: 42px;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.25), transparent);
            transition: right 0.6s ease;
        }

        .submit-btn:hover::before {
            right: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(8, 33, 76, 0.35);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .submit-btn .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========== رسائل الخطأ ========== */
        .error-msg {
            color: #ef4444;
            font-size: 0.72rem;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 4px;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%,
            100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-4px);
            }
            75% {
                transform: translateX(4px);
            }
        }

        /* ========== القسم الأيسر (الهوية) ========== */
        .brand-section {
            flex: 1.1;
            background: linear-gradient(135deg, var(--primary-dark) 0%, #0f2d5e 50%, #08214c 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            overflow: hidden;
            padding: clamp(0.75rem, 2vw, 1.5rem);
            z-index: 1;
            order: 2;
        }

        .brand-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23d4af37' stroke-width='0.5' opacity='0.12'%3E%3Cpath d='M30 0L60 30L30 60L0 30Z'/%3E%3Cpath d='M30 8L52 30L30 52L8 30Z'/%3E%3Ccircle cx='30' cy='30' r='12'/%3E%3C/g%3E%3C/svg%3E");
        }

        .brand-section::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 50%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .decorative-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            z-index: 0;
            animation: float 20s infinite ease-in-out;
        }

        .c-1 {
            width: clamp(250px, 45vw, 500px);
            height: clamp(250px, 45vw, 500px);
            top: clamp(-120px, -18vh, -80px);
            right: clamp(-120px, -18vw, -80px);
        }

        .c-2 {
            width: clamp(180px, 35vw, 350px);
            height: clamp(180px, 35vw, 350px);
            bottom: clamp(-80px, -12vh, -40px);
            left: clamp(-80px, -12vw, -40px);
            background: rgba(212, 175, 55, 0.06);
            animation-delay: -10s;
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-15px) scale(1.01);
            }
        }

        .brand-content {
            text-align: center;
            position: relative;
            z-index: 2;
            padding: 0.5rem;
            animation: fadeInUp 0.8s ease-out;
            max-width: 100%;
        }

        .brand-content h1,
        .brand-content h2,
        .brand-content p {
            margin-top: 0;
            margin-bottom: 0.5rem;
        }

        .logo-container {
            width: clamp(110px, 20vw, 220px);
            height: clamp(110px, 20vw, 220px);
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.25), 0 0 0 3px rgba(212, 175, 55, 0.25);
            border: clamp(2px, 0.4vw, 4px) solid var(--accent-gold);
            padding: clamp(10px, 1.5vw, 18px);
            transition: var(--transition);
            flex-shrink: 0;
            position: relative;
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent-gold), transparent, var(--accent-gold));
            opacity: 0.25;
            z-index: -1;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%,
            100% {
                opacity: 0.25;
                transform: scale(1);
            }
            50% {
                opacity: 0.4;
                transform: scale(1.03);
            }
        }

        .logo-container:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 20px 55px rgba(212, 175, 55, 0.4), 0 0 0 3px rgba(212, 175, 55, 0.4);
        }

        .logo-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .ministry-title {
            font-size: clamp(0.95rem, 2.2vw, 1.35rem);
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            line-height: 1.4;
        }

        .system-subtitle {
            font-size: clamp(0.85rem, 1.8vw, 1.05rem);
            color: var(--accent-gold);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-gold), transparent);
            margin: 0.75rem auto;
        }

        /* ========== Modal استعادة كلمة المرور ========== */
        .modal-content {
            border: none;
            border-radius: 14px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
            font-size: 0.85rem;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: var(--bg-light);
            border-radius: 14px 14px 0 0;
            padding: 0.75rem 1rem;
        }

        .modal-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .modal-body {
            padding: 1rem;
            max-height: 70dvh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 0.6rem 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .modal .form-control {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            height: 38px;
        }

        .modal .form-control:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .modal .form-label {
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            color: var(--text-dark);
        }

        .modal .btn-primary {
            background: var(--primary-dark);
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .modal .btn-secondary {
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            height: 38px;
        }

        .modal .alert {
            font-size: 0.78rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .modal p.text-muted {
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
        }

        /* ========================================================= */
        /* ========== التجاوب الشامل (جميع الأحجام) ========== */
        /* ========================================================= */

        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column-reverse;
            }

            .brand-section {
                flex: 0 0 auto;
                min-height: clamp(160px, 28vh, 260px);
                padding: 1rem;
                order: 2;
            }

            .form-section {
                flex: 1;
                padding: 1.25rem 1rem;
                order: 1;
                min-height: auto;
            }

            .login-card-modern {
                max-width: 100%;
                padding: 1.5rem;
            }

            .decorative-circle {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .brand-section {
                min-height: 180px;
                padding: 0.8rem;
            }

            .logo-container {
                width: 95px;
                height: 95px;
                margin-bottom: 0.75rem;
                padding: 10px;
            }

            .ministry-title {
                font-size: 0.9rem;
            }

            .system-subtitle {
                font-size: 0.8rem;
            }

            .login-card-modern {
                padding: 1.25rem;
            }

            .form-section {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .brand-section {
                min-height: 140px;
                padding: 0.6rem;
            }

            .logo-container {
                width: 75px;
                height: 75px;
                margin-bottom: 0.5rem;
                padding: 8px;
            }

            .ministry-title {
                font-size: 0.78rem;
            }

            .system-subtitle {
                font-size: 0.7rem;
            }

            .form-section {
                padding: 0.75rem;
            }

            .login-card-modern {
                padding: 1.1rem;
                border-radius: 14px;
                box-shadow: 0 10px 30px rgba(8, 33, 76, 0.06);
            }

            .welcome-text {
                font-size: 1.15rem;
            }

            .instruction-text {
                font-size: 0.78rem;
            }

            .custom-form-control {
                font-size: 16px !important;
                height: 38px;
                padding: 0.45rem 2rem 0.45rem 0.6rem;
            }

            .custom-input-group label {
                font-size: 0.75rem;
            }

            .actions-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.6rem;
            }

            .forgot-link {
                align-self: flex-end;
                font-size: 0.75rem;
                padding: 0.4rem 0.2rem;
                min-height: 40px;
            }

            .remember-me {
                font-size: 0.75rem;
                min-height: 40px;
            }

            .submit-btn {
                font-size: 0.85rem;
                height: 40px;
                min-height: 40px;
                padding: 0.5rem;
            }

            .password-toggle {
                width: 32px;
                height: 32px;
                left: 0.3rem;
            }

            .divider {
                width: 40px;
                margin: 0.5rem auto;
            }

            .modal-body {
                padding: 0.75rem;
            }
        }

        @media (max-width: 400px) {
            .brand-section {
                min-height: 100px;
                padding: 0.4rem;
            }

            .logo-container {
                width: 60px;
                height: 60px;
                margin-bottom: 0.3rem;
                padding: 6px;
                border-width: 2px;
            }

            .ministry-title {
                font-size: 0.65rem;
            }

            .system-subtitle {
                font-size: 0.6rem;
            }

            .login-card-modern {
                padding: 0.8rem;
                border-radius: 12px;
            }

            .welcome-text {
                font-size: 1rem;
            }

            .instruction-text {
                font-size: 0.7rem;
                margin-top: 0.4rem;
            }

            .custom-input-group {
                margin-bottom: 0.6rem;
            }

            .custom-form-control {
                font-size: 16px !important;
                height: 36px;
                padding: 0.4rem 1.8rem 0.4rem 0.5rem;
            }

            .submit-btn {
                font-size: 0.8rem;
                height: 38px;
                min-height: 38px;
                border-radius: 8px;
            }

            .form-header {
                margin-bottom: 0.8rem;
            }

            .actions-row {
                margin-bottom: 0.8rem;
                gap: 0.3rem;
            }

            .divider {
                width: 30px;
            }
        }

        @media (max-height: 650px) and (max-width: 576px) {
            .login-wrapper {
                min-height: 100dvh;
            }

            .brand-section {
                min-height: 90px;
                padding: 0.3rem;
            }

            .logo-container {
                width: 55px;
                height: 55px;
                margin-bottom: 0.2rem;
                padding: 5px;
            }

            .ministry-title {
                font-size: 0.6rem;
                margin-bottom: 0.1rem;
            }

            .system-subtitle {
                font-size: 0.55rem;
            }

            .divider {
                margin: 0.2rem auto;
                width: 25px;
                height: 1.5px;
            }

            .form-section {
                padding: 0.4rem;
            }

            .login-card-modern {
                padding: 0.6rem;
                border-radius: 10px;
            }

            .form-header {
                margin-bottom: 0.5rem;
            }

            .welcome-text {
                font-size: 0.9rem;
            }
            .welcome-text::after {
                bottom: -3px;
                height: 2px;
                width: 30px;
            }

            .instruction-text {
                font-size: 0.65rem;
                margin-top: 0.2rem;
            }

            .custom-input-group {
                margin-bottom: 0.4rem;
            }
            .custom-input-group label {
                font-size: 0.65rem;
                margin-bottom: 0.1rem;
            }

            .custom-form-control {
                height: 32px;
                padding: 0.2rem 1.5rem 0.2rem 0.4rem;
                font-size: 14px !important;
                border-radius: 6px;
            }

            .actions-row {
                margin-bottom: 0.5rem;
                gap: 0.2rem;
            }

            .submit-btn {
                height: 34px;
                min-height: 34px;
                font-size: 0.75rem;
                border-radius: 6px;
                padding: 0.3rem;
            }

            .forgot-link {
                font-size: 0.65rem;
                min-height: 30px;
                padding: 0.2rem;
            }

            .remember-me {
                font-size: 0.65rem;
                min-height: 30px;
            }

            .password-toggle {
                width: 26px;
                height: 26px;
                left: 0.2rem;
            }
        }

        @media (min-width: 1400px) {
            .brand-content {
                transform: scale(1.05);
            }

            .login-card-modern {
                max-width: 440px;
                padding: 2.2rem;
            }

            .form-section {
                padding: 2rem;
            }

            .brand-section {
                flex: 1.2;
            }
        }

        @media (min-width: 2000px) {
            .login-card-modern {
                max-width: 500px;
                padding: 2.5rem;
            }

            .brand-section {
                flex: 1.5;
            }

            .logo-container {
                width: 280px;
                height: 280px;
            }

            .ministry-title {
                font-size: 1.8rem;
            }

            .system-subtitle {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <!-- قسم النموذج -->
        <div class="form-section">
            <div class="login-card-modern">
                <div class="form-header">
                    <h2 class="welcome-text">مرحباً بك مجدداً</h2>
                    <p class="instruction-text">سجل الدخول للمتابعة إلى لوحة التحكم</p>
                </div>

                <form action="{{ route('auth.login') }}" method="POST" id="loginForm" novalidate>
                    @csrf

                    <div class="custom-input-group">
                        <label for="phone">رقم الهاتف</label>
                        <div class="input-wrapper">
                            <input type="tel" id="phone" name="phone" class="custom-form-control"
                                placeholder="أدخل رقم الهاتف" value="{{ old('phone') }}" required autofocus
                                autocomplete="tel" inputmode="tel">
                            <!-- أيقونة Font Awesome -->
                            <i class="fas fa-phone-alt input-icon"></i>
                        </div>
                        <div class="error-msg" role="alert" style="{{ $errors->has('phone') || $errors->has('username') ? 'display: flex;' : 'display: none;' }}">
                            <i class="fas fa-exclamation-circle" style="width: 12px; height: 12px;"></i>
                            <span class="error-text">{{ $errors->first('phone') ?: $errors->first('username') }}</span>
                        </div>
                    </div>

                    <div class="custom-input-group">
                        <label for="password">كلمة المرور</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="custom-form-control"
                                placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()"
                                aria-label="إظهار/إخفاء كلمة المرور">
                                <i class="fas fa-eye" id="toggleIconEye" style="font-size: 14px;"></i>
                                <i class="fas fa-eye-slash" id="toggleIconEyeSlash"
                                    style="font-size: 14px; display: none;"></i>
                            </button>
                        </div>
                        <div class="error-msg" role="alert" style="{{ $errors->has('password') || $errors->has('error') ? 'display: flex;' : 'display: none;' }}">
                            <i class="fas fa-exclamation-circle" style="width: 12px; height: 12px;"></i>
                            <span class="error-text">{{ $errors->first('password') ?: $errors->first('error') }}</span>
                        </div>
                    </div>

                    <div class="actions-row">
                        <label class="remember-me">
                            <div class="custom-checkbox-wrapper">
                                <input type="checkbox" name="remember" id="remember" checked>
                                <span class="custom-checkbox"></span>
                            </div>
                            <span>تذكرني</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="forgot-link">نسيت كلمة المرور؟</a>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <div class="spinner" id="btnSpinner"></div>
                        <span id="btnText">تسجيل الدخول</span>
                        <i class="fas fa-arrow-left" id="btnIcon" style="font-size: 14px;"></i>
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small style="font-size: 0.72rem; color: var(--text-light);">
                        تواجه مشكلة؟
                        <a href="#" class="text-decoration-none" style="color: var(--primary-light); font-weight: 600;">
                            تواصل مع الدعم الفني
                        </a>
                    </small>
                </div>
            </div>
        </div>

        <!-- قسم الهوية البصرية -->
        <div class="brand-section">
            <div class="decorative-circle c-1"></div>
            <div class="decorative-circle c-2"></div>

            <div class="brand-content">
                <div class="logo-container">
                    <!-- تم استبدال asset() بمسار ثابت -->
                    <img src="{{ asset('images/logo.png') }}" alt="شعار وزارة الزراعة والثروة السمكية والموارد المائية">
                </div>
                <h1 class="ministry-title">وزارة الزراعة والثروة السمكية والموارد المائية</h1>
                <div class="divider"></div>
                <h2 class="system-subtitle">نظام إدارة وتمويل المشاريع</h2>
            </div>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('toggleIconEye');
            const eyeSlashIcon = document.getElementById('toggleIconEyeSlash');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.style.display = 'none';
                eyeSlashIcon.style.display = 'inline-block';
                eyeIcon.parentElement.setAttribute('aria-label', 'إخفاء كلمة المرور');
            } else {
                passwordField.type = 'password';
                eyeIcon.style.display = 'inline-block';
                eyeSlashIcon.style.display = 'none';
                eyeIcon.parentElement.setAttribute('aria-label', 'إظهار كلمة المرور');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // فحص بسيط للتحقق من صحة الحقول
            const phoneInput = document.getElementById('phone') || document.getElementById('username');
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const password = document.getElementById('password').value.trim();

            // إخفاء أي رسائل خطأ سابقة تمت إضافتها بواسطة JS
            document.querySelectorAll('.error-msg').forEach(el => {
                // If it doesn't have a server-side error, hide it
                if (!el.textContent.trim()) {
                    el.style.display = 'none';
                }
            });

            let hasError = false;
            if (!phone) {
                const phoneError = document.querySelector('.custom-input-group:first-child .error-msg');
                phoneError.querySelector('.error-text').textContent = 'يرجى إدخال رقم الهاتف';
                phoneError.style.display = 'flex';
                hasError = true;
            }
            if (!password) {
                const passError = document.querySelector('.custom-input-group:nth-child(2) .error-msg');
                passError.querySelector('.error-text').textContent = 'كلمة المرور مطلوبة';
                passError.style.display = 'flex';
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
                return;
            }

            // إظهار حالة التحميل
            const btn = document.getElementById('submitBtn');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            const icon = document.getElementById('btnIcon');

            btn.disabled = true;
            btn.style.opacity = '0.85';
            text.textContent = 'جاري التحقق...';
            spinner.style.display = 'block';
            icon.style.display = 'none';

            // السماح للنموذج بالإرسال بشكل طبيعي
        });

        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('.custom-form-control');
            if (firstInput && window.innerWidth < 768) {
                setTimeout(() => firstInput.focus(), 300);
            }
        });
    </script>

    <!-- Modal استعادة كلمة المرور -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">استعادة كلمة المرور</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"
                        style="margin: 0; margin-left: auto;"></button>
                </div>
                <div class="modal-body">

                    <!-- خطوة 1 -->
                    <div id="step-phone">
                        <p class="text-muted">أدخل اسم المستخدم أو رقم الهاتف المسجل لإرسال رمز التحقق.</p>
                        <div class="mb-2">
                            <label class="form-label">اسم المستخدم أو رقم الهاتف</label>
                            <input type="text" id="reset-username" class="form-control"
                                placeholder="أدخل اسم المستخدم أو رقم الهاتف" dir="ltr" style="text-align: right;" inputmode="text">
                        </div>
                        <div class="d-grid mt-3">
                            <button type="button" class="btn btn-primary" id="btn-send-otp">
                                <span id="text-send-otp">إرسال رمز التحقق</span>
                                <div class="spinner-border spinner-border-sm text-light ms-2" id="spinner-send-otp"
                                    role="status" style="display: none;"></div>
                            </button>
                        </div>
                    </div>

                    <!-- خطوة 2 -->
                    <div id="step-otp" style="display: none;">
                        <p class="text-muted">أدخل رمز التحقق المرسل إلى هاتفك.</p>
                        <div class="mb-2">
                            <label class="form-label">رمز التحقق</label>
                            <input type="text" id="reset-otp" class="form-control text-center"
                                placeholder="6 أرقام" maxlength="6" dir="ltr"
                                style="letter-spacing: 4px; font-weight: bold; font-size: 1rem;" inputmode="numeric">
                        </div>
                        <div class="d-grid mt-3">
                            <button type="button" class="btn btn-primary" id="btn-verify-otp">
                                <span id="text-verify-otp">تحقق من الرمز</span>
                                <div class="spinner-border spinner-border-sm text-light ms-2" id="spinner-verify-otp"
                                    role="status" style="display: none;"></div>
                            </button>
                        </div>
                    </div>

                    <!-- خطوة 3 -->
                    <div id="step-new-password" style="display: none;">
                        <p class="text-muted">أدخل كلمة المرور الجديدة.</p>
                        <div class="mb-2">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" id="reset-new-password" class="form-control" placeholder="••••••••">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" id="reset-confirm-password" class="form-control"
                                placeholder="••••••••">
                        </div>
                        <div class="d-grid mt-3">
                            <button type="button" class="btn btn-primary" id="btn-reset-password">
                                <span id="text-reset-password">تغيير كلمة المرور</span>
                                <div class="spinner-border spinner-border-sm text-light ms-2" id="spinner-reset-password"
                                    role="status" style="display: none;"></div>
                            </button>
                        </div>
                    </div>

                    <div id="reset-alert" class="alert mt-2" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // محاكاة لاستعادة كلمة المرور (بدون خادم)
            const btnSendOtp = document.getElementById('btn-send-otp');
            const btnVerifyOtp = document.getElementById('btn-verify-otp');
            const btnResetPassword = document.getElementById('btn-reset-password');
            const resetAlert = document.getElementById('reset-alert');

            function showAlert(message, type = 'danger') {
                resetAlert.className = `alert alert-${type} mt-2`;
                resetAlert.innerHTML = message;
                resetAlert.style.display = 'block';
            }

            function toggleLoading(btnId, isLoading) {
                const text = document.getElementById(`text-${btnId}`);
                const spinner = document.getElementById(`spinner-${btnId}`);
                const btn = document.getElementById(`btn-${btnId}`);
                if (isLoading) {
                    btn.disabled = true;
                    spinner.style.display = 'inline-block';
                } else {
                    btn.disabled = false;
                    spinner.style.display = 'none';
                }
            }

            btnSendOtp.addEventListener('click', function() {
                const username = document.getElementById('reset-username').value.trim();
                if (!username) { showAlert('يرجى إدخال اسم المستخدم أو رقم الهاتف أو البريد الإلكتروني'); return; }
                resetAlert.style.display = 'none';
                toggleLoading('send-otp', true);
                
                fetch('{{ route('password.otp.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ username: username })
                })
                .then(response => response.json())
                .then(data => {
                    toggleLoading('send-otp', false);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        document.getElementById('step-phone').style.display = 'none';
                        document.getElementById('step-otp').style.display = 'block';
                    } else {
                        showAlert(data.message || 'حدث خطأ غير متوقع');
                    }
                })
                .catch(error => {
                    toggleLoading('send-otp', false);
                    showAlert('حدث خطأ في الاتصال بالخادم');
                });
            });

            btnVerifyOtp.addEventListener('click', function() {
                const username = document.getElementById('reset-username').value.trim();
                const otp = document.getElementById('reset-otp').value.trim();
                if (!otp || otp.length !== 6) { showAlert('أدخل رمز صحيح من 6 أرقام'); return; }
                resetAlert.style.display = 'none';
                toggleLoading('verify-otp', true);

                fetch('{{ route('password.otp.verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ username: username, otp: otp })
                })
                .then(response => response.json())
                .then(data => {
                    toggleLoading('verify-otp', false);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        document.getElementById('step-otp').style.display = 'none';
                        document.getElementById('step-new-password').style.display = 'block';
                    } else {
                        showAlert(data.message || 'الرمز غير صحيح');
                    }
                })
                .catch(error => {
                    toggleLoading('verify-otp', false);
                    showAlert('حدث خطأ في الاتصال بالخادم');
                });
            });

            btnResetPassword.addEventListener('click', function() {
                const username = document.getElementById('reset-username').value.trim();
                const otp = document.getElementById('reset-otp').value.trim();
                const newPassword = document.getElementById('reset-new-password').value;
                const confirmPassword = document.getElementById('reset-confirm-password').value;
                if (!newPassword || newPassword.length < 6) { showAlert('كلمة المرور 6 أحرف على الأقل'); return; }
                if (newPassword !== confirmPassword) { showAlert('تأكيد كلمة المرور غير متطابق'); return; }
                resetAlert.style.display = 'none';
                toggleLoading('reset-password', true);

                fetch('{{ route('password.otp.reset') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        username: username,
                        otp: otp,
                        password: newPassword,
                        password_confirmation: confirmPassword
                    })
                })
                .then(response => response.json())
                .then(data => {
                    toggleLoading('reset-password', false);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
                            if (modal) modal.hide();
                        }, 1500);
                    } else {
                        // Display validation errors if any
                        if (data.errors) {
                            const errorMsg = Object.values(data.errors).flat().join('<br>');
                            showAlert(errorMsg);
                        } else {
                            showAlert(data.message || 'حدث خطأ غير متوقع');
                        }
                    }
                })
                .catch(error => {
                    toggleLoading('reset-password', false);
                    showAlert('حدث خطأ في الاتصال بالخادم');
                });
            });

            // إعادة تعيين النموذج عند إغلاق المودال
            const forgotPasswordModal = document.getElementById('forgotPasswordModal');
            forgotPasswordModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('step-phone').style.display = 'block';
                document.getElementById('step-otp').style.display = 'none';
                document.getElementById('step-new-password').style.display = 'none';
                document.getElementById('reset-username').value = '';
                document.getElementById('reset-otp').value = '';
                document.getElementById('reset-new-password').value = '';
                document.getElementById('reset-confirm-password').value = '';
                resetAlert.style.display = 'none';
            });
        });
    </script>

</body>

</html>
\\
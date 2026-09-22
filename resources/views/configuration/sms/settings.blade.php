@extends('layouts.app')

@section('content')
    @include('configuration.shared_styles')

    <style>
        .sms-settings-wrapper {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            padding: 30px 0;
        }

        .sms-main-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 31, 63, 0.08);
            border: 1px solid rgba(0, 31, 63, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .sms-page-header {
            background: linear-gradient(135deg, #001f3f 0%, #003366 50%, #004080 100%);
            padding: 30px 35px;
            position: relative;
            overflow: hidden;
        }

        .sms-page-header h2 {
            color: #ffffff !important;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .sms-page-header .header-icon {
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

        .sms-page-header .header-icon i {
            font-size: 1.3rem;
            color: #ffffff;
        }

        .sms-page-header .subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-top: 6px;
        }

        .sms-page-header .title-line {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #00d4ff, #0099cc);
            border-radius: 3px;
            margin-top: 10px;
        }

        .btn-back-sms {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-back-sms:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff !important;
            transform: translateY(-2px);
        }

        .sms-form-body {
            padding: 35px;
        }

        .section-title-wrapper {
            display: flex;
            align-items: center;
            margin-top: 15px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
        }

        .section-title-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #001f3f, #004080);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            box-shadow: 0 4px 15px rgba(0, 31, 63, 0.2);
        }

        .section-title-icon i {
            color: #ffffff;
            font-size: 1rem;
        }

        .section-title-text h5 {
            color: #001f3f;
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 2px;
        }

        .event-card {
            background: #ffffff;
            border: 2px solid #eef1f6;
            border-radius: 16px;
            padding: 22px 25px;
            transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .event-card:hover {
            border-color: #001f3f;
            box-shadow: 0 8px 30px rgba(0, 31, 63, 0.1);
            transform: translateY(-4px);
        }

        .event-card.active-event {
            border-color: #28a745;
            background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
        }

        .event-card.required-event {
            border-color: #17a2b8;
            background: linear-gradient(135deg, #e3f7fc 0%, #ffffff 100%);
        }

        .event-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            background: linear-gradient(135deg, #e8f4fd, #d1ecf9);
            color: #0066cc;
        }

        .event-info h6 {
            color: #001f3f;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .event-info .event-desc {
            color: #8492a6;
            font-size: 0.82rem;
            line-height: 1.5;
            display: block;
        }

        .event-info .recipient-tag {
            font-size: 0.75rem;
            color: #004080;
            background: #e6f0fa;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 4px;
        }

        .form-check-input.custom-sms-switch {
            width: 52px;
            height: 28px;
            cursor: pointer;
            border: 2px solid #d1d9e6;
            background-color: #e9ecef;
            transition: all 0.3s ease;
        }

        .form-check-input.custom-sms-switch:checked {
            background-color: #28a745;
            border-color: #28a745;
        }

        .status-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 6px;
        }

        .status-badge.active {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-badge.inactive {
            background: rgba(132, 146, 166, 0.1);
            color: #8492a6;
        }

        .status-badge.required {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .btn-save-sms {
            background: linear-gradient(135deg, #001f3f 0%, #004080 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 6px 25px rgba(0, 31, 63, 0.25);
        }

        .btn-save-sms:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 35px rgba(0, 31, 63, 0.35);
            color: #ffffff;
        }

        .events-counter {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 12px 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .events-counter .count-number {
            background: linear-gradient(135deg, #001f3f, #004080);
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .events-counter .count-label {
            color: #495057;
            font-weight: 600;
            font-size: 0.85rem;
        }
    </style>

    <div class="sms-settings-wrapper">
        <div class="container">
            <div class="sms-main-card">

                {{-- ===== رأس الصفحة ===== --}}
                <div class="sms-page-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <div class="header-icon">
                                <i class="fas fa-sms"></i>
                            </div>
                            <div>
                                <h2 class="mb-0">إعدادات أحداث الرسائل النصية</h2>
                                <div class="title-line"></div>
                                <p class="subtitle mb-0">التحكم المركزي التفاعلي لإشعارات SMS على مستوى أقسام النظام</p>
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
                <div class="sms-form-body">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @can('config.view')
                    <form action="{{ route('configuration.sms.update-settings') }}" method="POST">
                    @endcan
                        @csrf

                        {{-- عداد الأحداث المفعّلة --}}
                        <div class="events-counter">
                            <div class="count-number" id="activeEventsCount">0</div>
                            <div class="count-label">أحداث مفعّلة حالياً في النظام</div>
                        </div>

                        {{-- ===== تكرار الأقسام والأحداث ديناميكياً من الـ Registry ===== --}}
                        @foreach($registry as $moduleKey => $module)
                            <div class="section-title-wrapper">
                                <div class="section-title-icon">
                                    <i class="{{ $module['icon'] ?? 'fas fa-cog' }}"></i>
                                </div>
                                <div class="section-title-text">
                                    <h5>{{ $module['label'] ?? $moduleKey }}</h5>
                                    <p>إدارة الأحداث التابعة لقسم {{ $module['label'] ?? $moduleKey }}</p>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                @foreach($module['events'] as $eventKey => $event)
                                    @php
                                        $isEnabled = $eventManager->enabled($eventKey);
                                        $isRequired = !empty($event['required']);
                                        $elementId = str_replace('.', '-', $eventKey);
                                    @endphp

                                    <div class="col-md-6">
                                        <div class="event-card {{ $isRequired ? 'required-event' : ($isEnabled ? 'active-event' : '') }}"
                                            id="card-{{ $elementId }}">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <div class="event-icon-wrapper">
                                                        <i class="{{ $module['icon'] ?? 'fas fa-bell' }}"></i>
                                                    </div>
                                                    <div class="event-info">
                                                        <h6 class="mb-0">{{ $event['label'] }}</h6>
                                                        <span class="event-desc">{{ $event['description'] ?? '' }}</span>
                                                        @if(!empty($event['recipient']))
                                                            <span class="recipient-tag">
                                                                <i class="fas fa-user-tag me-1"></i> المستلم: {{ $event['recipient'] }}
                                                            </span>
                                                        @endif
                                                        <br>
                                                        <span class="status-badge {{ $isRequired ? 'required' : ($isEnabled ? 'active' : 'inactive') }}"
                                                            id="badge-{{ $elementId }}">
                                                            @if($isRequired)
                                                                ● مفعّل دائماً (مطلب أمان)
                                                            @else
                                                                {{ $isEnabled ? '● مفعّل' : '○ معطّل' }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="custom-switch-wrapper">
                                                    <div class="form-check form-switch">
                                                        @if($isRequired)
                                                            <input class="form-check-input custom-sms-switch" type="checkbox"
                                                                checked disabled id="switch-{{ $elementId }}">
                                                        @else
                                                            <input class="form-check-input custom-sms-switch" type="checkbox"
                                                                name="events[{{ $eventKey }}]" value="1"
                                                                id="switch-{{ $elementId }}"
                                                                {{ $isEnabled ? 'checked' : '' }}
                                                                onchange="updateEventCard('{{ $elementId }}', this)">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        {{-- زر الحفظ --}}
                        <div class="col-12 mt-4 text-center">
                            @can('sms.settings.update')
                            <button type="submit" class="btn btn-save-sms">
                                <i class="fas fa-save me-2"></i> حفظ إعدادات الـ SMS
                            </button>
                            @endcan
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            updateCounter();
        });

        function updateEventCard(elementId, checkbox) {
            const card = document.getElementById('card-' + elementId);
            const badge = document.getElementById('badge-' + elementId);

            if (checkbox.checked) {
                card.classList.add('active-event');
                badge.className = 'status-badge active';
                badge.textContent = '● مفعّل';
            } else {
                card.classList.remove('active-event');
                badge.className = 'status-badge inactive';
                badge.textContent = '○ معطّل';
            }

            updateCounter();
        }

        function updateCounter() {
            const switches = document.querySelectorAll('.custom-sms-switch');
            let count = 0;
            switches.forEach(sw => {
                if (sw.checked) count++;
            });
            const counterEl = document.getElementById('activeEventsCount');
            if (counterEl) {
                counterEl.textContent = count;
            }
        }
    </script>

@endsection
@extends('layouts.app')

@section('title', __('إدارة صلاحيات الجهات'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-radius-15">
            <div class="card-header bg-white border-0 py-4">
                <div class="d-flex align-items-center">
                    <div class="icon-shape bg-soft-primary text-primary rounded-circle me-3">
                        <i class="fas fa-sitemap fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold">{{ __('إدارة صلاحيات الجهات') }}</h4>
                        <p class="text-muted mb-0 small">{{ __('تحكم في مصادر الجهات وظهورها في النظام') }}</p>
                    </div>
                </div>
            </div>

            <div class="card-body px-4 pb-5">
                @can('entity-authorities.view')
                <form action="{{ route('entity-authorities.update') }}" method="POST">
                @endcan
                    @csrf
                    
                    <div class="row mb-5">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="p-4 bg-light rounded-20 h-100">
                                <h5 class="fw-bold mb-3 border-bottom pb-2">
                                    <i class="fas fa-database text-primary me-2"></i>
                                    {{ __('مصدر الجهات النشط') }}
                                </h5>
                                <p class="text-muted small mb-4">
                                    {{ __('حدد نوع الجهات التي سيتم الاعتماد عليها عند اختيار الجهات في النماذج والمراسلات والمذكرات.') }}
                                </p>
                                
                                @php
                                    $activeSource = $settings['active_source'];
                                @endphp

                                <div class="source-options">
                                    <div class="form-check custom-option mb-3">
                                        <input class="form-check-input" type="radio" name="active_source" id="source_internal" value="internal" {{ $activeSource == 'internal' ? 'checked' : '' }} @cannot('entity-authorities.modify-source') disabled @endcannot>
                                        <label class="form-check-label p-3 rounded border w-100 d-flex align-items-center" for="source_internal">
                                            <div class="icon-box me-3 bg-white text-info rounded p-2 shadow-sm">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ __('الجهات الداخلية فقط') }}</div>
                                                <div class="small text-muted">{{ __('الاعتماد على التقسيمات الإدارية الداخلية للوزارة') }}</div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="form-check custom-option mb-3">
                                        <input class="form-check-input" type="radio" name="active_source" id="source_external" value="external" {{ $activeSource == 'external' ? 'checked' : '' }} @cannot('entity-authorities.modify-source') disabled @endcannot>
                                        <label class="form-check-label p-3 rounded border w-100 d-flex align-items-center" for="source_external">
                                            <div class="icon-box me-3 bg-white text-warning rounded p-2 shadow-sm">
                                                <i class="fas fa-external-link-alt"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ __('الجهات الخارجية فقط') }}</div>
                                                <div class="small text-muted">{{ __('الاعتماد على قائمة الجهات والمنظمات الخارجية الموثقة') }}</div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="form-check custom-option mb-0">
                                        <input class="form-check-input" type="radio" name="active_source" id="source_both" value="both" {{ $activeSource == 'both' ? 'checked' : '' }} @cannot('entity-authorities.modify-source') disabled @endcannot>
                                        <label class="form-check-label p-3 rounded border w-100 d-flex align-items-center" for="source_both">
                                            <div class="icon-box me-3 bg-white text-success rounded p-2 shadow-sm">
                                                <i class="fas fa-check-double"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ __('كلاهما (داخلي وخارجي)') }}</div>
                                                <div class="small text-muted">{{ __('إتاحة الاختيار من القائمتين معاً') }}</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-4 mt-md-0">
                            <div class="p-4 bg-light rounded-20 h-100">
                                <h5 class="fw-bold mb-3 border-bottom pb-2">
                                    <i class="fas fa-eye text-primary me-2"></i>
                                    {{ __('إعدادات الظهور والتحكم') }}
                                </h5>
                                <p class="text-muted small mb-4">
                                    {{ __('تحكم في مستوى الرؤية والإدارة للجهات بناءً على التصنيف.') }}
                                </p>

                                <div class="visibility-controls">
                                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-xs">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-info">
                                                <i class="fas fa-user-shield fa-lg"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ __('إدارة الجهات الداخلية') }}</div>
                                                <div class="small text-muted">{{ __('السماح بعرض قوائم الجهات الداخلية') }}</div>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch p-0 m-0">
                                            <input class="form-check-input ms-0 scale-1-5" type="checkbox" name="visibility_internal" value="1" {{ $settings['visibility_internal'] ? 'checked' : '' }} @cannot('entity-authorities.manage-internal') disabled @endcannot>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded shadow-xs">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-warning">
                                                <i class="fas fa-globe fa-lg"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ __('إدارة الجهات الخارجية') }}</div>
                                                <div class="small text-muted">{{ __('السماح بعرض قوائم الجهات الخارجية') }}</div>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch p-0 m-0">
                                            <input class="form-check-input ms-0 scale-1-5" type="checkbox" name="visibility_external" value="1" {{ $settings['visibility_external'] ? 'checked' : '' }} @cannot('entity-authorities.manage-external') disabled @endcannot>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-soft-primary mt-4 mb-0 border-0 rounded-10 small">
                                    <i class="fas fa-info-circle me-2"></i>
                                    {{ __('ملاحظة: هذه الإعدادات تؤثر على القوائم المنسدلة في كافة النظام (المذكرات، المراسلات، التواقيع).') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @can('entity-authorities.modify-source')
                    <div class="text-center mt-5">
                        <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill shadow">
                            <i class="fas fa-save me-2"></i>
                            {{ __('حفظ التغييرات النهائية') }}
                        </button>
                    </div>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-20 { border-radius: 20px; }
    .rounded-10 { border-radius: 10px; }
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
    .icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
    .custom-option input:checked + label {
        border-color: #0d6efd !important;
        background-color: rgba(13, 110, 253, 0.05);
    }
    .custom-option label {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #e9ecef !important;
        background-color: white;
    }
    .custom-option label:hover {
        border-color: #dee2e6 !important;
        transform: translateY(-2px);
    }
    .icon-box {
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .scale-1-5 { transform: scale(1.5); }
    .shadow-xs { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .alert-soft-primary { background-color: #e7f1ff; color: #084298; }
</style>
@endsection

@extends('layouts.app')

@section('title', 'نموذج متعدد الخطوات')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/multi-step-form.css') }}">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="page-header mb-4">
                <h1 class="page-title">نموذج متعدد الخطوات - Bootstrap 5.3</h1>
                <p class="page-subtitle">مثال على نموذج بخمس خطوات مع التحقق من صحة البيانات</p>
            </div>

            @can('sample.create')
            <form class="multi-step-form" id="sampleMultiStepForm" action="{{ route('sample.store') }}" method="POST">
            @endcan
                @csrf
                
                <!-- Step Progress -->
                <div class="step-progress">
                    <div class="steps-container">
                        <div class="step active">
                            <div class="step-number">1</div>
                            <div class="step-title">المعلومات الشخصية</div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-title">معلومات الاتصال</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-title">المؤهلات</div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-title">الخبرات</div>
                        </div>
                        <div class="step">
                            <div class="step-number">5</div>
                            <div class="step-title">المراجعة والإرسال</div>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="form-content">
                    <!-- Step 1: Personal Information -->
                    <div class="step-content active" data-step="1">
                        <h3>الخطوة الأولى: المعلومات الشخصية</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">الاسم الأول</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">الاسم الأخير</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">تاريخ الميلاد</label>
                                    <input type="date" name="birth_date" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">الجنس</label>
                                    <select name="gender" class="form-select" required>
                                        <option value="">اختر الجنس</option>
                                        <option value="male">ذكر</option>
                                        <option value="female">أنثى</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="required">الجنسية</label>
                            <select name="nationality" class="form-select" required>
                                <option value="">اختر الجنسية</option>
                                <option value="yemeni">يمني</option>
                                <option value="saudi">سعودي</option>
                                <option value="egyptian">مصري</option>
                                <option value="other">أخرى</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Step 2: Contact Information -->
                    <div class="step-content" data-step="2">
                        <h3>الخطوة الثانية: معلومات الاتصال</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">رقم الهاتف</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="required">العنوان</label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">المدينة</label>
                                    <input type="text" name="city" class="form-control" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>الرمز البريدي</label>
                                    <input type="text" name="postal_code" class="form-control">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Qualifications -->
                    <div class="step-content" data-step="3">
                        <h3>الخطوة الثالثة: المؤهلات</h3>
                        
                        <div class="dynamic-section">
                            <h4>
                                المؤهلات العلمية
                                <button type="button" class="add-item">
                                    <i class="fas fa-plus"></i> إضافة مؤهل
                                </button>
                            </h4>
                            
                            <div class="dynamic-container">
                                <div class="dynamic-item">
                                    <button type="button" class="remove-item" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">الدرجة العلمية</label>
                                                <select name="qualifications[0][degree]" class="form-select" required>
                                                    <option value="">اختر الدرجة</option>
                                                    <option value="bachelor">بكالوريوس</option>
                                                    <option value="master">ماجستير</option>
                                                    <option value="phd">دكتوراه</option>
                                                    <option value="diploma">دبلوم</option>
                                                </select>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">التخصص</label>
                                                <input type="text" name="qualifications[0][major]" class="form-control" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">الجامعة</label>
                                                <input type="text" name="qualifications[0][university]" class="form-control" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">سنة التخرج</label>
                                                <input type="number" name="qualifications[0][graduation_year]" class="form-control" min="1980" max="2024" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Experience -->
                    <div class="step-content" data-step="4">
                        <h3>الخطوة الرابعة: الخبرات</h3>
                        
                        <div class="dynamic-section">
                            <h4>
                                الخبرات العملية
                                <button type="button" class="add-item">
                                    <i class="fas fa-plus"></i> إضافة خبرة
                                </button>
                            </h4>
                            
                            <div class="dynamic-container">
                                <div class="dynamic-item">
                                    <button type="button" class="remove-item" style="display: none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">المسمى الوظيفي</label>
                                                <input type="text" name="experiences[0][job_title]" class="form-control" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">اسم الشركة</label>
                                                <input type="text" name="experiences[0][company]" class="form-control" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="required">تاريخ البداية</label>
                                                <input type="date" name="experiences[0][start_date]" class="form-control" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>تاريخ النهاية</label>
                                                <input type="date" name="experiences[0][end_date]" class="form-control">
                                                <div class="invalid-feedback"></div>
                                                <small class="form-text text-muted">اتركه فارغاً إذا كنت لا تزال تعمل</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>وصف المهام</label>
                                        <textarea name="experiences[0][description]" class="form-control" rows="3"></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Review and Submit -->
                    <div class="step-content" data-step="5">
                        <h3>الخطوة الخامسة: المراجعة والإرسال</h3>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            يرجى مراجعة جميع البيانات المدخلة قبل الإرسال. يمكنك العودة إلى أي خطوة سابقة لتعديل البيانات.
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="terms_accepted" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    أوافق على <a href="#" target="_blank">الشروط والأحكام</a> *
                                </label>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="newsletter" class="form-check-input" id="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    أرغب في تلقي النشرة الإخبارية
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات إضافية</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="أي ملاحظات أو معلومات إضافية تود إضافتها..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="form-navigation">
                    <button type="button" class="btn-step btn-step-secondary" data-action="prev" style="display: none;">
                        <i class="fas fa-arrow-right"></i> السابق
                    </button>
                    
                    <div class="flex-grow-1"></div>
                    
                    <button type="button" class="btn-step btn-step-primary" data-action="next">
                        التالي <i class="fas fa-arrow-left"></i>
                    </button>
                    
                    <button type="button" class="btn-step btn-step-success" data-action="submit" style="display: none;" data-original-text="إرسال البيانات">
                        <i class="fas fa-check"></i> إرسال البيانات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.page-header {
    background: #4e54c8;
    background: linear-gradient(to right, #4e54c8, #8f94fb);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .page-subtitle {
        font-size: 1rem;
    }
}
</style>

<script src="{{ asset('js/multi-step-form.js') }}"></script>
@endsection
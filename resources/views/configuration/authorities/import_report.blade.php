@extends('layouts.app')

@section('title', 'تقرير الاستيراد')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">تقرير استيراد الجهات</h6>
                    <div class="btn-group">
                        <a href="{{ route('authorities.index') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-list"></i> عرض الجهات
                        </a>
                        @can('authorities.import')
                        <a href="{{ route('authorities.import') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-import"></i> استيراد جديد
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <!-- رسالة نجاح -->
                    

                    <!-- ملخص النتائج -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <h6>تم الاستيراد</h6>
                                    <h4 class="text-success">{{ $imported ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-edit fa-2x text-warning mb-2"></i>
                                    <h6>تم التحديث</h6>
                                    <h4 class="text-warning">{{ $updated ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                    <h6>فشل الاستيراد</h6>
                                    <h4 class="text-danger">{{ $failed ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                    <h6>الوقت المستغرق</h6>
                                    <h4 class="text-info">{{ $duration ?? '0' }} ث</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل النتائج -->
                    @if(isset($results) && $results->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">تفاصيل العملية</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>اسم الجهة</th>
                                                <th>النتيجة</th>
                                                <th>الرسالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($results as $index => $result)
                                                <tr class="table-{{ $result['status'] == 'success' ? 'success' : ($result['status'] == 'updated' ? 'warning' : 'danger') }}">
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $result['agency_name'] }}</td>
                                                    <td>
                                                        @if($result['status'] == 'success')
                                                            <i class="fas fa-check-circle text-success"></i> تم الاستيراد
                                                        @elseif($result['status'] == 'updated')
                                                            <i class="fas fa-edit text-warning"></i> تم التحديث
                                                        @else
                                                            <i class="fas fa-times-circle text-danger"></i> فشل
                                                        @endif
                                                    </td>
                                                    <td>{{ $result['message'] ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- الأخطاء التفصيلية -->
                    @if(isset($errors) && $errors->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    الأخطاء المكتشفة ({{ $errors->count() }})
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-danger">
                                            <tr>
                                                <th>السطر</th>
                                                <th>اسم الجهة</th>
                                                <th>الخطأ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($errors as $row => $error)
                                                <tr>
                                                    <td>{{ $row + 1 }}</td>
                                                    <td>{{ $error['agency_name'] ?? '' }}</td>
                                                    <td>
                                                        <ul class="mb-0">
                                                            @foreach($error['errors'] ?? [] as $field => $messages)
                                                                @foreach($messages as $message)
                                                                    <li><strong>{{ $field }}:</strong> {{ $message }}</li>
                                                                @endforeach
                                                            @endforeach
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- أزرار التحكم -->
                    <div class="d-flex justify-content-center">
                        <div class="btn-group">
                            <a href="{{ route('authorities.index') }}" class="btn btn-success">
                                <i class="fas fa-list"></i> عرض الجهات
                            </a>
                            @can('authorities.import')
                            <a href="{{ route('authorities.import') }}" class="btn btn-primary">
                                <i class="fas fa-file-import"></i> استيراد آخر
                            </a>
                            @endcan
                            @if(isset($fileName))
                                <a href="{{ route('authorities.download-error-report', $fileName) }}" class="btn btn-warning">
                                    <i class="fas fa-download"></i> تحميل تقرير الأخطاء
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
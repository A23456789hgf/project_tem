@extends('layouts.app')

@section('content')
<div class="container-fluid text-end">
    <div class="row">
        <div class="col-12">
            <div class="custom-card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-bar"></i>
                        تقرير الاستيراد
                    </h4>
                </div>

                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h5>تم بنجاح</h5>
                                    <h3>{{ $results['success'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <h5>أخطاء</h5>
                                    <h3>{{ $results['errors'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <h5>تم تخطيها</h5>
                                    <h3>{{ $results['skipped'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <h5>الإجمالي</h5>
                                    <h3>{{ $results['success'] + $results['errors'] + $results['skipped'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Message -->
                    @if($results['success'] > 0)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            تم استيراد {{ $results['success'] }} جهة ممولة بنجاح!
                        </div>
                    @endif

                    <!-- Details -->
                    @if(!empty($results['details']))
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list-ul"></i>
                                    تفاصيل العملية
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>التفاصيل</th>
                                                <th>النوع</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($results['details'] as $index => $detail)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $detail }}</td>
                                                    <td>
                                                        @if(str_contains($detail, 'خطأ'))
                                                            <span class="badge bg-danger">خطأ</span>
                                                        @elseif(str_contains($detail, 'موجود'))
                                                            <span class="badge bg-warning">تخطي</span>
                                                        @else
                                                            <span class="badge bg-info">معلومات</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        @can('fundedentities.import')
                        <a href="{{ route('fundedentities.import') }}" class="btn btn-info">
                            <i class="fas fa-upload"></i> استيراد ملف آخر
                        </a>
                        @endcan
                        <a href="{{ route('fundedentities.index') }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> عرض القائمة
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
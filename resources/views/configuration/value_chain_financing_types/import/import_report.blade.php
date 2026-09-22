@extends('layouts.app')

@section('title', 'تقرير استيراد أنواع تمويل سلاسل القيمة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>تقرير استيراد أنواع تمويل السلاسل</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>ملخص الاستيراد</h5>
                        <ul>
                            <li>إجمالي السجلات المعالجة: {{ $report['total'] }}</li>
                            <li>عدد السجلات الناجحة: <span class="text-success">{{ $report['successful'] }}</span></li>
                            <li>عدد السجلات الفاشلة: <span class="text-danger">{{ $report['failed'] }}</span></li>
                        </ul>
                    </div>

                    @if($report['failed'] > 0)
                    <div class="alert alert-warning">
                        <h5>الأخطاء</h5>
                        <p>حدثت أخطاء في بعض السجلات. يمكنك عرض تفاصيل الأخطاء أدناه.</p>
                        
                        <div class="mt-3">
                            <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#errorDetails" aria-expanded="false" aria-controls="errorDetails">
                                عرض تفاصيل الأخطاء
                            </button>
                        </div>

                        <div class="collapse mt-3" id="errorDetails">
                            <div class="card card-body">
                                @foreach($errors as $rowNumber => $error)
                                    <div class="mb-2">
                                        <strong>الصف {{ $rowNumber }}:</strong>
                                        <ul>
                                            @foreach($error['errors'] as $errorMessage)
                                                <li class="text-danger">{{ $errorMessage }}</li>
                                            @endforeach
                                        </ul>
                                        <pre class="small">{{ json_encode($error['row_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($report['successful'] > 0)
                    <div class="alert alert-success">
                        <h5>الاستيراد الناجح</h5>
                        <p>تم استيراد {{ $report['successful'] }} سجل بنجاح.</p>
                        <p>اسم الملف المستورد: {{ $importedFile }}</p>
                        
                        @if($report['failed'] == 0)
                        <div class="mt-3">
                            @can('value-chain-financing-types.import.undo')
                            <form action="{{ route('value-chain-financing-types.import.undo', $importedFile) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning" onclick="return confirmAction(this, 'هل أنت متأكد من التراجع عن الاستيراد؟ سيتم حذف جميع السجلات المستوردة.')">
                                    <i class="fas fa-undo"></i> تراجع عن الاستيراد
                                </button>
                            </form>
                            @endcan
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('value-chain-financing-types.index') }}" class="btn btn-primary">
                            <i class="fas fa-list"></i> العودة إلى القائمة
                        </a>
                        <a href="{{ route('value-chain-financing-types.import.form') }}" class="btn btn-secondary">
                            <i class="fas fa-upload"></i> استيراد ملف آخر
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

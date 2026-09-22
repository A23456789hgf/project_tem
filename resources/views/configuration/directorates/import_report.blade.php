@extends('layouts.app')

@section('styles')
<link href="{{ asset('css/excel-drag-drop.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    تقرير استيراد المديريات
                </h2>
                <a href="{{ route('directorates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-1"></i>
                    العودة للقائمة
                </a>
            </div>

            <!-- Import Summary Card -->
            <div class="card import-report-card mb-4 {{ $report['failed'] > 0 ? 'report-warning' : 'report-success' }}">
                <div class="card-header">
                    <h5 class="mb-0">
                        @if($report['failed'] > 0)
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            اكتمل الاستيراد مع تحذيرات
                        @else
                            <i class="fas fa-check-circle text-success me-2"></i>
                            تم الاستيراد بنجاح
                        @endif
                    </h5>
                </div>
                
                <!-- Statistics -->
                <div class="report-stats">
                    <div class="report-stat stat-total">
                        <div class="report-stat-number">{{ $report['total'] }}</div>
                        <div class="report-stat-label">إجمالي السجلات</div>
                    </div>
                    <div class="report-stat stat-success">
                        <div class="report-stat-number">{{ $report['successful'] }}</div>
                        <div class="report-stat-label">تمت بنجاح</div>
                    </div>
                    <div class="report-stat stat-failed">
                        <div class="report-stat-number">{{ $report['failed'] }}</div>
                        <div class="report-stat-label">فشل</div>
                    </div>
                </div>

                <div class="card-body">
                    @if($report['successful'] > 0)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            تم استيراد <strong>{{ $report['successful'] }}</strong> مديرية بنجاح.
                        </div>
                    @endif

                    @if($report['failed'] > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            فشل في استيراد <strong>{{ $report['failed'] }}</strong> سجل. يرجى مراجعة الأخطاء أدناه.
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        @if($report['failed'] > 0)
                            <a href="{{ route('directorates.download-error-report') }}" class="btn btn-outline-danger">
                                <i class="fas fa-download me-1"></i>
                                تحميل تقرير الأخطاء
                            </a>
                        @endif
                        
                        <a href="{{ route('directorates.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-upload me-1"></i>
                            استيراد ملف آخر
                        </a>
                        
                        @if($report['successful'] > 0)
                            <button type="button" class="btn btn-outline-warning" onclick="confirmUndo()">
                                <i class="fas fa-undo me-1"></i>
                                تراجع عن الاستيراد
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Error Details -->
            @if($report['failed'] > 0 && !empty($report['errors']))
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle text-danger me-2"></i>
                        تفاصيل الأخطاء
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="error-details">
                        @foreach($report['errors'] as $row => $errors)
                        <div class="error-item">
                            <div class="error-row">
                                <i class="fas fa-times-circle me-1"></i>
                                الصف رقم {{ $row }}
                            </div>
                            <ul class="error-messages">
                                @foreach($errors as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Undo Confirmation Modal -->
<div class="modal fade" id="undoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    تأكيد التراجع
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من أنك تريد التراجع عن عملية الاستيراد؟</p>
                <p class="text-warning">
                    <i class="fas fa-info-circle me-1"></i>
                    سيتم حذف جميع السجلات التي تم استيرادها في هذه العملية ({{ $report['successful'] }} سجل).
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                @can('directorates.undo-import')
                <form action="{{ route('directorates.undo-import', $importedFile) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-1"></i>
                        تأكيد التراجع
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmUndo() {
    const modal = new bootstrap.Modal(document.getElementById('undoModal'));
    modal.show();
}

// Auto-hide alerts after 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 10000);
    });
});
</script>
@endsection
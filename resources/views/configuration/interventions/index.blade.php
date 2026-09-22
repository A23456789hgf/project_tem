@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

<div class="container">
    <div class="main-card">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0" style="color: #001f3f;">
                    <i class="fas fa-list-ul text-primary me-2"></i> قائمة التدخلات
                </h2>
                <div class="title-line"></div>
            </div>
            
            <div class="d-flex gap-2">
                @can('interventions.create')
                <a href="{{ route('interventions.create') }}" class="btn btn-success px-4 rounded-3 fw-bold shadow-sm auth-perm-interventions-create">
                    <i class="fas fa-plus me-1"></i> إضافة جديد
                </a>
                @endcan

                <div class="dropdown">
                    <button class="btn btn-navy-gold dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-database me-1"></i> البيانات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                        @can('interventions.export')
                        <li><a class="dropdown-item py-2 auth-perm-interventions-export" href="{{ route('config.export', ['entity' => 'interventions']) }}"><i class="fas fa-download text-success me-2"></i> تصدير Excel</a></li>
                        @endcan
                        @can('interventions.import')
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 auth-perm-interventions-import" href="{{ route('config.import', ['entity' => 'interventions']) }}"><i class="fas fa-upload text-info me-2"></i> استيراد ملف</a></li>
                        @endcan
                    </ul>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th class="text-start">اسم التدخل</th>
                        <th>المجال الرئيسي</th>
                        <th>المجال الفرعي</th>
                        <th>الحالة</th>
                        <th style="width: 120px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
@forelse ($interventions as $intervention)
                    <tr>
                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                        <td class="text-name">{{ $intervention->name }}</td>
                        <td><span class="badge-label">{{ $intervention->domain?->name ?? '-' }}</span></td>
                        <td><span class="badge-label text-muted small">{{ $intervention->subdomain?->name ?? '-' }}</span></td>
                        <td>
                            @if($intervention->status === 'pending')
                                <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="fas fa-clock me-1"></i> قيد المراجعة</span>
                            @elseif($intervention->status === 'rejected')
                                <span class="badge bg-secondary text-white fw-bold px-2 py-1"><i class="fas fa-ban me-1"></i> مرفوض</span>
                            @elseif($intervention->is_active)
                                <span class="badge bg-success text-white fw-bold px-2 py-1"><i class="fas fa-check-circle me-1"></i> معتمد ونشط</span>
                            @else
                                <span class="badge bg-danger text-white fw-bold px-2 py-1"><i class="fas fa-times-circle me-1"></i> معطل</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex justify-content-center align-items-center gap-1">
                                @if($intervention->status === 'pending')
                                    @can('interventions.approve')
                                    <form action="{{ route('interventions.approve', $intervention->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="اعتماد وموافقة على التدخل" onclick="return confirmAction(this, 'هل ترغب في اعتماد هذا التدخل وإضافته بشكل دائم؟')">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                    @endcan
                                    @can('interventions.reject')
                                    <form action="{{ route('interventions.reject', $intervention->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="رفض التدخل" onclick="return confirmAction(this, 'هل تريد رفض هذا التدخل؟')">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </form>
                                    @endcan
                                @endif

                                @can('interventions.edit')
                                <a href="{{ route('interventions.edit', $intervention->id) }}" class="btn-action btn btn-sm btn-outline-warning auth-perm-interventions-edit" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan

                                @can('interventions.delete')
                                <form action="{{ route('interventions.destroy', $intervention->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn btn-sm btn-outline-danger auth-perm-interventions-delete" onclick="return confirmAction(this, 'هل تريد الحذف؟')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">لا توجد بيانات متاحة حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($interventions instanceof \Illuminate\Pagination\LengthAwarePaginator && $interventions->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $interventions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'الفئات المستفيدة')

@section('content')
@include('configuration.shared_styles')

<div class="container">
    <div class="main-card">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0" style="color: #001f3f;">
                    <i class="fas fa-users text-primary me-2"></i> الفئات المستفيدة
                </h2>
                <div class="title-line"></div>
            </div>
            
            <div class="d-flex gap-2">
                @canany(['beneficiary-groups.create', 'beneficiary-groups.update'])
<a href="{{ route('beneficiary-groups.create') }}" class="btn btn-success px-4 rounded-3 fw-bold shadow-sm">
                    <i class="fas fa-plus me-1"></i> إضافة فئة جديدة
                </a>
@endcanany

                <div class="dropdown">
                    <button class="btn btn-navy-gold dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-database me-1"></i> البيانات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                        @can('config.export')
                        <li><a class="dropdown-item py-2" href="{{ route('config.export', ['entity' => 'beneficiary-groups']) }}"><i class="fas fa-download text-success me-2"></i> تصدير Excel</a></li>
                        @endcan
                        <li><hr class="dropdown-divider"></li>
                        @can('config.import')
                        <li><a class="dropdown-item py-2" href="{{ route('config.import', ['entity' => 'beneficiary-groups']) }}"><i class="fas fa-upload text-info me-2"></i> استيراد ملف</a></li>
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
                        <th class="text-start">اسم الفئة المستفيدة</th>
                        <th>تاريخ الإنشاء</th>
                        <th style="width: 120px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($beneficiaryGroups as $group)
                    <tr>
                        <td class="text-muted fw-bold">{{ $loop->iteration }}</td>
                        <td class="text-name">{{ $group->name }}</td>
                        <td><span class="badge-label">{{ $group->created_at->format('Y-m-d') }}</span></td>
                        <td>
                            <div class="d-flex justify-content-center">
                                @can('beneficiary-groups.edit')
                                <a href="{{ route('beneficiary-groups.edit', $group->id) }}" class="btn-action btn btn-sm btn-outline-warning" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('beneficiary-groups.delete')
                                <form action="{{ route('beneficiary-groups.destroy', $group->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn btn-sm btn-outline-danger" onclick="return confirmAction(this, 'هل تريد الحذف؟')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">لا توجد بيانات متاحة حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($beneficiaryGroups->hasPages())
        <div class="mt-4">{{ $beneficiaryGroups->links() }}</div>
        @endif
    </div>
</div>
@endsection
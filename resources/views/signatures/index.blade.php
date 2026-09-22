@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')

    <x-index-page title="إدارة التواقيع" icon="pen-tool">
        <x-slot name="headerActions">
            @can('signatures.create')
            <a href="{{ route('signatures.create') }}" class="btn btn-primary auth-perm-signatures-create">
                <x-icon name="plus" size="14" /> إضافة توقيع جديد
            </a>
            @endcan
        </x-slot>

        <x-slot name="filters">

        <div class="mb-4 p-3 bg-light rounded-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label for="search" class="field-label">البحث عن اسم أو صفة</label>
                    <input type="text" id="search" name="search" class="form-control custom-field" 
                           placeholder="بحث..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-navy-gold shadow-sm w-100">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                </div>
            </form>
        </x-slot>

        <x-slot name="table">
            <table class="table table-hover table-striped align-middle table-compact mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>الاسم الكامل</th>
                        <th>الصفة / المسمى الوظيفي</th>
                        <th>التوقيع</th>
                        <th>الحالة</th>
                        <th style="width: 150px;">الترتيب</th>
                        <th style="width: 120px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($signatures as $index => $signature)
                    <tr>
                        <td class="text-muted fw-bold">{{ $signatures->firstItem() + $index }}</td>
                        <td class="fw-bold">{{ $signature->name }}</td>
                        <td>{{ $signature->job_title }}</td>
                        <td>
                            @if($signature->signature_path)
                                <img src="{{ asset('storage/' . $signature->signature_path) }}" alt="signature" style="height: 40px; object-fit: contain;" class="rounded border bg-white p-1">
                            @else
                                <span class="badge bg-light text-muted border">لا يوجد</span>
                            @endif
                        </td>
                        <td>
                            @if($signature->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">نشط</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">متوقف</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-navy-gold p-2">{{ $signature->display_order }}</span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                @can('signatures.edit')
                                <a href="{{ route('signatures.edit', $signature) }}" class="btn btn-action-edit btn-icon" title="تعديل">
                                    <x-icon name="edit-2" class="action-icon" />
                                </a>
                                @endcan
                                @can('signatures.delete')
                                <form action="{{ route('signatures.destroy', $signature) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-action-delete btn-icon" onclick="return confirmAction(this, 'هل تريد حذف هذا المسمى؟')">
                                        <x-icon name="trash-2" class="action-icon" />
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">لا توجد تواقيع مضافة حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </x-slot>

        <x-slot name="pagination">
            @if(method_exists($signatures, 'hasPages') && $signatures->hasPages())
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="text-muted small">
                        عرض <strong>{{ $signatures->firstItem() }}</strong> إلى <strong>{{ $signatures->lastItem() }}</strong> من <strong>{{ $signatures->total() }}</strong>
                    </div>
                    <div>{{ $signatures->appends(request()->query())->links() }}</div>
                </div>
            @endif
        </x-slot>
    </x-index-page>
@endsection

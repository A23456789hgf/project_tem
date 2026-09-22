@extends('layouts.app')

@section('content')
@include('configuration.shared_styles')
<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container">
    <div class="main-card">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0" style="color: #001f3f;">
                    <i class="fas fa-handshake text-primary me-2"></i> قائمة الجمعيات
                </h2>
                <div class="title-line"></div>
            </div>
            
            <div class="d-flex gap-2">
                @canany(['associations.create', 'associations.update'])
<a href="{{ route('associations.create') }}" class="btn btn-success px-4 rounded-3 fw-bold shadow-sm">
                    <i class="fas fa-plus me-1"></i> إضافة جمعية جديدة
                </a>
@endcanany
            </div>
        </div>

        

        <!-- Search/Filter Section -->
        <div class="mb-4 p-3 bg-light rounded-3">
            <form action="{{ route('associations.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="field-label">البحث</label>
                    <input type="text" name="search" class="form-control custom-field" placeholder="بحث في الجمعيات..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="field-label">عدد النتائج</label>
                    <select name="per_page" class="form-select custom-field" onchange="this.form.submit()">
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                        <option value="500" {{ request('per_page', 20) == 500 ? 'selected' : '' }}>500</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-navy-gold shadow-sm w-100">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th class="text-start">
                            <a href="{{ route('associations.index', ['sort' => 'name', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}" class="text-dark text-decoration-none">
                                <i class="fas fa-sort me-1"></i> اسم الجمعية
                            </a>
                        </th>
                        <th style="width: 120px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($associations as $association)
                    <tr>
                        <td class="text-muted fw-bold">{{ $association->id }}</td>
                        <td class="text-name">{{ $association->name }}</td>
                        <td>
                            <div class="d-flex justify-content-center">
                                @can('associations.edit')
                                <a href="{{ route('associations.edit', $association) }}" class="btn-action btn btn-sm btn-outline-warning" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('associations.delete')
                                <form action="{{ route('associations.destroy', $association) }}" method="POST" class="d-inline">
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
                        <td colspan="3" class="text-center py-5 text-muted">لا توجد بيانات متاحة حالياً</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($associations->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $associations->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

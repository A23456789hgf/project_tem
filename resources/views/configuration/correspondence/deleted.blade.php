@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-trash-alt me-2"></i> المراسلات المحذوفة
                    </h5>
                    <a href="{{ route('correspondence.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> رجوع للكل
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>رقم المراسلة</th>
                                    <th>الموضوع</th>
                                    <th>إلى</th>
                                    <th>تاريخ الحذف</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($correspondences as $correspondence)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><span class="badge bg-secondary">{{ $correspondence->correspondence_number }}</span></td>
                                        <td>{{ $correspondence->subject }}</td>
                                        <td>{{ $correspondence->recipientEntity->name }}</td>
                                        <td>{{ $correspondence->deleted_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @can('correspondence.restore')
                                            <form action="{{ route('correspondence.restore', $correspondence->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirmAction(this, 'هل أنت متأكد من استعادة هذه المراسلة؟')">
                                                    <i class="fas fa-undo"></i> استعادة
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">لا يوجد مراسلات محذوفة حالياً.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

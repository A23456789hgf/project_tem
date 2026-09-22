@extends('layouts.app')

@section('content')
<div class="container-fluid text-end">
    <div class="row">
        <div class="col-12">
            <div class="custom-card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-eye"></i>
                        معاينة بيانات الاستيراد
                    </h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>تأكد من صحة البيانات قبل المتابعة!</strong>
                        سيتم استيراد البيانات التالية إلى النظام.
                    </div>

                    <!-- Data Preview Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    @foreach($headers as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        @foreach($row as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <h5>إجمالي الصفوف</h5>
                                    <h3>{{ count($rows) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-columns fa-2x mb-2"></i>
                                    <h5>عدد الأعمدة</h5>
                                    <h3>{{ count($headers) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-file fa-2x mb-2"></i>
                                    <h5>اسم الملف</h5>
                                    <h6>{{ $fileName }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <div>
                            @can('fundedentities.import')
                            <a href="{{ route('fundedentities.import') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> العودة
                            </a>
                            @endcan
                        </div>
                        <div>
                            @can('fundedentities.process-import')
                            <form action="{{ route('fundedentities.process-import') }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="file_name" value="{{ $fileName }}">
                                <button type="submit" class="btn btn-success" onclick="return confirmAction(this, 'هل أنت متأكد من استيراد هذه البيانات؟')">
                                    <i class="fas fa-check"></i> تأكيد الاستيراد
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
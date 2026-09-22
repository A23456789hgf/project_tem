@extends('layouts.app')

@section('title', 'معاينة استيراد البرامج')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>معاينة استيراد البرامج</h4>
                </div>
                <div class="card-body">
                    @if(isset($headers) && isset($rows))
                    @can('programs.import')
                    <form action="{{ route('programs.process-import') }}" method="POST">
                    @endcan
                        @csrf
                        <input type="hidden" name="file_path" value="{{ $filePath }}">

                        <div class="form-group">
                            <label for="operation">عملية الاستيراد</label>
                            <select class="form-control" id="operation" name="operation" required>
                                <option value="insert">إضافة جديدة فقط</option>
                                <option value="update">تحديث الموجود فقط</option>
                                <option value="both">إضافة وتحديث</option>
                            </select>
                        </div>

                        <h5>تعيين الحقول</h5>
                        <p>يرجى تعيين أعمدة الملف مع الحقول المناسبة في النظام.</p>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>حقل النظام</th>
                                        <th>عمود الملف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dbColumns as $dbKey => $dbLabel)
                                    <tr>
                                        <td>{{ $dbLabel }}</td>
                                        <td>
                                            <select name="mapping[{{ $dbKey }}]" class="form-control">
                                                <option value="">-- اختر العمود --</option>
                                                @foreach($headers as $header)
                                                    <option value="{{ $header }}" {{ old('mapping.'.$dbKey) == $header ? 'selected' : '' }}>{{ $header }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <h5>معاينة البيانات (أول 10 صفوف)</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        @foreach($headers as $header)
                                            <th>{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                    <tr>
                                        @foreach($headers as $header)
                                            <td>{{ $row[$header] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> بدء الاستيراد
                            </button>
                            @can('programs.import')
                            <a href="{{ route('programs.import') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            @endcan
                        </div>
                    </form>
                    @else
                    <div class="alert alert-danger">
                        لم يتم تحميل بيانات للمعاينة. يرجى المحاولة مرة أخرى.
                    </div>
                    @can('programs.import')
                    <a href="{{ route('programs.import') }}" class="btn btn-secondary">العودة</a>
                    @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
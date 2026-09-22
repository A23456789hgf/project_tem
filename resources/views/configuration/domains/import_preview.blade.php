@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>معاينة استيراد المجالات</h4>
                </div>
                <div class="card-body">
                    @if(isset($headers) && isset($rows))
                    @can('domains.process-import')
                    <form action="{{ route('domains.process-import') }}" method="POST">
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

                        <div class="row">
                            @foreach($mappingFields as $field)
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mapping_{{ $field }}">{{ $field }}</label>
                                    <select class="form-control" id="mapping_{{ $field }}" name="mapping[{{ $field }}]">
                                        <option value="">-- اختر من أعمدة الملف --</option>
                                        @foreach($headers as $header)
                                            <option value="{{ $header }}" {{ old('mapping.'.$field) == $header ? 'selected' : '' }}>{{ $header }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <h5>معاينة البيانات (أول 10 صفوف)</h5>
                        <div class="table-responsive">
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

                        <button type="submit" class="btn btn-primary">بدء الاستيراد</button>
                        @can('domains.import')
                        <a href="{{ route('domains.import') }}" class="btn btn-secondary">إلغاء</a>
                        @endcan
                    </form>
                    @else
                    <div class="alert alert-danger">
                        لم يتم تحميل بيانات للمعاينة. يرجى المحاولة مرة أخرى.
                    </div>
                    @can('domains.import')
                    <a href="{{ route('domains.import') }}" class="btn btn-secondary">العودة</a>
                    @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

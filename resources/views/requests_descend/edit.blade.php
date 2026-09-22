@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary font-weight-bold d-flex align-items-center">
                <x-icon name="edit" class="ms-2" />تعديل طلب الإنزال #{{ $requestDescend->id }}
            </h5>
            <a href="{{ route('requests_descend.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2">
                <x-icon name="arrow-left" />العودة إلى القائمة
            </a>
        </div>

        <div class="card-body">
            

            @can('requests_descend.edit')
            <form action="{{ route('requests_descend.update', $requestDescend->id) }}" method="POST">
            @endcan
                @csrf
                @method('PUT')
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold">مرتبط بمشروع؟</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_linked_to_project" id="link_yes" value="1"
                                    {{ old('is_linked_to_project', $requestDescend->is_linked_to_project) == '1' ? 'checked' : '' }}
                                    onchange="toggleProjectSelect(true)">
                                <label class="form-check-label" for="link_yes">نعم</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_linked_to_project" id="link_no" value="0"
                                    {{ old('is_linked_to_project', $requestDescend->is_linked_to_project) == '0' ? 'checked' : '' }}
                                    onchange="toggleProjectSelect(false)">
                                <label class="form-check-label" for="link_no">لا</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3" id="project_select_container"
                        style="{{ old('is_linked_to_project', $requestDescend->is_linked_to_project) == '1' ? '' : 'display:none;' }}">
                        <label for="project_id" class="form-label font-weight-bold">اختر المشروع</label>
                        <select name="project_id" id="project_id" class="form-select">
                            <option value="">-- اختر مشروع --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ old('project_id', $requestDescend->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="priority" class="form-label font-weight-bold">الأولوية</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="">-- اختر الأولوية --</option>
                            <option value="Important" {{ old('priority', $requestDescend->priority) == 'Important' ? 'selected' : '' }}>مهم</option>
                            <option value="Urgent" {{ old('priority', $requestDescend->priority) == 'Urgent' ? 'selected' : '' }}>عاجل</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="needs" class="form-label font-weight-bold">الاحتياجات</label>
                        <textarea name="needs" id="needs" rows="3" class="form-control">{{ old('needs', $requestDescend->needs) }}</textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="reason_for_drop" class="form-label font-weight-bold">سبب الإنزال</label>
                        <textarea name="reason_for_drop" id="reason_for_drop" rows="3" class="form-control">{{ old('reason_for_drop', $requestDescend->reason_for_drop) }}</textarea>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="objective_of_drop" class="form-label font-weight-bold">هدف الإنزال</label>
                        <textarea name="objective_of_drop" id="objective_of_drop" rows="3" class="form-control">{{ old('objective_of_drop', $requestDescend->objective_of_drop) }}</textarea>
                    </div>
                </div>

                <hr>

                <h5 class="text-primary font-weight-bold mb-3">الأنشطة</h5>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered" id="activities_table">
                        <thead class="bg-light">
                            <tr>
                                <th>النشاط</th>
                                <th>الناتج المتوقع</th>
                                <th>من تاريخ</th>
                                <th>إلى تاريخ</th>
                                <th>المدة (أيام)</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>

                        <tbody id="activities_body">
                            @php 
                                $oldActivities = old('activities', $requestDescend->activities->toArray()); 
                            @endphp

                            @if(count($oldActivities) > 0)
                                @foreach($oldActivities as $index => $activity)
                                    <tr class="activity-row">
                                        <input type="hidden" name="activities[{{ $index }}][id]" value="{{ $activity['id'] ?? '' }}">

                                        <td><input type="text" name="activities[{{ $index }}][activity]" class="form-control" value="{{ $activity['activity'] ?? '' }}" required></td>

                                        <td><input type="text" name="activities[{{ $index }}][expected_output]" class="form-control" value="{{ $activity['expected_output'] ?? '' }}"></td>

                                        <td><input type="date" name="activities[{{ $index }}][from_date]" class="form-control from-date"
                                            value="{{ (isset($activity['from_date']) && strlen($activity['from_date']) > 10) ? substr($activity['from_date'],0,10) : ($activity['from_date'] ?? '') }}"
                                            onchange="calculateDuration(this)"></td>

                                        <td><input type="date" name="activities[{{ $index }}][to_date]" class="form-control to-date"
                                            value="{{ (isset($activity['to_date']) && strlen($activity['to_date']) > 10) ? substr($activity['to_date'],0,10) : ($activity['to_date'] ?? '') }}"
                                            onchange="calculateDuration(this)"></td>

                                        <td><input type="text" class="form-control duration text-center" readonly></td>

                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center" 
                                                style="width: 32px; height: 32px; padding: 0 !important;" 
                                                onclick="removeActivityRow(this)">
                                                <x-icon name="trash" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="activity-row">
                                    <td><input type="text" name="activities[0][activity]" class="form-control" required></td>
                                    <td><input type="text" name="activities[0][expected_output]" class="form-control"></td>
                                    <td><input type="date" name="activities[0][from_date]" class="form-control from-date" onchange="calculateDuration(this)"></td>
                                    <td><input type="date" name="activities[0][to_date]" class="form-control to-date" onchange="calculateDuration(this)"></td>
                                    <td><input type="text" class="form-control duration text-center" readonly></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center" 
                                            style="width: 32px; height: 32px; padding: 0 !important;" 
                                            onclick="removeActivityRow(this)">
                                            <x-icon name="trash" />
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <button type="button" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" onclick="addActivityRow()">
                        <x-icon name="plus" /> إضافة نشاط
                    </button>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('requests_descend.index') }}" class="btn btn-secondary me-2">إلغاء</a>
                    <button type="submit" class="btn btn-primary">تحديث طلب الإنزال</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleProjectSelect(show) {
        document.getElementById('project_select_container').style.display = show ? 'block' : 'none';
        if (!show) {
            document.getElementById('project_id').value = '';
        }
    }

    let activityIndex = {{ count(old('activities', $requestDescend->activities)) }};
    if (activityIndex === 0) activityIndex = 1;

    function addActivityRow() {
        const tbody = document.getElementById('activities_body');
        const row = document.createElement('tr');
        row.className = 'activity-row';

        row.innerHTML = `
            <td><input type="text" name="activities[${activityIndex}][activity]" class="form-control" required></td>
            <td><input type="text" name="activities[${activityIndex}][expected_output]" class="form-control"></td>
            <td><input type="date" name="activities[${activityIndex}][from_date]" class="form-control from-date" onchange="calculateDuration(this)"></td>
            <td><input type="date" name="activities[${activityIndex}][to_date]" class="form-control to-date" onchange="calculateDuration(this)"></td>
            <td><input type="text" class="form-control duration text-center" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; padding: 0 !important;" onclick="removeActivityRow(this)"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="svg-icon svg-icon-trash" style="fill: currentColor !important; width: 1em; height: 1em; vertical-align: -0.125em;" fill="currentColor" aria-hidden="true"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg></button></td>
        `;

        tbody.appendChild(row);
        activityIndex++;
    }

    function removeActivityRow(btn) {
        if(document.querySelectorAll('.activity-row').length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('يجب وجود نشاط واحد على الأقل');
        }
    }

    function calculateDuration(input) {
        const row = input.closest('tr');
        const fromDate = row.querySelector('.from-date').value;
        const toDate = row.querySelector('.to-date').value;
        const durationInput = row.querySelector('.duration');

        if (fromDate && toDate) {
            const diff = new Date(toDate) - new Date(fromDate);
            const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
            durationInput.value = days >= 0 ? days : 'غير صالح';
        } else {
            durationInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.from-date').forEach(i => calculateDuration(i));
    });
</script>
@endsection
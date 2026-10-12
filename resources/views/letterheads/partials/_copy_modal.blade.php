<div class="modal fade" id="letterheadCopyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" dir="rtl">
            <form action="{{ route('letterheads.copy-settings') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">نسخ إعدادات الترويسة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">الترويسة المصدر</label>
                    <select name="source_letterhead_id" class="form-select mb-3" required>
                        <option value="">-- اختر --</option>
                        @foreach(($letterheads ?? collect()) as $sourceLetterhead)
                            <option value="{{ $sourceLetterhead->id }}">{{ $sourceLetterhead->name }}</option>
                        @endforeach
                    </select>

                    <div class="row g-2">
                        @foreach(($scopedOrganizations ?? collect()) as $organization)
                            @php
                                $type = data_get($organization, 'type') ?? data_get($organization, 'organization_type') ?? 'internal';
                                $id = data_get($organization, 'id') ?? data_get($organization, 'organization_id');
                                $name = data_get($organization, 'name') ?? data_get($organization, 'organization_name') ?? ('جهة #' . $id);
                                if (in_array($type, ['Company', 'Department'], true)) $type = 'internal';
                            @endphp
                            @if($id)
                                <div class="col-md-6">
                                    <label class="form-check border rounded p-2 w-100">
                                        <input class="form-check-input" type="checkbox" name="target_organizations[]" value="{{ $type }}:{{ $id }}">
                                        <span class="form-check-label ms-2">{{ $name }}</span>
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <hr>
                    @foreach(['header' => 'الترويسة', 'logos' => 'الشعارات', 'typography' => 'الخطوط', 'footer' => 'التذييل', 'page' => 'إعدادات الصفحة'] as $value => $label)
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="settings_groups[]" value="{{ $value }}">
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">نسخ</button>
                </div>
            </form>
        </div>
    </div>
</div>

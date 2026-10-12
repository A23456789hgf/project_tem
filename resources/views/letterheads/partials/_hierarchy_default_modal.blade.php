<div class="modal fade" id="hierarchyDefaultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" dir="rtl">
            <form action="{{ route('letterheads.hierarchical-default') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">تعيين افتراضي هرمي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نوع الجهة</label>
                        <select name="organization_type" class="form-select" required>
                            <option value="internal">داخلية</option>
                            <option value="external">خارجية</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">معرف الجهة</label>
                        <input type="number" min="1" name="organization_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الترويسة</label>
                        <select name="letterhead_id" class="form-select" required>
                            <option value="">-- اختر --</option>
                            @foreach(($letterheads ?? collect()) as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وضع التطبيق</label>
                        <select name="mode" class="form-select" required>
                            <option value="self">الجهة فقط</option>
                            <option value="branch">الجهة والفروع</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تعيين</button>
                </div>
            </form>
        </div>
    </div>
</div>

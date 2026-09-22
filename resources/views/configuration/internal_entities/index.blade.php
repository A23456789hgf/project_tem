@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">
@endpush

@section('content')
    <x-index-page title="الجهات الداخلية" icon="sitemap">

        <x-slot name="headerActions">
            @can('internal-entities.create')
                <x-ui.button href="{{ route('internal-entities.create') }}" variant="success" size="sm" icon="fas fa-plus">إضافة جهة</x-ui.button>
            @endcan

            @can('internal-entities.view')
                <x-ui.button href="{{ route('internal-entities.hierarchy-tree') }}" variant="info" size="sm" icon="fas fa-sitemap">المشجر التنظيمي</x-ui.button>
            @endcan

            @can('internal-entities.export')
                <x-ui.button href="{{ route('config.export', ['entity' => 'internal-entities']) }}" variant="outline-primary" size="sm" icon="fas fa-file-excel">تصدير Excel</x-ui.button>
            @endcan

            @can('internal-entities.import')
                <x-ui.button type="button" variant="primary" size="sm" icon="fas fa-upload" data-bs-toggle="modal" data-bs-target="#importModal">استيراد</x-ui.button>
            @endcan

            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                    id="dropdownDataMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <x-icon name="database" size="14" />
                    البيانات
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" aria-labelledby="dropdownDataMenu">
                    @can('import-logs.view')
                        <li>
                            <a class="dropdown-item py-2 auth-perm-import-logs-view"
                                href="{{ route('import-logs.index', ['unit_name' => 'الجهات الداخلية']) }}">
                                <x-icon name="clock" size="14" class="text-secondary me-2" /> سجل الاستيراد
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
        </x-slot>

        <x-slot name="filters">
            <form id="auto-filter-form" action="{{ route('internal-entities.index') }}" method="GET" class="row g-3 align-items-end">

                @if(request('parent_id') && ($parentEntity = \App\Models\InternalEntity::find(request('parent_id'))))
                    <div class="col-12">
                        <div class="alert alert-info d-flex justify-content-between align-items-center py-2 mb-0 border-0 rounded-3">
                            <span>
                                <x-icon name="corner-down-right" size="14" class="me-1" />
                                <strong>عرض الجهات التابعة لـ:</strong> {{ $parentEntity->name }}
                            </span>
                            <a href="{{ route('internal-entities.index') }}" class="btn btn-sm btn-light text-info fw-bold rounded-pill px-3">
                                <x-icon name="x" size="12" /> إلغاء الفلترة
                            </a>
                        </div>
                    </div>
                @endif

                <div class="col-md-3">
                    <label for="search" class="form-label small fw-semibold">بحث فوري</label>
                    <input type="search" name="search" id="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="ابحث باسم الجهة أو الرمز...">
                </div>

                <div class="col-md-3">
                    <label for="authorityFilterSelect" class="form-label small fw-semibold">نطاق العرض</label>
                    <select name="authority_filter" id="authorityFilterSelect" class="form-select auto-filter"
                        onchange="toggleAuthoritySelector(); this.form.submit()">
                        <option value="none" {{ ($authorityFilter ?? '') === 'none' ? 'selected' : '' }}>لا يعرض شيئاً</option>
                        <option value="all" {{ ($authorityFilter ?? 'all') === 'all' ? 'selected' : '' }}>جميع الجهات</option>
                        <option value="same_governorate" {{ ($authorityFilter ?? '') === 'same_governorate' ? 'selected' : '' }}>نطاق المحافظة</option>
                        <option value="same_directorate" {{ ($authorityFilter ?? '') === 'same_directorate' ? 'selected' : '' }}>نطاق المديرية</option>
                        <option value="specific" {{ ($authorityFilter ?? '') === 'specific' ? 'selected' : '' }}>جهة محددة</option>
                    </select>
                </div>

                <div class="col-md-3" id="authoritySelectWrapper"
                    style="display: {{ in_array($authorityFilter ?? 'all', ['specific', 'same_governorate']) ? 'block' : 'none' }};">
                    <label for="filterAuthorityId" class="form-label small fw-semibold">اختر الجهة المشرفة</label>
                    <select name="authority_id" id="filterAuthorityId" class="form-select auto-filter" onchange="this.form.submit()">
                        <option value="">-- اختر الجهة المشرفة --</option>
                        @foreach($authorities as $auth)
                            <option value="{{ $auth->id }}" {{ (($filterAuthorityId ?? '') == $auth->id) ? 'selected' : '' }}>
                                {{ $auth->agency_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @foreach(request()->except(['search', 'authority_filter', 'authority_id', 'page']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @elseif(!is_null($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
            </form>
        </x-slot>

        <x-slot name="table">
            @can('internal-entities.delete')
            <form id="bulkDeleteForm" action="{{ route('internal-entities.bulk.destroy') }}" method="POST" onsubmit="return confirmBulkDelete()">
            @endcan
                @csrf
                @method('DELETE')

                <table class="table table-hover table-striped align-middle table-compact mb-0">
                    <thead>
                        <tr>
                            @can('internal-entities.delete')
                                <th width="40" class="text-center">
                                    <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAll(this)">
                                </th>
                            @endcan
                            <th width="50">#</th>
                            <th>اسم الجهة</th>
                            <th>الجهة الأم</th>
                            <th>الجهة المشرفة</th>
                            <th class="hide-xl">الموقع</th>
                            <th class="text-center" width="80">الحالة</th>
                            <th class="text-center" width="160">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($internalEntities as $entity)
                            <tr>
                                @can('internal-entities.delete')
                                    <td class="text-center">
                                        <input class="form-check-input row-checkbox" type="checkbox" name="ids[]" value="{{ $entity->id }}">
                                    </td>
                                @endcan
                                <td>
                                    <span class="fw-bold text-primary small">#{{ $entity->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded px-2 py-1">
                                            <x-icon name="sitemap" size="13" />
                                        </div>
                                        <span class="fw-medium small" title="{{ $entity->name }}">{{ $entity->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($entity->parent)
                                        <span class="badge-compact" style="background:#e0f2fe;color:#0369a1;">{{ $entity->parent->name }}</span>
                                    @else
                                        <span class="text-muted small">رئيسية</span>
                                    @endif
                                </td>
                                <td>
                                    @if($entity->authority)
                                        <div class="text-truncate-custom small text-muted" title="{{ $entity->authority->agency_name }}">
                                            {{ $entity->authority->agency_name }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="hide-xl">
                                    <div class="small text-muted">
                                        @if($entity->governorate)
                                            <span class="d-block">{{ $entity->governorate->name }}</span>
                                        @endif
                                        @if($entity->directorate)
                                            <span>{{ $entity->directorate->name }}</span>
                                        @endif
                                        @if(!$entity->governorate && !$entity->directorate)
                                            <span>-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($entity->is_active)
                                        <span class="badge-compact badge-active">
                                            <x-icon name="check" size="10" /> نشط
                                        </span>
                                    @else
                                        <span class="badge-compact badge-disabled">
                                            <x-icon name="x" size="10" /> غير نشط
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        @can('internal-entities.view')
                                            <a href="{{ route('internal-entities.show', $entity) }}"
                                                class="btn btn-action-view btn-icon auth-perm-internal-entities-view position-relative"
                                                title="عرض التفاصيل">
                                                <x-icon name="eye" class="action-icon" />
                                                @if($entity->children_count ?? 0)
                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.55rem;">
                                                        {{ $entity->children_count }}
                                                    </span>
                                                @endif
                                            </a>
                                        @endcan

                                        @can('internal-entities.edit')
                                            <a href="{{ route('internal-entities.edit', $entity) }}"
                                                class="btn btn-action-edit btn-icon auth-perm-internal-entities-edit" title="تعديل">
                                                <x-icon name="edit-2" class="action-icon" />
                                            </a>
                                        @endcan

                                        @can('internal-entities.delete')
                                            <button type="button"
                                                class="btn btn-action-delete btn-icon auth-perm-internal-entities-delete"
                                                data-delete-url="{{ route('internal-entities.destroy', $entity) }}"
                                                onclick="confirmDelete(this)"
                                                title="حذف">
                                                <x-icon name="trash-2" class="action-icon" />
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('internal-entities.delete') ? 8 : 7 }}" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center gap-3">
                                        <div class="p-4 rounded-circle bg-light">
                                            <x-icon name="inbox" size="40" class="text-muted" />
                                        </div>
                                        <div class="text-center">
                                            <h6 class="text-muted mb-1">لا توجد جهات داخلية حالياً</h6>
                                            <small class="text-muted d-block">قم بإضافة جهات جديدة أو جرب تغيير معايير البحث.</small>
                                        </div>
                                        @can('internal-entities.create')
                                            <a href="{{ route('internal-entities.create') }}" class="btn btn-primary-compact btn-compact">
                                                <x-icon name="plus" size="12" /> إضافة جهة
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @can('internal-entities.delete')
                    <div class="d-flex justify-content-start mt-3 px-2">
                        <button type="submit" class="btn btn-compact btn-danger-compact" id="bulkDeleteBtn">
                            <x-icon name="trash-2" size="12" /> حذف المحدد
                        </button>
                    </div>
                @endcan
            </form>

            {{-- نموذج الحذف الفردي المشترك --}}
            @can('internal-entities.delete')
                <form id="single-delete-form" action="" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        </x-slot>

        <x-slot name="pagination">
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    عرض {{ $internalEntities->firstItem() ?? 0 }} - {{ $internalEntities->lastItem() ?? 0 }} من إجمالي
                    {{ $internalEntities->total() }}
                </div>
                <div>
                    {{ $internalEntities->withQueryString()->links() }}
                </div>
            </div>
        </x-slot>

    </x-index-page>

    {{-- مودال الاستيراد --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom-0 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="importModalLabel">
                        <x-icon name="upload" size="16" class="text-success me-2" /> استيراد الجهات
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @can('internal-entities.view')
                <form id="importForm" action="{{ route('internal-entities.preview-import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-4 text-center">
                            <a href="{{ route('internal-entities.download-template') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm">
                                <x-icon name="download" size="13" class="me-1" /> تحميل نموذج Excel المعتمد
                            </a>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label fw-bold small text-muted">اختر ملف البيانات</label>
                            <input type="file" id="file" name="file" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="mb-3">
                            <label for="authority_id" class="form-label fw-bold small text-muted">الجهة المشرفة التلقائية (اختياري)</label>
                            <select name="authority_id" id="authority_id" class="form-select rounded-3">
                                <option value="">-- اختر الجهة المشرفة --</option>
                                @foreach($authorities as $auth)
                                    <option value="{{ $auth->id }}">{{ $auth->agency_name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text small mt-2">
                                <x-icon name="info" size="12" class="me-1" />سيتم ربط جميع الجهات بهذه الجهة ما لم يحدد خلاف ذلك في الملف.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0 rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm">
                            <x-icon name="eye" size="14" class="me-1" /> معاينة البيانات
                        </button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filterForm = document.getElementById('auto-filter-form');
                if (!filterForm) return;

                const searchInput = document.getElementById('search');
                if (searchInput) {
                    let debounceTimer;
                    searchInput.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => filterForm.submit(), 400);
                    });
                }
            });

            function toggleAuthoritySelector() {
                const filter = document.getElementById('authorityFilterSelect').value;
                const wrapper = document.getElementById('authoritySelectWrapper');
                if (filter === 'specific' || filter === 'same_governorate') {
                    wrapper.style.display = 'block';
                } else {
                    wrapper.style.display = 'none';
                    const sel = document.getElementById('filterAuthorityId');
                    if (sel) sel.value = '';
                }
            }

            function toggleAll(master) {
                document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = master.checked);
            }

            function confirmBulkDelete() {
                const checked = document.querySelectorAll('.row-checkbox:checked');
                if (checked.length === 0) {
                    alert('الرجاء تحديد جهة واحدة على الأقل لإتمام عملية الحذف.');
                    return false;
                }
                return confirm(`هل أنت متأكد من رغبتك في حذف ${checked.length} جهة بشكل نهائي؟`);
            }

            function confirmDelete(btn) {
                if (confirm('هل أنت متأكد من حذف هذه الجهة؟')) {
                    var url = btn.getAttribute('data-delete-url');
                    var form = document.getElementById('single-delete-form');
                    form.action = url;
                    form.submit();
                }
            }
        </script>
    @endpush
@endsection
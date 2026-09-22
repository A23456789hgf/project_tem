<!-- Stakeholder Management Modals -->

<!-- Add/Edit Stakeholder Modal -->
<div class="modal fade" id="stakeholderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>إضافة جهة جديدة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            @can('projects.entities.create')
            <form id="stakeholderForm" method="POST" action="{{ route('projects.entities.store', $project->id) }}">
            @endcan
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="entity_name" class="form-label">اسم الجهة *</label>
                                <input type="text" class="form-control" id="entity_name" name="entity_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="entity_type" class="form-label">نوع الجهة *</label>
                                <select class="form-select" id="entity_type" name="entity_type">
                                    <option value="">اختر نوع الجهة</option>
                                    <option value="executing">جهة منفذة</option>
                                    <option value="supervising">جهة مشرفة</option>
                                    <option value="funding">جهة ممولة</option>
                                    <option value="participating">جهة مشاركة</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="entity_task" class="form-label">المهام والمسؤوليات *</label>
                        <textarea class="form-control" id="entity_task" name="entity_task" rows="4" 
                                  placeholder="اكتب المهام والمسؤوليات المكلفة بها هذه الجهة..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">الجهة الرئيسية</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">جهة رئيسية</option>
                                    @foreach($project->entities->where('is_sub_entity', false) as $entity)
                                        <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_sub_entity" name="is_sub_entity" value="1">
                                    <label class="form-check-label" for="is_sub_entity">
                                        جهة فرعية
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">معلومات إضافية</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="contact_person" class="form-label">الشخص المسؤول</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                            <div class="col-md-4">
                                <label for="contact_phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone">
                            </div>
                            <div class="col-md-4">
                                <label for="contact_email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ الجهة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Entity Details Modal -->
<div class="modal fade" id="entityDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>تفاصيل الجهة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="entityDetailsContent">
                <!-- Content will be loaded dynamically -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Team Management Modal -->
<div class="modal fade" id="teamManagementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>إدارة فرق العمل
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h6>فرق العمل الحالية</h6>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-sm btn-primary" onclick="addTeamMember()">
                            <i class="fas fa-plus me-1"></i>إضافة عضو
                        </button>
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-standard table-data table-sm">
                            <thead>
                                <tr>
                                    <th>اسم العضو</th>
                                    <th>المنصب</th>
                                    <th>المهام</th>
                                    <th>الحالة</th>
                                    <th class="table-actions">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="teamMembersTable">
                                <!-- Team members will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Add Team Member Form -->
                <div class="border-top pt-3 mt-3">
                    <h6>إضافة عضو جديد</h6>
                    <form id="addTeamMemberForm">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" placeholder="اسم العضو" name="member_name">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" placeholder="المنصب" name="position">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                    <option value="on_leave">في إجازة</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <textarea class="form-control" placeholder="المهام المكلف بها..." name="tasks" rows="2"></textarea>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-success">
                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-tasks me-2"></i>تفاصيل المهام
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="taskDetailsContent">
                <!-- Task details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Risk Assessment Modal -->
<div class="modal fade" id="riskAssessmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>تقييم المخاطر
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="riskAssessmentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="risk_type" class="form-label">نوع المخاطر</label>
                                <select class="form-select" id="risk_type" name="risk_type">
                                    <option value="">اختر نوع المخاطر</option>
                                    <option value="execution_delay">تأخير في التنفيذ</option>
                                    <option value="funding_shortage">نقص في التمويل</option>
                                    <option value="resource_unavailability">عدم توفر الموارد</option>
                                    <option value="coordination_issues">مشاكل في التنسيق</option>
                                    <option value="external_factors">عوامل خارجية</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="risk_level" class="form-label">مستوى الخطورة</label>
                                <select class="form-select" id="risk_level" name="risk_level">
                                    <option value="">اختر مستوى الخطورة</option>
                                    <option value="low">منخفض</option>
                                    <option value="medium">متوسط</option>
                                    <option value="high">عالي</option>
                                    <option value="critical">حرج</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="risk_description" class="form-label">وصف المخاطر</label>
                        <textarea class="form-control" id="risk_description" name="risk_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mitigation_actions" class="form-label">الإجراءات المقترحة للتخفيف</label>
                        <textarea class="form-control" id="mitigation_actions" name="mitigation_actions" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="responsible_entity" class="form-label">الجهة المسؤولة</label>
                                <select class="form-select" id="responsible_entity" name="responsible_entity">
                                    <option value="">اختر الجهة المسؤولة</option>
                                    @foreach($project->entities as $entity)
                                        <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_date" class="form-label">التاريخ المستهدف للحل</label>
                                <input type="date" class="form-control" id="target_date" name="target_date">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" form="riskAssessmentForm" class="btn btn-danger">
                    <i class="fas fa-save me-2"></i>حفظ تقييم المخاطر
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal management functions
function addTeamMember() {
    // Show add team member form
    document.getElementById('addTeamMemberForm').style.display = 'block';
}

// Form submissions
document.getElementById('stakeholderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Handle stakeholder form submission
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload(); // Reload to show new stakeholder
        } else {
            alert('حدث خطأ أثناء حفظ البيانات');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ البيانات');
    });
});

document.getElementById('addTeamMemberForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Handle team member addition
    const formData = new FormData(this);
    
    // Add team member to table
    const tbody = document.getElementById('teamMembersTable');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${formData.get('member_name')}</td>
        <td>${formData.get('position')}</td>
        <td>${formData.get('tasks')}</td>
        <td><span class="table-badge bg-success">${formData.get('status')}</span></td>
        <td class="table-actions">
            <button type="button" class="btn btn-sm btn-warning" onclick="editTeamMember(this)">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeTeamMember(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    
    // Reset form
    this.reset();
});

function editTeamMember(button) {
    // Edit team member functionality
    const row = button.closest('tr');
    // Implementation for editing team member
}

function removeTeamMember(button) {
    Swal.fire({
        title: 'تأكيد العملية',
        text: 'هل أنت متأكد من حذف هذا العضو؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'لا',
        reverseButtons: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('tr').remove();
        }
    });
}

// Parent entity selection handling
document.getElementById('parent_id').addEventListener('change', function() {
    const isSubEntity = document.getElementById('is_sub_entity');
    if(this.value) {
        isSubEntity.checked = true;
        isSubEntity.disabled = true;
    } else {
        isSubEntity.disabled = false;
    }
});
</script>
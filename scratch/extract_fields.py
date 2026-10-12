import os
import re

views_dir = r'E:\temp_p\project\resources\views\projects'

files_to_scan = [
    'create.blade.php',
    'edit.blade.php',
    'wizard.blade.php',
    'partials/_basic_info.blade.php',
    'partials/_financing.blade.php',
    'partials/_project_cost.blade.php',
    'partials/_project_details.blade.php',
    'partials/_project_objective.blade.php',
    'partials/_review.blade.php',
    'partials/_risks.blade.php',
    'partials/stakeholders_management.blade.php',
    'steps/step-4-preliminary-activities.blade.php',
    'partials/modals/_add_entity_modal.blade.php',
    'partials/modals/_edit_entity_modal.blade.php'
]

print('=== FIELD INVENTORY ===')
for file_name in files_to_scan:
    path = os.path.join(views_dir, file_name)
    if os.path.exists(path):
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
            
            inputs = re.findall(r'<input[^>]+name=[\""\x27]([^\x27\""]+)[\""\x27]', content)
            selects = re.findall(r'<select[^>]+name=[\""\x27]([^\x27\""]+)[\""\x27]', content)
            textareas = re.findall(r'<textarea[^>]+name=[\""\x27]([^\x27\""]+)[\""\x27]', content)
            
            all_fields = set(inputs + selects + textareas)
            if all_fields:
                print(f\"\nFile: {file_name}\")
                for field in sorted(all_fields):
                    print(f\"  - {field}\")

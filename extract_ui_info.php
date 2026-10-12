<?php

$viewsDir = __DIR__.'/resources/views';
$outputFile = __DIR__.'/ui_architecture.md';

$modules = [
    'admin', 'approvals', 'auth', 'configuration', 'consultations',
    'correspondence', 'entity-officers', 'home', 'planning', 'profile',
    'project-requests', 'projects', 'project_financing', 'reports',
    'requests_descend', 'roles', 'roles-permissions', 'stages', 'user', 'value_chains',
];

$output = "# مستند تحليل واجهات النظام (UI Architecture)\n\n";
$output .= "هذا المستند يحتوي على تحليل شامل للواجهات والمكونات في النظام.\n\n";

foreach ($modules as $module) {
    $moduleDir = $viewsDir.'/'.$module;
    if (! is_dir($moduleDir)) {
        continue;
    }

    $output .= '## Module: '.ucfirst($module)."\n\n";

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $path = str_replace($viewsDir.'/', '', $file->getPathname());
            $content = file_get_contents($file->getPathname());

            $output .= '### صفحة: `'.$path."`\n";

            // Extract route or URL (rough guess from forms/links)
            preg_match_all("/route\(['\"]([^'\"]+)['\"]/i", $content, $routes);
            if (! empty($routes[1])) {
                $output .= '**الروابط (Routes) المستخدمة:** '.implode(', ', array_unique($routes[1]))."\n\n";
            }

            // Extract Components
            preg_match_all("/<x-([a-zA-Z0-9\-]+)/", $content, $components);
            if (! empty($components[1])) {
                $output .= '**المكونات (Components):** '.implode(', ', array_unique($components[1]))."\n\n";
            }

            // Extract Modals
            preg_match_all("/id=['\"]([a-zA-Z0-9\-_]*modal[a-zA-Z0-9\-_]*)['\"]/i", $content, $modals);
            if (! empty($modals[1])) {
                $output .= '**النوافذ المنبثقة (Modals):** '.implode(', ', array_unique($modals[1]))."\n\n";
            }

            // Extract Tables
            if (strpos($content, '<table') !== false) {
                preg_match_all("/<th[^>]*>(.*?)<\/th>/is", $content, $th);
                $headers = array_map('trim', array_map('strip_tags', $th[1]));
                $headers = array_filter($headers, function ($h) {
                    return ! empty(trim($h));
                });
                if (! empty($headers)) {
                    $output .= '**الجداول - الأعمدة:** '.implode(' | ', array_unique($headers))."\n\n";
                } else {
                    $output .= "**الجداول:** يوجد جدول في هذه الصفحة.\n\n";
                }
            }

            // Extract Forms
            preg_match_all('/<form[^>]*>/i', $content, $forms);
            if (! empty($forms[0])) {
                $output .= '**النماذج (Forms):** '.count($forms[0])." نموذج.\n";
                // Extract Inputs
                preg_match_all("/<input[^>]*name=['\"]([^'\"]+)['\"][^>]*>/i", $content, $inputs);
                preg_match_all("/<select[^>]*name=['\"]([^'\"]+)['\"][^>]*>/i", $content, $selects);
                preg_match_all("/<textarea[^>]*name=['\"]([^'\"]+)['\"][^>]*>/i", $content, $textareas);
                $allInputs = array_merge($inputs[1], $selects[1], $textareas[1]);
                if (! empty($allInputs)) {
                    $output .= '- **الحقول:** '.implode(', ', array_unique($allInputs))."\n";
                }
                $output .= "\n";
            }

            // Extract Buttons
            preg_match_all("/<button[^>]*>(.*?)<\/button>/is", $content, $buttons);
            preg_match_all("/<a[^>]*class=['\"][^'\"]*btn[^'\"]*['\"][^>]*>(.*?)<\/a>/is", $content, $btnLinks);
            $allBtns = array_merge($buttons[1], $btnLinks[1]);
            $allBtns = array_map('trim', array_map('strip_tags', $allBtns));
            $allBtns = array_filter($allBtns, function ($b) {
                return ! empty(trim($b));
            });
            if (! empty($allBtns)) {
                $output .= '**الأزرار والإجراءات:** '.implode('، ', array_unique($allBtns))."\n\n";
            }

            // Extract Tabs
            if (strpos($content, 'nav-tabs') !== false || strpos($content, 'tab-pane') !== false) {
                $output .= "**التبويبات (Tabs):** يوجد تبويبات في الصفحة.\n\n";
            }

            // Extract Wizards (Step)
            if (strpos($content, 'step') !== false && strpos($content, 'wizard') !== false) {
                $output .= "**متعدد الخطوات (Wizard):** الصفحة تحتوي على معالج خطوات.\n\n";
            }

            $output .= "---\n";
        }
    }
}

file_put_contents($outputFile, $output);
echo "Done. Generated ui_architecture.md\n";

<?php
$file = '/Applications/XAMPP/xamppfiles/htdocs/nas/src/Api/TemplateController.php';
$content = file_get_contents($file);

$search = "    public function previewLiveHotspotHtml(): void\n    {\n        \$data = getJsonBody();";
$replace = "    public function previewLiveHotspotHtml(): void\n    {\n        \$data = getJsonBody();\n        file_put_contents('/Applications/XAMPP/xamppfiles/htdocs/nas/preview_data.log', json_encode(\$data));";

file_put_contents($file, str_replace($search, $replace, $content));

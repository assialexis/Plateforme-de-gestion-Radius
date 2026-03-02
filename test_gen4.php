<?php
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once 'src/Api/TemplateController.php';

class MockDB {}
class TestTemplateController extends TemplateController {
    public function __construct() {
        $this->db = new MockDB();
    }
    public function testGen($template) {
        $method = new ReflectionMethod('TemplateController', 'generateMikroTikHtml');
        $method->setAccessible(true);
        return $method->invoke($this, $template);
    }
}

$c = new TestTemplateController();
$html = $c->testGen([
    'config' => json_encode([
        'slider_images' => ['hotspot_image_1_1771592815.webp'],
        'logo_url' => 'company_logo_1771592860.png'
    ])
]);
echo "HTML for Slides:\n";
function grep($search, $text) {
    foreach(explode("\n", $text) as $line) {
        if(strpos($line, $search) !== false) echo trim($line) . "\n";
    }
}
grep('slide active', $html);
grep('logo-icon', $html);

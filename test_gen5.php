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
file_put_contents('/tmp/preview5.html', $html);
echo shell_exec("grep -a \"hotspot_image_\" /tmp/preview5.html");
echo shell_exec("grep -a \"company_logo_\" /tmp/preview5.html");

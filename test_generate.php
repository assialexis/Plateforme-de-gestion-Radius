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
        'slider_images' => ['uploads/media/hotspot_image_1_1771592815.webp'],
        'logo_url' => 'uploads/media/company_logo_1771592860.png'
    ])
]);
file_put_contents('/tmp/preview4.html', $html);
echo "Result:\n";
grep('hotspot_image', '/tmp/preview4.html');
function grep($search, $file) {
    echo shell_exec("grep -a \"$search\" \"$file\"");
}

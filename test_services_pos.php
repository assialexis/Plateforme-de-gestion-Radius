<?php
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once 'src/Api/TemplateController.php';

class MockDB {
    public function getProfileById($id) { return null; }
}
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
        'services' => [
            ['title' => 'Test Service 1', 'description' => 'Test Desc']
        ]
    ])
]);
file_put_contents('/tmp/preview_svc.html', $html);
$posPricing = strpos($html, 'pricing-section');
$posServices = strpos($html, 'services-section');
echo "Pricing section exists at: " . ($posPricing !== false ? "Yes" : "No") . "\n";
echo "Services section exists at: " . ($posServices !== false ? "Yes" : "No") . "\n";
if ($posPricing !== false && $posServices !== false) {
    echo "Services after Pricing: " . ($posServices > $posPricing ? "Yes" : "No") . "\n";
}

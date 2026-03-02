<?php
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once 'src/Api/TemplateController.php';

class MockDB {
    public function getProfileById($id) {
        return [
            'id' => $id,
            'name' => 'Test Profile',
            'validity_unit' => 'd',
            'validity' => 1,
            'price' => 500,
            'admin_id' => 99
        ];
    }
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
        'selected_profiles' => [123]
    ])
]);
file_put_contents('/tmp/preview_pay.html', $html);
echo shell_exec("grep -a \"pay.php\" /tmp/preview_pay.html");

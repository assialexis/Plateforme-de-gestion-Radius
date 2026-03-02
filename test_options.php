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
    'show_logo' => 0,
    'show_password_field' => 0,
    'show_remember_me' => 1,
    'show_footer' => 0,
    'show_chat_support' => 1,
    'chat_support_type' => 'whatsapp',
    'chat_whatsapp_phone' => '+2376990000',
    'config' => json_encode([])
]);

if (strpos($html, '.logo-icon, .logo img { display: none !important; }') !== false) {
    echo "Logo hidden ok\n";
}
if (strpos($html, '#password-group, #tab-membre { display: none !important; }') !== false) {
    echo "Password hidden ok\n";
}
if (strpos($html, 'name="remember"') !== false) {
    echo "Remember me ok\n";
}
if (strpos($html, 'wa.me') !== false) {
    echo "Chat support ok\n";
}

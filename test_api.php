<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['session_id'] = 'test';

require 'src/Auth/AuthService.php';
require 'src/Radius/RadiusDatabase.php';
require 'src/Api/PaymentController.php';

$config = require 'config/config.php';
$db = $config['database'];
$pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}", $db['username'], $db['password']);

$auth = clone(new class($pdo) extends AuthService {
    public function __construct($pdo) { parent::__construct($pdo); }
    public function getAdminId(?int $userId = null): ?int { return 1; }
});

$radiusDb = clone(new class($config, $pdo) extends RadiusDatabase {
    public function __construct($config, $pdo) { 
        parent::__construct($config, null); 
        $this->pdo = $pdo;
    }
});

function jsonSuccess($data) { print_r($data); }
function jsonError($msg, $code) { echo "ERROR: $msg ($code)\n"; }

$controller = new PaymentController($radiusDb, $auth);
$controller->index();
?>

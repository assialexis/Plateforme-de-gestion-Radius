<?php
$config = require 'config/config.php';
require 'src/Radius/RadiusDatabase.php';

$db = new RadiusDatabase($config);

echo "Getting active gateways for admin_id 1\n";
print_r($db->getActivePaymentGateways(1));

echo "Getting active gateways for admin_id 2\n";
print_r($db->getActivePaymentGateways(2));

echo "Getting global active gateways (admin_id NULL)\n";
print_r($db->getActiveGlobalRechargeGateways());

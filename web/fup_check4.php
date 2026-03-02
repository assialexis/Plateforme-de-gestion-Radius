<?php
// Vérifier ce que setActiveQueueSpeed génère MAINTENANT
require_once __DIR__ . '/../src/Mikrotik/CommandSender.php';

// Lire directement le fichier pour voir le code source
$file = __DIR__ . '/../src/Mikrotik/CommandSender.php';
$content = file_get_contents($file);

// Chercher setActiveQueueSpeed
preg_match('/function setActiveQueueSpeed.*?^    \}/ms', $content, $matches);
echo "=== setActiveQueueSpeed dans le fichier ===\n";
echo $matches[0] ?? "NON TROUVE";
echo "\n\n=== Chemin du fichier ===\n";
echo realpath($file);

unlink(__FILE__);

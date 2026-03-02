<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache cleared";
} else {
    echo "opcache not enabled";
}
// Auto-suppression
unlink(__FILE__);

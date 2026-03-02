<?php
$files = ['lang/fr.php', 'lang/en.php'];
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Remove the bad keys
    $content = preg_replace("/\s*'sales\.system_notifications'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.system_notifications_desc'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.new_notification'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.edit_notification'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.notification_title'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.notification_type'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.notification_message'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.notification_reads'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.notification_created'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'sales\.no_notifications'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'otp\.sys_notifications'.*?,\n/s", "", $content);
    $content = preg_replace("/\s*'\/\/ Notifications Système'.*?\n/s", "", $content);
    
    // Also remove the bad en.php ones
    $content = preg_replace("/\s*'superadmin\.update_rate_success'.*?,\n/s", "", $content);
    
    file_put_contents($file, $content);
}

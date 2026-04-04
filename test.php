<?php
$paths = ['/tmp', '/var/www/html/uploads', '/var/www/html', '/tmp/uploads'];
foreach ($paths as $p) {
    echo $p . ' — exists: ' . (is_dir($p) ? 'yes' : 'no') . ' — writable: ' . (is_writable($p) ? 'YES' : 'NO') . '<br>';
}

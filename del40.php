<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->set_charset('utf8mb4');
$db->query("DELETE FROM oc_custom_tag WHERE tag_id=40");
echo "deleted 40\n";

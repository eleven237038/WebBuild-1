<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->set_charset('utf8mb4');
$r = $db->query("SELECT tag_id, name, parent_id, tag_type, status FROM oc_custom_tag WHERE name LIKE 'AutoL%' ORDER BY tag_id");
while ($row = $r->fetch_assoc()) echo "id={$row['tag_id']} name={$row['name']} parent={$row['parent_id']} type={$row['tag_type']} status={$row['status']}\n";

<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->set_charset('utf8mb4');
$r = $db->query("SELECT tag_id, name, parent_id, tag_type FROM oc_custom_tag WHERE name IN ('AutoL1','AutoL2','AutoL3','TmpField','TestStruct') ORDER BY tag_id");
while ($row = $r->fetch_assoc()) echo "{$row['name']}=>id={$row['tag_id']} parent={$row['parent_id']} type={$row['tag_type']}\n";

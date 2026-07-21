<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
if ($db->connect_errno) { die("connect: " . $db->connect_error); }
$db->set_charset('utf8mb4');
$r = $db->query("SELECT status, COUNT(*) c FROM oc_custom_tag GROUP BY status");
while ($row = $r->fetch_assoc()) { echo "status={$row['status']} count={$row['c']}\n"; }
echo "--- structs ---\n";
$r = $db->query("SELECT tag_id, name, parent_id, sort_order FROM oc_custom_tag WHERE tag_type='struct' ORDER BY sort_order");
while ($row = $r->fetch_assoc()) { echo "id={$row['tag_id']} name={$row['name']} parent={$row['parent_id']} sort={$row['sort_order']}\n"; }

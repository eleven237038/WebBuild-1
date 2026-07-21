<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->set_charset('utf8mb4');
$r = $db->query("SELECT tag_id, name, product_type_id, parent_id, tag_type FROM oc_custom_tag WHERE tag_id=37");
echo "struct37: "; print_r($r->fetch_assoc());
echo "--- product types ---\n";
$r = $db->query("SELECT product_type_id, name FROM oc_product_type ORDER BY sort_order");
while ($row = $r->fetch_assoc()) echo "ptid={$row['product_type_id']} name={$row['name']}\n";

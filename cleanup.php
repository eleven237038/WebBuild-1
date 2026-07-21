<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->set_charset('utf8mb4');
$ids = [41, 42, 43, 44];
$idlist = implode(',', $ids);
$db->query("DELETE FROM oc_product_to_custom_tag WHERE tag_id IN ($idlist)");
$db->query("DELETE FROM oc_custom_tag_option WHERE tag_id IN ($idlist)");
$db->query("DELETE FROM oc_custom_tag WHERE tag_id IN ($idlist)");
echo "deleted: $idlist\n";
// Verify remaining structs + TestStruct/Color demo intact
$r = $db->query("SELECT tag_id, name, parent_id, tag_type FROM oc_custom_tag WHERE tag_type='struct' OR tag_id=38 ORDER BY tag_id");
echo "--- remaining structs + Color(38) ---\n";
while ($row = $r->fetch_assoc()) echo "id={$row['tag_id']} name={$row['name']} parent={$row['parent_id']} type={$row['tag_type']}\n";
echo "--- total custom_tag count ---\n";
echo $db->query("SELECT COUNT(*) c FROM oc_custom_tag")->fetch_assoc()['c'] . "\n";

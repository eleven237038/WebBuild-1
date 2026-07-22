<?php
require "/var/www/html/config.php";
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_errno) { die("connect failed: ".$db->connect_error); }
$db->set_charset("utf8mb4");
echo "=== type 1 fields: tag_id | name | system_column | field_type | tag_type | parent_id | sort_order | status ===\n";
$r = $db->query("SELECT tag_id, name, system_column, field_type, tag_type, parent_id, sort_order, status FROM oc_custom_tag WHERE product_type_id=1 ORDER BY sort_order, tag_id");
while ($row = $r->fetch_assoc()) {
    echo $row["tag_id"]." | ".($row["name"]??"")." | ".($row["system_column"]??"")." | ".($row["field_type"]??"")." | ".($row["tag_type"]??"")." | ".($row["parent_id"]??"")." | ".($row["sort_order"]??"")." | ".($row["status"]??"")."\n";
}
echo "\n=== system_column fields across all types ===\n";
$r = $db->query("SELECT tag_id, product_type_id, name, system_column FROM oc_custom_tag WHERE system_column IS NOT NULL AND system_column<>'' ORDER BY product_type_id, tag_id");
while ($row = $r->fetch_assoc()) echo $row["tag_id"]." | type".$row["product_type_id"]." | ".($row["name"]??"")." | ".($row["system_column"]??"")."\n";
echo "\n=== sample product custom_tags (product_id=50) tag_id|name|system_column|parent_name|value ===\n";
$r = $db->query("SELECT t.tag_id, t.name, t.system_column, IFNULL(p.name,'') AS parent_name, IFNULL(o.label, pt.value) AS value FROM oc_custom_tag t INNER JOIN oc_product_to_custom_tag pt ON t.tag_id=pt.tag_id LEFT JOIN oc_custom_tag p ON t.parent_id=p.tag_id LEFT JOIN oc_custom_tag_option o ON t.tag_id=o.tag_id AND o.value=pt.value WHERE pt.product_id=50 AND t.status=1 AND t.tag_type<>'struct' ORDER BY t.parent_id, t.sort_order LIMIT 20");
while ($row = $r->fetch_assoc()) echo $row["tag_id"]." | ".($row["name"]??"")." | ".($row["system_column"]??"")." | ".($row["parent_name"]??"")." | ".($row["value"]??"")."\n";
echo "\n=== product 50 basic ===\n";
$r = $db->query("SELECT product_id, product_type_id, price, image FROM oc_product WHERE product_id=50");
print_r($r->fetch_assoc());

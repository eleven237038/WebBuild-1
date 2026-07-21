<?php
require '/var/www/html/config.php';
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$db->set_charset('utf8mb4');
$r = $db->query("SELECT product_id FROM oc_product ORDER BY product_id LIMIT 1");
echo "product_id=" . $r->fetch_assoc()['product_id'] . "\n";

<?php
// Boot a minimal OpenCart registry to call the catalog product model's getProducts
// for category 59, bypassing all HTTP/APCu page caching. Ground truth.
$base = '/var/www/html';
require($base . '/config.php');

require(DIR_SYSTEM . 'startup.php');
$registry = new Registry();
$loader = new Loader($registry);
$registry->set('load', $loader);
$config = new Config();
$registry->set('config', $config);
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);
$config->load('default');
$config->set('config_language_id', 1);
$config->set('config_store_id', 0);

$loader->model('catalog/product');
$m = $registry->get('model_catalog_product');

$filter_data = array(
    'filter_category_id' => 59,
    'filter_filter'      => '',
    'sort'               => 'p.sort_order',
    'order'              => 'ASC',
    'start' => 0,
    'limit' => 20,
);
$results = $m->getProducts($filter_data);
echo "getProducts(category 59) returned " . count($results) . " products:\n";
foreach ($results as $r) {
    echo "  pid={$r['product_id']} type={$r['product_type_id']} status={$r['status']} name=" . (isset($r['name'])?$r['name']:'?') . " price={$r['price']}\n";
}
echo "Total (getTotalProducts): " . $m->getTotalProducts($filter_data) . "\n";

// Also test handleSingleProduct for pid 64
echo "\n--- handleSingleProduct(64) ---\n";
$p64 = $m->getProduct(64);
if ($p64) {
    echo "getProduct(64): pid={$p64['product_id']} type={$p64['product_type_id']} name={$p64['name']} price={$p64['price']}\n";
    $card = $m->handleSingleProduct($p64, 100, 100);
    echo "card show_image={$card['show_image']} show_name={$card['show_name']} show_price={$card['show_price']} show_desc={$card['show_description']} show_badges={$card['show_badges']}\n";
    echo "card name_value=[" . substr($card['name_value'],0,40) . "] price_value=[" . substr(var_export($card['price_value'],true),0,40) . "]\n";
} else {
    echo "getProduct(64) returned NULL/empty!\n";
}

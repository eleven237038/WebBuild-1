<?php
/**
 * Realtime product-card test (curl-based, NO screenshots).
 * Verifies the user's three realtime requirements end-to-end:
 *   1. Cards render from DB (商品管理 + custom_tags)            -> baseline
 *   2. 商品卡片 style edit reflects immediately                  -> settings.ver
 *   3. 商品管理 add/delete reflects immediately                  -> content.ver
 *   4. Field-mapping (show_badges hide/show) reflects immediately-> settings.ver
 *   5. New data-driven affordances (SALE badge + strikethrough) render when data exists
 *
 * Mechanism: system/framework.php page_fw APCu cache key embeds
 *   filemtime(content.ver) + filemtime(settings.ver). Each save bumps the
 *   relevant stamp via oc_event/perf.bump (products) or invalidateSettingsCache
 *   (settings). We simulate a save by writing the DB row + touching the stamp,
 *   then curl and assert the rendered HTML changed.
 *
 * sleep(1) before each stamp touch: filemtime has 1-second resolution, so two
 * touches in the same second would yield the same cache key and mask a change.
 */

$BASE = 'http://127.0.0.1/';
$CACHE = '/var/www/html/system/storage/cache/';
$SETTINGS_VER = $CACHE . 'settings.ver';
$CONTENT_VER = $CACHE . 'content.ver';
$MARK = '__TEST_CARD_REALTIME__';   // unique product-name marker

$pass = 0; $fail = 0;
function ok($label, $cond, $got = '', $exp = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "[PASS] $label\n"; }
    else       { $fail++; echo "[FAIL] $label" . ($got !== '' || $exp !== '' ? " | got=[$got] exp=[$exp]" : "") . "\n"; }
}
function fetch($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 12]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$html, $code];
}
function card_count($html) { return substr_count($html, 'class="product-card"'); }
function bump($file) { sleep(1); @touch($file); }  // sleep guarantees mtime advances

// ---- DB (credentials sourced from OpenCart config.php, not hardcoded) ----
// config.php is pure define()s (DB_HOSTNAME/USERNAME/PASSWORD/DATABASE/PORT),
// resolvable via getenv() with safe fallbacks - safe to require here.
require __DIR__ . '/config.php';
$m = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($m->connect_errno) { die("connect: " . $m->connect_error); }
$m->query("SET NAMES utf8mb4");

// ---- 0. Pre-clean any leftover test product from a prior aborted run ----
$res = $m->query("SELECT product_id FROM oc_product_description WHERE name='" . $MARK . "'");
$old = array();
while ($r = $res->fetch_assoc()) { $old[] = (int)$r['product_id']; }
foreach ($old as $pid) {
    foreach (['oc_product','oc_product_description','oc_product_to_store','oc_product_to_category','oc_product_special','oc_product_to_custom_tag'] as $t) {
        $m->query("DELETE FROM $t WHERE product_id=$pid");
    }
}
if ($old) { bump($CONTENT_VER); }

// =========================================================
echo "\n=== 1. BASELINE: cards render from DB ===\n";
list($home, $c) = fetch($BASE . 'index.php?route=common/home');
ok("home HTTP 200", $c === 200, $c, '200');
$hc = card_count($home);
ok("home renders >0 cards", $hc > 0, $hc, '>0');
preg_match('/<h3 class="pcard-name"[^>]*>([^<]+)<\/h3>/', $home, $hm);
preg_match('/<span class="pcard-price"[^>]*>([^<]+)<\/span>/', $home, $hp);
ok("home card maps real DB name", !empty($hm[1]) && $hm[1] !== $MARK, $hm[1] ?? '', 'a real product name');
ok("home card maps real DB price", !empty($hp[1]), $hp[1] ?? '', 'a price');

list($shop, $c) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("shop HTTP 200", $c === 200, $c, '200');
$base_shop = card_count($shop);
ok("shop renders >0 cards", $base_shop > 0, $base_shop, '>0');
echo "    baseline: home=$hc cards, shop=$base_shop cards\n";

// =========================================================
echo "\n=== 2. STYLE EDIT REALTIME (primary_color -> settings.ver) ===\n";
$orig = $m->query("SELECT value FROM oc_setting WHERE code='product_card' AND `key`='product_card_primary_color'")->fetch_assoc()['value'];
$test_color = '#FF6B6B';
$m->query("UPDATE oc_setting SET value='" . $test_color . "' WHERE code='product_card' AND `key`='product_card_primary_color'");
bump($SETTINGS_VER);
list($home2, $_) = fetch($BASE . 'index.php?route=common/home');
$seen = (strpos($home2, '--pcard-primary:' . $test_color) !== false);
ok("primary_color=$test_color appears in card inline style", $seen, $seen ? 'yes' : 'no', 'yes');
// rollback
$m->query("UPDATE oc_setting SET value='" . $m->real_escape_string($orig) . "' WHERE code='product_card' AND `key`='product_card_primary_color'");
bump($SETTINGS_VER);

// =========================================================
echo "\n=== 3. FIELD-MAP REALTIME (show_badges hide/show -> settings.ver) ===\n";
// badges has NO system-column fallback, so '' truly hides and a tag_id shows.
$orig_badges = $m->query("SELECT value FROM oc_setting WHERE code='product_card' AND `key`='product_card_show_badges'")->fetch_assoc()['value'];
// baseline: badges blank -> no tag badge
list($shop_b0, $_) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("baseline: no pcard-badge--tag when show_badges blank", strpos($shop_b0, 'pcard-badge--tag') === false, strpos($shop_b0, 'pcard-badge--tag') === false ? 'absent' : 'present', 'absent');
// map badges to tag 7 (name system field) -> every product has a name -> badge renders
$m->query("UPDATE oc_setting SET value='7' WHERE code='product_card' AND `key`='product_card_show_badges'");
bump($SETTINGS_VER);
list($shop_b1, $_) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("show_badges=7 -> pcard-badge--tag renders", strpos($shop_b1, 'pcard-badge--tag') !== false, strpos($shop_b1, 'pcard-badge--tag') !== false ? 'present' : 'absent', 'present');
// rollback
$m->query("UPDATE oc_setting SET value='' WHERE code='product_card' AND `key`='product_card_show_badges'");
bump($SETTINGS_VER);
list($shop_b2, $_) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("rollback show_badges='' -> pcard-badge--tag gone", strpos($shop_b2, 'pcard-badge--tag') === false, strpos($shop_b2, 'pcard-badge--tag') === false ? 'absent' : 'present', 'absent');
if ($orig_badges !== '') { $m->query("UPDATE oc_setting SET value='" . $m->real_escape_string($orig_badges) . "' WHERE code='product_card' AND `key`='product_card_show_badges'"); bump($SETTINGS_VER); }

// =========================================================
echo "\n=== 4. PRODUCT ADD REALTIME (new 商品 -> content.ver) ===\n";
$m->query("INSERT INTO oc_product (model,product_type_id,sku,upc,ean,jan,isbn,mpn,location,quantity,stock_status_id,image,manufacturer_id,shipping,price,tax_class_id,date_available,weight,weight_class_id,length,width,height,length_class_id,subtract,minimum,sort_order,status,viewed,date_added,date_modified,show_on_homepage) VALUES ('__TEST_CARD__',1,'','','','','','','',10,7,'',0,1,123.4500,0,CURDATE(),0,0,0,0,0,0,1,1,999,1,0,NOW(),NOW(),0)");
$pid = (int)$m->insert_id;
$m->query("INSERT INTO oc_product_description (product_id,language_id,name,description,tag,meta_title,meta_description,meta_keyword) VALUES ($pid,1,'" . $MARK . "','realtime test desc','','" . $MARK . "','',''),($pid,2,'" . $MARK . "','realtime test desc','','" . $MARK . "','','')");
$m->query("INSERT INTO oc_product_to_store (product_id,store_id) VALUES ($pid,0)");
$m->query("INSERT INTO oc_product_to_category (product_id,category_id) VALUES ($pid,59)");
// special price -> exercises the SALE affordance + strikethrough original price.
// Use a real date range: MySQL NO_ZERO_DATE rejects '0000-00-00' under strict mode.
$m->query("INSERT INTO oc_product_special (product_id,customer_group_id,priority,price,date_start,date_end) VALUES ($pid,1,1,99.0000,'2000-01-01','2038-12-31')");
bump($CONTENT_VER);
list($shop_add, $_) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("new product '" . $MARK . "' appears on shop", strpos($shop_add, $MARK) !== false, strpos($shop_add, $MARK) !== false ? 'yes' : 'no', 'yes');
$after_add = card_count($shop_add);
ok("shop card count +1 after add", $after_add === $base_shop + 1, $after_add, ($base_shop + 1));

echo "\n=== 5. DATA-DRIVEN AFFORDANCES (SALE badge + strikethrough) ===\n";
ok("SALE badge (.pcard-badge--sale) renders for special product", strpos($shop_add, 'pcard-badge--sale') !== false, strpos($shop_add, 'pcard-badge--sale') !== false ? 'yes' : 'no', 'yes');
ok("strikethrough original price (.pcard-price-old) renders", strpos($shop_add, 'pcard-price-old') !== false, strpos($shop_add, 'pcard-price-old') !== false ? 'yes' : 'no', 'yes');

// =========================================================
echo "\n=== 6. PRODUCT DELETE REALTIME (gone -> content.ver) ===\n";
foreach (['oc_product','oc_product_description','oc_product_to_store','oc_product_to_category','oc_product_special','oc_product_to_custom_tag'] as $t) {
    $m->query("DELETE FROM $t WHERE product_id=$pid");
}
bump($CONTENT_VER);
list($shop_del, $_) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("deleted product gone from shop", strpos($shop_del, $MARK) === false, strpos($shop_del, $MARK) === false ? 'yes' : 'no', 'yes');
$after_del = card_count($shop_del);
ok("shop card count restored after delete", $after_del === $base_shop, $after_del, $base_shop);

// =========================================================
echo "\n=== 7. POST-TEST INTEGRITY ===\n";
list($homeF, $_) = fetch($BASE . 'index.php?route=common/home');
ok("home still renders cards after all tests", card_count($homeF) === $hc, card_count($homeF), $hc);
// ensure no test marker leaked anywhere
list($shopF, $_) = fetch($BASE . 'index.php?route=product/category&path=59');
ok("no test marker left on shop", strpos($shopF, $MARK) === false, strpos($shopF, $MARK) === false ? 'clean' : 'leaked', 'clean');

$m->close();
echo "\n========================================\n";
echo "RESULT: $pass passed, $fail failed\n";
echo "========================================\n";
exit($fail > 0 ? 1 : 0);

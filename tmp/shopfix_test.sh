#!/usr/bin/env bash
# Verify the shop-category fix (admin/controller/catalog/product.php):
#   Test 1 (edit, preserve):  product 64 already in cat 59; bare edit (no product_category) -> stays in 59
#   Test 2 (edit, default-empty): remove 64 from cat 59; bare edit -> code re-defaults to 59
#   Test 3 (add, no category):  POST add with NO product_category -> new product auto-assigned to 59
set -u
PID=64
BASE="http://localhost:8081"
JAR=/tmp/shopfix_cookies.txt
SHOP="$BASE/index.php?route=product/category&path=59&nocache=1"
D=/tmp/shopfix_dumps
mkdir -p "$D"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uroot -proot1234 opencart 2>/dev/null; }
PASS=0; FAIL=0
pass(){ echo "PASS  $1"; PASS=$((PASS+1)); }
fail(){ echo "FAIL  $1"; FAIL=$((FAIL+1)); }

build_baseline() {
  docker exec -i genscript-mysql mysqldump -uroot -proot1234 --no-create-info --skip-triggers --where="product_id=64" opencart oc_product 2>/dev/null > "$D/baseline_oc_product.sql"
  for t in oc_product_to_category oc_product_to_custom_tag oc_product_to_store oc_product_description; do
    docker exec -i genscript-mysql mysqldump -uroot -proot1234 --no-create-info --skip-triggers --where="product_id=64" opencart $t 2>/dev/null > "$D/baseline_$t.sql"
  done
}
restore_baseline() {
  docker exec -i genscript-mysql mysql -uroot -proot1234 opencart 2>/dev/null < "$D/baseline_oc_product.sql"
  for t in oc_product_to_category oc_product_to_custom_tag oc_product_to_store oc_product_description; do
    docker exec -i genscript-mysql mysql -uroot -proot1234 opencart 2>/dev/null < "$D/baseline_$t.sql"
  done
}

cat_in59() { echo "SELECT COUNT(*) FROM oc_product_to_category WHERE product_id=$1 AND category_id=59" | sql | tail -1; }
card_present() { curl -s "$SHOP" 2>/dev/null | grep -c "cart.add('$1'"; }
shop_count() { curl -s "$SHOP" 2>/dev/null | grep -o "cart.add('[0-9]*'" | sort -u | wc -l; }
clearcache(){ curl -s "$BASE/data-reset.php" >/dev/null 2>&1; }

echo "=== build baseline (product 64) ==="
build_baseline
echo "baseline: 64 in cat59 = $(cat_in59 64) (expect 1)"

echo "=== LOGIN ==="
rm -f "$JAR"
curl -s -c "$JAR" "$BASE/admin/index.php?route=common/login" -o /dev/null 2>/dev/null
HDR=$(curl -s -c "$JAR" -b "$JAR" -D - -o /dev/null -d "username=admin" -d "password=admin123" "$BASE/admin/index.php?route=common/login" 2>/dev/null)
TOKEN=$(echo "$HDR" | grep -oiE 'user_token=[a-z0-9]+' | head -1 | cut -d= -f2)
[ -z "$TOKEN" ] && { echo "LOGIN FAILED"; exit 1; }
echo "token=$TOKEN"

bare_edit() {
  curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
    -d "product_description[2][name]=EDIT_$$" \
    -d "product_description[2][meta_title]=EDIT_$$" \
    -d "product_description[2][description]=saved" \
    -d "product_type_id=1" \
    "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null
}

echo "=== TEST 1: edit preserve (64 already in 59, no product_category posted) ==="
restore_baseline
CODE=$(bare_edit); echo "save HTTP=$CODE (302=ok)"
sleep 0.3; clearcache
C=$(cat_in59 64); echo "64 in cat59 = $C (expect 1)"
P=$(card_present 64); echo "card64 present = $P (expect >=1)"
[ "$C" = "1" ] && [ "$P" -ge 1 ] && pass "edit preserves existing category 59" || fail "edit dropped category"

echo "=== TEST 2: edit default-empty (remove 64 from 59 first, then bare edit) ==="
restore_baseline
echo "DELETE FROM oc_product_to_category WHERE product_id=64 AND category_id=59;" | sql >/dev/null
echo "after manual remove: 64 in cat59 = $(cat_in59 64) (expect 0)"
CODE=$(bare_edit); echo "save HTTP=$CODE"
sleep 0.3; clearcache
C=$(cat_in59 64); echo "after bare edit: 64 in cat59 = $C (expect 1 -- code default kicked in)"
P=$(card_present 64); echo "card64 present = $P (expect >=1)"
[ "$C" = "1" ] && [ "$P" -ge 1 ] && pass "edit re-defaults empty category to 59" || fail "edit did not re-default category"

echo "=== TEST 3: add new product with NO product_category -> auto-assigned to 59 ==="
restore_baseline
UNIQ="ADDTEST_$$"
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
  -d "product_description[2][name]=$UNIQ" \
  -d "product_description[2][meta_title]=$UNIQ" \
  -d "product_description[2][description]=" \
  -d "product_description[2][tag]=" \
  -d "product_description[2][meta_description]=" \
  -d "product_description[2][meta_keyword]=" \
  -d "product_type_id=1" \
  -d "model=$UNIQ" -d "sku=" -d "upc=" -d "ean=" -d "jan=" -d "isbn=" -d "mpn=" -d "location=" \
  -d "quantity=10" -d "minimum=1" -d "subtract=1" -d "stock_status_id=7" \
  -d "date_available=2026-07-23" -d "manufacturer_id=0" -d "shipping=1" \
  -d "price=9.99" -d "points=0" -d "weight=0" -d "weight_class_id=1" \
  -d "length=0" -d "width=0" -d "height=0" -d "length_class_id=1" \
  -d "status=1" -d "tax_class_id=0" -d "sort_order=0" \
  "$BASE/admin/index.php?route=catalog/product/add&user_token=$TOKEN" 2>/dev/null)
echo "add HTTP=$CODE (302=ok)"
NEWPID=$(echo "SELECT product_id FROM oc_product_description WHERE name='$UNIQ' ORDER BY product_id DESC LIMIT 1" | sql | tail -1)
echo "new product_id=$NEWPID"
if [ -n "$NEWPID" ]; then
  C=$(cat_in59 "$NEWPID"); echo "new product in cat59 = $C (expect 1)"
  ST=$(echo "SELECT status FROM oc_product WHERE product_id=$NEWPID" | sql | tail -1)
  echo "new product status=$ST (expect 1)"
  sleep 0.3; clearcache
  P=$(card_present "$NEWPID"); echo "new product card on shop = $P (expect >=1)"
  [ "$C" = "1" ] && [ "$ST" = "1" ] && [ "$P" -ge 1 ] && pass "add auto-assigns new product to shop category" || fail "add did not assign category"
  echo "=== cleanup: delete test product $NEWPID ==="
  echo "DELETE FROM oc_product WHERE product_id=$NEWPID; DELETE FROM oc_product_description WHERE product_id=$NEWPID; DELETE FROM oc_product_to_category WHERE product_id=$NEWPID; DELETE FROM oc_product_to_store WHERE product_id=$NEWPID; DELETE FROM oc_product_to_custom_tag WHERE product_id=$NEWPID;" | sql >/dev/null
else
  fail "add did not create product"
fi

echo "=== restore product 64 baseline ==="
restore_baseline
clearcache

echo "=== FINAL: shop shows all 11 status=1 products ==="
restore_baseline
# Re-apply the data fix (all 11 -> cat 59) in case baseline restore only touched 64
echo "INSERT IGNORE INTO oc_product_to_category (product_id, category_id) SELECT product_id, 59 FROM oc_product WHERE status=1;" | sql >/dev/null
clearcache
N=$(shop_count); echo "shop card count = $N (expect 11)"
ALL=$(curl -s "$SHOP" 2>/dev/null | grep -o "cart.add('[0-9]*'" | sort -u | tr '\n' ' ')
echo "shop products: $ALL"
[ "$N" = "11" ] && pass "shop shows all 11 products" || fail "shop count=$N"

echo "===== SHOP CATEGORY FIX: PASS=$PASS FAIL=$FAIL ====="

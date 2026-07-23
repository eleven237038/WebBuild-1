#!/usr/bin/env bash
# Admin SAVE flow test: login -> POST edit for product 64 -> verify frontend card.
PID=64
BASE="http://localhost:8081"
JAR=/tmp/admin_cookies.txt
SHOP="$BASE/index.php?route=product/category&path=59&nocache=1"
HOME="$BASE/?nocache=1"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_category.sql
}
PASS=0; FAIL=0
card_name() { curl -s "$SHOP" 2>/dev/null | grep -oE 'pcard-name[^>]*>[^<]+' | grep -oE '>[^<]+$' | tr '\n' ',' ; }
card_present() { curl -s "$SHOP" 2>/dev/null | grep -c "cart.add('$PID'"; }
http_ok() { local c; c=$(curl -s -o /dev/null -w '%{http_code}' "$1" 2>/dev/null); [ "$c" = "200" ] && return 0 || return 1; }

echo "=== LOGIN ==="
rm -f "$JAR"
curl -s -c "$JAR" "$BASE/admin/index.php?route=common/login" -o /dev/null 2>/dev/null
HDR=$(curl -s -c "$JAR" -b "$JAR" -D - -o /dev/null -d "username=admin" -d "password=admin123" "$BASE/admin/index.php?route=common/login" 2>/dev/null)
TOKEN=$(echo "$HDR" | grep -oiE 'user_token=[a-z0-9]+' | head -1 | cut -d= -f2)
[ -z "$TOKEN" ] && { echo "LOGIN FAILED"; exit 1; }
echo "token=$TOKEN"

post_edit() {
  # $1 = extra POST data (urlencoded, &-joined). Returns HTTP code.
  local extra="$1"
  curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
    -d "product_description[2][name]=ADMINSAVE_$$" \
    -d "product_description[2][meta_title]=ADMINSAVE_$$" \
    -d "product_description[2][description]=saved" \
    -d "product_type_id=1" \
    $extra \
    "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null
}

echo "=== TEST 1: edit name via admin (card should show new name) ==="
restore_p64
CODE=$(post_edit "")
echo "save HTTP=$CODE (expect 302 redirect on success)"
sleep 0.3
NAME=$(card_name)
echo "card names: $NAME"
if echo "$NAME" | grep -q "ADMINSAVE_$$"; then echo "PASS  admin-edit-name reflects"; PASS=$((PASS+1)); else echo "FAIL  admin-edit-name not reflected"; FAIL=$((FAIL+1)); fi

echo "=== TEST 2: bare edit (minimal POST) - preserve status/store/en-gb, card still visible ==="
restore_p64
CODE=$(post_edit "")
echo "save HTTP=$CODE"
# Check status preserved =1, store=0 present, both lang rows present
ST=$(echo "SELECT status FROM oc_product WHERE product_id=$PID" | sql | tail -1)
STORE=$(echo "SELECT COUNT(*) FROM oc_product_to_store WHERE product_id=$PID AND store_id=0" | sql | tail -1)
LANGS=$(echo "SELECT COUNT(*) FROM oc_product_description WHERE product_id=$PID" | sql | tail -1)
echo "status=$ST (expect 1) store0=$STORE (expect 1) lang_rows=$LANGS (expect 2)"
PRES=$(card_present)
echo "card present on shop: $PRES"
if [ "$ST" = "1" ] && [ "$STORE" = "1" ] && [ "$LANGS" = "2" ] && [ "$PRES" -ge 1 ]; then echo "PASS  bare-edit preserves visibility"; PASS=$((PASS+1)); else echo "FAIL  bare-edit preserve"; FAIL=$((FAIL+1)); fi

echo "=== TEST 3: edit changing type to 6 via admin (card must still display) ==="
restore_p64
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
  -d "product_description[2][name]=TYP6ADMIN_$$" \
  -d "product_description[2][meta_title]=TYP6ADMIN_$$" \
  -d "product_type_id=6" \
  "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null)
echo "save HTTP=$CODE"
TYPE=$(echo "SELECT product_type_id FROM oc_product WHERE product_id=$PID" | sql | tail -1)
PRES=$(card_present)
NAME=$(card_name)
echo "type=$TYPE card_present=$PRES names=$NAME"
if [ "$TYPE" = "6" ] && [ "$PRES" -ge 1 ]; then echo "PASS  admin type-change card displays"; PASS=$((PASS+1)); else echo "FAIL  admin type-change"; FAIL=$((FAIL+1)); fi

echo "=== TEST 4: edit with status=0 via admin (card should HIDE) ==="
restore_p64
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
  -d "product_description[2][name]=HIDE_$$" \
  -d "product_description[2][meta_title]=HIDE_$$" \
  -d "product_type_id=1" \
  -d "status=0" \
  "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null)
echo "save HTTP=$CODE"
ST=$(echo "SELECT status FROM oc_product WHERE product_id=$PID" | sql | tail -1)
PRES=$(card_present)
echo "status=$ST card_present=$PRES (expect 0)"
if [ "$ST" = "0" ] && [ "$PRES" = "0" ]; then echo "PASS  admin status=0 hides card"; PASS=$((PASS+1)); else echo "FAIL  admin status=0"; FAIL=$((FAIL+1)); fi

echo "=== TEST 5: re-enable via admin (status=1, card reappears) ==="
CODE=$(curl -s -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
  -d "product_description[2][name]=RESHOW_$$" \
  -d "product_description[2][meta_title]=RESHOW_$$" \
  -d "product_type_id=1" \
  -d "status=1" \
  "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null)
PRES=$(card_present)
echo "card_present=$PRES (expect >=1)"
if [ "$PRES" -ge 1 ]; then echo "PASS  admin re-enable shows card"; PASS=$((PASS+1)); else echo "FAIL  admin re-enable"; FAIL=$((FAIL+1)); fi

echo "===== ADMIN SAVE FLOW: PASS=$PASS FAIL=$FAIL ====="
restore_p64
echo "baseline restored"

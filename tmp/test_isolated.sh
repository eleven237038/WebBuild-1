#!/usr/bin/env bash
# Exhaustive ISOLATED attribute-mutation test for product 64 card display.
# Before EACH test: restore product 64 baseline (oc_product + oc_product_description
# + oc_product_to_custom_tag dumps) so every test starts from a clean state.
PID=64
BASE="http://localhost:8081"
HOME_URL="$BASE/"
SHOP_URL="$BASE/index.php?route=product/category&path=59"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
MARK="wishlist.add('$PID')"
PASS=0; FAIL=0; FAILED_NAMES=""

restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}

check_page() {
  local url="$1"; local expect="$2"
  local body code
  body=$(curl -s -o - -w "\n__HTTPCODE__%{http_code}" "$url" 2>/dev/null)
  code=$(echo "$body" | grep -oE '__HTTPCODE__[0-9]+' | grep -oE '[0-9]+')
  body=${body%__HTTPCODE__*}
  if [ "$code" != "200" ]; then echo "HTTP $code"; return 1; fi
  if echo "$body" | grep -qiE 'Fatal error|Parse error|Notice: |Warning: |Undefined (variable|index|offset)|Uncaught'; then
    echo "PHP-ERR:$(echo "$body" | grep -oiE '(Fatal error[^<]{0,60}|Notice: [^<]{0,60}|Warning: [^<]{0,60}|Uncaught[^<]{0,60})' | head -1)"; return 1
  fi
  local has; has=$(echo "$body" | grep -c "$MARK")
  if [ "$expect" = "present" ]; then
    [ "$has" -ge 1 ] && return 0 || { echo "pid MISSING"; return 1; }
  else
    [ "$has" -eq 0 ] && return 0 || { echo "pid STILL PRESENT"; return 1; }
  fi
}

T() {
  local name="$1"; local sqlstmt="$2"; local eh="$3"; local es="$4"
  restore_p64
  echo "$sqlstmt" | sql
  curl -s "$RESET" >/dev/null
  local r1 r2=""
  r1=$(check_page "$HOME_URL" "$eh"); [ $? -ne 0 ] && r1="HOME:$r1" || r1=""
  r2=$(check_page "$SHOP_URL" "$es"); [ $? -ne 0 ] && r2="SHOP:$r2" || r2=""
  if [ -z "$r1" ] && [ -z "$r2" ]; then
    echo "PASS  $name"; PASS=$((PASS+1))
  else
    echo "FAIL  $name :: $r1 $r2"; FAIL=$((FAIL+1)); FAILED_NAMES="$FAILED_NAMES|$name"
  fi
}

echo "===== ISOLATED ATTRIBUTE TESTS (restore before each) ====="

# --- name ---
T "name empty"        "UPDATE oc_product_description SET name='' WHERE product_id=$PID" present present
T "name long(500)"    "UPDATE oc_product_description SET name=REPEAT('A',500) WHERE product_id=$PID" present present
T "name HTML"         "UPDATE oc_product_description SET name='<script>x</script><b>B</b>' WHERE product_id=$PID" present present
T "name emoji"        "UPDATE oc_product_description SET name='💊CJC 🧪' WHERE product_id=$PID" present present
T "name null"         "UPDATE oc_product_description SET name=NULL WHERE product_id=$PID" present present
# --- model/sku ---
T "model empty"       "UPDATE oc_product SET model='' WHERE product_id=$PID" present present
T "model long"        "UPDATE oc_product SET model=REPEAT('m',200) WHERE product_id=$PID" present present
T "sku empty"         "UPDATE oc_product SET sku='' WHERE product_id=$PID" present present
# --- price ---
T "price 0"           "UPDATE oc_product SET price=0 WHERE product_id=$PID" present present
T "price negative"    "UPDATE oc_product SET price=-99.99 WHERE product_id=$PID" present present
T "price huge"        "UPDATE oc_product SET price=99999999.99 WHERE product_id=$PID" present present
T "price null"        "UPDATE oc_product SET price=NULL WHERE product_id=$PID" present present
# --- quantity ---
T "quantity 0"        "UPDATE oc_product SET quantity=0 WHERE product_id=$PID" present present
T "quantity neg"      "UPDATE oc_product SET quantity=-5 WHERE product_id=$PID" present present
T "quantity huge"     "UPDATE oc_product SET quantity=2147483647 WHERE product_id=$PID" present present
# --- weight/dimensions ---
T "weight 0"          "UPDATE oc_product SET weight=0 WHERE product_id=$PID" present present
T "dims 0"            "UPDATE oc_product SET length=0,width=0,height=0 WHERE product_id=$PID" present present
T "dims huge"         "UPDATE oc_product SET length=9999,width=9999,height=9999 WHERE product_id=$PID" present present
# --- sort_order ---
T "sort_order 0"      "UPDATE oc_product SET sort_order=0 WHERE product_id=$PID" present present
T "sort_order huge"   "UPDATE oc_product SET sort_order=99999 WHERE product_id=$PID" present present
# --- date_available ---
T "date future"       "UPDATE oc_product SET date_available=DATE_ADD(NOW(),INTERVAL 30 DAY) WHERE product_id=$PID" absent absent
T "date past"         "UPDATE oc_product SET date_available='2020-01-01' WHERE product_id=$PID" present present
T "date today"        "UPDATE oc_product SET date_available=CURDATE() WHERE product_id=$PID" present present
T "date null"         "UPDATE oc_product SET date_available=NULL WHERE product_id=$PID" present present
# --- status ---
T "status 0"          "UPDATE oc_product SET status=0 WHERE product_id=$PID" absent absent
T "status 1"          "UPDATE oc_product SET status=1 WHERE product_id=$PID" present present
# --- stock/shipping/subtract/min ---
T "stock_status 5"    "UPDATE oc_product SET stock_status_id=5 WHERE product_id=$PID" present present
T "stock_status 0"    "UPDATE oc_product SET stock_status_id=0 WHERE product_id=$PID" present present
T "shipping 0"        "UPDATE oc_product SET shipping=0 WHERE product_id=$PID" present present
T "subtract 0"        "UPDATE oc_product SET subtract=0 WHERE product_id=$PID" present present
T "minimum 99"        "UPDATE oc_product SET minimum=99 WHERE product_id=$PID" present present
# --- product_type_id ---
T "type 1->6"         "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" present present
T "type 6->1"         "UPDATE oc_product SET product_type_id=1 WHERE product_id=$PID" present present
T "type 0"            "UPDATE oc_product SET product_type_id=0 WHERE product_id=$PID" present present
# --- meta/desc ---
T "meta_title long"   "UPDATE oc_product_description SET meta_title=REPEAT('M',500) WHERE product_id=$PID" present present
T "meta_title empty"  "UPDATE oc_product_description SET meta_title='' WHERE product_id=$PID" present present
T "meta_keyword x"    "UPDATE oc_product_description SET meta_keyword='a,b,c' WHERE product_id=$PID" present present
T "tag html"          "UPDATE oc_product_description SET tag='<b>x</b>,药' WHERE product_id=$PID" present present
T "desc empty"        "UPDATE oc_product_description SET description='' WHERE product_id=$PID" present present
T "desc long"         "UPDATE oc_product_description SET description=REPEAT('<p>d</p>',200) WHERE product_id=$PID" present present
T "desc null"         "UPDATE oc_product_description SET description=NULL WHERE product_id=$PID" present present

echo "===== DONE: PASS=$PASS FAIL=$FAIL ====="
[ -n "$FAILED_NAMES" ] && echo "FAILED:${FAILED_NAMES#|}"
restore_p64; curl -s "$RESET" >/dev/null; echo "baseline restored"

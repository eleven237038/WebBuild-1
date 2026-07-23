#!/usr/bin/env bash
# Exhaustive attribute-mutation test for product 64 card display.
# Each test: mutate DB -> data-reset -> fetch home+shop -> check 200 + no PHP error + pid present/absent.
PID=64
BASE="http://localhost:8081"
HOME_URL="$BASE/"
SHOP_URL="$BASE/index.php?route=product/category&path=59"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
PASS=0; FAIL=0; ERRS=""
MARK="wishlist.add('$PID')"   # unique per-card marker for product $PID

# restore full baseline of product 64 from dumps
restore_all() {
  for t in oc_product oc_product_description oc_product_special oc_product_discount oc_product_image oc_product_to_category oc_product_to_custom_tag oc_product_reward oc_product_related oc_product_filter; do
    docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_${t}.sql
  done
  curl -s "$RESET" >/dev/null
}

# check a page: arg1=url arg2=expect("present"|"absent") -> sets $RC 0=pass
check_page() {
  local url="$1"; local expect="$2"
  local body code
  body=$(curl -s -o - -w "\n__HTTPCODE__%{http_code}" "$url" 2>/dev/null)
  code=$(echo "$body" | grep -oE '__HTTPCODE__[0-9]+' | grep -oE '[0-9]+')
  body=${body%__HTTPCODE__*}
  if [ "$code" != "200" ]; then echo "  HTTP $code"; return 1; fi
  if echo "$body" | grep -qiE 'Fatal error|Parse error|Notice: |Warning: |Undefined (variable|index|offset)|Call to (a|undefined)'; then
    echo "  PHP-ERROR: $(echo "$body" | grep -oiE '(Fatal error|Notice: [^<]*|Warning: [^<]*|Undefined[^<]*)' | head -1)"; return 1
  fi
  if echo "$body" | grep -q 'Fatal error\|Uncaught'; then echo "  FATAL"; return 1; fi
  local has; has=$(echo "$body" | grep -c "$MARK")
  if [ "$expect" = "present" ]; then
    [ "$has" -ge 1 ] && return 0 || { echo "  pid $PID MISSING (marker not found)"; return 1; }
  else
    [ "$has" -eq 0 ] && return 0 || { echo "  pid $PID STILL PRESENT (expected hidden)"; return 1; }
  fi
}

# run a test: arg1=name arg2=sql arg3=expect_home arg4=expect_shop
T() {
  local name="$1"; local sql="$2"; local eh="$3"; local es="$4"
  echo "$sql" | sql
  curl -s "$RESET" >/dev/null
  local r1 r2 out=""
  r1=$(check_page "$HOME_URL" "$eh"); [ $? -ne 0 ] && r1="HOME-FAIL:$r1" || r1=""
  r2=$(check_page "$SHOP_URL" "$es"); [ $? -ne 0 ] && r2="SHOP-FAIL:$r2" || r2=""
  if [ -z "$r1" ] && [ -z "$r2" ]; then
    echo "PASS  $name"; PASS=$((PASS+1))
  else
    echo "FAIL  $name :: $r1 $r2"; FAIL=$((FAIL+1)); ERRS="$ERRS\n  $name"
  fi
}

echo "===== RESTORING BASELINE ====="
restore_all
echo "===== RUNNING TESTS ====="

# --- name (oc_product_description) ---
T "name empty"        "UPDATE oc_product_description SET name='' WHERE product_id=$PID" present present
T "name long(500)"    "UPDATE oc_product_description SET name=REPEAT('A',500) WHERE product_id=$PID" present present
T "name HTML"         "UPDATE oc_product_description SET name='<script>x</script><b>B</b>' WHERE product_id=$PID" present present
T "name emoji"        "UPDATE oc_product_description SET name='💊CJC 测试 🧪' WHERE product_id=$PID" present present
T "name special\"quotes" "UPDATE oc_product_description SET name='O\\'Brien \"quoted\" & <weird>' WHERE product_id=$PID" present present
T "name unicode"      "UPDATE oc_product_description SET name='中文测试日本語한국어' WHERE product_id=$PID" present present
T "name null"         "UPDATE oc_product_description SET name=NULL WHERE product_id=$PID" present present

# --- model / sku ---
T "model empty"       "UPDATE oc_product SET model='' WHERE product_id=$PID" present present
T "model long"        "UPDATE oc_product SET model=REPEAT('m',200) WHERE product_id=$PID" present present
T "sku empty"         "UPDATE oc_product SET sku='' WHERE product_id=$PID" present present
T "sku special"       "UPDATE oc_product SET sku='A-B_C/1.2\\'3' WHERE product_id=$PID" present present

# --- price ---
T "price 0"           "UPDATE oc_product SET price=0 WHERE product_id=$PID" present present
T "price negative"    "UPDATE oc_product SET price=-99.99 WHERE product_id=$PID" present present
T "price huge"        "UPDATE oc_product SET price=99999999.99 WHERE product_id=$PID" present present
T "price tiny"        "UPDATE oc_product SET price=0.01 WHERE product_id=$PID" present present
T "price null"        "UPDATE oc_product SET price=NULL WHERE product_id=$PID" present present

# --- quantity ---
T "quantity 0 (OOS)"  "UPDATE oc_product SET quantity=0 WHERE product_id=$PID" present present
T "quantity negative" "UPDATE oc_product SET quantity=-5 WHERE product_id=$PID" present present
T "quantity huge"     "UPDATE oc_product SET quantity=2147483647 WHERE product_id=$PID" present present

# --- weight/dimensions ---
T "weight 0"          "UPDATE oc_product SET weight=0 WHERE product_id=$PID" present present
T "weight huge"       "UPDATE oc_product SET weight=99999999 WHERE product_id=$PID" present present
T "length 0"          "UPDATE oc_product SET length=0,width=0,height=0 WHERE product_id=$PID" present present
T "length huge"       "UPDATE oc_product SET length=9999,width=9999,height=9999 WHERE product_id=$PID" present present

# --- sort_order ---
T "sort_order 0"      "UPDATE oc_product SET sort_order=0 WHERE product_id=$PID" present present
T "sort_order neg"    "UPDATE oc_product SET sort_order=-100 WHERE product_id=$PID" present present
T "sort_order huge"   "UPDATE oc_product SET sort_order=99999 WHERE product_id=$PID" present present

# --- date_available ---
T "date_available future" "UPDATE oc_product SET date_available=DATE_ADD(NOW(),INTERVAL 30 DAY) WHERE product_id=$PID" absent absent
T "date_available past"   "UPDATE oc_product SET date_available='2020-01-01' WHERE product_id=$PID" present present
T "date_available today"  "UPDATE oc_product SET date_available=CURDATE() WHERE product_id=$PID" present present
T "date_available null"   "UPDATE oc_product SET date_available=NULL WHERE product_id=$PID" present present

# --- status (expected hide) ---
T "status 0"          "UPDATE oc_product SET status=0 WHERE product_id=$PID" absent absent
T "status 1"          "UPDATE oc_product SET status=1 WHERE product_id=$PID" present present

# --- stock_status_id ---
T "stock_status 5"    "UPDATE oc_product SET stock_status_id=5 WHERE product_id=$PID" present present
T "stock_status 0"    "UPDATE oc_product SET stock_status_id=0 WHERE product_id=$PID" present present

# --- shipping/subtract/minimum (don't affect card presence) ---
T "shipping 0"        "UPDATE oc_product SET shipping=0 WHERE product_id=$PID" present present
T "subtract 0"        "UPDATE oc_product SET subtract=0 WHERE product_id=$PID" present present
T "minimum 99"        "UPDATE oc_product SET minimum=99 WHERE product_id=$PID" present present

# --- product_type_id change ---
T "type_id 1->6"      "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" present present
T "type_id 6->1"      "UPDATE oc_product SET product_type_id=1 WHERE product_id=$PID" present present
T "type_id 0"         "UPDATE oc_product SET product_type_id=0 WHERE product_id=$PID" present present

# --- meta fields (don't break card) ---
T "meta_title long"   "UPDATE oc_product_description SET meta_title=REPEAT('M',500) WHERE product_id=$PID" present present
T "meta_title empty"  "UPDATE oc_product_description SET meta_title='' WHERE product_id=$PID" present present
T "meta_keyword x"    "UPDATE oc_product_description SET meta_keyword='a,b,c,<x>' WHERE product_id=$PID" present present
T "tag html"          "UPDATE oc_product_description SET tag='<b>x</b>,药' WHERE product_id=$PID" present present
T "description empty" "UPDATE oc_product_description SET description='' WHERE product_id=$PID" present present
T "description long"  "UPDATE oc_product_description SET description=REPEAT('<p>desc</p>',200) WHERE product_id=$PID" present present
T "description null"  "UPDATE oc_product_description SET description=NULL WHERE product_id=$PID" present present

echo "===== DONE: PASS=$PASS FAIL=$FAIL ====="
[ -n "$ERRS" ] && echo -e "FAILED:$ERRS"
echo "===== RESTORING BASELINE ====="
restore_all
echo "done"

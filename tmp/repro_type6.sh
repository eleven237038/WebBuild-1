#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
MARK="wishlist.add('$PID')"
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
echo "=== restore_p64, NO mutation, check ==="
restore_p64; curl -s "$RESET" >/dev/null
echo "type now: $(echo "SELECT product_type_id FROM oc_product WHERE product_id=$PID" | sql)"
echo "shop pid64: $(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -c "$MARK")"
echo "home pid64: $(curl -s "$BASE/" 2>/dev/null | grep -c "$MARK")"
echo "=== set type=6, reset, check ==="
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
echo "type now: $(echo "SELECT product_type_id FROM oc_product WHERE product_id=$PID" | sql)"
echo "shop pid64: $(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -c "$MARK")"
echo "home pid64: $(curl -s "$BASE/" 2>/dev/null | grep -c "$MARK")"
echo "=== shop card count + pids with type=6 ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE "cart.add\('[0-9]+'" | sort -u
echo "=== total products in category 59 + their types ==="
echo "SELECT p.product_id,p.product_type_id,p.status,p.date_available,p.sort_order FROM oc_product p JOIN oc_product_to_category pc ON p.product_id=pc.product_id WHERE pc.category_id=59 ORDER BY p.sort_order" | sql

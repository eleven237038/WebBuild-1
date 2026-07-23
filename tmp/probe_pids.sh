#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
restore_p64; curl -s "$RESET" >/dev/null
echo "=== type=1 (baseline) shop pids ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE "cart.add\('[0-9]+'" | sort -u
echo "=== type=1 product_total via direct query (category 59, status=1, date<=now) ==="
echo "SELECT p.product_id,p.product_type_id FROM oc_product p JOIN oc_product_to_category pc ON p.product_id=pc.product_id WHERE pc.category_id=59 AND p.status=1 AND p.date_available<=NOW()" | sql
echo "=== set type=6 ==="
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
echo "=== type=6 shop pids ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE "cart.add\('[0-9]+'" | sort -u

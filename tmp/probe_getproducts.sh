#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
restore_p64
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
echo "=== 3 cards' pids (cart.add ids) ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE "cart.add\('[0-9]+'" | sort -u
echo "=== pcard-name texts on page ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE 'pcard-name[^>]*>[^<]+' | sed 's/.*>//' | head

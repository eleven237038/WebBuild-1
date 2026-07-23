#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
echo "=== TEST A: type=6, 5 fetches (expect consistent) ==="
restore_p64
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
for i in 1 2 3 4 5; do
  B=$(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null)
  echo "fetch $i: cart64=$(echo "$B" | grep -c "cart.add('64')") pid64href=$(echo "$B" | grep -cE 'product_id=64[^0-9]') cards=$(echo "$B" | grep -c 'class=\"product-card\"')"
done
echo "=== TEST B: rapid double data-reset + name change, 5 fetches ==="
restore_p64
echo "UPDATE oc_product_description SET name='RELIABLE_TEST_X' WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
echo "UPDATE oc_product_description SET name='RELIABLE_TEST_Y' WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
for i in 1 2 3 4 5; do
  B=$(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null)
  echo "fetch $i: name_X=$(echo "$B" | grep -c 'RELIABLE_TEST_X') name_Y=$(echo "$B" | grep -c 'RELIABLE_TEST_Y')"
done

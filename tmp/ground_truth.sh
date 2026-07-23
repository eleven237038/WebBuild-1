#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
graceful() { docker exec genscript-opencart apachectl graceful 2>/dev/null; sleep 1.5; }
echo "=== restore + graceful (clear ALL workers) + type=6 ==="
restore_p64
graceful
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
sleep 0.5
echo "type in DB: $(echo "SELECT product_type_id FROM oc_product WHERE product_id=$PID" | sql | tail -1)"
for i in 1 2 3; do
  B=$(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null)
  echo "fetch $i: cart64=$(echo "$B" | grep -c "cart.add('64')") cards=$(echo "$B" | grep -c 'class=\"product-card\"') names=[$(echo "$B" | grep -oE 'pcard-name[^>]*>[^<]+' | sed 's/.*>//' | tr '\n' ',')]"
done
echo "=== restore + graceful + type=1 (baseline truth) ==="
restore_p64
graceful
curl -s "$RESET" >/dev/null
sleep 0.5
for i in 1 2 3; do
  B=$(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null)
  echo "fetch $i: cart64=$(echo "$B" | grep -c "cart.add('64')") cards=$(echo "$B" | grep -c 'class=\"product-card\"')"
done

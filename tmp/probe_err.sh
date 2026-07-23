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
# clear error log marker
docker exec genscript-opencart sh -c 'echo "===MARKER===" >> /var/www/html/system/storage/logs/error.log'
# fetch shop page 3 times (in case of flaky cache)
for i in 1 2 3; do
  B=$(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null)
  echo "fetch $i: cart64=$(echo "$B" | grep -c "cart.add('64')") wish64=$(echo "$B" | grep -c "wishlist.add('64')") pid64=$(echo "$B" | grep -cE 'product_id=64[^0-9]') cards=$(echo "$B" | grep -c 'class=\"product-card\"')"
done
echo "=== error log after MARKER ==="
docker exec genscript-opencart sh -c 'awk "/===MARKER===/{p=1;next} p" /var/www/html/system/storage/logs/error.log | tail -40'

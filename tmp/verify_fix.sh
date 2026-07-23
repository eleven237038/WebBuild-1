#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
SHOP="$BASE/index.php?route=product/category&path=59&nocache=1"
HOME="$BASE/?nocache=1"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
restore_p64
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
echo "=== type=6 AFTER fix ==="
B=$(curl -s "$SHOP" 2>/dev/null)
echo "shop: cart64=$(echo "$B" | grep -c "cart.add('64'") wish64=$(echo "$B" | grep -c "wishlist.add('64')") pid64=$(echo "$B" | grep -cE 'product_id=64[^0-9]')"
echo "names=[$(echo "$B" | grep -oE 'pcard-name[^>]*>[^<]+' | sed 's/.*>//' | tr '\n' ',')]"
echo "CJC-1295 present? $(echo "$B" | grep -c 'CJC-1295')"
echo "=== card 64 has image? (pcard-img-wrap containing product 64) ==="
echo "$B" | grep -oE 'product/product&product_id=64[^"]*"' | head
echo "=== home type=6 ==="
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
H=$(curl -s "$HOME" 2>/dev/null)
echo "home: cart64=$(echo "$H" | grep -c "cart.add('64'") wish64=$(echo "$H" | grep -c "wishlist.add('64')") CJC=$(echo "$H" | grep -c 'CJC-1295')"
restore_p64

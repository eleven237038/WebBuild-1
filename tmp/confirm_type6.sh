#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
SHOP="$BASE/index.php?route=product/category&path=59&nocache=1"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
restore_p64
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
B=$(curl -s "$SHOP" 2>/dev/null)
echo "type=6 shop: cart64prefix=$(echo "$B" | grep -c "cart.add('64'") wish64=$(echo "$B" | grep -c "wishlist.add('64')") pid64=$(echo "$B" | grep -cE 'product_id=64[^0-9]') cards=$(echo "$B" | grep -c 'class=\"product-card\"')"
echo "=== the 3 cards' product_ids (cart.add prefix) ==="
echo "$B" | grep -oE "cart.add\('[0-9]+'" | sort -u
echo "=== pcard-price count (any price shown?) ==="
echo "$B" | grep -c 'pcard-price'
echo "=== pcard-img count (any image?) ==="
echo "$B" | grep -c 'pcard-img'

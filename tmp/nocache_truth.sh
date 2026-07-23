#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
SHOP="$BASE/index.php?route=product/category&path=59&nocache=1"
HOME="$BASE/?nocache=1"
fetchcheck() {
  local url="$1" label="$2"
  local B; B=$(curl -s "$url" 2>/dev/null)
  echo "$label: cart64=$(echo "$B" | grep -c "cart.add('64')") wish64=$(echo "$B" | grep -c "wishlist.add('64')") pid64=$(echo "$B" | grep -cE 'product_id=64[^0-9]') cards=$(echo "$B" | grep -c 'class=\"product-card\"') names=[$(echo "$B" | grep -oE 'pcard-name[^>]*>[^<]+' | sed 's/.*>//' | tr '\n' ',')]"
}
echo "=== BASELINE type=1 (no mutation) with nocache ==="
restore_p64
fetchcheck "$SHOP" "shop"
fetchcheck "$HOME" "home"
echo "=== set type=6, NO reset (nocache bypasses page_fw) ==="
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
fetchcheck "$SHOP" "shop"
fetchcheck "$HOME" "home"
echo "=== set type=1 again ==="
echo "UPDATE oc_product SET product_type_id=1 WHERE product_id=$PID" | sql
fetchcheck "$SHOP" "shop"
restore_p64

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
echo "=== type 6 shop card for pid 64 (full article) ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE '<article class="product-card"[^>]*>.*?</article>' | head -1 | sed 's/<[^>]*>/ /g' | tr -s ' ' | head -c 400
echo ""
echo "=== markers present? ==="
BODY=$(curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null)
echo "cart.add('64'): $(echo "$BODY" | grep -c "cart.add('64')")"
echo "wishlist.add('64'): $(echo "$BODY" | grep -c "wishlist.add('64')")"
echo "product_id=64 (non-digit after): $(echo "$BODY" | grep -cE 'product_id=64[^0-9]')"
echo "=== Is the card empty (no name/price/image content)? show_* flags ==="
echo "pcard-name count: $(echo "$BODY" | grep -c 'pcard-name')"
echo "pcard-price count: $(echo "$BODY" | grep -c 'pcard-price')"
echo "pcard-img count: $(echo "$BODY" | grep -c 'pcard-img')"

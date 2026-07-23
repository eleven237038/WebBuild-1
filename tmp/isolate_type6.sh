#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
HOME_URL="$BASE/"; SHOP_URL="$BASE/index.php?route=product/category&path=59"
RESET="$BASE/data-reset.php"
MARK="wishlist.add('$PID')"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_all() {
  for t in oc_product oc_product_description oc_product_special oc_product_discount oc_product_image oc_product_to_category oc_product_to_custom_tag oc_product_reward oc_product_related oc_product_filter; do
    docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_${t}.sql
  done; curl -s "$RESET" >/dev/null; }
present() { curl -s "$1" 2>/dev/null | grep -c "$MARK"; }

echo "=== fresh restore ==="; restore_all
echo "home pid64 count: $(present "$HOME_URL") | shop: $(present "$SHOP_URL")"
echo "=== set type_id=6 (fresh) ==="
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
echo "home pid64 count: $(present "$HOME_URL") | shop: $(present "$SHOP_URL")"
echo "=== which product types exist ==="
echo "SELECT product_type_id,name FROM oc_product_type ORDER BY product_type_id" | sql
echo "=== products per type ==="
echo "SELECT product_type_id,COUNT(*) c FROM oc_product GROUP BY product_type_id" | sql

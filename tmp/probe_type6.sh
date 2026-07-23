#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
RESET="$BASE/data-reset.php"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_all() { for t in oc_product oc_product_description oc_product_special oc_product_discount oc_product_image oc_product_to_category oc_product_to_custom_tag oc_product_reward oc_product_related oc_product_filter; do docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_${t}.sql; done; curl -s "$RESET" >/dev/null; }
restore_all
echo "UPDATE oc_product SET product_type_id=6 WHERE product_id=$PID" | sql
curl -s "$RESET" >/dev/null
echo "=== SHOP page: count of product-card articles ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -c 'class="product-card"'
echo "=== SHOP page: any error/notice text? ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oiE '(fatal error|notice: [^<]{0,80}|warning: [^<]{0,80}|uncaught|exception|undefined[^<]{0,60})' | head -10
echo "=== SHOP: list pids present (cart.add ids) ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE "cart.add\('[0-9]+'" | sort -u
echo "=== custom_tag fields for type 6 ==="
echo "SELECT tag_id,name,system_column,field_type,status FROM oc_custom_tag WHERE product_type_id=6" | sql
echo "=== custom_tag fields for type 1 (compare) ==="
echo "SELECT tag_id,name,system_column,field_type,status FROM oc_custom_tag WHERE product_type_id=1 ORDER BY sort_order" | sql
echo "=== product_card_6 setting rows? ==="
echo "SELECT code,key0,value,serialized FROM oc_setting WHERE code LIKE 'product_card%' ORDER BY code,key0" | sql

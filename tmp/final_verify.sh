#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
echo "=== full baseline restore (all dumps) ==="
for t in oc_product oc_product_description oc_product_discount oc_product_filter oc_product_image oc_product_related oc_product_reward oc_product_special oc_product_to_category oc_product_to_custom_tag; do
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_${t}.sql
done
curl -s "$BASE/data-reset.php" >/dev/null
echo "=== product 64 DB state ==="
echo "SELECT product_id,product_type_id,status,date_available,price FROM oc_product WHERE product_id=64" | sql
echo "cat59: $(echo "SELECT COUNT(*) FROM oc_product_to_category WHERE product_id=64 AND category_id=59" | sql | tail -1) | store0: $(echo "SELECT COUNT(*) FROM oc_product_to_store WHERE product_id=64 AND store_id=0" | sql | tail -1) | langs: $(echo "SELECT COUNT(*) FROM oc_product_description WHERE product_id=64" | sql | tail -1)"
echo "=== PRODUCTION frontend (cached, no nocache) ==="
SHOP="$BASE/index.php?route=product/category&path=59"
HOME="$BASE/"
echo "shop: card64=$(curl -s "$SHOP" 2>/dev/null | grep -c "cart.add('64'") CJC=$(curl -s "$SHOP" 2>/dev/null | grep -c 'CJC-1295') http=$(curl -s -o /dev/null -w '%{http_code}' "$SHOP" 2>/dev/null)"
echo "home: card64=$(curl -s "$HOME" 2>/dev/null | grep -c "cart.add('64'") CJC=$(curl -s "$HOME" 2>/dev/null | grep -c 'CJC-1295') http=$(curl -s -o /dev/null -w '%{http_code}' "$HOME" 2>/dev/null)"
echo "=== PDP (product detail page) for 64 ==="
PDP="$BASE/index.php?route=product/product&product_id=64"
echo "pdp: http=$(curl -s -o /dev/null -w '%{http_code}' "$PDP" 2>/dev/null) CJC=$(curl -s "$PDP" 2>/dev/null | grep -c 'CJC-1295') fatal=$(curl -s "$PDP" 2>/dev/null | grep -ciE 'fatal error|uncaught')"

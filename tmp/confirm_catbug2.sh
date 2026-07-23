#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
JAR=/tmp/admin_cookies.txt
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
full_restore() {
  for t in oc_product oc_product_description oc_product_to_custom_tag oc_product_to_category; do
    docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_${t}.sql
  done
}
full_restore
echo "BEFORE: cat59=$(echo "SELECT COUNT(*) FROM oc_product_to_category WHERE product_id=$PID AND category_id=59" | sql | tail -1) card=$(curl -s "$BASE/index.php?route=product/category&path=59&nocache=1" 2>/dev/null | grep -c "cart.add('64'")"
rm -f "$JAR"; curl -s -c "$JAR" "$BASE/admin/index.php?route=common/login" -o /dev/null 2>/dev/null
HDR=$(curl -s -c "$JAR" -b "$JAR" -D - -o /dev/null -d "username=admin" -d "password=admin123" "$BASE/admin/index.php?route=common/login" 2>/dev/null)
TOKEN=$(echo "$HDR" | grep -oiE 'user_token=[a-z0-9]+' | head -1 | cut -d= -f2)
echo "bare admin save (no product_category, no product_store in POST)..."
curl -s -b "$JAR" -o /dev/null -w 'HTTP=%{http_code}\n' \
  -d "product_description[2][name]=XYZ" -d "product_description[2][meta_title]=XYZ" \
  -d "product_type_id=1" \
  "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null
echo "AFTER:  cat59=$(echo "SELECT COUNT(*) FROM oc_product_to_category WHERE product_id=$PID AND category_id=59" | sql | tail -1) store0=$(echo "SELECT COUNT(*) FROM oc_product_to_store WHERE product_id=$PID AND store_id=0" | sql | tail -1) card=$(curl -s "$BASE/index.php?route=product/category&path=59&nocache=1" 2>/dev/null | grep -c "cart.add('64'")"
full_restore

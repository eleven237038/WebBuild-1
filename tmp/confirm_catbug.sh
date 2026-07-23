#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
JAR=/tmp/admin_cookies.txt
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
restore_p64() {
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
  docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
}
restore_p64
echo "BEFORE save: product_to_category for 64:"
echo "SELECT * FROM oc_product_to_category WHERE product_id=$PID" | sql
rm -f "$JAR"; curl -s -c "$JAR" "$BASE/admin/index.php?route=common/login" -o /dev/null 2>/dev/null
HDR=$(curl -s -c "$JAR" -b "$JAR" -D - -o /dev/null -d "username=admin" -d "password=admin123" "$BASE/admin/index.php?route=common/login" 2>/dev/null)
TOKEN=$(echo "$HDR" | grep -oiE 'user_token=[a-z0-9]+' | head -1 | cut -d= -f2)
# bare edit, NO product_category posted
curl -s -b "$JAR" -o /dev/null \
  -d "product_description[2][name]=XYZ" -d "product_description[2][meta_title]=XYZ" \
  -d "product_type_id=1" \
  "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null
echo "AFTER bare save (no product_category): product_to_category for 64:"
echo "SELECT * FROM oc_product_to_category WHERE product_id=$PID" | sql
echo "date_available:"; echo "SELECT date_available FROM oc_product WHERE product_id=$PID" | sql
restore_p64

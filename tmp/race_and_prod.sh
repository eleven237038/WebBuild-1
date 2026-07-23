#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
JAR=/tmp/admin_cookies.txt
SHOP_NOCACHE="$BASE/index.php?route=product/category&path=59&nocache=1"
SHOP_CACHE="$BASE/index.php?route=product/category&path=59"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
full_restore() {
  for t in oc_product oc_product_description oc_product_to_custom_tag oc_product_to_category oc_product_special oc_product_discount oc_product_image; do
    docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_${t}.sql
  done
}
full_restore
rm -f "$JAR"; curl -s -c "$JAR" "$BASE/admin/index.php?route=common/login" -o /dev/null 2>/dev/null
HDR=$(curl -s -c "$JAR" -b "$JAR" -D - -o /dev/null -d "username=admin" -d "password=admin123" "$BASE/admin/index.php?route=common/login" 2>/dev/null)
TOKEN=$(echo "$HDR" | grep -oiE 'user_token=[a-z0-9]+' | head -1 | cut -d= -f2)
echo "=== RACE TEST: 5 rapid consecutive admin saves ==="
ERRBEFORE=$(docker exec genscript-opencart sh -c 'wc -l < /var/www/html/system/storage/logs/error.log 2>/dev/null')
for i in 1 2 3 4 5; do
  CODE=$(curl -s -b "$JAR" -o /dev/null -w '%{http_code}' \
    -d "product_description[2][name]=RACE_$i" -d "product_description[2][meta_title]=RACE_$i" \
    -d "product_type_id=1" -d "status=1" \
    "$BASE/admin/index.php?route=catalog/product/edit&user_token=$TOKEN&product_id=$PID" 2>/dev/null)
  echo "  save $i: HTTP=$CODE name=$(echo "SELECT name FROM oc_product_description WHERE product_id=$PID AND language_id=2" | sql | tail -1)"
done
ERRAFTER=$(docker exec genscript-opencart sh -c 'wc -l < /var/www/html/system/storage/logs/error.log 2>/dev/null')
echo "error.log lines before=$ERRBEFORE after=$ERRAFTER (expect no 'Duplicate entry' added)"
docker exec genscript-opencart sh -c "tail -n $((ERRAFTER-ERRBEFORE)) /var/www/html/system/storage/logs/error.log 2>/dev/null | grep -i 'duplicate' | head"
echo "=== PRODUCTION PATH (cached, with data-reset) ==="
full_restore
# change name in DB, then data-reset (production invalidation), then fetch WITHOUT nocache
echo "UPDATE oc_product_description SET name='PRODCACHE_TEST' WHERE product_id=$PID" | sql
curl -s "$BASE/data-reset.php" >/dev/null
echo "cached shop after data-reset: PRODCACHE_TEST present=$(curl -s "$SHOP_CACHE" 2>/dev/null | grep -c 'PRODCACHE_TEST') card64=$(curl -s "$SHOP_CACHE" 2>/dev/null | grep -c "cart.add('64'")"
echo "2nd fetch (cache hit, should still show new name): PRODCACHE_TEST=$(curl -s "$SHOP_CACHE" 2>/dev/null | grep -c 'PRODCACHE_TEST')"
full_restore
curl -s "$BASE/data-reset.php" >/dev/null
echo "restored + reset"

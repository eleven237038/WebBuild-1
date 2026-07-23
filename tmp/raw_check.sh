#!/usr/bin/env bash
PID=64; BASE="http://localhost:8081"
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
echo "=== product 64 DB state NOW ==="
echo "SELECT product_id,product_type_id,status,date_available,price,quantity,sort_order,show_on_homepage FROM oc_product WHERE product_id=64" | sql
echo "=== is 64 in category 59? ==="
echo "SELECT * FROM oc_product_to_category WHERE product_id=64" | sql
echo "=== product 64 name/desc ==="
echo "SELECT product_id,name FROM oc_product_description WHERE product_id=64" | sql
echo "=== ver counters ==="
docker exec genscript-opencart sh -c 'echo "content.ver=[$(cat /var/www/html/system/storage/cache/content.ver)] settings.ver=[$(cat /var/www/html/system/storage/cache/settings.ver)]"' 2>&1
echo "=== raw shop page: every product-card article (first 200 chars each) ==="
curl -s "$BASE/index.php?route=product/category&path=59" 2>/dev/null | grep -oE '<article class="product-card".*?</article>' | while read -r a; do echo "---CARD---"; echo "$a" | sed 's/<[^>]*>/ /g' | tr -s ' ' | head -c 150; echo ""; done

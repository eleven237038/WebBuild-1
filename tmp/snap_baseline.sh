#!/usr/bin/env bash
# Snapshot product 64's oc_product + oc_product_description rows into restorable SQL.
sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }
docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product.sql
docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_description.sql
docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null < /tmp/dump_oc_product_to_custom_tag.sql
echo "SELECT * FROM oc_product WHERE product_id=64\G" | sql > /tmp/base_p64_product.txt
echo "SELECT * FROM oc_product_description WHERE product_id=64\G" | sql > /tmp/base_p64_desc.txt
echo "SELECT product_id,tag_id,value FROM oc_product_to_custom_tag WHERE product_id=64\G" | sql > /tmp/base_p64_tags.txt
echo "baseline snapshot taken"; echo "---product---"; head -5 /tmp/base_p64_product.txt; echo "---tags---"; cat /tmp/base_p64_tags.txt

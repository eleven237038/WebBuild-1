#!/usr/bin/env bash
BASE="http://localhost:8081"
JAR=/tmp/admin_cookies.txt
rm -f "$JAR"
# GET login page first (to seed session cookie)
curl -s -c "$JAR" "$BASE/admin/index.php?route=common/login" -o /dev/null 2>/dev/null
# POST login; capture response headers to extract user_token from 302 Location
HDR=$(curl -s -c "$JAR" -b "$JAR" -D - -o /dev/null \
  -d "username=admin" -d "password=admin123" \
  "$BASE/admin/index.php?route=common/login" 2>/dev/null)
echo "=== response headers ==="
echo "$HDR" | grep -iE "HTTP/|Location|user_token"
TOKEN=$(echo "$HDR" | grep -oiE 'user_token=[a-z0-9]+' | head -1 | cut -d= -f2)
echo "TOKEN=$TOKEN"
echo "=== verify session: GET dashboard with cookie ==="
curl -s -b "$JAR" "$BASE/admin/index.php?route=common/dashboard&user_token=$TOKEN" 2>/dev/null | grep -oiE '(<title>[^<]*</title>|login|logout)' | head -3
echo "TOKEN_FILE=/tmp/admin_token.txt"; echo -n "$TOKEN" > /tmp/admin_token.txt

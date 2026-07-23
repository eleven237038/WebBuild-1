#!/usr/bin/env bash
# Fast product card attribute mutation test harness.
# Product data reads on home/category are NOT cached (getProduct/getProductsByIds),
# so data-only changes reflect immediately — no cache reset needed.
PID=64
BASE="http://localhost:8081"
HOME_URL="$BASE/"
SHOP_URL="$BASE/index.php?route=product/category&path=59"
RESET="$BASE/opcache-reset.php"

sql() { docker exec -i genscript-mysql mysql --default-character-set=utf8mb4 -uopencart -popencart1234 opencart 2>/dev/null; }

# Single curl: prints "HTTPCODE<TAB>body". Checks errors + product presence.
check_page() {
  local url="$1"
  local tmp; tmp=$(mktemp)
  local code; code=$(curl -s -m 20 -w "%{http_code}" -o "$tmp" "$url")
  if [ "$code" != "200" ]; then rm -f "$tmp"; echo "HTTP$code"; return 1; fi
  local err; err=$(grep -oiE 'fatal error|parse error|uncaught exception|<b>fatal</b>|<b>parse error</b>|call to a member function|Allowed memory size' "$tmp" | head -1)
  if [ -n "$err" ]; then rm -f "$tmp"; echo "PHP:$err"; return 1; fi
  if ! grep -q "product_id=$PID" "$tmp"; then rm -f "$tmp"; echo "MISSING"; return 1; fi
  rm -f "$tmp"; echo "OK"; return 0
}

PASS=0; FAIL=0; FAILED_TESTS=""
test_attr() {
  local desc="$1"; local sqlstmt="$2"
  [ -n "$sqlstmt" ] && echo "$sqlstmt" | sql
  local h s
  h=$(check_page "$HOME_URL")
  s=$(check_page "$SHOP_URL")
  if [ "$h" = "OK" ] && [ "$s" = "OK" ]; then
    echo "PASS | $desc"; PASS=$((PASS+1))
  else
    echo "FAIL | $desc | home=$h shop=$s"; FAIL=$((FAIL+1)); FAILED_TESTS="$FAILED_TESTS\n  - $desc (home=$h shop=$s)"
  fi
}

# Like test_attr but EXPECTS the product to vanish (status=0 / future date) — still must be 200 + no PHP error.
test_attr_hide() {
  local desc="$1"; local sqlstmt="$2"
  [ -n "$sqlstmt" ] && echo "$sqlstmt" | sql
  local h s
  h=$(check_page "$HOME_URL")
  s=$(check_page "$SHOP_URL")
  # Expect MISSING (product filtered out) but NOT HTTP/PHP error.
  if printf '%s\n%s\n' "$h" "$s" | grep -qE '^(HTTP|PHP):'; then
    echo "FAIL | $desc | (expected hide, got error) home=$h shop=$s"; FAIL=$((FAIL+1)); FAILED_TESTS="$FAILED_TESTS\n  - $desc (home=$h shop=$s)"
  else
    echo "PASS(hide) | $desc | home=$h shop=$s"; PASS=$((PASS+1))
  fi
}

inspect_card() {
  local url="$1"; local tmp; tmp=$(mktemp)
  curl -s -m 20 -o "$tmp" "$url"
  grep -A 40 "product_id=$PID\"" "$tmp" | grep -oE 'pcard-(img-wrap|name|desc|price-wrap|price-old|badge--tag|badge--sale|badge--oos|add|wishlist|bottom|rating)' | sort -u | tr '\n' ' '; echo
  rm -f "$tmp"
}

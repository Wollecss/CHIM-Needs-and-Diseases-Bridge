#!/usr/bin/env bash
# SunHelm Needs & Diseases Bridge - post-install verification.
EXT="/var/www/html/HerikaServer/ext/sunhelm_needs"
ROOT="/var/www/html/HerikaServer/comm.php"

echo "=== state.json ==="
cat "$EXT/state.json"
echo
echo "=== settings.json ==="
cat "$EXT/settings.json"
echo
echo "=== disease handler present in root comm.php? ==="
grep -n "sunhelm_disease@" "$ROOT" || echo "NOT FOUND"
echo
echo "=== PHP lint plugin files ==="
for f in context_pre.php comm.php config.php update.php context.php preprocessing.php; do
    php -l "$EXT/$f"
done
echo
echo "=== PHP lint root comm.php ==="
php -l "$ROOT"

echo
echo "=== Oghma Database Verification ==="
PGPASSWORD=dwemer psql -h localhost -U dwemer -d dwemer -c "SELECT topic, knowledge_class_basic, category FROM public.oghma WHERE category = 'diseases' ORDER BY topic;"

echo
echo "=== SunHelm verification complete ==="

#!/usr/bin/env sh

echo "Running composer"
composer install --no-scripts --no-dev

echo "Changing cron permissions"
chmod -f 755 src/cron_*.sh

echo "Running database sync"
php web/index.php dbtools/sync

echo "Clearing kohana cache"
rm -f storage/cache/kohana_*

echo "Clearing media cache"
php web/index.php media_tools/clean

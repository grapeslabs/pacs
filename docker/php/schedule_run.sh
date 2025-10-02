#!/usr/bin/env sh

echo "shedule_run.sh started as user $UID."

while [ true ]
  do
    php /var/www/html/artisan schedule:run --verbose --no-interaction &
    sleep 60
done

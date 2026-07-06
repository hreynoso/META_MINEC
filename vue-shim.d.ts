#!/command/execlineb -P
with-contenv
s6-setuidgid www-data
php /var/www/html/artisan horizon

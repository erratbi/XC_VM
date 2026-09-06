#!/bin/bash
set -e

SCRIPT="/home/xc_vm"
DB_HOST="${DB_HOST:-mariadb}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-xtream_iptvpro}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
REDIS_HOST="${REDIS_HOST:-redis}"
REDIS_PORT="${REDIS_PORT:-6379}"
ADMIN_CODE="${XTREAMPI_ADMIN_CODE:-admin}"
ADMIN_USER="${XTREAMPI_ADMIN_USER:-admin}"
ADMIN_PASS="${XTREAMPI_ADMIN_PASS:-admin1234}"
ADMIN_EMAIL="${XTREAMPI_ADMIN_EMAIL:-admin@example.com}"

echo "==> [XtreamPi Dev] Booting development container..."

# 1. Setup multiarch library links if on ARM64
if [ -d /usr/x86_64-linux-gnu/lib ]; then
    mkdir -p /lib/x86_64-linux-gnu /usr/lib/x86_64-linux-gnu /lib64 /usr/local/lib
    ln -sf /usr/x86_64-linux-gnu/lib/* /lib/x86_64-linux-gnu/ 2>/dev/null || true
    ln -sf /usr/x86_64-linux-gnu/lib/* /usr/lib/x86_64-linux-gnu/ 2>/dev/null || true
    ln -sf /usr/x86_64-linux-gnu/lib/ld-linux-x86-64.so.2 /lib64/ld-linux-x86-64.so.2 2>/dev/null || true
fi
if [ -d /home/xc_vm/bin/ffmpeg_bin/lib ]; then
    mkdir -p /usr/local/lib
    cp -d /home/xc_vm/bin/ffmpeg_bin/lib/* /usr/local/lib/ 2>/dev/null || true
    cp -d /home/xc_vm/bin/ffmpeg_bin/lib/* /usr/lib/x86_64-linux-gnu/ 2>/dev/null || true
    echo "/usr/local/lib" > /etc/ld.so.conf.d/xtreampi-ffmpeg.conf
    ldconfig 2>/dev/null || true
fi

# 2. Wait for MariaDB to be ready
echo "==> [XtreamPi Dev] Waiting for MariaDB (${DB_HOST}:${DB_PORT})..."
until mariadb-admin ping -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" --password="${DB_PASS}" --silent >/dev/null 2>&1; do
    sleep 1
done
echo "==> [XtreamPi Dev] MariaDB is reachable!"

# 3. Wait for Redis to be ready
echo "==> [XtreamPi Dev] Waiting for Redis (${REDIS_HOST}:${REDIS_PORT})..."
until (echo PING | nc -w 1 "${REDIS_HOST}" "${REDIS_PORT}" | grep -q PONG) 2>/dev/null; do
    sleep 1
done
echo "==> [XtreamPi Dev] Redis is reachable!"

# 4. Create required runtime directories and set permissions
mkdir -p /home/xc_vm/content/streams /home/xc_vm/tmp /home/xc_vm/storage /home/xc_vm/config /home/xc_vm/backups /home/xc_vm/bin/nginx/sbin /home/xc_vm/bin/nginx/conf/codes /home/xc_vm/bin/nginx/logs /home/xc_vm/bin/php/sockets /home/xc_vm/bin/php/sessions /var/lib/nginx/body /var/lib/nginx/fastcgi /var/lib/nginx/proxy /var/lib/nginx/uwsgi /var/lib/nginx/scgi
chmod 1777 /tmp /home/xc_vm/tmp /home/xc_vm/content/streams 2>/dev/null || true
chown -R xc_vm:xc_vm /home/xc_vm/content/streams /home/xc_vm/tmp /home/xc_vm/storage /home/xc_vm/config /home/xc_vm/bin/nginx/logs /home/xc_vm/bin/php /var/lib/nginx 2>/dev/null || true
chmod 777 /home/xc_vm/config 2>/dev/null || true
touch /var/log/php-fpm.log && chmod 666 /var/log/php-fpm.log

# 4.1. Fallback: Download distribution PHP binaries if missing
if [ ! -f /home/xc_vm/bin/php/sbin/php-fpm ]; then
    echo "==> [XtreamPi Dev] Installing distribution PHP binaries..."
    BIN_TAG=$(curl -s https://api.github.com/repos/Vateron-Media/XC_VM_Binaries/releases/latest | grep '"tag_name":' | head -n 1 | cut -d '"' -f 4)
    BIN_TAG="${BIN_TAG:-29062026}"
    mkdir -p /tmp/xtreampi_extract /home/xc_vm/bin
    curl -sL "https://github.com/Vateron-Media/XC_VM_Binaries/releases/download/${BIN_TAG}/ubuntu_24.tar.gz" -o /tmp/ubuntu_24.tar.gz
    tar -xzf /tmp/ubuntu_24.tar.gz -C /tmp/xtreampi_extract/
    if [ -d /tmp/xtreampi_extract/bin/php ]; then
        cp -r /tmp/xtreampi_extract/bin/php /home/xc_vm/bin/
    elif [ -d /tmp/xtreampi_extract/ubuntu_24/bin/php ]; then
        cp -r /tmp/xtreampi_extract/ubuntu_24/bin/php /home/xc_vm/bin/
    fi
    chmod -R 755 /home/xc_vm/bin/php 2>/dev/null || true
    chmod +x /home/xc_vm/bin/php/bin/* /home/xc_vm/bin/php/sbin/* 2>/dev/null || true
    chown -R xc_vm:xc_vm /home/xc_vm/bin/php 2>/dev/null || true
    rm -rf /tmp/ubuntu_24.tar.gz /tmp/xtreampi_extract
fi

# 5. Write config.ini if config.enc does not exist
if [ ! -f /home/xc_vm/config/config.enc ]; then
    echo "==> [XtreamPi Dev] Initializing database credentials in config.ini..."
    cat << EOF > /home/xc_vm/config/config.ini
; XtreamPi Configuration
; -----------------
[XC_VM]
hostname    =   "${DB_HOST}"
database    =   "${DB_NAME}"
port        =   ${DB_PORT}
server_id   =   1

[Encrypted]
username    =   "${DB_USER}"
password    =   "${DB_PASS}"
EOF
    chown xc_vm:xc_vm /home/xc_vm/config/config.ini
fi

# 6. Ensure OPcache instant reload in php.ini
if [ -f /home/xc_vm/bin/php/lib/php.ini ]; then
    sed -i 's/opcache.revalidate_freq = .*/opcache.revalidate_freq = 0/' /home/xc_vm/bin/php/lib/php.ini 2>/dev/null || true
    sed -i 's/opcache.validate_timestamps = .*/opcache.validate_timestamps = 1/' /home/xc_vm/bin/php/lib/php.ini 2>/dev/null || true
fi

# 7. Setup smart Nginx launcher
if [ -f /usr/sbin/nginx-arm64 ]; then
    cat << "EOF" > /home/xc_vm/bin/nginx/sbin/nginx
#!/bin/bash
exec /usr/sbin/nginx-arm64 -p /home/xc_vm/bin/nginx/ -c /home/xc_vm/bin/nginx/conf/nginx.conf "$@"
EOF
    chmod 755 /home/xc_vm/bin/nginx/sbin/nginx
    chown xc_vm:xc_vm /home/xc_vm/bin/nginx/sbin/nginx
    setcap "cap_net_bind_service=+ep" /usr/sbin/nginx-arm64 2>/dev/null || true
else
    chmod 755 /home/xc_vm/bin/nginx/sbin/nginx 2>/dev/null || true
    setcap "cap_net_bind_service=+ep" /home/xc_vm/bin/nginx/sbin/nginx 2>/dev/null || true
fi

# 8. Generate Admin Code configuration for Nginx
echo "==> [XtreamPi Dev] Configuring Admin Route code: /${ADMIN_CODE}..."
cat << EOF > "/home/xc_vm/bin/nginx/conf/codes/${ADMIN_CODE}.conf"
location ^~ /${ADMIN_CODE}/assets/ {
    alias /home/xc_vm/Public/assets/admin/;
    etag on;
    add_header Cache-Control "no-cache";
}

location ~ ^/${ADMIN_CODE}/(live|vod|timeshift|thumb|proxy_api)$ {
    limit_req zone=one burst=8;
    include limit_queue.conf;
    fastcgi_index index.php;
    fastcgi_pass php;
    include fastcgi_params;
    fastcgi_buffering on;
    fastcgi_buffers 128 32k;
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 128k;
    fastcgi_max_temp_file_size 0;
    fastcgi_keep_conn on;
    fastcgi_param SCRIPT_FILENAME /home/xc_vm/Public/admin/index.php;
    fastcgi_param SCRIPT_NAME /public/admin/index.php;
    fastcgi_param XC_ADMIN \$1;
}

location /${ADMIN_CODE} {
    alias /home/xc_vm/Public/Views/admin;
    try_files \$uri \$uri.html @fc_${ADMIN_CODE};
    
    location ~ \.php$ {
        limit_req zone=one burst=500 nodelay;
        try_files \$uri @fc_${ADMIN_CODE};
        fastcgi_index index.php;
        fastcgi_pass php;
        include fastcgi_params;
        fastcgi_buffering on;
        fastcgi_buffers 96 32k;
        fastcgi_buffer_size 32k;
        fastcgi_max_temp_file_size 0;
        fastcgi_keep_conn on;
        fastcgi_param SCRIPT_FILENAME \$request_filename;
        fastcgi_param SCRIPT_NAME \$fastcgi_script_name;
    }
}

location @fc_${ADMIN_CODE} {
    fastcgi_index index.php;
    fastcgi_pass php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /home/xc_vm/Public/index.php;
    fastcgi_param SCRIPT_NAME /public/index.php;
    fastcgi_param XC_SCOPE admin;
    fastcgi_param XC_CODE  ${ADMIN_CODE};
    fastcgi_buffering on;
    fastcgi_buffers 96 32k;
    fastcgi_buffer_size 32k;
    fastcgi_max_temp_file_size 0;
    fastcgi_keep_conn on;
}
EOF
chown -R xc_vm:xc_vm "/home/xc_vm/bin/nginx/conf/codes" 2>/dev/null || true

# 9. Start Cron Scheduler
echo "==> [XtreamPi Dev] Starting cron scheduler..."
service cron start 2>/dev/null || cron || true

# 10. Start PHP-FPM pools
echo "==> [XtreamPi Dev] Starting PHP-FPM pools..."
sudo -u xc_vm /home/xc_vm/bin/daemons.sh 2>/dev/null || true

# 11. Run console startup, crontab setup, and database migrations automatically
echo "==> [XtreamPi Dev] Running automatic database migrations and status checks..."
sudo /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php status 1 >/dev/null 2>&1 || true
sudo /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php status >/dev/null 2>&1 || true
sudo /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php startup >/dev/null 2>&1 || true

# 11.1. Provision the free GeoLite2 country database when a fresh dev
# container starts. The normal weekly cron maintains it afterwards. Failure is
# non-fatal so offline development still starts, with GeoIP safely unavailable.
if [ ! -s /home/xc_vm/bin/maxmind/GeoLite2-Country.mmdb ]; then
    echo "==> [XtreamPi Dev] Downloading the GeoLite2 Country database..."
    sudo /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php cron:maxmind >/dev/null 2>&1 \
        || echo "==> [XtreamPi Dev] GeoLite2 download unavailable; continuing without GeoIP data."
fi

# 12. Auto-seed default Administrator if database is fresh
echo "==> [XtreamPi Dev] Checking administrator account..."
sudo /home/xc_vm/bin/php/bin/php -r "
require_once '/home/xc_vm/bootstrap.php';
\XC_Bootstrap::boot(\XcVm\Core\Enum\BootContext::Cli, ['process' => 'AdminInit']);
\$db = \XcVm\Infrastructure\Database\DatabaseFactory::get();
if (\$db) {
    \$res = \$db->query('SELECT COUNT(\`id\`) AS \`count\` FROM \`users\` LEFT JOIN \`users_groups\` ON \`users_groups\`.\`group_id\` = \`users\`.\`member_group_id\` WHERE \`users_groups\`.\`is_admin\` = 1');
    if (\$res && \$db->get_row()['count'] == 0) {
        \$user = '${ADMIN_USER}';
        \$pass = '${ADMIN_PASS}';
        \$email = '${ADMIN_EMAIL}';
        \$hash = \XcVm\Core\Auth\Authenticator::hashPassword(\$pass);
        \$db->query('INSERT INTO \`users\` (\`username\`, \`password\`, \`email\`, \`member_group_id\`, \`date_registered\`, \`last_login\`, \`ip\`, \`status\`) VALUES (?, ?, ?, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), \"127.0.0.1\", 1)', \$user, \$hash, \$email);
        \$db->query('UPDATE \`servers\` SET \`server_ip\` = \"127.0.0.1\" WHERE \`is_main\` = 1 LIMIT 1');
        \$db->query('UPDATE \`settings\` SET \`ffmpeg_cpu\` = \"8.0\", \`ffmpeg_gpu\` = \"8.0\" WHERE \`id\` = 1');
        echo '==> [XtreamPi Dev] Fresh installation initialized! Default admin created: ' . \$user . PHP_EOL;
    }
}
" 2>/dev/null || true

# 13. Run initial root signals update to populate system status
echo "==> [XtreamPi Dev] Running initial root signals cycle..."
sudo /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php cron:root_signals >/dev/null 2>&1 || true
chmod 666 /home/xc_vm/config/signals.last 2>/dev/null || true

# 14. Start background worker threads
sudo -u xc_vm /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php signals >/dev/null 2>&1 &
sudo -u xc_vm /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php watchdog >/dev/null 2>&1 &
sudo -u xc_vm /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php queue >/dev/null 2>&1 &
sudo -u xc_vm /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php cache_handler >/dev/null 2>&1 &

# 15. Launch Nginx Web Server
echo "==> [XtreamPi Dev] Launching Nginx Web Server..."
sudo -u xc_vm /home/xc_vm/bin/nginx/sbin/nginx 2>/dev/null || true

# 15.1. Database access codes are the source of truth for admin routes. This
# replaces the development bootstrap route above after services are online, so
# a container recreate cannot leave Nginx serving a code absent from the DB.
sudo /home/xc_vm/bin/php/bin/php /home/xc_vm/console.php tools access >/dev/null 2>&1 || true

echo "==> [XtreamPi Dev] Everything is ready! Panel: http://localhost:8880/${ADMIN_CODE}"

# Keep container running in foreground
exec sleep infinity

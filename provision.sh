#!/bin/bash
set -e

if [ -z "$1" ]; then
    echo "Usage: ./provision.sh <app-name> [domain]"
    echo "Example: ./provision.sh portfolio portfolio.benhughes.uk"
    echo ""
    echo "If domain is provided, a Caddy site config is created on the VPS"
    exit 1
fi

APP_NAME=$1
DOMAIN=$2
JUMP="ssh -o ConnectTimeout=10 -J cb-dvr endor"

echo "==> Finding next available VMID on endor..."
VMID=$($JUMP "pvesh get /cluster/nextid")
echo "==> Using VMID: $VMID"
DEPLOY_KEY="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFtwKK916sKkFWHQxjV2qUpPZI05bS5v9l2jugvpg4We forgejo-runner-deploy"

echo "==> Creating LXC $VMID ($APP_NAME) on endor..."
$JUMP "pct create $VMID vault:vztmpl/debian-13-standard_13.1-2_amd64.tar.zst \
    --hostname $APP_NAME \
    --cores 2 \
    --memory 4096 \
    --swap 512 \
    --rootfs local-btrfs:20 \
    --net0 name=eth0,bridge=vmbr3,ip=dhcp,type=veth \
    --nameserver 10.0.60.181 \
    --searchdomain fnstr.uk \
    --ostype debian \
    --unprivileged 1 \
    --features nesting=1 \
    --onboot 1 \
    --start 1"

sleep 3

IP=$($JUMP "pct exec $VMID -- hostname -I" | tr -d ' ')
echo "==> LXC IP: $IP"

echo "==> Installing base packages..."
$JUMP "pct exec $VMID -- bash -c '
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl gnupg git unzip >/dev/null
'"

echo "==> Installing PHP 8.4, nginx, node, composer..."
$JUMP "pct exec $VMID -- bash -c '
    apt-get install -y -qq nginx \
        php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml \
        php8.4-curl php8.4-zip php8.4-bcmath php8.4-gd php8.4-intl \
        php8.4-readline php8.4-opcache php8.4-redis php8.4-xsl \
        php8.4-sockets php8.4-pcntl >/dev/null

    curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer >/dev/null

    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1
    apt-get install -y -qq nodejs >/dev/null

    php -v | head -1
    node -v
    composer -V | head -1
    nginx -v
'"

echo "==> Configuring nginx for $APP_NAME..."
$JUMP "pct exec $VMID -- bash -c 'cat > /etc/nginx/sites-available/$APP_NAME << \"NGINX\"
server {
    listen 80;
    server_name _;
    root /var/www/$APP_NAME/public;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    index index.php;
    charset utf-8;

    location / {
        try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php\\\$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \\\$realpath_root\\\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
NGINX
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/$APP_NAME
nginx -t && systemctl reload nginx
'"

echo "==> Setting up deploy key and git..."
$JUMP "pct exec $VMID -- bash -c '
    mkdir -p /root/.ssh
    echo \"$DEPLOY_KEY\" >> /root/.ssh/authorized_keys
    chmod 700 /root/.ssh
    chmod 600 /root/.ssh/authorized_keys
    git config --global --add safe.directory /var/www/$APP_NAME
'"

echo "==> Creating web root..."
$JUMP "pct exec $VMID -- bash -c '
    mkdir -p /var/www/$APP_NAME
    chown -R www-data:www-data /var/www/$APP_NAME
'"

if [ -n "$DOMAIN" ]; then
    echo "==> Adding Caddy site for $DOMAIN on VPS..."
    ssh -o ConnectTimeout=10 beacon "cat > /etc/caddy/sites/$DOMAIN << CADDY
$DOMAIN, www.$DOMAIN {
    import security_headers
    import log_to_file
    encode zstd gzip
    reverse_proxy $IP:80 {
        header_up X-Forwarded-Proto {scheme}
    }
}
CADDY
caddy validate --config /etc/caddy/Caddyfile && systemctl reload caddy"
    echo "==> Caddy configured: https://$DOMAIN -> $IP:80"
    echo ""
    echo "==> REMINDER: Add DNS A record for $DOMAIN pointing to 5.75.209.240 (floating IP)"
fi

echo ""
echo "============================================"
echo "  LXC $VMID ($APP_NAME) is ready"
echo "  IP: $IP"
if [ -n "$DOMAIN" ]; then
echo "  Domain: https://$DOMAIN"
fi
echo "============================================"
echo ""
echo "Next steps:"
echo "  1. Clone your repo:  ssh -J cb-dvr endor 'pct exec $VMID -- git clone https://git.fnstr.uk/ben/$APP_NAME.git /var/www/$APP_NAME'"
echo "  2. Create .env:      ssh -J cb-dvr endor 'pct exec $VMID -- cp /var/www/$APP_NAME/.env.production /var/www/$APP_NAME/.env'"
echo "  3. Edit .env:        ssh -J cb-dvr endor 'pct exec $VMID -- nano /var/www/$APP_NAME/.env'"
echo "  4. Key + migrate:    ssh -J cb-dvr endor 'pct exec $VMID -- bash -c \"cd /var/www/$APP_NAME && php artisan key:generate && php artisan migrate && php artisan storage:link\"'"
echo "  5. Permissions:      ssh -J cb-dvr endor 'pct exec $VMID -- chown -R www-data:www-data /var/www/$APP_NAME'"
echo "  6. Push to trigger first deploy"

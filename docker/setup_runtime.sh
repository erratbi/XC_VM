#!/bin/bash
set -e

ARCH=$(dpkg --print-architecture)

if [ "$ARCH" = "arm64" ]; then
    echo "==> Configuring AMD64 multiarch runtime for Apple Silicon..."
    cat << "EOF" > /etc/apt/sources.list.d/amd64.sources
Types: deb
URIs: http://archive.ubuntu.com/ubuntu/
Suites: noble noble-updates noble-security noble-backports
Components: main restricted universe multiverse
Architectures: amd64
Signed-By: /usr/share/keyrings/ubuntu-archive-keyring.gpg
EOF

    sed -i 's/Types: deb/Types: deb\nArchitectures: arm64/' /etc/apt/sources.list.d/ubuntu.sources 2>/dev/null || true
    dpkg --add-architecture amd64
    apt-get update

    apt-get install -y --no-install-recommends \
        libc6-amd64-cross libstdc++6-amd64-cross iptables

    mkdir -p /lib64 /usr/lib/x86_64-linux-gnu /lib/x86_64-linux-gnu
    ln -sf /usr/x86_64-linux-gnu/lib/ld-linux-x86-64.so.2 /lib64/ld-linux-x86-64.so.2
    echo "/usr/x86_64-linux-gnu/lib" > /etc/ld.so.conf.d/x86_64-cross.conf

    cd /tmp
    apt-get download \
        libxml2:amd64 libssl3t64:amd64 zlib1g:amd64 libonig5:amd64 libzip4t64:amd64 \
        libpng16-16t64:amd64 libjpeg-turbo8:amd64 libfreetype6:amd64 libicu74:amd64 \
        liblzma5:amd64 libsodium23:amd64 libxslt1.1:amd64 libzstd1:amd64 libbz2-1.0:amd64 \
        libgcrypt20:amd64 libgpg-error0:amd64 libffi8:amd64 libssh2-1t64:amd64 \
        libcurl3t64-gnutls:amd64 libgnutls30t64:amd64 libhogweed6t64:amd64 libnettle8t64:amd64 \
        libgmp10:amd64 libp11-kit0:amd64 libtasn1-6:amd64 libunistring5:amd64 libidn2-0:amd64 \
        libbrotli1:amd64 libnghttp2-14:amd64 libpsl5t64:amd64 librtmp1:amd64 libssh-4:amd64 \
        libldap2:amd64 libsasl2-2:amd64 libgssapi-krb5-2:amd64 libkrb5-3:amd64 libk5crypto3:amd64 \
        libcom-err2:amd64 libkrb5support0:amd64 libkeyutils1:amd64

    for deb in *.deb; do
        dpkg -x "$deb" /
        rm -f "$deb"
    done

    # Setup native ARM64 nginx binary wrapper to bypass qemu-user io_setup limitation
    apt-get download nginx:arm64
    dpkg -x nginx_*_arm64.deb /tmp/arm_nginx
    cp /tmp/arm_nginx/usr/sbin/nginx /usr/sbin/nginx-arm64
    chmod 755 /usr/sbin/nginx-arm64
    rm -rf /tmp/arm_nginx nginx_*_arm64.deb

    ldconfig
else
    echo "==> Native AMD64 build detected, installing AMD64 libraries..."
    apt-get update && apt-get install -y --no-install-recommends \
        libssl3t64 zlib1g libonig5 libzip4t64 libpng16-16t64 libjpeg-turbo8 \
        libfreetype6 libicu74 liblzma5 libxslt1.1 libzstd1 libbz2-1.0 \
        libgcrypt20 libgpg-error0 libffi8 libssh2-1t64 libcurl3t64-gnutls iptables
fi

apt-get clean
rm -rf /var/lib/apt/lists/* /tmp/*

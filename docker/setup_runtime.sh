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
    echo "/usr/lib/x86_64-linux-gnu" >> /etc/ld.so.conf.d/x86_64-cross.conf
    echo "/home/xc_vm/bin/ffmpeg_bin/lib" >> /etc/ld.so.conf.d/x86_64-cross.conf

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
        libcom-err2:amd64 libkrb5support0:amd64 libkeyutils1:amd64 \
        libopenal1:amd64 libdc1394-25:amd64 libpulse0:amd64 libharfbuzz0b:amd64 \
        libfribidi0:amd64 libass9:amd64 libva2:amd64 libva-drm2:amd64 libzmq5:amd64 \
        libfontconfig1:amd64 libmfx1:amd64 libzvbi0t64:amd64 libfdk-aac2:amd64 \
        libgsm1:amd64 libmp3lame0:amd64 libopus0:amd64 libspeex1:amd64 libtheora0:amd64 \
        libvorbis0a:amd64 libvorbisenc2:amd64 libx265-199:amd64 libxvidcore4:amd64 \
        libsoxr0:amd64 libnorm1t64:amd64 libpgm-5.3-0t64:amd64 libsndio7.0:amd64 \
        libnuma1:amd64 libraw1394-11:amd64 libusb-1.0-0:amd64 libdbus-1-3:amd64 \
        libgraphite2-3:amd64 libbsd0:amd64 libexpat1:amd64 libuuid1:amd64 libogg0:amd64 \
        libcairo2:amd64 libasound2t64:amd64 libudev1:amd64 libsndfile1:amd64 \
        libx11-xcb1:amd64 libx11-6:amd64 libxcb1:amd64 libsystemd0:amd64 libasyncns0:amd64 \
        libapparmor1:amd64 libmd0:amd64 libpixman-1-0:amd64 libxcb-shm0:amd64 \
        libxcb-render0:amd64 libxrender1:amd64 libxext6:amd64 libxau6:amd64 \
        libxdmcp6:amd64 liblz4-1:amd64 libcap2:amd64 libgomp1:amd64 libdrm2:amd64 \
        libpcre2-8-0:amd64 libmpg123-0t64:amd64 \
        libstdc++6:amd64 libglib2.0-0t64:amd64 libunibreak5:amd64 libflac12t64:amd64

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
        libgcrypt20 libgpg-error0 libffi8 libssh2-1t64 libcurl3t64-gnutls iptables \
        libopenal1 libdc1394-25 libpulse0 libharfbuzz0b libfribidi0 libass9 \
        libva2 libva-drm2 libzmq5 libfontconfig1 libmfx1 libzvbi0t64 libfdk-aac2 \
        libgsm1 libmp3lame0 libopus0 libspeex1 libtheora0 libvorbis0a libvorbisenc2 \
        libx265-199 libxvidcore4 libsoxr0 libnorm1t64 libpgm-5.3-0t64 libsndio7.0 \
        libnuma1 libraw1394-11 libusb-1.0-0 libdbus-1-3 libgraphite2-3 libbsd0 \
        libexpat1 libuuid1 libogg0 libcairo2 libasound2t64 libudev1 libsndfile1 \
        libx11-xcb1 libx11-6 libxcb1 libsystemd0 libasyncns0 libapparmor1 libmd0 \
        libpixman-1-0 libxcb-shm0 libxcb-render0 libxrender1 libxext6 libxau6 \
        libxdmcp6 liblz4-1 libcap2 libgomp1 libdrm2 libpcre2-8-0 libmpg123-0t64
    apt-get clean
    rm -rf /var/lib/apt/lists/* /tmp/*
fi

# Ensure /tmp has standard sticky bit permissions for PHP and ionCube lock files
chmod 1777 /tmp

# Download distribution PHP binaries if not already present
if [ ! -f /home/xc_vm/bin/php/sbin/php-fpm ]; then
    echo "==> Installing distribution PHP binaries..."
    BIN_TAG=$(curl -s https://api.github.com/repos/Vateron-Media/XC_VM_Binaries/releases/latest | grep '"tag_name":' | head -n 1 | cut -d '"' -f 4)
    BIN_TAG="${BIN_TAG:-29062026}"
    mkdir -p /tmp/xcvm_extract /home/xc_vm/bin
    curl -sL "https://github.com/Vateron-Media/XC_VM_Binaries/releases/download/${BIN_TAG}/ubuntu_24.tar.gz" -o /tmp/ubuntu_24.tar.gz
    tar -xzf /tmp/ubuntu_24.tar.gz -C /tmp/xcvm_extract/
    if [ -d /tmp/xcvm_extract/bin/php ]; then
        cp -r /tmp/xcvm_extract/bin/php /home/xc_vm/bin/
    elif [ -d /tmp/xcvm_extract/ubuntu_24/bin/php ]; then
        cp -r /tmp/xcvm_extract/ubuntu_24/bin/php /home/xc_vm/bin/
    fi
    chmod -R 755 /home/xc_vm/bin/php 2>/dev/null || true
    chmod +x /home/xc_vm/bin/php/bin/* /home/xc_vm/bin/php/sbin/* 2>/dev/null || true
    rm -rf /tmp/ubuntu_24.tar.gz /tmp/xcvm_extract
fi

apt-get clean
rm -rf /var/lib/apt/lists/* /tmp/*

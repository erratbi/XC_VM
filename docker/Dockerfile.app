FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive
ENV container=docker
ENV LC_ALL=C
ENV TZ=UTC

# Install native system utilities and runtime tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    sudo cron curl wget xz-utils unzip iproute2 net-tools ca-certificates \
    procps psmisc netcat-openbsd mariadb-client \
    libmaxminddb0 libxml2 libsodium23 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy and execute architecture runtime setup
COPY docker/setup_runtime.sh /tmp/setup_runtime.sh
RUN chmod +x /tmp/setup_runtime.sh && /tmp/setup_runtime.sh && rm -f /tmp/setup_runtime.sh

# Create xc_vm user and group matching standard installation
RUN groupadd -g 102 xc_vm 2>/dev/null || groupadd xc_vm && \
    useradd -u 102 -g xc_vm -m -s /bin/bash xc_vm 2>/dev/null || useradd -g xc_vm -m -s /bin/bash xc_vm && \
    echo "xc_vm ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers.d/xc_vm && \
    chmod 0440 /etc/sudoers.d/xc_vm

WORKDIR /home/xc_vm

# Copy bundled binaries into /home/xc_vm/bin
COPY src/bin /home/xc_vm/bin

# Set execution permissions on all binary scripts and executables
RUN chmod -R 755 /home/xc_vm/bin && \
    chmod +x /home/xc_vm/bin/daemons.sh /home/xc_vm/bin/nginx/sbin/nginx /home/xc_vm/bin/nginx_rtmp/sbin/nginx_rtmp /home/xc_vm/bin/php/bin/* 2>/dev/null || true

# Copy service and update scripts
COPY src/service /home/xc_vm/service
COPY src/update /home/xc_vm/update
RUN chmod 755 /home/xc_vm/service /home/xc_vm/update

# Create storage, stream, and temp directories
RUN mkdir -p /home/xc_vm/content/streams /home/xc_vm/tmp /home/xc_vm/storage /home/xc_vm/config /home/xc_vm/bin/nginx/conf/codes && \
    chown -R xc_vm:xc_vm /home/xc_vm && \
    chmod 1777 /home/xc_vm/tmp /home/xc_vm/content/streams

# Copy and setup entrypoint
COPY docker/entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["/docker-entrypoint.sh"]

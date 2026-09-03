# CONTEXT.md — XC_VM System & Streaming Architecture

This document provides complete, high-density context for developers and AI agents working in this repository. It documents architectural invariants, the theme isolation layer, streaming pipeline mechanics, DASH Clearkey DRM handling, HTTP redirect resolution, Docker development environment details, security settings, and deployment conventions.

---

## 1. Repository Overview

* **Upstream Repository:** [Vateron-Media/XC_VM](https://github.com/Vateron-Media/XC_VM)
* **Active Working Fork:** [erratbi/XC_VM](https://github.com/erratbi/XC_VM) (`main` branch)
* **Application Root:** `src/` (deployed verbatim to `/home/xc_vm/` on servers, aliased to `MAIN_HOME`).
* **Technology Stack:** PHP 8.1+ (PSR-4 autoloading via `XcVm\` namespace), Nginx, Redis, MariaDB 10.11, FFmpeg/FFprobe.
* **Database Access:** Core classes use `\XcVm\Infrastructure\Database\DatabaseAware` (`self::db()`) or `DatabaseFactory::get()`.

---

## 2. Absolute Architectural Rules & Invariants (CRITICAL)

### A. Theme Layer Isolation (Never Touch Core for Themes)
* **Rule:** StreamCreed (`src/Public/Views/streamcreed/`, `src/Public/assets/admin/streamcreed/`) is an overlay theme.
* **Core files must remain 100% untouched:** Never edit core controllers (e.g. [`src/Public/Controllers/Admin/TableController.php`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Public/Controllers/Admin/TableController.php)), domain models, or legacy views in `src/Public/Views/admin/` to accommodate theme aesthetics, formatting, or data structures.
* **Legacy Compatibility:** The original legacy theme must remain completely intact and functional at all times.
* **Theme-Side Adaptation:** Any data normalization, HTML tag stripping, status badge rendering, or layout adaptation **must** be implemented inside the theme layer (client-side JS in `src/Public/assets/admin/streamcreed/` or theme views in `src/Public/Views/streamcreed/`).

### B. Git Workflow Rules
* Commit logically with atomic Conventional Commits (`feat(...)`, `fix(...)`, `ci(...)`).
* **Never push to remote (`git push`)** unless explicitly instructed by the user.
* Keep the working tree clean and test every change.

### C. IonCube Loader & PHP CLI Testing Caveat
* The bundled PHP distribution includes the ionCube loader extension, which hooks into PHP's compilation engine.
* Running `php -l` without the `-n` flag causes an immediate **segmentation fault (exit code 139)** in CLI environments.
* **Always run CLI syntax checks with `-n` (disable php.ini extensions):**
  ```bash
  php -n -l <filepath>
  ```

---

## 3. Docker Local Development Stack (`compose.yaml`)

### Topology & Ports
* **`xcvm-app`:** Primary application container running PHP 8.1 FPM + Nginx on Ubuntu 24.
  * HTTP: `8880` (maps to container port `80`)
  * HTTPS: `8443` (maps to container port `443`)
* **`mariadb`:** MariaDB 10.11 (`xtream_iptvpro` database, port `3306`).
* **`redis`:** Redis 7.0 for session management, stream connection tracking, and caching (port `6379`).
* **`phpmyadmin`:** Database GUI accessible at `http://localhost:8080`.

### Zombie Process Reaping (`init: true`)
* In Docker, cron runs internal maintenance jobs (`cron:root_signals`, `cron:root_mysql`) every minute.
* Because containerized cron jobs exit frequently, their parent exits and children reparent to PID 1.
* **`init: true`** is configured under `xcvm-app` in `compose.yaml` to run Docker's `tini` as PID 1.
* Tini handles `SIGCHLD` and calls `waitpid()` to reap terminated child processes. Without `init: true`, zombie processes accumulate rapidly (30,000+ over a few days), exhausting the kernel `nproc` table and freezing PHP-FPM worker forking (`EAGAIN: Resource temporarily unavailable`).

### Live Mounts & Nginx IPC
* Live source directories (`Core`, `Domain`, `Streaming`, `Public`, `Infrastructure`, `Modules`, etc.) are mounted from `./src` into `/home/xc_vm/`.
* Host `./src/bin/nginx/conf/nginx.conf` is mounted read-only into `/home/xc_vm/bin/nginx/conf/nginx.conf:ro`.
* Nginx writes its master PID to `/home/xc_vm/bin/nginx/logs/nginx.pid` (owned by user `xc_vm`), avoiding `/run/nginx.pid` root permission errors.
* **Nginx Route Isolation:** Streaming endpoints (`location ~ ^/admin/(live|proxy_api|thumb|timeshift|vod)$`) are strictly isolated from the front controller (`@fc_admin`) so that `/admin/api` (UI stats and AJAX) and `/admin/login` are never shadowed by cluster node streaming handlers.

### Multiarch Codec Libraries & FFmpeg Setup (Apple Silicon / Docker)
* **Bundled Binaries:** Bundled FFmpeg / FFprobe binaries in `/home/xc_vm/bin/ffmpeg_bin/` are compiled for AMD64 (`x86_64`) and require dynamic multimedia libraries (`libopenal1`, `libdc1394-25`, `libass9`, `libpulse0`, `libstdc++6`, `libglib2.0-0t64`, `libunibreak5`, `libflac12t64`, etc.).
* **Library Search Path:** The multiarch library directories `/usr/lib/x86_64-linux-gnu` and `/home/xc_vm/bin/ffmpeg_bin/lib` must be registered in `/etc/ld.so.conf.d/x86_64-cross.conf` followed by `ldconfig`.
* **FFmpeg Version Invariant:** Modern bundled FFmpeg binaries remove obsolete flags like `-nofix_dts`. Database default setting `ffmpeg_cpu` and `ffmpeg_gpu` is set to `8.0` in `database.sql` and seeded in `entrypoint.sh`.

### Automatic Administrator Seeding (`entrypoint.sh`)
* On fresh container boot (`docker compose down -v && docker compose up`), `entrypoint.sh` auto-provisions the administrator if the `users` table contains zero admins.
* **Credentials:** Uses `XCVM_ADMIN_USER` (default `admin`) and `XCVM_ADMIN_PASS` (default `admin1234`).
* **Hashing Rule:** Password hashing **must** use `\XcVm\Core\Auth\Authenticator::hashPassword($pass)` directly rather than inline bash `crypt()` strings to avoid bash variable expansion corrupting the salt.

---

## 4. StreamCreed UI Architecture

StreamCreed is the modern, responsive administrative interface providing a dark/light design system built on pure Vanilla CSS and modular JavaScript.

* **Views Location:** `src/Public/Views/streamcreed/admin/`
  * `dashboard.php` — Server metrics, real-time load, connection sparklines.
  * `stream.php` — Stream editor, source probe, adaptive streaming, track mapping, failover.
  * `streams.php` — Live streams overview, search, category filter, connection counts, status badges.
  * `lines.php` — User subscriptions, line credentials, connection caps, expiration badges.
  * `mag.php`, `enigma.php`, `hmac.php` — Device management tables and activation handoffs.
  * `bouquet.php`, `stream_category.php` — Bouquet and category management.
* **Assets Location:** `src/Public/assets/admin/streamcreed/`
  * `streamcreed.css` — Modern design system tokens, OKLCH color palettes, smooth transitions, mobile responsiveness.
  * `streams.js`, `lines.js`, `mag.js`, `enigma.js`, `stream-*.js` — Self-contained ES6/Vanilla JS modules with zero dependencies.
* **Navigation:** Drill-in collapsible sidebar categorized by Content, User Management, Device Management, and Service Setup with user group permission checks.

### Visual Aesthetics & Code Consistency Standards
* **Super User-Friendly & Visually Appealing:** StreamCreed interfaces must look visually stunning, modern, clean, and intuitive. Avoid generic colors, raw unstyled buttons, or clunky layouts. Use subtle micro-animations, consistent padding, badges with indicator dots, and crisp typography.
* **Consistency Check Before Coding:** Whenever creating or updating a StreamCreed page, always inspect existing reference pages (`lines.php`, `streams.php`, `mag.php`, `stream.php`, `bouquet.php`) to preserve identical visual styling, layout rhythm, class conventions, and JavaScript architecture:
  * **Standard DOM Architecture:**
    - Container: `<section data-sc-<feature>>`
    - Header: `<div class="sc-page-heading"><div><p class="sc-eyebrow">Category</p><h1>Page Title</h1></div><div class="sc-page-actions">...</div></div>`
    - Controls: `<div class="sc-toolbar">` with live search, dropdown filters, and per-page entries selector.
    - Data Container: `<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table">...`
    - Pagination Footer: `<footer class="sc-table-footer"><span data-range></span><div class="sc-pagination">...</div></footer>`
  * **Standard JavaScript Architecture:**
    - Scoped self-invoking closure: `(function () { 'use strict'; ... })();`
    - Safe DOM building helper: `el(tag, className, text)` using `textContent` for safety.
    - Loading & empty states: `state('Loading...')` and `state('No items match these filters.')`.
    - Resilient frontend parsing: Always parse and sanitize responses in the theme layer rather than requiring core controller changes.

---

## 5. Ingestion & Streaming Engine Architecture

### Ingestion Protocol Flow
1. **Source Configuration:** Sources are stored in `streams` and `streams_options` tables.
2. **URL Normalization & Key Extraction ([`src/Core/Util/StreamUtils.php`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Core/Util/StreamUtils.php)):**
   * **CENC DRM Keys:** `StreamUtils::extractDecryptionKey($rURL)` extracts and normalizes Clearkey hex keys from query strings or pipe parameters:
     * `?decryption_key=KID1:KEY1,KID2:KEY2,...` $\implies$ returns formatted `"KID1:KEY1,KID2:KEY2,..."`
     * `?decryption_key=KID:KEY` $\implies$ returns formatted `"KID:KEY"`
     * `?decryption_key=KEY` or `?cenc_decryption_key=KEY` $\implies$ returns 32-hex `"KEY"`
     * `|decryption_key=KID1:KEY1,KID2:KEY2` $\implies$ returns formatted `"KID1:KEY1,KID2:KEY2"`
   * **Proxy Extraction:** `StreamUtils::extractProxy($rURL, $rFetchArguments)` extracts HTTP proxies from query params (`?proxy=...`, `?http_proxy=...`), pipe syntax (`|proxy=...`), or FFmpeg args (`-http_proxy '...'`).
   * **HTTP Redirects:** `StreamUtils::parseStreamURL($rURL, $rProxy)` resolves 301/302 redirects via `CurlClient::getEffectiveURL($rURL, 4, $userAgent, $rProxy)` to find the final `.mpd` manifest URL on the CDN.
3. **FFmpeg Command Generation ([`src/Domain/Stream/StreamProcess.php`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Domain/Stream/StreamProcess.php)):**
   * Decryption keys are appended via `-decryption_key '<KEYS>'` before `-i '<EFFECTIVE_URL>'`.
   * Live network reconnect options (`-reconnect 1 -reconnect_at_eof 1 -reconnect_streamed 1 -reconnect_delay_max 5`) maintain stream stability during upstream hiccups.
   * Container format detection disables `-re` for DASH streams to preserve native segment delivery rate.
4. **Stream Prober ([`src/Streaming/Codec/FFprobeRunner.php`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Streaming/Codec/FFprobeRunner.php)):**
   * `FFprobeRunner::probeStream($url)` resolves the effective URL via proxy, attaches `-decryption_key '<KEYS>'`, and invokes FFprobe with structured JSON output.

---

## 6. Critical Gotchas & Troubleshooting

### A. DASH Relative Segments Behind 302 Redirects
* **Symptom:** FFmpeg fails to fetch `.m4s` segments with `404 Not Found` when source URL is a proxy that returns an HTTP 302 redirect to a CDN.
* **Cause:** FFmpeg's DASH demuxer (`dashdec.c`) computes relative segment URLs against the *input URL* rather than the *redirect target*.
* **Fix:** [`CurlClient::getEffectiveURL()`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Core/Http/CurlClient.php) resolves the target URL before FFmpeg is executed.

### B. Subnet IP Matching (`ip_subnet_match`)
* **Symptom:** Client requests return 404/401 when accessing streams generated by the web panel or client apps.
* **Cause:** If client IP differs across requests (Docker NAT `192.168.97.1` vs `192.168.97.0`, mobile networks, multi-WAN), token verification fails if subnet matching is disabled.
* **Setting:** Database `settings.ip_subnet_match` (UI: **Settings** $\to$ **Security** $\to$ **`Match Subnet of IP`**). Keep enabled (`1`).

### C. Database Strict Mode & Timestamp Handling
* **Symptom:** `SQLSTATE[HY000]: General error: 1364 Field 'date_added' doesn't have a default value` or invalid default value for `CURRENT_TIMESTAMP`.
* **Fix:** [`QueryHelper::verifyPostTable()`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Core/Database/QueryHelper.php) checks column defaults and skips emitting explicit string literals for `current_timestamp()` defaults on INSERT.

### D. Multi-Server Cluster Streaming
* [`ApiClient::queryServer()`](file:///Users/amir/work/IPTV/project_manhattan/XC_VM/xtream_ui/src/Core/Http/ApiClient.php) handles parallel multi-cURL dispatch across cluster nodes when starting, stopping, or probing streams across multiple server nodes.

### E. Bootstrap & Globals in CLI Scratch Scripts
* Merely including `bootstrap.php` only registers autoloaders.
* For CLI scripts, always boot with explicit context:
  ```php
  require_once "/home/xc_vm/bootstrap.php";
  \XC_Bootstrap::boot(\XcVm\Core\Enum\BootContext::Cli, ["process" => "XC_VM[Test]"]);
  ```

---

## 7. VPS Deployment & Updating

### To apply local fork patches to a live production server:
```bash
# Clone the patched fork into a temporary staging folder
git clone https://github.com/erratbi/XC_VM.git /tmp/xc_vm_patch

# Copy source tree over /home/xc_vm/
cp -r /tmp/xc_vm_patch/src/* /home/xc_vm/
chown -R xc_vm:xc_vm /home/xc_vm/

# Restart services
/home/xc_vm/service restart
rm -rf /tmp/xc_vm_patch
```

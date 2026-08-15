# Streaming, DASH DRM, and Ingestion Context

This document captures architecture, debugging history, edge cases, and design decisions regarding the XC_VM streaming engine, DASH Clearkey DRM, HTTP redirect handling, and process supervision.

---

## 1. Repository & Deployment Context

* **Upstream Project:** [Vateron-Media/XC_VM](https://github.com/Vateron-Media/XC_VM)
* **Working Fork:** [erratbi/XC_VM](https://github.com/erratbi/XC_VM) (`main` branch)
* **Architecture:** Modular monolith in PHP 8.1+ with PSR-4 autoloading (`XcVm\` $\to$ `src/`).
* **Deploy Root:** `src/` is copied verbatim to `/home/xc_vm/` on the server (`MAIN_HOME`).

---

## 2. DASH & Clearkey CENC DRM Decryption Engine

### The Problem Solved
Modern IPTV sources increasingly use MPEG-DASH (`.mpd`) manifests protected with Common Encryption (CENC / Clearkey DRM). Without decryption keys passed to FFmpeg's DASH demuxer, FFmpeg remuxes raw ciphertext packets into MPEG-TS, causing browser decoders to throw `MEDIA_ERR_DECODE (CODE:3)` / corruption errors.

### Implementation Architecture
DASH CENC decryption is handled natively in Core across all streaming lifecycles:

1. **Key Extraction ([`src/Core/Util/StreamUtils.php`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/src/Core/Util/StreamUtils.php)):**
   * `StreamUtils::extractCencKey($url)` parses Clearkey hexadecimal keys from query parameters and URL pipe strings:
     * `?decryption_key=KID:KEY` $\implies$ extracts 32-hex `KEY`
     * `?decryption_key=KEY` $\implies$ extracts 32-hex `KEY`
     * `?cenc_decryption_key=KEY` $\implies$ extracts 32-hex `KEY`
     * `|decryption_key=KID:KEY` $\implies$ extracts 32-hex `KEY`

2. **Command Injection ([`src/Domain/Stream/StreamProcess.php`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/src/Domain/Stream/StreamProcess.php)):**
   * In `startStream()` (Live) and `startMovie()` (VOD), `-cenc_decryption_key '<KEY>'` is automatically injected into `$rFetchOptions` and `$rProbeOptions` as an input option before `-i <URL>`.
   * Also integrated into [`src/Cli/Commands/ScannerCommand.php`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/src/Cli/Commands/ScannerCommand.php) and [`src/Streaming/Codec/FFprobeRunner.php`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/src/Streaming/Codec/FFprobeRunner.php).

---

## 3. HTTP 301/302 Redirect Discovery for DASH Manifests

### The Relative Path Gotcha
When a stream URL is an HTTP redirect endpoint (e.g. `http://proxy:4444/watch/channel?decryption_key=...` $\to$ `302 Found` $\to$ `https://cdn.example.com/live/.../index.mpd`):
* Inside `.mpd` manifests, media segments are defined as **relative paths** (`video/1080p/segment_$Number$.m4s`).
* FFmpeg's DASH demuxer (`libavformat/dashdec.c`) mistakenly resolves relative media segments against the **original input URL** (`http://proxy:4444/watch/...`) rather than the **redirect destination CDN URL**, causing upstream proxies to return `404 Not Found`.

### The Solution
* **[`src/Core/Http/CurlClient.php`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/src/Core/Http/CurlClient.php):**
  * `CurlClient::getEffectiveURL($url)` uses a lightweight range-bounded request (`Range: 0-1024`, `CURLOPT_FOLLOWLOCATION => true`) to resolve the destination URL.
* **[`src/Core/Util/StreamUtils.php`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/src/Core/Util/StreamUtils.php):**
  * `StreamUtils::parseStreamURL($url)` calls `CurlClient::getEffectiveURL($url)`.
* **Flow:**
  1. `$rCencKey` is extracted from the original configured source `$rSource`.
  2. `$rEffectiveURL` is resolved via `StreamUtils::parseStreamURL()`.
  3. FFmpeg is executed with `-cenc_decryption_key '<KEY>' -i '<EFFECTIVE_CDN_URL>'`.

---

## 4. Subnet IP Matching (`ip_subnet_match`)

* **Issue:** Stream preview or player tokens return `404` or `401` when client IP changes slightly between API token generation and stream playback (e.g. Docker NAT gateway `192.168.97.1` vs container IP `192.168.97.0`, mobile data roaming, or multi-WAN load balancers).
* **Setting:** Database table `settings` $\to$ column `ip_subnet_match`.
* **Panel Location:** **Settings** $\to$ **Security** tab $\to$ **`Match Subnet of IP`**.
* **Recommendation:** Ensure this setting is enabled (`1`) in environments with proxies, reverse proxies, or Docker NAT.

---

## 5. Bootstrap & Global State Notes

* **Crucial for CLI / Docker testing (`php -r` / scratch scripts):**
  Merely running `require "/home/xc_vm/bootstrap.php";` only registers PSR-4 autoloaders. It does **not** connect to MySQL or populate `$GLOBALS`.
* To run tests or standalone one-liners, you **must** call `XC_Bootstrap::boot()`:
  ```php
  require_once "/home/xc_vm/bootstrap.php";
  XC_Bootstrap::boot(XC_Bootstrap::CONTEXT_CLI, ["process" => "XC_VM[Test]"]);
  ```
* **What `XC_Bootstrap::boot()` does:**
  * Runs `DatabaseFactory::connect()`.
  * Calls `FfmpegPaths::resolve(SettingsManager::get('ffmpeg_cpu'))`.
  * Calls `LegacyInitializer::initCore()`, which executes `LegacyInitializer::exportGlobals()` to populate `$GLOBALS['rSettings']`, `$GLOBALS['rServers']`, and `$GLOBALS['rFFPROBE']`.
* In production application classes, direct singleton access is preferred: `SettingsManager::getAll()`, `ServerRepository::getAll()`, and `FfmpegPaths::probe()`.

---

## 6. Module System vs. Core Engine Boundaries

* **Core Responsibility:**
  * Protocol ingestion, demuxing, URL normalization, DRM key extraction, FFmpeg/FFprobe command builders (`src/Core/`, `src/Domain/Stream/`, `src/Streaming/`).
* **Module Responsibility ([`docs/en/development/modules.md`](file:///Users/amir/Desktop/project_manhattan/XC_VM/xtream_ui/docs/en/development/modules.md)):**
  * New features, external CRM/billing integrations, automated source fetchers, custom web/admin UI tabs, custom API routes, custom CLI commands, and event listeners (`src/Modules/<name>_<hash5>/`).

---

## 7. Deploying Patches to VPS

### Fresh Install from Fork:
```bash
sudo apt update && sudo apt install -y python3 curl wget git
git clone https://github.com/erratbi/XC_VM.git /tmp/xc_vm_install
cd /tmp/xc_vm_install
python3 install
# Overlay patched PHP code after installer finishes:
cp -r /tmp/xc_vm_install/src/* /home/xc_vm/
chown -R xc_vm:xc_vm /home/xc_vm/
/home/xc_vm/service restart
rm -rf /tmp/xc_vm_install
```

### Updating an Existing VPS Instance:
```bash
git clone https://github.com/erratbi/XC_VM.git /tmp/xc_vm_patch
cp -r /tmp/xc_vm_patch/src/* /home/xc_vm/
chown -R xc_vm:xc_vm /home/xc_vm/
/home/xc_vm/service restart
rm -rf /tmp/xc_vm_patch
```

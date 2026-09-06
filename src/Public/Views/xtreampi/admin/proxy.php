<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/server.js'];
$xtreampiCanEdit = Authorization::check('adv', 'edit_server');
$xtreampiProxy = is_array($rServerArr ?? null) ? $rServerArr : [];
$xtreampiValue = static fn(string $key, string $default = ''): string => htmlspecialchars((string) ($xtreampiProxy[$key] ?? $default), ENT_QUOTES, 'UTF-8');
$xtreampiChecked = static fn(string $key): string => !empty($xtreampiProxy[$key]) ? ' checked' : '';
$xtreampiDomains = array_values(array_filter(array_map('trim', explode(',', (string) ($xtreampiProxy['domain_name'] ?? '')))));
$xtreampiCountries = is_array($rCountries ?? null) ? $rCountries : [];
$xtreampiSelectedCountries = json_decode((string) ($xtreampiProxy['geoip_countries'] ?? '[]'), true) ?: [];
?>
<section class="sc-server-editor" data-sc-server-editor>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Infrastructure</p><h1>Edit proxy</h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="proxies"><i class="fe-chevron-left" aria-hidden="true"></i> Back to proxies</a></div></div>
    <form class="sc-form" action="post.php?action=proxy&amp;referer=proxies" method="post">
        <input type="hidden" name="edit" value="<?php echo (int) ($xtreampiProxy['id'] ?? 0); ?>">
        <section class="sc-form-section"><h2>Proxy details</h2><div class="sc-form-grid">
            <label>Proxy name<input required name="server_name" value="<?php echo $xtreampiValue('server_name'); ?>"></label>
            <label>Public IP address<input required name="server_ip" inputmode="numeric" value="<?php echo $xtreampiValue('server_ip'); ?>"></label>
            <label>Maximum clients<input required min="0" type="number" name="total_clients" value="<?php echo $xtreampiValue('total_clients', '0'); ?>"></label>
        </div><div class="sc-package-options sc-line-switches"><label class="sc-selection-card"><input type="checkbox" name="enabled"<?php echo $xtreampiChecked('enabled'); ?>><span><strong>Enabled</strong><small>Allow this proxy to handle client connections.</small></span></label></div></section>
        <section class="sc-form-section"><h2>Domains and IPs</h2><p class="sc-section-copy">The first entry is the default client address. Add alternatives to choose a different address or randomly distribute clients.</p><div class="sc-server-tag-editor" data-server-tags data-name="domain_name[]"><label>Domains and IP addresses<input type="text" placeholder="Add a domain or IP, then press Enter" data-server-tag-input></label><div class="sc-token-list" data-server-tag-values><?php foreach ($xtreampiDomains as $xtreampiDomain): ?><span><?php echo htmlspecialchars($xtreampiDomain, ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="domain_name[]" value="<?php echo htmlspecialchars($xtreampiDomain, ENT_QUOTES, 'UTF-8'); ?>"><button type="button" aria-label="Remove">×</button></span><?php endforeach; ?></div><p data-server-tag-empty<?php echo $xtreampiDomains ? ' hidden' : ''; ?>>No additional domains or IPs.</p></div><div class="sc-package-options sc-line-switches"><label class="sc-selection-card"><input type="checkbox" name="random_ip"<?php echo $xtreampiChecked('random_ip'); ?>><span><strong>Serve a random domain or IP</strong><small>Choose an address randomly from the configured entries for each client.</small></span></label></div></section>
        <section class="sc-form-section"><h2>Delivery and GeoIP</h2><div class="sc-form-grid">
            <label>HTTP port<input required min="1" max="65535" type="number" name="http_broadcast_port" value="<?php echo $xtreampiValue('http_broadcast_port', '80'); ?>"></label>
            <label>HTTPS port<input required min="1" max="65535" type="number" name="https_broadcast_port" value="<?php echo $xtreampiValue('https_broadcast_port', '443'); ?>"></label>
            <label>Guaranteed network speed (Mbps)<input required min="0" type="number" name="network_guaranteed_speed" value="<?php echo $xtreampiValue('network_guaranteed_speed', '0'); ?>"></label>
            <label>GeoIP priority<select name="geoip_type"><?php foreach (['high_priority' => 'High priority', 'low_priority' => 'Low priority', 'strict' => 'Strict'] as $xtreampiOption => $xtreampiLabel): ?><option value="<?php echo $xtreampiOption; ?>"<?php echo ($xtreampiProxy['geoip_type'] ?? '') === $xtreampiOption ? ' selected' : ''; ?>><?php echo $xtreampiLabel; ?></option><?php endforeach; ?></select></label>
            <label class="sc-form-span">GeoIP countries<select name="geoip_countries[]" multiple size="7"><?php foreach ($xtreampiCountries as $xtreampiCountry): ?><option value="<?php echo htmlspecialchars((string) $xtreampiCountry['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($xtreampiCountry['id'], $xtreampiSelectedCountries, true) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $xtreampiCountry['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        </div><div class="sc-package-options sc-line-switches"><label class="sc-selection-card"><input type="checkbox" name="enable_https"<?php echo $xtreampiChecked('enable_https'); ?>><span><strong>Enable HTTPS</strong><small>Allow proxy connections over TLS.</small></span></label><label class="sc-selection-card"><input type="checkbox" name="enable_geoip"<?php echo $xtreampiChecked('enable_geoip'); ?>><span><strong>Enable GeoIP load balancing</strong><small>Use the selected countries to route connections to this proxy.</small></span></label></div></section>
        <div class="sc-form-error" data-sc-server-error role="alert" hidden></div><div class="sc-form-actions"><?php if ($xtreampiCanEdit): ?><button class="sc-button sc-button-primary" type="submit">Save proxy</button><?php endif; ?><a class="sc-button sc-button-secondary" href="proxies">Cancel</a></div>
    </form>
</section>

<?php
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Line\LineRepository;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\User\UserRepository;
$xtreampiPageScripts = ['assets/xtreampi/line.js'];
$xtreampiLine = is_array($rLine ?? null) ? $rLine : [];
$xtreampiEditing = intval($xtreampiLine['id'] ?? 0) > 0;
$xtreampiOwnerId = intval($xtreampiLine['member_id'] ?? ($rUserInfo['id'] ?? 0));
$xtreampiOwner = UserRepository::getRegisteredUserById($xtreampiOwnerId);
$xtreampiServers = ServerRepository::getAllSimple();
$xtreampiOutputs = LineRepository::getOutputFormats();
$xtreampiBouquets = BouquetService::getAllSimple();
$xtreampiSelectedOutputs = array_map('intval', json_decode((string) ($xtreampiLine['allowed_outputs'] ?? '[]'), true) ?: []);
$xtreampiSelectedBouquets = array_map('intval', json_decode((string) ($xtreampiLine['bouquet'] ?? '[]'), true) ?: []);
$xtreampiAllowedIps = json_decode((string) ($xtreampiLine['allowed_ips'] ?? '[]'), true) ?: [];
$xtreampiAllowedUa = json_decode((string) ($xtreampiLine['allowed_ua'] ?? '[]'), true) ?: [];
$xtreampiValue = static fn(string $key, string $default = ''): string => htmlspecialchars((string) ($xtreampiLine[$key] ?? $default), ENT_QUOTES, 'UTF-8');
$xtreampiChecked = static fn(string $key): string => !empty($xtreampiLine[$key]) ? ' checked' : '';
$xtreampiExpiry = !empty($xtreampiLine['exp_date']) ? date('Y-m-d\TH:i', intval($xtreampiLine['exp_date'])) : date('Y-m-d\TH:i', time() + 2592000);
?>
<section class="sc-line-editor">
 <div class="sc-page-heading"><div><p class="sc-eyebrow">Subscriber Access</p><h1><?php echo $xtreampiEditing ? 'Edit Subscription' : 'Add Subscription'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="lines"><i class="fe-chevron-left"></i> Back to subscriptions</a></div></div>
 <form class="sc-form sc-line-form" action="post.php?action=line&amp;referer=lines" method="post" enctype="multipart/form-data" data-status-invalid-date="<?php echo intval(STATUS_INVALID_DATE); ?>" data-status-existing-username="<?php echo intval(STATUS_EXISTS_USERNAME); ?>">
  <?php if ($xtreampiEditing): ?><input type="hidden" name="edit" value="<?php echo intval($xtreampiLine['id']); ?>"><?php endif; ?><input type="hidden" name="bouquets_selected" value="[]">
  <section class="sc-form-section"><h2>Account Details</h2><div class="sc-form-grid">
   <label>Username<input type="text" name="username" autocomplete="off" pattern="[A-Za-z0-9@._-]*" placeholder="Generated automatically if blank" value="<?php echo $xtreampiValue('username'); ?>"></label>
   <label>Password<input type="text" name="password" autocomplete="new-password" pattern="[A-Za-z0-9@._-]*" placeholder="Generated automatically if blank" value="<?php echo $xtreampiValue('password'); ?>"></label>
   <label class="sc-form-span">Owner<div class="sc-line-owner" data-sc-line-owner><input type="search" value="<?php echo htmlspecialchars((string) ($xtreampiOwner['username'] ?? $rUserInfo['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search for an owner" autocomplete="off" data-sc-owner-search><input type="hidden" name="member_id" value="<?php echo $xtreampiOwnerId; ?>"><button type="button" data-sc-owner-clear>Clear</button><div class="sc-owner-options" data-sc-owner-options hidden></div></div></label>
   <label>Expiration<input type="datetime-local" name="exp_date" value="<?php echo $xtreampiExpiry; ?>"<?php echo array_key_exists('exp_date', $xtreampiLine) && $xtreampiLine['exp_date'] === null ? ' disabled' : ''; ?>></label>
   <label class="sc-check sc-line-check"><input type="checkbox" name="no_expire" data-sc-no-expire<?php echo array_key_exists('exp_date', $xtreampiLine) && $xtreampiLine['exp_date'] === null ? ' checked' : ''; ?>><span>Never expires</span></label>
   <label>Maximum Connections<input type="number" min="0" name="max_connections" required value="<?php echo $xtreampiValue('max_connections', '1'); ?>"></label>
   <label>WhatsApp<input type="tel" name="contact" placeholder="+491234567890" value="<?php echo $xtreampiValue('contact'); ?>"></label>
   <label>Admin Notes<textarea name="admin_notes" rows="3"><?php echo $xtreampiValue('admin_notes'); ?></textarea></label>
   <label>Reseller Notes<textarea name="reseller_notes" rows="3"><?php echo $xtreampiValue('reseller_notes'); ?></textarea></label>
  </div></section>
  <section class="sc-form-section"><h2>Connection Options</h2><div class="sc-package-options sc-line-switches"><?php foreach ([['is_stalker','Ministra portal'],['is_restreamer','Restreamer'],['is_trial','Trial account'],['is_isplock','Lock to ISP'],['bypass_ua','Bypass user-agent restrictions']] as [$key,$label]): ?><label class="sc-check"><input type="checkbox" name="<?php echo $key; ?>"<?php echo $xtreampiChecked($key); ?>><span><?php echo $label; ?></span></label><?php endforeach; ?></div><div class="sc-form-grid">
   <label>Forced Connection<select name="force_server_id"><option value="0">Disabled</option><?php foreach ($xtreampiServers as $server): ?><option value="<?php echo intval($server['id']); ?>"<?php echo intval($xtreampiLine['force_server_id'] ?? 0) === intval($server['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
   <label>Forced Country<select name="forced_country"><?php foreach (($rCountries ?? []) as $country): ?><option value="<?php echo htmlspecialchars((string) $country['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) ($xtreampiLine['forced_country'] ?? '') === (string) $country['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $country['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
   <label>Current ISP<div class="sc-input-action"><input type="text" readonly name="isp_clear" value="<?php echo $xtreampiValue('isp_desc'); ?>"><button type="button" data-sc-clear-input="isp_clear">Clear</button></div></label>
   <label>Access Token<div class="sc-input-action"><input type="text" readonly name="access_token" value="<?php echo $xtreampiValue('access_token'); ?>"><button type="button" data-sc-token-generate>Generate</button><button type="button" data-sc-clear-input="access_token">Clear</button></div></label>
   <fieldset class="sc-package-output sc-form-span"><legend>Output Formats</legend><?php foreach ($xtreampiOutputs as $output): ?><label class="sc-check"><input type="checkbox" name="access_output[]" value="<?php echo intval($output['access_output_id']); ?>"<?php echo (!$xtreampiEditing || in_array(intval($output['access_output_id']), $xtreampiSelectedOutputs, true)) ? ' checked' : ''; ?>><span><?php echo htmlspecialchars((string) $output['output_name'], ENT_QUOTES, 'UTF-8'); ?></span></label><?php endforeach; ?></fieldset>
  </div></section>
  <section class="sc-form-section"><h2>Client Restrictions</h2><div class="sc-restriction-grid">
   <div class="sc-restriction-editor" data-sc-list-editor="allowed_ips"><label>Allowed IP Addresses<div class="sc-input-action"><input type="text" data-sc-list-input placeholder="IPv4, IPv6, or CIDR"><button type="button" data-sc-list-add>Add</button></div></label><div class="sc-token-list" data-sc-list-values><?php foreach ($xtreampiAllowedIps as $value): ?><span><?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="allowed_ips[]" value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>"><button type="button" aria-label="Remove">×</button></span><?php endforeach; ?></div><p data-sc-list-empty<?php echo $xtreampiAllowedIps ? ' hidden' : ''; ?>>Any IP address is allowed.</p></div>
   <div class="sc-restriction-editor" data-sc-list-editor="allowed_ua"><label>Allowed User Agents<div class="sc-input-action"><input type="text" data-sc-list-input placeholder="User-agent value"><button type="button" data-sc-list-add>Add</button></div></label><div class="sc-token-list" data-sc-list-values><?php foreach ($xtreampiAllowedUa as $value): ?><span><?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="allowed_ua[]" value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>"><button type="button" aria-label="Remove">×</button></span><?php endforeach; ?></div><p data-sc-list-empty<?php echo $xtreampiAllowedUa ? ' hidden' : ''; ?>>Any user agent is allowed.</p></div>
  </div></section>
  <section class="sc-form-section"><div class="sc-section-heading"><h2>Bouquets</h2><button class="sc-button sc-button-secondary" type="button" data-sc-toggle-bouquets>Toggle all</button></div><div class="sc-selection-grid" data-sc-line-bouquets><?php foreach ($xtreampiBouquets as $bouquet): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo intval($bouquet['id']); ?>"<?php echo in_array(intval($bouquet['id']), $xtreampiSelectedBouquets, true) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string) $bouquet['bouquet_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo count(json_decode((string) $bouquet['bouquet_channels'], true) ?: []); ?> live · <?php echo count(json_decode((string) $bouquet['bouquet_movies'], true) ?: []); ?> movies · <?php echo count(json_decode((string) $bouquet['bouquet_series'], true) ?: []); ?> series · <?php echo count(json_decode((string) $bouquet['bouquet_radios'], true) ?: []); ?> radio</small></span></label><?php endforeach; ?></div></section>
  <div class="sc-form-error" data-sc-line-error role="alert" hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_line" value="<?php echo $xtreampiEditing ? 'Edit' : 'Add'; ?>"><?php echo $xtreampiEditing ? 'Save Subscription' : 'Add Subscription'; ?></button><a class="sc-button sc-button-secondary" href="lines">Cancel</a></div>
 </form>
</section>

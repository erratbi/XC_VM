<?php
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\User\UserRepository;

$streamcreedPageScripts = ['assets/streamcreed/mag.js'];
$streamcreedMag = is_array($rDevice ?? null) ? $rDevice : [];
$streamcreedLine = is_array($streamcreedMag['user'] ?? null) ? $streamcreedMag['user'] : [];
$streamcreedEditing = intval($streamcreedMag['mag_id'] ?? 0) > 0;
$streamcreedOwnerId = intval($streamcreedLine['member_id'] ?? ($rUserInfo['id'] ?? 0));
$streamcreedOwner = UserRepository::getRegisteredUserById($streamcreedOwnerId);
$streamcreedBouquets = BouquetService::getAllSimple();
$streamcreedSelectedBouquets = array_map('intval', json_decode((string) ($streamcreedLine['bouquet'] ?? '[]'), true) ?: []);
$streamcreedAllowedIps = json_decode((string) ($streamcreedLine['allowed_ips'] ?? '[]'), true) ?: [];
$streamcreedServers = ServerRepository::getAllSimple();
$streamcreedExpiry = !empty($streamcreedLine['exp_date']) ? date('Y-m-d\TH:i', intval($streamcreedLine['exp_date'])) : date('Y-m-d\TH:i', time() + 2592000);
$streamcreedValue = static fn(array $source, string $key, string $default = ''): string => htmlspecialchars((string) ($source[$key] ?? $default), ENT_QUOTES, 'UTF-8');
?>
<section class="sc-line-editor" data-sc-mag-editor>
 <div class="sc-page-heading"><div><p class="sc-eyebrow">Device Management</p><h1><?php echo $streamcreedEditing ? 'Edit MAG Device' : 'Add MAG Device'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="mags"><i class="fe-chevron-left"></i> Back to MAG devices</a></div></div>
 <form class="sc-form sc-line-form" action="post.php?action=mag&amp;referer=mags" method="post" enctype="multipart/form-data" data-status-invalid-mac="<?php echo intval(STATUS_INVALID_MAC); ?>" data-status-existing-mac="<?php echo intval(STATUS_EXISTS_MAC); ?>" data-status-invalid-date="<?php echo intval(STATUS_INVALID_DATE); ?>">
  <?php if ($streamcreedEditing): ?><input type="hidden" name="edit" value="<?php echo intval($streamcreedMag['mag_id']); ?>"><?php endif; ?><input type="hidden" name="bouquets_selected" value="[]">
  <section class="sc-form-section"><h2>Device &amp; Account</h2><div class="sc-form-grid">
   <label>MAC Address<input type="text" name="mac" required autocomplete="off" inputmode="text" maxlength="17" pattern="[0-9A-Fa-f]{2}(?::[0-9A-Fa-f]{2}){5}" placeholder="00:1A:79:00:00:00" value="<?php echo $streamcreedValue($streamcreedMag, 'mac', '00:1A:79:'); ?>" data-sc-mac-input></label>
   <label>Adult PIN<input type="text" name="parent_password" inputmode="numeric" maxlength="4" value="<?php echo $streamcreedValue($streamcreedMag, 'parent_password', '0000'); ?>"></label>
   <label class="sc-form-span">Owner<div class="sc-line-owner" data-sc-line-owner><input type="search" value="<?php echo htmlspecialchars((string) ($streamcreedOwner['username'] ?? $rUserInfo['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search for an owner" autocomplete="off" data-sc-owner-search><input type="hidden" name="member_id" value="<?php echo $streamcreedOwnerId; ?>"><button type="button" data-sc-owner-clear>Clear</button><div class="sc-owner-options" data-sc-owner-options hidden></div></div></label>
   <label>Expiration<input type="datetime-local" name="exp_date" value="<?php echo $streamcreedExpiry; ?>"<?php echo array_key_exists('exp_date', $streamcreedLine) && $streamcreedLine['exp_date'] === null ? ' disabled' : ''; ?>></label>
   <label class="sc-check sc-line-check"><input type="checkbox" name="no_expire" data-sc-no-expire<?php echo array_key_exists('exp_date', $streamcreedLine) && $streamcreedLine['exp_date'] === null ? ' checked' : ''; ?>><span>Never expires</span></label>
   <label>Admin Notes<textarea name="admin_notes" rows="3"><?php echo $streamcreedValue($streamcreedLine, 'admin_notes'); ?></textarea></label>
   <label>Reseller Notes<textarea name="reseller_notes" rows="3"><?php echo $streamcreedValue($streamcreedLine, 'reseller_notes'); ?></textarea></label>
  </div><div class="sc-mag-policies"><h3>Device Policies</h3><div class="sc-package-options sc-line-switches">
   <label class="sc-selection-card"><input type="checkbox" name="is_trial"<?php echo !empty($streamcreedLine['is_trial']) ? ' checked' : ''; ?>><span><strong>Trial device</strong><small>Mark this device as a trial account.</small></span></label>
   <label class="sc-selection-card"><input type="checkbox" name="is_isplock"<?php echo !empty($streamcreedLine['is_isplock']) ? ' checked' : ''; ?>><span><strong>Lock to ISP</strong><small>Restrict access to the detected provider.</small></span></label>
   <label class="sc-selection-card"><input type="checkbox" name="lock_device"<?php echo !$streamcreedEditing || !empty($streamcreedMag['lock_device']) ? ' checked' : ''; ?>><span><strong>Lock device identity</strong><small>Prevent the device identifiers from changing.</small></span></label>
  </div></div></section>
  <?php if ($streamcreedEditing): ?><section class="sc-form-section"><h2>Reported Device Information</h2><div class="sc-form-grid">
   <label>Line Username<input type="text" name="username" value="<?php echo $streamcreedValue($streamcreedLine, 'username'); ?>"></label><label>Line Password<input type="text" name="password" value="<?php echo $streamcreedValue($streamcreedLine, 'password'); ?>"></label>
   <?php foreach ([['sn','Serial Number'],['stb_type','STB Type'],['image_version','Image Version'],['hw_version','Hardware Version'],['device_id','Primary Device ID'],['device_id2','Secondary Device ID'],['ver','Firmware Version']] as [$key,$label]): ?><label><?php echo $label; ?><input type="text" name="<?php echo $key; ?>" value="<?php echo $streamcreedValue($streamcreedMag, $key); ?>"></label><?php endforeach; ?>
  </div></section><?php endif; ?>
  <section class="sc-form-section"><h2>Connection Options</h2><div class="sc-form-grid">
   <label>Forced Server<select name="force_server_id"><option value="0">Disabled</option><?php foreach ($streamcreedServers as $server): ?><option value="<?php echo intval($server['id']); ?>"<?php echo intval($streamcreedLine['force_server_id'] ?? 0) === intval($server['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
   <label>Forced Country<select name="forced_country"><option value="">Disabled</option><?php foreach (($rCountries ?? []) as $country): ?><option value="<?php echo htmlspecialchars((string) $country['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) ($streamcreedLine['forced_country'] ?? '') === (string) $country['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $country['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
   <label>Current ISP<div class="sc-input-action"><input type="text" readonly name="isp_clear" value="<?php echo $streamcreedValue($streamcreedLine, 'isp_desc'); ?>"><button type="button" data-sc-clear-isp>Clear</button></div></label>
   <div class="sc-restriction-editor" data-sc-list-editor><label>Allowed IP Addresses<div class="sc-input-action"><input type="text" data-sc-list-input placeholder="IPv4, IPv6, or CIDR"><button type="button" data-sc-list-add>Add</button></div></label><div class="sc-token-list" data-sc-list-values><?php foreach ($streamcreedAllowedIps as $ip): ?><span><?php echo htmlspecialchars((string) $ip, ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="allowed_ips[]" value="<?php echo htmlspecialchars((string) $ip, ENT_QUOTES, 'UTF-8'); ?>"><button type="button" aria-label="Remove">×</button></span><?php endforeach; ?></div></div>
  </div></section>
  <section class="sc-form-section"><div class="sc-section-heading"><h2>Bouquets</h2><button class="sc-button sc-button-secondary" type="button" data-sc-toggle-bouquets>Toggle all</button></div><div class="sc-selection-grid" data-sc-mag-bouquets><?php foreach ($streamcreedBouquets as $bouquet): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo intval($bouquet['id']); ?>"<?php echo in_array(intval($bouquet['id']), $streamcreedSelectedBouquets, true) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string) $bouquet['bouquet_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo count(json_decode((string) $bouquet['bouquet_channels'], true) ?: []); ?> live · <?php echo count(json_decode((string) $bouquet['bouquet_movies'], true) ?: []); ?> movies · <?php echo count(json_decode((string) $bouquet['bouquet_series'], true) ?: []); ?> series</small></span></label><?php endforeach; ?></div></section>
  <div class="sc-form-error" data-sc-mag-error role="alert" hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_device" value="<?php echo $streamcreedEditing ? 'Edit' : 'Add'; ?>"><?php echo $streamcreedEditing ? 'Save Device' : 'Add Device'; ?></button><a class="sc-button sc-button-secondary" href="mags">Cancel</a></div>
 </form>
</section>

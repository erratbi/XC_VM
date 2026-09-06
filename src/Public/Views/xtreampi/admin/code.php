<?php
use XcVm\Domain\User\GroupService;
require_once __DIR__ . '/_migration_helpers.php';
$row = is_array($rCode ?? null) ? $rCode : [];
$groups = json_decode((string) ($row['groups'] ?? '[]'), true); $groups = is_array($groups) ? $groups : [];
$whitelist = json_decode((string) ($row['whitelist'] ?? '[]'), true); $whitelist = is_array($whitelist) ? $whitelist : [];
$types = [0 => 'Admin', 1 => 'Reseller', 2 => 'Ministra', 3 => 'Admin API', 4 => 'Reseller API', 6 => 'Web Player'];
?>
<section class="sc-migration-editor"><div class="sc-page-heading"><div><p class="sc-eyebrow">Access control</p><h1><?php echo $row ? 'Edit access code' : 'Add access code'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="codes">Back to codes</a></div></div>
<form class="sc-form" action="post.php?action=code&amp;referer=codes" method="post">
 <?php if ($row): ?><input type="hidden" name="edit" value="<?php echo (int) ($row['id'] ?? 0); ?>"><?php endif; ?><section class="sc-form-section"><div class="sc-form-grid"><label>Access code<span class="sc-input-action"><input name="code" maxlength="16" required data-sc-code value="<?php echo sc_m_escape($row['code'] ?? ''); ?>"><button class="sc-button sc-button-secondary" type="button" data-sc-generate-code aria-label="Generate access code">Generate</button></span></label><label>Access type<select name="type"><?php foreach ($types as $index => $label): ?><option value="<?php echo $index; ?>"<?php echo (int) ($row['type'] ?? 0) === $index ? ' selected' : ''; ?>><?php echo sc_m_escape($label); ?></option><?php endforeach; ?></select></label><label class="sc-check"><input type="checkbox" name="enabled" value="1"<?php echo (!$row || !empty($row['enabled'])) ? ' checked' : ''; ?>><span>Enabled</span></label></div></section>
 <section class="sc-form-section"><h2>Groups</h2><div class="sc-selection-grid"><?php foreach (GroupService::getAll() as $group): ?><label class="sc-selection-card"><input type="checkbox" name="groups[]" value="<?php echo (int) $group['group_id']; ?>"<?php echo in_array((int) $group['group_id'], array_map('intval', $groups), true) ? ' checked' : ''; ?>><span><?php echo sc_m_escape($group['group_name']); ?></span></label><?php endforeach; ?></div></section>
 <section class="sc-form-section"><h2>Restrictions</h2><label>Allowed IP addresses (one per line)<textarea name="whitelist[]" rows="4"><?php echo sc_m_escape(implode("\n", array_map('strval', $whitelist))); ?></textarea></label><p class="sc-section-copy">The legacy handler accepts the <code>whitelist[]</code> field; values are passed unchanged.</p></section><div class="sc-form-error" data-sc-form-error hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit" value="Save">Save code</button><a class="sc-button sc-button-secondary" href="codes">Cancel</a></div>
</form></section>
<?php $xtreampiPageScripts = ['assets/xtreampi/migration.js']; ?>

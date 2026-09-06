<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/server-order.js'];
$xtreampiCanOrder = Authorization::check('adv', 'server_order');
$xtreampiServers = is_array($rOrderedServers ?? null) ? $rOrderedServers : [];
?>
<section class="sc-server-order" data-sc-server-order>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Infrastructure</p><h1>Server order</h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="servers"><i class="fe-chevron-left" aria-hidden="true"></i> Back to servers</a></div></div>
    <form class="sc-form" action="post.php?action=server_order&amp;referer=servers" method="post"><input type="hidden" name="server_order" value="[]">
        <section class="sc-form-section"><h2>Connection allocation priority</h2><p class="sc-section-copy">Drag servers into priority order. Offline servers are still automatically moved to the end when clients are allocated.</p><ol class="sc-sort-list" data-sc-server-order-list><?php foreach ($xtreampiServers as $xtreampiServer): ?><li draggable="true" data-server-id="<?php echo (int) $xtreampiServer['id']; ?>"><span class="sc-sort-handle" title="Drag to reorder"><i class="fe-menu" aria-hidden="true"></i></span><div><strong><?php echo htmlspecialchars((string) $xtreampiServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small>#<?php echo (int) $xtreampiServer['id']; ?> · <?php echo !empty($xtreampiServer['server_online']) ? 'Online' : 'Offline'; ?></small></div><span data-sc-server-order-position></span></li><?php endforeach; ?></ol></section>
        <div class="sc-form-error" data-sc-server-order-error hidden></div><div class="sc-form-actions"><?php if ($xtreampiCanOrder): ?><button class="sc-button sc-button-primary" type="submit">Save order</button><?php endif; ?><a class="sc-button sc-button-secondary" href="servers">Cancel</a></div>
    </form>
</section>

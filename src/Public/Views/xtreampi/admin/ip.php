<?php
use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/ip.js'];
$xtreampiInvalidIpStatus = defined('STATUS_INVALID_IP') ? (int) STATUS_INVALID_IP : '';
$xtreampiCanManageIPs = Authorization::check('adv', 'block_ips');
?>
<section class="sc-group-editor" data-sc-ip-editor data-can-manage="<?php echo $xtreampiCanManageIPs ? '1' : '0'; ?>">
    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Access security</p>
            <h1>Block an IP address</h1>
            <p>Add an IPv4, IPv6, or CIDR address to the shared block list.</p>
        </div>
        <div class="sc-page-actions">
            <a class="sc-button sc-button-secondary" href="ips"><i class="fe-chevron-left" aria-hidden="true"></i> Back to blocked IPs</a>
        </div>
    </div>

    <form class="sc-form sc-group-form" action="post.php?action=ip&amp;referer=ips" method="post" data-status-invalid-ip="<?php echo $xtreampiInvalidIpStatus; ?>">
        <section class="sc-form-section">
            <h2>Block details</h2>
            <p class="sc-section-copy">The address is checked by the existing blocklist service before it is saved and propagated.</p>
            <div class="sc-form-grid">
                <label>
                    IP address or CIDR
                    <input type="text" name="ip" required autocomplete="off" inputmode="url" placeholder="203.0.113.10 or 2001:db8::/32">
                </label>
                <label>
                    Notes
                    <textarea name="notes" required autocomplete="off" placeholder="Why should this address be blocked?"></textarea>
                </label>
            </div>
        </section>
        <p class="sc-form-error" data-sc-ip-error role="alert" hidden></p>
        <div class="sc-form-actions">
            <?php if ($xtreampiCanManageIPs): ?>
                <button class="sc-button sc-button-primary" type="submit" name="submit_ip" value="Block">Block IP</button>
            <?php else: ?>
                <span class="sc-toolbar-note">You do not have permission to change the block list.</span>
            <?php endif; ?>
            <a class="sc-button sc-button-secondary" href="ips">Cancel</a>
        </div>
    </form>
</section>

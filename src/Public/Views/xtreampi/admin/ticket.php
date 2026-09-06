<?php
require_once __DIR__ . '/_migration_helpers.php';
$ticket = is_array($rTicket ?? null) ? $rTicket : [];
?>
<section class="sc-ticket-editor"><div class="sc-page-heading"><div><p class="sc-eyebrow">Support</p><h1>Reply to <?php echo sc_m_escape($ticket['title'] ?? ('Ticket #' . ($ticket['id'] ?? ''))); ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="ticket_view?id=<?php echo (int) ($ticket['id'] ?? 0); ?>">View thread</a></div></div><form class="sc-form" action="post.php?action=ticket&amp;referer=ticket_view" method="post"><input type="hidden" name="respond" value="<?php echo (int) ($ticket['id'] ?? 0); ?>"><section class="sc-form-section"><h2>Message</h2><label><span class="sc-visually-hidden">Message</span><textarea name="message" rows="8" required></textarea></label></section><div class="sc-form-error" data-sc-form-error hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_ticket" value="Create">Send reply</button><a class="sc-button sc-button-secondary" href="tickets">Cancel</a></div></form></section>
<?php $xtreampiPageScripts = ['assets/xtreampi/migration.js']; ?>

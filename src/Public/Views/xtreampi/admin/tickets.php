<?php

$xtreampiPageScripts = ['assets/xtreampi/tickets.js'];
$xtreampiStatusLabels = $rStatusArray ?? ['CLOSED', 'OPEN', 'RESPONDED TO', 'READ BY USER', 'NEW RESPONSE', 'READ BY ME', 'READ BY USER'];
$xtreampiTicketRepository = 'XcVm' . chr(92) . 'Domain' . chr(92) . 'User' . chr(92) . 'TicketRepository';
$xtreampiAuthorization = 'XcVm' . chr(92) . 'Core' . chr(92) . 'Auth' . chr(92) . 'Authorization';
$xtreampiTickets = $xtreampiTicketRepository::getAll($rUserInfo['id'], true);
$xtreampiCanManageTickets = $xtreampiAuthorization::check('adv', 'ticket');
?>
<section class="sc-tickets" data-sc-tickets>
	<div class="sc-page-heading"><div><p class="sc-eyebrow">Support</p><h1>Tickets</h1></div></div>
	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search tickets</span><input type="search" placeholder="Search ticket, user, or title" data-sc-ticket-search></label>
		<label class="sc-filter-field"><span>Status</span><select data-sc-ticket-filter><option value="all">All tickets</option><?php foreach ($xtreampiStatusLabels as $xtreampiStatusId => $xtreampiStatusLabel): ?><option value="<?php echo intval($xtreampiStatusId); ?>"><?php echo htmlspecialchars((string) $xtreampiStatusLabel, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
	</div>
	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Created</th><th>Last reply</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-ticket-rows>
		<?php foreach ($xtreampiTickets as $xtreampiTicket): ?>
			<?php $xtreampiId = intval($xtreampiTicket['id'] ?? 0); $xtreampiStatus = intval($xtreampiTicket['status'] ?? 0); $xtreampiTitle = trim((string) ($xtreampiTicket['title'] ?? 'Untitled ticket')); $xtreampiUser = trim((string) ($xtreampiTicket['username'] ?? '—')); ?>
			<tr data-sc-ticket-row data-status="<?php echo $xtreampiStatus; ?>" data-search="<?php echo htmlspecialchars(strtolower($xtreampiId . ' ' . $xtreampiTitle . ' ' . $xtreampiUser), ENT_QUOTES, 'UTF-8'); ?>"><td><div class="sc-table-identity"><a href="ticket_view?id=<?php echo $xtreampiId; ?>"><?php echo htmlspecialchars($xtreampiTitle, ENT_QUOTES, 'UTF-8'); ?></a><small>#<?php echo $xtreampiId; ?></small></div></td><td class="sc-table-secondary"><?php echo htmlspecialchars($xtreampiUser, ENT_QUOTES, 'UTF-8'); ?></td><td><span class="sc-row-status <?php echo $xtreampiStatus === 0 ? 'is-disabled' : 'is-active'; ?>"><?php echo htmlspecialchars((string) ($xtreampiStatusLabels[$xtreampiStatus] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></span></td><td class="sc-table-secondary"><?php echo htmlspecialchars((string) ($xtreampiTicket['created'] ?? $xtreampiTicket['created_date'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td><td class="sc-table-secondary"><?php echo htmlspecialchars((string) ($xtreampiTicket['last_reply'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td><td class="sc-table-actions"><a class="sc-row-action" href="ticket_view?id=<?php echo $xtreampiId; ?>">View</a></td></tr>
		<?php endforeach; ?>
		<tr data-sc-ticket-empty<?php echo $xtreampiTickets ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="6">No support tickets have been created yet.</td></tr>
	</tbody></table></div></div>
</section>

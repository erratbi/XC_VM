<?php

$streamcreedPageScripts = ['assets/streamcreed/tickets.js'];
$streamcreedStatusLabels = $rStatusArray ?? ['CLOSED', 'OPEN', 'RESPONDED TO', 'READ BY USER', 'NEW RESPONSE', 'READ BY ME', 'READ BY USER'];
$streamcreedTicketRepository = 'XcVm' . chr(92) . 'Domain' . chr(92) . 'User' . chr(92) . 'TicketRepository';
$streamcreedAuthorization = 'XcVm' . chr(92) . 'Core' . chr(92) . 'Auth' . chr(92) . 'Authorization';
$streamcreedTickets = $streamcreedTicketRepository::getAll($rUserInfo['id'], true);
$streamcreedCanManageTickets = $streamcreedAuthorization::check('adv', 'ticket');
?>
<section class="sc-tickets" data-sc-tickets>
	<div class="sc-page-heading"><div><p class="sc-eyebrow">Support</p><h1>Tickets</h1></div></div>
	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search tickets</span><input type="search" placeholder="Search ticket, user, or title" data-sc-ticket-search></label>
		<label class="sc-filter-field"><span>Status</span><select data-sc-ticket-filter><option value="all">All tickets</option><?php foreach ($streamcreedStatusLabels as $streamcreedStatusId => $streamcreedStatusLabel): ?><option value="<?php echo intval($streamcreedStatusId); ?>"><?php echo htmlspecialchars((string) $streamcreedStatusLabel, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
	</div>
	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Created</th><th>Last reply</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-ticket-rows>
		<?php foreach ($streamcreedTickets as $streamcreedTicket): ?>
			<?php $streamcreedId = intval($streamcreedTicket['id'] ?? 0); $streamcreedStatus = intval($streamcreedTicket['status'] ?? 0); $streamcreedTitle = trim((string) ($streamcreedTicket['title'] ?? 'Untitled ticket')); $streamcreedUser = trim((string) ($streamcreedTicket['username'] ?? '—')); ?>
			<tr data-sc-ticket-row data-status="<?php echo $streamcreedStatus; ?>" data-search="<?php echo htmlspecialchars(strtolower($streamcreedId . ' ' . $streamcreedTitle . ' ' . $streamcreedUser), ENT_QUOTES, 'UTF-8'); ?>"><td><div class="sc-table-identity"><a href="ticket_view?id=<?php echo $streamcreedId; ?>"><?php echo htmlspecialchars($streamcreedTitle, ENT_QUOTES, 'UTF-8'); ?></a><small>#<?php echo $streamcreedId; ?></small></div></td><td class="sc-table-secondary"><?php echo htmlspecialchars($streamcreedUser, ENT_QUOTES, 'UTF-8'); ?></td><td><span class="sc-row-status <?php echo $streamcreedStatus === 0 ? 'is-disabled' : 'is-active'; ?>"><?php echo htmlspecialchars((string) ($streamcreedStatusLabels[$streamcreedStatus] ?? 'Unknown'), ENT_QUOTES, 'UTF-8'); ?></span></td><td class="sc-table-secondary"><?php echo htmlspecialchars((string) ($streamcreedTicket['created'] ?? $streamcreedTicket['created_date'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td><td class="sc-table-secondary"><?php echo htmlspecialchars((string) ($streamcreedTicket['last_reply'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td><td class="sc-table-actions"><a class="sc-row-action" href="ticket_view?id=<?php echo $streamcreedId; ?>">View</a></td></tr>
		<?php endforeach; ?>
		<tr data-sc-ticket-empty<?php echo $streamcreedTickets ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="6">No support tickets have been created yet.</td></tr>
	</tbody></table></div></div>
</section>

			</main>
			</div>
			<footer class="sc-footer">
				<span><?php echo htmlspecialchars($streamcreedServerName, ENT_QUOTES, 'UTF-8'); ?></span>
				<span>XC_VM v<?php echo htmlspecialchars((string) XC_VM_VERSION, ENT_QUOTES, 'UTF-8'); ?></span>
			</footer>
		</div>
	</div>
	<script>window.streamcreedTranslations=<?php echo json_encode($streamcreedTranslationMap ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;</script>
	<script src="assets/streamcreed/translate.js"></script>
	<script src="assets/streamcreed/streamcreed.js"></script>
	<?php foreach (($streamcreedPageScripts ?? []) as $streamcreedScript): ?>
		<script src="<?php echo htmlspecialchars($streamcreedScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
	<?php endforeach; ?>
</body>
</html>

			</main>
			</div>
			<footer class="sc-footer">
				<span><?php echo htmlspecialchars($xtreampiServerName, ENT_QUOTES, 'UTF-8'); ?></span>
				<span>XtreamPi v<?php echo htmlspecialchars((string) XC_VM_VERSION, ENT_QUOTES, 'UTF-8'); ?></span>
			</footer>
		</div>
	</div>
	<script>window.xtreampiTranslations=<?php echo json_encode($xtreampiTranslationMap ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;</script>
	<script src="assets/xtreampi/translate.js"></script>
	<script src="assets/xtreampi/xtreampi.js"></script>
	<?php foreach (($xtreampiPageScripts ?? []) as $xtreampiScript): ?>
		<script src="<?php echo htmlspecialchars($xtreampiScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
	<?php endforeach; ?>
</body>
</html>

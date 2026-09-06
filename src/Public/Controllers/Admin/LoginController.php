<?php

namespace XcVm\Public\Controllers\Admin;

use XcVm\Core\Ui\AdminUiTheme;

/**
 * LoginController — Страница авторизации admin-панели.
 *
 * Login имеет собственный HTML-документ (не использует layout header/footer).
 * Файл admin/login.php содержит полный bootstrap через functions.php.
 * Контроллер делегирует напрямую в legacy-файл.
 *
 * @renders public/Views/admin/login.php
 *
 * @package XC_VM_Public_Controllers_Admin
 * @author  Divarion_D <https://github.com/Divarion-D>
 * @copyright 2025-2026 Vateron Media
 * @link    https://github.com/Vateron-Media/XC_VM
 * @license AGPL-3.0 https://www.gnu.org/licenses/agpl-3.0.html
 */

class LoginController extends BaseAdminController {
	public function index() {
		$adminUi = AdminUiTheme::resolve($_GET, $_COOKIE);
		$requestedAdminUi = AdminUiTheme::requested($_GET);
		if ($requestedAdminUi !== null) {
			$this->persistAdminUiTheme($requestedAdminUi);
		}

		@chdir(MAIN_HOME . 'Public/Views/admin/');
		if ($adminUi !== AdminUiTheme::XTREAMPI) {
			require MAIN_HOME . 'Public/Views/admin/login.php';
			return;
		}

		ob_start();
		require MAIN_HOME . 'Public/Views/admin/login.php';
		if (!is_string(ob_get_clean())) {
			return;
		}

		require MAIN_HOME . 'Public/Views/xtreampi/admin/login.php';
	}

	private function persistAdminUiTheme(string $theme): void {
		if (headers_sent()) {
			return;
		}

		setcookie(AdminUiTheme::COOKIE_NAME, $theme, [
			'expires' => time() + 31536000,
			'path' => '/',
			'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		$_COOKIE[AdminUiTheme::COOKIE_NAME] = $theme;
	}
}

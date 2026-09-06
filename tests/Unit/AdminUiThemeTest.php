<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XcVm\Core\Ui\AdminUiTheme;

final class AdminUiThemeTest extends TestCase {
	public function testXtreampiIsTheDefault(): void {
		self::assertSame(AdminUiTheme::XTREAMPI, AdminUiTheme::resolve([], []));
	}

	public function testCookieEnablesXtreampi(): void {
		self::assertSame(
			AdminUiTheme::XTREAMPI,
			AdminUiTheme::resolve([], [AdminUiTheme::COOKIE_NAME => AdminUiTheme::XTREAMPI])
		);
	}

	public function testQuerySelectionOverridesCookieForImmediateRollback(): void {
		self::assertSame(
			AdminUiTheme::LEGACY,
			AdminUiTheme::resolve(
				[AdminUiTheme::QUERY_PARAMETER => AdminUiTheme::LEGACY],
				[AdminUiTheme::COOKIE_NAME => AdminUiTheme::XTREAMPI]
			)
		);
	}

	public function testUnknownSelectionsFallBackToXtreampi(): void {
		self::assertSame(
			AdminUiTheme::XTREAMPI,
			AdminUiTheme::resolve(
				[AdminUiTheme::QUERY_PARAMETER => 'unknown'],
				[AdminUiTheme::COOKIE_NAME => 'unknown']
			)
		);
	}

	public function testLegacyCookieRemainsAvailableAsRollback(): void {
		self::assertSame(
			AdminUiTheme::LEGACY,
			AdminUiTheme::resolve([], [AdminUiTheme::COOKIE_NAME => AdminUiTheme::LEGACY])
		);
	}
}

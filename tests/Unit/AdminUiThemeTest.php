<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XcVm\Core\Ui\AdminUiTheme;

final class AdminUiThemeTest extends TestCase {
	public function testLegacyIsTheSafeDefault(): void {
		self::assertSame(AdminUiTheme::LEGACY, AdminUiTheme::resolve([], []));
	}

	public function testCookieEnablesStreamcreed(): void {
		self::assertSame(
			AdminUiTheme::STREAMCREED,
			AdminUiTheme::resolve([], [AdminUiTheme::COOKIE_NAME => AdminUiTheme::STREAMCREED])
		);
	}

	public function testQuerySelectionOverridesCookieForImmediateRollback(): void {
		self::assertSame(
			AdminUiTheme::LEGACY,
			AdminUiTheme::resolve(
				[AdminUiTheme::QUERY_PARAMETER => AdminUiTheme::LEGACY],
				[AdminUiTheme::COOKIE_NAME => AdminUiTheme::STREAMCREED]
			)
		);
	}

	public function testUnknownSelectionsAreIgnored(): void {
		self::assertSame(
			AdminUiTheme::LEGACY,
			AdminUiTheme::resolve(
				[AdminUiTheme::QUERY_PARAMETER => 'unknown'],
				[AdminUiTheme::COOKIE_NAME => 'unknown']
			)
		);
	}
}

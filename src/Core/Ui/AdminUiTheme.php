<?php

declare(strict_types=1);
namespace XcVm\Core\Ui;

/**
 * Resolves the opt-in admin UI without coupling it to database state.
 *
 * The query parameter is intentionally allowed to override the cookie so an
 * administrator always has a direct recovery path back to the legacy UI.
 */
final class AdminUiTheme {
	public const LEGACY = 'legacy';
	public const STREAMCREED = 'streamcreed';
	public const QUERY_PARAMETER = 'admin_ui';
	public const COOKIE_NAME = 'xc_vm_admin_ui';

	/**
	 * Resolve the requested UI, preferring an explicit query-string choice.
	 *
	 * @param array<string,mixed> $query
	 * @param array<string,mixed> $cookies
	 */
	public static function resolve(array $query, array $cookies): string {
		$requested = self::requested($query);
		if ($requested !== null) {
			return $requested;
		}

		$cookie = $cookies[self::COOKIE_NAME] ?? null;
		return self::isSupported($cookie) ? $cookie : self::LEGACY;
	}

	/**
	 * Return an explicit, valid query-string selection when one is present.
	 *
	 * @param array<string,mixed> $query
	 */
	public static function requested(array $query): ?string {
		$requested = $query[self::QUERY_PARAMETER] ?? null;
		return self::isSupported($requested) ? $requested : null;
	}

	private static function isSupported(mixed $theme): bool {
		return is_string($theme) && in_array($theme, [self::LEGACY, self::STREAMCREED], true);
	}
}

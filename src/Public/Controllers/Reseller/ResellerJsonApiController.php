<?php

namespace XcVm\Public\Controllers\Reseller;

use XcVm\Core\Auth\Authenticator;
use XcVm\Core\Auth\Authorization;
use XcVm\Core\Auth\AuthRepository;
use XcVm\Core\Config\SettingsManager;
use XcVm\Core\Database\QueryHelper;
use XcVm\Core\Http\ApiClient;
use XcVm\Core\Http\RequestManager;
use XcVm\Core\Util\AdminHelpers;
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Device\EnigmaService;
use XcVm\Domain\Device\MagService;
use XcVm\Domain\Line\LineService;
use XcVm\Domain\Line\PackageService;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\Stream\ConnectionTracker;
use XcVm\Domain\User\UserRepository;
use XcVm\Domain\User\UserService;

/**
 * ResellerJsonApiController — Clean Native JSON REST API Bridge for Next.js Reseller Portal.
 *
 * Supports authentication via:
 * 1. Active PHPSESSID session cookie ($_SESSION['reseller'])
 * 2. HTTP Headers: X-Reseller-Username & X-Reseller-Password
 * 3. Request Parameters: username & password OR api_key
 *
 * @package XC_VM_Public_Controllers_Reseller
 */
class ResellerJsonApiController
{
    private static $db = null;
    private static $userInfo = null;
    private static $permissions = null;

    public function index()
    {
        // 1. Force JSON headers and disable browser caching
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (!defined('MAIN_HOME')) {
            define('MAIN_HOME', dirname(__DIR__, 3) . '/');
        }
        require_once MAIN_HOME . 'bootstrap.php';
        \XC_Bootstrap::boot(\XC_Bootstrap::CONTEXT_ADMIN, ['process' => 'XC_VM[ResellerAPI]']);

        global $db;
        self::$db = &$db;

        // 2. Parse request payload
        $rData = RequestManager::getAll();
        if (empty($rData)) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $jsonDecoded = json_decode($rawInput, true);
                if (is_array($jsonDecoded)) {
                    $rData = $jsonDecoded;
                }
            }
        }

        $action = trim((string)($rData['action'] ?? $_GET['action'] ?? ''));

        $action = trim((string)($rData['action'] ?? $_GET['action'] ?? ''));

        // 3. Public Actions (login and refresh_token)
        if ($action === 'login') {
            self::handleLogin($rData);
        }

        if ($action === 'refresh_token') {
            self::handleRefreshToken($rData);
        }

        // 4. Resolve and authenticate reseller user for all protected actions
        if (!self::authenticate($rData)) {
            http_response_code(401);
            echo json_encode([
                'success'       => false,
                'authenticated' => false,
                'error'         => 'Authentication required or token expired/invalidated. Provide valid Bearer token, X-Api-Key, credentials, or session.',
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        switch ($action) {
            case 'me':
                self::handleMe();
                break;
            case 'packages':
                self::handlePackages();
                break;
            case 'bouquets':
                self::handleBouquets();
                break;
            case 'lines':
                self::handleLines($rData);
                break;
            case 'get_line':
                self::handleGetLine($rData);
                break;
            case 'create_line':
                self::handleCreateLine($rData);
                break;
            case 'extend_line':
                self::handleExtendLine($rData);
                break;
            case 'toggle_line':
                self::handleToggleLine($rData);
                break;
            case 'delete_line':
                self::handleDeleteLine($rData);
                break;
            case 'connections':
                self::handleConnections($rData);
                break;
            case 'kill_connection':
                self::handleKillConnection($rData);
                break;
            case 'mags':
                self::handleMags($rData);
                break;
            case 'create_mag':
                self::handleCreateMag($rData);
                break;
            case 'delete_mag':
                self::handleDeleteMag($rData);
                break;
            case 'enigmas':
                self::handleEnigmas($rData);
                break;
            case 'create_enigma':
                self::handleCreateEnigma($rData);
                break;
            case 'delete_enigma':
                self::handleDeleteEnigma($rData);
                break;
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error'   => "Invalid or missing action parameter: '{$action}'",
                ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit();
        }
    }

    /**
     * POST ?action=login
     * Authenticates username/password and issues a Short-Lived Access Token (2h) + Refresh Token (30d).
     */
    private static function handleLogin(array $params): void
    {
        $db = self::$db;
        $username = trim((string)($params['username'] ?? ''));
        $password = trim((string)($params['password'] ?? ''));

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username and password are required.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $userAuth = UserRepository::getAuthUserByCredentials($username, $password);
        if (!$userAuth || empty($userAuth['status'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid username or password.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $user = UserRepository::getRegisteredUserById((int)$userAuth['id']);
        if (!$user || empty($user['status'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Account is inactive or disabled.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $group = AuthRepository::getPermissions($user['member_group_id']);
        if (empty($group['is_reseller'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Account does not have reseller permissions.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Ensure user has a 32-character API key
        $apiKey = $user['api_key'] ?? '';
        if (strlen($apiKey) !== 32) {
            $apiKey = bin2hex(random_bytes(16));
            $db->query('UPDATE `users` SET `api_key` = ? WHERE `id` = ?;', $apiKey, $user['id']);
            $user['api_key'] = $apiKey;
        }

        // Password hash fingerprint (for instant revocation if password changes)
        $pwh = substr(hash('sha256', (string)($user['password'] ?? '')), 0, 16);

        // 1. Short-Lived Access Token (2 hours = 7200 seconds)
        $accessTokenTtl = 7200;
        $accessToken = self::generateJwt([
            'user_id'         => (int)$user['id'],
            'username'        => $user['username'],
            'member_group_id' => (int)$user['member_group_id'],
            'pwh'             => $pwh,
            'typ'             => 'access',
        ], $accessTokenTtl);

        // 2. Long-Lived Refresh Token (30 days = 2592000 seconds)
        $refreshTokenTtl = 30 * 86400;
        $refreshToken = self::generateJwt([
            'user_id'  => (int)$user['id'],
            'username' => $user['username'],
            'pwh'      => $pwh,
            'typ'      => 'refresh',
        ], $refreshTokenTtl);

        // Also establish session for legacy fallback
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['reseller'] = (int)$user['id'];

        echo json_encode([
            'success'       => true,
            'access_token'  => $accessToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $accessTokenTtl,
            'refresh_token' => $refreshToken,
            'user'          => [
                'id'              => (int)$user['id'],
                'username'        => $user['username'],
                'credits'         => (float)$user['credits'],
                'member_group'    => $group['group_name'] ?? 'Reseller',
                'member_group_id' => (int)$user['member_group_id'],
                'email'           => $user['email'] ?? '',
                'permissions'     => self::getFullGroupPermissions((int)$user['member_group_id'], (int)$user['id']),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=refresh_token
     * Exchanges a valid Refresh Token for a brand new Access Token (and rotates refresh token).
     */
    private static function handleRefreshToken(array $params): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        $refreshToken = $params['refresh_token'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $refreshToken = trim($matches[1]);
        }

        if ($refreshToken === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'refresh_token parameter is required.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $payload = self::verifyJwt($refreshToken);
        if (!$payload || empty($payload['user_id']) || ($payload['typ'] ?? '') !== 'refresh') {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid, expired, or wrong token type for refresh.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $user = UserRepository::getRegisteredUserById((int)$payload['user_id']);
        if (!$user || empty($user['status'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Account is inactive or disabled.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $group = AuthRepository::getPermissions($user['member_group_id']);
        if (empty($group['is_reseller'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Account does not have reseller permissions.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Verify password fingerprint (instant revocation check)
        $currentPwh = substr(hash('sha256', (string)($user['password'] ?? '')), 0, 16);
        if (!empty($payload['pwh']) && !hash_equals($currentPwh, (string)$payload['pwh'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Password has changed since this token was issued. Please log in again.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Generate fresh new access token (2 hours) and rotated refresh token
        $accessTokenTtl = 7200;
        $newAccessToken = self::generateJwt([
            'user_id'         => (int)$user['id'],
            'username'        => $user['username'],
            'member_group_id' => (int)$user['member_group_id'],
            'pwh'             => $currentPwh,
            'typ'             => 'access',
        ], $accessTokenTtl);

        $refreshTokenTtl = 30 * 86400;
        $newRefreshToken = self::generateJwt([
            'user_id'  => (int)$user['id'],
            'username' => $user['username'],
            'pwh'      => $currentPwh,
            'typ'      => 'refresh',
        ], $refreshTokenTtl);

        echo json_encode([
            'success'       => true,
            'access_token'  => $newAccessToken,
            'token_type'    => 'Bearer',
            'expires_in'    => $accessTokenTtl,
            'refresh_token' => $newRefreshToken,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Authenticate request using Bearer JWT token, API key, headers, credentials, or session.
     */
    private static function authenticate(array $params = []): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $db = self::$db;

        // A. Check Authorization: Bearer <JWT_TOKEN> or X-Auth-Token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        $token = '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } elseif (!empty($authHeader) && substr_count($authHeader, '.') === 2) {
            $token = trim($authHeader);
        } elseif (!empty($params['token']) && substr_count($params['token'], '.') === 2) {
            $token = trim($params['token']);
        }

        if ($token !== '') {
            $payload = self::verifyJwt($token);
            if ($payload && !empty($payload['user_id'])) {
                $user = UserRepository::getRegisteredUserById((int)$payload['user_id']);
                if ($user && !empty($user['status'])) {
                    // Password change / revocation check
                    if (!empty($payload['pwh'])) {
                        $currentPwh = substr(hash('sha256', (string)($user['password'] ?? '')), 0, 16);
                        if (!hash_equals($currentPwh, (string)$payload['pwh'])) {
                            return false; // Revoked due to password change!
                        }
                    }

                    $group = AuthRepository::getPermissions($user['member_group_id']);
                    if (!empty($group['is_reseller'])) {
                        self::setupUserContext($user, $group);
                        return true;
                    }
                }
            }
        }

        // B. Check API Key (X-Api-Key header, or api_key param)
        $apiKey = $params['api_key'] ?? $_REQUEST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apiKey) && strlen($apiKey) === 32) {
            $db->query('SELECT `id` FROM `users` LEFT JOIN `users_groups` ON `users_groups`.`group_id` = `users`.`member_group_id` WHERE `api_key` = ? AND `is_reseller` = 1 AND `status` = 1 LIMIT 1;', $apiKey);
            if ($db->num_rows() > 0) {
                $userId = (int)$db->get_row()['id'];
                $user = UserRepository::getRegisteredUserById($userId);
                $group = AuthRepository::getPermissions($user['member_group_id']);
                self::setupUserContext($user, $group);
                $_SESSION['reseller'] = $user['id'];
                return true;
            }
        }

        // C. Check active session (PHPSESSID)
        if (!empty($_SESSION['reseller'])) {
            $user = UserRepository::getRegisteredUserById((int)$_SESSION['reseller']);
            if ($user && !empty($user['status'])) {
                $group = AuthRepository::getPermissions($user['member_group_id']);
                if (!empty($group['is_reseller'])) {
                    self::setupUserContext($user, $group);
                    return true;
                }
            }
        }

        // D. Check custom headers (X-Reseller-Username / X-Reseller-Password)
        $headerUser = $_SERVER['HTTP_X_RESELLER_USERNAME'] ?? $_SERVER['X_RESELLER_USERNAME'] ?? '';
        $headerPass = $_SERVER['HTTP_X_RESELLER_PASSWORD'] ?? $_SERVER['X_RESELLER_PASSWORD'] ?? '';

        if (!empty($headerUser) && !empty($headerPass)) {
            $userAuth = UserRepository::getAuthUserByCredentials($headerUser, $headerPass);
            if ($userAuth && !empty($userAuth['status'])) {
                $user = UserRepository::getRegisteredUserById((int)$userAuth['id']);
                if ($user && !empty($user['status'])) {
                    $group = AuthRepository::getPermissions($user['member_group_id']);
                    if (!empty($group['is_reseller'])) {
                        self::setupUserContext($user, $group);
                        $_SESSION['reseller'] = $user['id'];
                        return true;
                    }
                }
            }
        }

        // E. Check query or body credentials
        $reqUser = $params['username'] ?? $_REQUEST['username'] ?? '';
        $reqPass = $params['password'] ?? $_REQUEST['password'] ?? '';
        if (!empty($reqUser) && !empty($reqPass)) {
            $userAuth = UserRepository::getAuthUserByCredentials($reqUser, $reqPass);
            if ($userAuth && !empty($userAuth['status'])) {
                $user = UserRepository::getRegisteredUserById((int)$userAuth['id']);
                if ($user && !empty($user['status'])) {
                    $group = AuthRepository::getPermissions($user['member_group_id']);
                    if (!empty($group['is_reseller'])) {
                        self::setupUserContext($user, $group);
                        $_SESSION['reseller'] = $user['id'];
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * JWT Helper: Secret key derived from server install configuration.
     */
    private static function getJwtSecret(): string
    {
        $settings = SettingsManager::getAll();
        $serverSalt = $settings['server_name'] ?? 'xc_vm_jwt_salt_2026';
        return hash('sha256', 'XC_VM_JWT_SECRET_' . $serverSalt);
    }

    /**
     * JWT Helper: Generate HS256 signed JSON Web Token.
     */
    public static function generateJwt(array $payload, int $ttlSeconds = 2592000): string
    {
        $secret = self::getJwtSecret();
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttlSeconds;

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    /**
     * JWT Helper: Verify and decode HS256 signed JSON Web Token.
     */
    public static function verifyJwt(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        [$b64Header, $b64Payload, $b64Sig] = $parts;
        $secret = self::getJwtSecret();
        $expectedSig = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', "{$b64Header}.{$b64Payload}", $secret, true)));

        if (!hash_equals($expectedSig, $b64Sig)) {
            return null;
        }

        $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $b64Payload));
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload) || (isset($payload['exp']) && $payload['exp'] < time())) {
            return null;
        }

        return $payload;
    }

    private static function setupUserContext(array $user, array $group): void
    {
        unset($user['password']);
        self::$userInfo = $user;
        self::$permissions = array_merge(
            $group,
            AuthRepository::getGroupPermissions($user['id'])
        );
        self::$userInfo['reports'] = array_map('intval', array_merge(
            [$user['id']],
            self::$permissions['all_reports'] ?? []
        ));

        $GLOBALS['rUserInfo'] = self::$userInfo;
        $GLOBALS['rPermissions'] = self::$permissions;

        if (!empty(self::$userInfo['timezone'])) {
            date_default_timezone_set(self::$userInfo['timezone']);
        }
    }

    /**
     * Retrieve complete dynamic group permissions from users_groups table & package entitlements.
     */
    private static function getFullGroupPermissions(int $groupId, int $userId): array
    {
        $db = self::$db;
        $db->query('SELECT * FROM `users_groups` WHERE `group_id` = ? LIMIT 1;', $groupId);
        $rGroup = $db->get_row() ?: [];

        $rPerms = [];
        if (!empty($rGroup['group_permissions'])) {
            $rPerms = json_decode($rGroup['group_permissions'], true) ?: (is_string($rGroup['group_permissions']) && @unserialize($rGroup['group_permissions']) !== false ? unserialize($rGroup['group_permissions']) : []);
            if (!is_array($rPerms)) {
                $rPerms = [];
            }
        }

        // Package capabilities (create_line, create_mag, create_enigma)
        $createLine = false;
        $createMag = false;
        $createEnigma = false;
        $db->query("SELECT * FROM `users_packages` WHERE JSON_CONTAINS(`groups`, ?, '$');", $groupId);
        foreach ($db->get_rows() ?: [] as $pkg) {
            if (!empty($pkg['is_line'])) $createLine = true;
            if (!empty($pkg['is_mag'])) $createMag = true;
            if (!empty($pkg['is_e2'])) $createEnigma = true;
        }

        return [
            'live_connections'        => (bool)($rGroup['reseller_client_connection_logs'] ?? $rPerms['live_connections'] ?? 1),
            'view_vod'                => (bool)($rGroup['can_view_vod'] ?? $rPerms['view_vod'] ?? 1),
            'show_m3u'                => (bool)($rGroup['allow_download'] ?? $rPerms['show_m3u'] ?? 1),
            'delete_users'            => (bool)($rGroup['delete_users'] ?? $rPerms['delete_users'] ?? 1),
            'allow_change_bouquets'   => (bool)($rGroup['allow_change_bouquets'] ?? $rPerms['allow_change_bouquets'] ?? 0),
            'edit_usernames'          => (bool)($rGroup['allow_change_username'] ?? $rPerms['edit_usernames'] ?? 1),
            'edit_passwords'          => (bool)($rGroup['allow_change_password'] ?? $rPerms['edit_passwords'] ?? 1),
            'min_username_len'        => (int)($rGroup['minimum_username_length'] ?? $rPerms['min_username_len'] ?? 8),
            'min_password_len'        => (int)($rGroup['minimum_password_length'] ?? $rPerms['min_password_len'] ?? 8),
            'min_trial_credits'       => (float)($rGroup['minimum_trial_credits'] ?? $rPerms['min_trial_credits'] ?? 0),
            'allowed_trials'          => (int)($rGroup['total_allowed_gen_trials'] ?? $rPerms['allowed_trials'] ?? 100000),
            'allowed_trials_in'       => (string)($rGroup['total_allowed_gen_in'] ?? $rPerms['allowed_trials_in'] ?? 'month'),
            'allow_line_restrictions' => (bool)($rGroup['allow_restrictions'] ?? $rPerms['allow_line_restrictions'] ?? 1),
            'create_line'             => (bool)($createLine || ($rPerms['create_line'] ?? true)),
            'create_mag'              => (bool)($createMag || ($rPerms['create_mag'] ?? false)),
            'create_enigma'           => (bool)($createEnigma || ($rPerms['create_enigma'] ?? false)),
            'create_sub_resellers'    => (bool)($rGroup['create_sub_resellers'] ?? $rPerms['create_sub_resellers'] ?? 0),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Endpoints
    // ─────────────────────────────────────────────────────────────

    /**
     * GET ?action=me
     */
    private static function handleMe(): void
    {
        $db = self::$db;
        $user = self::$userInfo;
        $reports = implode(',', $user['reports']);

        // 1. Total lines count
        $db->query("SELECT COUNT(*) AS `count` FROM `lines` WHERE `is_mag` = 0 AND `is_e2` = 0 AND `member_id` IN ({$reports});");
        $totalLines = (int)($db->get_row()['count'] ?? 0);

        // 2. Active accounts count (enabled and unexpired)
        $now = time();
        $db->query("SELECT COUNT(*) AS `count` FROM `lines` WHERE `is_mag` = 0 AND `is_e2` = 0 AND `enabled` = 1 AND `exp_date` > {$now} AND `member_id` IN ({$reports});");
        $activeAccounts = (int)($db->get_row()['count'] ?? 0);

        // 3. Open connections count
        $db->query("SELECT COUNT(`lines_live`.`activity_id`) AS `count` FROM `lines_live` LEFT JOIN `lines` ON `lines`.`id` = `lines_live`.`user_id` WHERE `lines_live`.`hls_end` = 0 AND `lines`.`member_id` IN ({$reports});");
        $openConnections = (int)($db->get_row()['count'] ?? 0);

        // 4. Group name
        $groupName = self::$permissions['group_name'] ?? 'Reseller';

        echo json_encode([
            'success'          => true,
            'id'               => (int)$user['id'],
            'username'         => $user['username'],
            'credits'          => (float)$user['credits'],
            'member_group'     => $groupName,
            'member_group_id'  => (int)$user['member_group_id'],
            'email'            => $user['email'] ?? '',
            'reseller_dns'     => $user['reseller_dns'] ?? '',
            'timezone'         => $user['timezone'] ?? '',
            'total_lines'      => $totalLines,
            'active_accounts'  => $activeAccounts,
            'open_connections' => $openConnections,
            'permissions'      => self::getFullGroupPermissions((int)$user['member_group_id'], (int)$user['id']),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=packages
     */
    private static function handlePackages(): void
    {
        $user = self::$userInfo;
        $packages = PackageService::getAll((int)$user['member_group_id']) ?: [];
        $override = !empty($user['override_packages']) ? json_decode($user['override_packages'], true) : [];

        $out = [];
        foreach ($packages as $pkg) {
            $pkgId = (int)$pkg['id'];
            $cost = (float)$pkg['official_credits'];
            if (isset($override[$pkgId]['official_credits']) && strlen((string)$override[$pkgId]['official_credits']) > 0) {
                $cost = (float)$override[$pkgId]['official_credits'];
            }

            $trialCost = (float)($pkg['trial_credits'] ?? 0);
            if (isset($override[$pkgId]['trial_credits']) && strlen((string)$override[$pkgId]['trial_credits']) > 0) {
                $trialCost = (float)$override[$pkgId]['trial_credits'];
            }

            $bouquets = !empty($pkg['bouquets']) ? json_decode($pkg['bouquets'], true) : [];

            $out[] = [
                'id'               => $pkgId,
                'name'             => $pkg['package_name'],
                'cost_credits'     => $cost,
                'trial_credits'    => $trialCost,
                'duration'         => (int)$pkg['official_duration'],
                'duration_unit'    => $pkg['official_duration_in'] ?? 'months',
                'trial_duration'   => (int)($pkg['trial_duration'] ?? 24),
                'trial_duration_unit' => $pkg['trial_duration_in'] ?? 'hours',
                'max_connections'  => (int)$pkg['max_connections'],
                'is_line'          => (bool)$pkg['is_line'],
                'is_mag'           => (bool)$pkg['is_mag'],
                'is_e2'            => (bool)$pkg['is_e2'],
                'is_trial'         => (bool)$pkg['is_trial'],
                'bouquets'         => is_array($bouquets) ? array_values(array_map('intval', $bouquets)) : [],
            ];
        }

        echo json_encode([
            'success'  => true,
            'packages' => $out,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=bouquets
     */
    private static function handleBouquets(): void
    {
        $db = self::$db;
        $db->query('SELECT `id`, `bouquet_name` FROM `bouquets` ORDER BY `id` ASC;');
        $bouquets = $db->get_rows() ?: [];

        $out = [];
        foreach ($bouquets as $b) {
            $out[] = [
                'id'   => (int)$b['id'],
                'name' => $b['bouquet_name'],
            ];
        }

        echo json_encode([
            'success'  => true,
            'bouquets' => $out,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=lines
     */
    private static function handleLines(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;
        $reports = implode(',', $user['reports']);

        $page = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(200, (int)($params['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        $search = trim((string)($params['search'] ?? ''));

        $whereClause = "`lines`.`is_mag` = 0 AND `lines`.`is_e2` = 0 AND `lines`.`member_id` IN ({$reports})";
        $whereParams = [];

        if ($search !== '') {
            $whereClause .= ' AND (`lines`.`username` LIKE ? OR `lines`.`reseller_notes` LIKE ? OR `lines`.`contact` LIKE ?)';
            $whereParams[] = "%{$search}%";
            $whereParams[] = "%{$search}%";
            $whereParams[] = "%{$search}%";
        }

        $countQuery = "SELECT COUNT(*) AS `count` FROM `lines` WHERE {$whereClause};";
        $db->query($countQuery, ...$whereParams);
        $totalRecords = (int)($db->get_row()['count'] ?? 0);

        $dataQuery = "SELECT `lines`.*, `users`.`username` AS `reseller_owner`
                      FROM `lines`
                      LEFT JOIN `users` ON `users`.`id` = `lines`.`member_id`
                      WHERE {$whereClause}
                      ORDER BY `lines`.`id` DESC
                      LIMIT {$offset}, {$limit};";

        $db->query($dataQuery, ...$whereParams);
        $rows = $db->get_rows() ?: [];

        // Fetch active connection line IDs for online status
        $lineIds = array_column($rows, 'id');
        $activeConnectionsMap = [];
        if (!empty($lineIds)) {
            $idsList = implode(',', array_map('intval', $lineIds));
            $db->query("SELECT `user_id`, COUNT(*) AS `conns` FROM `lines_live` WHERE `hls_end` = 0 AND `user_id` IN ({$idsList}) GROUP BY `user_id`;");
            foreach ($db->get_rows() ?: [] as $cRow) {
                $activeConnectionsMap[(int)$cRow['user_id']] = (int)$cRow['conns'];
            }
        }

        $now = time();
        $lines = [];
        foreach ($rows as $r) {
            $lineId = (int)$r['id'];
            $expDate = (int)$r['exp_date'];
            $enabled = (int)$r['enabled'];
            $adminEnabled = (int)($r['admin_enabled'] ?? 1);

            $status = 'active';
            if ($enabled === 0 || $adminEnabled === 0) {
                $status = 'disabled';
            } elseif ($expDate <= $now) {
                $status = 'expired';
            }

            $conns = $activeConnectionsMap[$lineId] ?? 0;

            $lines[] = [
                'id'                 => $lineId,
                'username'           => $r['username'],
                'password'           => $r['password'],
                'owner'              => $r['reseller_owner'] ?? $user['username'],
                'status'             => $status,
                'is_online'          => $conns > 0,
                'is_trial'           => (bool)$r['is_trial'],
                'active_connections' => $conns,
                'max_connections'    => (int)$r['max_connections'],
                'exp_date'           => $expDate > 0 ? date('Y-m-d H:i:s', $expDate) : null,
                'exp_timestamp'      => $expDate,
                'created_at'         => (int)$r['created_at'] > 0 ? date('Y-m-d H:i:s', (int)$r['created_at']) : null,
                'last_activity'      => (int)$r['last_activity'] > 0 ? date('Y-m-d H:i:s', (int)$r['last_activity']) : null,
                'notes'              => $r['reseller_notes'] ?? '',
                'contact'            => $r['contact'] ?? '',
                'package_id'         => (int)$r['package_id'],
            ];
        }

        echo json_encode([
            'success' => true,
            'total'   => $totalRecords,
            'page'    => $page,
            'limit'   => $limit,
            'lines'   => $lines,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=get_line
     */
    private static function handleGetLine(array $params): void
    {
        $lineId = (int)($params['line_id'] ?? $params['id'] ?? 0);
        $line = UserRepository::getLineById($lineId);

        if (!$line || !in_array((int)$line['member_id'], self::$userInfo['reports'], true)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Line not found or access denied.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        echo json_encode([
            'success' => true,
            'line'    => $line,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=create_line
     */
    private static function handleCreateLine(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;

        $username = trim((string)($params['username'] ?? ''));
        $password = trim((string)($params['password'] ?? ''));
        $packageId = (int)($params['package_id'] ?? $params['package'] ?? 0);
        $isTrial = !empty($params['trial']) ? 1 : 0;
        $contact = trim((string)($params['contact'] ?? ''));
        $notes = trim((string)($params['reseller_notes'] ?? $params['notes'] ?? ''));

        if ($username === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username and password are required.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        if ($packageId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid package_id is required.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Check if username already exists in lines or users
        $db->query('SELECT `id` FROM `lines` WHERE `username` = ? LIMIT 1;', $username);
        if ($db->num_rows() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Username '{$username}' already exists."], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Fetch package & verify access
        $package = PackageService::getById($packageId);
        if (!$package || !in_array((int)$user['member_group_id'], json_decode($package['groups'], true) ?: [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid or unauthorized package.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Determine price & trial allowance
        $override = !empty($user['override_packages']) ? json_decode($user['override_packages'], true) : [];
        if ($isTrial) {
            if (!LineService::canGenerateTrials($user['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Trial generation limit reached for your account.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit();
            }
            $cost = (float)($package['trial_credits'] ?? 0);
            $expDate = strtotime('+' . (int)$package['trial_duration'] . ' ' . ($package['trial_duration_in'] ?? 'hours'));
        } else {
            $cost = (float)$package['official_credits'];
            if (isset($override[$packageId]['official_credits']) && strlen((string)$override[$packageId]['official_credits']) > 0) {
                $cost = (float)$override[$packageId]['official_credits'];
            }
            $expDate = strtotime('+' . (int)$package['official_duration'] . ' ' . ($package['official_duration_in'] ?? 'months'));
        }

        if ((float)$user['credits'] < $cost) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => 'Insufficient credits. Required: ' . $cost . ', Available: ' . (float)$user['credits'],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Bouquets selection
        $bouquets = json_decode($package['bouquets'], true) ?: [];
        if (!empty($params['bouquets']) && is_array($params['bouquets']) && !empty(self::$permissions['allow_change_bouquets'])) {
            $allowedBouquets = array_intersect(array_map('intval', $params['bouquets']), $bouquets);
            if (!empty($allowedBouquets)) {
                $bouquets = $allowedBouquets;
            }
        }
        $bouquetStr = '[' . implode(',', array_map('intval', $bouquets)) . ']';

        $outputFormats = json_decode($package['output_formats'], true) ?: [1, 2, 3];
        $outputStr = '[' . implode(',', array_map('intval', $outputFormats)) . ']';

        // Insert into lines table
        $lineData = [
            'member_id'        => (int)$user['id'],
            'username'         => $username,
            'password'         => $password,
            'exp_date'         => $expDate,
            'admin_enabled'    => 1,
            'enabled'          => 1,
            'reseller_notes'   => $notes,
            'bouquet'          => $bouquetStr,
            'allowed_outputs'  => $outputStr,
            'max_connections'  => (int)$package['max_connections'],
            'is_restreamer'    => (int)$package['is_restreamer'],
            'is_trial'         => $isTrial,
            'is_mag'           => 0,
            'is_e2'            => 0,
            'created_at'       => time(),
            'package_id'       => $packageId,
            'contact'          => $contact,
            'force_server_id'  => (int)$package['force_server_id'],
            'forced_country'   => (string)$package['forced_country'],
            'is_isplock'       => (int)$package['is_isplock'],
        ];

        $prepare = QueryHelper::prepareArray($lineData);
        $insertQuery = 'INSERT INTO `lines`(' . $prepare['columns'] . ') VALUES(' . $prepare['placeholder'] . ');';

        if (!$db->query($insertQuery, ...$prepare['data'])) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error creating line.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $lineId = (int)$db->last_insert_id();

        // Deduct credits & write audit log
        $newCredits = (float)$user['credits'] - $cost;
        $db->query('UPDATE `users` SET `credits` = ? WHERE `id` = ?;', $newCredits, $user['id']);

        $db->query(
            "INSERT INTO `users_logs`(`owner`, `type`, `action`, `log_id`, `package_id`, `cost`, `credits_after`, `date`, `deleted_info`) VALUES(?, 'line', 'new', ?, ?, ?, ?, ?, ?);",
            $user['id'],
            $lineId,
            $packageId,
            $cost,
            $newCredits,
            time(),
            json_encode($lineData)
        );

        echo json_encode([
            'success'           => true,
            'line_id'           => $lineId,
            'username'          => $username,
            'credits_remaining' => $newCredits,
            'cost'              => $cost,
            'exp_date'          => date('Y-m-d H:i:s', $expDate),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=extend_line
     */
    private static function handleExtendLine(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;

        $lineId = (int)($params['line_id'] ?? $params['id'] ?? 0);
        $packageId = (int)($params['package_id'] ?? $params['package'] ?? 0);
        $notes = isset($params['reseller_notes']) ? trim((string)$params['reseller_notes']) : null;
        $newPass = isset($params['password']) ? trim((string)$params['password']) : null;

        $line = UserRepository::getLineById($lineId);
        if (!$line || !in_array((int)$line['member_id'], $user['reports'], true)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Line not found or unauthorized.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $package = PackageService::getById($packageId);
        if (!$package || !in_array((int)$user['member_group_id'], json_decode($package['groups'], true) ?: [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid or unauthorized package.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Calculate cost
        $override = !empty($user['override_packages']) ? json_decode($user['override_packages'], true) : [];
        $cost = (float)$package['official_credits'];
        if (isset($override[$packageId]['official_credits']) && strlen((string)$override[$packageId]['official_credits']) > 0) {
            $cost = (float)$override[$packageId]['official_credits'];
        }

        if ((float)$user['credits'] < $cost) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient credits. Required: ' . $cost], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Calculate new expiration date
        $now = time();
        $currExp = (int)$line['exp_date'];
        $baseTime = ($currExp > $now) ? $currExp : $now;
        $newExpDate = strtotime('+' . (int)$package['official_duration'] . ' ' . ($package['official_duration_in'] ?? 'months'), $baseTime);

        // Update line
        $updates = [
            'exp_date'   => $newExpDate,
            'is_trial'   => 0,
            'package_id' => $packageId,
            'enabled'    => 1,
        ];
        if ($notes !== null) {
            $updates['reseller_notes'] = $notes;
        }
        if ($newPass !== null && $newPass !== '') {
            $updates['password'] = $newPass;
        }

        $updateCols = [];
        $updateVals = [];
        foreach ($updates as $col => $val) {
            $updateCols[] = "`{$col}` = ?";
            $updateVals[] = $val;
        }
        $updateVals[] = $lineId;

        $db->query('UPDATE `lines` SET ' . implode(', ', $updateCols) . ' WHERE `id` = ?;', ...$updateVals);

        // Deduct credits & log
        $newCredits = (float)$user['credits'] - $cost;
        $db->query('UPDATE `users` SET `credits` = ? WHERE `id` = ?;', $newCredits, $user['id']);

        $db->query(
            "INSERT INTO `users_logs`(`owner`, `type`, `action`, `log_id`, `package_id`, `cost`, `credits_after`, `date`, `deleted_info`) VALUES(?, 'line', 'extend', ?, ?, ?, ?, ?, ?);",
            $user['id'],
            $lineId,
            $packageId,
            $cost,
            $newCredits,
            time(),
            json_encode($line)
        );

        echo json_encode([
            'success'           => true,
            'line_id'           => $lineId,
            'new_exp_date'      => date('Y-m-d H:i:s', $newExpDate),
            'exp_timestamp'     => $newExpDate,
            'credits_remaining' => $newCredits,
            'cost'              => $cost,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=toggle_line
     */
    private static function handleToggleLine(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;

        $lineId = (int)($params['line_id'] ?? $params['id'] ?? 0);
        $status = strtolower((string)($params['status'] ?? ''));

        $line = UserRepository::getLineById($lineId);
        if (!$line || !in_array((int)$line['member_id'], $user['reports'], true)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Line not found or unauthorized.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $newEnabled = ($status === 'enable' || $status === '1' || $status === 'true') ? 1 : 0;
        $db->query('UPDATE `lines` SET `enabled` = ? WHERE `id` = ?;', $newEnabled, $lineId);

        echo json_encode([
            'success' => true,
            'line_id' => $lineId,
            'enabled' => (bool)$newEnabled,
            'status'  => $newEnabled ? 'active' : 'disabled',
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=delete_line
     */
    private static function handleDeleteLine(array $params): void
    {
        $user = self::$userInfo;
        $lineId = (int)($params['line_id'] ?? $params['id'] ?? 0);

        $line = UserRepository::getLineById($lineId);
        if (!$line || !in_array((int)$line['member_id'], $user['reports'], true)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Line not found or unauthorized.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        LineService::deleteLineById($lineId);

        echo json_encode([
            'success' => true,
            'line_id' => $lineId,
            'message' => 'Line deleted successfully.',
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=connections
     */
    private static function handleConnections(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;
        $reports = implode(',', $user['reports']);

        $db->query("SELECT `lines_live`.*, `lines`.`username` AS `line_username`, `streams`.`stream_display_name`
                    FROM `lines_live`
                    LEFT JOIN `lines` ON `lines`.`id` = `lines_live`.`user_id`
                    LEFT JOIN `streams` ON `streams`.`id` = `lines_live`.`stream_id`
                    WHERE `lines_live`.`hls_end` = 0 AND `lines`.`member_id` IN ({$reports})
                    ORDER BY `lines_live`.`date_start` DESC;");

        $rows = $db->get_rows() ?: [];
        $now = time();

        $out = [];
        foreach ($rows as $r) {
            $duration = max(0, $now - (int)$r['date_start']);
            $out[] = [
                'activity_id'      => (int)$r['activity_id'],
                'line_id'          => (int)$r['user_id'],
                'username'         => $r['line_username'],
                'stream_id'        => (int)$r['stream_id'],
                'stream_name'      => $r['stream_display_name'] ?? 'Stream #' . $r['stream_id'],
                'user_ip'          => $r['user_ip'],
                'user_agent'       => $r['user_agent'],
                'country_code'     => $r['geoip_country_code'] ?? '',
                'isp'              => $r['isp'] ?? '',
                'date_start'       => date('Y-m-d H:i:s', (int)$r['date_start']),
                'duration_seconds' => $duration,
                'pid'              => (int)($r['pid'] ?? 0),
                'server_id'        => (int)($r['server_id'] ?? 1),
            ];
        }

        echo json_encode([
            'success'     => true,
            'total'       => count($out),
            'connections' => $out,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=kill_connection
     */
    private static function handleKillConnection(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;
        $reports = implode(',', $user['reports']);

        $pid = (int)($params['pid'] ?? 0);
        $activityId = (int)($params['activity_id'] ?? 0);

        if ($pid <= 0 && $activityId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid pid or activity_id is required.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $where = ($pid > 0) ? "`lines_live`.`pid` = {$pid}" : "`lines_live`.`activity_id` = {$activityId}";
        $db->query("SELECT `lines_live`.*, `lines`.`member_id` FROM `lines_live` LEFT JOIN `lines` ON `lines`.`id` = `lines_live`.`user_id` WHERE {$where} AND `lines`.`member_id` IN ({$reports}) LIMIT 1;");
        $conn = $db->get_row();

        if (!$conn) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Connection not found or unauthorized.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $serverId = (int)($conn['server_id'] ?? 1);
        $connPid = (int)($conn['pid'] ?? 0);

        if ($connPid > 0) {
            ApiClient::systemRequest($serverId, ['action' => 'kill_pid', 'pid' => $connPid]);
        }
        $db->query('DELETE FROM `lines_live` WHERE `activity_id` = ?;', (int)$conn['activity_id']);

        echo json_encode([
            'success' => true,
            'message' => 'Connection terminated successfully.',
            'pid'     => $connPid,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=mags
     */
    private static function handleMags(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;
        $reports = implode(',', $user['reports']);

        $page = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(200, (int)($params['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $db->query("SELECT COUNT(*) AS `count` FROM `mag_devices` LEFT JOIN `lines` ON `lines`.`id` = `mag_devices`.`user_id` WHERE `lines`.`member_id` IN ({$reports});");
        $total = (int)($db->get_row()['count'] ?? 0);

        $db->query("SELECT `mag_devices`.*, `lines`.`exp_date`, `lines`.`enabled`, `lines`.`admin_enabled`, `lines`.`reseller_notes`
                    FROM `mag_devices`
                    LEFT JOIN `lines` ON `lines`.`id` = `mag_devices`.`user_id`
                    WHERE `lines`.`member_id` IN ({$reports})
                    ORDER BY `mag_devices`.`mag_id` DESC
                    LIMIT {$offset}, {$limit};");

        $rows = $db->get_rows() ?: [];
        $now = time();
        $mags = [];

        foreach ($rows as $r) {
            $expDate = (int)$r['exp_date'];
            $status = ($r['enabled'] && $r['admin_enabled'] && $expDate > $now) ? 'active' : (($expDate <= $now) ? 'expired' : 'disabled');

            $mags[] = [
                'id'         => (int)$r['mag_id'],
                'line_id'    => (int)$r['user_id'],
                'mac'        => $r['mac'],
                'sn'         => $r['sn'] ?? '',
                'stb_type'   => $r['stb_type'] ?? '',
                'ip'         => $r['ip'] ?? '',
                'status'     => $status,
                'exp_date'   => $expDate > 0 ? date('Y-m-d H:i:s', $expDate) : null,
                'notes'      => $r['reseller_notes'] ?? '',
            ];
        }

        echo json_encode([
            'success' => true,
            'total'   => $total,
            'mags'    => $mags,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=create_mag
     */
    private static function handleCreateMag(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;

        $mac = strtoupper(trim((string)($params['mac'] ?? '')));
        $packageId = (int)($params['package_id'] ?? $params['package'] ?? 0);
        $isTrial = !empty($params['trial']) ? 1 : 0;
        $notes = trim((string)($params['reseller_notes'] ?? $params['notes'] ?? ''));

        if (!filter_var($mac, FILTER_VALIDATE_MAC)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid MAC address format (must be 00:1A:79:XX:XX:XX).'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $db->query('SELECT `mag_id` FROM `mag_devices` WHERE `mac` = ? LIMIT 1;', $mac);
        if ($db->num_rows() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "MAC '{$mac}' is already registered."], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $package = PackageService::getById($packageId);
        if (!$package || empty($package['is_mag']) || !in_array((int)$user['member_group_id'], json_decode($package['groups'], true) ?: [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid MAG package.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $override = !empty($user['override_packages']) ? json_decode($user['override_packages'], true) : [];
        $cost = (float)$package['official_credits'];
        if (isset($override[$packageId]['official_credits']) && strlen((string)$override[$packageId]['official_credits']) > 0) {
            $cost = (float)$override[$packageId]['official_credits'];
        }
        $expDate = strtotime('+' . (int)$package['official_duration'] . ' ' . ($package['official_duration_in'] ?? 'months'));

        if ((float)$user['credits'] < $cost) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient credits.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        // Generate synthetic line for MAG
        $username = AdminHelpers::generateString(32);
        $password = AdminHelpers::generateString(32);
        $bouquetStr = $package['bouquets'] ?? '[]';
        $outputStr = $package['output_formats'] ?? '[]';

        $lineData = [
            'member_id'        => (int)$user['id'],
            'username'         => $username,
            'password'         => $password,
            'exp_date'         => $expDate,
            'admin_enabled'    => 1,
            'enabled'          => 1,
            'reseller_notes'   => $notes,
            'bouquet'          => $bouquetStr,
            'allowed_outputs'  => $outputStr,
            'max_connections'  => (int)$package['max_connections'],
            'is_trial'         => $isTrial,
            'is_mag'           => 1,
            'is_e2'            => 0,
            'created_at'       => time(),
            'package_id'       => $packageId,
        ];

        $pLine = QueryHelper::prepareArray($lineData);
        $db->query('INSERT INTO `lines`(' . $pLine['columns'] . ') VALUES(' . $pLine['placeholder'] . ');', ...$pLine['data']);
        $lineId = (int)$db->last_insert_id();

        $magData = [
            'user_id'    => $lineId,
            'mac'        => $mac,
            'theme_type' => 1,
            'created'    => time(),
        ];
        $pMag = QueryHelper::prepareArray($magData);
        $db->query('INSERT INTO `mag_devices`(' . $pMag['columns'] . ') VALUES(' . $pMag['placeholder'] . ');', ...$pMag['data']);
        $magId = (int)$db->last_insert_id();

        $newCredits = (float)$user['credits'] - $cost;
        $db->query('UPDATE `users` SET `credits` = ? WHERE `id` = ?;', $newCredits, $user['id']);

        echo json_encode([
            'success'           => true,
            'mag_id'            => $magId,
            'line_id'           => $lineId,
            'mac'               => $mac,
            'credits_remaining' => $newCredits,
            'exp_date'          => date('Y-m-d H:i:s', $expDate),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=delete_mag
     */
    private static function handleDeleteMag(array $params): void
    {
        $user = self::$userInfo;
        $magId = (int)($params['mag_id'] ?? $params['id'] ?? 0);

        $mag = MagService::getById($magId);
        if (!$mag || !in_array((int)$mag['user']['member_id'], $user['reports'], true)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'MAG device not found or unauthorized.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        MagService::deleteDevice($magId);

        echo json_encode([
            'success' => true,
            'mag_id'  => $magId,
            'message' => 'MAG device deleted successfully.',
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * GET ?action=enigmas
     */
    private static function handleEnigmas(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;
        $reports = implode(',', $user['reports']);

        $db->query("SELECT `enigma2_devices`.*, `lines`.`exp_date`, `lines`.`enabled`, `lines`.`admin_enabled`, `lines`.`reseller_notes`
                    FROM `enigma2_devices`
                    LEFT JOIN `lines` ON `lines`.`id` = `enigma2_devices`.`user_id`
                    WHERE `lines`.`member_id` IN ({$reports})
                    ORDER BY `enigma2_devices`.`device_id` DESC;");

        $rows = $db->get_rows() ?: [];
        $now = time();
        $enigmas = [];

        foreach ($rows as $r) {
            $expDate = (int)$r['exp_date'];
            $status = ($r['enabled'] && $r['admin_enabled'] && $expDate > $now) ? 'active' : (($expDate <= $now) ? 'expired' : 'disabled');

            $enigmas[] = [
                'id'       => (int)$r['device_id'],
                'line_id'  => (int)$r['user_id'],
                'mac'      => $r['mac'],
                'local_ip' => $r['local_ip'] ?? '',
                'status'   => $status,
                'exp_date' => $expDate > 0 ? date('Y-m-d H:i:s', $expDate) : null,
                'notes'    => $r['reseller_notes'] ?? '',
            ];
        }

        echo json_encode([
            'success' => true,
            'enigmas' => $enigmas,
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=create_enigma
     */
    private static function handleCreateEnigma(array $params): void
    {
        $db = self::$db;
        $user = self::$userInfo;

        $mac = strtoupper(trim((string)($params['mac'] ?? '')));
        $packageId = (int)($params['package_id'] ?? $params['package'] ?? 0);
        $notes = trim((string)($params['reseller_notes'] ?? $params['notes'] ?? ''));

        if (!filter_var($mac, FILTER_VALIDATE_MAC)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid MAC format.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $package = PackageService::getById($packageId);
        if (!$package || empty($package['is_e2']) || !in_array((int)$user['member_group_id'], json_decode($package['groups'], true) ?: [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid Enigma2 package.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $cost = (float)$package['official_credits'];
        if ((float)$user['credits'] < $cost) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Insufficient credits.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        $expDate = strtotime('+' . (int)$package['official_duration'] . ' ' . ($package['official_duration_in'] ?? 'months'));
        $username = AdminHelpers::generateString(32);
        $password = AdminHelpers::generateString(32);

        $lineData = [
            'member_id'        => (int)$user['id'],
            'username'         => $username,
            'password'         => $password,
            'exp_date'         => $expDate,
            'admin_enabled'    => 1,
            'enabled'          => 1,
            'reseller_notes'   => $notes,
            'bouquet'          => $package['bouquets'] ?? '[]',
            'allowed_outputs'  => $package['output_formats'] ?? '[]',
            'max_connections'  => (int)$package['max_connections'],
            'is_mag'           => 0,
            'is_e2'            => 1,
            'created_at'       => time(),
            'package_id'       => $packageId,
        ];

        $pLine = QueryHelper::prepareArray($lineData);
        $db->query('INSERT INTO `lines`(' . $pLine['columns'] . ') VALUES(' . $pLine['placeholder'] . ');', ...$pLine['data']);
        $lineId = (int)$db->last_insert_id();

        $e2Data = [
            'user_id' => $lineId,
            'mac'     => $mac,
        ];
        $pE2 = QueryHelper::prepareArray($e2Data);
        $db->query('INSERT INTO `enigma2_devices`(' . $pE2['columns'] . ') VALUES(' . $pE2['placeholder'] . ');', ...$pE2['data']);
        $e2Id = (int)$db->last_insert_id();

        $newCredits = (float)$user['credits'] - $cost;
        $db->query('UPDATE `users` SET `credits` = ? WHERE `id` = ?;', $newCredits, $user['id']);

        echo json_encode([
            'success'           => true,
            'enigma_id'         => $e2Id,
            'line_id'           => $lineId,
            'mac'               => $mac,
            'credits_remaining' => $newCredits,
            'exp_date'          => date('Y-m-d H:i:s', $expDate),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ?action=delete_enigma
     */
    private static function handleDeleteEnigma(array $params): void
    {
        $user = self::$userInfo;
        $e2Id = (int)($params['enigma_id'] ?? $params['id'] ?? 0);

        $e2 = EnigmaService::getById($e2Id);
        if (!$e2 || !in_array((int)$e2['user']['member_id'], $user['reports'], true)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Enigma2 device not found or unauthorized.'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }

        EnigmaService::deleteDevice($e2Id);

        echo json_encode([
            'success'   => true,
            'enigma_id' => $e2Id,
            'message'   => 'Enigma2 device deleted successfully.',
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
}

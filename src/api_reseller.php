<?php

declare(strict_types=1);

use XcVm\Public\Controllers\Reseller\ResellerJsonApiController;

/**
 * api_reseller.php — Standalone Native JSON REST API Bridge for Xtream UI / XC_VM Reseller Portal.
 */

if (!defined('MAIN_HOME')) {
    define('MAIN_HOME', __DIR__ . '/');
}

require_once MAIN_HOME . 'bootstrap.php';
\XC_Bootstrap::boot(\XC_Bootstrap::CONTEXT_ADMIN, ['process' => 'XC_VM[ResellerAPI]']);

$controller = new ResellerJsonApiController();
$controller->index();

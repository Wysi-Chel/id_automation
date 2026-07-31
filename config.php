<?php
declare(strict_types=1);

/**
 * Root configuration entry point.
 *
 * The application configuration lives in config/config.php. Keeping this
 * small bootstrap file allows scripts and deployment tools that expect a
 * root-level config.php to load the same settings without duplicating them.
 */
require_once __DIR__ . '/config/config.php';

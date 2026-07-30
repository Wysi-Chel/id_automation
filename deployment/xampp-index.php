<?php

declare(strict_types=1);

$remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$localAddresses = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];

if (!in_array($remoteAddress, $localAddresses, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    exit('Directory browsing is not allowed.');
}

header('Location: /micei_mis/', true, 302);
exit;

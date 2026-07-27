<?php
declare(strict_types=1);

const APP_NAME = 'Automated Employee ID Maker';
const COMPANY_NAME = 'MINDANAO INTEGRATED COMMERCIAL ENTERPRISES INCORPORATED';
const COMPANY_ADDRESS = 'National Highway, Brgy. City Heights, General Santos City';
const PRESIDENT_NAME = 'NEREO PLACIDO G. REGOLLO, JR.';

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'automated_id_maker';
const DB_USER = 'root';
const DB_PASS = '';

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

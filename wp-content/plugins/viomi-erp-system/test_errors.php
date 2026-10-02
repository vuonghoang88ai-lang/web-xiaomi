<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'mi-erp-system.php';
    echo "NO ERRORS";
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}

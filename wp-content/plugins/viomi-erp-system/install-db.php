<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    require_once '../../../wp-load.php';
    require_once 'mi-erp-system.php';
    Mi_ERP_Installer::install();
    echo 'Install OK';
} catch (Throwable $e) {
    echo $e->getMessage();
}

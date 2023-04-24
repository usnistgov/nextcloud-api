<?php
/**
 * Generic File Manager API entry point
 * See "/Controller/FunctionController.php" for Expected API endpoints
 */
use NamespaceFunction\FunctionController;

require __DIR__ . "/inc/bootstrap.php";
require_once __DIR__ . "/Controller/FunctionController.php";


$objFeedController = new FunctionController();
$objFeedController->controller();
?>

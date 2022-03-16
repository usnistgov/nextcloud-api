<?php
/**
 * Generic File Manager API entry point
 * See "/Controller/FunctionController.php" for Expected API endpoints
 */
require __DIR__ . "/inc/bootstrap.php";

$objFeedController = new FunctionController();
$objFeedController->controller();
?>

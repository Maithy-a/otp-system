<?php
ob_start();
session_start();
session_unset();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;
?>
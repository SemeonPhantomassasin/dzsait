<?php
require_once __DIR__ . '/inc/functions.php';
$tab = request_tab_id();
tab_logout();
header('Location: /' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
exit;


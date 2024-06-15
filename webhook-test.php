<?php
$log_file_path = __DIR__ . '/simple_webhook_log.txt';
$log_file = fopen($log_file_path, 'a');
if ($log_file) {
    fwrite($log_file, "Plain PHP Webhook Handler Triggered at " . date('Y-m-d H:i:s') . "\n");
    fclose($log_file);
} else {
    error_log("Failed to open log file at $log_file_path");
}

file_put_contents($log_file_path, "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_post_data = file_get_contents('php://input');
    if ($raw_post_data !== false) {
        file_put_contents($log_file_path, "Raw POST data: " . $raw_post_data . "\n", FILE_APPEND);
    } else {
        file_put_contents($log_file_path, "Failed to get raw post data\n", FILE_APPEND);
    }
}
?>

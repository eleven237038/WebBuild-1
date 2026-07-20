<?php
require __DIR__ . '/admin/config.php';
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($mysqli->connect_errno) { die("connect fail: " . $mysqli->connect_error . "\n"); }
$mysqli->set_charset('utf8');
$tbl = DB_PREFIX . 'custom_tag';
$res = $mysqli->query("SHOW COLUMNS FROM `$tbl` LIKE 'show_in_list'");
if ($res && $res->num_rows > 0) {
	echo "show_in_list already exists - skip\n";
} else {
	$ok = $mysqli->query("ALTER TABLE `$tbl` ADD COLUMN show_in_list TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
	echo $ok ? "show_in_list added\n" : ("ALTER failed: " . $mysqli->error . "\n");
}
if ($res) $res->close();
$mysqli->close();

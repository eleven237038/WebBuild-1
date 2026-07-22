<?php
$__t0 = microtime(true);
// Version
define('VERSION', '3.8.0.0');
define('OCTYPE', 'FREE');
define('BUILD', '20220308');

// Configuration
if (is_file('config.php')) {
    require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
    header('Location: install/index.php');
    exit;
}

// VirtualQMOD
require_once('./vqmod/vqmod.php');
VQMod::bootup();
$__t1 = microtime(true);

// VQMODDED Startup
require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));
$__t2 = microtime(true);

start('catalog');
$__t3 = microtime(true);
@file_put_contents(__DIR__ . '/timing.log', sprintf("vqmod_boot:%.4f startup:%.4f framework(dispatch+render):%.4f total:%.4f\n", $__t1 - $__t0, $__t2 - $__t1, $__t3 - $__t2, $__t3 - $__t0), FILE_APPEND);

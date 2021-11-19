<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$_CONFIG['dbms'] = 'mysql';                           // on which system your datbase is running (currently only mysql available)
$_CONFIG['dbcharset'] = 'utf8mb4';                    // database charset
$_CONFIG['dbhost'] = 'wp099.webpack.hosteurope.de';                     // hostname for your database
$_CONFIG['dbname'] = 'db1064847-test2';                         // name of your database
$_CONFIG['dbuser']= 'db1064847-test2';                          // user for your database
$_CONFIG['dbpasswd'] = '6q%3K&a+-6fD';                       // password for your database
$_CONFIG['key'] = '940772502731818414639013526449450'; // for encryption of the session data, change this to whatever number you want ( recommended: 32 digits )
$_CONFIG['table_prefix'] = 'demo_';                     // prefix for the tables in the database
$_CONFIG['site_hosts'] = array('testedwin2.q2e.eu' => 1);
$_CONFIG['protocol'] = 'http://';

// E_ALL ( php 5.3 ),
// E_ALL ^ E_STRICT ( >= php 5.4 to ignore "Strict standards" error output )
// E_ALL ^ E_STRICT ^ E_DEPRECATED( >= php 5.5 to ignore "Deprecated" warnings - EDWIN uses many functions, that are deprecated in php 5.5 )
// E_ALL ^ E_DEPRECATED ( >= php 7.0 as E_STRICT has no influence any more http://php.net/manual/de/migration70.incompatible.php#migration70.incompatible.error-handling.strict )
//ini_set('display_errors', 1);
$_CONFIG['DEBUG_PHP'] = E_ALL;
$_CONFIG['DEBUG_TPL'] = false;
$_CONFIG['DEBUG_SQL'] = false; // bottom strict backtrace | bottom strict backtrace ajax | plain live
// if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
//   $_CONFIG['DEBUG_SQL'] = false; // disable completely on xmlhttprequest
// }

// ACHTUNG: Komprimierte Ressourcen sind in der Spielwiese nicht verfügbar

$_CONFIG['m_live_mode'] = false;
// Activate dev modus only for Q2E internal network
// $_CONFIG['m_live_mode'] = isset($_SERVER['REMOTE_ADDR']) && strpos($_SERVER['REMOTE_ADDR'], '172.3') !== false ? false : true;

$_CONFIG['m_use_compressed_lang_file'] = false;
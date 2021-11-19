<?php

/* -----------------------------------------------------------------------------
 | Use as a draft for your project's config.live.php
   -------------------------------------------------------------------------- */

$_CONFIG['dbhost'] = 'localhost';
$_CONFIG['dbname'] = 'edwin';
$_CONFIG['dbuser']= 'edwin';
$_CONFIG['dbpasswd'] = 'edwin';
$_CONFIG['site_hosts'] = array ('edwin.test' => 1);
$_CONFIG['site_google_analytics_tracking_code'] = array(1 => '');
$_CONFIG['site_google_site_verification_code'] = array(1 => '');
$_CONFIG['sender_system_mailbox_address'] = null;

// Set this configuration, if you encounter issues with automatic root site url
// generation on some production systems (the image path would not be
// correct in this case):
// $_CONFIG['root_path'] = 'http://www.domain.tld/';

$_CONFIG['m_robots_txt_allow'] = true;

$_CONFIG['m_debug'] = false;

//ini_set('display_errors', 1);
$_CONFIG['DEBUG_PHP'] = false;
$_CONFIG['DEBUG_TPL'] = false;
$_CONFIG['DEBUG_SQL'] = false;

$_CONFIG['m_live_mode'] = true;
$_CONFIG['m_use_compressed_lang_file'] = true;

$_CONFIG['ip_block_time'] = 15;

//ModuleForm
$_CONFIG['fo_ip_block_time'] = 15;

//ModuleSitemapNavMain
$_CONFIG['sn_cache_navigation'] = true;

//ModuleSitemapNavMainMobile
$_CONFIG['sx_cache_navigation'] = true;
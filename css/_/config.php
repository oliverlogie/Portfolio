<?php

// -----------------------------------------------------------------------------
// These configuration values may be overridden in config.live.php
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
//
// REQUIRED
// config settings that are required an should be set
//
// -----------------------------------------------------------------------------

// Settings that are overridden within config.live.php

$_CONFIG['dbms'] = 'mysql';                           // on which system your datbase is running (currently only mysql available)
$_CONFIG['dbcharset'] = 'utf8mb4';                    // database charset
$_CONFIG['dbhost'] = 'localhost';                     // hostname for your database
$_CONFIG['dbname'] = 'edwin-demo';                         // name of your database
$_CONFIG['dbuser']= 'edwin-demo';                          // user for your database
$_CONFIG['dbpasswd'] = 'edwin-demo';                       // password for your database
$_CONFIG['key'] = '94077250273181841463901352644945'; // for encryption of the session data, change this to whatever number you want ( recommended: 32 digits )
$_CONFIG['table_prefix'] = 'demo_';                     // prefix for the tables in the database
$_CONFIG['site_hosts'] = array('dev.q2e.at/q2e-edwin-demo' => 1);
$_CONFIG['site_google_analytics_tracking_code'] = array(1 => '');
$_CONFIG['site_google_site_verification_code'] = array(1 => '');
$_CONFIG['m_mail_sender_label'] = array(0 => 'Q2E Online-Agentur <no-reply@q2e.at>');
$_CONFIG['sender_system_mailbox_address'] = null;
$_CONFIG['protocol'] = 'https://';

// E_ALL ( php 5.3 ),
// E_ALL ^ E_STRICT ( >= php 5.4 to ignore "Strict standards" error output )
// E_ALL ^ E_STRICT ^ E_DEPRECATED( >= php 5.5 to ignore "Deprecated" warnings - EDWIN uses many functions, that are deprecated in php 5.5 )
// E_ALL ^ E_DEPRECATED ( >= php 7.0 as E_STRICT has no influence any more http://php.net/manual/de/migration70.incompatible.php#migration70.incompatible.error-handling.strict )
//ini_set('display_errors', 1);
$_CONFIG['DEBUG_PHP'] = false;
$_CONFIG['DEBUG_TPL'] = false;
$_CONFIG['DEBUG_SQL'] = false; // bottom strict backtrace | bottom strict backtrace ajax | plain live
// if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
//   $_CONFIG['DEBUG_SQL'] = false; // disable completely on xmlhttprequest
// }

$_CONFIG['m_live_mode'] = true;
// Activate dev modus only for Q2E internal network
// $_CONFIG['m_live_mode'] = isset($_SERVER['REMOTE_ADDR']) && strpos($_SERVER['REMOTE_ADDR'], '172.3') !== false ? false : true;

$_CONFIG['m_use_compressed_lang_file'] = false;

// $_CONFIG['m_debug'] = isset($_SERVER['REMOTE_ADDR']) &&
//   in_array($_SERVER['REMOTE_ADDR'], array('172.30.10.187', '172.30.10.185')); // JUA, ULB

$_CONFIG['ip_block_time'] = 0;

// settings that are NOT overridden within config.live.php

$_CONFIG['m_cache_resource_version'] = '201903201106';

$_CONFIG["print_version_available"] = array(  );
$_CONFIG["content_additional_main"] = array('main_content_left_form' => '', 'main_content_right' => '', 'main_content_suchet' => '');
$_CONFIG['m_language_code'] = array('german' => 'de_DE', 'english' => 'en_EN');

$_CONFIG["m_nv_home_disabled"] = true;
$_CONFIG['m_session_name_backend'] = 'edw_be_' . $_CONFIG['table_prefix']; // unique backend session id
$_CONFIG['m_client_uploads_expiration'] = 0; // Do not delete client uploads (may possible via ModuleForm)

$_CONFIG['m_logging'] = array(
  // Automatisches Logging für htmlMimeMail5 aktivieren
  'htmlMimeMail5' => array(
    'enabled' => true, // Achtung: abhängig von Core\Logging\Simple\Service > enabled: true.
    'level' => 'info', // Setzt den Log Level der Log Einträge, die von htmlMimeMail5 erzeugt werden
  ),
  // Konfiguration des Logging Services der Applikation
  'Core\Logging\Simple\Service' => array(
    'enabled' => true,
    'loggers' => array(
      'Core\Logging\Simple\Loggers\DbLogger' => array(
        'min_level' => 'info', // Log Einträge unter diesem minimalen Level ( hier 'debug') werden ignoriert
      ),
    ),
  ),
);

$_CONFIG['m_output_filters'] = array();
$_CONFIG['m_output_filters'][] = function($html) {

  //
  // in live mode:
  // parse prod/_functions.js file inline
  //
  if (ConfigHelper::get('m_live_mode')) {

    if (is_file(base_path() . 'prog/_functions.js')) {
      $html = str_replace('{main_live_mode_assets_js_functions}', sprintf('<script>%s</script>', file_get_contents(base_path() . 'prog/_functions.js')), $html);
    }
    else {
      throw new Exception("Missing JavaScript file 'prog/_functions.js'. Please make sure to minify ressources or disable the output filter for {main_live_mode_assets_js_functions}.");
    }

  }

  return $html;
};
// replace <b> tags by <strong>
$_CONFIG['m_output_filters'][] = array(new \Core\Services\Output\Filters\RegexOutputFilter('/<b>((?:(?!<b>).)*)<\/b>/ui', '<strong>\1</strong>'), 'filter');
// replace <i> tags by <em>
$_CONFIG['m_output_filters'][] = array(new \Core\Services\Output\Filters\RegexOutputFilter('/<i>((?:(?!<i>).)*)<\/i>/ui', '<em>\1</em>'), 'filter');
// remove empty <h*> tags
$_CONFIG['m_output_filters'][] = array(new \Core\Services\Output\Filters\RegexOutputFilter('/<h[^>]*>([^\S]*)<\/h[1-9]?>/ui', ''), 'filter');

// -----------------------------------------------------------------------------
//
// COMMON
// global configuration values, that do not refer to content items or modules only
//
// -----------------------------------------------------------------------------

$_CONFIG['si_mobile_buttons'][0] = array(
  'mobile' => array('template' => '<div class="si-mobile-buttons__icon si-mobile-buttons__icon--mobile"><div class="c-btn si-mobile_buttons__btn"><a href="tel:%s" class="fa fa-mobile-phone"></a></div></div>'),
  'phone'  => array('template' => '<div class="si-mobile-buttons__icon si-mobile-buttons__icon--tel"><div class="c-btn si-mobile_buttons__btn"><a href="tel:%s" class="fa fa-phone"></a></div></div>'),
  'email'  => array('template' => '<div class="si-mobile-buttons__icon si-mobile-buttons__icon--email"><div class="c-btn si-mobile_buttons__btn"><a href="mailto:%s" class="fa fa-envelope"></a></div></div>'),
  'maps'   => array('template' => '<div class="si-mobile-buttons__icon si-mobile-buttons__icon--map"><div class="c-btn si-mobile_buttons__btn"><a href="%s" class="fa fa-map-marker"></a></div></div>'),
);

$_CONFIG["lo_max_levels"] = array(
  0 => array(
    'main' => 2, 'footer'=> 1, 'hidden' => 1, 'login' => 0, 'pages' => 0, 'user' => 0,
  )
);

$_CONFIG["lo_max_items"] = array(
  0 => array(
    'main' => array(1 => 7, 2 => 10),
    'footer' => array(1 => 3),
    'hidden' => array(1 => 10),
    'login' => array(),
    'pages' => array(),
    'user' => array(),
  )
);

$_CONFIG["lo_excluded_contenttypes"] = array(
  0 => array(
    'main'  => array(),
    'footer'=> array(3, 75, 76, 77, 78, 79, 80),
    'hidden' => array(),
    'login' => array(),
    'pages' => array(76),
    'user' => array(77, 78),
  )
);

$_CONFIG["lo_allow_imageboxes_at_level"] = array(
  0 => array(
    'main'  => array(0,1),
    'footer'=> array(0),
    'hidden' => array(0),
    'login' => array(0),
    'pages' => array(0),
    'user' => array(0),
  )
);

// -----------------------------------------------------------------------------
//
// CONTENT_TYPES
// content type specific configuration values
//
// -----------------------------------------------------------------------------

// ContentItemBG
$_CONFIG["bg_galleryimages_per_page"] =21;

// ContentItemIB
$_CONFIG["ib_level_data_active"] = true;
$_CONFIG['ib_shorttext_maxlength'] = 200;

// ContentItemPB
$_CONFIG['pb_level_data_active'] = true;
$_CONFIG['pb_results_per_page'] = 4;

// ContentItemQP
$_CONFIG["qp_ignore_empty_statements"] =  false;

// -----------------------------------------------------------------------------
//
// MODULES
// module specific configuration values
//
// -----------------------------------------------------------------------------

// ModuleSidebox
$_CONFIG['sb_max_sideboxes'] = 2;

// ModuleProductBoxTeaser
$_CONFIG['px_show_on_pages'] = array(0 => array('/'));

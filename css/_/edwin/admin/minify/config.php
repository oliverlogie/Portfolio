<?php

/* do not change **************************************************************/
//error_reporting(E_ALL);

include '../../includes/bootstrap.php';

define ('__SITE_PATH', realpath('../../../') . '/');
define ('__LIB_DIR', $_CONFIG['minify_library']);
define ('__BACKEND_THEME_DIR', __SITE_PATH . 'edwin/' . $_CONFIG['m_backend_theme']);
define ('__BACKEND_RELATIVE_THEME_DIR',  '../../' . $_CONFIG['m_backend_theme']);
/******************************************************************************/

/*******************************************************************************

Example filepaths:

- __SITE_PATH . 'css/pn_main0.css' // FE - CSS File
- __SITE_PATH . 'prog/date.js'     // FE - JS File

*******************************************************************************/

// $actions contains all files for css / js project build
// Modify ressources here:
$actions = array(
  'css' => array(
    // default
    __SITE_PATH . 'tps/css/_tps.css' => array(
      __SITE_PATH . 'tps/css/bootstrap-4.4.1/custom.css',
      __SITE_PATH . 'tps/css/swiper.min.css',
      __SITE_PATH . 'tps/css/magnific-popup.css',
      __SITE_PATH . 'tps/css/font-awesome.css',
      __SITE_PATH . 'tps/css/cookieconsent.min.css',
    ),
    __SITE_PATH . 'css/_styles_1.css' => array(
      __SITE_PATH . 'css/styles_1.less',
    ),
    // backend
    // __BACKEND_THEME_DIR . 'tps/css/_tps.css' => array(
    //   __BACKEND_THEME_DIR . 'tps/css/bootstrap.min.css',
    //   __BACKEND_THEME_DIR . 'tps/css/jquery-ui.min.css',
    //   __BACKEND_THEME_DIR . 'tps/css/bootstrap-datetimepicker.min.css',
    //   __BACKEND_THEME_DIR . 'tps/css/magnific-popup.css',
    //   __BACKEND_THEME_DIR . 'tps/css/select2.css',
    //   __BACKEND_THEME_DIR . 'tps/css/select2-bootstrap.css',
    //   __BACKEND_THEME_DIR . 'tps/css/q2e-iconfont.css',
    // ),
    // __BACKEND_THEME_DIR . 'css/_styles.css' => array(
    //   __BACKEND_THEME_DIR . 'css/styles.less',
    // ),
  ),
  'js' => array(
    // default
    __SITE_PATH . 'tps/js/_tps.js' => array(
      __SITE_PATH . 'tps/js/jquery-3.3.1.js',
      __SITE_PATH . 'tps/js/bootstrap-4.4.1/custom.js',
      __SITE_PATH . 'tps/js/swiper.min.js',
      __SITE_PATH . 'tps/js/jquery.magnific-popup.js',
      __SITE_PATH . 'tps/js/jquery.scrollTo.min.js',
      __SITE_PATH . 'tps/js/jquery.localscroll.min.js',
      __SITE_PATH . 'tps/js/jquery.touchwipe.min.js',
      __SITE_PATH . 'tps/js/cookieconsent.min.js',
      __SITE_PATH . 'tps/js/jquery.fitvids.js',
      __SITE_PATH . 'tps/js/jquery.lazyload.js',
      //__SITE_PATH . 'tps/js/store.min.js', // Only required by ModulePopUp
      __SITE_PATH . 'tps/js/imagesloaded.pkgd.min.js', // Only required by ContentItemPB
    ),
    __SITE_PATH . 'prog/_q2e.js' => array(
      __SITE_PATH . 'prog/script.js',
      // __SITE_PATH . 'prog/tracking.js',
    ),
    // _functions.js needs to be included in the document head, otherwise
    // the email decryption won't work.
    __SITE_PATH . 'prog/_functions.js' => array(
      __SITE_PATH . 'prog/functions.js',
    ),
    // backend
    // __BACKEND_THEME_DIR . 'tps/js/_tps.js' => array(
    //   __BACKEND_THEME_DIR . 'tps/js/jquery.min.js',
    //   __BACKEND_THEME_DIR . 'tps/js/bootstrap.min.js',
    //   __BACKEND_THEME_DIR . 'tps/js/jquery-ui.min.js',
    //   __BACKEND_THEME_DIR . 'tps/js/moment/moment.js',
    //   __BACKEND_THEME_DIR . 'tps/js/moment/locale/de.js',
    //   __BACKEND_THEME_DIR . 'tps/js/bootstrap-datetimepicker.min.js',
    //   __BACKEND_THEME_DIR . 'tps/js/jquery.magnific-popup.min.js',
    //   __BACKEND_THEME_DIR . 'tps/js/select2/select2.min.js',
    //   __BACKEND_THEME_DIR . 'tps/js/select2/select2_locale_de.js',
    //   __BACKEND_THEME_DIR . 'tps/js/browser-report.js',
    // ),
    // __BACKEND_THEME_DIR . 'tps/js/tiny-mce/utils/_utils.js' => array(
    //   __BACKEND_THEME_DIR . 'tps/js/tiny-mce/utils/editable_selects.js',
    //   __BACKEND_THEME_DIR . 'tps/js/tiny-mce/utils/mctabs.js',
    //   __BACKEND_THEME_DIR . 'tps/js/tiny-mce/utils/form_utils.js',
    //   __BACKEND_THEME_DIR . 'tps/js/tiny-mce/utils/validate.js',
    // ),
    // __BACKEND_THEME_DIR . 'js/_q2e.js' => array(
    //   __BACKEND_THEME_DIR . 'js/be.js',
    // ),
  ),
);

/*******************************************************************************

DO NOT CHANGE FROM HERE

*******************************************************************************/

/* CSS / CSSMIN ***************************************************************/
$_CONFIG['m_minify']['css'] = array(
  'actions' => $actions['css'],
  'config' => array(
    'filters' => array (
      'ImportImports'                 => false,
      'RemoveComments'                => true,
      'RemoveEmptyRulesets'           => true,
      'RemoveEmptyAtBlocks'           => true,
      'ConvertLevel3AtKeyframes'      => false,
      'ConvertLevel3Properties'       => false,
      'Variables'                     => true,
      'RemoveLastDelarationSemiColon' => true
    ),
    'plugins' => array (
      'Variables'                => true,
      'ConvertFontWeight'        => false,
      'ConvertHslColors'         => false,
      'ConvertRgbColors'         => false,
      'ConvertNamedColors'       => false,
      'CompressColorValues'      => false,
      'CompressUnitValues'       => false,
      'CompressExpressionValues' => false
    )
  ),
);

/* JS / JSMIN *****************************************************************/
$_CONFIG['m_minify']['js'] = array(
  'actions' => $actions['js'],
);

/* CSS / Yuicompressor ********************************************************/
$_CONFIG['m_yuiminify']['css'] = array(
  'actions' => $actions['css'],
  /* do not change */
  'config' => array(
      'jarFile' => __LIB_DIR . 'tps/yuicompressor.jar',
      'tempDir'  => __LIB_DIR . 'tmp/',
  ),
);

/* JS / Yuicompressor *********************************************************/
$_CONFIG['m_yuiminify']['js'] = array(
  'actions' => $actions['js'],
  /* do not change */
  'config' => array(
      'jarFile' => __LIB_DIR . 'tps/yuicompressor.jar',
      'tempDir'  => __LIB_DIR . 'tmp/',
  ),
);

/* JS / JShrink ***************************************************************/
$_CONFIG['m_jshrink']['js'] = array(
  'actions' => $actions['js'],
  /* do not change */
  'config' => array(
    'flaggedComments' => false, // Disable YUI style comment preservation.
  ),
);
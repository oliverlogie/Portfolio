<?php

  /**
   * Main Functions
   *
   * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Check for valid ZIP                                                                     //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function isPLZ ($str) {
    $help=1;
    for ($i=0;$i<mb_strlen($str);$i++) {
      $teil=mb_substr($str,$i,1);
      if (!($teil>='a' && $teil<='z' || $teil>='A' && $teil<='Z' || $teil=='-' || $teil>='0' && $teil<='9'))
        $help=0;
    }
    return $help;
  }


  /**
   * Check if a string is a number (contains only digits)
   *
   * @param string $str
   */
  function isNumber ($str) {

    if (!$str) {
      return false;
    }

    $digits = array('0','1','2','3','4','5','6','7','8','9');

    for ($i=0; $i < mb_strlen($str); $i++)
    {
      $char = mb_substr($str, $i, 1);
      if (!in_array($char, $digits)) {
       return false;
      }
    }

    return true;
  }

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Generate a Password Code                                                                //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function generate_code($length = 8)
  {
    $chars = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9");

    $max_elements = count($chars) - 1;
    srand ((double)microtime()*1000000);

    $key = "";
    for ($i = 0; $i < $length; $i++)
     $key .= $chars[rand(0, $max_elements)];

    return $key;
  }

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Parse Content for Output                                                                //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function parseOutput ($text,$output_type=0)
  {
    if (!$output_type || ($output_type == 2 && !ConfigHelper::get('be_allow_html_in_titles'))) {
      $text = htmlentities($text, ENT_QUOTES, ConfigHelper::get('charset'));
    }
    // get html entities for doublequotes inside HTML tag 'value' attributes
    else if ($output_type == 2) {
      $text = str_replace('"', "&quot;", $text);
    }
    // return plain text ( except quotes ) for HTML tag 'title' attributes
    else if ($output_type == 3) {
      $text = strip_tags($text);
      $text = htmlentities($text, ENT_QUOTES, ConfigHelper::get('charset'));
    }
    else if ($output_type == 99) {
      $text = str_replace(".", ",", $text);
    }
    else
    {
      $text = str_replace("ä","&auml;",$text);
      $text = str_replace("Ä","&Auml;",$text);
      $text = str_replace("ö","&ouml;",$text);
      $text = str_replace("Ö","&Ouml;",$text);
      $text = str_replace("ü","&uuml;",$text);
      $text = str_replace("Ü","&Uuml;",$text);
      $text = str_replace("ß","&szlig;",$text);
    }

    return $text;
  }

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Parse Content for Mail-Output                                                           //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function parseMailOutput ($text,$html)
  {
    if (!$html)
      $text = html_entity_decode(strip_tags($text), ENT_COMPAT, ConfigHelper::get('charset'));
    else
      $text = strip_tags($text);

    return $text;
  }

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Parse Content for CSV Output                                                            //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function parseCSVOutput ($text)
  {
    $text = html_entity_decode(strip_tags($text), ENT_COMPAT, ConfigHelper::get('charset'));

    return $text;
  }

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Parse Input Data for Processing internally                                              //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function parseInput($text, $output_type = 0)
  {
    switch($output_type){
      default: case 0: // allow no html
        $text = strip_tags($text);
        $text = str_replace("'","´",$text);
        break;

      case 1: // allow maximal html, used for content-texts
        // fix ie data from wysiwyg editor
        $text = str_ireplace("<strong>","<b>",$text);
        $text = str_ireplace("</strong>","</b>",$text);
        $text = str_ireplace("<em>","<i>",$text);
        $text = str_ireplace("</em>","</i>",$text);

        // fix ' for editor
        $text = str_replace("'","&prime;",$text);

        $allowed = ConfigHelper::get('be_allowed_html_level1');
        $text = strip_tags($text, $allowed);
        break;

      case 2: // allow minimal html, used for content-titles
        if (ConfigHelper::get('be_allow_html_in_titles')) {
          $allowed = ConfigHelper::get('be_allowed_html_level2');
          $text = strip_tags($text, $allowed);
        }
        else
          $text = strip_tags($text);
        $text = str_replace("'","´",$text);
        break;

      case 3: // allow minimal html, used for manual short-texts
        $allowed = ConfigHelper::get('be_allowed_html_level3');
        $text = strip_tags($text, $allowed);
        break;

      case 4: // allow minimal html, used for intlinks, extlinks and downloads
        $allowed = ConfigHelper::get('be_allowed_html_level4');
        $text = strip_tags($text, $allowed);
        break;

      case 99: // replace , with . for float values
        $text = str_replace(",", ".", $text);
        break;
    }
    return $text;
  }

  /////////////////////////////////////////////////////////////////////////////////////////////
  // Create a Page Navigation                                                                //
  /////////////////////////////////////////////////////////////////////////////////////////////
  function create_page_navigation ($count,$page,$area,$per_page,$lang_current,$lang_other,$page_url,$linktype = "page_number", $lang_first="", $lang_last=""){
    global $_LANG;

    $page_navigation = "";
    $temp = "";
    $lang_current = ($lang_current) ? $lang_current : $_LANG['global_results_showpage_current'];
    $lang_other = ($lang_other) ? $lang_other : $_LANG['global_results_showpage_other'];
    $lang_first = ($lang_first) ? $lang_first : $_LANG['global_results_showpage_first'];
    $lang_last = ($lang_last) ? $lang_last : $_LANG['global_results_showpage_last'];

    // more than one page
    if ($count > $per_page)
    {
      // add first page link
      $temp = sprintf($lang_first, $page_url.'1');
      $parts = intval( $count / $per_page ) + 1;

      $right_limit = $page+$area;
      $left_limit = $page-$area;
      if ($right_limit>$parts)
        $left_limit -= $right_limit-$parts;
      if ($left_limit<0)
        $right_limit += abs($left_limit)+1;

      if ($count%$per_page == 0)
        $parts = $parts - 1;

      $i=1;
      while ($i <= $parts ){ // create page links
        if ($i <= $right_limit && $i >= $left_limit){
          if ($i == $page)
            $temp .= sprintf($lang_current,$i);
          else{
            if ($linktype == "page_number")
              $temp .= sprintf($lang_other,$page_url.$i,$i);
            else if ($linktype == "item_number")
              $temp .= sprintf($lang_other,$page_url.((($i-1)*$per_page)+1),$i);
          }
        }
        $i++;
      }
      // add last page link
      $temp .= sprintf($lang_last, $page_url.($i-1));

    }
    $page_navigation = $temp;

    return $page_navigation;
  }

/**
 * Deletes a file and avoids errors by checking if the file exists.
 *
 * @param string $filePath
 *        the path to the file that should be deleted
 * @return boolean
 *        true if the file was deleted or didn't exist, false if it could not be deleted.
 */
function unlinkIfExists($filePath) {
  return is_file($filePath) ? @unlink($filePath) : true;
}

/**
 * Determine file name without extension from basename. (utility function for PHP < 5.2.0)
 *
 * @param string $basename
 *        the basename of a file
 * @return string
 *        the file name without extension
 */
function getFilenameFromBasename($basename) {
  $filename = $basename;
  if (mb_strstr($basename, ".")) {
    $filename = mb_substr($basename, 0, mb_strrpos($basename, "."));
  }
  return $filename;
}

/**
 * Returns the first argument that isn't equivalent to false.
 *
 * @param mixed $var
 *        Any variable that will be tested for being non-false.
 * @param mixed $var,...
 *        An unlimited number of additional variables.
 * @return mixed
 *        The first non-false argument.
 */
function coalesce($var) {
  $args = func_get_args();
  if (!$args) {
    return null;
  }

  foreach ($args as $arg) {
    if ($arg) {
      return $arg;
    }
  }
  return $args[0];
}

/**
 * This function transforms the php.ini notation for sizes (i.e. '2M') to an integer.
 *
 * @param string $size
 *        A file size in php.ini notation.
 * @return integer
 *        The amount of bytes that the parameter $size equates to.
 */
function parsePhpIniSize($size)
{
  // separate number and unit
  $size = trim($size);
  $number = (int)mb_substr($size, 0, -1);
  $unit = mb_substr($size, -1);

  // there must not be breaks between the cases
  switch (mb_strtoupper($unit)) {
    case 'G':
      $number *= 1024;
    case 'M':
      $number *= 1024;
    case 'K':
      $number *= 1024;
  }

  return $number;
}

/**
 * Returns the probable maximum allowed file size for an upload.
 *
 * The return value is the minimum value among these sources:
 * 1. configuration option 'post_max_size'
 * 2. configuration option 'upload_max_filesize'
 * 3. POST variable 'MAX_FILE_SIZE'
 *
 * @return integer
 *        The maximum allowed file size for an upload (in bytes).
 */
function getUploadLimit()
{
  $limits[] = parsePhpIniSize(ini_get('post_max_size'));
  $limits[] = parsePhpIniSize(ini_get('upload_max_filesize'));
  if (isset($_POST['MAX_FILE_SIZE'])) {
    $limits[] = (int)$_POST['MAX_FILE_SIZE'];
  }

  return min($limits);
}

/**
 * Converts a given file size (in bytes) into a user friendly format.
 *

 * @param int $bytes
 *        A file size in bytes.
 * @return string
 *        A user friendly representation of the file size.
 */
function formatFileSize($bytes)
{
  $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  $exp = 0;
  if ($bytes) {
    $exp = (int)floor(log($bytes) / log(1024));
  }

  $format = '%.2f';
  if (!$exp) {
    $format = '%d';
  }
  return sprintf($format, ($bytes / pow(1024, floor($exp)))) . ' ' . $units[$exp];
}

if (!function_exists('mb_substr_replace'))
{
  /**
  * UTF-8 aware substr_replace.
  * @see http://www.php.net/substr_replace
  * @see http://hg.joomla.org/joomla-platform/src/247ba8d88526096394c44dee8b9e5f4c7e315afc/libraries/phputf8/substr_replace.php?at=default
  */
  function mb_substr_replace($str, $repl, $start , $length = NULL ) {
    preg_match_all('/./us', $str, $ar);
    preg_match_all('/./us', $repl, $rar);
    if ($length === NULL) {
        $length = mb_strlen($str);
    }
    array_splice( $ar[0], $start, $length, $rar[0] );
    return join('',$ar[0]);
  }
}

/**
 * Converts a camel case string into a lowercase
 * string with underscores.
 * Useful to convert EDWIN SQL column names to
 * beautiful template names.
 *
 * @param string $string
 *        The string to convert.
 * @return string
 *         The converted lowercase string.
 */
function camelToUnderscore($string)
{
  $result = mb_strtolower(preg_replace('/([a-z])([A-Z])/u', '$1_$2', $string));

  return $result;
}

/**
 * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName).
 * @param string $str
 *        String in underscore format.
 * @param bool $capitalise_first_char
 *        If true, capitalise the first char in $str.
 * @return string
 *         Translated into camel caps.
 */
function underscoreToCamel($str, $capitalise_first_char = false) {
  if ($capitalise_first_char) {
    $str[0] = mb_strtoupper($str[0]);
  }

  return preg_replace_callback('/_([a-z])/u', function ($c) {
    return mb_strtoupper($c[1]);
  }, $str);
}

/**
 * lcfirst functions exists only in PHP version 5.3 or newer.
 * So create custom function if not available.
 * // TODO: remove this if PHP 5.3 is used by most web servers.
 */
if (function_exists('lcfirst') === false) {
  function lcfirst($str) {
      $str[0] = mb_strtolower($str[0]);
      return $str;
  }
}

/**
 * Gets the first key in a (possibly) associative array.
 *
 * @see https://stackoverflow.com/questions/1028668/get-first-key-in-a-possibly-associative-array
 * @param $array
 * @return int|null|string
 */
if (function_exists('array_first_key') === false) {
  function array_first_key($array)
  {
    reset($array);
    return key($array);
  }
}

/**
 * Returns the URL to the page with the specified ID.
 *
 * EDWIN Frontend + Backend
 *
 * @param int &$ID
 *        The ID of the page. Is changed to 0 if the internal link is a dead link.
 * @return string
 *        The URL to the page.
 */
function getPageUrl(&$ID)
{
  global $db;

  $navigation = Navigation::getInstance($db, ConfigHelper::get('table_prefix'));
  // If dead internal links are migrated from a previous version they link to
  // page ID 0. getPageByID() throws an exception with IDs <= 0.
  $navigationPage = null;
  if ($ID > 0) {
    $navigationPage = $navigation->getPageByID($ID);
  }

  // If the linked page does not exist anymore or if it is not visible we
  // just link to the current page.
  if (!$navigationPage || !$navigationPage->isVisible()) {
    $navigationPage = $navigation->getCurrentPage();
    $ID = 0;
  }

  // retrieving requested page / current page failed
  if (!$navigationPage) {
    return $navigation->getCurrentSite()->getUrl();
  }

  return $navigationPage->getUrl();
}

  /**
   * Returns the URL to the file with the specified type and ID.
   *
   * EDWIN Frontend + Backend
   *
   * @param string $type
   *        The type of the file (file, dlfile or centralfile).
   * @param int &$ID
   *        The ID of the file. Is changed to 0 if the file link is a dead link.
   *
   * @return array
   *         An associative array containing:
   *         "url" The URL to the file.
   *         "class" Additional link classes to add to <a> tag
   */
  function getFileUrl($type, &$ID)
  {
    global $db;
    $tablePrefix = ConfigHelper::get('table_prefix');

    $file = FileCache::getInstance($db)->getFileByIdAndType($ID, $type);
    $filePath = $file['File'] ?? null;
    $class = isset($file['Protected']) && $file['Protected'] ? 'protected' : '';

    $navigation = Navigation::getInstance($db, $tablePrefix);
    if ($filePath) {
      $fileUrl = $navigation->getCurrentSite()->getUrl() . $filePath;
    } else {
      $navigationPage = $navigation->getCurrentPage();
      $fileUrl = $navigationPage->getUrl();
      $ID = 0;
    }

    return array("url" => $fileUrl, "class" => $class);
  }

/**
 * Add special class 'nlink3a' to anchor links
 *
 * EDWIN Frontend + Backend
 *
 * @param string $text
 *        The string.
 *
 * @return string
 *         The modified (or unmodified if it contained no links) string.
 */
function parseOutputAnchorLinks($text)
{
  // Add class attribute to file links.
  $pattern = '#<a( title="[^"]*")? href="\##ui';
  $replacement = '<a\1 class="nlink3a" href="#';
  $text = preg_replace($pattern, $replacement, $text);
  return $text;
}

/**
 * Prepare external links inside a string for display.
 *
 * EDWIN Frontend + Backend
 *
 * @param string $text
 *        The string.
 * @return string
 *        The modified (or unmodified if it contained no links) string.
 */
function parseOutputExternalLinks($text)
{
  // Add class and target attribute to external links.
  $pattern = '#<a( title="[^"]*")? href="(https?://)#ui';
  $replacement = '<a\1 class="nlink3" target="_blank" href="\2';
  $text = preg_replace($pattern, $replacement, $text);

  return $text;
}

/**
 * Prepare internal links inside a string for display.
 *
 * EDWIN Frontend + Backend
 *
 * @param string $text
 *        The string.
 * @return string
 *        The modified (or unmodified if it contained no links) string.
 */
function parseOutputInternalLinks($text)
{
  // Add class attribute to internal links.
  $pattern = '#<a( title="[^"]*")? href="edwin-link://#ui';
  $replacement = '<a\1 class="nlink3i" href="edwin-link://';
  $text = preg_replace($pattern, $replacement, $text);

  $pattern = '#href="(edwin-link://internal/(\d+))"#ui';
  while (preg_match($pattern, $text, $matches)) {
    $pageID = $matches[2];

    // Determine the URL to the linked page.
    $replacement = getPageUrl($pageID);

    // Replace the found address with the encrypted address.
    $text = mb_substr_replace($text, $replacement, mb_strpos($text, $matches[1]), strlen($matches[1]));
  }

  return $text;
}

/**
 * Prepare file links inside a string for display.
 *
 * EDWIN Frontend + Backend
 *
 * @param string $text
 *        The string.
 * @return string
 *        The modified (or unmodified if it contained no links) string.
 */
function parseOutputFileLinks($text)
{
  // Add class attribute to file links.
  $pattern = '#<a( title="[^"]*")? href="edwin-file://#ui';
  $replacement = '<a\1 class="nlink3i" href="edwin-file://';
  $text = preg_replace($pattern, $replacement, $text);

  $pattern = '#<a (title=".*?" )?(class="nlink3i" )href="(edwin-file://(file|centralfile|dlfile)/(\d+))"#ui';
  while (preg_match($pattern, $text, $matches)) {
    $fileType = $matches[4];
    $fileID = (int)$matches[5];

    // Determine the URL to the linked file.
    $replacement = getFileUrl($fileType, $fileID);

    // Replace the EDWIN file link with real url to file
    $text = mb_substr_replace($text, $replacement["url"], mb_strpos($text, $matches[3]), mb_strlen($matches[3]));

    if ($replacement["class"]) { // additional file link classes
      $rep = 'class="nlink3i ' . $replacement["class"] .  '"';
      $text = mb_substr_replace($text, $rep, mb_strpos($text, $matches[2]), mb_strlen($matches[2]));
    }
  }

  return $text;
}

/**
 * Returns the custom_config.css filepath if the customer specific file is
 * available otherwise returns the custom_config_default.css file path for
 * inclusion in templates.
 *
 * @return string
 */
function custom_config_stylesheet_path()
{
  $path = 'css/custom_config_default.css';
  if (is_file(base_path() . 'edwin/css/custom_config.css')) {
    $path = 'css/custom_config.css';
  }

  return $path;
}

/**
 * Get the application base path
 *
 * @return string
 *         the base path with trailing slash
 */
function base_path()
{
  global $_CONFIG;

  return rtrim(dirname($_CONFIG['INCLUDE_DIR']), '/') . '/';
}

/**
 * Get the path to the cache folder
 *
 * @return string
 *         the cache path with trailing slash
 */
function cache_path()
{
  return base_path() . 'storage/cache/';
}

/**
 * Get the path to the storage folder
 *
 * @return string
 *         the storage path with trailing slash
 */
function storage_path()
{
  return base_path() . 'storage/';
}

/**
 * Get the path to the third party files
 *
 * @return string
 *         the tps path with trailing slash
 */
function tps_path()
{
  return base_path() . 'tps/includes/';
}

/* -----------------------------------------------------------------------------
 |
 | Url helper functions
 |
   -------------------------------------------------------------------------- */

/**
 * Returns the website root url with trailing slash
 *
 * @return string
 */
function root_url()
{
  global $_CONFIG;

  if (empty($_CONFIG['root_path'])) {

    $basePath = str_replace(DIRECTORY_SEPARATOR, '/', trim($_CONFIG['INCLUDE_DIR'], '/') . '/../');
    $basePath = trim($basePath, '/');
    $documentRoot = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['DOCUMENT_ROOT']);
    $documentRoot = trim($documentRoot, '/');

    $protocol = (    !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                  || $_SERVER['SERVER_PORT'] == 443 ) ? "https://" : "http://";

    $domainName = $_SERVER['HTTP_HOST'].'/';

    $subdir = str_replace($documentRoot, '', $basePath);
    $subdir = trim($subdir, '/');
    $subdir = $subdir ? $subdir . '/' : '';

    $_CONFIG['root_path'] = $protocol . $domainName . $subdir;
  }

  return $_CONFIG['root_path'];
}

/**
 * Returns the EDWIN CMS backend root url with trailing slash
 *
 * @return string
 */
function edwin_url()
{
  return root_url() . 'edwin/';
}

/* -----------------------------------------------------------------------------
 |
 | Miscellaneous helper functions
 |
   -------------------------------------------------------------------------- */

/**
 * Returns true if the debug mode of the application is on
 *
 * @see $_CONFIG['m_debug']
 *
 * @return bool
 */
function app_debug()
{
  global $_CONFIG;

  return (bool)$_CONFIG['m_debug'];
}

/**
 * @return \Core\Logging\Simple\AbstractLogger
 */
function app_log()
{
  return Container::make('Core\Logging\Simple\Service');
}

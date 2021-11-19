<?php

/**
 * sitemap.xml handling
 *
 * Sitemap files are created per host / domain name. If multiple websites use
 * the same domain there is only created one sitemap.xml combining all URLs.
 *
 * $LastChangedDate: 2018-08-06 09:04:35 +0200 (Mo, 06 Aug 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Benjamin Ulmer
 * @copyright (c) 2017 Q2E GmbH
 */

include 'includes/bootstrap.php';

// Include language files - use german by default and english as fallback
// Include default language file
// Include customized language file if it exists

$language = 'german';
$langFile = (ConfigHelper::get('m_use_compressed_lang_file')) ? 'compressed' : 'core';

$langDefaultLanguage = is_file("language/$language-default/lang.$langFile.php") ? $language : 'english';
$langFileDefault = "language/$langDefaultLanguage-default/lang.$langFile.php";

include $langFileDefault;

$langCustomizedLanguage = is_file("language/$language/lang.$langFile.php") ? $language : 'english';
$langFileCustomized = "language/$langCustomizedLanguage/lang.$langFile.php";

if (is_file($langFileCustomized)) {
  include $langFileCustomized ;
}

// generating XML sitemap

$xml = '';
$ids = array();

// $_SERVER['REQUEST_SCHEME'] is not reliable ( = not available on all systems )
// so we have to make a few checks to determine a request scheme
//
// @see http://php.net/manual/de/reserved.variables.server.php
//
if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME']) {
  $scheme = $_SERVER['REQUEST_SCHEME'];
}
else if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && mb_strtolower($_SERVER['HTTPS']) !== 'off') {
  $scheme = 'https';
}
else {
  $scheme = 'http';
}

$host = parse_url($scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_HOST);

// find site IDs of sites belonging to same hostname.
foreach (ConfigHelper::get('site_hosts') as $siteHost => $siteId) {
  if (mb_strpos($siteHost, $host) === 0) {
    $ids[] = $siteId;
  }
}

if ($ids) {
  try {
    $siteMap = new Sitemap(Navigation::getInstance(Container::make('db'), ConfigHelper::get('table_prefix')), $ids);
    $siteMap->setCacheDir(cache_path() . 'sitemap');
    $siteMap->setRefreshTime(ConfigHelper::get('m_sitemap_cache_refresh_time'));

    $file = $siteMap->generate()->getFilePath();
    $xml  = file_get_contents($file);
  }
  catch (Exception $e) {
    Container::make('CmsBugtracking')->track(
      $e->getMessage(),
      sprintf('[Error] sitemap.xml generation error on %s', root_url())
    );
  }
}

if ($xml) {
  // @see https://stackoverflow.com/questions/3272534/what-content-type-value-should-i-send-for-my-xml-sitemap#answer-3272572
  header('Content-Type: application/xml');
  echo $xml;
}
else {
  header('HTTP/1.0 404 Not Found');
}
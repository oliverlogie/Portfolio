<?php

/**
 * robots.txt handling
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

// default
$robots = <<<ROBOTS
User-agent: *
Disallow: /
ROBOTS;

if (file_exists(base_path() . 'robots.txt')) {
  if (is_readable(base_path() . 'robots.txt')) {
    header("Content-Type: text/plain");
    $robots = file_get_contents(base_path() . 'robots.txt');
  }
  else {
    $bugtracking = new CmsBugtracking;
    $bugtracking->mail(
      sprintf("robots.txt is not readable. Please make it readable by changing the permissions of the file.\n\n%srobots.txt", root_url()),
      'robots.txt not readable'
    );
  }
}
else if (ConfigHelper::get('m_robots_txt_allow')) {
  $rootUrl = root_url();
  $robots = <<<ROBOTS
User-agent: *
Disallow:
Sitemap: {$rootUrl}sitemap.xml
ROBOTS;
}
header("Content-Type: text/plain");
echo $robots;
<?php

  /**
   * Edwin Frontend Index
   *
   * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Frontend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  include 'includes/bootstrap.php';

  $requestInfo = GetRequestInformation();
  $request_path = $requestInfo['path'];
  $request_args = $requestInfo['arguments'];
  $request_is_page_host = $requestInfo['isPageHost'];
  ConfigHelper::set('site_id', (int)$requestInfo['site']);

  // Check for site base config overrides
  $config = ConfigHelper::get('m_config_overrides', null,  ConfigHelper::get('site_id'));
  if (is_array($config)) {
    foreach ($config as $name => $value) {
      ConfigHelper::set($name, $value);
    }
  }

  // write site language into CONFIG File
  // if language files do not exist take german as default language
  $sql = ' SELECT SID, SLanguage '
       . ' FROM ' . ConfigHelper::get('table_prefix') . 'site';
  $result = $db->query($sql);
  $languages = array();
  while ($row = $db->fetch_row($result)) {
      $languages[(int)$row['SID']] = $row['SLanguage'];
  }
  $db->free_result($result);
  ConfigHelper::set('site_languages', $languages);

  $siteLang = $languages[ConfigHelper::get('site_id')];
  $langFile = (ConfigHelper::get('m_use_compressed_lang_file')) ? 'compressed' : 'core';
  // Include default language file - english is used if there is not provided a
  // default language file for site's language
  $langDefaultLanguage = is_file("language/$siteLang-default/lang.$langFile.php") ? $siteLang : 'english';
  $langFileDefault = "language/$langDefaultLanguage-default/lang.$langFile.php";
  include($langFileDefault);

  // Include customized lang file if it exists
  $langFileCustomized = "language/$siteLang/lang.$langFile.php";
  if (is_file($langFileCustomized)) { include($langFileCustomized); }

  $tpl = Container::make('Template');
  $tpl->load_tpl('main', 'main.tpl');

  // Call a bootstrap function, that requires environment information, that is
  // available after bootstrap and additional actions only.
  $onBeforeStartRequestExecution();

/*
  $page = new ContentRequest(ConfigHelper::get('site_id'),$request_path,$request_args,$tpl,$db,ConfigHelper::get('table_prefix'));
  $page->show();

  $tpl->destroy();
  $db->close();
*/

  ob_start();
  ContentRequest::$templateRoot = $tpl->get_root();
  ContentRequest::$outputDevice = getDeviceTemplateName();
  $page = new ContentRequest(ConfigHelper::get('site_id'),$request_path,$request_args,$tpl,$db,ConfigHelper::get('table_prefix'));
  $page->show();
  $output = ob_get_contents();
  ob_end_clean();

  $host = array_flip(ConfigHelper::get('site_hosts'));
  $host = $host[ConfigHelper::get('site_id')];

  // The site host contains a virtual subdirectory, so we have to
  // fix the resource URLs, that have been created using the site host instead
  // of the website root URL
  if (mb_strlen($host) > mb_strlen($_SERVER['HTTP_HOST'])) {
    $length = mb_strlen(ConfigHelper::get('protocol'));
    $rootUrlWithoutProtocol = mb_substr(root_url(), $length);
    $search = array(
        $host . '/css',
        $host . '/img',
        $host . '/pix',
        $host . '/prog',
        $host . '/tps',
    );
    $replace = array(
        $rootUrlWithoutProtocol . 'css',
        $rootUrlWithoutProtocol . 'img',
        $rootUrlWithoutProtocol . 'pix',
        $rootUrlWithoutProtocol . 'prog',
        $rootUrlWithoutProtocol . 'tps',
    );
    $output = str_replace($search, $replace,  $output);
  }

  if ($request_is_page_host) {
    $tmp = explode('/', $request_path);
    $output = str_replace($host.'/'.$tmp[0], $host, $output);
  }

  // calls all configured output filters
  foreach (ConfigHelper::get('m_output_filters') as $filter) {
    if (!is_callable($filter)) {
      throw new Exception("Invalid output filter in 'm_output_filter'");
    }

    $output = call_user_func($filter, $output);
  }

  echo $output;

  if ($db->getLogger()) {
    $o = new \Core\Widgets\BottomOfThePageLogOutput(ConfigHelper::get('DEBUG_SQL'), $db->getLogger());

    if (   !ed_is_ajax()
        || (ed_is_ajax() && mb_stristr(ConfigHelper::get('DEBUG_SQL'), 'ajax'))
    ) {
      echo $o->getOutput();
    }
  }

  $tpl->destroy();
  $db->close();

  /**
   * Returns information about the current request
   *
   * @return array
   *         'site'       => site id,
   *         'path'       => requested page path,
   *         'isPageHost' => true | false, for page specific hosts
   *         'arguments'  => request arguments,
   */
  function getRequestInformation()
  {
    $site = 0;
    $uri = mb_substr($_SERVER['REQUEST_URI'], 1);
    $isPageHost = false;

    $siteHosts = array_flip(ConfigHelper::get('site_hosts'));
    $siteId = determineCurrentHostId($siteHosts);
    if ($siteId) {
      $site = $siteId;
      $host = $siteHosts[$siteId];
      $uri = getUriForHost($host);
    }

    // special hosts for single pages / levels are usually set for pages from
    // landingpage tree
    if (!$site && ConfigHelper::get('page_hosts')) {
      $pageHosts = array_flip(ConfigHelper::get('page_hosts'));
      $pageId = determineCurrentHostId($pageHosts);
      $pageData = getPageDataFromPageId($pageId);
      $site = $pageData['siteId'];

      if ($site) {
        $isPageHost = true;
        $host = $pageHosts[$pageId];

        // we have to redirect to valid url if the requested URI contains the
        // page path of 'host page' itself ( this occurs, whenever the EDWIN
        // CMS makes a redirect )
        $cleanedUri = removeHostPagePathFromUri($uri, $pageData['identifier']);
        if ($cleanedUri != $uri) {
          header('Location: ' . getUrl($host, $cleanedUri));
          exit;
        }
        // Only append full identifier, if this isn't a file or module-response
        // request. So the ContentRequest object is able to handle these special
        // requests, because there isn't any other path allowed in front of the
        // file or module-response requests.
        if (mb_substr($uri, 0, 6) != 'files/'
          && mb_substr($uri, 0, 19) != "index.php?page=MDL_") {
          $uri = $pageData['identifier'] . '/' . $uri;
        }
        $uriParts = getUriParts($uri);
        $path = $uriParts[0];
        redirectOnInvalidPageHostPath($path, $pageData);

        $_SERVER['REQUEST_URI'] = $uri;
        replaceSiteHost($site, $host);
      }
    }

    if (!$site) { $site = 1; }
    $uriParts = getUriParts($uri);
    $path = $uriParts[0];

    return array('site'       => $site,
                 'path'       => $path,
                 'isPageHost' => $isPageHost,
                 'arguments'  => array_slice($uriParts, 1), );
  }

  /**
   * Determines the host id from given hosts array. Expected format:
   * - key   = host id
   * - value = host
   *
   * @param array $hosts
   * @return int
   *         zero if request host does not match any of the defined hosts
   */
  function determineCurrentHostId($hosts)
  {
    $hostId = 0;
    $requestHostURI = getHostAndURI();

    foreach ($hosts as $id => $host) {
      if (   $requestHostURI == $host
          || mb_strpos($requestHostURI, "$host/") === 0
          || mb_strpos($requestHostURI, "$host?") === 0
      ) {
        if (!$hostId || mb_strlen($hosts[$hostId]) < mb_strlen($host)) {
          $hostId = $id;
        }
      }
    }
    return $hostId;
  }

  /**
   * @return string The request host and uri
   */
  function getHostAndURI()
  {
    // $_SERVER["HTTP_HOST"] does contain the port, so this should always work
    // NOTE: When the default port 80 is specified either only in the request or
    //       only in the configuration, problems will arise
    return $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  }

  /**
   * Returns the uri for given host and the requests host and uri. This is
   * required, because EDWIN CMS site hosts possibly look like:
   * - edwin.test
   * - edwin.test/en => the /en part has to be removed from request URI
   *
   * @param string $host
   *        the determined page host from config
   * @return string
   */
  function getUriForHost($host)
  {
    if (mb_strstr(getHostAndURI(), $host . '/') !== false) {
      return (string)mb_substr(getHostAndURI(), mb_strlen($host) + 1);
    }
    else {
      return (string)mb_substr(getHostAndURI(), mb_strlen($host));
    }
  }

  /**
   * Retrieves page identifier and site id from database
   * @param int $pageId
   * @return array
   *         'id'         => page id,
   *         'identifier' => identifier,
   *         'siteId'     => site id,
   */
  function getPageDataFromPageId($pageId)
  {
    global $db;

    $pageId = (int)$pageId;
    $siteId = 0;
    $identifier = '';
    $tablePrefix = ConfigHelper::get('table_prefix');

    $sql = " SELECT CIIdentifier, FK_SID "
         . " FROM {$tablePrefix}contentitem "
         . " WHERE CIID = $pageId ";
    $row = $db->GetRow($sql);

    if ($row) {
      $siteId = (int)$row['FK_SID'];
      $identifier = $row['CIIdentifier'];
    }

    return array('id' => $pageId,
                 'identifier' => $identifier,
                 'siteId' => $siteId, );
  }

  /**
   * Replaces the configured site host by provided $host for site with id $id
   * @param int $id
   * @param string $host
   */
  function replaceSiteHost($id, $host)
  {
    $siteHosts = array_flip(ConfigHelper::get('site_hosts'));
    if (isset($siteHosts[$id])) {
      $siteHosts[$id] = $host;
    }
    ConfigHelper::set('site_hosts', array_flip($siteHosts));
  }

  /**
   * If the request URI contains the host page path, remove it and return the
   * actual URI.
   *
   * Example: Host page = level_page
   *          URI       = level_page/my_leaf_page
   *          =>
   *          cleaned   = my_leaf_page
   *
   * @param string $uri
   *        the requested URI
   * @param string $hostPagePath
   *        the host page path ( identifier )
   *
   * @return string
   *         the URI without host page path
   */
  function removeHostPagePathFromUri($uri, $hostPagePath)
  {
    $cleanedUri = $uri;

    // check for a "/" - without a slash, we do not have a level page request or
    // the level page path is not available
    //
    // i.e. custom page host
    // landingpage.url/my_leaf_page > website.url/level_page/my_leaf_page
    //
    // this is also important to make the custom page hosts work for page paths
    // where the level page name is equal the leaf page name
    //
    // i.e. landingpage.url/a_page > website.url/a_page/a_page
    //
    if (mb_stristr($uri, '/') && mb_strpos($uri, $hostPagePath) === 0) {
      $cleanedUri = mb_substr($uri, mb_strlen($hostPagePath));
      if (mb_strpos($cleanedUri, '/') === 0) {
        $cleanedUri = mb_substr($cleanedUri, 1);
      }
    }
    return $cleanedUri;
  }

  /**
   * Returns the page path and EDWIN URL parameters from URI.
   * @param string $uri
   * @return array
   */
  function getUriParts($uri)
  {
    if (mb_substr($uri, 0, 15) == 'index.php?page=') $uri = mb_substr($uri, 15);
    // remove additional GET url params from uri
    $tmp = explode('?', $uri);
    $uri = reset($tmp);

    // split URI into parts (path and arguments)
    $uriParts = explode('.', $uri);
    // remove the optional trailing slash from the path ( because content items
    // in the database are stored without it )
    $uriParts[0] = preg_replace('/\/$/u', '', $uriParts[0]);

    return $uriParts;
  }

  /**
   * Redirects for invalid requests, for example domain of host page, but page
   * path from page, that is not a subpage of host page.
   *
   * @param string $path
   * @param string $hostPageData
   */
  function redirectOnInvalidPageHostPath($path, $hostPageData)
  {
    global $db;

    $tablePrefix = ConfigHelper::get('table_prefix');

    $siteId = $hostPageData['siteId'];
    $sql = " SELECT CIIdentifier "
         . " FROM {$tablePrefix}contentitem "
         . " WHERE CIIdentifier = '{$db->escape($path)}' "
         . "   AND FK_SID = $siteId ";
    $row = $db->GetRow($sql);

    if (!$row) {
      $path = removeHostPagePathFromUri($path, $hostPageData['identifier']);
      $sql = " SELECT CIIdentifier "
           . " FROM {$tablePrefix}contentitem "
           . " WHERE CIIdentifier = '{$db->escape($path)}' "
           . "   AND FK_SID = $siteId ";
      $row = $db->GetRow($sql);
      if ($row) {
        $hosts = array_flip(ConfigHelper::get('site_hosts'));
        $host = $hosts[$siteId];
        header('Location: ' . getUrl($host, $row['CIIdentifier']));
        exit;
      }
    }
  }

  function getUrl($host, $path)
  {
    // remove trailing slash from path
    if (mb_strpos($path, '/') === 0) {
      $path = mb_substr($path, 1);
    }

    // append slash to host
    if (mb_strrpos($host, '/') !== (mb_strlen($host) - 1)) {
      $host = $host . '/';
    }

    return ConfigHelper::get('protocol') . $host . $path;
  }

  function getBrowser()
  {
    $browscap = new CmsBrowscap(cache_path() . 'browscap');
    try {
      $browser = $browscap->getBrowser();
    }
    catch(Exception $e) {
      trigger_error($e->getMessage(), E_USER_NOTICE);
    }

    return $browser;
  }

  /**
   * @return string
   */
  function getDeviceTemplateName()
  {
    $template = '';
    $config = ConfigHelper::get('output_device');

    if ($config) {
      $info = getBrowser();
      $ua = $_SERVER['HTTP_USER_AGENT'];

      // fix iphone home screen icons - they are missing the safari part
      if ($info && $info->browser == 'Default Browser' && mb_strpos($ua,'iPhone')){
        $info->browser  = 'iPhone';
        $info->version  = '5.0';
        $info->platform = 'iPhone OSX';
      }

      $detection = new TemplateDetection($info, $ua);

      foreach ($config as $d) {
        $device = new TemplateDetectionDevice();
        $device->setCode($d['device_code'])
               ->setName($d['device_name'])
               ->setPlatform($d['device_platform'])
               ->setTemplate($d['device_template'])
               ->setVersion($d['device_version']);
        if (isset($d['match_callback'])) {
          $device->setMatchCallback($d['match_callback']);
        }
        $detection->addDevice($device);
      }

      $template = $detection->detect()->getTemplate();
    }

    return $template;
  }
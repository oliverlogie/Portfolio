<?php

  /**
   * Backend Wrapper Class
   *
   * $LastChangedDate: 2020-02-28 09:34:18 +0100 (Fr., 28 Feb 2020) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  $_MODULES_NAV = array();

  /**
   * Stores all active modules.
   */
  $_MODULES = array();

  class BackendRequest {
    /**
     * Array containing all sites (including sites not available to the user).
     *
     * Array indices are the site IDs, values are the site titles.
     * The array is already ordered in a user friendly way.
     *
     * @var array
     */
    private $_allSites = array();
    /**
     * The ID of the current site.
     *
     * This is guaranteed to always contain a valid site ID.
     *
     * @var integer
     */
    private $site_id = 0;
    private $page_id = "";
    private $action = "";
    private $action2 = "";
    /**
     * The database object
     *
     * @var Db
     */
    private $db;
    private $table_prefix = "";
    private $tpl = 0;
    private $content_page = 0;

    /**
     * The user object.
     *
     * @var User
     */
    private $_user = "";
    private $session = 0;

    /**
     * Navigation object
     *
     * @var Navigation
     */
    private $_navigation;

    const EDWIN_CORE_VERSION = '4.6.15 (200228)';

    const EDWIN_COMPONENT_CONTENT_ITEM  = 'content_item';

    const EDWIN_COMPONENT_MODULE = 'module';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Constructor                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function __construct($page_id, $action, $action2, $tpl, $db, $table_prefix)
    {
      global $_LANG, $_LANG2;

      $this->page_id = (int)$page_id;
      $this->action = $action;
      $this->action2 = $action2;
      $this->db = $db;
      $this->table_prefix = $table_prefix;
      $this->tpl = $tpl;

      if (!$this->action) {
        $this->action = "mod_cmsindex";
      }

      $session = $this->session = Container::make('Session');

      // Set the site id of CmsBugtracking early as possible from the request data.
      // On the frontend we can determine the site id through the requested url, but
      // that is not always possible on the backend.
      Container::extend('CmsBugtracking', function () use ($session) {
        $bugtracking = new CmsBugtracking();
        $request = new Input(Input::SOURCE_REQUEST);
        // Determine the current site from request
        $siteId = $request->exists('site') ? $request->readInt('site') : (int)$session->read('site');
        if ($siteId) {
          $bugtracking->setSiteId($siteId);
        }
        return $bugtracking;
      });

      // Check Authorisation
      $login = new Login($this->tpl, $this->db, $this->table_prefix, $this->session);
      $this->_user = $login->check();

      // Include default and customized language file for user language
      $langFile = ConfigHelper::get('m_use_compressed_lang_file') ? 'compressed' : 'core';
      $langFileLanguage = $this->_user->getLanguage() ? $this->_user->getLanguage() : 'german';
      $langFileDefault = "language/$langFileLanguage-default/lang.$langFile.php";
      $langFileCustomized = "language/$langFileLanguage/lang.$langFile.php";
      include($langFileDefault);
      if (is_file($langFileCustomized)) {
        include($langFileCustomized);
      }

      $this->_navigation = Navigation::getInstance($this->db, $this->table_prefix);
      $this->_initSites();
      $this->_initSiteId();
      $this->_initModules();

      if ($this->_user->isValid()) {
        // If the user has not access to any sites or modules exit and print an
        // error message. (If this error occurs, the user configuration from the
        // user management module is invalid)
        if ($this->_user->noSites() && !$this->_user->getOptionalModule()) {
          die ('This user has neither access to any sites, nor to any modules! Check user configuration!');
        }

        // Get the first available site id of current user.
        // Site id is usually 0 if user just logged in.
        if (!$this->site_id) {
          $this->site_id = $this->_getFirstAvailableSiteId();
          $this->session->save("site", $this->site_id);
          header("Location: index.php");
          exit;
        }

        // Check for site base config overrides
        $config = ConfigHelper::get('m_config_overrides', null, $this->site_id);
        if (is_array($config)) {
          foreach ($config as $name => $value) {
            ConfigHelper::set($name, $value);
          }
        }

        // no permisson to this page
        if (!$this->_user->ContentPermitted($this->site_id, $this->page_id, $this->action))
        {
          // if requested page isn't a module
          if (mb_substr($this->action,0,4) != "mod_")
          {
            // the user hasn't got permission to the requested page -> check if
            // page exists as there could have been a wrong page requested (a user
            // hasn't got access to pages that do not exist)
            $sql = " SELECT CIID "
                  ." FROM {$this->table_prefix}contentitem "
                  ." WHERE CIID = {$this->page_id}";
            $exists = $this->db->GetOne($sql);
            if (!$exists) {
              $this->redirect_page("invalid_path");
            }
          }
          $this->redirect_page("permission_denied");
        }

        // Cache all active content types, by loading them.
        // NavigationPage may loads (and ContentTypeFactory caches) inactive types later, if
        // required.
        $factory = new ContentTypeFactory($this->db, $this->table_prefix);
        $factory->getAllActive();
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show() {
      global $_LANG, $_MODULES;

      if (!$this->_user->isValid()) {
        return '';
      }

      $module_type_class = array_flip($_MODULES);
      if (mb_substr($this->action,0,4) == "mod_")
      {
        $module_shortname = mb_substr($this->action,4);
        if (mb_substr($this->action,0,13) == "mod_response_") {
          $module_shortname = mb_substr($this->action,13);
        }

        if ($module_shortname != "cmsindex" && $module_shortname != "siteindex" && $module_shortname != "bugtracking")
        {
          // invalid module class
          if (!file_exists("includes/classes/modules/class.".$module_type_class[$module_shortname].".php")) {
            $this->redirect_page("contentclass_notfound");
          }
        }

        $moduleClass = $module_type_class[$module_shortname];
        $c_module = new $moduleClass($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix, $this->action2, $this->page_id, $this->_user, $this->session, $this->_navigation);

        // module response
        if (mb_substr($this->action,0,13) == "mod_response_") {
          if (!empty($_REQUEST['request'])) {
            echo $c_module->getSendResponse($_REQUEST['request']);
          }
          exit;
        }
        // normal module request
        else {
          $c_content = $c_module->show_content();
        }
      }
      else {
        switch ($this->action) {
          case "change_pw":
            $c_contenuser = new ContentUser($this->tpl,$this->db,$this->table_prefix,$this->_user);
            $c_content = $c_contenuser->show_change_pw();
            break;
          case 'comments':
            $c_content = $this->show_subpage_common_items('ContentItemComments');
            break;
          case "files":
            $c_content = $this->show_subpage_common_items("ContentItemFiles");
            break;
          case "intlinks":
            $c_content = $this->show_subpage_common_items("ContentItemIntLinks");
            break;
          case "extlinks":
            $c_content = $this->show_subpage_common_items("ContentItemExtLinks");
            break;
          case "strlinks":
            $c_content = $this->show_subpage_common_items("ContentItemStrLinks");
            break;
          case "response":
            $this->sendResponse();
            break;
          case "content":
          default:
            $c_content = $this->show_subpage();
            break;
        }
      }

      // preview content
      if ($this->action2 == "preview") {
        $this->tpl->load_tpl('main', 'main_preview.tpl');
        $this->tpl->parse_if('main', 'm_live_mode', ConfigHelper::get('m_live_mode'));
        $this->tpl->parse_if('main', 'm_live_mode', ConfigHelper::get('m_live_mode'));
        $this->tpl->parse_if('main', 'm_dev_mode', !ConfigHelper::get('m_live_mode'));
        $this->tpl->parseprint('main', array( 'main_content' => $c_content,
                                              'main_surl' => "../",
                                              'main_sid' => ($this->site_id-1),
                                              'main_title' => $_LANG["global_main_name"]." - ".$_LANG["global_main_name_preview"] ));
      }
      // show content
      else
      {
        // content_output_mode:
        // 1...normal, 2...only content data, 10...alternative main template
        // (an alternative main template has to be defined in 'content_output_tpl')
        $content_output_mode = 1;
        if (isset($c_content["content_output_mode"]) && $c_content["content_output_mode"]) {
          $content_output_mode = $c_content["content_output_mode"];
        }

        // Retrieve the alternative main template
        $contentOutputTemplate = '';
        if (isset($c_content['content_output_tpl']) && $c_content['content_output_tpl']) {
          $contentOutputTemplate = $c_content['content_output_tpl'];
        }

        $this->render_page($c_content["content"], $c_content["content_left"],
                           $c_content["content_top"], $c_content["content_contenttype"],
                           $content_output_mode, $contentOutputTemplate);
      }
    }

    /**
     * Removes cached sitemap files
     *
     * @param int $siteId
     *            The ID of the site.
     */
    public static function removeCachedFiles($siteId)
    {
      // Remove the cached sitemap-navigation for current site - generated from
      // ModuleSitemapNavFooter - from filesystem.
      if (ConfigHelper::get('sf_cache_navigation')) {
        self::_removeCachedFilesIncludingTemplatesets(
          '../storage/cache/templates/modules/',
          'ModuleSitemapNavFooter_' . $siteId . '.tmp');
      }

      // Remove the cached sitemap-navigation for current site - generated from
      // ModuleSitemapNavMain - from filesystem.
      if (ConfigHelper::get('sn_cache_navigation')) {
        self::_removeCachedFilesIncludingTemplatesets(
            '../storage/cache/templates/modules/',
            'ModuleSitemapNavMain_' . $siteId . '.tmp');
      }

      // Remove the cached sitemap (ContentItemST) for current site.
      if (ConfigHelper::get('st_cache_navigation')) {
        self::_removeCachedFilesIncludingTemplatesets(
            '../storage/cache/templates/content_types/',
            'ContentItemST_' . $siteId . '.tmp');
      }

      // Remove the cached sitemap-navigation for current site - generated from
      // ModuleSitemapNavMainMobile - from filesystem.
      if (ConfigHelper::get('sx_cache_navigation')) {
        self::_removeCachedFilesIncludingTemplatesets(
            '../storage/cache/templates/modules/',
            'ModuleSitemapNavMainMobile_' . $siteId . '.tmp');
      }
    }

    /**
     * Removes the specified file from directory and templateset subdirectories:
     * - _mobile
     * - custom templateset directories starting with "_"
     *
     * @param $basedir
     * @param $filename
     */
    private static function _removeCachedFilesIncludingTemplatesets($basedir, $filename)
    {
      $directories = array();
      if (is_dir($basedir)) {
        $handle = opendir($basedir);
        while (($file = readdir($handle)) !== false) {
          if (is_dir($basedir . $file) && strpos($file, '_') === 0) {
            $directories[] = $basedir . $file . '/';
          }
        }
      }

      array_push($directories, $basedir);
      foreach ($directories as $dir) {
        unlinkIfExists($dir . $filename);
      }
    }

    /**
     * Gets the full url of current page.
     *
     * @return string
     */
    public static function getFullUrl()
    {
      $s = empty($_SERVER['HTTPS']) ? '' : (($_SERVER['HTTPS'] == 'on') ? 's' : '');
      $protocol = mb_substr(mb_strtolower($_SERVER["SERVER_PROTOCOL"]), 0, mb_strpos(mb_strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
      $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
      return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    /**
     * Creates a ContentItem object for a specified page ID.
     *
     * @param int $pageID
     *        The ID of the page.
     * @return ContentItem
     *        The created ContentItem object.
     */
    private function createContentItem($pageID)
    {
      // Create and return an instance of the class.
      $contentItem = ContentItem::create($this->site_id, $pageID, $this->tpl,
                                         $this->db, $this->table_prefix,
                                         $this->action2, $this->_user, $this->session,
                                         $this->_navigation);
      if ($contentItem instanceof ContentItem) {
        return $contentItem;
      }

      $this->redirect_page($contentItem);
    }

    /**
     * Calls the sendResponse Method of the ContentItem and stops execution
     */
    private function sendResponse()
    {
      if (empty($_REQUEST['request'])) {
        exit();
      }

      $contentItem = $this->createContentItem($this->page_id);
      echo $contentItem->sendResponse($_REQUEST['request']);

      exit();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content Subsite                                                                  //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function show_subpage()
    {
      global $_LANG;

      $page = $this->_navigation->getPageByID($this->page_id);
      if (!$page->isContentLocked())
      {
        $this->content_page = $this->createContentItem($this->page_id);
        $processed = false;

        if ($this->content_page->isProcessed()) {

          $this->content_page->edit_content();
          $processed = true;
          // for 'process' as well as for 'process_date' POST data, we have to
          // enable the contentitem, if it has valid content.
          // Remove cached files ( i.e. SitemapNavigation ) as they might
          // are invalid due to contentitem activation.
          if (in_array($this->content_page->getProcessedValue(),
                       array('process', 'process_date'))
          ) {
            $sql = " SELECT CHasContent "
                 . " FROM {$this->table_prefix}contentitem "
                 . " WHERE CIID = $this->page_id ";
            if ($this->db->GetOne($sql)) {
              $sql = "UPDATE {$this->table_prefix}contentitem "
                   . 'SET CDisabled = 0, '
                   . '    CDisabledLocked = 0 '
                   . "WHERE CIID = $this->page_id "
                   . 'AND CDisabled != 0 ';
              $this->db->query($sql);
            }
            self::removeCachedFiles($this->site_id);
          }

        }
        else if (isset($_GET['did'])) {
          $this->_deletePage((int)$_GET['did']);
          $processed = true;
        }

        // clear cache if content has been processed
        if ($processed === true)
          Navigation::clearCache($this->db, $this->table_prefix);

        // Spider content must run before the content of the content item
        // is called, because the infobox content (contentitem_log) is updated
        // via spiderContent function.
        $this->content_page->spiderContent();
        if ($this->action2 == "preview") {
          $c_content = $this->content_page->preview();
        }
        else {
          $c_content = $this->content_page->get_content();
        }

        return $c_content;
      }
      else{ // invalid path
        $this->redirect_page("invalid_path");
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Files / Internal Links / External Links                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function show_subpage_common_items($item_type)
    {
      $sql = 'SELECT CIIdentifier, CContentLocked '
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE CIID = $this->page_id ";
      $row = $this->db->GetRow($sql);
      if ($row && !$row['CContentLocked']){ // valid path
        $this->content_page = new $item_type($this->site_id,$this->page_id,$this->tpl,$this->db,$this->table_prefix,$this->action,$row["CIIdentifier"],$this->_user,$this->session,$this->_navigation);

        if (isset($_POST["process"])) $this->content_page->edit_content();
        else if (isset($_GET["did"])) $this->content_page->delete_content();
        $c_content = $this->content_page->get_content();
        return $c_content;
      }
      else{ // invalid path
        $this->redirect_page("invalid_path");
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Display Site & Navigation                                                             //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function render_page($main_content,$main_content_left = "",$main_content_top = "",$main_content_type = "dummy", $main_content_output_mode = 1, $contentOutputTemplate = '') {
      global $_LANG, $_LANG2, $_MODULES_NAV;

      if ($main_content_output_mode == 2){ // show only content data
        echo $main_content;
        return 1;
      }

      $altMain = '';
      // show module specific main template main_<moduleshortname>.tpl
      if ($main_content_output_mode == 10 && $contentOutputTemplate)
      {
        // there exists a special main template for the current module type
        if (file_exists('templates/main_'.$contentOutputTemplate.'.tpl')) {
          $altMain = '_' . $contentOutputTemplate;
        }
      }

      $main_title = $this->_getMainTitle();
      $m_path = $this->_getNavigationPath();
      $m_levels = $this->_getNavigationLevels();
      $m_backlink_left = $this->_getNavigationBacklink();

      // Load the left content for the subpage.
      if ($m_backlink_left) {
        $this->tpl->load_tpl('content_site_left', 'content_site_left.tpl');
        $this->tpl->parse_if('content_site_left', 'leftbox', $main_content_left, array('main_content_left' => $main_content_left));
        $main_content_left = $this->tpl->parsereturn('content_site_left', array ( 'm_backlink' => $m_backlink_left));
      }

      /**
       * Site navigation
       * Known bugs:
       * - If user has not got permission for any site, but for modules, the
       *   user is not able to edit a backend user or to edit a sidebox. Because many modules
       *   require a site id to add/update/delete data.
       * - It is not possible to configure sub-portals (of course this case
       *   is not really needed): KUNDE -> KUNDE Schweiz -> KUNDE Suisse
       *   allSites array does only store portals of first level!
       * - If user has got permission to access only one language site, but
       *   preferred language is equal to language of a not accessable language site with a higher position,
       *   the site link in the dropdown menu would be wrong/not available.
       */
      $userPermittedSites = $this->_user->getPermittedSites();
      $userPermittedPaths = $this->_user->getPermittedPaths();
      /**
       * Start generating the site menu (left list)
       *
       * Create list of sites, also if there is only one site,
       * but do not create it if there are permitted paths configured for
       * current user, we want to show only the house icon and link to cms index (since 3.1.2)!
       */
      $siteItems = array();
      if (count($userPermittedSites) >= 1 && !$userPermittedPaths) {
        $leftMenuSites = $this->_getMenuLeftNavigationSites();
        foreach ($leftMenuSites as $siteId) {
          $siteItems[] = $this->_getNavigationPortalItemDetails($this->_navigation->getSiteByID($siteId));
        }
      }
      /**
       * Start generating the language menu (right list)
       *
       * Language list menu is available if there is more than one permitted site for
       * current user available and no configured access paths available.
       */
      $languageItems = array();
      if (count($userPermittedSites) > 1 && !$userPermittedPaths) {
        $rightMenuSites = $this->_getMenuRightNavigationSites();
        foreach ($rightMenuSites as $siteId) {
          $languageItems[] = $this->_getNavigationLanguageItemDetails($this->_navigation->getSiteByID($siteId));
        }
      }

      $activeSite = $this->_navigation->getSiteByID($this->site_id);
      $availableSitesCount = $this->_user->getNavPermittedSites() ?
                             count(array_keys($this->_user->getNavPermittedSites())) : 1;
      // set the active item
      $activeSiteItem = array();
      // one or more sites available
      if ($availableSitesCount > 1)
      {
        $parent = $activeSite->getLanguageParent();
        // if site is language parent
        if (!$parent) {
          // take special title
          if (isset($_LANG['global_sites_backend_language_site_general_label'][$activeSite->getID()]))
          {
            $siteTitle = $_LANG['global_sites_backend_language_site_general_label'][$activeSite->getID()];
          }
          else {
            $siteTitle = $this->_allSites[$activeSite->getID()];
          }
        }
        // if site is language site take title of parent (or special title of parent from $_LANG)
        else {
          if (isset($_LANG['global_sites_backend_language_site_general_label'][$parent->getID()]))
          {
            $siteTitle = $_LANG['global_sites_backend_language_site_general_label'][$parent->getID()];
          }
          else {
            $siteTitle = $this->_allSites[$parent->getID()];
          }
        }
      }
      else { // only one site available
        $siteTitle = ContentBase::getLanguageSiteLabel($activeSite);
      }
      $activeSiteTitle = $siteTitle;

      $link = $this->_getSiteSelectionLink($activeSite);
      $siteTitle = $this->_allSites[$activeSite->getID()];
      // if no other languages of site exist
      $language = $activeSite->getLanguage();
      if ($languageItems) {
        $language.='_arrow';
      }
      $activeLanguageItem = array(
        'm_nv_link_lang_active' => $link,
        'm_nv_label_lang_active' => parseOutput($siteTitle),
        'm_sid_lang_active' => $language,
      );

      // Determine main site title.
      $m_site_label = '';
      if ($this->action != 'mod_cmsindex') {
        $m_site_label = ContentBase::getLanguageSiteLabel($activeSite);
      }

      /**
       * Start generating the module navigation
       */
      $module_items = array();
      // If the only permission the user has, is a single module, or in
      // other words:
      // If the current user has not got access to any site and there is only
      // one module displayed within the module navigation do not display it.
      if ($this->_user->getPermittedSites() || count($_MODULES_NAV) > 2)
      {
        ksort($_MODULES_NAV, SORT_NUMERIC);
        foreach ($_MODULES_NAV as $mid => $module)
        {
          if ($mid > 0 && $module && $this->_user->AvailableModule($module, $this->site_id))
          {
            $module_items[] = array (
              'm_mod_link' => "index.php?action=mod_$module&amp;site=$this->site_id",
              'm_mod_label' => $_LANG['mod_'.$module.'_title'],
              'm_mod_short' => $module
            );
          }
        }
      }

      // get current mode
      $m_current_mode = $_LANG["m_default_mode_name"];
      if (mb_substr($this->action,0,4) == "mod_") {
        $m_current_mode = $_LANG["m_mode_name_".$this->action];
      }
      // if there isn't a module requested - get tree and mode name of current tree
      else {
        $modeTree = $this->_getCurrentTree() ?: 'default';
        // get mode label for current navigation tree
        if (isset($_LANG["m_{$modeTree}_mode_name"])) {
          $m_current_mode = $_LANG["m_{$modeTree}_mode_name"];
        }
      }

      $tmp = explode(";",$this->action2);
      if (isset($_LANG["m_mode_name_".$this->action.$tmp[0]])) {
        if ($tmp[0] != "main") {
          $m_current_mode = $_LANG["m_mode_name_".$this->action.$tmp[0]];
        }
      }

      // global templates for bugtracking module
      $m_bugtracking_link = ModuleBugtracking::main_template_part($this->tpl);
      $mod_bt = new ModuleBugtracking($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix, $this->action2, $this->page_id, $this->_user, $this->session, $this->_navigation);
      $main_bugtracking = $mod_bt->get_content($this->action);

      // navigation
      $this->tpl->load_tpl('navigation_level0', 'main_navigation_level0.tpl');
      $this->tpl->parse_if('navigation_level0', 'm_nv_site_and_portal_nav_levels', count($siteItems) > 1);
      $this->tpl->parse_if('navigation_level0', 'm_nv_language_nav_levels', $languageItems);
      // do not display the language selection if only a single site is available
      $this->tpl->parse_if('navigation_level0', 'm_nv_user_has_sites', !$this->_user->noSites());
      $this->tpl->parse_if('navigation_level0', 'm_nv_show_language_sites', $availableSitesCount > 1 && !$userPermittedPaths);
      // site navigation
      $this->tpl->load_tpl('navigation_level0_levels_sites', 'main_navigation_level0_levels_sites.tpl');
      $this->tpl->parse_if('navigation_level0_levels_sites', 'site_items', $siteItems);
      $this->tpl->parse_loop('navigation_level0_levels_sites', $siteItems, 'site_items');
      $siteAndPortalNav = $this->tpl->parsereturn('navigation_level0_levels_sites', array());
      //language navigation
      $this->tpl->load_tpl('main_navigation_level0_levels_languages', 'main_navigation_level0_levels_languages.tpl');
      $this->tpl->parse_if('main_navigation_level0_levels_languages', 'language_items', $languageItems);
      $this->tpl->parse_loop('main_navigation_level0_levels_languages', $languageItems, 'language_items');
      $languageNav  = $this->tpl->parsereturn('main_navigation_level0_levels_languages', array());
      // insert module icons into navigation
      $this->tpl->parse_if('navigation_level0', 'module_items', $module_items);
      $this->tpl->parse_loop('navigation_level0', $module_items, 'module_items');
      foreach ($module_items as $value) {
        $this->tpl->parse_if('navigation_level0', 'm_nv_mod_'.$value['m_mod_short'].'_current', ($this->action == 'mod_'.$value['m_mod_short']));
        $this->tpl->parse_if('navigation_level0', 'm_nv_mod_'.$value['m_mod_short'].'_not_current', ($this->action != 'mod_'.$value['m_mod_short']));
      }
      $main_navigation_level0 = $this->tpl->parsereturn('navigation_level0', array_merge(
        array(
          'm_nv_current_user' => sprintf('%s %s', $this->_user->getFirstname(), $this->_user->getLastname()),
          'm_nv_current_user_link' => 'index.php?action=change_pw',
          'm_current_mode' => $m_current_mode,
          'm_bugtracking_link' => $m_bugtracking_link,
          'm_nv_logout_label' => $_LANG["m_nv_logout_label"],
          'm_nv_site_and_portal_nav_levels' => $siteAndPortalNav,
          'm_nv_language_nav_levels' => $languageNav,
          'm_nv_link_active' => ($this->_user->getPermittedPaths()) ? 'index.php' : $this->_getSiteSelectionLink($activeSite),
          'm_nv_label_active' => parseOutput($activeSiteTitle),
          // m_sid_active:0 show simple house icon;
          // m_sid_active:1 show house icon with dropdown arrow
          'm_sid_active' => ($availableSitesCount == 1 || $userPermittedPaths) ? '0' : '1',
          'm_nv_quicklinks' => $availableSitesCount >= 1 ? $this->_getQuickLinks($activeSite) : '',
          'm_nv_modules_active_cls' => mb_substr($this->action, 0, 4) == 'mod_' && !in_array($this->action, array('mod_siteindex', 'mod_cmsindex')) ? 'active' : '',
        ),
        $activeLanguageItem, $activeSiteItem));

      $this->tpl->load_tpl('navigation_level1', 'main_navigation_level1.tpl');
      $main_navigation_level1 = $this->tpl->parsereturn('navigation_level1', array( 'm_site_label' => parseOutput($m_site_label),
                                                                                    'm_path' => $m_path,
                                                                                    'm_levels' => $m_levels ));

      $moduleShortname = '';
      $mainContentTypePath = '';
      // additional js file for current content
      $currentContent = '';
      if (mb_substr($main_content_type,0,6)=="Module") {
        $mainContentTypePath = "modules/".$main_content_type;
        $currentContent = self::EDWIN_COMPONENT_MODULE;
        // Get shortname
        if ($main_content_type != "ModuleCmsindex" && $main_content_type != "ModuleSiteindexCompendium" && $main_content_type != "ModuleBugtracking") {
          $module = new $main_content_type($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix, $this->action2, $this->page_id, $this->_user, $this->session, $this->_navigation);
          $moduleShortname = $module->getShortname();
        }
      }
      else if (mb_substr($main_content_type,0,11)=="ContentItem") {
        $mainContentTypePath = "content_types/".$main_content_type;
        $currentContent = self::EDWIN_COMPONENT_CONTENT_ITEM;
      }
      $editorLang = ConfigHelper::get('be_editor_lang');
      $editorLang = isset($editorLang[$this->_user->getLanguage()]) ?
                    $editorLang[$this->_user->getLanguage()] : 'de';

      // parse subtemplates in main template and print out
      $this->tpl->load_tpl('main', "main$altMain.tpl");
      $this->tpl->parse_if('main', 'additional_js', is_file(ConfigHelper::get('m_backend_theme') . 'js/' . $mainContentTypePath.".js"), array( 'main_content_type_js' => $mainContentTypePath ));
      $this->tpl->parse_if('main', 'additional_js', is_file(ConfigHelper::get('m_backend_theme') . 'js/' . $mainContentTypePath.".js"), array( 'main_content_type_js' => $mainContentTypePath ));
      $this->tpl->parse_if('main', 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
      $this->tpl->parse_if('main', 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
      $this->tpl->parseprint('main', array_merge(array(
        'main_title' => strip_tags(htmlspecialchars_decode($main_title)),
        'main_sid' => $this->site_id,
        'main_sid_fe' => $this->site_id-1,
        'main_navigation_level0' => $main_navigation_level0,
        'main_navigation_level1' => $main_navigation_level1,
        'main_content_left' => $main_content_left,
        'main_content_top' => $main_content_top,
        'main_bugtracking' => $main_bugtracking,
        'main_content' => $main_content,
        'main_preview_close_label' => $_LANG["global_preview_close_label"],
        'main_user_id' => $this->_user->getID(),
        'main_user_language' => $this->_user->getLanguage(),
        'main_user_editor_language' => $editorLang,
        'main_page_id' => $this->page_id,
        'main_content_type' => $main_content_type,
        'main_current_content' => $currentContent,
        'm_site_label' => parseOutput($m_site_label),
        'main_module_shortname' => $moduleShortname,
        'main_cache_resource_version' => ConfigHelper::get('m_cache_resource_version'),
        'main_theme' => ConfigHelper::get('m_backend_theme'),
        'main_custom_config' => custom_config_stylesheet_path()
      ), $_LANG2['global']));
    }

    /**
     * @param int $id
     * @return void
     */
    private function _deletePage($id)
    {
      global $_LANG;

      $page = $this->_navigation->getPageByID((int)$id);
      if ($page) {
        $class = $page->getCustomTemplate() ?
                 $page->getContentTypeClass() . $page->getCustomTemplate() :
                 $page->getContentTypeClass();

        // Delete the content item.
        $delete_page = $this->createContentItem($page->getID());
        $del_success = $delete_page->delete_content();
        self::removeCachedFiles($this->site_id);

        // Set success message.
        if ($del_success) {
          $this->content_page->setMessage(Message::createSuccess(
              sprintf($del_success, $_LANG["global_{$class}_label"])));
        }
      }
      else {
        $this->redirect_page('invalid_path', 'index.php?action=content&site=' .
            $this->site_id . '&page=' . $this->page_id);
      }
    }

    /**
     * Gets the main title for display in the browser.
     *
     * The main title contains the name of the CMS installation (from the
     * language file) and the path to the current page.
     *
     * @return string
     *        The main title for display in the browser.
     */
    private function _getMainTitle()
    {
      global $_LANG;

      // If there is no current page then only the name of the CMS installation is returned.
      $page = $this->_navigation->getCurrentPage();
      if (!$page)
      {
        $key = 'global_sites_backend_root_siteindex_title';
        if (mb_substr($this->action,0,4) == 'mod_') {
          $key = $this->action . '_title';
        }
        $label = isset($_LANG[$key]) ? $_LANG[$key] : '';
        $label = $label ? sprintf($_LANG['m_title_path_part'], $label) : '';
        return $_LANG['global_main_name'].$label;
      }

      // If the current page is a root page return label for root page (tree specific)
      if ($page->isRoot()) {
        $label = $_LANG["global_sites_backend_root_{$page->getTree()}_title"] ?
                 $_LANG["global_sites_backend_root_{$page->getTree()}_title"] : '';
        $label = $label ? sprintf($_LANG['m_title_path_part'], $label) : '';
        return $_LANG['global_main_name'] . $label;
      }

      // The path is built from the titles of all pages in the hierarchy.
      // A root page is only displayed in the path if it is the current page.
      $path = '';
      do {
        $path = sprintf($_LANG['m_title_path_part'], $page->getTitle()) . $path;
      } while (($page = $page->getParent()) && !$page->isRoot());

      // The built path is appended to the name of the CMS installation.
      return $_LANG['global_main_name'] . $path;
    }

    /**
     * Gets the parsed navigation path for the top navigation.
     *
     * @return string
     *        The parsed navigation path for the top navigation.
     */
    private function _getNavigationPath()
    {
      global $_LANG;

      $currentSite = $this->_navigation->getCurrentSite();
      $currentPage = $this->_navigation->getCurrentPage();

      // If there is no current site the path is empty.
      // But we show a path if a module was called.
      if ((!$currentSite && mb_substr($this->action, 0, 4) != 'mod_') || $this->action == 'mod_cmsindex') {
        return '';
      }

      // The path for the siteindex has exactly one element.
      if ($this->action == 'mod_siteindex')
      {
        $this->tpl->load_tpl('main_path_part', 'main_path_part_current.tpl');
        $this->tpl->parse_if('main_path_part', 'levelarrow', false);
        return $this->tpl->parsereturn('main_path_part', array(
          'm_level_id' => 0,
          'm_item_label' => $_LANG['global_edit_home_label'],
          'm_level_label' => $_LANG['m_mainmenu_label'],
          'm_show_ll_items_label' => $_LANG['m_show_ll_items_label'],
          'm_nv_state' => '',
          'm_item_link' => "index.php?action=mod_siteindex&amp;site={$currentSite->getID()}",
          'm_nv_ctype' => 'home',
          'm_nv_ctype_label' => $_LANG['m_nv_ctype_label_home'],
        ));
      }

      // Special handling for modules.
      if (mb_substr($this->action, 0, 4) == 'mod_')
      {
        $action2 = '';
        if (mb_strstr($this->action2, 'edit')) {
          $action2 = 'edit';
        } else if (mb_strstr($this->action2, 'new')) {
          $action2 = 'new';
        } else if (mb_strstr($this->action2, 'reply')) {
          $action2 = 'reply';
        }
        $this->tpl->load_tpl('main_path_part', ($action2) ? 'main_path_part.tpl' : 'main_path_part_current.tpl');
        $this->tpl->parse_if('main_path_part', 'levelarrow', false);
        $modShort = mb_substr($this->action, 4);
        $path = $this->tpl->parsereturn('main_path_part', array(
          'm_level_id' => 0,
          'm_level_label' => $_LANG[$this->action.'_title'],
          'm_item_label' => $_LANG['m_mode_name_'.$this->action],
          'm_show_ll_items_label' => $_LANG['m_mode_name_'.$this->action],
          'm_nv_state' => '',
          'm_item_link' => 'index.php?action='.$this->action,
          'm_nv_ctype' => $modShort,
          'm_nv_ctype_label' => $_LANG[$this->action.'_title'],
        ));

        if ($action2) {
          // look if there is a submodul request (like main, answer, etc.)
          $action2Part = explode(';', $this->action2, -1);
          $action2Part = (!empty($action2Part[0])) ? $action2Part[0].'_' : '';
          $action2Label = isset($_LANG[$this->action.'_'.$action2Part.$action2.'_label']) ? $_LANG[$this->action.'_'.$action2Part.$action2.'_label'] : $_LANG['module_'.$action2.'_label'];
          $this->tpl->load_tpl('main_path_part', 'main_path_part_current.tpl');
          $this->tpl->parse_if('main_path_part', 'levelarrow', false);
          $path .= $this->tpl->parsereturn('main_path_part', array(
            'm_level_id' => 1,
            'm_level_label' => $_LANG['module_action_label'],
            'm_item_label' => $action2Label,
            'm_show_ll_items_label' => $_LANG['m_mode_name_'.$this->action],
            'm_nv_state' => '',
            'm_item_link' => '',
            'm_nv_ctype' => $action2,
            'm_nv_ctype_label' => $action2Label,
          ));
        }
        return $path;
      }

      if ($currentPage->getTree() == Navigation::TREE_USER) {
        $page = $currentSite->getRootPageOfUser($currentPage->getFrontendUserID());
      } else {
        // Get the root page of the current navigation tree.
        $page = $currentSite->getRootPage($currentPage->getTree());
      }

      // Walk down the navigation hierarchy from the root to the current page.
      $path = '';
      do {
        $level = $page->getRealLevel();
        $displayLevel = $level;
        if ($page->getParent() && $page->getParent()->isRealLeaf()) {
          $level++;
        }

        $status = $this->_navigation->GetPageStatus($page);

        // Only link to a page if the user has permission.
        $link = 'index.php';
        if ($this->_user->AvailablePage($page)) {
          $link = "index.php?action=content11&amp;site={$currentSite->getID()}&amp;page={$page->getID()}";
        }

        if ($displayLevel < 0) {
          $levelLabel = $_LANG['m_mainmenu_label'];
        } else {
          $levelLabel = $_LANG["m_level_label$displayLevel"];
        }
        if ($page->getParent() && $page->getParent()->isArchive()) {
          $levelLabel .= ' ' . $_LANG['m_level_label_archive'];
        }
        if ($page->getParent() && $page->getParent()->isVariation()) {
          $levelLabel .= ' ' . $_LANG['m_level_label_variation'];
        }

        // handle root pages
        if ($page->isRoot())
        {
          $cTypeGroup = $page->getTree();
          $cTypeTooltip = $_LANG["m_nv_ctype_label_{$page->getTree()}"];
          $itemLabel = $_LANG["m_nv_ctype_label_{$page->getTree()}"];
        }
        // page isn't root - create breadcrumb entries for content items
        else
        {
          // determine page content type for displaying correct icon in navigation
          $itemLabel = $page->getTitle();
          $cTypeGroup = ContentItem::getTypeShortname($page->getContentTypeId());
          $cTypeTooltip = $_LANG["global_{$page->getContentTypeClass()}_intlabel"];
        }

        $levelData = array(
          'm_level_id' => $level + 1,
          'm_item_label' => $itemLabel,
          'm_level_label' => $levelLabel,
          'm_show_ll_items_label' => $_LANG['m_show_ll_items_label'],
          'm_nv_state' => $page->isDisabled() ? '_disabled' : '',
          'm_item_link' => $link,
          'm_nv_ctype' => $cTypeGroup,
          'm_nv_ctype_label' => $cTypeTooltip,
        );
        /**
         * Use different template if page is active
         */
        if ($status == NavigationPage::STATUS_ACTIVE) {
          $this->tpl->load_tpl('main_path_part', 'main_path_part_current.tpl');
          $this->tpl->parse_if('main_path_part', 'levelarrow', $level !== -1);
        } else {
          $this->tpl->load_tpl('main_path_part', 'main_path_part.tpl');
          $this->tpl->parse_if('main_path_part', 'levelarrow', $level !== -1);
        }
        $path .= $this->tpl->parsereturn('main_path_part', $levelData);
      } while ($page = $this->_navigation->getPageActiveChild($page));

      return $path;
    }

    /**
     * Gets the parsed navigation levels for the top navigation.
     *
     * @return string
     *        The parsed navigation levels for the top navigation.
     */
    private function _getNavigationLevels()
    {
      global $_LANG;

      $levels = '';

      $currentSite = $this->_navigation->getCurrentSite();

      // If there is no current site the levels are empty.
      if (!$currentSite) {
        return '';
      }

      // The levels for all other modules except for the siteindex module are empty.
      if (mb_substr($this->action, 0, 4) == 'mod_' && $this->action != 'mod_siteindex') {
        return '';
      }

      $currentPage = $this->_navigation->getCurrentPage();
      // get the root page of active page - use main tree root page on siteindex
      // (no current page found)
      $tree = isset($currentPage) ? $currentPage->getTree() : Navigation::TREE_MAIN;
      if ($tree == Navigation::TREE_USER) {
        $page = $currentSite->getRootPageOfUser($currentPage->getFrontendUserID());
      } else {
        // Get the root page of the current navigation tree.
        $page = $currentSite->getRootPage($tree);
      }
      // Walk down the navigation hierarchy from the root to the current page.
      do {
        $level = $page->getRealLevel();

        $levelItems = array();
        foreach ($page->getAllChildren() as $childPage) {
          // Only show a page link if the user has permission.
          if (!$this->_user->AvailablePage($childPage)) {
            continue;
          }

          // determine page content type for displaying correct icon in navigation
          $cTypeGroup = ContentItem::getTypeShortname($childPage->getContentTypeId());
          $cTypeTooltip = $_LANG["global_{$childPage->getContentTypeClass()}_intlabel"];

          $state = '';
          if ($this->_navigation->getPageStatus($childPage) != NavigationPage::STATUS_INACTIVE) {
            if ((!$childPage->isVisible()) && ($this->_navigation->getPageStatus($childPage) != NavigationPage::STATUS_INACTIVE)) {
              $state = '_cpath hndiv_inactive';
            } else {
            $state = '_cpath';
            }
          } else if (!$childPage->isVisible()) {
            $state = '_inactive';
          }
          $levelItems[] = array(
            'm_nv_id' => $childPage->getID(),
            'm_nv_label' => $childPage->getTitle(),
            'm_nv_link' => "index.php?action=content&amp;site={$currentSite->getID()}&amp;page={$childPage->getID()}",
            'm_show_ll_items_label' => $_LANG['m_show_ll_items_label'],
            'm_nv_state' => $state,
            'contentLocked' => $childPage->isContentLocked(),
            'm_nv_ctype' => $cTypeGroup,
            'm_nv_ctype_label' => $cTypeTooltip,
            'm_nv_active_cls' => $currentPage && $currentPage->getID() == $childPage->getID() ? 'item_active' : '',
          );
        }

        $this->tpl->load_tpl('navigation_level1_levels', 'main_navigation_level1_levels.tpl');
        $this->tpl->parse_loop('navigation_level1_levels', $levelItems, 'level_items');
        foreach ($levelItems as $levelItem) {
          $contentLocked = $levelItem['contentLocked'];
          $this->tpl->parse_if('navigation_level1_levels',
                               "item{$levelItem['m_nv_id']}_content_unlocked",
                               !$contentLocked, array(0));
          $this->tpl->parse_if('navigation_level1_levels',
                               "item{$levelItem['m_nv_id']}_content_locked",
                               $contentLocked, array(0));
        }
        $levels .= $this->tpl->parsereturn('navigation_level1_levels', array(
          'm_level_id' => $level + 2,
        ));

        $page = $this->_navigation->getPageActiveChild($page);
      } while ($page && $this->_navigation->getPageStatus($page) != NavigationPage::STATUS_ACTIVE);

      return $levels;
    }

    /**
     * Gets the backlink for the current page.
     *
     * @return string
     *        The backlink for the current page.
     */
    private function _getNavigationBacklink()
    {
      global $_LANG;

      $currentPage = $this->_navigation->getCurrentPage();

      // If there is no current page or it's the root page and it is not an user tree then there is no backlink.
      if (!$currentPage || $currentPage->isRoot() && $currentPage->getTree() != Navigation::TREE_USER) {
        return null;
      }

      $parentPage = $currentPage->getParent();
      $currentSite = $currentPage->getSite();

      // Show back link for root pages on user trees
      if ($currentPage->isRoot() && $currentPage->getTree() == Navigation::TREE_USER) {
        $link = 'index.php?action=mod_frontendusermgmt&amp;site='.$currentSite->getID();
        return sprintf($_LANG['m_nv2_backlink'], $link, $_LANG['module_backlink_list_title'], $_LANG['module_backlink_list_title']);
      }

      // Only link to the root page if the user has permission.
      $link = 'index.php';
      if ($this->_user->AvailablePage($parentPage)) {
        $link = "index.php?action=content&amp;site={$currentSite->getID()}&amp;page={$parentPage->getID()}";
      }
      return sprintf($_LANG['m_nv1_backlink'], $link, $currentSite->getTitle());
    }

    /**
     * Redirect to an error message page.
     *
     * @param string $message
     *        The message to display.
     * @param string $url [optional]
     *        An url to redirect to. If not set an url is retrieved automatically.
     */
    public function redirect_page ($message, $url = null)
    {
      global $_LANG, $_LANG2;

      if (!$url)
      {
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != self::getFullUrl()) {
          $url = $_SERVER['HTTP_REFERER'];
        }
        // If the user has not got permission to any sites (only modules)
        // make a redirect to the first module available instead of the
        // cmsindex.
        else if (!$this->_user->getPermittedSites())
        {
          $moduleShortname = $this->_user->getOptionalModule();
          $url = 'index.php?action=mod_' . $moduleShortname;
        }
        else {
          $this->site_id = $this->_getFirstAvailableSiteId();
          $this->session->save('site', $this->site_id);
          $url = 'index.php';
        }
      }

      $this->tpl->load_tpl('redirect_page', 'redirect_page.tpl');
      $this->tpl->parse_if('redirect_page', 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
      $this->tpl->parse_if('redirect_page', 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
      $this->tpl->parseprint('redirect_page', array_merge(array(
        'rp_url'       => $url,
        'rp_url_label' => $_LANG["rp_{$message}_url_label"],
        'rp_message'   => $_LANG["rp_{$message}_message_label"],
        'rp_title'     => $this->_allSites[$this->site_id],
        'main_theme' => ConfigHelper::get('m_backend_theme'),
      ), $_LANG2['global']));

      exit();
    }

    /**
     * Gets the first available site id of current user.
     *
     * @return int
     *         The site id. 0 if current user has not got access to any site.
     */
    private function _getFirstAvailableSiteId()
    {
      foreach ($this->_allSites as $siteID => $siteTitle) {
        if ($this->_user->AvailableSite($siteID)) {
          return $siteID;
        }
      }

      return 0;
    }

    /**
     * Initializes the members _allSites and site_id.
     *
     * _allSites is filled with the IDs and titles of all sites. The array is
     * filled in a user friendly order.
     * site_id is either taken from the request (if available) or else from the
     * session data. It will always contain a valid site ID.
     */
    private function _initSites()
    {
      global $_LANG;

      $this->_allSites = array();

      // call Navigation::getAllSites in order to load all site objects
      $sites = $this->_navigation->getAllSites();

      // Get all top-level sites.
      $rootSites = $this->_navigation->getRootSites();

      // Get sub-portals
      $tmpSites = array();
      foreach ($rootSites as $site)
      {
        $tmpSites[$site->getID()] = $site->getTitle();

        foreach ($site->getPortalChildren() as $child) {
          $tmpSites[$child->getID()] = $child->getTitle();
        }
      }

      // Add language sites
      foreach ($tmpSites as $id => $title)
      {
        $this->_allSites[$id] = isset($_LANG['global_sites_backend_label'][$id]) ? $_LANG['global_sites_backend_label'][$id] : $title;
        $tmpSite = $this->_navigation->getSiteByID($id);

        foreach ($tmpSite->getLanguageChildren() as $child) {
          $this->_allSites[$child->getID()] = isset($_LANG['global_sites_backend_label'][$child->getID()]) ? $_LANG['global_sites_backend_label'][$child->getID()] : $child->getTitle();
        }
      }
    }

    /**
     * Sets the site id from request or session.
     */
    private function _initSiteId()
    {
      $request = new Input(Input::SOURCE_REQUEST);
      // Determine the current site from request
      if ($request->exists('site')) {
        $this->site_id = $request->readInt('site');
        $this->session->save('site', $this->site_id);
      }
      // Take the site from the session data.
      else {
        $this->site_id = (int)$this->session->read('site');
      }
    }

    /**
     * Sets the global $_MODULES and $_MODULES_NAV arrays.
     */
    private function _initModules()
    {
      global $_MODULES, $_MODULES_NAV;

      // load module types
      $result = $this->db->query("SELECT MID,MShortname,MClass,MPosition FROM ".$this->table_prefix."moduletype_backend WHERE MActive=1");
      while ($row = $this->db->fetch_row($result)){
        $_MODULES_NAV[$row["MPosition"]] = $row["MShortname"];
        $_MODULES[$row["MClass"]] = $row["MShortname"];
      }
      $this->db->free_result($result);
    }

    /**
     * Takes the given site ID and returns the matching parent / children /
     * sibling site where the language of the site matches the current users
     * preferred language settings
     *
     * @param int $siteID the ID of the site a preferred language site
     *        should be found for
     * @return NavigationSite - the site with preferred language, current
     *         site if preferred isn't available
     */
    private function _getAutoLanguageSite($siteID) {
      $site = $this->_navigation->getSiteByID($siteID);
      $parent = $site->getLanguageParent();
      // check if parent language is preferred
      if ($parent) {
        if ($parent->getLanguage() == $this->_user->getPreferredLanguage()) {
          return $parent;
        }
        // if a parent exists, retrieve possible siblings of current site and check
        $siblings = $parent->getLanguageChildren();
        foreach ($siblings as $sibling) {
          if ($sibling->getLanguage() == $this->_user->getPreferredLanguage()) {
            return $sibling;
          }
        }
      }
      $children = $site->getLanguageChildren();
      if ($children) {
        foreach ($children as $child) {
          if ($child->getLanguage() == $this->_user->getPreferredLanguage()) {
            return $child;
          }
        }
      }

      return $site;
    }

    /**
     * Return link of siteindex module or if not available get link to root page of first tree available.
     * (main, footer, landing pages, login)
     *
     * @param NavigationSite $activeSite
     * @return string $link - link to rootpage
     */
    private function _getSiteSelectionLink(NavigationSite $activeSite)
    {
      // site id
      $id = $activeSite->getID();
      $link = '';

      // Always link to siteindex module if it is available
      if ($this->_user->AvailableModule("siteindex", $id)) {
        $link = "index.php?action=mod_siteindex&amp;site={$id}";
        return $link;
      }

      $mainRoot = $activeSite->getRootPage(Navigation::TREE_MAIN);
      $footerRoot = $activeSite->getRootPage(Navigation::TREE_FOOTER);
      $hiddenRoot = $activeSite->getRootPage(Navigation::TREE_HIDDEN);
      $pagesRoot = $activeSite->getRootPage(Navigation::TREE_PAGES);
      $loginRoot = $activeSite->getRootPage(Navigation::TREE_LOGIN);

      if ($this->_user->AvailablePath($mainRoot->getPath(), $id, $mainRoot->getTree())) {
        $link = "index.php?action=content&amp;site={$id}&amp;page={$mainRoot->getID()}";
      }
      else if ($this->_user->AvailablePath($footerRoot->getPath(), $id, $footerRoot->getTree())) {
        $link = "index.php?action=content&amp;site={$id}&amp;page={$footerRoot->getID()}";
      }
      else if ($this->_user->AvailablePath($pagesRoot->getPath(), $id, $pagesRoot->getTree())) {
        $link = "index.php?action=content&amp;site={$id}&amp;page={$pagesRoot->getID()}";
      }
      else if ($this->_user->AvailablePath($loginRoot->getPath(), $id, $loginRoot->getTree())) {
        $link = "index.php?action=content&amp;site={$id}&amp;page={$loginRoot->getID()}";
      }
      else if ($this->_user->AvailablePath($hiddenRoot->getPath(), $id, $hiddenRoot->getTree())) {
        $link = "index.php?action=content&amp;site={$id}&amp;page={$hiddenRoot->getID()}";
      }
      // no root page available - check for permitted paths
      // Since 3.1.2 there is no drop down menu available if permitted paths are set and
      // link target is always cms index.
      else if ($this->_user->getPermittedPaths($id))
      {
        // link to first page available.
        foreach ($this->_user->getPermittedPaths($id) as $tree => $paths)
        {
          // get first permitted path, since 3.1.2.
          // Before 3.1.2 last permitted path link was set.
          $path = $paths[0];
          $sql = ' SELECT CIID '
               . " FROM {$this->table_prefix}contentitem "
               . " WHERE CIIdentifier = '$path' "
               . " AND FK_SID = $id ";
          $pageId = $this->db->GetOne($sql);
          $link = "index.php?action=content&amp;site={$id}&amp;page={$pageId}";
          break;
        }
      }
      // get link of first available language child, if user has not got access to
      // site
      else if (!$this->_user->AvailableSite($activeSite->getID(), true)) {
        $children = $activeSite->getLanguageChildren();
        foreach ($children as $child)
        {
          if (!$this->_user->AvailableSite($child->getID())) {
            continue;
          }
          $link = $this->_getSiteSelectionLink($child);
          break;
        }
      }

      // No available site, so link to cms index
      if (!$link) {
        $link = 'index.php';
      }

      return $link;
    }

    /**
     * Gets the site ids of the left drop down menu.
     *
     * @return array
     *         Contains all site ids and may language children site ids
     *         for left navigation.
     */
    private function _getMenuLeftNavigationSites()
    {
      $sites = array();

      foreach ($this->_navigation->getRootSites() as $rootSite) {
        $this->_addNavigationPortalItem($sites, $rootSite);
      }

      return $sites;
    }

    /**
     * Gets the site ids of the right drop down menu.
     *
     * @return array
     *         Contains all language children site ids of the
     *         for active site.
     */
    private function _getMenuRightNavigationSites()
    {
      $sites = array();

      $activeSite = $this->_navigation->getSiteByID($this->site_id);

      // Active site is language parent
      if ($activeSite->hasLanguageChildren())
      {
        if ($this->_user->AvailableSite($activeSite->getID(), true)) {
          $sites[] = $activeSite->getID();
        }
        $children = $activeSite->getLanguageChildren();
        foreach ($children as $child) {
          if ($this->_user->AvailableSite($child->getID(), true)) {
            $sites[] = $child->getID();
          }
        }
      }
      // Active site is language child, so get parent
      else if ($activeSite->getLanguageParent()) {
        $parent = $activeSite->getLanguageParent();
        if ($this->_user->AvailableSite($parent->getID(), true)) {
          $sites[] = $parent->getID();
        }
        $children = $parent->getLanguageChildren();
        foreach ($children as $child) {
          if ($this->_user->AvailableSite($child->getID(), true)) {
            $sites[] = $child->getID();
          }
        }
      }

      return $sites;
    }

    /**
     * Add a portal site and all subportal site items to the site selection
     * array ($items).
     *
     * @param array &$sites
     *        The array containing site ids.
     * @param NavigationSite $site
     *        The site to add.
     */
    private function _addNavigationPortalItem(&$sites, NavigationSite $site)
    {
      if ($this->_user->AvailableSite($site->getID(), true)) {
        $sites[] = $site->getID();
      }
      // Look for available language children
      else if ($site->hasLanguageChildren()) {
        $children = $site->getLanguageChildren();
        $langChild = null;
        foreach ($children as $child) {
          if (!$this->_user->AvailableSite($child->getID(), true)) {
            continue;
          }
          // Set always first available language child
          if (!$langChild) {
            $langChild = $child;
          }
          // If a language child with preferred language is available store it and stop the loop
          else if ($child->getLanguage() == $this->_user->getPreferredLanguage()) {
            $langChild = $child;
            break;
          }
        }
        // Add language child to $sites
        if ($langChild) {
          $sites[] = $langChild->getID();
        }
      }

      // Call method itself recursively for all portal children
      foreach ($site->getPortalChildren() as $child) {
        $this->_addNavigationPortalItem($sites, $child);
      }
    }

    /**
     * Gets the language navigation dropdown details for given site.
     * Link, label, portal, active status.
     *
     * @param NavigationSite $site
     *
     * @return array
     */
    private function _getNavigationLanguageItemDetails(NavigationSite $site)
    {
      $link = $this->_getSiteSelectionLink($site);
      $siteTitle = $this->_allSites[$site->getID()];
      $languageItem = array (
        'm_nv_label_lang'  => parseOutput($siteTitle),
        'm_nv_link_lang'   => $link,
        'm_nv_site_active' => ($site->getID() == $this->site_id) ? 'active' : '',
        'm_sid_lang'       => $site->getLanguage(),
      );

      return $languageItem;
    }

    /**
     * Gets the site navigation dropdown details for given site.
     * Link, label, portal, active status and quicklinks.
     *
     * @param NavigationSite $site
     */
    private function _getNavigationPortalItemDetails($site)
    {
      $langChild = null;
      // If our site is a language children, get its parent
      if ($site->getLanguageParent()) {
        $langChild = $site;
        $site = $site->getLanguageParent();
      }

      // use page's language site general label, if available
      $siteTitle = $this->_getSiteTitle($site);
      $linkSite = $this->_getAutoLanguageSite($site->getID());
      $link = $this->_getSiteSelectionLink($linkSite);

      $siteActive = false;
      // Determine if site is current active site
      if ($site->getID() == $this->site_id) {
        $siteActive = true;
      }
      // Look for active language children
      else if ($site->getLanguageChildren()) {
        $children = $site->getLanguageChildren();
        foreach ($children as $child) {
          if ($child->getID() == $this->site_id) {
            $siteActive = true;
          }
        }
      }

      $content = array (
        'm_nv_label'       => parseOutput($siteTitle),
        'm_nv_link'        => $link,
        'm_nv_site_active' => $siteActive ? 'active' : '',
        'm_nv_site_portal' => $site->getPortalParent() ? 'unknown' : 'portal',
        'm_nv_site_id'     => $site->getID(),
      );

      return $content;
    }

    /**
     * Gets the site title of given site.
     *
     * @param NavigationSite $site
     * @return string
     *         The site title.
     */
    private function _getSiteTitle($site) {
      global $_LANG;
      $siteTitle = isset($_LANG['global_sites_backend_language_site_general_label'][$site->getID()]) ?
                   $_LANG['global_sites_backend_language_site_general_label'][$site->getID()] :
                   $this->_allSites[$site->getID()];
      return $siteTitle;
    }

    /**
     * Gets the quicklinks (root pages) of given NavigationSite
     *
     * @param NavigationSite $site
     *        The site that is used to get the root pages
     * @return string
     *         Contains quicklinks icons of root pages or empty string if no root
     *         pages are available of current user.
     */
    private function _getQuickLinks(NavigationSite $site) {
      global $_LANG;

      $rootPages = $site->getRootPages();
      $quicklinks = '';

      // Always link to siteindex module if it is available
      if ($this->_user->AvailableModule('siteindex', $site->getID())) {
        $quicklinks .= sprintf(
          $_LANG['global_quicklink_siteindex'],
          'index.php?action=mod_siteindex&amp;site=' . $site->getID(),
          $this->action === 'mod_siteindex' ? 'active' : ''
        );
      }

      /* @var NavigationPage $root */
      foreach ($rootPages as $root) {
        if (is_array($root)) {
          continue;
        }
        $link = $this->_getQuickLink($root);
        if ($link) {
          $activeClass = '';
          // current tree
          if ($root->getTree() === $this->_getCurrentTree()) {
            // if on main tree but editing the siteindex than of course
            // the siteindex menu item is active and we do not provide an
            // active class here
            if ($root->getTree() === 'main') {
              $activeClass = !($this->action === 'mod_siteindex') ? 'active' : '';
            }
            else {
              $activeClass = 'active';
            }
          }

          $quicklinks .= sprintf(
            $_LANG["global_quicklink_{$root->getTree()}"], $link, $activeClass
          );
        }
      }

      return $quicklinks;
    }

    /**
     * Gets the link for a root page.
     *
     * @param NavigationPage $root
     * @return Ambiguous
     */
    private function _getQuickLink(NavigationPage $root)
    {
      $link = '';
      $siteId = $root->getSite()->getID();
      // Get root link
      // Do not display a quicklink for the user-tree ( = Navigation::getRootPages()
      // returns an array) and pages the user has not got permission to.
      // If $root is an array we got the user tree, see NavigationSite::_readRootPages
      if ($this->_user->AvailablePage($root)) {
        $link = "index.php?action=content&amp;site={$siteId}&amp;page={$root->getID()}";
      }
      // Get first available link of permitted paths
      else if ($this->_user->getPermittedPaths()) {
        $permittedPaths = $this->_user->getPermittedPaths();
        if (isset($permittedPaths[$siteId][$root->getTree()])) {
          $paths = $permittedPaths[$siteId][$root->getTree()];
          $path = $paths[0];
          $sql = ' SELECT CIID '
               . " FROM {$this->table_prefix}contentitem "
               . " WHERE CIIdentifier = '$path' "
               . " AND FK_SID = $siteId ";
          $pageId = $this->db->GetOne($sql);
          $link = "index.php?action=content&amp;site={$siteId}&amp;page={$pageId}";
        }
      }

      return $link;
    }

    /**
     * Returns the current tree the user is navigating on when editing content
     * pages.
     *
     * @return null|string
     *         null will be returned if there is no page edited, but i.e a module
     */
    private function _getCurrentTree()
    {
      $page = $this->_navigation->getCurrentPage();
      return $page ? $page->getTree() : null;
    }
  }
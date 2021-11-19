<?php

  /**
   * User Permission and Data Class
   *
   * $LastChangedDate: 2014-04-02 12:23:53 +0200 (Mi, 02 Apr 2014) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class User
  {
    /**
     * The database object
     *
     * @var Db
     */
    private $_db;

    /**
     * The database table prefix
     *
     * @var string
     */
    private $_tablePrefix;

    private $id;
    private $nick;
    private $email;
    private $language;
    private $preferredLanguage;
    private $permitted_modules;
    /**
     * Stores all paths for available sites / trees the user has permission to
     * NOTE: Sites the user hasn't got permission to, aren't set within the array
     *
     * @var array e.g. array(1 => array('main' => 'path/to/pages'))
     *      the user has permission to edit the 'main' navigation tree of site 1
     *      with path 'path/to/pages'
     */
    private $permitted_paths;
    /**
     * Stores permitted sites / trees the user has permission to
     * NOTE: Sites the user hasn't got permission to, aren't set within the array
     *
     * @var array e.g. array(1 => array('main' => 1)) - permission to site 1 'main' navigation tree
     */
    private $permitted_sites;
    /**
     * Stores all permitted sites / trees the user has permission to without the user tree.
     * Used for site navigation.
     * NOTE: Sites the user hasn't got permission to, aren't set within the array
     *
     * @var array e.g. array(1 => array('main' => 1)) - permission to site 1 'main' navigation tree
     */
    private $_navPermittedSites = array();
    /**
     * Stores site ids, where the user has permission to the siteindex module
     *
     * @var array e.g. array(1 => array('main' => 1)) - permission to site 1 'main' navigation tree
     */
    private $_permittedSiteIndex;
    /**
     * Stores the first optional module available to the user. (module shortname)
     *
     * @var string
     *      The module shortname
     */
    private $_optionalModule = '';
    /**
     * Contains permitted submodules if specified. Modules without any submodule
     * restrictions are not stored in this array (all submodules available).
     *
     * @var array
     *      key = module shortname
     *      value = array of submodule names
     */
    private $_permittedSubmodules = null;

    /**
     * User permission to create new content items.
     *
     * @var bool
     */
    private $_createContent = false;

    /**
     * User permission to delete existing content items.
     *
     * @var bool
     */
    private $_deleteContent = false;

    /**
     * User firstname
     *
     * @var string
     */
    private $_firsname = '';

    /**
     * User lastname
     *
     * @var string
     */
    private $_lastname = '';

    /**
     * User session id
     *
     * @var string
     */
    private $_sId = '';

    /**
     * User - Last login date-time
     *
     * @var string
     */
    private $_lastLogin = '';

    /**
     * User login count
     *
     * @var int
     */
    private $_countLogins = 0;

    /**
     * User password
     *
     * @var string
     */
    private $_password = '';

    /**
     * User deleted
     *
     * @var bool
     */
    private $_deleted = false;

    /**
     * User blocked
     *
     * @var bool
     */
    private $_blocked = false;

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Constructor                                                                           //
    // sets the default values                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function __construct($SID,$UID)
    {
      global $db;

      $this->_db = $db;
      $this->_tablePrefix = ConfigHelper::get('table_prefix');

      $sql = " SELECT UID, USID, UNick, UPW, ULanguage, UPreferredLanguage, "
           . "        ULastLogin, UCountLogins, UEmail, UDeleted, UFirstname, "
           . "        ULastname, UBlocked, UModuleRights "
           . " FROM {$this->_tablePrefix}user "
           . " WHERE USID='{$SID}' AND UID={$UID}";
      $result = $this->_db->query($sql);
      $row = $this->_db->fetch_row($result);

      if ($row)
      {
        $this->id = $row['UID'];
        $this->nick = $row['UNick'];
        $this->email = $row['UEmail'];
        $this->language = $row['ULanguage'];
        $this->preferredLanguage = $row['UPreferredLanguage'];
        $this->_firstname = $row['UFirstname'];
        $this->_lastname = $row['ULastname'];
        $this->_sId = $row['USID'];
        $this->_lastLogin = $row['ULastLogin'];
        $this->_countLogins = $row['UCountLogins'];
        $this->_password = $row['UPW'];
        $this->_deleted = ($row['UDeleted']) ? true : false;
        $this->_blocked = ($row['UBlocked']) ? true : false;
        $this->permitted_modules = explode(',', $row['UModuleRights']);

        $sql = " SELECT FK_SID, UPaths, UScope "
             . " FROM {$this->_tablePrefix}user_rights "
             . " WHERE FK_UID={$this->id} ";
        $result1 = $this->_db->query($sql);
        while ($row1 = $this->_db->fetch_row($result1))
        {
          $siteId = (int)$row1['FK_SID'];
          $scope = $row1['UScope'];
          $paths = trim($row1['UPaths']);

          if ($scope == 'siteindex') {
            $this->_permittedSiteIndex[$siteId] = 1;
          }

          $this->permitted_sites[$siteId][$scope] = 1;
          $this->_navPermittedSites[$siteId][$scope] = 1;
          if ($paths) {
            $this->permitted_paths[$siteId][$scope] = explode(',', $paths);
          }
        }
        $this->_db->free_result($result1);

        foreach ($this->_navPermittedSites as $siteId => $site) {
          if (count($site) == 1 && isset($site[Navigation::TREE_USER])) {
            unset($this->_navPermittedSites[$siteId]);
          }
        }
        if (!$this->permitted_modules) {
          $this->_optionalModule = '';
        }
        else
        {
          $modules = $this->permitted_modules;
          foreach ($modules as $key => &$val) {
            $val = ' "' . $val . '" ';
          }
          $mShortnames = implode(', ',$modules);

          $sql = ' SELECT MShortname '
               . " FROM {$this->_tablePrefix}moduletype_backend "
               . ' WHERE MRequired != 1 '
               . " AND MShortname IN ({$mShortnames}) "
               . ' ORDER BY MPosition ASC ';
          $this->_optionalModule = $this->_db->GetOne($sql);
        }

        $createProhibited = ConfigHelper::get('m_user_create_content_prohibited');
        // The user is allowed to create new content items
        if ($this->nick && !in_array($this->nick, $createProhibited)) {
          $this->_createContent = true;
        }
        // The user is allowed to delete new content items
        if ($this->nick && !in_array($this->nick, $createProhibited)) {
          $this->_deleteContent = true;
        }
      }
      else
      {
        $this->id = $this->nick = $this->permitted_modules = $this->_optionalModule
                  = $this->permitted_paths = $this->path = $this->_permittedSiteIndex
                  = $this->_firstname = $this->_lastname = $this->_lastLogin
                  = $this->_password = $this->_sId = "";
        $this->_countLogins = 0;
        $this->language = "german";
        $this->_permittedSubmodules = null;
        $this->_createContent = $this->_deleteContent = $this->_deleted
                              = $this->_blocked = false;
      }
      $this->_db->free_result($result);
    }

    public function setUser ($SID,$UID)
    {
      $this->__construct($SID, $UID);
    }

    public function getID()
    {
      return $this->id;
    }

    public function getNick()
    {
      return $this->nick;
    }

    public function getEmail()
    {
      return $this->email;
    }

    public function getLanguage()
    {
      return $this->language;
    }

    public function getFirstname()
    {
      return $this->_firstname;
    }

    public function getLastname()
    {
      return $this->_lastname;
    }

    public function getPreferredLanguage()
    {
      return $this->preferredLanguage;
    }

    public function getSID()
    {
      return $this->_sId;
    }

    public function getCountLogins()
    {
      return $this->_countLogins;
    }

    public function getLastLogin()
    {
      return $this->_lastLogin;
    }

    public function getPassword()
    {
      return $this->_password;
    }

    public function getDeleted()
    {
      return $this->_deleted;
    }

    public function isBlocked()
    {
      return $this->_blocked;
    }

    public function isValid()
    {
      if ($this->id > 0) {
        return true;
      }
      else {
        return false;
      }
    }

    public function ContentPermitted($currentSiteID, $currentPathID, $currentAction)
    {
      if ($currentSiteID && !isset($this->permitted_sites[$currentSiteID]) && $currentPathID) {
        return false;
      }

      // special handling for change password function, which isn't a module
      // the 'action' get parameter is 'change_pw'
      if ($currentSiteID && $currentAction == 'change_pw') {
        return true;
      }

      // structure links action requested
      if ($currentAction == ContentItem::ACTION_STRUCTURELINKS)
      {
        // not available for user or site
        if (!$this->AvailableModule('structurelinks', $currentSiteID) ||
            !in_array($currentSiteID, ConfigHelper::get('m_structure_links')))
        {
          return false;
        }
      }

      $permittedPath = 0;
      if ($currentSiteID && mb_substr($currentAction,0,4) != "mod_")
      {
        $sql = "SELECT CIIdentifier, CTree "
              ."FROM {$this->_tablePrefix}contentitem "
              ."WHERE CIID = {$currentPathID}";
        $row = $this->_db->GetRow($sql);
        if ($row) {
          $pagePath = $row["CIIdentifier"];
          $pageTree = $row["CTree"];
        }
        else {
          $pagePath = '';
          $pageTree = '';
        }

        if (!  isset($this->permitted_paths[$currentSiteID][$pageTree])
            && isset($this->permitted_sites[$currentSiteID][$pageTree])) {
          $permittedPath = 1;
        }
        else if (isset($this->permitted_paths[$currentSiteID][$pageTree]))
        {
          foreach ($this->permitted_paths[$currentSiteID][$pageTree] as $startpath)
          {
            if ($pagePath == $startpath || mb_substr($pagePath, 0, mb_strlen($startpath) + 1) == "$startpath/") {
              $permittedPath = 1;
            }
          }
        }
      }

      $permittedModule = 0;
      if (is_array($this->permitted_modules))
      {
        if (   mb_substr($currentAction,0,4) == "mod_"
            && in_array(mb_substr($currentAction,4),$this->permitted_modules)
        ) {
          $permittedModule = 1;
        }
        if (  mb_substr($currentAction,0,13) == "mod_response_"
            && in_array(mb_substr($currentAction,13),$this->permitted_modules)
        ) {
          $permittedModule = 1;
        }
      }
      if (($currentAction == "mod_siteindex" && !isset($this->permitted_sites[$currentSiteID]))) {
        return false;
      }
      else if (   $currentAction == "mod_cmsindex"
               || $currentAction == "mod_bugtracking"
               || $currentAction == "mod_response_bugtracking"
               || $permittedPath
               || $permittedModule
      ) {
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * Checks if given NavigationPage is available for
     * this user.
     * It determines if site and path of the page are
     * permitted.
     *
     * @param NavigationPage $navigationPage
     *        The NavigationPage to check.
     * @return boolean
     *         True, if path is available, otherwise false.
     */
    public function AvailablePage(NavigationPage $navigationPage) {
      if (!$navigationPage) {
        return false;
      }

      return $this->AvailablePath($navigationPage->getPath(), $navigationPage->getSite()->getID(), $navigationPage->getTree());
    }

    public function AvailablePath($currentPath, $currentSiteID, $currentTree)
    {
      if (!$currentSiteID || !$currentTree) {
        return false;
      }
      // if the user hasn't got permission to current site and tree and a path is specified
      if (!isset($this->permitted_sites[$currentSiteID][$currentTree])) {
        return false;
      }
      // if all paths are permitted (= array is empty)
      if (empty($this->permitted_paths[$currentSiteID][$currentTree])) {
        return true;
      }

      // there are only some paths permitted -> check if path is a permitted path
      foreach ($this->permitted_paths[$currentSiteID][$currentTree] as $path)
      {
        if ($currentPath == $path || mb_substr($currentPath, 0, mb_strlen($path) + 1) == "$path/") {
          return true;
        }
      }

      return false;
    }

    public function AvailableModule($module, $siteID)
    {
      if (!is_array($this->permitted_modules)) {
        return false;
      }

      if (in_array($module, $this->permitted_modules))
      {
        // if requested module is 'siteindex' check if it is available for the current site
        if ($module == 'siteindex')
        {
          if (is_array($this->_permittedSiteIndex) && key_exists($siteID, $this->_permittedSiteIndex)) {
            return true;
          }
          else {
            return false;
          }
        }
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * Determine if the user has permission to the SiteIndex of the specified site
     *
     * @param int $siteID
     */
    public function AvailableSiteIndex($siteID)
    {
      if (isset($this->_permittedSiteIndex[$siteID])) {
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * Determine if the user has permission to access site of given site id.
     *
     * @param $current_site_id
     *        Site id to check.
     * @param boolean $navigation
     *        If set to true User::_navPermittedSites will be checked
     *        first. Default value is false.
     * @return bool
     *         True if user has got access to site with given site id,
     *         otherwise false
     */
    public function AvailableSite($current_site_id, $navigation=false)
    {
      if ($navigation && isset($this->_navPermittedSites[$current_site_id])) {
        return true;
      }
      else if ($navigation) {
        return false;
      }

      if (isset($this->permitted_sites[$current_site_id])) {
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * Check if the submodule is available for the current user.
     *
     * @param $module
     *        The module shortname (from database).
     * @param $submodule
     *        The name of the submodule from the Module::subClasses array.
     *
     * @return bool
     */
    public function AvailableSubmodule($module, $submodule)
    {
      if ($this->_permittedSubmodules === null) {
        $this->_loadPermittedSubmodules();
      }

      // The module is not set within the permitted submodules array -> all
      // submodules are available.
      if (!isset($this->_permittedSubmodules[$module])) {
        return true;
      }
      else
      {
        // Is the submodule available?
        if (in_array($submodule, $this->_permittedSubmodules[$module]))
        {
          return true;
        }

        return false;
      }
    }

    /**
     * Check if the user has permission to create new content items.
     *
     * @return bool
     */
    public function createContentPermitted()
    {
      return $this->_createContent;
    }

    /**
     * Check if the user has permission to delete existing content items.
     *
     * @return bool
     */
    public function deleteContentPermitted()
    {
      return $this->_deleteContent;
    }

    public function getPermittedModules()
    {
      return $this->permitted_modules;
    }

    /**
     * Returns permitted paths array - with no parameters specified an array
     * containing the paths of all sites and trees available is returned
     *
     * @param int $currentSiteId - if specified, only paths for the given site
     *        are returned (containing all trees available)
     * @param string $currentTree - if specified (and $currentSiteId is specified)
     *        only paths for the given tree of current site are returned
     *
     * @return array - the permitted paths: depending on the parameters provided
     *         different levels are returned (all sites/ site / tree)
     */
    public function getPermittedPaths($currentSiteId = 0, $currentTree = '')
    {
      if ($currentSiteId && $currentTree) {
        return $this->permitted_paths[$currentSiteId][$currentTree];
      }
      if ($currentSiteId) {
        return $this->permitted_paths[$currentSiteId];
      }
      else {
        return $this->permitted_paths;
      }
    }

    /**
     * Return permitted sites.
     */
    public function getPermittedSites()
    {
      return $this->permitted_sites;
    }

    /**
     * Return permitted sites for navigation.
     */
    public function getNavPermittedSites()
    {
      return $this->_navPermittedSites;
    }

    /**
     * Return the submodule permissions
     *
     * @return array | null
     *         The array containing submodule permissions.
     */
    public function getPermittedSubmodules()
    {
      if ($this->_permittedSubmodules === null) {
        $this->_loadPermittedSubmodules();
      }

      return $this->_permittedSubmodules;
    }

    /**
     * Reset all user settings
     */
    public function reset()
    {
      $this->id = $this->nick = $this->permitted_paths
                = $this->permitted_modules = $this->_permittedSiteIndex = "";
      $this->language = "german";
      $this->_permittedSubmodules = null;
    }

    /**
     * Returns true if the user has not got permission to any sites. A user might
     * has not got permission to any site, if he is only working with modules.
     *
     * @return bool true | false
     */
    public function noSites()
    {
      if (!$this->permitted_sites) {
        return true;
      }
      return false;
    }

    /**
     * Return the first optional module available to the user. (optional modules
     * are the opposite of so-called required modules, which are available to
     * all users and required by the EDWIN CMS).
     *
     * @return string | null | false - the module shortname of the first
     *         optional module available to the user. Empty string / false /
     *         null if there isn't a module available.
     */
    public function getOptionalModule()
    {
      return $this->_optionalModule;
    }

    /**
     * Load the permitted subodules array.
     */
    private function _loadPermittedSubmodules()
    {
      // The user is not valid, do not load submodule permissions.
      if (!$this->isValid()) {
        return;
      }
      $this->_permittedSubmodules = array();
      $sql = ' SELECT FK_UID, URMModuleShortname, URMSubmodules '
           . " FROM {$this->_tablePrefix}user_rights_submodules "
           . " WHERE FK_UID = $this->id ";
      $result = $this->_db->query($sql);
      // foreach submodule added, store the permitted submodules.
      // Submodules with no restrictions are not stored within the
      // mc_user_rights_submodules database table and therefore are not stored
      // within the _permittedSubmodules array.
      while ($row = $this->_db->fetch_row($result)) {
        $this->_permittedSubmodules[$row['URMModuleShortname']] = explode(',', $row['URMSubmodules']);
      }
    }
  }



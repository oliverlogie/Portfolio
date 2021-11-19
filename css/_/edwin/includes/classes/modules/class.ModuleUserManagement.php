<?php

  /**
   * UserManagement Module Class
   *
   * $LastChangedDate: 2018-01-31 16:06:04 +0100 (Mi, 31 Jan 2018) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ModuleUserManagement extends Module
  {
    protected $_prefix = 'um';

    /**
     * Stores all module shortnames of modules required for using EDWIN Backend
     *
     * @var array - contains the module shortnames
     */
    private $_requiredModules = null;

    /**
     * Stores different navigation trees available
     * NOTE: Changing this array requires adjustment of this class
     *
     * @var array - containes the navigation tree names
     */
    private $_trees = array('main', 'footer', 'login', 'pages', 'hidden', 'user');

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){
      $this->_readRequiredModules();

      if (isset($_POST["process"]) && $this->action[0]=="new") $this->create_content();
      if (isset($_POST["process"]) && $this->action[0]=="edit") $this->edit_content();
      if (isset($_GET["did"])) $this->delete_content((int)$_GET["did"]);

      if (empty($this->action[0]))
        return $this->list_content();
      else if ($this->action[0] == 'export')
        return $this->_getCsv();
      else
        return $this->get_content();
    }

    protected function _getContentLeftLinks()
    {
      global $_LANG;

      $links = parent::_getContentLeftLinks();
      if (empty($this->action[0])) { // list view displayed
        $links[] = array($this->_parseUrl('export'), $this->_langVar('moduleleft_export_label'));
      }

      return $links;
    }

    private function create_content()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $passwordLength = (int)ConfigHelper::get('m_password_length');
      $quality = (int)ConfigHelper::get('m_password_quality');
      $passwordTypes = ConfigHelper::get('m_password_types');

      // Retrieve permitted sites foreach navigation tree & siteindex
      // = so called scope
      $permittedSites = array();
      $permittedSites['siteindex'] = $this->_readPermittedSites('index');
      foreach ($this->_trees as $tree) {
        $permittedSites[$tree] = $this->_readPermittedSites($tree);
      }

      $nick = $post->readString('um_nick', Input::FILTER_PLAIN);
      $email = $post->readString('um_email', Input::FILTER_PLAIN);
      $password = $post->readString('um_password', Input::FILTER_NONE);
      $password2 = $post->readString('um_password2', Input::FILTER_NONE);
      $firstname = $post->readString('um_firstname', Input::FILTER_PLAIN);
      $lastname = $post->readString('um_lastname', Input::FILTER_PLAIN);
      $blocked = $post->exists('um_blocked') ? 1 : 0;
      $blockedMessage = $post->readString('um_blocked_message', Input::FILTER_PLAIN);

      $sql = " SELECT UID "
           . " FROM {$this->table_prefix}user "
           . " WHERE UDeleted = 0 "
           . " AND UNick = '{$this->db->escape($nick)}' ";
      $user_exists = $this->db->GetOne($sql);
      $sql = " SELECT UID "
           . " FROM {$this->table_prefix}user "
           . " WHERE UDeleted = 0 "
           . " AND UEmail = '{$this->db->escape($email)}' ";
      $email_exists = $this->db->GetOne($sql);

      $pwHelper = new Password();
      $pwQuality = $pwHelper->setPassword($password)->getCalculatedQuality();

      $result = 0;
      if ($user_exists) {
        $this->setMessage(Message::createFailure($_LANG['um_message_user_exists']));
      }
      else if ($email_exists) {
        $this->setMessage(Message::createFailure($_LANG['um_message_email_exists']));
      }
      else if (!$nick || !$email) {
        $this->setMessage(Message::createFailure($_LANG['um_message_insufficient_input']));
      }
      else if (!Validation::isEmail($email)) {
        $this->setMessage(Message::createFailure($_LANG['um_message_invalid_email']));
      }
      else if ($password && $password != $password2) {
        $this->setMessage(Message::createFailure($_LANG['um_message_password_mismatch']));
      }
      else if (mb_strlen($password) < $passwordLength) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['um_message_invalid_too_short'], $passwordLength)));
      }
      else if ($pwQuality < $quality) {
        $characterTypes = '';
        for ($i = 0; $i < $quality; $i++) {
          $characterTypes .= $_LANG['um_password_character_type'][$passwordTypes[$i]];

          if ($i + 2 < $quality) {
            $characterTypes .= $_LANG['um_message_invalid_too_weak_spacer'];
          } else if ($i + 1 < $quality) {
            $characterTypes .= $_LANG['um_message_invalid_too_weak_lastspacer'];
          }
        }

        $this->setMessage(Message::createFailure(sprintf($_LANG['um_message_invalid_too_weak'], $characterTypes)));
      }
      else
      {
        $passwordMD5 = md5($password);

        $sqlArgs = array(
          'UNick' => "'{$this->db->escape($nick)}'",
          'UEmail' => "'{$this->db->escape($email)}'",
          'UPW' => "'$passwordMD5'",
          'UFirstname' => "'{$this->db->escape($firstname)}'",
          'ULastname' => "'{$this->db->escape($lastname)}'",
          'UBlocked' => $blocked,
          'UBlockedMessage' => "'{$this->db->escape($blockedMessage)}'",
          'UModuleRights'   => "'" . implode(',', $this->_getPermittedModules()) . "'",
        );

        $sqlFields = implode(',', array_keys($sqlArgs));
        $sqlValues = implode(',', $sqlArgs);

        $sql = " INSERT INTO {$this->table_prefix}user "
             . " ($sqlFields) VALUES ($sqlValues) ";
        $result = $this->db->query($sql);
        $this->item_id = $this->db->insert_id();

        foreach ($permittedSites as $scope => $sites) {
          foreach ($sites as $key => $value) {
            $paths = $value['paths'] ? "'{$value['paths']}'" : "''";
            $sql = " INSERT INTO {$this->table_prefix}user_rights (FK_UID, FK_SID, UPaths, UScope) "
                  ." VALUES ("
                  ."     {$this->item_id}, {$value['id']}, {$paths} , '{$scope}'"
                  ." ) ";
            $result = $this->db->query($sql);
          }
        }

        $this->_updatePermittedSubmodules($this->item_id, $this->_getPermittedSubmodules());

        if ($this->_redirectAfterProcessingRequested('list')) {
          $this->_redirect($this->_getBackLinkUrl(),
              Message::createSuccess($_LANG['um_message_newitem_success']));
        }
        else {
          $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
              Message::createSuccess($_LANG['um_message_newitem_success']));
        }
      }
    }

    private function edit_content()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      // Retrieve permitted sites foreach navigation tree & siteindex
      // = so called scope
      $permittedSites = array();
      $permittedSites['siteindex'] = $this->_readPermittedSites('index');
      foreach ($this->_trees as $tree) {
        $permittedSites[$tree] = $this->_readPermittedSites($tree);
      }
      $nick = $post->readString('um_nick', Input::FILTER_PLAIN);
      $email = $post->readString('um_email', Input::FILTER_PLAIN);
      $password = $post->readString('um_password', Input::FILTER_NONE);
      $password2 = $post->readString('um_password2', Input::FILTER_NONE);
      $firstname = $post->readString('um_firstname', Input::FILTER_PLAIN);
      $lastname = $post->readString('um_lastname', Input::FILTER_PLAIN);
      $blocked = $post->exists('um_blocked') ? 1 : 0;
      $blockedMessage = $post->readString('um_blocked_message', Input::FILTER_PLAIN);

      $sql = " SELECT UID "
           . " FROM {$this->table_prefix}user "
           . " WHERE UDeleted = 0 "
           . " AND UNick = '{$this->db->escape($nick)}' "
           . " AND UID <> $this->item_id ";
      $user_exists = $this->db->GetOne($sql);
      $sql = " SELECT UID "
           . " FROM {$this->table_prefix}user "
           . " WHERE UDeleted = 0 "
           . " AND UEmail = '{$this->db->escape($email)}' "
           . " AND UID <> $this->item_id ";
      $email_exists = $this->db->GetOne($sql);

      $result = 0;
      if ($user_exists) {
        $this->setMessage(Message::createFailure($_LANG['um_message_user_exists']));
      }
      else if ($email_exists) {
        $this->setMessage(Message::createFailure($_LANG['um_message_email_exists']));
      }
      else if (!$nick || !$email) {
        $this->setMessage(Message::createFailure($_LANG['um_message_insufficient_input']));
      }
      else if ($password && $password != $password2) {
        $this->setMessage(Message::createFailure($_LANG['um_message_password_mismatch']));
      }
      else {
        $passwordMD5 = md5($password);

        $sql = "UPDATE {$this->table_prefix}user "
             . "SET UNick = '{$this->db->escape($nick)}', "
             . "    UEmail = '{$this->db->escape($email)}', "
             . "    UFirstname = '{$this->db->escape($firstname)}', "
             . "    ULastname = '{$this->db->escape($lastname)}', "
             . "    UBlocked = $blocked, "
             . "    UBlockedMessage = '{$this->db->escape($blockedMessage)}', "
             . "    UModuleRights = '{$this->db->escape(implode(',', $this->_getPermittedModules()))}' "
             . ($password ? ", UPW = '$passwordMD5' " : '')
             . "WHERE UID = $this->item_id ";
        $this->db->query($sql);

        $sql = " DELETE FROM {$this->table_prefix}user_rights "
              ." WHERE FK_UID = {$this->item_id} ";
        $this->db->query($sql);

        foreach ($permittedSites as $scope => $sites) {
          foreach ($sites as $key => $value) {
            $paths = $value['paths'] ? "'{$value['paths']}'" : "''";
            $sql = " INSERT INTO {$this->table_prefix}user_rights (FK_UID, FK_SID, UPaths, UScope) "
                  ." VALUES ("
                  ."     {$this->item_id}, {$value['id']}, {$paths} , '{$scope}'"
                  ." ) ";
            $this->db->query($sql);
          }
        }

        $this->_updatePermittedSubmodules($this->item_id, $this->_getPermittedSubmodules());

        if (!$this->_getMessage()) {
          if ($this->_redirectAfterProcessingRequested('list')) {
            $this->_redirect($this->_getBackLinkUrl(),
                Message::createSuccess($_LANG['um_message_edititem_success']));
          }
          else {
            $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
                Message::createSuccess($_LANG['um_message_edititem_success']));
          }
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_content(){
      global $_LANG, $_LANG2, $_MODULES;

      // initialize variables for all sites
      $available_sites = array();
      foreach ($this->_allSites as $siteID => $siteTitle)
      {
        $siteTitle = ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($siteID));
        $available_sites[$siteID] = array(
          'site_title' => parseOutput($siteTitle),
          'site_checked_main' => '',
          'site_checked_footer' => '',
          'site_checked_hidden' => '',
          'site_checked_login' => '',
          'site_checked_pages' => '',
          'site_checked_user' => '',
          'site_checked_index' => '',
          'site_main_class' => '',
          'site_footer_class' => '',
          'site_hidden_class' => '',
          'site_login_class' => '',
          'site_pages_class' => '',
          'site_user_class' => '',
          'site_paths_main' => '',
          'site_paths_footer' => '',
          'site_paths_hidden' => '',
          'site_paths_login' => '',
          'site_paths_pages' => '',
          'site_main_info' => '',
          'site_footer_info' => '',
          'site_hidden_info' => '',
          'site_login_info' => '',
          'site_pages_info' => '',
          'site_id' => $siteID,
        );
      }
      $userTreeChecked = '';

      // load module types
      $available_modules = array();
      foreach ($_MODULES as $className => $shortName)
      {
        if (!in_array($shortName, $this->_requiredModules))
        {
          $available_modules[$shortName] = array (
            'module_title'        => (isset($_LANG["mod_".$shortName."_title"]) ? $_LANG["mod_".$shortName."_title"] : $className),
            'module_permitted'    => 0,
            'module_checked'      => "",
            'module_id'           => $shortName,
            'um_submodule_rights' => '',
          );
        }
      }
      $submoduleRightRows = array();

      $um_random_password = 0;
      $uPathsAvailable = false;
      if ($this->item_id){ // edit user -> load data
        $sql = " SELECT UID, UNick, UEmail, UFirstname, ULastname, UBlocked, "
             . "        UBlockedMessage, UModuleRights "
             . " FROM {$this->table_prefix}user "
             . " WHERE UID={$this->item_id} ";
        $row = $this->db->GetRow($sql);
        $um_nick = $row['UNick'];
        $um_email = $row['UEmail'];
        $um_password = '';
        $um_password2 = '';
        $um_firstname = $row['UFirstname'];
        $um_lastname = $row['ULastname'];
        $blocked = $row['UBlocked'];
        $blockedMessage = $row['UBlockedMessage'];

        // set modules
        $tmp = explode(',',$row['UModuleRights']);
        foreach ($tmp as $id) {
          if (isset($available_modules[$id]['module_permitted'])) {
            $available_modules[$id]['module_permitted'] = 1;
            $available_modules[$id]['module_checked'] = 'checked="checked"';
          }
        }

        // set submodules
        $sql = " SELECT URMModuleShortname, URMSubmodules "
             . " FROM {$this->table_prefix}user_rights_submodules "
             . " WHERE FK_UID = $this->item_id ";
        $submoduleRightRows = $this->db->GetAssoc($sql);

        $sql = " SELECT FK_SID, UPaths, UScope "
             . " FROM {$this->table_prefix}user_rights "
             . " WHERE FK_UID={$this->item_id} ";
        $result = $this->db->query($sql);
        while ($row = $this->db->fetch_row($result)) {

          $siteId = (int)$row['FK_SID'];
          $scope = $row['UScope'];
          $paths = $row['UPaths'];

          if ($scope == 'siteindex') {
            $available_sites[$siteId]["site_checked_index"] = 'checked="checked"';
          }
          else {
            $available_sites[$siteId]["site_permitted"] = 1;
            $available_sites[$siteId]["site_checked_{$scope}"] = 'checked="checked"';

            if ($paths) {
              $uPathsAvailable = true;
              $available_sites[$siteId]["site_paths_{$scope}"] = str_replace(",","\n",$paths);
              $available_sites[$siteId]["site_{$scope}_class"] = 'ed_highlight_bg';
              $available_sites[$siteId]["site_{$scope}_info"] = $_LANG['um_site_paths_label'];
            }
          }

          if (!$userTreeChecked && $scope == 'user') {
            $userTreeChecked = 'checked="checked"';
          }
        }

        $this->db->free_result($result);
        $um_function = "edit";
      }
      else { // new user
        $um_nick = "";
        $um_email = "";
        $pwHelper = new Password();
        $pwHelper->setLength(ConfigHelper::get('m_password_length'))
                 ->setQuality(ConfigHelper::get('m_password_quality'))
                 ->setTypes(ConfigHelper::get('m_password_types'))
                 ->create();
        $um_random_password = $pwHelper->getPassword();
        $um_password = $um_random_password;
        $um_password2 = $um_random_password;
        $um_firstname = '';
        $um_lastname = '';
        $blocked = '';
        $blockedMessage = '';

        if (isset($_POST["um_nick"])) $um_nick = strip_tags($_POST["um_nick"]);
        if (isset($_POST["um_email"])) $um_email = strip_tags($_POST["um_email"]);
        if (isset($_POST["um_firstname"])) $um_firstname = strip_tags($_POST["um_firstname"]);
        if (isset($_POST["um_lastname"])) $um_lastname = strip_tags($_POST["um_lastname"]);
        if (isset($_POST["um_blocked"])) $blocked = 'checked="checked"';
        if (isset($_POST["um_blocked_message"])) $blockedMessage = strip_tags($_POST["um_blocked_message"]);
        $um_function = "new";
      }

      // adjust module selection items: add submodules, check choosen submodules, ...
      foreach ($available_modules as $id => $vars) {

        $availableSubmodules = isset($submoduleRightRows[$id]) ? explode(',', $submoduleRightRows[$id]) : array();
        $items = array();
        foreach ($this->_getSubmodules($id) as $key => $classname) {
          $items[] = array(
              'um_module_id' => $id,
              'um_submodule_id' => $key,
              'um_submodule_title' => isset($_LANG['modtop_' . $classname]) ? $_LANG['modtop_' . $classname] : $key,
              'um_submodule_checked' => in_array($key, $availableSubmodules) ? 'checked="checked"' : '',
          );
        }

        $tplName = 'um_usermgmt_submodule_rights';
        $this->tpl->load_tpl($tplName, 'modules/ModuleUserManagement_module_submodules.tpl');
        $this->tpl->parse_loop($tplName, $items, 'submodules');

        $available_modules[$id]['um_submodule_rights'] = $this->tpl->parsereturn($tplName);
      }
      sort($available_modules);

      $um_hidden_fields = '<input type="hidden" name="action" value="mod_usermgmt" /><input type="hidden" name="action2" value="main;'.$um_function.'" /><input type="hidden" name="page" value="'.$this->item_id.'" />';

      $this->tpl->load_tpl('content_user', 'modules/ModuleUserManagement.tpl');
      $this->tpl->parse_if('content_user', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('um'));
      $this->tpl->parse_if('content_user', 'message_access_rights_available', $uPathsAvailable);
      $this->tpl->parse_loop('content_user', $available_sites, 'site_items');
      $this->tpl->parse_loop('content_user', $available_modules, 'module_items');
      $this->tpl->parse_if('content_user', 'random_pw', $um_random_password, array( 'um_auto_password_label' => $_LANG["um_auto_password_label"],
                                                                                    'um_auto_password' => $um_random_password ));
      $um_content = $this->tpl->parsereturn('content_user', array_merge(
        array (
          'um_nick' => $um_nick,
          'um_email' => $um_email,
          'um_password' => $um_password,
          'um_password2' => $um_password2,
          'um_firstname' => $um_firstname,
          'um_lastname' => $um_lastname,
          'um_blocked' => $blocked ? 'checked="checked"' : '',
          'um_blocked_message' => $blockedMessage,
          'um_nick_label' => $_LANG["um_nick_label"],
          'um_email_label' => $_LANG["um_email_label"],
          'um_firstname_label' => $_LANG["um_firstname_label"],
          'um_lastname_label' => $_LANG["um_lastname_label"],
          'um_password_label' => $_LANG["um_password_label"],
          'um_password2_label' => $_LANG["um_password2_label"],
          'um_site_paths_label' => $_LANG["um_site_paths_label"],
          'um_function_label' => $_LANG["um_function_".$um_function."_label"],
          'um_action' => "index.php",
          'um_hidden_fields' => $um_hidden_fields,
          'um_function_label2' => $_LANG["um_function_".$um_function."_label2"],
          'um_data_label' => $_LANG["um_data_label"],
          'um_site_rights_label' => $_LANG["um_site_rights_label"],
          'um_module_rights_label' => $_LANG["um_module_rights_label"],
          'um_module_action_boxes' => $this->_getContentActionBoxes(),
          'um_site_checked_user' => $userTreeChecked,
        ), $_LANG2['um']));

      return array(
          'content'      => $um_content,
          'content_left' => $this->_getContentLeft(true),
      );
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function delete_content($did)
    {
      global $_LANG;

      $result = $this->db->query("UPDATE ".$this->table_prefix."user SET UDeleted=1,USID='' WHERE UID=".$did);

      $this->setMessage(Message::createSuccess($_LANG['um_message_deleteitem_success']));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Contents in a List                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function list_content()
    {
      global $_LANG;

      // read users
      $user_items = array ();
      $result = $this->db->query("SELECT UID,UNick,UEmail from ".$this->table_prefix."user WHERE UDeleted=0 ORDER BY UNick ASC");
      while ($row = $this->db->fetch_row($result)){
        $user_items[] = array ( 'um_user_id' => intval($row["UID"]),
                                'um_user_nick' => parseOutput($row["UNick"]),
                                'um_user_email' => parseOutput($row["UEmail"]),
                                'um_delete_link' => "index.php?action=mod_usermgmt&amp;did=".$row["UID"],
                                'um_delete_label' => $_LANG["um_delete_label"],
                                'um_content_link' => "index.php?action=mod_usermgmt&amp;action2=main;edit&amp;page=".$row["UID"],
                                'um_content_label' => $_LANG["um_content_label"] );
      }
      $this->db->free_result($result);

      $this->tpl->load_tpl('content_teamlist', 'modules/ModuleUserManagement_list.tpl');
      $this->tpl->parse_if('content_teamlist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('um'));
      $this->tpl->parse_loop('content_teamlist', $user_items, 'user_items');
      $um_content = $this->tpl->parsereturn('content_teamlist', array ( 'um_deleteitem_question_label' => $_LANG["um_deleteitem_question_label"],
                                                                        'um_function_label' => $_LANG["um_function_list_label"],
                                                                        'um_function_label2' => $_LANG["um_function_list_label2"],
                                                                        'um_list_nick_label' => $_LANG["um_list_nick_label"],
                                                                        'um_list_email_label' => $_LANG["um_list_email_label"] ));

      return array(
          'content'      => $um_content,
          'content_left' => $this->_getContentLeft(),
      );

    }

    /**
     * Reads required Modules from DB
     * @return void
     */
    private function _readRequiredModules() {
      if ($this->_requiredModules) {
        return;
      }
      $sql = "SELECT MShortname ".
             "FROM ".$this->table_prefix."moduletype_backend "
            ."WHERE MActive=1 AND MRequired='1'";
      $this->_requiredModules = $this->db->GetCol($sql);

    }

    /**
     * get the userlist as csv / xls
     */
    private function _getCsv()
    {
      global $_LANG;

      //header('Content-Type: text/x-csv');
      header("Content-Type: application/vnd.ms-excel");
      header("Content-Disposition: inline; filename=\"user_export".date("Y-m-d").".xls\"");
      //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache');

      // output title row - decode all html entities in order to generate proper output
      echo parseCSVOutput($_LANG["um_id_label"])."\t".
           parseCSVOutput($_LANG["um_nick_label"])."\t".
           parseCSVOutput($_LANG["um_email_label"])."\t".
           parseCSVOutput($_LANG["um_firstname_label"])."\t".
           parseCSVOutput($_LANG["um_lastname_label"])."\t".
           parseCSVOutput($_LANG["um_language_label"])."\t".
           parseCSVOutput($_LANG["um_preferredlanguage_label"])."\t".
           parseCSVOutput($_LANG["um_lastlogin_label"])."\t".
           parseCSVOutput($_LANG["um_countlogins_label"])."\t\n";

      $sql = " SELECT UID, UNick, UEmail, ULanguage, UPreferredLanguage, ULastLogin, "
           . "        UCountLogins, UDeleted, UFirstname, ULastname "
           . " FROM {$this->table_prefix}user "
           . ' WHERE UDeleted = 0 ';
      $result = $this->db->query($sql);

      while ($row = $this->db->fetch_row($result))
      {
        $date = ContentBase::strToTime($row['ULastLogin']) ?
                date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'um'), ContentBase::strToTime($row['ULastLogin'])) : '';
        echo parseCSVOutput($row['UID'])."\t".
             parseCSVOutput($row['UNick'])."\t".
             parseCSVOutput($row['UEmail'])."\t".
             parseCSVOutput($row['UFirstname'])."\t".
             parseCSVOutput($row['ULastname'])."\t".
             parseCSVOutput($row['ULanguage'])."\t".
             parseCSVOutput($row['UPreferredLanguage'])."\t".
             parseCSVOutput($date)."\t".
             parseCSVOutput($row['UCountLogins'])."\t\n";
      }

      $this->db->free_result($result);
      exit();
    }

    /**
     * Reads portal specific settings and returns the permitted
     * sites for given scope ( = tree / siteindex )
     *
     * @param String $scope
     *        one of the tree identifiers or "index" for siteindex settings
     *
     * @return array
     *         The array containing all permitted sites, for defined scope
     *         - id    : the site id
     *         - paths : path settings for tree
     */
    private function _readPermittedSites($scope)
    {
      $post = new Input(Input::SOURCE_POST);
      $result = array();

      $sites = $post->readArrayIntToString("um_site_{$scope}");
      $paths = $post->readArrayIntToString("site_paths_{$scope}");

      foreach ($sites as $siteId => $isPermitted) {
        if ($isPermitted) {
          $result[] = array(
            'id'     => $siteId,
            'paths'  => $paths && $paths[$siteId] ?
                        str_replace(array("\r\n", "\n", "\r"), ",", $paths[$siteId]) : '',
          );
        }
      }

      return $result;
    }

    /**
     * Update submodule permissions
     *
     * @param int $userId
     * @param array $permittedSubmodules
     *        the array of permitted submodules whereas the array key is the
     *        module name always
     */
    private function _updatePermittedSubmodules($userId, $permittedSubmodules)
    {
      $sql = " DELETE FROM {$this->table_prefix}user_rights_submodules "
           . " WHERE FK_UID = $userId ";
      $this->db->query($sql);
      if ($permittedSubmodules) {
        $sql = " INSERT INTO {$this->table_prefix}user_rights_submodules "
             . " (FK_UID, URMModuleShortname, URMSubmodules) "
             . " VALUES ";
        foreach ($permittedSubmodules as $module => $submodules) {
          if (!in_array('main', $submodules)) {
            $submodules[] = 'main';
          }
          $submodules = implode(',', $submodules);
          $sql .= "($userId, '" . $this->db->escape($module) . "', '" . $this->db->escape($submodules) . "'),";
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $this->db->query($sql);
      }
    }

    /**
     * Fetch submodule names for given module shortname
     *
     * @param string $shortname
     *
     * @return array
     */
    private function _getSubmodules($shortname = null)
    {
      $module = $this->_getModuleFactory()->getByShortname($shortname);

      $submodules = array();
      if ($module !== null) {
        $classname = $module->getClass();
        if (class_exists($classname)) {
          $submodules = $classname::$subClasses;
        }
      }

      return $submodules;
    }

    /**
     * @return array
     */
    private function _getPermittedModules()
    {
      // necessary modules are allways permitted (can not be selected or deseleted in Backend)
      $permittedModules = $this->_requiredModules;
      if (isset($_POST['um_module'])) {
        foreach ($_POST['um_module'] as $module => $value) {
          $permittedModules[] = $module;
        }
      }
      return $permittedModules;
    }

    /**
     * @return array
     */
    private function _getPermittedSubmodules()
    {
      $permittedSubmodules = array();
      foreach ($this->_getPermittedModules() as $key => $shortname) {
        // ignore modules that are not displayed ( = required modules )
        if (!in_array($shortname, $this->_requiredModules)) {

          $permittedSubmodules[$shortname] = array();
          if (isset($_POST['um_module_' . $shortname . '_submodule'])) {
            $permittedSubmodules[$shortname] = array_keys($_POST['um_module_' . $shortname . '_submodule']);
          }
        }
      }

      return $permittedSubmodules;
    }
  }
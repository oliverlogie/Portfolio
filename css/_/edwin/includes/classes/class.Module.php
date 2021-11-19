<?php

/**
 * Module Class
 *
 * $LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
  abstract class Module extends ContentBase implements InterfaceCFunction
  {
    /**
     * An array containing URL parameter module name and classname for all
     * submodules of given module class
     *
     * @var array
     */
    public static $subClasses = array();

    /**
     * The member variable of each object instance holding the subclass values
     * from Model::$subClasses
     *
     * Do not modify inconsiderately!
     *
     * @var array
     */
    protected $_subClasses = array();

    protected $site_id = 0;
    protected $item_id = "";

    /**
     * Contains the action(s) of the original request (with the actions of above levels still there)
     * @var array contains strings
     */
    protected $originalAction = array();

    protected $action = "";

    /**
     * Array containing all sites (including sites not available to the user).
     *
     * Array indices are the site IDs, values are the site titles.
     *
     * @var array
     */
    protected $_allSites = array();

    /**
     * Module's shortname.
     *
     * @var string
     */
    protected $_shortname = '';

    /**
     * Module's prefix used for configuration, template
     * and language variables.
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * Module's model.
     *
     * @var AbstractModel
     */
    protected $_model = null;

    /**
     * Module type instance.
     *
     * @var \ModuleType
     */
    protected $_type = null;

    /**
     * Cached page assignments of module box.
     *
     * @var array Array with SQL resultsets.
     */
    private $_cachedPageAssignments = array();

    /**
     * @see Module::_grid()
     * @var DataGrid
     */
    private $_grid;

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Constructor                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function __construct($allSites, $site_id, Template $tpl, db $db, $table_prefix, $action = '', $item_id = '', User $user = null, Session $session = null, Navigation $navigation, $originalAction = '')
    {
      parent::__construct($db, $table_prefix, $tpl, $user, $session, $navigation);

      $this->_allSites = $allSites;
      $this->site_id = (int)$site_id;
      $this->item_id = $item_id;
      $this->action = explode(";",$action);
      // If shortname not set by module class, try to get it from page request
      $this->_shortname = (!$this->_shortname) ? $this->_getModuleShortname() : $this->_shortname;
      $this->originalAction = $originalAction ? $originalAction : $this->action;
      $this->_type = $this->_getModuleFactory()->getByShortname($this->_shortname);
      $classname = get_class($this);
      $this->_subClasses = $classname::$subClasses;
    }

    public function getShortname()
    {
      if (!$this->_type) {
        return '';
      }

      return $this->_type->getShortname();
    }

    /**
     * Checks if this module is active.
     * @see InterfaceCFunction::isActive()
     *
     * @return boolean
     */
    public function isActive()
    {
      global $_MODULES;

      if (!$this->_shortname) {
        return false;
      }
      if (in_array($this->_shortname, $_MODULES)) {
        return true;
      }
      else {
        return false;
      }
    }


    /**
     * Checks if this module is available for given user.
     * @see InterfaceCFunction::isAvailableForUser()
     *
     * @return boolean
     */
    public function isAvailableForUser(User $user, NavigationSite $site)
    {
      $available = false;

      if (!$this->_shortname) {
        return false;
      }

      $active = $this->isActive();
      $permitted = $user->AvailableModule($this->_shortname, $site->getID());

      if ($active && $permitted) {
        $available = true;
      }

      return $available;
    }

    /**
     * Checks if this module is available for given page.
     * Overwrite this function if a configuration for excluded
     * content items is available.
     *
     * @see InterfaceCFunction::isAvailableOnPage()
     *
     * @return boolean
     */
    public function isAvailableOnPage(NavigationPage $page)
    {
      $available = false;

      $active = $this->isActive();
      $permitted = true; // Specify in module class

      if ($active && $permitted) {
        $available = true;
      }

      return $available;
    }

    public function isAvailableForUserOnPage(User $user, NavigationPage $page)
    {
      return ($this->isAvailableForUser($user, $page->getSite()) &&
              $this->isAvailableOnPage($page));
    }

    /**
     * Renders the content of the module
     *
     * Supported and required keys of the array returned:
     * array(
     *    "content"             => required: the module main content,
     *    "content_left"        => the left content option / action boxes [optional], empty if not set,
     *    "content_contenttype" => the contenttype for including special js files [optional], classname if not set,
     *    "content_output_mode" => special output mode [optional], 1 if not set ( = standard ),
     *    "content_output_tpl"  => special template name to use [optional], the name returned results in a template path edwin/templates/main_<content_output_tpl>.tpl
     * )
     *
     * @return array
     */
    public function show_innercontent()
    {
      return '';
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public final function show_content()
    {
      global $_MODULES;

      $class = "";

      $this->_createPageAssignment();
      $this->_deletePageAssignment();

      // subclasses and action defined -> load need subclass
      if (isset($this->_subClasses[$this->action[0]]) && $this->_subClasses[$this->action[0]] != 'main')
      {
        // invalid module subclass
        if (!class_exists($this->_subClasses[$this->action[0]], true)) {
          $this->redirect_page('modulesubclass_notfound');
        }
        // submodule not available
        else if (!$this->_user->AvailableSubmodule($_MODULES[get_class($this)], $this->action[0])) {
          $this->redirect_page('permission_denied');
        }

        $class = $this->_subClasses[$this->action[0]];
        $action = $this->action;
        unset($action[0]);
        $action = implode(";",$action);

        /* @var $c_module Module */
        $c_module = new $class($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix, $action, $this->item_id, $this->_user, $this->session, $this->_navigation, $this->originalAction);

        $message = $this->_popSessionMessage();
        if ($message) {
          $c_module->setMessage($message);
        }
        $sub_output = $c_module->show_innercontent();
        if (!isset($sub_output['content_contenttype'])) {
          $sub_output['content_contenttype'] = get_class($c_module);
        }
      }
      // get local content
      else
      {
        if ($this->action[0] == "main") {
          array_shift($this->action);
        }

        $message = $this->_popSessionMessage();
        if ($message) {
          $this->setMessage($message);
        }
        $sub_output = $this->show_innercontent();
      }

      return $this->render_framework($sub_output);
    }

    public final function getSendResponse($request)
    {
      global $_MODULES;

      // subclasses and action defined -> load needed subclass
      if (isset($this->_subClasses[$this->action[0]]) && $this->_subClasses[$this->action[0]] != 'main')
      {
        if (!class_exists($this->_subClasses[$this->action[0]], true)) {
          ed_http_code(\Core\Http\ResponseCode::NOT_FOUND, true);
        }
        else if (!$this->_user->AvailableSubmodule($_MODULES[get_class($this)], $this->action[0])) {
          ed_http_code(\Core\Http\ResponseCode::FORBIDDEN, true);
        }

        $class = $this->_subClasses[$this->action[0]];
        $action = $this->action;
        $action = implode(";",$action);

        /* @var $c_module Module */
        $c_module = new $class($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix, $action, $this->item_id, $this->_user, $this->session, $this->_navigation, $this->originalAction);

        if (is_callable(array($c_module, "sendResponse"))) {
          return $c_module->sendResponse($request);
        }
      }

      return $this->sendResponse($request);

    }

    /**
     * Gets module's prefix if defined.
     */
    public function getPrefix()
    {
      return $this->_prefix;
    }

    /**
     * Gets the item id, which is usually set
     * if an item/element of a module gets edited.
     */
    public function getItemId()
    {
      return $this->item_id;
    }

    /**
     * @see Module::_initGrid()
     * @return DataGrid
     */
    protected final function _grid()
    {
      if ($this->_grid === null) {
        $this->_grid = $this->_initGrid();
      }
      return $this->_grid;
    }

    /**
     * Initializes the datagrid for this module
     *
     * Implement when using Module::_grid() within extending class
     *
     * @see Module::_grid()
     *
     * @throws Exception
     *
     * @return DataGrid
     */
    protected function _initGrid()
    {
      throw new Exception("Module::_initGrid() not implemented. Implement and use Module::_grid().");
    }

    /**
     * Adds a content item log entry.
     * At the moment only ModuleSiteindexCompendium uses this,
     * because it may shows an infobox.
     */
    protected function _addContentItemLogEntry()
    {
      // Retrieve the root page (siteindex).
      $currentSite = $this->_navigation->getCurrentSite();
      $currentPage = $currentSite->getRootPage(Navigation::TREE_MAIN);
      $now = date("Y-m-d H:i:s");

      Container::make('ContentItemLogService')->logUpdated(array(
          'FK_CIID'      => $currentPage->getID(),
          'CIIdentifier' => '',
          'FK_UID'       => $this->_user->getID(),
      ));

      $sql = " UPDATE {$this->table_prefix}contentitem "
           . " SET CChangeDateTime='".$now."' "
            ." WHERE FK_CIID IS NULL "
            ." AND FK_CTID IS NULL "
            ." AND FK_SID = {$this->site_id} "
            ." AND CTree = 'main' ";
      $result = $this->db->query($sql);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Render Framework (Navigation etc)                                                     //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function render_framework($sub_output){
      global $_LANG;

      $content_top = $this->_getContentTop();

      // content_output_mode:
      // 1...normal, 2...only content data, 10...alternative main template
      $content_output_mode = 1;
      if (isset($sub_output["content_output_mode"]) && $sub_output["content_output_mode"]) {
        $content_output_mode = $sub_output["content_output_mode"];
      }

      // Retrieve the alternative main template
      $contentOutputTemplate = '';
      if (isset($sub_output['content_output_tpl']) && $sub_output['content_output_tpl']) {
        $contentOutputTemplate = $sub_output['content_output_tpl'];
      }

      if (!isset($sub_output['content_contenttype'])) {
        $sub_output['content_contenttype'] = get_class($this);
      }

      if (!isset($sub_output['content_left'])) {
        $sub_output['content_left'] = '';
      }

      return array(
        'content'             => $sub_output['content'],
        'content_left'        => $sub_output['content_left'],
        'content_top'         => $content_top,
        'content_contenttype' => $sub_output['content_contenttype'],
        'content_output_mode' => $content_output_mode,
        'content_output_tpl'  => $contentOutputTemplate,
      );
    }

    /**
     * Returns the standard file name component(s).
     *
     * In the class ContentItem the standard components are site ID and item ID.
     *
     * @return array
     *        The standard file name components.
     */
    protected function _storeImageGetDefaultComponents()
    {
      return array($this->site_id, $this->item_id);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Redirect to an Error Message Page                                                     //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function redirect_page ($message, $url = null)
    {
      global $_LANG, $_LANG2;

      if (!$url)
      {
        if (isset($_SERVER['HTTP_REFERER'])) {
          $url = $_SERVER['HTTP_REFERER'];
        }
        // If the user has not got permission to any sites (only modules)
        // make a redirect to the first module available instead of the
        // cmsindex.
        else if (!$this->_user->getPermittedSites())
        {
          $moduleShortname = $this->_user->getOptionalModule();
          $url = 'index.php?action=mod_' . $moduleShortname . '&site=1';
        }
        else {
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
     * @param string | array $module
     *        the module shortname for link generation or array with module
     *        shortname and submodule identifier
     * @param string $prefix
     *        the prefix for template variable names ( = array index )
     * @param int $imageNumber
     *        image number
     * @return array
     *         the array containing template variables, required for parsing
     *         the image deletion link for modules
     */
    protected function get_delete_image($module, $prefix, $imageNumber)
    {
      global $_LANG;

      $submodule = 'main';
      if (is_array($module)) {
        $submodule = $module[1];
        $module = $module[0];
      }

      $delete_link = "index.php?action=mod_".$module."&amp;action2=$submodule;edit&amp;site=".$this->site_id."&amp;page=".$this->item_id."&amp;dimg=".$imageNumber;
      $delete_data = array( $prefix.'_delete_image_label' => (isset($_LANG[$prefix."_delete_image_label"]) ? $_LANG[$prefix."_delete_image_label"] : $_LANG["global_delete_image_label"]),
                            $prefix.'_delete_image_question_label' => (isset($_LANG[$prefix."_delete_image_question_label"]) ? $_LANG[$prefix."_delete_image_question_label"] : $_LANG["global_delete_image_question_label"]),
                            $prefix.'_delete_image'.$imageNumber.'_link' => $delete_link );

      return $delete_data;
    }

    /**
     * Deletes module image and redirects to modules edit action page
     * @param string | array $module
     *        the module shortname for redirection or array with module
     *        shortname and submodule identifier
     * @param string $table
     *        the table name without mc_module_
     * @param string $primaryColumn
     *        the primary key column name
     * @param string $imageColumn
     *        the image column name
     * @param int $number
     *        image number
     */
    protected function delete_content_image($module, $table, $primaryColumn,
                                            $imageColumn, $number)
    {
      $image = $this->db->GetOne("SELECT $imageColumn$number FROM {$this->table_prefix}module_$table WHERE $primaryColumn = $this->item_id");
      if ($image) {
        self::_deleteImageFiles($image);
        $this->db->query("UPDATE {$this->table_prefix}module_$table SET $imageColumn$number = '' WHERE $primaryColumn = $this->item_id");
      }

      $submodule = 'main';
      if (is_array($module)) {
        $submodule = $module[1];
        $module = $module[0];
      }
      header("Location: index.php?action=mod_$module&action2=$submodule;edit&site=$this->site_id&page=$this->item_id");
      exit();
    }

    /**
     * Gets the language variable for given name using the modules prefix.
     * Uses the "m_" prefix as a fallback if available.
     *
     * @param string $name
     * @return string | null
     */
    protected function _langVar($name)
    {
      global $_LANG;

      return isset($_LANG["{$this->_prefix}_{$name}"]) ? $_LANG["{$this->_prefix}_{$name}"] :
                ( isset($_LANG["m_{$name}"]) ? $_LANG["m_{$name}"] : null );
    }

    /**
     * Parses the module attachment campaign area.
     *
     * @param CampaignAttached $cgAttached
     *        An instance of CampaignAttached.
     * @return string
     *         Parsed template.
     */
    protected function _parseModuleCampaignFormAttachment($cgAttached)
    {
      $cgType = new CampaignType($this->db, $this->table_prefix);
      $where = " FK_SID = {$this->site_id} ORDER BY CGTPosition ASC ";
      $cgTypeList = $cgType->readAllCampaignTypes($where);

      $cg = new Campaign($this->db, $this->table_prefix);
      $where = " FK_SID = {$this->site_id} AND CGStatus = 1 ORDER BY CGPosition ASC ";
      $cgList = $cg->readAllCampaigns($where);

      $optGroups = '';
      foreach ($cgTypeList as $type)
      {
        $options = '';
        foreach ($cgList as $key => $campaign)
        {
          if ($campaign->typeId == $type->id)
          {
            $selected = '';
            if ($cgAttached->parentId == $campaign->id) {
              $selected = 'selected="selected"';
            }
            $options .= '<option value="'.$campaign->id.'" '.$selected.' >'.parseOutput($campaign->name).'</option>';
            // Remove campaign of list, we do not need it anymore.
            unset($cgList[$key]);
          }
        }
        if ($options) {
          $optGroups .= '<optgroup label="'.parseOutput($type->name).'">'.$options.'</optgroup>';
        }
      }

      $this->tpl->load_tpl('module_campaign_form_attachment', 'module_campaign_form_attachment.tpl');
      return $this->tpl->parsereturn('module_campaign_form_attachment', array(
        'm_cg_data_origin' => $cgAttached->dataOrigin,
        'm_cg_recipient'   => $cgAttached->recipient,
        'm_cg_forms'       => $optGroups,
      ));
    }

    /**
     * Parses form fields of module's model.
     *
     * @param array $ignore (optional)
     *        Fields to ignore.
     * @return string
     *         Parsed template.
     */
    protected function _parseModuleFormFields($ignore = array('id'))
    {
      $fields = $this->_generateFormFieldLoopArray($ignore);
      $this->tpl->load_tpl('module_form_fields', 'module_form_fields.tpl');
      $this->tpl->parse_loop('module_form_fields', $fields, 'fields');
      $this->_parseModuleFormFieldMsg();
      $this->_parseModuleFormFieldInfo();

      return $this->tpl->parsereturn('module_form_fields', array());
    }

    /**
     * Parses form field messages of module's model.
     *
     * @param string $tplId
     *        The id of the template to parse the message ifs into.
     * @throws Exception
     */
    protected function _parseModuleFormFieldMsg($tplId = 'module_form_fields')
    {
      if (!$this->_model) {
        throw new Exception("There is no model to parse module's form field messages!");
      }

      $modelFields = $this->_model->getFields();
      foreach ($modelFields as $name => $field)
      {
        /* @var $field Field */
        $tplName = $field->getTplName($this->_prefix);
        $msg = $field->getFirstMsg();
        $this->tpl->parse_if($tplId, 'field_msg_'.$tplName, ($field->hasValidationErrors()), array(
          'm_ff_field_msg_text' => ($msg) ? $msg->getText() : '',
          'm_ff_field_msg_type' => ($msg) ? $msg->getType() : '',
        ));
      }
    }

      /**
     * Parses form field info of module's model.
     *
     * @param string $tplId
     *        The id of the template to parse the info into.
     * @throws Exception
     */
    protected function _parseModuleFormFieldInfo($tplId = 'module_form_fields')
    {
      global $_LANG;

      if (!$this->_model) {
        throw new Exception("There is no model to parse module's form field info into!");
      }

      $modelFields = $this->_model->getFields();
      foreach ($modelFields as $name => $field) {
        /* @var $field Field */
        $tplName = $field->getTplName($this->_prefix);
        $info = isset($_LANG[$tplName . '_info']) ? $_LANG[$tplName . '_info'] : '';
        $this->tpl->parse_if($tplId, 'field_info_'.$tplName, isset($_LANG[$tplName . '_info']), array(
          'm_ff_field_info' => $info,
        ));
      }
    }

    /**
     * Gets an array of module's model fields.
     *
     * @param array $ignore (optional)
     *        Fields to ignore
     * @param boolean $moduleTplPrefix (optional)
     *        If set to true, module's prefix will be used for template variables.
     * @throws Exception
     * @return array
     *         An array, ready to parse into a template.
     */
    protected function _generateFormFieldLoopArray($ignore = array('id'), $moduleTplPrefix = false)
    {
      global $_LANG;

      if (!$this->_model) {
        throw new Exception("There is no model to parse module's form fields!");
      }

      $prefix = ($moduleTplPrefix) ? $this->_prefix : 'm_ff';
      $fields = array();
      $modelFields = $this->_model->getFields();
      foreach ($modelFields as $name => $field)
      {
        /* @var $field Field */
        if (in_array($name, $ignore) || $field->isHidden()) {
          continue;
        }
        $fields[] = array(
          $prefix.'_field'         => $field->getHTML($this->_configHelper, $this->_prefix),
          $prefix.'_field_failure' => ($field->hasValidationErrors()) ? 'has-error' : '',
          $prefix.'_field_label'   => $field->getLabel($this->_prefix),
          $prefix.'_field_name'    => $field->getTplName($this->_prefix),
          $prefix.'_module_prefix' => $this->_prefix,
        );
      }

      return $fields;
    }

    /**
     * Parses the page assignment area.
     *
     * @param array $pageParameter
     *        Additional page GET parameter for links (optional).
     * @return string
     *         Parsed template.
     */
    protected function _parseModulePageAssignment($pageParameter = array())
    {
      // Page assignment is only in edit mode available
      if (!$this->item_id) {
        return '';
      }
      $assItems = array();
      $assBeneathItems = array();
      $autoCompleteUrl = 'index.php?action=mod_response_'.$this->_shortname.'&site=' . $this->site_id
                       . '&request=ContentItemAutoComplete';

      $tmpDelLinkArgs = array_merge(array(
        'action'  => "mod_{$this->_shortname}",
        'action2' => 'edit',
        'site'    => "$this->site_id",
        'page'    => "$this->item_id",
      ), $pageParameter);

      $delLinkArgs = array();
      foreach ($tmpDelLinkArgs as $key => $value) {
        $delLinkArgs[] = "$key=$value";
      }
      $delLinkArgs = implode('&amp;', $delLinkArgs);

      /**
       * Create two arrays each containing assigned items. The $assBeneathItems
       * array contains logical levels, where the sidebox item is displayed for
       * all child pages, while the $assItems array contains only items, where
       * the box is displayed directly.
       */
      foreach ($this->_readPageAssignments() as $row)
      {
        $beneath = (int)$row[$this->_dbColumnPrefix.'ABeneath'];
        $page = $this->_navigation->getPageByID($row['CIID']);
        $delLink = "index.php?{$delLinkArgs}&amp;deleteShowOnPageID={$page->getID()}";
        /* @var $page NavigationPage */

        $item = array(
          'm_pa_contentitem_title' => parseOutput($row['CTitle']),
          'm_pa_delete_link'       => $delLink,
          'm_pa_frontend_link'     => $page->getUrl(),
          'm_pa_contentitem_link'  => 'index.php?action=content&amp;site='.$page->getSite()->getID().'&amp;page='.$page->getID(),
        );

        // logical levels, with the sidebox displayed beneath
        if ($beneath) {
          $assBeneathItems[] = $item;
        }
        // all items, with the box displayed directly
        else {
          $assItems[] = $item;
        }
      }
      $this->tpl->load_tpl('module_page_assignment', 'module_page_assignment.tpl');
      $this->tpl->parse_if('module_page_assignment', 'show_assignments', $this->item_id);
      $this->tpl->parse_if('module_page_assignment', 'assignments', count($this->_readPageAssignments()));
      $this->tpl->parse_if('module_page_assignment', 'assignments_items', count($assItems));
      $this->tpl->parse_loop('module_page_assignment', $assItems, 'assignments_items');
      $this->tpl->parse_if('module_page_assignment', 'assignments_beneath_items', count($assBeneathItems));
      $this->tpl->parse_loop('module_page_assignment', $assBeneathItems, 'assignments_beneath_items');
      $this->tpl->parse_if('module_page_assignment', 'no_assignments', $this->item_id && !count($this->_readPageAssignments()));

      return $this->tpl->parsereturn('module_page_assignment', array(
        'm_pa_autocomplete_contentitem_url' => $autoCompleteUrl . '&scope=local&excludeContentTypes=' . ContentType::TYPE_LOGICAL_WITH_NAV,
        'm_pa_autocomplete_contentitem_beneath_url' => $autoCompleteUrl . '&scope=local&includeContentTypes=' . ContentType::TYPE_LOGICAL_WITH_NAV,
        'm_pa_item_id' => $this->item_id,
      ));
    }

    /**
     * Parse the module site selection template
     *
     * Modules probably use this method in their list_content method if a site
     * selection should be available
     *
     * @param string $moduleShortname - module shortname as defined in the database,
     *       used for building action GET parameter
     * @param string $siteLabel - a description for listed elements,
     *    if possible the string is formatted with sprintf (site name inserted)
     * @param string $action2 - action2 GET parameter
     *
     * @return string - the parsed template content
     */
    protected function _parseModuleSiteSelection($moduleShortname, $siteLabel, $action2 = null) {
      global $_LANG;

      // create site dropdown
      $siteSelect = '<select name="site" class="form-control" onchange="if (this.options[this.selectedIndex].value > 0) { document.forms.change_site.submit(); }">';
      $count = 0;

      foreach ($this->_allSites as $siteId => $siteName)
      {
        $site = $this->_navigation->getSiteByID($siteId);
        $siteID = $site->getID();
        if ($this->_user->AvailableSite($siteID))
        {
          /**
           * Determine the language site for the currently added site. There are
           * two possibilities:
           *  (1) The site has a language parent
           *  (2) The site itself is language site
           */
          $langSite = $site->getLanguageParent() ? $site->getLanguageParent() : $site;
          $langLabel = null;
          // There is defined a language label for the language site.
          if (isset($_LANG['global_sites_backend_language_site_general_label'][$langSite->getID()])) {
            $langLabel = $_LANG['global_sites_backend_language_site_general_label'][$langSite->getID()] . ' - ';
          }

          $title = $langLabel . parseOutput($siteName);

          $siteSelect .= '<option value="' . $siteID . '"';
          if ($this->site_id == $siteID) {
            $siteLabel = sprintf($siteLabel, $title);
            $siteSelect .= ' selected="selected"';
          }
          $siteSelect .= '>' . $title . '</option>';
          $count ++;
        }
      }
      if ($count < 2) {
        // do not return site navigation, if there is only one site for current user available
        return;
      }
      $siteSelect .= '</select>';

      $action = "index.php?action=mod_{$moduleShortname}";
      if ($action2) {
        $action .= "&amp;action2={$action2}";
      }

      $messageWarning = null;
      $moduleTypeFactory = new ModuleTypeFrontendFactory($this->db, $this->table_prefix);
      $moduleAliasShortnames = ConfigHelper::get('m_module_alias_shortnames', '', $moduleShortname) ?: array($moduleShortname);
      foreach ($moduleAliasShortnames as $moduleAliasShortname) {
        $moduleType = $moduleTypeFactory->getByShortname($moduleAliasShortname);
        // Notice: We do not consider navigation tree or specific frontend visibility configurations!
        if ($moduleType && !$moduleType->isAvailableWithNavigation($this->_navigation)) {
          $messageWarning = Message::createFailure($_LANG['global_message_frontend_module_not_available']);
        }
      }
      $this->tpl->load_tpl('module_site_selection', 'module_site_selection.tpl');
      $this->tpl->parse_if('module_site_selection', 'message_warning', $messageWarning,
        $messageWarning ? $messageWarning->getTemplateArray('module_site_select') : array());
      return $this->tpl->parsereturn('module_site_selection', array(
        "module_show_change_site_label" => $_LANG['module_show_change_site_label'],
        "module_action" => $action,
        "module_site_select_label" => $_LANG['module_site_select_label'],
        "module_site_select" => $siteSelect,
        "module_site_label" => $siteLabel,
      ));
    }

    /**
     * Parse the module top content (for modules with subclasses (submodules))
     * and return the parsed template.
     *
     * @return string
     *         The parsed module_top.tpl (or a module specific template if found)
     */
    protected function _getContentTop() {
      global $_LANG, $_MODULES;

      $content = '';
      $subClasses = array();
      $subclassesAvailable = 0;
      if (isset($this->_subClasses) && $this->_subClasses) {
        $subclassesAvailable = 1;
      }

      // top only displayed when subclasses defined
      if ($subclassesAvailable)
      {
        // main class
        // here we check the first element of $this->originalAction because in $this->action
        // the first element "main" could already be cut away (i.e. if the action was "main;new")
        $subClasses[] = array (
          'item_class' => ("main" == $this->originalAction[0] || !$this->originalAction[0] ? "active" : "inactive"),
          'item_link'  => "index.php?action=mod_".$_MODULES[get_class($this)]."&amp;action2=main&amp;site=".$this->site_id,
          'item_label' => $_LANG["modtop_".get_class($this)] );

        foreach ($this->_subClasses as $action => $value)
        {
          // Do not add the submodule in case it is not available for the
          // current user.
          if (!$this->_user->AvailableSubmodule($_MODULES[get_class($this)], $action)) {
            continue;
          }

          $subClasses[] = array (
            'item_class' => ($this->action && $action == $this->action[0] ? "active" : "inactive"),
            'item_link'  => "index.php?action=mod_".$_MODULES[get_class($this)]."&amp;action2=".$action."&amp;site=".$this->site_id,
            'item_label' => $_LANG["modtop_".$value] );
        }

        // Check if there is a special template for the module top navigation.
        if (is_file('./templates/modules/'.get_class($this).'_module_top.tpl')) {
          $this->tpl->load_tpl('content_top', 'modules/'.get_class($this).'_module_top.tpl');
        }
        else {
          $this->tpl->load_tpl('content_top', 'module_top.tpl');
        }

        $this->tpl->parse_loop('content_top', $subClasses, 'sub_classes');
        $content = $this->tpl->parsereturn('content_top', array(
          'module_class'     => 'module_top_' . $_MODULES[get_class($this)],
          'module_shortname' => $_MODULES[get_class($this)],
        ));
      }

      return $content;
    }

    /**
     * Returns the action box content and automatically generates default buttons
     * if not provided
     *
     * @param $buttons string [optional]
     * @return string
     */
    protected function _getContentActionBoxes($buttons = null)
    {
      global $_LANG;

      if ($buttons === null) {
        $buttons = $this->_getContentActionBoxButtons();
      }

      $tplName = 'module_' . $this->_prefix . '_action_boxes';
      $this->tpl->load_tpl($tplName, 'module_action_boxes.tpl');
      $moduleActionBoxes = $this->tpl->parsereturn($tplName, array(
          'module_actions_buttons' => $buttons,
          'module_actions_label'   => $this->_langVar('actions_label'),
      ));

      return $moduleActionBoxes;
    }

    /**
     * Returns the action box buttons' HTML
     *
     * @var string
     *
     * @return string
     */
    protected function _getContentActionBoxButtons()
    {
      $tplName = 'module_' . $this->_prefix . '_action_boxes_buttons';
      $this->tpl->load_tpl($tplName, $this->_getContentActionBoxButtonsTemplate());
      return $this->tpl->parsereturn($tplName, array(
        'm_prefix'                    => $this->_prefix,
        'm_backlink_url'              => $this->_getBackLinkUrl(),
        'm_submit_label'              => $this->_langVar('button_submit_label'),
        'm_submit_and_redirect_label' => $this->_langVar('button_submit_and_redirect_label'),
        'm_cancel_label'              => $this->_langVar('button_cancel_label'),
      ));
    }

    /**
     * Returns the filename of the actionboxbuttons template
     *
     * @return string
     */
    protected function _getContentActionBoxButtonsTemplate()
    {
      return 'module_action_boxes_buttons_default.tpl';
    }

    /**
     * Parses left navigation template.
     *
     * @param boolean $back
     *        If true backlink will be available.
     *
     * @return string
     *         The parsed template.
     */
    protected function _getContentLeft($back = false)
    {
      global $_LANG;

      $backlink = sprintf($_LANG['m_nv2_backlink'], $this->_parseUrl(),
          $_LANG['module_backlink_list_title'],
          $_LANG['module_backlink_list_title']);
      $links = $this->_getPreparedContentLeftLinks();
      $this->tpl->load_tpl("content_{$this->_prefix}_left", 'module_left_sub.tpl');
      $this->tpl->parse_if("content_{$this->_prefix}_left", 'links', count($links));
      $this->tpl->parse_loop("content_{$this->_prefix}_left", $links, 'links');
      return $this->tpl->parsereturn("content_{$this->_prefix}_left", array(
          'm_backlink'            => ($back) ? $backlink : '',
      ));
    }

    /**
     * Returns an array of links for left content
     *
     * Override in subclasses to modify behaviour:
     * a. return an empty array for backlink only
     * b. do not override to retrieve the "new" link
     * c. override and merge or return different links
     *
     * @return array
     *         the "new" link by default, override to modify
     */
    protected function _getContentLeftLinks()
    {
      return array(
          array($this->_parseUrl('new'), $this->_langVar('moduleleft_newitem_label'), 'newitem btn btn-success', 'data-slot-source="ed_actionbar_top"')
      );
    }

    /**
     * Stores a message within the session that is automatically read and se
     * on next module request.
     *
     * @param Message $message
     */
    protected function _setSessionMessage(Message $message)
    {
      $this->session->save('message', $message);
    }

    /**
     * Gets the information text how the box (sidebox, employee, multimedia box) will be displayed on
     * the frontend.
     *
     * @param boolean $noRandom
     *        True if box should be randomly displayed, otherwise false.
     * @param int $assignments
     *        Number of assignments.
     * @return string
     *         Information language text or empty string.
     */
    protected function _getDisplayOnInfoText($random, $assignments)
    {
      global $_LANG;

      if ($random && !$assignments) {
        return $_LANG['global_info_box_random_only'];
      }
      else if (!$random && $assignments) {
        return $_LANG['global_info_box_assignment_only'];
      }
      else if ($random && $assignments) {
        return $_LANG['global_info_box_random_and_assignment'];
      }
      else if (!$random && !$assignments) {
        return $_LANG['global_info_box_not_displayed'];
      }

      return '';
    }

    /**
     * Creates an assignment to a content item.
     */
    protected function _createPageAssignment()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);
      if (!$post->exists('m_pa_process') || !$this->item_id) {
        return;
      }

      $assignmentType = $post->readString('m_pa_newassignment_type', Input::FILTER_PLAIN, 'page');
      $beneath = 0;
      switch ($assignmentType) {
        case 'root':
          $currentSite = $this->_navigation->getCurrentSite();
          $rootPage = $currentSite->getRootPage(Navigation::TREE_MAIN);
          $assignmentPageID = $rootPage->getID();
          break;
        case 'beneath':
          list($assignmentPage, $assignmentPageID) = $post->readContentItemLink('m_pa_newassignment_beneath_contentitem');
          $beneath = 1;
          break;
        case 'page':
        default:
          // An unknown type is simply handled like the type 'page'.
          list($assignmentPage, $assignmentPageID) = $post->readContentItemLink('m_pa_newassignment_contentitem');
          break;
      }

      if (!$assignmentPageID) {
        $this->setMessage(Message::createFailure($_LANG['global_pa_message_createassignment_no_page']));
        return;
      }

      if (!isset($this->_dbColumnPrefix) || !$this->_dbColumnPrefix) {
        throw new Exception('No column prefix defined in module!');
      }
      if (!isset($this->_shortname) || !$this->_shortname) {
        throw new Exception("Module's shortname is not available!");
      }

      $sql = " SELECT FK_{$this->_dbColumnPrefix}ID "
           . " FROM {$this->table_prefix}module_{$this->_shortname}_assignment "
           . " WHERE FK_{$this->_dbColumnPrefix}ID = " . $this->item_id
           . ' AND FK_CIID = ' . $assignmentPageID;
      $exists = $this->db->GetOne($sql);

      // The box has been assigned to the contentitem before.
      if ($exists)
      {
        $this->setMessage(Message::createFailure($_LANG['global_pa_message_assignment_exists']));
        return;
      }

      $sql = " INSERT INTO {$this->table_prefix}module_{$this->_shortname}_assignment "
           . " (FK_{$this->_dbColumnPrefix}ID, FK_CIID, {$this->_dbColumnPrefix}ABeneath) "
           . " VALUES ($this->item_id, $assignmentPageID, $beneath) ";
      $result = $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG['global_pa_message_createassignment_success']));
    }

    /**
     * Deletes an assignment to a content item.
     */
    protected function _deletePageAssignment()
    {
      global $_LANG;

      $get = new Input(Input::SOURCE_GET);
      $ID = $get->readInt('deleteShowOnPageID');
      if (!$ID) {
        return;
      }
      if (!isset($this->_dbColumnPrefix) || !$this->_dbColumnPrefix) {
        throw new Exception('No column prefix defined in module!');
      }
      if (!isset($this->_shortname) || !$this->_shortname) {
        throw new Exception("Module's shortname is not available!");
      }
      $sql = " DELETE FROM {$this->table_prefix}module_{$this->_shortname}_assignment "
           . " WHERE FK_{$this->_dbColumnPrefix}ID = $this->item_id "
           . " AND FK_CIID = $ID ";
      $result = $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG['global_pa_message_deleteassignment_success']));
    }

    /**
     * Reads page assignments of current box.
     *
     * @throws Exception
     *
     * @return array|NULL
     */
    protected function _readPageAssignments()
    {
      if (!$this->item_id) {
        return array();
      }

      if (isset($this->_cachedPageAssignments[$this->item_id])) {
        return $this->_cachedPageAssignments[$this->item_id];
      }

      if (!isset($this->_dbColumnPrefix) || !$this->_dbColumnPrefix) {
        throw new Exception('No column prefix defined in module!');
      }
      if (!isset($this->_shortname) || !$this->_shortname) {
        throw new Exception("Module's shortname is not available!");
      }

      // Read all assignments from the database
      $sql = " SELECT CIID, CTitle, {$this->_dbColumnPrefix}ABeneath "
           . " FROM {$this->table_prefix}module_{$this->_shortname}_assignment sba "
           . " JOIN {$this->table_prefix}contentitem ci "
           . '      ON sba.FK_CIID = ci.CIID '
           . " WHERE FK_{$this->_dbColumnPrefix}ID = $this->item_id "
           . ' ORDER BY CIIdentifier ';
      $this->_cachedPageAssignments[$this->item_id] = $this->db->GetAssoc($sql);

      return $this->_cachedPageAssignments[$this->item_id];
    }

    /**
     * Redirect to given URL and set loaded module's message to given message
     * after redirect.
     *
     * @param string $url
     * @param Message $message
     */
    protected function _redirect($url, Message $message = null)
    {
      if ($message instanceof Message) {
        $this->_setSessionMessage($message);
      }

      header("Location: " . $url);
      exit;
    }

    /**
     * Parses the url for this module
     * @param string $action [optional]
     * @param array $params
     *        URL parameters to add as key value pairs
     * @return string
     */
    protected function _parseUrl($action = '', $params = array())
    {
      $action = $action && strlen($action) ? ';' . $action : '';

      $url = $this->_getModuleUrl() . $action;
      foreach ($params as $key => $val) {
        $url .= "&{$key}={$val}";
      }

      return $url;
    }

    /**
     * Gets the response url of this module or submodule.
     *
     * @param string $request
     * @param array $params
     * @return string
     */
    protected function _parseResponseUrl($request = '', $params = array())
    {
      $params = array_merge(array(
        'action' => 'mod_response_'.$this->_shortname,
        'action2' => isset($this->originalAction[0]) && $this->originalAction[0] ? implode(';',$this->originalAction) : 'main',
        'site' => $this->site_id,
        'request' => $request,
      ), $params);

      $url = 'index.php?' . http_build_query($params);
      return $url;
    }

    /**
     * Returns an array of module URL parts ( key => value ) required when
     * creating the module URL using Module::_parseUrl(), whereas the keys specify the
     * URL param names.
     *
     * Always merge with parent::_getModuleUrlParts() when overriding.
     *
     * @return array
     */
    protected function _getModuleUrlParts()
    {
      return array();
    }

    /**
     * Returns the module backlink URL.
     *
     * Essentially the base URL of module as returned when calling
     * Module::_parseUrl() without any parameters.
     *
     * @return string
     */
    protected function _getBackLinkUrl()
    {
      return $this->_parseUrl();
    }

    /**
     * @param string $type
     * @return boolean
     */
    protected function _redirectAfterProcessingRequested($type)
    {
      if (isset($_POST['redirect']) && $_POST['redirect'] == $type) {
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * @return ModuleTypeBackendFactory
     */
    protected function _getModuleFactory()
    {
      return Container::make('ModuleTypeBackendFactory');
    }

    /**
     * Returns the file URL for backend users
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function _fileUrlForBackendUser($filePath)
    {
      return $this->_fileUrlForBackendUserOnSite($filePath, $this->site_id);
    }

    /**
     * @return Core\Services\ExtendedData\ExtendedDataService
     */
    protected function _extendedDataService()
    {
      return Container::make('Core\Services\ExtendedData\ExtendedDataService');
    }

    /**
     * Gets module's shortname.
     *
     * @return string
     */
    private function _getModuleShortname()
    {
      $shortname = '';
      // Get all possible request sources.
      // Action could be set as GET parameter and/or POST parameter.
      $request = new Input(Input::SOURCE_REQUEST);
      $action = $request->readString('action');
      if (mb_strpos($action, 'mod_response_') === 0) {
        $shortname = mb_substr($action, mb_strlen('mod_response_'));
      }
      else if (mb_strpos($action, 'mod_') === 0) {
        $shortname = mb_substr($action, mb_strlen('mod_'));
      }

      return $shortname;
    }

    /**
     * Returns the module base URL, whereas the action2 parameter is at last
     * position, so one can add the second action part i.e.
     * // index.php?site=1&action=mod_survey&action2=main
     * $this->_getModuleUrl() . ';edit';  // add the edit action to the URL
     *
     * @return string
     */
    private function _getModuleUrl()
    {
      $submodule = isset($this->originalAction[0]) && $this->originalAction[0] ?
                   $this->originalAction[0] : 'main';

      // This parameters are not added within the _getModuleUrlParts, as action2
      // is expected to be the last parameter
      $params = array_merge($this->_getModuleUrlParts(), array(
          'site' => $this->site_id,
          'action' => 'mod_' . $this->_type->getShortname(),
          'action2' => $submodule,
      ));

      return 'index.php?' . http_build_query($params);
    }

    /**
     * @param Message $default [optional]
     * @return Message | null
     */
    private function _popSessionMessage(Message $default = null)
    {
      $message = $this->session->read('message');
      if ($message instanceof Message) {
        $this->session->reset('message');
      }
      else {
        $message = $default;
      }

      return $message;
    }

    /**
     * Returns the content left links prepared for parsing into a template loop
     *
     * @see Module::_getContentLeftLinks()
     * @return array
     */
    private function _getPreparedContentLeftLinks()
    {
      $links = array();
      foreach ($this->_getContentLeftLinks() as $value) {
        $links[] = array(
            'm_link_url'   => $value[0],
            'm_link_label' => $value[1],
            'm_link_class' => isset($value[2]) ? $value[2] : '',
            'm_link_attributes' => isset($value[3]) ? $value[3] : '',
        );
      }

      return $links;
    }
  }
<?php

use Core\Services\ExtendedData\Interfaces\InterfaceExtendable;

/**
 * Siteindex module.
 *
 * $LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleSiteindex extends Module implements InterfaceExtendable
{
  public static $subClasses = array('mobile' => 'ModuleSiteindexCompendiumMobile');

  /**
   * @var bool
   */
  protected $_validationError = false;

  /**
   * The module URL action parameter value
   *
   * @var string
   */
  protected $_moduleAction = 'main';

  /**
   * @var string
   *      the actionbox template to use, override to change for extending classes
   */
  protected $_actionBoxTemplate = 'modules/ModuleSiteindexCompendium_action_box.tpl';

  protected $_functionSeoManagement;

  public function __construct($allSites, $site_id, Template $tpl, db $db,
                              $table_prefix, $action = '', $item_id = '',
                              User $user = null, Session $session = null,
                              Navigation $navigation, $originalAction = '')
  {
    global $_MODULES;

    parent::__construct($allSites, $site_id, $tpl, $db, $table_prefix, $action,
                        $item_id, $user, $session, $navigation, $originalAction);

    if ($this->getType()) {
      // include custom template's langfile
      $path = './language/' . $this->_user->getLanguage() . '-custom/modules'
            . '/lang.ModuleSiteindexCompendium' . $this->getType() . '.php';
      if (is_file($path)) { require_once($path); }
    }

    if (!ConfigHelper::get('m_mobile_device_detection_activated')) {
      unset($this->_subClasses['mobile']);
    }

    $currentSite = $this->_navigation->getCurrentSite();
    $currentPage = $currentSite->getRootPage(Navigation::TREE_MAIN);
    $this->_functionSeoManagement = new CFunctionSeoManagement(
      $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
    $this->_functionSeoManagement->setPageId($currentPage->getID());
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function _checkDatabase()
  {
    $sql = " SELECT FK_SID "
         . " FROM {$this->table_prefix}module_siteindex_compendium "
         . " WHERE FK_SID = $this->site_id "
         . "   AND SIType = '{$this->getType()}'";
    $exists = $this->db->GetOne($sql);

    if (!$exists) {
      $sql = " INSERT INTO {$this->table_prefix}module_siteindex_compendium (FK_SID, SIType) "
           . " VALUES ($this->site_id, '{$this->getType()}') ";
      $result = $this->db->query($sql);
    }

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->createExtendedData($this,
        $this->_navigation->getCurrentSite()->getRootPage(Navigation::TREE_MAIN)->getID());
    }
  }

  /**
   * Updates the content if the POST parameter 'process' or 'process_all' is set.
   */
  private function _updateContent()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') && !$post->exists('process_all')) {
      return;
    }

    $title   = $post->readString('si_title', Input::FILTER_CONTENT_TITLE);
    $text1   = $post->readString('si_text1', Input::FILTER_CONTENT_TEXT);
    $text2   = $post->readString('si_text2', Input::FILTER_CONTENT_TEXT);
    $text3   = $post->readString('si_text3', Input::FILTER_CONTENT_TEXT);
    $extlink = $post->readString('si_extlink', Input::FILTER_PLAIN);
    list($link, $linkID) = $post->readContentItemLink('si_link');

    if (!Validation::isEmpty($extlink) && !Validation::isUrl($extlink)) {
      $this->_validationError = true;
      $this->setMessage(Message::createFailure($_LANG['si_message_invalid_extlink']));
      return;
    }

    // Read image titles.
    $imageTitles = $post->readImageTitles('si_image_title');

    // Perform the image uploads.
    $sql = " SELECT SIImage1, SIImage2, SIImage3 "
         . " FROM {$this->table_prefix}module_siteindex_compendium "
         . " WHERE FK_SID = $this->site_id "
         . "   AND SIType = '{$this->getType()}'";
    $existingImages = $this->db->GetRow($sql);
    $prefix = $this->_getImagePrefix();

    $image1 = $existingImages['SIImage1'];
    if ($uploadedImage = $this->_storeImage($_FILES['si_image1'], $image1, $prefix, 1, $this->site_id, false)) {
      $image1 = $uploadedImage;
    }
    $image2 = $existingImages['SIImage2'];
    if ($uploadedImage = $this->_storeImage($_FILES['si_image2'], $image2, $prefix, 2, $this->site_id)) {
      $image2 = $uploadedImage;
    }
    $image3 = $existingImages['SIImage3'];
    if ($uploadedImage = $this->_storeImage($_FILES['si_image3'], $image3, $prefix, 3, $this->site_id)) {
      $image3 = $uploadedImage;
    }

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium "
         . " SET SITitle = '{$this->db->escape($title)}', "
         . "     SIText1 = '{$this->db->escape($text1)}', "
         . "     SIText2 = '{$this->db->escape($text2)}', "
         . "     SIText3 = '{$this->db->escape($text3)}', "
         . "     SIImage1 = '$image1', "
         . "     SIImage2 = '$image2', "
         . "     SIImage3 = '$image3', "
         . "     SIImageTitles = '{$this->db->escape($imageTitles)}', "
         . "     FK_CIID = $linkID, "
         . "     SIExtlink = '{$this->db->escape($extlink)}' "
         . " WHERE FK_SID = $this->site_id "
         . "   AND SIType = '{$this->getType()}'";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    $configShareDisplay = ConfigHelper::get('m_share_display', '', $this->site_id);
    $configShareDefault = ConfigHelper::get('m_share_default', '', $this->site_id);
    // set CShare of siteindex
    $share = 0;
      if ($post->exists("share_item")) {
        $share = 1;
    }
    // if checkbox isn't displayed, but sharing activated for the siteindex
    // and the default value set to true -> display share section on siteindex
    else if ($this->_sharingAvailable() && !$configShareDisplay && $configShareDefault) {
      $share = 1;
    }

    $sql = " UPDATE {$this->table_prefix}contentitem "
         . " SET CShare = {$share} "
          ." WHERE FK_CIID IS NULL "
          ." AND FK_CTID IS NULL "
          ." AND FK_SID = {$this->site_id} "
          ." AND CTree = 'main' ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    $this->_functionSeoManagement->setVarsFromPost($post)->update();
    $this->_addContentItemLogEntry();

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->updateExtendedData($this,
        $this->_navigation->getCurrentSite()->getRootPage(Navigation::TREE_MAIN)->getID());
    }

    $this->setMessage(Message::createSuccess($_LANG['si_message_update_success']));
  }

  /**
   * Deletes an image if the GET parameter deleteImage is set.
   */
  private function _deleteImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $number = $get->readInt('deleteImage');
    if (!$number) {
      return;
    }

    // Determine the existing image file.
    $sql = " SELECT SIImage$number "
         . " FROM {$this->table_prefix}module_siteindex_compendium "
         . " WHERE FK_SID = $this->site_id "
         . "   AND SIType = '{$this->getType()}'";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium "
         . " SET SIImage$number = '' "
         . " WHERE FK_SID = $this->site_id "
         . "   AND SIType = '{$this->getType()}'";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['si_message_deleteimage_success']));
  }

  /**
   * Displays the content.
   */
  private function _showContent()
  {
    global $_LANG, $_LANG2;

    $this->_prepareContent();

    // Retrieve the root page (siteindex).
    $currentSite = $this->_navigation->getCurrentSite();
    $currentPage = $currentSite->getRootPage(Navigation::TREE_MAIN);

    $post = new Input(Input::SOURCE_POST);

    // Image prefixes for determining the image size info.
    $prefix = $this->_getImagePrefix();

    $boxCount = 0;

    $sql = 'SELECT SITitle, SIText1, SIText2, SIText3, '
         . '       SIImage1, SIImage2, SIImage3, SIImageTitles, msc.FK_CIID, '
         . '       SIExtlink, CIID, CIIdentifier, ci.FK_SID '
         . "FROM {$this->table_prefix}module_siteindex_compendium msc "
         . "LEFT JOIN {$this->table_prefix}contentitem ci "
         . '          ON msc.FK_CIID = ci.CIID '
         . "WHERE msc.FK_SID = $this->site_id "
         . "  AND msc.SIType = '{$this->getType()}'";
    $row = $this->db->GetRow($sql);

    $deleteImageLink1 = $row['SIImage1'] ? $this->_parseUrl('', array('site' => $this->site_id, 'deleteImage' => 1)) : '';
    $deleteImageLink2 = $row['SIImage2'] ? $this->_parseUrl('', array('site' => $this->site_id, 'deleteImage' => 2)) : '';
    $deleteImageLink3 = $row['SIImage3'] ? $this->_parseUrl('', array('site' => $this->site_id, 'deleteImage' => 3)) : '';

    $zoom1 = $this->_getImageZoomLink('si', $row['SIImage1']);
    $zoom2 = $this->_getImageZoomLink('si', $row['SIImage2']);
    $zoom3 = $this->_getImageZoomLink('si', $row['SIImage3']);

    $siImageTitles = $this->explode_content_image_titles('si', $row['SIImageTitles']);

    $invalidLinks = 0;
    $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);

    if ($this->_validationError) {
      $title = $post->readString('si_title', Input::FILTER_CONTENT_TITLE);
      $text1 = $post->readString('si_text1', Input::FILTER_CONTENT_TEXT);
      $text2 = $post->readString('si_text2', Input::FILTER_CONTENT_TEXT);
      $text3 = $post->readString('si_text3', Input::FILTER_CONTENT_TEXT);
      $extlink = $post->readString('si_extlink', Input::FILTER_PLAIN);
      list($link, $linkID) = $post->readContentItemLink('si_link');
    }
    else {
      $title   = $row['SITitle'];
      $text1   = $row['SIText1'];
      $text2   = $row['SIText2'];
      $text3   = $row['SIText3'];
      $extlink = $row['SIExtlink'];
      if ($internalLink->isInvalid()) {
        $invalidLinks++;
      }
      $link = $internalLink->getIdentifier();
      $linkID = $internalLink->getId();
    }

    // Read areas.
    $areas = new ModuleSiteindexCompendium_Areas($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix, implode(';', $this->action), $this->item_id, $this->_user, $this->session, $this->_navigation, $this->originalAction, $this);
    $areasContent = $areas->show_innercontent();
    $areasItems = $areasContent['content'];
    if ($areasContent['message']) {
      $this->setMessage($areasContent['message']);
    }
    $invalidLinks += $areasContent['invalidLinks'];

    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['si_message_invalid_links'], $invalidLinks)));
    }

    $extendedData = array();
    if (ConfigHelper::get('m_extended_data')) {
      $extendedData = $this->_extendedDataService()->getContentExtensionData($this,
        $this->_navigation->getCurrentSite()->getRootPage(Navigation::TREE_MAIN)->getID());
    }

    $action = 'index.php';
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="action" value="mod_siteindex" />'
                  . '<input type="hidden" name="action2" value="' . $this->_moduleAction . ';edit" />'
                  . '<input type="hidden" id="scrollToAnchor" name="scrollToAnchor" value="" />';
    $buttons = '<input type="submit" class="btn btn-success" name="process_all" value="' . $_LANG['si_button_submit_label'] . '" />';

    // check if sharing is available for the siteindex , if the user has permission
    // to the share module and if it should be displayed
    $configShareDisplay = $configShareDisplay = ConfigHelper::get('m_share_display', '', $this->site_id);

    $share = $currentPage->getShare() ? 1 : 0;
    $this->tpl->load_tpl('site_index_data_action_box', $this->_actionBoxTemplate);
    $this->tpl->parse_if('site_index_data_action_box', 'share_user_available',
      $this->_sharingAvailable() && $configShareDisplay && $this->_user->AvailableModule('share', $this->site_id),
      array(
        'share_checked' => $share ? 'checked' : '',
        'share_item_label' => $_LANG['global_share_item_label'],
        'global_share_item_tooltip' => $_LANG['global_share_item_tooltip'],
      ));
    $this->tpl->parse_if('site_index_data_action_box', 'share_user_not_available',
      $this->_sharingAvailable() && $configShareDisplay && !$this->_user->AvailableModule('share', $this->site_id),
      array(
        'share_checked' => $share ? 'checked' : '',
        'share_item_label' => $_LANG['global_share_item_label'],
        'global_share_item_status' => $_LANG["lo_share_status{$share}"],
      ));

    // Initialize variables for the information box.
    $infoChangeDateTime = '';
    $infoChangedBy = '';
    if (ConfigHelper::get('m_infobox'))
    {
      // Retrieve the user, who changed the content at last and the changedatetime.
      // As the siteindex isn't created by users themselves there is no entry
      // 'created' in the log table.
      $log = Container::make('ContentItemLogService')->getLastUpdated($currentPage->getID());
      // if the content has been changed
      if ($log)
      {
        $timestamp = ContentBase::strToTime($log['LDateTime']);
        if ($timestamp) {
          $infoChangeDateTime = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'si'), $timestamp);
        }
        $infoChangedBy = sprintf($_LANG['global_info_from_label'], $log['UNick']);
      }
    }
    // Display content item information if it is activated in the config.
    $this->tpl->parse_if('site_index_data_action_box', 'info_content', ConfigHelper::get('m_infobox'));
    // Display the user, who changed the content item at last (with datetime)
    // if the content item has been changed.
    $this->tpl->parse_if('site_index_data_action_box', 'info_content_changed',
                         $infoChangeDateTime && $infoChangedBy, array(
      'info_changedatetime' => $infoChangeDateTime,
      'info_changed_by' => $infoChangedBy,
    ));
    $this->tpl->parse_if('site_index_data_action_box', 'info_content_not_changed',
                         !$infoChangeDateTime || !$infoChangedBy);
    $moduleActionBoxes = $this->tpl->parsereturn('site_index_data_action_box',
      array(
        'module_actions_buttons' => $buttons,
        'module_actions_label' => $_LANG["m_actions_label"],
      ));

    $request = new Input(Input::SOURCE_REQUEST);
    $scrollToAnchor = $request->readString('scrollToAnchor');

    $this->tpl->parse_if('site_index_data', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('si'));
    $this->tpl->parse_if('site_index_data', 'delete_image1', $deleteImageLink1, array(
      'si_delete_image1_link' => $deleteImageLink1,
    ));
    $this->tpl->parse_if('site_index_data', 'delete_image2', $deleteImageLink2, array(
      'si_delete_image2_link' => $deleteImageLink2,
    ));
    $this->tpl->parse_if('site_index_data', 'delete_image3', $deleteImageLink3, array(
      'si_delete_image3_link' => $deleteImageLink3,
    ));
    $this->tpl->parse_if('site_index_data', 'seo_management', $this->_functionSeoManagement->isAvailableForUser($this->_user, $currentSite));
    $content = $this->tpl->parsereturn('site_index_data', array_merge(
      $siImageTitles,
      $this->_getUploadedImageDetails($row['SIImage1'], 'si', $prefix, 1),
      $this->_getUploadedImageDetails($row['SIImage2'], 'si', $prefix, 2),
      $this->_getUploadedImageDetails($row['SIImage3'], 'si', $prefix, 3),
      $this->_functionSeoManagement->getTemplateVars(),
      $internalLink->getTemplateVars('si'),
      array(
        'si_title'   => parseOutput($title, 2),
        'si_text1'   => $text1,
        'si_text2'   => $text2,
        'si_text3'   => $text3,
        'si_extlink' => $extlink,
        'si_required_resolution_label1' => $this->_getImageSizeInfo($prefix, 1),
        'si_required_resolution_label2' => $this->_getImageSizeInfo($prefix, 2),
        'si_required_resolution_label3' => $this->_getImageSizeInfo($prefix, 3),
        'si_large_image_available1' => $zoom1,
        'si_large_image_available2' => $zoom2,
        'si_large_image_available3' => $zoom3,
        'si_delete_image_label' => $_LANG['global_delete_image_label'],
        'si_delete_image_question_label' => $_LANG['global_delete_image_question_label'],
        'si_link' => $link,
        'si_link_id' => $linkID,
        'si_areas' => $areasItems,
        'si_site' => $this->site_id,
        'si_action' => $action,
        'si_hidden_fields' => $hiddenFields,
        'si_actions_label' => $_LANG['m_actions_label'],
        'si_image_alt_label' => $_LANG['m_image_alt_label'],
        'si_autocomplete_contentitem_url' => "index.php?action=mod_response_siteindex&site=$this->site_id&request=ContentItemAutoComplete&scope=global",
        'si_scroll_to_anchor' => $scrollToAnchor,
        'si_module_action_boxes' => $moduleActionBoxes,
      ),
      $extendedData,
      $_LANG2['si']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(),
    );
  }

  public function show_innercontent()
  {
    // Create database entries.
    $this->_checkDatabase();

    // Perform update of the content if necessary.
    $this->_updateContent();

    // Perform delete of an image if necessary.
    $this->_deleteImage();

    return $this->_showContent();
  }

  /**
   * Returns the config value prefixes, depending on siteindex type
   *
   * @return array
   *         i.e. array( "si1", "si" )
   */
  public function getConfigPrefix()
  {
    if ($this->getType()) { $prefix[] = 'si' . $this->getType(); }
    $prefix[] = 'si';

    return array_unique($prefix);
  }


  /**
   * Returns the config value prefixes for image configuration values of
   * siteindex areas
   *
   * @param int $position
   *        the position of area to retrieve configuration values for
   *
   * @return array
   */
  public function getAreaImagePrefix($position)
  {
    $position = (int)$position;

    foreach ($this->getConfigPrefix() as $str) {
      $prefix[] = $str . "_site{$this->site_id}_area{$position}";
      $prefix[] = $str . "_site{$this->site_id}_area";
      $prefix[] = $str . "_area{$position}";
      $prefix[] = $str . '_area';
    }

    return $prefix;
  }

  /**
   * Returns the config value prefixes for image configuration values of area
   * boxes
   *
   * @param int $areaPosition
   *        the position of area to retrieve configuration values for
   * @param int $boxPosition
   *        the position of box in area to retrieve configuration values for
   *
   * @return array
   */
  public function getAreaBoxImagePrefix($areaPosition, $boxPosition)
  {
    $areaPosition = (int)$areaPosition;
    $boxPosition = (int)$boxPosition;

    foreach ($this->getConfigPrefix() as $str) {
      $prefix[] = "{$str}_site{$this->site_id}_area{$areaPosition}_box$boxPosition";
      $prefix[] = "{$str}_site{$this->site_id}_area{$areaPosition}_box";
      $prefix[] = "{$str}_site{$this->site_id}_area_box";
      $prefix[] = "{$str}_area{$areaPosition}_box$boxPosition";
      $prefix[] = "{$str}_area{$areaPosition}_box";
      $prefix[] = "{$str}_area_box";
    }

    return $prefix;
  }

  /**
   * The type of siteindex
   *
   * Example: $_CONFIG['si_type'][2] = 1; // use type 1 on site 2
   *          => ModuleSiteindexCompendium1.tpl is loaded
   *          => lang.ModuleSiteindexCompendium1.php is loaded if available
   *          => config variable names may start with "si1_"
   *
   * @return mixed
   *         the siteindex type configured, the type can be an integer value as
   *         well as a string defining which template and configuration to use,
   *         returns ab empty string if no special type
   */
  public function getType()
  {
    return ConfigHelper::get('si_type', '', $this->site_id) ?
           ConfigHelper::get('si_type', '', $this->site_id) : '';
  }

  /**
   * Gets the content of the left box which contains links to siteindex, menu and special pages.
   */
  protected function _getContentLeft($back = false)
  {
    global $_LANG;

    $currentSite = $this->_navigation->getCurrentSite();
    $searchModule = new ModuleSearch($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix,
        '', '', $this->_user, $this->session, $this->_navigation);
    $this->tpl->load_tpl('site_index_left', 'modules/ModuleSiteindex_left.tpl');
    return $this->tpl->parsereturn('site_index_left', array(
        'si_contentleft_showfrontend_link' => sprintf($_LANG['m_contentleft_showfrontend_link'], $currentSite->getUrl()),
        'si_contentleft_search' => $searchModule->getSearchBox(),
    ));
  }

  /**
   * Returns the config value prefixes for image configuration values
   *
   * @return array
   */
  protected function _getImagePrefix()
  {
    foreach ($this->getConfigPrefix() as $str) {
      $prefix[] = $str . '_site' . $this->site_id;
      $prefix[] = $str;
    }

    return $prefix;
  }

  /**
   * Check if the share function is available for the siteindex
   *
   * @return bool true | false
   */
  protected function _sharingAvailable()
  {
    global $_MODULES;

    if (!in_array('share', $_MODULES)) {
      return false;
    }

    $configShare = ConfigHelper::get('m_share_from_page');
    if (isset($configShare[$this->site_id])) {
      return ConfigHelper::isPageOnPath('/', $configShare[$this->site_id]);
    }
    else {
      return true;
    }
  }

  /**
   * This method loads the template for siteindex_compendium within the namespace
   * site_index_data.
   *
   * When extending ModuleSiteindex class, this method can be overridden to
   * parse additional data into the template.
   *
   * NOTE: ensure to call parent::_prepareContent() within the extending Module's
   * _prepareContent() method
   *
   * @return void
   */
  protected function _prepareContent()
  {
    $tplPath = 'modules/ModuleSiteindexCompendium' . $this->getType() . '.tpl';
    if (!is_file($this->tpl->get_root() . '/'. $tplPath)) {
       trigger_error(__CLASS__ . ": Missing module template '$tplPath'.", E_USER_ERROR);
    }
    $this->tpl->load_tpl('site_index_data', $tplPath);
  }
}

<?php

use Core\Services\ExtendedData\Interfaces\InterfaceExtendable;

/**
 * class.ModuleGlobalAreaManagement.php
 *
 * $LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2013 Q2E GmbH
 */

class ModuleGlobalAreaManagement extends Module implements InterfaceExtendable
{
  protected $_dbColumnPrefix = 'GA';

  /**
   * @var string
   */
  protected $_prefix = 'ga';

  /**
   * @var Input
   */
  private $_get;

  /**
   * @var Input
   */
  private $_post;

  /**
   * @var Input
   */
  private $_request;

  /**
   * The position of the currently edited area
   * or first area if there is only one.
   *
   * @var int
   */
  private $_activePosition = 0;

  public function show_innercontent()
  {
    $this->_post = new Input(Input::SOURCE_POST);
    $this->_get  = new Input(Input::SOURCE_GET);
    $this->_request = new Input(Input::SOURCE_REQUEST);

    // Insert missing database entries
    $this->_checkDatabase();

    $this->_update();
    $this->_delete();
    $this->_deleteImage();
    $this->_changeActivation();

    return $this->_getContent();
  }

  /**
   * {@inheritDoc}
   */
  public function getConfigPrefix()
  {
    return array($this->_prefix);
  }

  /**
   * Parses the url for this module
   * @param string $action [optional]
   * @param array $params
   *        URL parameters to add as key value pairs
   * @return string
   */
  public function parseUrl($action = null, $params = array())
  {
    $action = $action !== null ? ';' . $action : '';
    $url = "index.php?action=mod_globalareamgmt&"
         . "action2=main{$action}&site=$this->site_id";
    foreach ($params as $key => $val) {
      $url .= "&{$key}={$val}";
    }
    return $url;
  }

  protected function _createPageAssignment()
  {
    $input = new Input(Input::SOURCE_POST);
    $originalItemId = $this->item_id;
    $this->item_id = $input->readInt('area');

    $result = parent::_createPageAssignment();

    $this->item_id = $originalItemId;
    return $result;
  }

  /**
   * Change area activation status, if the fowllowing $_GET
   * parameters are set
   *   - changeActivationID
   *   - changeActivationTo
   */
  private function _changeActivation()
  {
    global $_LANG;

    $id = $this->_get->readInt('changeActivationID');
    $type = $this->_get->readString('changeActivationTo', Input::FILTER_NONE);

    if (!$id || !$type) {
      return;
    }

    switch ( $type ) {
      case ContentBase::ACTIVATION_ENABLED:
        $to = 0;
        break;
      case ContentBase::ACTIVATION_DISABLED:
        $to = 1;
        break;
      default: return; // invalid activation status
    }

    $sql = " UPDATE {$this->table_prefix}module_global_area "
         . " SET GADisabled = $to "
         . " WHERE GAID = $id "
         . " AND FK_SID = $this->site_id ";
    $this->db->query($sql);

    $msg = $_LANG[$this->_prefix . '_area_message_activation_' . $type];
    $this->setMessage(Message::createSuccess($msg));
  }

  /**
   * Updates an area (does not update assigned boxes).
   */
  private function _update()
  {
    global $_LANG;

    if (!$this->_post->exists('process_ga_area')) {
      return;
    }

    $ID = $this->_post->readKey('process_ga_area');

    $title = $this->_post->readString("ga_area{$ID}_title", Input::FILTER_CONTENT_TITLE);
    $text = $this->_post->readString("ga_area{$ID}_text", Input::FILTER_CONTENT_TEXT);
    list($link, $linkID) = $this->_post->readContentItemLink("ga_area{$ID}_link");
    $extlink = $this->_post->readString("ga_area{$ID}_extlink", Input::FILTER_PLAIN);

    if (!Validation::isEmpty($extlink) && !Validation::isUrl($extlink)) {
      $this->setMessage(Message::createFailure($_LANG['ga_message_invalid_extlink']));
      return;
    }

    $sql = 'SELECT GAPosition '
         . "FROM {$this->table_prefix}module_global_area "
         . "WHERE GAID = $ID ";
    $position = $this->db->GetOne($sql);

    // Perform the image uploads.
    $sql = 'SELECT GAImage '
         . "FROM {$this->table_prefix}module_global_area "
         . "WHERE GAID = $ID ";
    $existingImage = $this->db->GetOne($sql);

    $prefix = $this->_getImagePrefix($position);
    $components = array($ID);
    $image = $existingImage;
    $uploadedImage = $this->_storeImage($_FILES["ga_area{$ID}_image"], $existingImage, $prefix, 0, $components, false, false, 'ga', true, false);
    if ($uploadedImage) {
      $image = $uploadedImage;
    }

    $sql = "UPDATE {$this->table_prefix}module_global_area "
         . "SET GATitle = '{$this->db->escape($title)}', "
         . "    GAText = '{$this->db->escape($text)}', "
         . "    GAImage = '$image', "
         . "    FK_CIID = $linkID, "
         . "    GAExtlink = '{$this->db->escape($extlink)}' "
         . "WHERE GAID = $ID ";
    $result = $this->db->query($sql);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->updateExtendedData($this, $ID);
    }

    $this->setMessage(Message::createSuccess($_LANG['ga_message_area_update_success']));
  }

  /**
   * Deletes an area and assigned boxes.
   */
  private function _delete()
  {
    global $_LANG;

    $ID = $this->_get->readInt('deleteAreaID');
    if (!$ID) {
      return;
    }

    // Determine the existing image files (area and boxes).
    $sql = 'SELECT GAImage '
         . "FROM {$this->table_prefix}module_global_area "
         . "WHERE GAID = $ID "
         . 'UNION '
         . 'SELECT GABImage '
         . "FROM {$this->table_prefix}module_global_area_box "
         . "WHERE FK_GAID = $ID ";
    $images = $this->db->GetCol($sql);

    // Clear the area database entry.
    $sql = "UPDATE {$this->table_prefix}module_global_area "
         . "SET GATitle = '', "
         . "    GAText = '', "
         . "    GAImage = '', "
         . "    GAExtlink = '' "
         . "WHERE GAID = $ID ";
    $this->db->query($sql);

    // Clear the box database entries.
    $sql = "UPDATE {$this->table_prefix}module_global_area_box "
         . "SET GABTitle = '', "
         . "    GABText = '', "
         . "    GABImage = '', "
         . '    GABNoImage = 0, '
         . '    GABNoText = 0, '
         . '    FK_CIID = 0, '
         . "    GABExtlink = '' "
         . "WHERE FK_GAID = $ID ";
    $this->db->query($sql);

    // Delete the image files.
    self::_deleteImageFiles($images);

    // Delete the area contentitem assignments.
    $sql = " DELETE FROM {$this->table_prefix}module_globalareamgmt_assignment "
         . " WHERE FK_GAID = $ID ";
    $this->db->query($sql);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->deleteExtendedData($this, $ID);
      $this->_checkDatabase(); // recreate deleted extended data sets manually
    }

    $this->setMessage(Message::createSuccess($_LANG[$this->_prefix . '_message_area_delete_success']));
  }

  /**
   * Deletes an area image if the GET parameter deleteAreaImage is set.
   */
  private function _deleteImage()
  {
    global $_LANG;

    $ID = $this->_get->readInt('deleteAreaImage');
    if (!$ID) {
      return;
    }

    // Determine the existing image file.
    $sql = 'SELECT GAImage '
         . "FROM {$this->table_prefix}module_global_area "
         . "WHERE GAID = $ID ";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = "UPDATE {$this->table_prefix}module_global_area "
         . "SET GAImage = '' "
         . "WHERE GAID = $ID ";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG[$this->_prefix . '_message_area_deleteimage_success']));
  }

  /**
   * Gets and parses the content of given areas.
   * @param array $items
   * @return string
   */
  private function _getContentAreas($items)
  {
    global $_LANG2;

    $tplPath = 'modules/ModuleGlobalAreaManagement_area.tpl';
    $tplName = $this->_prefix . '_content_area';
    $this->tpl->load_tpl($tplName, $tplPath);
    $this->tpl->parse_loop($tplName, $items, 'area_items');
    foreach ($items as $item) {
      $id = $item['ga_area_id'];
      $position = $item['ga_area_position'];
      $this->tpl->parse_if($tplName, "message{$position}", $position == $this->_activePosition && $this->_getMessage(), $this->_getMessageTemplateArray('ga_area'));
      $this->tpl->parse_if($tplName, "delete_area{$position}_image", $item['ga_area_image'], array(
        'ga_area_delete_image_link' => $this->parseUrl('', array('area' => $id, 'deleteAreaImage' => $id, 'scrollToAnchor' => "a_area{$position}")),
      ));
    }
    $contentArea = $this->tpl->parsereturn($tplName, array_merge($_LANG2[$this->_prefix], array(
      'ga_area_active_position' => $this->_activePosition,
    )));

    return $contentArea;
  }

  /**
   * Gets the areas.
   *
   * @return array
   */
  private function _getAreas()
  {
    global $_LANG;

    $items = array();
    $invalidLinks = 0;

    $sql = ' SELECT GAID, GATitle, GAText, GAImage, GABoxType, GAPosition, GADisabled, '
         . '        GAExtlink, mga.FK_CIID, ci.CIID, ci.CIIdentifier, ci.FK_SID '
         . " FROM {$this->table_prefix}module_global_area mga "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . '      ON mga.FK_CIID = ci.CIID '
         . " WHERE mga.FK_SID = $this->site_id "
         . ' ORDER BY GAPosition ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $areaId = $row['GAID'];
      $areaPosition = $row['GAPosition'];
      $box = new ModuleGlobalAreaManagement_Box(
        $this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix,
        $this->action, $this->item_id, $this->_user, $this->session, $this->_navigation,
        $this->originalAction, $this, $areaId, $areaPosition, $row['GABoxType']);
      $boxesContent = $box->show_innercontent();
      if ($box->getMessage()) {
        $this->setMessage($box->getMessage());
      }
      $invalidLinks += $box->getInvalidLinks();

      // Determine if current area is active.
      if ($this->_request->readInt('area') == $areaId || $count == 1) {
        $this->_activePosition = $areaPosition;
      }

      // detect invalid links
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
      $class = $internalLink->getClass();
      // if a link inside a block is invalid then the block is also marked invalid
      if ($box->getInvalidLinks()) {
        $class = 'invalid';
      }
      if ($internalLink->isInvalid()) {
        $invalidLinks++;
      }

      // Image prefixes for determining the image size info.
      $imagePrefix = $this->_getImagePrefix($areaPosition);

      $activationLightLink = $this->parseUrl('', array('changeActivationID' => $areaId, 'changeActivationTo' => ''));
      if ($row['GADisabled'] == 1) {
        $activationLight = ActivationLightInterface::RED;
        $activationLightLink .= ContentBase::ACTIVATION_ENABLED;
      }
      else {
        $activationLight = ActivationLightInterface::GREEN;
        $activationLightLink .= ContentBase::ACTIVATION_DISABLED;;
      }
      $activationLightLink .= '&amp;scrollToAnchor=a_areas';

      $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                    . '<input type="hidden" name="action" value="mod_globalareamgmt" />'
                    . '<input type="hidden" name="action2" value="edit" />'
                    . '<input type="hidden" id="area'.$areaId.'" name="area" value="'.$areaId.'" />'
                    . '<input type="hidden" id="area'.$areaId.'_scrollToAnchor" name="scrollToAnchor" value="a_area'.$areaId.'" />';

      $items[] = array_merge(
        $this->_getUploadedImageDetails($row['GAImage'], 'ga_area', $imagePrefix),
        $internalLink->getTemplateVars('ga_area'),
        $this->_extendedDataService()->getContentExtensionData($this, $areaId), array(
        'ga_area_action' => 'index.php',
        'ga_area_hidden_fields' => $hiddenFields,
        'ga_area_title' => $row["GATitle"],
        'ga_area_title_plain' => strip_tags($row["GATitle"]),
        'ga_area_text' => $row["GAText"],
        'ga_area_image' => $row['GAImage'],
        'ga_area_large_image_available' => $this->_getImageZoomLink('ga_area', $row['GAImage']),
        'ga_area_required_resolution_label' => $this->_getImageSizeInfo($imagePrefix, 0),
        'ga_area_boxes' => $boxesContent,
        'ga_area_id' => $areaId,
        'ga_area_position' => intval($areaPosition),
        'ga_area_class' => $class,
        'ga_area_delete_link' => $this->parseUrl('', array('deleteAreaID' => $areaId, 'scrollToAnchor' => 'a_areas')),
        'ga_area_label' => $this->_getAreaLabel($areaPosition),
        'ga_area_extlink' => $row["GAExtlink"],
        'ga_area_activation_light'       => $activationLight,
        'ga_area_activation_light_label' => $_LANG['global_activation_light_'.$activationLight.'_label'],
        'ga_area_activation_light_link'  => $activationLightLink,
        'ga_page_assignment' => $this->_parseAreaPageAssignment($row),
        'ga_area_button_save_label' => sprintf($_LANG['ga_area_button_save_label'], $areaPosition),
      ));
    }

    return $items;
  }

  /**
   * Gets the area label of current site with given position
   * or a fallback label.
   *
   * @param int $position
   * @return string
   */
  private function _getAreaLabel($position)
  {
    global $_LANG;

    $label = '';

    if (isset($_LANG['ga_area_label'][$this->site_id][$position])) {
      $label = $_LANG['ga_area_label'][$this->site_id][$position];
    }
    else if (isset($_LANG['ga_area_label'][$this->site_id][0])) {
      $label = $_LANG['ga_area_label'][$this->site_id][0];
    }
    else if (!isset($_LANG['ga_area_label'][$this->site_id]) && isset($_LANG['ga_area_label'][0][$position])) {
      $label = $_LANG['ga_area_label'][0][$position];
    }
    else if (isset($_LANG['ga_area_label'][0][0])) {
      $label = $_LANG['ga_area_label'][0][0];
    }

    $label = ($label) ? sprintf($label, $position) : '';

    return $label;
  }

  /**
   * Gets module's content.
   *
   * @return array
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $pfx = $this->_prefix;
    $areas = $this->_getAreas();
    $siteSelection = $this->_parseModuleSiteSelection($this->getShortname(), $_LANG[$pfx . '_site_label']);
    if (!$areas) {
      $this->setMessage(Message::createFailure($_LANG[$pfx . '_message_areas_not_available']));
    }
    $tplName = 'content_' . $pfx;
    $this->tpl->load_tpl($tplName, 'modules/ModuleGlobalAreaManagement.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray($pfx));
    $content = $this->tpl->parsereturn($tplName, array_merge($_LANG2[$pfx], array(
      $pfx . '_site_selection' => $siteSelection,
      $pfx . '_areas' => $this->_getContentAreas($areas),
      $pfx . '_autocomplete_contentitem_url' => "index.php?action=mod_response_globalareamgmt&site=$this->site_id&request=ContentItemAutoComplete&scope=global",
      $pfx . '_scroll_to_anchor' => $this->_request->readString('scrollToAnchor'),
      $pfx . '_area_box_last_edited' => ModuleGlobalAreaManagement_Box::$lastEdited,
      $pfx . '_area_box_last_edited_id' => ModuleGlobalAreaManagement_Box::$lastEditedId,
    )));

    return array(
      'content' => $content,
    );
  }

  /**
   * Returns the config value prefixes for image configuration values
   *
   * @param int $position
   * @return array
   */
  private function _getImagePrefix($position)
  {
    $str = $this->_prefix;
    $prefix[] = $str . "_site{$this->site_id}_area{$position}";
    $prefix[] = $str . "_site{$this->site_id}_area";
    $prefix[] = $str . "_area{$position}";
    $prefix[] = $str . '_area';

    return $prefix;
  }

  /**
   * Checks database for missing areas and may
   * creates them.
   *
   * @throws Exception
   */
  private function _checkDatabase()
  {
    // Determine the amount of currently existing areas.
    $sql = 'SELECT COUNT(GAID) '
         . "FROM {$this->table_prefix}module_global_area "
         . "WHERE FK_SID = $this->site_id ";
    $existingAreas = $this->db->GetOne($sql);

    $boxTypes = ConfigHelper::get('type_of_boxes', $this->_prefix, $this->site_id);
    $numberOfAreas = ConfigHelper::get('number_of_boxes', $this->_prefix, $this->site_id);

    // "ga_number_of_boxes" specifies the amount of boxes in each area, so
    // the array count specifies the amount of areas.
    if (!is_array($numberOfAreas) || !is_array($boxTypes)) {
      return;
    }
    $numberOfAreas = count($numberOfAreas);

    if ($numberOfAreas != count($boxTypes)) {
      $message = 'The configuration variables "ga_number_of_boxes" and '
               . '"ga_type_of_boxes" do not match (different amount of areas specified).';
      throw new Exception($message);
    }

    $model = $this->_getModel();
    // Create missing areas.
    for ($i = $existingAreas + 1; $i <= $numberOfAreas; $i++) {
      $boxType = $boxTypes[$i - 1];
      if (   $boxType != GlobalArea::BOX_TYPE_LARGE
          && $boxType != GlobalArea::BOX_TYPE_MEDIUM
          && $boxType != GlobalArea::BOX_TYPE_SMALL
      ) {
        throw new Exception("The configured box type '$boxType' is unknown.");
      }

      $model->reset();
      $model->boxType = $boxType;
      $model->position = $i;
      $model->parent = $this->site_id;
      $model->linkId = 0;
      $model->create();
    }

    if (ConfigHelper::get('m_extended_data')) {
      $sql = " SELECT GAID "
           . " FROM {$this->table_prefix}module_global_area "
           . " WHERE FK_SID = $this->site_id ";
      $ids = $this->db->GetCol($sql);

      $this->_extendedDataService()->createExtendedData($this, $ids);
    }
  }

  /**
   * @return GlobalArea
   */
  private function _getModel()
  {
    if ($this->_model === null) {
      $this->_model = new GlobalArea($this->db, $this->table_prefix, $this->_prefix);
    }
    return $this->_model;
  }

  /**
   * Fetches the page assignment HTML for given area
   *
   * @see Module::_parseModulePageAssignment()
   *
   * @param array $row
   * @return string
   */
  private function _parseAreaPageAssignment($row)
  {
    $html = '';

    // check if page assignments are available for given area
    $available = ConfigHelper::get('assignments_available', $this->_prefix, $this->site_id);
    $available = isset($available[(int)$row['GAPosition'] - 1]) ? (bool)$available[(int)$row['GAPosition'] - 1] : false;

    if ($available) {
      $originalItem = $this->item_id;
      $this->item_id = $row['GAID'];

      $html = $this->_parseModulePageAssignment(array('area' => $row['GAID'], 'scrollToAnchor' => 'a_area' . $row['GAPosition']));

      $this->item_id = $originalItem;
    }

    return $html;
  }
}
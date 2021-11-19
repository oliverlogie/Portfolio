<?php

use Core\Services\ExtendedData\Interfaces\InterfaceExtendable;

/**
 * Siteindex module helper (handling areas).
 *
 * $LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleSiteindexCompendium_Areas extends Module implements InterfaceExtendable
{
  /**
   * The siteindex Module object of siteindex the areas belong to
   *
   * @var ModuleSiteindex
   */
  private $_parent;

  /**
   * @var bool
   */
  private $_validationError = false;

  /**
   * Change siteindex compedium area activation status, if the fowllowing $_GET
   * parameters are set
   *   - changeActivationID
   *   - changeActivationTo
   */
  private function _changeActivation()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $id = $get->readInt('changeActivationID');
    $type = $get->readString('changeActivationTo', Input::FILTER_NONE);

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

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area "
         . " SET SADisabled = $to "
         . " WHERE SAID = $id ";
    $this->db->query($sql);

    $msg = $_LANG['si_area_message_activation_'.$type];
    $this->setMessage(Message::createSuccess($msg));
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  private function _checkDatabase()
  {
    // Determine the amount of currently existing areas.
    $sql = " SELECT COUNT(SAID) "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE FK_SID = $this->site_id "
         . "   AND SASiteindexType = '{$this->getSiteindexType()}' ";
    $existingAreas = $this->db->GetOne($sql);

    $boxTypes = ConfigHelper::get('type_of_boxes', $this->_parent->getConfigPrefix(), $this->site_id);
    $numberOfAreas = ConfigHelper::get('number_of_boxes', $this->_parent->getConfigPrefix(), $this->site_id);

    // "si_number_of_boxes" specifies the amount of boxes in each area, so
    // the array count specifies the amount of areas.
    if (!is_array($numberOfAreas) || !is_array($boxTypes)) {
      return;
    }
    $numberOfAreas = count($numberOfAreas);

    if ($numberOfAreas != count($boxTypes)) {
      $message = 'The configuration variables "si_number_of_boxes" and '
               . '"si_type_of_boxes" do not match (different amount of areas specified).';
      throw new Exception($message);
    }

    // Create missing areas.
    for ($i = $existingAreas + 1; $i <= $numberOfAreas; $i++) {
      $boxType = $boxTypes[$i - 1];
      if (   $boxType != ModuleSiteindexCompendium_Area_Boxes::TYPE_LARGE
          && $boxType != ModuleSiteindexCompendium_Area_Boxes::TYPE_MEDIUM
          && $boxType != ModuleSiteindexCompendium_Area_Boxes::TYPE_SMALL
      ) {
        throw new Exception("The configured box type '$boxType' is unknown.");
      }

      $sql = " INSERT INTO {$this->table_prefix}module_siteindex_compendium_area "
           . " (SABoxType, SAPosition, FK_SID, SASiteindexType) VALUES "
           . " ('$boxType', $i, $this->site_id, '{$this->getSiteindexType()}') ";
      $this->db->query($sql);
    }

    if (ConfigHelper::get('m_extended_data')) {
      $sql = " SELECT SAID "
           . " FROM {$this->table_prefix}module_siteindex_compendium_area "
           . " WHERE FK_SID = $this->site_id "
           . "   AND SASiteindexType = '{$this->getSiteindexType()}' ";
      $ids = $this->db->GetCol($sql);

      $this->_extendedDataService()->createExtendedData($this, $ids);
    }
  }

  /**
   * Updates areas if the POST parameter 'process_si_area' is set.
   */
  private function _updateArea()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_si_area')) {
      return;
    }

    $ID = $post->readKey('process_si_area');

    $title = $post->readString("si_area{$ID}_title", Input::FILTER_CONTENT_TITLE);
    $text = $post->readString("si_area{$ID}_text", Input::FILTER_CONTENT_TEXT);
    $extlink = $post->readString("si_area{$ID}_extlink", Input::FILTER_PLAIN);
    list($link, $linkID) = $post->readContentItemLink("si_area{$ID}_link");

    if (!Validation::isEmpty($extlink) && !Validation::isUrl($extlink)) {
      $this->_validationError = true;
      $this->setMessage(Message::createFailure($_LANG['si_message_invalid_extlink']));
      return;
    }

    $sql = " SELECT SAPosition "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE SAID = $ID ";
    $position = $this->db->GetOne($sql);

    // Perform the image uploads.
    $sql = " SELECT SAImage "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE SAID = $ID ";
    $existingImage = $this->db->GetOne($sql);

    $prefix = $this->_getImagePrefix($position);
    $components = array($this->site_id, $ID);
    $image = $existingImage;
    if ($uploadedImage = $this->_storeImage($_FILES["si_area{$ID}_image"], $existingImage, $prefix, 0, $components)) {
      $image = $uploadedImage;
    }

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area "
         . " SET SATitle = '{$this->db->escape($title)}', "
         . "     SAText = '{$this->db->escape($text)}', "
         . "     SAImage = '$image', "
         . "     FK_CIID = $linkID, "
         . "     SAExtlink = '{$this->db->escape($extlink)}' "
         . " WHERE SAID = $ID ";
    $this->db->query($sql);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->updateExtendedData($this, $ID);
    }

    $this->setMessage(Message::createSuccess($_LANG['si_message_area_update_success']));

    $this->_addContentItemLogEntry();
  }

  /**
   * Deletes (resets) an area if the GET parameter deleteAreaID is set.
   */
  private function _deleteArea()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteAreaID');
    if (!$ID) {
      return;
    }

    // Determine the existing image files (area and boxes).
    $sql = " SELECT SAImage "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE SAID = $ID ";
    $images = $this->db->GetCol($sql);

    $sql = " SELECT SBImage1, SBImage2, SBImage3 "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
         . " WHERE FK_SAID = $ID ";
    $results = $this->db->GetAssoc($sql);

    foreach ($results as $row) {
      $images[] = $row['SBImage1'];
      $images[] = $row['SBImage2'];
      $images[] = $row['SBImage3'];
    }

    // Clear the area database entry.
    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area "
         . " SET SATitle = '', "
         . "     SAText = '', "
         . "     SAImage = '' "
         . " WHERE SAID = $ID ";
    $this->db->query($sql);

    // Clear the box database entries.
    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area_box "
         . " SET SBTitle1 = '', "
         . "     SBTitle2 = '', "
         . "     SBTitle3 = '', "
         . "     SBText1 = '', "
         . "     SBText2 = '', "
         . "     SBText3 = '', "
         . "     SBImage1 = '', "
         . "     SBImage2 = '', "
         . "     SBImage3 = '', "
         . "     SBNoImage = 0, "
         . "     FK_CIID = 0 "
         . " WHERE FK_SAID = $ID ";
    $this->db->query($sql);

    // Delete the image files.
    self::_deleteImageFiles($images);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->deleteExtendedData($this, $ID);
      $this->_checkDatabase(); // recreate deleted extended data sets manually
    }

    $this->setMessage(Message::createSuccess($_LANG['si_message_area_delete_success']));
  }

  /**
   * Deletes an area image if the GET parameter deleteAreaImage is set.
   */
  private function _deleteAreaImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteAreaImage');
    if (!$ID) {
      return;
    }

    // Determine the existing image file.
    $sql = " SELECT SAImage "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE SAID = $ID ";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area "
         . " SET SAImage = '' "
         . " WHERE SAID = $ID ";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['si_message_area_deleteimage_success']));
  }

  /**
   * Shows all areas.
   */
  private function _showAreas()
  {
    global $_LANG;

    $items = array();
    $activePosition = 0;
    $invalidLinks = 0;
    $positionHelper = new PositionHelper(
      $this->db, "{$this->table_prefix}module_siteindex_compendium_area",
      'SAID', 'SAPosition',
      'FK_SID', $this->site_id
    );

    $sql = " SELECT SAID, SATitle, SAText, SAImage, SABoxType, SAPosition, SADisabled, "
         . "        SAExtlink, msca.FK_CIID, ci.CIID, ci.CIIdentifier, ci.FK_SID "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area msca "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . "      ON msca.FK_CIID = ci.CIID "
         . " WHERE msca.FK_SID = $this->site_id "
         . "   AND SASiteindexType = '{$this->getSiteindexType()}' "
         . " ORDER BY SAPosition ";
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['SAPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['SAPosition']);
      // Read boxes.
      $boxes = new ModuleSiteindexCompendium_Area_Boxes($this->_allSites, $this->site_id, $this->tpl, $this->db,
                                                        $this->table_prefix, implode(';', $this->action),
                                                        $this->item_id, $this->_user, $this->session, $this->_navigation,
                                                        $this->originalAction, $this->_parent, $row['SAID'], $row['SAPosition'],
                                                        $row['SABoxType']);
      $boxesContent = $boxes->show_innercontent();
      $boxesItems = $boxesContent['content'];
      if ($boxesContent['message']) {
        $this->setMessage($boxesContent['message']);
      }
      $invalidLinks += $boxesContent['invalidLinks'];

      // Determine if current area is active.
      $request = new Input(Input::SOURCE_REQUEST);
      if ($request->readInt('area') == $row['SAID'] || $count == 1) {
        $activePosition = $row['SAPosition'];
      }

      // detect invalid links
      $class = 'normal';
      // if a link inside a block is invalid then the block is also marked invalid
      if ($boxesContent['invalidLinks']) {
        $class = 'invalid';
      }

      // Image prefixes for determining the image size info.
      $imagePrefix = $this->_getImagePrefix($row['SAPosition']);
      $areaLabel = isset($_LANG['si_area_label'][$row['SAPosition']]) ?
                         $_LANG['si_area_label'][$row['SAPosition']] :
                         $_LANG['si_area_label'][0];

      if ($row['SADisabled'] == 1) {
        $activationLight = ActivationLightInterface::RED;
        $activationChangeTo = ContentBase::ACTIVATION_ENABLED;
      }
      else {
        $activationLight = ActivationLightInterface::GREEN;
        $activationChangeTo = ContentBase::ACTIVATION_DISABLED;;
      }

      $activationLightLink = $this->_parseUrl('', array(
          'site' => $this->site_id,
          'changeActivationID' => $row['SAID'],
          'changeActivationTo' => $activationChangeTo,
          'scrollToAnchor' => 'a_areas',
      ));

      $action = isset($this->originalAction[0]) && $this->originalAction[0] ?
                $this->originalAction[0] . ';edit' : 'main;edit';
      $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                    . '<input type="hidden" name="action" value="mod_siteindex" />'
                    . '<input type="hidden" name="action2" value="' . $action . '" />'
                    . '<input type="hidden" id="area'.$row['SAID'].'" name="area" value="0" />'
                    . '<input type="hidden" id="area'.$row['SAID'].'_scrollToAnchor" name="scrollToAnchor" value="" />';

      $areaId = $row['SAID'];
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
      if ($this->_validationError && $activePosition) { // display unsaved data
        $areaTitle    = $request->readString("si_area{$areaId}_title", Input::FILTER_CONTENT_TITLE);
        $areaText     = $request->readString("si_area{$areaId}_text", Input::FILTER_CONTENT_TEXT);
        $areaExtlink  = $request->readString("si_area{$areaId}_extlink", Input::FILTER_PLAIN);
        list($link, $linkID) = $request->readContentItemLink("si_area{$areaId}_link");
      }
      else {
        $areaTitle    = $row['SATitle'];
        $areaText     = $row['SAText'];
        $areaExtlink  = $row['SAExtlink'];

        // Detect invalid and invisible area links.
        if ($class !== 'invalid')  {
          $class = $internalLink->getClass();
        }
        if ($internalLink->isInvalid()) {
          $invalidLinks++;
        }
        $link = $internalLink->getIdentifier();
        $linkID = $internalLink->getId();
      }

      $extendedData = array();
      if (ConfigHelper::get('m_extended_data')) {
        $extendedData = $this->_extendedDataService()->getContentExtensionData($this, $row['SAID']);
      }

      $items[] = array_merge($this->_getUploadedImageDetails($row['SAImage'], 'si_area', $imagePrefix),
        $internalLink->getTemplateVars('si_area'), array(
        'si_area_action' => 'index.php',
        'si_area_hidden_fields' => $hiddenFields,
        'si_area_title' => parseOutput($areaTitle, 2),
        'si_area_title_plain' => strip_tags($areaTitle),
        'si_area_text' => $areaText,
        'si_area_extlink' => $areaExtlink,
        'si_area_image' => $row['SAImage'],
        'si_area_large_image_available' => $this->_getImageZoomLink('si_area', $row['SAImage']),
        'si_area_required_resolution_label' => $this->_getImageSizeInfo($imagePrefix, 0),
        'si_area_boxes' => $boxesItems,
        'si_area_id' => $areaId,
        'si_area_position' => intval($row["SAPosition"]),
        'si_area_class' => $class,
        'si_area_delete_link' => $this->_parseUrl('', array('site' => $this->site_id, 'deleteAreaID' => $row['SAID'], 'scrollToAnchor' => 'a_areas')),
        'si_area_label' => $areaLabel,
        'si_area_link' => $link,
        'si_area_link_id' => $linkID,
        'si_area_activation_light'       => $activationLight,
        'si_area_activation_light_label' => $_LANG['global_activation_light_'.$activationLight.'_label'],
        'si_area_activation_light_link'  => $activationLightLink,
        'si_area_move_up_link'           => $this->_parseUrl('', array('site' => $this->site_id, 'moveAreaID' => $row["SAID"], 'moveAreaTo' => $moveUpPosition, 'scrollToAnchor' => 'a_areas')),
        'si_area_move_down_link'         => $this->_parseUrl('', array('site' => $this->site_id, 'moveAreaID' => $row["SAID"], 'moveAreaTo' => $moveDownPosition, 'scrollToAnchor' => 'a_areas')),
        'si_area_button_save_label' => sprintf($_LANG['si_area_button_save_label'], $row['SAPosition']),
      ), $extendedData);
    }
    $this->db->free_result($result);

    $tplPath = 'modules/ModuleSiteindexCompendium' . $this->getSiteindexType() . '_Area.tpl';
    if (!is_file($this->tpl->get_root() . '/'. $tplPath)) {
       trigger_error(__CLASS__ . ": Missing module template '$tplPath'.", E_USER_ERROR);
    }
    $this->tpl->load_tpl('site_index_area', $tplPath);
    $this->tpl->parse_loop('site_index_area', $items, 'area_items');
    foreach ($items as $item) {
      $this->tpl->parse_if('site_index_area', "message{$item['si_area_position']}", $item['si_area_position'] == $activePosition && $this->_getMessage(), $this->_getMessageTemplateArray('si_area'));
      $this->tpl->parse_if('site_index_area', "delete_area{$item['si_area_position']}_image", $item['si_area_image'], array(
        'si_area_delete_image_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $item['si_area_id'], 'deleteAreaImage' => $item['si_area_id'], 'scrollToAnchor' => 'a_area' . $item['si_area_position'])),
      ));
    }
    $itemsOutput = $this->tpl->parsereturn('site_index_area', array(
      'si_area_count' => $count,
      'si_area_active_position' => $activePosition,
      'si_area_dragdrop_link_js' => $this->_parseUrl('', array('site' => $this->site_id, 'moveAreaID' => '#moveID#', 'moveAreaTo' => '#moveTo#', 'scrollToAnchor' => 'a_areas')),
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $itemsOutput,
      'invalidLinks' => $invalidLinks,
    );
  }

  /**
   * Returns the config value prefixes for image configuration values
   *
   * @param int $position
   *        the position of area to retrieve configuration values for
   *
   * @return array
   */
  private function _getImagePrefix($position)
  {
    return $this->_parent->getAreaImagePrefix($position);
  }

  /**
   * Moves an area if the GET parameters moveAreaID and moveAreaTo are set.
   */
  private function _move() {
    global $_LANG;

    if (!isset($_GET['moveAreaID'], $_GET['moveAreaTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveAreaID'];
    $moveTo = (int)$_GET['moveAreaTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_siteindex_compendium_area",
                                         'SAID', 'SAPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['si_message_area_move_success']));
    }
  }

  public function __construct($allSites, $site_id, Template $tpl, db $db,
                              $table_prefix, $action = '', $item_id = '',
                              User $user = null, Session $session = null,
                              Navigation $navigation, $originalAction = '',
                              ModuleSiteindex $parent)
  {
    parent::__construct($allSites, $site_id, $tpl, $db, $table_prefix, $action,
                        $item_id, $user, $session, $navigation, $originalAction);

    $this->_parent = $parent;
  }

  public function show_innercontent()
  {
    // Insert missing database entries
    $this->_checkDatabase();

    // Perform update/delete of an area if necessary.
    $this->_updateArea();
    $this->_deleteArea();
    $this->_changeActivation();
    $this->_move();

    // Perform delete of area image if necessary.
    $this->_deleteAreaImage();

    return $this->_showAreas();
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
  public function getSiteindexType()
  {
    return $this->_parent->getType();
  }

  /**
   * {@inheritDoc}
   */
  public function getConfigPrefix()
  {
    $prefixes = $this->_parent->getConfigPrefix();

    foreach ($prefixes as $key => $prefix) {
      $prefixes[$key] = str_replace('si', 'si_area', $prefix);
    }

    return $prefixes;
  }
}
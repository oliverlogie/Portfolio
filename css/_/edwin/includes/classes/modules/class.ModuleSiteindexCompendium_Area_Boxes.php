<?php

/**
 * Siteindex module helper (handling boxes).
 *
 * $LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleSiteindexCompendium_Area_Boxes extends Module implements \Core\Services\ExtendedData\Interfaces\InterfaceExtendable
{
  /**
   * The large box type contains title, text, image and link.
   *
   * @var string
   */
  const TYPE_LARGE = 'large';
  /**
   * The medium box type contains title, image and link.
   *
   * @var string
   */
  const TYPE_MEDIUM = 'medium';
  /**
   * The small box type contains title and link.
   *
   * @var string
   */
  const TYPE_SMALL = 'small';

  /**
   * The area id.
   *
   * @var integer
   */
  private $_areaID;
  /**
   * The area position (starting with 1).
   *
   * @var integer
   */
  private $_areaPosition;
  /**
   * The type of the boxes inside the area.
   *
   * One of the TYPE_* constants.
   *
   * @var string
   */
  private $_areaBoxType;

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
   * Change siteindex compedium area box activation status, if the fowllowing
   * $_GET parameters are set
   *   - changeActivationID
   *   - changeActivationTo
   */
  private function _changeActivation()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $id = $get->readInt('changeActivationBoxID');
    $type = $get->readString('changeActivationBoxTo', Input::FILTER_NONE);

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

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area_box "
         . " SET SBDisabled = $to "
         . " WHERE SBID = $id ";
    $this->db->query($sql);

    $msg = $_LANG['si_area_box_message_activation_'.$type];
    $this->setMessage(Message::createSuccess($msg));
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  private function _checkDatabase()
  {
    // Determine the amount of currently existing boxes.
    $sql = 'SELECT COUNT(SBID) '
         . "FROM {$this->table_prefix}module_siteindex_compendium_area_box "
         . "WHERE FK_SAID = $this->_areaID ";
    $existingBoxes = $this->db->GetOne($sql);

    // "si_number_of_boxes" specifies the amount of boxes in each area, to
    // determine the amount of boxes for the current area we use the area
    // position - 1 as the array index.
    $numberOfBoxes = ConfigHelper::get('number_of_boxes', $this->_parent->getConfigPrefix(), $this->site_id);
    if (!isset($numberOfBoxes[$this->_areaPosition - 1])) {
      return;
    }
    $numberOfBoxes = $numberOfBoxes[$this->_areaPosition - 1];
    // Create missing boxes.
    for ($i = $existingBoxes + 1; $i <= $numberOfBoxes; $i++) {
      $sql = " INSERT INTO {$this->table_prefix}module_siteindex_compendium_area_box "
           . " (SBPosition, FK_SAID) VALUES "
           . " ($i, $this->_areaID) ";
      $result = $this->db->query($sql);
    }

    if (ConfigHelper::get('m_extended_data')) {
      $sql = " SELECT SBID "
           . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
           . " WHERE FK_SAID = $this->_areaID ";
      $ids = $this->db->GetCol($sql);

      $this->_extendedDataService()->createExtendedData($this, $ids);
    }
  }

  /**
   * Updates boxes if the POST parameter 'process_si_area_box' is set.
   */
  private function _updateBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    if (!$post->exists('process_si_area_box') || ($post->readInt('area') != $this->_areaID)) {
      return;
    }

    $ID = $post->readKey('process_si_area_box');

    $title1 = $post->readString("si_area_box{$ID}_title1", Input::FILTER_CONTENT_TITLE);
    $title2 = $post->readString("si_area_box{$ID}_title2", Input::FILTER_CONTENT_TITLE);
    $title3 = $post->readString("si_area_box{$ID}_title3", Input::FILTER_CONTENT_TITLE);
    $text1 = $post->readString("si_area_box{$ID}_text1", Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString("si_area_box{$ID}_text2", Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString("si_area_box{$ID}_text3", Input::FILTER_CONTENT_TEXT);
    $extlink = $post->readString("si_area_box{$ID}_extlink", Input::FILTER_PLAIN);
    $noAutoText = (int)$post->readBool("checkbox_hide_text_{$ID}");
    $noImage = (int)$post->readBool("si_area_box{$ID}_noimage");
    list($link, $linkID) = $post->readContentItemLink("si_area_box{$ID}_link");

    if (!Validation::isEmpty($extlink) && !Validation::isUrl($extlink)) {
      $this->_validationError = true;
      $this->setMessage(Message::createFailure($_LANG['si_message_invalid_extlink']));
      return;
    }

    if ($noAutoText) {
      $text1 = '&nbsp;';
    }

    // Read existing box infos.
    $sql = " SELECT SBImage1, SBImage2, SBImage3, SBPosition, FK_CIID "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
         . " WHERE SBID = $ID ";
    $row = $this->db->GetRow($sql);
    $position = (int)$row['SBPosition'];

    // Perform the image uploads.
    $prefix = $this->_getImagePrefix($position);
    $components = array($this->site_id, $this->_areaID, $ID);

    $existingImage1 = $row['SBImage1'];
    $image1 = $existingImage1;
    if (!$noImage && isset($_FILES["si_area_box{$ID}_image1"]) && $uploadedImage = $this->_storeImage($_FILES["si_area_box{$ID}_image1"], $existingImage1, $prefix, 1, $components)) {
      $image1 = $uploadedImage;
    }

    $existingImage2 = $row['SBImage2'];
    $image2 = $existingImage2;
    if (isset($_FILES["si_area_box{$ID}_image2"]) && $uploadedImage = $this->_storeImage($_FILES["si_area_box{$ID}_image2"], $existingImage2, $prefix, 2, $components)) {
      $image2 = $uploadedImage;
    }

    $existingImage3 = $row['SBImage3'];
    $image3 = $existingImage3;
    if (isset($_FILES["si_area_box{$ID}_image3"]) && $uploadedImage = $this->_storeImage($_FILES["si_area_box{$ID}_image3"], $existingImage3, $prefix, 3, $components)) {
      $image3 = $uploadedImage;
    }

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area_box "
         . " SET SBTitle1 = '{$this->db->escape($title1)}', "
         . "     SBTitle2 = '{$this->db->escape($title2)}', "
         . "     SBTitle3 = '{$this->db->escape($title3)}', "
         . "     SBText1 = '{$this->db->escape($text1)}', "
         . "     SBText2 = '{$this->db->escape($text2)}', "
         . "     SBText3 = '{$this->db->escape($text3)}', "
         . "     SBImage1 = '$image1', "
         . "     SBImage2 = '$image2', "
         . "     SBImage3 = '$image3', "
         . "     SBNoImage = $noImage, "
         . "     FK_CIID = $linkID, "
         . "     SBExtlink = '{$this->db->escape($extlink)}' "
         . " WHERE SBID = $ID ";
    $this->db->query($sql);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->updateExtendedData($this, $ID);
    }

    $this->setMessage(Message::createSuccess($_LANG['si_message_area_box_update_success']));
    $this->_addContentItemLogEntry();
  }

  /**
   * Moves a box if the GET parameters moveBoxID and moveBoxTo are set.
   */
  private function _moveBox()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('area') != $this->_areaID) {
      return;
    }

    $moveID = $get->readInt('moveBoxID');
    $moveTo = $get->readInt('moveBoxTo');
    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_siteindex_compendium_area_box",
                                         'SBID', 'SBPosition',
                                         'FK_SAID', $this->_areaID,
                                         'SBPositionLocked');
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['si_message_area_box_move_success']));
    }
  }

  /**
   * Deletes (resets) a box if the GET parameter deleteBoxID is set.
   */
  private function _deleteBox()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('area') != $this->_areaID) {
      return;
    }

    $ID = $get->readInt('deleteBoxID');
    if (!$ID) {
      return;
    }

    // Determine the existing image file.
    $sql = " SELECT SBImage1, SBImage2, SBImage3 "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
         . " WHERE SBID = $ID ";
    $row = $this->db->GetRow($sql);

    // Update database entry before actually deleting the image file.
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
         . " WHERE SBID = $ID ";
    $result = $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($row['SBImage1']);
    self::_deleteImageFiles($row['SBImage2']);
    self::_deleteImageFiles($row['SBImage3']);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->deleteExtendedData($this, $ID);
      $this->_checkDatabase(); // recreate deleted extended data datasets manually
    }

    $this->setMessage(Message::createSuccess($_LANG['si_message_area_box_delete_success']));
  }

  /**
   * Deletes a box image if the GET parameter deleteBoxImage is set.
   */
  private function _deleteBoxImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('area') != $this->_areaID) {
      return;
    }

    $ID = $get->readInt('deleteBoxImage');
    if (!$ID) {
      return;
    }

    $number = $get->readInt('deleteBoxImageNumber');
    if (!$number) {
      return;
    }

    // Determine the existing image file.
    $sql = " SELECT SBImage$number "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
         . " WHERE SBID = $ID ";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area_box "
         . " SET SBImage$number = '' "
         . " WHERE SBID = $ID ";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['si_message_area_box_deleteimage_success']));
  }

  /**
   * Shows all boxes.
   */
  private function _showBoxes()
  {
    global $_LANG;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_siteindex_compendium_area_box",
                                         'SBID', 'SBPosition',
                                         'FK_SAID', $this->_areaID,
                                         'SBPositionLocked');
    $items = array();
    $activePosition = 0;
    $invalidLinks = 0;

    $sql = " SELECT SBID, SBTitle1, SBTitle2, SBTitle3, "
         . "        SBText1, SBText2, SBText3, "
         . "        SBImage1, SBImage2, SBImage3, SBNoImage, SBPosition, "
         . "        SBDisabled, SADisabled, SBExtlink, "
         . "        mscb.FK_CIID, CIID, CIIdentifier, ci.FK_SID, ca.CImage "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area_box mscb "
         . " JOIN {$this->table_prefix}module_siteindex_compendium_area msca "
         . "      ON SAID = FK_SAID "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . "      ON mscb.FK_CIID = ci.CIID "
         . " LEFT JOIN {$this->table_prefix}contentabstract ca "
         . "      ON ci.CIID = ca.FK_CIID "
         . " WHERE mscb.FK_SAID = $this->_areaID "
         . " ORDER BY SBPosition ";
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['SBPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['SBPosition']);

      // Determine if current box is active.
      $request = new Input(Input::SOURCE_REQUEST);
      if ($request->readInt('box') == $row['SBID']) {
        $activePosition = $row['SBPosition'];
      }

      $position = (int)$row['SBPosition'];
      $prefix = $this->_getImagePrefix($position);
      // box activation state
      if ($row['SBDisabled'] == 1) {
        $activationLight = ActivationLightInterface::RED;
        $activationChangeTo = ContentBase::ACTIVATION_ENABLED;
      }
      else {
        // area is disabled so item itself has a yellow light as it can not be
        // displayed although it is enabled
        if ($row['SADisabled'] == 1)
          $activationLight = ActivationLightInterface::YELLOW;
        else
          $activationLight = ActivationLightInterface::GREEN;
        $activationChangeTo = ContentBase::ACTIVATION_DISABLED;;
      }
      $activationLightLink = $this->_parseUrl('', array(
          'site'                  => $this->site_id,
          'area'                  => $this->_areaID,
          'changeActivationBoxID' => $row['SBID'],
          'changeActivationBoxTo' => $activationChangeTo,
          'scrollToAnchor'        => "a_area{$this->_areaPosition}_boxes",
      ));

      $action2 = isset($this->originalAction[0]) && $this->originalAction[0] ?
                 $this->originalAction[0] . ';edit' : 'main;edit';
      $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                    . '<input type="hidden" name="action" value="mod_siteindex" />'
                    . '<input type="hidden" name="action2" value="' . $action2 . '" />'
                    . '<input type="hidden" id="area'.$this->_areaID.'_box'.$row['SBID'].'" name="area" value="0" />'
                    . '<input type="hidden" id="box'.$row['SBID'].'" name="box" value="0" />'
                    . '<input type="hidden" id="box'.$row['SBID'].'_scrollToAnchor" name="scrollToAnchor" value="" />';

      $boxId = $row['SBID'];
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
      if ($this->_validationError && $activePosition) { // display unsaved data
        $boxTitle1       = $request->readString("si_area_box{$boxId}_title1", Input::FILTER_CONTENT_TITLE);
        $boxTitle2       = $request->readString("si_area_box{$boxId}_title2", Input::FILTER_CONTENT_TITLE);
        $boxTitle3       = $request->readString("si_area_box{$boxId}_title3", Input::FILTER_CONTENT_TITLE);
        $boxText1        = $request->readString("si_area_box{$boxId}_text1", Input::FILTER_CONTENT_TEXT);
        $boxText2        = $request->readString("si_area_box{$boxId}_text2", Input::FILTER_CONTENT_TEXT);
        $boxText3        = $request->readString("si_area_box{$boxId}_text3", Input::FILTER_CONTENT_TEXT);
        $boxExtlink     = $request->readString("si_area_box{$boxId}_extlink", Input::FILTER_PLAIN);
        $boxNoAutoText  = (int)$request->readBool("checkbox_hide_text1_{$boxId}");
        $boxNoImage     = (int)$request->readBool("si_area_box{$boxId}_noimage");
        list($link, $linkID) = $request->readContentItemLink("si_area_box{$boxId}_link");
      }
      else {
        $boxTitle1    = $row['SBTitle1'];
        $boxTitle2    = $row['SBTitle2'];
        $boxTitle3    = $row['SBTitle3'];
        $boxText1     = $row['SBText1'];
        $boxText2     = $row['SBText2'];
        $boxText3     = $row['SBText3'];
        $boxExtlink  = $row['SBExtlink'];
        $boxNoImage  = $row['SBNoImage'];
        if ($internalLink->isInvalid()) {
          $invalidLinks++;
        }
        $link = $internalLink->getIdentifier();
        $linkID = $internalLink->getId();
      }

      $image1 = '';
      if ($row["SBImage1"]) {
        $image1 = $row["SBImage1"];
      }
      else if ($row["CImage"]) {
        $image1 = $row["CImage"];
      }

      $extendedData = array();
      if (ConfigHelper::get('m_extended_data')) {
        $extendedData = $this->_extendedDataService()->getContentExtensionData($this, $row['SBID']);
      }

      $item = array_merge($this->_getUploadedImageDetails($image1, 'si_area_box', $prefix, 1),
        $this->_getUploadedImageDetails($row["SBImage2"], 'si_area_box', $prefix, 2),
        $this->_getUploadedImageDetails($row["SBImage3"], 'si_area_box', $prefix, 3),
        $internalLink->getTemplateVars('si_area_box'),
        array(
        'si_area_box_action' => 'index.php',
        'si_area_box_hidden_fields' => $hiddenFields,
        'si_area_box_title1' => parseOutput($boxTitle1, 2),
        'si_area_box_title1_plain' => strip_tags($boxTitle1),
        'si_area_box_title2' => parseOutput($boxTitle2, 2),
        'si_area_box_title2_plain' => strip_tags($boxTitle2),
        'si_area_box_title3' => parseOutput($boxTitle3, 2),
        'si_area_box_title3_plain' => strip_tags($boxTitle3),
        'si_area_box_text1' => $boxText1,
        'si_area_box_text2' => $boxText2,
        'si_area_box_text3' => $boxText3,
        'si_area_box_extlink' => $boxExtlink,
        'si_area_box_image1' => $row['SBImage1'],
        'si_area_box_image2' => $row['SBImage2'],
        'si_area_box_image3' => $row['SBImage3'],
        'si_area_box_large_image1_available' => $this->_getImageZoomLink('si_area_box', $row['SBImage1']),
        'si_area_box_large_image2_available' => $this->_getImageZoomLink('si_area_box', $row['SBImage2']),
        'si_area_box_large_image3_available' => $this->_getImageZoomLink('si_area_box', $row['SBImage3']),
        'si_area_box_required_resolution1_label' => $this->_getImageSizeInfo($prefix, 1),
        'si_area_box_required_resolution2_label' => $this->_getImageSizeInfo($prefix, 2),
        'si_area_box_required_resolution3_label' => $this->_getImageSizeInfo($prefix, 3),
        'si_area_box_link' => $link,
        'si_area_box_link_id' => $linkID,
        'si_area_box_id' => $boxId,
        'si_area_box_position' => $position,
        'si_area_box_position_status' => $positionHelper->isLocked((int)$row['SBPosition']) ? 'locked' : 'unlocked',
        'si_area_box_delete_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'deleteBoxID' => $row['SBID'])),
        'si_area_box_move_up_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'moveBoxID' => $row['SBID'], 'moveBoxTo' => $moveUpPosition)),
        'si_area_box_move_down_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'moveBoxID' => $row['SBID'], 'moveBoxTo' => $moveDownPosition)),
        'si_area_box_noimage' => $boxNoImage,
        'si_area_box_activation_light'       => $activationLight,
        'si_area_box_activation_light_label' => $_LANG['global_activation_light_'.$activationLight.'_label'],
        'si_area_box_activation_light_link'  => $activationLightLink,
        'si_area_box_button_save_label' => sprintf($_LANG['si_area_box_button_save_label'], $position),
      ), $extendedData);

      $sitype = $this->_parent->getType() ? $this->_parent->getType() : '';
      $tplPath = "modules/ModuleSiteindexCompendium{$sitype}_Area_Box-$this->_areaBoxType.tpl";
      if (!is_file($this->tpl->get_root() . '/'. $tplPath)) {
        trigger_error(__CLASS__ . ": Missing module template '$tplPath'.", E_USER_ERROR);
      }
      $this->tpl->load_tpl('site_index_area_box', $tplPath);
      $this->tpl->parse_vars('site_index_area_box', $item);

      $positionLocked = $positionHelper->isLocked($item['si_area_box_position']);
      $this->tpl->parse_if('site_index_area_box', "area_box{$item['si_area_box_position']}_position_locked", $positionLocked);
      $this->tpl->parse_if('site_index_area_box', "area_box{$item['si_area_box_position']}_position_unlocked", !$positionLocked);

      $this->tpl->parse_if('site_index_area_box', "area_box{$item['si_area_box_position']}_image1", !$item['si_area_box_noimage']);
      $this->tpl->parse_if('site_index_area_box', "area_box{$item['si_area_box_position']}_noimage", $item['si_area_box_noimage']);

      // The delete_image1 link is shown if there is an image that is not inherited from the linked content item.
      $this->tpl->parse_if('site_index_area_box', "delete_area_box{$item['si_area_box_position']}_image1", $item['si_area_box_image1'], array(
        'si_area_box_delete_image1_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'box' => $item['si_area_box_id'], 'deleteBoxImage' => $item['si_area_box_id'], 'deleteBoxImageNumber' => 1)),
      ));
      $this->tpl->parse_if('site_index_area_box', "delete_area_box{$item['si_area_box_position']}_image2", $item['si_area_box_image2'], array(
        'si_area_box_delete_image2_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'box' => $item['si_area_box_id'], 'deleteBoxImage' => $item['si_area_box_id'], 'deleteBoxImageNumber' => 2)),
      ));
      $this->tpl->parse_if('site_index_area_box', "delete_area_box{$item['si_area_box_position']}_image3", $item['si_area_box_image3'], array(
        'si_area_box_delete_image3_link' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'box' => $item['si_area_box_id'], 'deleteBoxImage' => $item['si_area_box_id'], 'deleteBoxImageNumber' => 3)),
      ));

      $items[] = array('si_area_box_item' => $this->tpl->parsereturn('site_index_area_box'));
    }
    $this->db->free_result($result);

    $sitype = $this->_parent->getType() ? $this->_parent->getType() : '';
    $tplPath = "modules/ModuleSiteindexCompendium{$sitype}_Area_Box.tpl";
    if (!is_file($this->tpl->get_root() . '/'. $tplPath)) {
      trigger_error(__CLASS__ . ": Missing module template '$tplPath'.", E_USER_ERROR);
    }
    $this->tpl->load_tpl('site_index_area_box', $tplPath);
    $this->tpl->parse_if('site_index_area_box', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('si_area_box'));
    $this->tpl->parse_loop('site_index_area_box', $items, 'area_box_items');
    $itemsOutput = $this->tpl->parsereturn('site_index_area_box', array(
      'si_area_box_count' => $count,
      'si_area_box_active_position' => $activePosition,
      'si_area_box_dragdrop_link_js' => $this->_parseUrl('', array('site' => $this->site_id, 'area' => $this->_areaID, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes", 'moveBoxID' => '#moveID#', 'moveBoxTo' => '#moveTo#')),
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $itemsOutput,
      'count' => $count,
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
    return $this->_parent->getAreaBoxImagePrefix($this->_areaPosition, $position);
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function __construct($allSites, $site_id, Template $tpl, db $db,
                              $table_prefix, $action, $item_id, User $user,
                              Session $session, Navigation $navigation, $originalAction,
                              ModuleSiteindex $parent, $areaID,
                              $areaPosition, $areaBoxType)
  {
    parent::__construct($allSites, $site_id, $tpl, $db, $table_prefix, $action,
                        $item_id, $user, $session, $navigation, $originalAction);
    $this->_areaID = $areaID;
    $this->_areaPosition = $areaPosition;
    $this->_areaBoxType = $areaBoxType;
    $this->_parent = $parent;
  }

  // Public functions
  ///////////////////
  public function show_innercontent()
  {
    // Insert missing database entries
    $this->_checkDatabase();

    // Perform update/move/delete of a box if necessary.
    $this->_updateBox();
    $this->_moveBox();
    $this->_deleteBox();
    $this->_changeActivation();
    // Perform delete of box image if necessary.
    $this->_deleteBoxImage();

    return $this->_showBoxes();
  }

  /**
   * {@inheritDoc}
   */
  public function getConfigPrefix()
  {
    $prefixes = $this->_parent->getConfigPrefix();

    foreach ($prefixes as $key => $prefix) {
      $prefixes[$key] = str_replace('si', 'si_area_box', $prefix);
    }

    return $prefixes;
  }
}
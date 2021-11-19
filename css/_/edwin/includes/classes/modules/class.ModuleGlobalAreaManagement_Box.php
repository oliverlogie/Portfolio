<?php

use Core\Services\ExtendedData\Interfaces\InterfaceExtendable;

/**
 * class.ModuleGlobalAreaManagement_Box.php
 *
 * $LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2013 Q2E GmbH
 */

class ModuleGlobalAreaManagement_Box extends Module implements InterfaceExtendable
{
  /**
   * Defines the identifier for the box content.
   * @var string
   */
  const BOX_TYPE_CONTENT = 'content';

  /**
   * Defines the identifier for the timing settings box.
   * @var string
   */
  const BOX_TYPE_TIMING = 'timing';

  /**
   * @var string
   */
  public static $lastEdited = '';

  /**
   * @var int
   */
  public static $lastEditedId = 0;

  /**
   * @var string
   */
  protected $_prefix = 'ga_box';

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
   * @var ModuleGlobalAreaManagement
   */
  private $_parent;

  /**
   * @var int
   */
  private $_areaId = 0;

  /**
   * @var int
   */
  private $_areaPosition = 0;

  /**
   * @var string
   */
  private $_areaBoxType = '';

  /**
   * @var int
   */
  private $_invalidLinks = 0;

  /**
   * @param array $allSites
   * @param int $site_id
   * @param Template $tpl
   * @param db $db
   * @param string $table_prefix
   * @param string|array $action
   * @param int $item_id
   * @param User $user
   * @param Session $session
   * @param Navigation $navigation
   * @param string $originalAction
   * @param ModuleGlobalAreaManagement $parent
   * @param int $areaId
   * @param int $areaPosition
   * @param string $areaBoxType
   */
  public function __construct($allSites, $site_id, Template $tpl, db $db, $table_prefix, $action = '', $item_id = '',
                              User $user = null, Session $session = null, Navigation $navigation, $originalAction = '',
                              ModuleGlobalAreaManagement $parent, $areaId, $areaPosition, $areaBoxType)
  {
    $action = (is_array($action)) ? implode(';', $action) : $action;
    parent::__construct($allSites, $site_id, $tpl, $db, $table_prefix, $action, $item_id, $user, $session, $navigation, $originalAction = '');

    $this->_parent = $parent;
    $this->_areaId = $areaId;
    $this->_areaPosition = $areaPosition;
    $this->_areaBoxType = $areaBoxType;
  }

  /**
   * @see Module::show_innercontent()
   */
  public function show_innercontent()
  {
    $this->_post = new Input(Input::SOURCE_POST);
    $this->_get  = new Input(Input::SOURCE_GET);
    $this->_request  = new Input(Input::SOURCE_REQUEST);

    // Insert missing database entries
    $this->_checkDatabase();

    $this->_move();
    $this->_update();
    $this->_delete();
    $this->_deleteImage();
    $this->_changeActivation();

    return $this->_getContent();
  }

  /**
   * Gets a message if set.
   *
   * @return Message
   */
  public function getMessage()
  {
    return $this->_getMessage();
  }

  /**
   * Gets the number of invalid links.
   *
   * @return number
   */
  public function getInvalidLinks()
  {
    return $this->_invalidLinks;
  }

  /**
   * {@inheritDoc}
   */
  public function getConfigPrefix()
  {
    return array($this->_prefix);
  }

  /**
   * Moves box.
   */
  private function _move()
  {
    global $_LANG;

    if ($this->_get->readInt('area') != $this->_areaId) {
      return;
    }

    $moveID = $this->_get->readInt('moveBoxID');
    $moveTo = $this->_get->readInt('moveBoxTo');
    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_global_area_box",
                                         'GABID', 'GABPosition',
                                         'FK_GAID', $this->_areaId,
                                         'GABPositionLocked');
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ga_message_area_box_move_success']));
    }
  }

  /**
   * Updates box content.
   */
  private function _update()
  {
    global $_LANG;

    if (!$this->_post->exists('process_ga_area_box')
        && !$this->_post->exists("process_ga_area_box_date")
        || ($this->_post->readInt('area') != $this->_areaId)) {
      return;
    }

    if ($this->_post->exists("process_ga_area_box_date")) {
      $ID = $this->_post->readKey('process_ga_area_box_date');
      self::$lastEdited = self::BOX_TYPE_TIMING;
      self::$lastEditedId = $ID;
      $newDates = $this->_readTimingData();
      if (!empty($newDates)) {

        $sql = " UPDATE {$this->table_prefix}module_global_area_box "
             . " SET GABShowFromDateTime = ?, "
             . "     GABShowUntilDateTime = ? "
             . " WHERE GABID = $ID ";
        $this->db->q($sql, array($newDates['GABShowFromDateTime'] ?: null,
          $newDates['GABShowUntilDateTime'] ?: null));
        $this->setMessage(Message::createSuccess($_LANG['ga_message_area_box_update_success']));
      }
    }
    else {
      $ID = $this->_post->readKey('process_ga_area_box');
      $title = $this->_post->readString("ga_area_box{$ID}_title", Input::FILTER_CONTENT_TITLE);
      $text = $this->_post->readString("ga_area_box{$ID}_text", Input::FILTER_CONTENT_TEXT);
      $noText = (int)$this->_post->readBool("checkbox_hide_text_{$ID}");
      $noImage = (int)$this->_post->readBool("ga_area_box{$ID}_noimage");
      list($link, $linkID) = $this->_post->readContentItemLink("ga_area_box{$ID}_link");
      $extlink = $this->_post->readString("ga_area_box{$ID}_extlink", Input::FILTER_PLAIN);
      self::$lastEdited = self::BOX_TYPE_CONTENT;
      self::$lastEditedId = $ID;

      if (!Validation::isEmpty($extlink) && !Validation::isUrl($extlink)) {
        $this->setMessage(Message::createFailure($_LANG['ga_message_invalid_extlink']));
        return;
      }

      // Read existing box infos.
      $sql = 'SELECT GABImage, GABPosition, FK_CIID '
           . "FROM {$this->table_prefix}module_global_area_box "
           . "WHERE GABID = $ID ";
      $row = $this->db->GetRow($sql);
      $image = $row['GABImage'];
      $oldLinkID = (int)$row['FK_CIID'];
      $position = (int)$row['GABPosition'];

      // Perform the image uploads.
      $existingImage = $row['GABImage'];
      $image = $existingImage;
      if (!$noImage && isset($_FILES["ga_area_box{$ID}_image"])) {
        $components = array($this->site_id, $this->_areaId, $ID);
        $prefix = $this->_getImagePrefix($position);
        $uploadedImage = $this->_storeImage($_FILES["ga_area_box{$ID}_image"], $existingImage, $prefix, 0, $components, false, false, 'ga_area_box', true, false);
        if ($uploadedImage) {
          $image = $uploadedImage;
        }
      }

      $sql = "UPDATE {$this->table_prefix}module_global_area_box "
           . "SET GABTitle = '{$this->db->escape($title)}', "
           . "    GABText = '{$this->db->escape($text)}', "
           . "    GABImage = '$image', "
           . "    GABNoImage = $noImage, "
           . "    GABNoText  = $noText, "
           . "    FK_CIID = $linkID, "
           . "    GABExtlink = '{$this->db->escape($extlink)}' "
           . "WHERE GABID = $ID ";
      $result = $this->db->query($sql);

      if (ConfigHelper::get('m_extended_data')) {
        $this->_extendedDataService()->updateExtendedData($this, $ID);
      }

      $this->setMessage(Message::createSuccess($_LANG['ga_message_area_box_update_success']));
    }
  }

  /**
   * Deletes box content.
   */
  private function _delete()
  {
    global $_LANG;

    if ($this->_get->readInt('area') != $this->_areaId) {
      return;
    }

    $ID = $this->_get->readInt('deleteBoxID');
    if (!$ID) {
      return;
    }

    // Determine the existing image file.
    $sql = 'SELECT GABImage '
         . "FROM {$this->table_prefix}module_global_area_box "
         . "WHERE GABID = $ID ";
    $image = $this->db->GetOne($sql);

    // Update database entry before actually deleting the image file.
    $sql = "UPDATE {$this->table_prefix}module_global_area_box "
         . "SET GABTitle = '', "
         . "    GABText = '', "
         . "    GABImage = '', "
         . '    GABNoImage = 0, '
         . '    GABNoText = 0, '
         . '    FK_CIID = 0, '
         . "    GABExtlink = '' "
         . "WHERE GABID = $ID ";
    $result = $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    if (ConfigHelper::get('m_extended_data')) {
      $this->_extendedDataService()->deleteExtendedData($this, $ID);
      $this->_checkDatabase(); // recreate deleted extended data datasets manually
    }

    $this->setMessage(Message::createSuccess($_LANG['ga_message_area_box_delete_success']));
  }

  /**
   * Deletes box image.
   */
  private function _deleteImage()
  {
    global $_LANG;

    if ($this->_get->readInt('area') != $this->_areaId) {
      return;
    }

    $ID = $this->_get->readInt('deleteBoxImage');
    if (!$ID) {
      return;
    }

    // Determine the existing image file.
    $sql = 'SELECT GABImage '
         . "FROM {$this->table_prefix}module_global_area_box "
         . "WHERE GABID = $ID ";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = "UPDATE {$this->table_prefix}module_global_area_box "
         . "SET GABImage = '' "
         . "WHERE GABID = $ID ";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['ga_message_area_box_deleteimage_success']));
  }

  /**
   * Changes the activation status.
   */
  private function _changeActivation()
  {
    global $_LANG;

    $id = $this->_get->readInt('changeActivationBoxID');
    $type = $this->_get->readString('changeActivationBoxTo', Input::FILTER_NONE);

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

    $sql = " UPDATE {$this->table_prefix}module_global_area_box "
         . " SET GABDisabled = $to "
         . " WHERE GABID = $id "
         . " AND FK_GAID = $this->_areaId ";
    $this->db->query($sql);

    $msg = $_LANG['ga_area_box_message_activation_'.$type];
    $this->setMessage(Message::createSuccess($msg));
  }

  /**
   * Gets content of all boxes of assigned area.
   * @return string
   */
  private function _getContent()
  {
    global $_LANG;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_global_area_box",
                                         'GABID', 'GABPosition',
                                         'FK_GAID', $this->_areaId,
                                         'GABPositionLocked');
    $items = array();
    $activePosition = 0;
    $invalidLinks = 0;

    $sql = ' SELECT GABID, GABTitle, GABText, GABNoImage, GABImage, GABPosition, '
         . '        GABNoText, GABDisabled, GADisabled, GABShowFromDateTime, '
         . '        FK_GAID, GABShowUntilDateTime, GABExtlink, '
         . '        mgab.FK_CIID, CIID, CIIdentifier, ci.FK_SID, ca.CImage '
         . " FROM {$this->table_prefix}module_global_area_box mgab "
         . " JOIN {$this->table_prefix}module_global_area mga "
         . "      ON GAID = FK_GAID"
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . '      ON mgab.FK_CIID = ci.CIID '
         . " LEFT JOIN {$this->table_prefix}contentabstract ca "
         . '      ON ci.CIID = ca.FK_CIID '
         . " WHERE mgab.FK_GAID = $this->_areaId "
         . ' ORDER BY GABPosition ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {

      $box = new GlobalAreaBox($this->db, $this->table_prefix, $this->_prefix, $row);

      $moveUpPosition = $positionHelper->getMoveUpPosition($box->position);
      $moveDownPosition = $positionHelper->getMoveDownPosition($box->position);
      $timingStartDate = DateHandler::getValidDateTime($box->showFromDateTime, 'd.m.Y');
      $timingEndDate = DateHandler::getValidDateTime($box->showUntilDateTime, 'd.m.Y');
      $timingStartTime = DateHandler::getValidDateTime($box->showFromDateTime, 'H:i');
      $timingEndTime = DateHandler::getValidDateTime($box->showUntilDateTime, 'H:i');
      $timingActive = (($timingStartDate && $timingStartTime) || ($timingEndDate && $timingEndTime)) ? true : false;

      // Determine if current box is active.
      if ($this->_request->readInt('box') == $box->id) {
        $activePosition = $box->position;
      }

      // Detect invalid and invisible links.
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
      if ($internalLink->isInvalid()) {
        $invalidLinks++;
      }

      $position = (int)$row['GABPosition'];
      $prefix = $this->_getImagePrefix($position);
      $activationLightLink = $this->_getActivationLink($box);
      $activationLight = $this->_getActivationLight($box);

      $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                    . '<input type="hidden" name="action" value="mod_globalareamgmt" />'
                    . '<input type="hidden" name="action2" value="edit" />'
                    . '<input type="hidden" id="area'.$this->_areaId.'_box'.$row['GABID'].'" name="area" value="0" />'
                    . '<input type="hidden" id="box'.$row['GABID'].'" name="box" value="0" />'
                    . '<input type="hidden" id="box'.$row['GABID'].'_scrollToAnchor" name="scrollToAnchor" value="" />';

      $image = '';
      if ($row["GABImage"]) {
        $image = $row["GABImage"];
      }
      else if ($row["CImage"]) {
        $image = $row["CImage"];
      }
      $timingMsg = ($box->disabled) ? Message::createFailure($_LANG['ga_area_box_message_timing_has_no_effect']) : '';
      $items[] = array_merge(
        $this->_getUploadedImageDetails($image, 'ga_area_box', $prefix),
        $internalLink->getTemplateVars('ga_area_box'),
        $this->_extendedDataService()->getContentExtensionData($this, $row['GABID']),
        array(
        'ga_area_box_action' => 'index.php',
        'ga_area_box_hidden_fields' => $hiddenFields,
        'ga_area_box_title' => $row["GABTitle"],
        'ga_area_box_title_plain' => strip_tags($row["GABTitle"]),
        'ga_area_box_text' => $row["GABText"],
        'ga_area_box_text_visible_css' => $row["GABNoText"] ? 'display:none;' : '',
        'ga_area_box_checked' => $row["GABNoText"] ? 'checked="checked"' : '',
        'ga_area_box_image' => $row['GABImage'],
        'ga_area_box_large_image_available' => $this->_getImageZoomLink('ga_area_box', $row['GABImage']),
        'ga_area_box_required_resolution_label' => $this->_getImageSizeInfo($prefix, 0),
        'ga_area_box_extlink' => $row['GABExtlink'],
        'ga_area_box_id' => $row['GABID'],
        'ga_area_box_position' => $position,
        'ga_area_box_position_status' => $positionHelper->isLocked((int)$row['GABPosition']) ? 'locked' : 'unlocked',
        'ga_area_box_delete_link' => $this->_parent->parseUrl('', array('area' => $this->_areaId, 'deleteBoxID' => $row['GABID'], 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes")),
        'ga_area_box_move_up_link' => $this->_parent->parseUrl('', array('area' => $this->_areaId, 'moveBoxID' => $row['GABID'], 'moveBoxTo' => $moveUpPosition, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes")),
        'ga_area_box_move_down_link' => $this->_parent->parseUrl('', array('area' => $this->_areaId, 'moveBoxID' => $row['GABID'], 'moveBoxTo' => $moveDownPosition, 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes")),
        'ga_area_box_noimage' => $row['GABNoImage'],
        'ga_area_box_activation_light'       => $activationLight,
        'ga_area_box_activation_light_label' => $_LANG['global_activation_light_'.$activationLight.'_label'],
        'ga_area_box_activation_light_link'  => $activationLightLink,
        'ga_area_box_date_from' => $timingStartDate,
        'ga_area_box_time_from' => $timingStartTime,
        'ga_area_box_date_until' => $timingEndDate,
        'ga_area_box_time_until' => $timingEndTime,
        'ga_area_box_button_save_label' => sprintf($_LANG['ga_area_box_button_save_label'], $position),
        'timing_message' => $timingMsg,
        'timing_active' => $timingActive,
        'disabled' => $box->disabled,
      ));
    }
    $this->db->free_result($result);

    $timingAvailable = $this->_configHelper->get('ga_area_box_timing_activated');
    $tplName = 'ga_area_box';
    $tplPath = "modules/ModuleGlobalAreaManagement_box_{$this->_areaBoxType}.tpl";
    $this->tpl->load_tpl($tplName, $tplPath);
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ga_area_box'));
    $this->tpl->parse_loop($tplName, $items, 'area_box_items');
    foreach ($items as $item) {
      $positionLocked = $positionHelper->isLocked($item['ga_area_box_position']);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_position_locked", $positionLocked);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_position_unlocked", !$positionLocked);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_timing_message", $item['timing_message'], array(
        'ga_area_box_timing_message' => ($item['timing_message']) ? $item['timing_message']->getText() : '',
      ));
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_image", !$item['ga_area_box_noimage']);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_noimage", $item['ga_area_box_noimage']);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_timebox", $timingAvailable);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_timing_type_activated", $timingAvailable);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_timing_active", $item['timing_active'] && !$item['disabled']);
      $this->tpl->parse_if($tplName, "area_box{$item['ga_area_box_position']}_timing_not_active", !$item['timing_active'] || $item['disabled']);

      // The delete_image link is shown if there is an image that is not inherited from the linked content item.
      $this->tpl->parse_if($tplName, "delete_area_box{$item['ga_area_box_position']}_image", $item['ga_area_box_image'], array(
        'ga_area_box_delete_image_link' => $this->_parent->parseUrl('', array('area' => $this->_areaId, 'box' => $item['ga_area_box_id'], 'deleteBoxImage' => $item['ga_area_box_id'], 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes")),
      ));
    }
    $itemsOutput = $this->tpl->parsereturn($tplName, array(
      'ga_area_box_count' => $count,
      'ga_area_box_active_position' => $activePosition,
      'ga_area_box_dragdrop_link_js' => $this->_parent->parseUrl('', array('area' => $this->_areaId, 'moveBoxID' => '#moveID#', 'moveBoxTo' => '#moveTo#', 'scrollToAnchor' => "a_area{$this->_areaPosition}_boxes")),
    ));

    $this->_invalidLinks = $invalidLinks;

    return $itemsOutput;
  }

  /**
   * Returns the activation link for a box.
   *
   * @param GlobalAreaBox $box
   *        The box to get the activation link.
   */
  private function _getActivationLink(GlobalAreaBox $box)
  {
    $activationLightLink = $this->_parent->parseUrl('', array(
      'area' => $this->_areaId,
      'changeActivationBoxID' => $box->id,
      'changeActivationBoxTo' => ''
    ));
    if ($box->disabled) {
      $activationLightLink .= ContentBase::ACTIVATION_ENABLED;
    }
    else {
      $activationLightLink .= ContentBase::ACTIVATION_DISABLED;;
    }
    $activationLightLink .= "&amp;scrollToAnchor=a_area{$this->_areaPosition}_boxes";

    return $activationLightLink;
  }

  /**
   * Returns the activation light for a box.
   *
   * @param GlobalAreaBox $box
   *        The box to get the activation light.
   * @return string
   *         The activation light string i.e. yellow, green, clock, ...
   */
  private function _getActivationLight(GlobalAreaBox $box)
  {
    if ($box->getArea()->disabled) {
      $activationLight = ActivationLightInterface::YELLOW;
    }
    else if ($box->disabled) {
      $activationLight = ActivationLightInterface::RED;
    }
    else {
      $activationLight = ActivationLightInterface::GREEN;
    }

    // the box is not enabled, so return value as timing does not
    // matter for disabled items
    if ($activationLight != ActivationLightInterface::GREEN)
      return $activationLight;

    // the box is enabled so we have too take a look at timing
    // settings in order to retrieve the correct activation light
    if ($box->isTimingActive()) {
      if ($box->isVisible()) {
        $activationLight = ActivationClockInterface::GREEN;
      }
      else {
        $activationLight = ActivationClockInterface::RED;
      }
    }

    return $activationLight;
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
    $position = (int)$position;

    $str = $this->_parent->getPrefix();
    $prefix[] = "{$str}_site{$this->site_id}_area{$this->_areaPosition}_box$position";
    $prefix[] = "{$str}_site{$this->site_id}_area{$this->_areaPosition}_box";
    $prefix[] = "{$str}_site{$this->site_id}_area_box";
    $prefix[] = "{$str}_area{$this->_areaPosition}_box$position";
    $prefix[] = "{$str}_area{$this->_areaPosition}_box";
    $prefix[] = "{$str}_area_box";

    return $prefix;
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  /**
   * Ensures that all necessary database entries exist.
   */
  private function _checkDatabase()
  {
    // Determine the amount of currently existing boxes.
    $sql = 'SELECT COUNT(GABID) '
         . "FROM {$this->table_prefix}module_global_area_box "
         . "WHERE FK_GAID = $this->_areaId ";
    $existingBoxes = $this->db->GetOne($sql);

    // "ga_number_of_boxes" specifies the amount of boxes in each area, to
    // determine the amount of boxes for the current area we use the area
    // position - 1 as the array index.
    $numberOfBoxes = ConfigHelper::get('number_of_boxes', $this->_parent->getPrefix());
    if (!isset($numberOfBoxes[$this->site_id])) {
      return;
    }
    $numberOfBoxes = (array)$numberOfBoxes[$this->site_id];
    if (!isset($numberOfBoxes[$this->_areaPosition - 1])) {
      return;
    }
    $numberOfBoxes = $numberOfBoxes[$this->_areaPosition - 1];

    $model = $this->_getModel();
    // Create missing boxes.
    for ($i = $existingBoxes + 1; $i <= $numberOfBoxes; $i++) {
      $model->reset();
      $model->position = $i;
      $model->parent = $this->_areaId;
      $model->noImage = 0;
      $model->noText = 0;
      $model->link = 0;
      $model->create();

    }

    if (ConfigHelper::get('m_extended_data')) {
      $sql = " SELECT GABID "
           . " FROM {$this->table_prefix}module_global_area_box "
           . " WHERE FK_GAID = $this->_areaId ";
      $ids = $this->db->GetCol($sql);

      $this->_extendedDataService()->createExtendedData($this, $ids);
    }
  }

  /**
   * @return GlobalAreaBox
   */
  private function _getModel()
  {
    if ($this->_model === null) {
      $this->_model = new GlobalAreaBox($this->db, $this->table_prefix, $this->_prefix);
    }

    return $this->_model;
  }

  /**
   * Reads all dates that were input by the user and returns them.
   *
   * @return array
   *        Contains dates that were entered by the user. The array index
   *        is the name of the database column, the array value is the date.
   */
  private function _readTimingData()
  {
    global $_LANG;

    $prefix = 'ga_area_box';
    if (!$this->_post->exists("process_{$prefix}_date")) {
      return array();
    }

    $formNumber = $this->_post->readKey("process_{$prefix}_date");

    $postDateFrom = $this->_post->readString($prefix . "{$formNumber}_date_from", Input::FILTER_PLAIN);
    $postTimeFrom = $this->_post->readString($prefix . "{$formNumber}_time_from", Input::FILTER_PLAIN);
    $postDateUntil = $this->_post->readString($prefix . "{$formNumber}_date_until", Input::FILTER_PLAIN);
    $postTimeUntil = $this->_post->readString($prefix . "{$formNumber}_time_until", Input::FILTER_PLAIN);

    // Create date strings and time strings and combine afterwards
    $dateFrom = DateHandler::getValidDate($postDateFrom, 'Y-m-d');
    $timeFrom = DateHandler::getValidDate($postTimeFrom, 'H:i:s');
    $dateUntil = DateHandler::getValidDate($postDateUntil, 'Y-m-d');
    $timeUntil = DateHandler::getValidDate($postTimeUntil, 'H:i:s');

    $datetimeFrom = DateHandler::combine($dateFrom, $timeFrom);
    $datetimeUntil = DateHandler::combine($dateUntil, $timeUntil);

    if (!DateHandler::isValidDate($postDateFrom) && $postDateFrom != ''
        || !DateHandler::isValidDate($postTimeFrom) && $postTimeFrom != ''
        || !DateHandler::isValidDate($postDateUntil) && $postDateUntil != ''
        || !DateHandler::isValidDate($postTimeUntil) && $postTimeUntil != '')
    {
      $this->setMessage(Message::createFailure($_LANG['global_message_invalid_date']));
      return array();
    }
    if (DateHandler::isValidDate($datetimeFrom) && !DateHandler::isFutureDateTime($datetimeFrom)
        || DateHandler::isValidDate($datetimeUntil) && !DateHandler::isFutureDateTime($datetimeUntil))
    {
      $this->setMessage(Message::createFailure($_LANG['global_message_past_date']));
      return array();
    }
    if (strtotime($datetimeFrom) > strtotime($datetimeUntil)
        && DateHandler::isValidDate($datetimeFrom) && DateHandler::isValidDate($datetimeUntil))
    {
      $this->setMessage(Message::createFailure($_LANG['global_message_wrong_date']));
      return array();
    }

    $dates['GABShowFromDateTime'] = $datetimeFrom;
    $dates['GABShowUntilDateTime'] = $datetimeUntil;
    return $dates;
  }
}

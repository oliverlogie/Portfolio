<?php

/**
 * Compendium areas contenttype class handling the area boxes
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemCA_Area_Boxes extends ContentItem
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

  protected $_configPrefix = 'ca';
  protected $_contentPrefix = 'ca_area_box';
  protected $_columnPrefix = 'CAAB';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
    'Link' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'CA'; // "_Area_Boxes" is added in ContenItemCA_Area_Boxes::_construct()

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
   * The parent contentitem ( ContentItemCA )
   *
   * @var ContentItemCA
   */
  private $_parent = null;

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function __construct($site_id, $page_id, Template $tpl, db $db,
                              $table_prefix, $action, $page_path, User $user = null,
                              Session $session, Navigation $navigiation, ContentItemCA $parent,
                              $areaID, $areaPosition, $areaBoxType)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigiation);

    $this->_areaID = $areaID;
    $this->_areaPosition = $areaPosition;
    $this->_areaBoxType = $areaBoxType;
    $this->_parent = $parent;
    $this->_configPrefix .= '_area_box';
    $this->_templateSuffix .= '_Area_Boxes';
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CAAID = {$id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      parent::duplicateContent($pageId, $newParentId, "FK_CAAID", $id, "{$this->_columnPrefix}ID");
    }
  }

  public function edit_content()
  {
    $settings = array(
      'parentField' => 'FK_CAAID',
      'parentId'    => $this->_areaID,
      'idParam'     => 'changeActivationBoxID',
      'toParam'     => 'changeActivationBoxTo',
    );

    $this->_addElement();
    $this->_changeActivation($settings);
    $this->_deleteBox();
    $this->_deleteBoxImage();
    $this->_moveBox();
    $this->_updateBox();
  }

  public function getAreaId()
  {
    return $this->_areaID;
  }

  public function get_content($params = array())
  {
    global $_LANG;

    $this->_checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ca_area_box ",
                                         'CAABID', 'CAABPosition',
                                         'FK_CAAID', $this->_areaID);
    $items = array();
    $activePosition = 0;
    $invalidLinks = 0;

    $sql = ' SELECT CAABID, CAABTitle, CAABText, CAABNoImage, CAABImage, '
         . '        CAABPosition, CAABLink, CAABExtlink, CAABDisabled, CAADisabled, '
         . '        c_link.CIID AS Link_CIID, c_link.CIIdentifier AS Link_CIIdentifier, '
         . '        c_link.FK_SID AS Link_FK_SID, ca.CImage '
         . " FROM {$this->table_prefix}contentitem_ca_area_box cicaab "
         . " JOIN {$this->table_prefix}contentitem_ca_area cicaa "
         . '      ON FK_CAAID = CAAID '
         . " LEFT JOIN {$this->table_prefix}contentitem c_link "
         . '      ON CAABLink = c_link.CIID '
         . " LEFT JOIN {$this->table_prefix}contentabstract ca "
         . '      ON c_link.CIID = ca.FK_CIID '
         . " WHERE cicaab.FK_CAAID = $this->_areaID "
         . ' ORDER BY CAABPosition ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result))
    {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['CAABPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['CAABPosition']);

      // Determine if current box is active.
      $request = new Input(Input::SOURCE_REQUEST);
      if ($request->readInt('box') == $row['CAABID']) {
        $activePosition = $row['CAABPosition'];
      }

      $internalLink = $this->getInternalLinkHelper($row['CAABLink']);
      if ($internalLink->isInvalid()) {
        $invalidLinks++;
      }

      $position = (int)$row['CAABPosition'];
      $imagePrefix = $this->_getPrefixImageConfig($position);

      $settings = array(
        'parentDisabledField' => 'CAADisabled',
        'urlParams'           => "area={$this->_areaID}&amp;scrollToAnchor=a_area{$this->_areaPosition}_boxes",
        'idParam'             => 'changeActivationBoxID',
        'toParam'             => 'changeActivationBoxTo',
      );

      $imageSrc = $row["CAABImage"] ? $row["CAABImage"] : ($row["CImage"] ? $row["CImage"] : "");
      $items[] = array_merge($this->_getUploadedImageDetails($imageSrc, $this->_contentPrefix, $imagePrefix), $this->_getActivationData($row, $settings),
        $internalLink->getTemplateVars('ca_area_box'), array(
        'ca_area_box_title' => $row["CAABTitle"],
        'ca_area_box_title_plain' => strip_tags($row["CAABTitle"]),
        'ca_area_box_text' => $row["CAABText"],
        'ca_area_box_image' => $row['CAABImage'],
        'ca_area_box_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['CAABImage']),
        'ca_area_box_required_resolution_label' => $this->_getImageSizeInfo($imagePrefix, 0),
        'ca_area_box_extlink' => parseOutput($row['CAABExtlink'], 0),
        'ca_area_box_id' => $row['CAABID'],
        'ca_area_box_position' => $position,
        'ca_area_box_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area=$this->_areaID&amp;deleteBoxID={$row['CAABID']}&amp;scrollToAnchor=a_area{$this->_areaPosition}_boxes",
        'ca_area_box_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area=$this->_areaID&amp;moveBoxID={$row['CAABID']}&amp;moveBoxTo=$moveUpPosition&amp;scrollToAnchor=a_area{$this->_areaPosition}_boxes",
        'ca_area_box_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area=$this->_areaID&amp;moveBoxID={$row['CAABID']}&amp;moveBoxTo=$moveDownPosition&amp;scrollToAnchor=a_area{$this->_areaPosition}_boxes",
        'ca_area_box_noimage' => $row['CAABNoImage'],
        'ca_area_box_button_save_label' => sprintf($_LANG['ca_area_box_button_save_label'], $position),
      ), $this->_getContentExtensionData($row['CAABID']));
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ca_area_box'));

    $numberOfElements = $this->_parent->getConfig('number_of_boxes');
    if (!isset($numberOfElements[$this->_areaPosition - 1])) {
      return;
    }
    $numberOfElements = $numberOfElements[$this->_areaPosition - 1];
    $subMsg = null;
    if ($count >= $numberOfElements) {
      $subMsg = Message::createFailure($_LANG['ca_message_area_box_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('ca_area_box') : array());
    $this->tpl->parse_if($tplName, 'ca_area_box_add_subelement', $count < $numberOfElements);
    $this->tpl->parse_loop($tplName, $items, 'area_box_items');
    foreach ($items as $item)
    {
      $this->tpl->parse_if($tplName, "area_box{$item['ca_area_box_position']}_image", !$item['ca_area_box_noimage']);
      $this->tpl->parse_if($tplName, "area_box{$item['ca_area_box_position']}_noimage", $item['ca_area_box_noimage']);
      // The delete_image link is shown if there is an image that is not inherited from the linked content item.
      $this->tpl->parse_if($tplName, "delete_area_box{$item['ca_area_box_position']}_image", $item['ca_area_box_image'], array(
        'ca_area_box_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$this->_areaID}&amp;box={$item['ca_area_box_id']}&amp;deleteBoxImage={$item['ca_area_box_id']}&amp;scrollToAnchor=a_area{$this->_areaPosition}_boxes",
      ));
      $this->_parseTemplateCommonParts($tplName, $item['ca_area_box_id']);
    }
    $itemsOutput = $this->tpl->parsereturn($tplName, array(
      'ca_area_box_count' => $count,
      'ca_area_box_active_position' => $activePosition,
      'ca_area_box_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&area=$this->_areaID&moveBoxID=#moveID#&moveBoxTo=#moveTo#&scrollToAnchor=a_area{$this->_areaPosition}_boxes",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $itemsOutput,
      'count' => $count,
      'invalidLinks' => $invalidLinks,
    );
  }

  public function updateStructureLinkSubContentImages($subID, $fields = array())
  {
    // Retrieve box at position equal to box $sourceID
    $sql = ' SELECT CAABID ' . ($fields ? (', ' . implode(',', $fields)) : '')
         . " FROM {$this->table_prefix}contentitem_ca_area_box "
         . " WHERE CAABPosition IN ( "
         . '         SELECT CAABPosition '
         . "         FROM {$this->table_prefix}contentitem_ca_area_box "
         . "         WHERE CAABID = $subID "
         . '       ) '
         . "   AND FK_CAAID = $this->_areaID ";
    $row = $this->db->GetRow($sql);

    $count = 0;
    // Check for invalid content of target item.
    foreach ($fields as $field) {
      if (!$row[$field]) {
        $count++;
      }
    }

    // none of the required fields contains valid content
    if ($count === count($fields)) {
      return false;
    }

    // box id
    $ID = $row['CAABID'];
    $components = array($this->site_id, $this->page_id, $ID);
    // Retrieve and store new images (only linked images)
    $input['Image'] = $this->_readContentElementsImages($components, $subID, $ID, 'CAABID', true);

    if (empty($input['Image'])) {
      return false;
    }

    // Update the database.
    $sql = " UPDATE {$this->table_prefix}contentitem_ca_area_box "
         . " SET CAABImage = '" . $input['Image']['CAABImage'] . "' "
         . " WHERE FK_CAAID = {$this->_areaID} "
         . "   AND CAABID = $ID ";
    $result = $this->db->query($sql);

    return true;
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function _checkDatabase()
  {
    // Determine the amount of currently existing boxes.
    $existingElements = $this->_getElementCount();

    // "ca_number_of_boxes" specifies the amount of boxes in each area, to
    // determine the amount of boxes for the current area we use the area
    // position - 1 as the array index.
    $numberOfBoxes = $this->_parent->getConfig('number_of_boxes');
    if (!is_array($numberOfBoxes)) {
      return;
    }
    if (!isset($numberOfBoxes[$this->_areaPosition - 1])) {
      return;
    }
    $numberOfBoxes = $numberOfBoxes[$this->_areaPosition - 1];

    // Create missing boxes.
    if (!$existingElements && $numberOfBoxes) {
      $sql = " INSERT INTO {$this->table_prefix}contentitem_ca_area_box "
           . " (CAABPosition, FK_CAAID) VALUES "
           . " (1, $this->_areaID) ";
      $result = $this->db->query($sql);
    }

    $sql = " SELECT CAABID "
         . " FROM {$this->table_prefix}contentitem_ca_area_box "
         . " WHERE FK_CAAID = {$this->getAreaId()} ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_createExtendedData($ids);
    }
  }

  protected function _getTemplatePath($string = '')
  {
    $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '-'
             . $this->_areaBoxType . '.tpl';

    if (!is_file($this->tpl->get_root() . '/' . $tplPath)) {
      $tplPath = parent::_getTemplatePath();
    }

    return $tplPath;
  }

  protected function _processedValues()
  {
    return array( 'changeActivationBoxID',
                  'deleteBoxID',
                  'deleteBoxImage',
                  'moveBoxID',
                  'process_ca_area_box',
                  'process_ca_area_box_subelement',);
  }

  /**
   * May adds a new element, if corresponding process button was clicked
   */
  private function _addElement()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_ca_area_box_subelement');
    if (!$ID) {
      return;
    }
    if ($post->readInt('area') != $this->_areaID) {
      return;
    }

    // Determine the amount of currently existing elements.
    $existingElements = $this->_getElementCount();
    $numberOfElements = $this->_parent->getConfig('number_of_boxes');
    if (!isset($numberOfElements[$this->_areaPosition - 1])) {
      return;
    }
    $numberOfElements = $numberOfElements[$this->_areaPosition - 1];

    if ($existingElements < $numberOfElements) {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_ca_area_box "
           . '(CAABPosition, FK_CAAID) '
           . "VALUES($pos, $this->_areaID) ";
      $result = $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG["ca_message_area_box_create_success"]));
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
    $sql = 'SELECT CAABImage '
         . "FROM {$this->table_prefix}contentitem_ca_area_box "
         . "WHERE CAABID = $ID ";
    $image = $this->db->GetOne($sql);

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ca_area_box",
                                          'CAABID', 'CAABPosition',
                                          'FK_CAAID', $this->_areaID);
    // move element to highest position to resort all other elements
    $positionHelper->move($ID, $positionHelper->getHighestPosition());

    // Now delete database entry before actually deleting the image file.
    $sql = "DELETE FROM {$this->table_prefix}contentitem_ca_area_box "
         . "WHERE CAABID = $ID ";
    $result = $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->_deleteExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['ca_message_area_box_delete_success']));
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

    // Determine the existing image file.
    $sql = ' SELECT CAABImage '
         . " FROM {$this->table_prefix}contentitem_ca_area_box "
         . " WHERE CAABID = $ID ";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = " UPDATE {$this->table_prefix}contentitem_ca_area_box "
         . " SET CAABImage = '' "
         . " WHERE CAABID = $ID ";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['ca_message_area_box_deleteimage_success']));
  }

  /**
   * Determine the amount of currently existing area boxes.
   * @return int the number of area boxes
   */
  private function _getElementCount() {
    $sql = ' SELECT COUNT(CAABID) '
         . " FROM {$this->table_prefix}contentitem_ca_area_box "
         . " WHERE FK_CAAID = $this->_areaID ";

    return (int) $this->db->GetOne($sql);
  }

  /**
   * Returns an array containing the image configuration prefix strings
   *
   * E.g. array(
   *        "ca_area1_box$position",
   *        "ca_area1_box",
   *        "ca_area_box"
   *      )
   * for area 1 and box at $position.
   *
   *
   * @return array
   */
  private function _getPrefixImageConfig($position)
  {
    $prefix = array();

    foreach ($this->_parent->getConfigPrefix() as $str) {
      $prefix[] = $str . "_area{$this->_areaPosition}_box$position";
      $prefix[] = $str . "_area{$this->_areaPosition}_box";
      $prefix[] = $str . "_area_box";
    }

    return array_unique($prefix);
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

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ca_area_box",
                                         'CAABID', 'CAABPosition',
                                         'FK_CAAID', $this->_areaID);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ca_message_area_box_move_success']));
    }
  }

  /**
   * Updates boxes if the POST parameter 'process_ca_area_box' or 'process_all' is set.
   */
  private function _updateBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_ca_area_box') && !$post->exists('process_all')) {
      return;
    }
    if (!$post->exists('process_all') && $post->readInt('area') != $this->_areaID) {
      return;
    }

    if ($post->exists('process_all')) {
      // If the POST parameter process_all is set we have to update all boxes
      // in this area.
      $sql = ' SELECT CAABID '
           . " FROM {$this->table_prefix}contentitem_ca_area_box "
           . " WHERE FK_CAAID = $this->_areaID ";
      $IDs = $this->db->GetCol($sql);
    } else {
      // If only process_ca_area_box is set we have to update only one box.
      $IDs = (array)$post->readKey('process_ca_area_box');
    }

    foreach ($IDs as $ID)
    {
      // Read title, text and image content elements.
      $input['Title'] = $this->_readContentElementsTitles($ID);
      $input['Text'] = $this->_readContentElementsTexts($ID);
      $input['Link'] = $this->_readContentElementsLinks($ID);
      $input['Extlink'] = $post->readString("ca_area_box{$ID}_extlink", Input::FILTER_PLAIN);

      $components = array($this->site_id, $this->page_id, $this->_areaID, $ID);
      $noImage = (int)$post->readBool("ca_area_box{$ID}_noimage");
      // Perform the image uploads.
      // Do not use the ContentItem::_readContentElementsImages() method as area
      // box images have special configuration options (considering area and box
      // position)
      $sql = ' SELECT CAABImage, CAABPosition '
           . " FROM {$this->table_prefix}contentitem_ca_area_box "
           . " WHERE CAABID = $ID ";
      $row = $this->db->GetRow($sql);
      $existingImage = $row['CAABImage'];
      $position = $row['CAABPosition'];

      $prefix = $this->_getPrefixImageConfig($position);
      $image = $existingImage;
      if (isset($_FILES["ca_area_box{$ID}_image"]) && $uploadedImage = $this->_storeImage($_FILES["ca_area_box{$ID}_image"], $existingImage, $prefix, 0, $components)) {
        $image = $uploadedImage;
        $noImage = 0;
      }

      // update biglink images of linked content items
      if ($this->_structureLinksAvailable && $this->_structureLinks)
      {
        $sql = ' SELECT FK_SID, CIID, CAAID, CAAPosition, CAABoxType '
             . " FROM {$this->table_prefix}contentitem_ca_area_box caab "
             . " JOIN {$this->table_prefix}contentitem_ca_area caa "
             . "      ON FK_CAAID = CAAID AND CAAPosition = $this->_areaPosition "
             . " JOIN {$this->table_prefix}contentitem ci "
             . '      ON caa.FK_CIID = ci.CIID '
             . ' WHERE CIID IN (' . implode(',', $this->_structureLinks) . ') ';
        $result = $this->db->query($sql);

        while ($row = $this->db->fetch_row($result))
        {
          $biglinks = new ContentItemCA_Area_Boxes($row['FK_SID'], $row['CIID'],
                          $this->tpl, $this->db, $this->table_prefix, '', '',
                          $this->_user, $this->session, $this->_navigation, $this->_parent,
                          $row["CAAID"], $row['CAAPosition'], $row['CAABoxType']);
          $biglinks->updateStructureLinkSubContentImages($ID, array('CAABTitle', 'CAABText', 'CAABImage', 'CAABLink'));
        }
      }

      $noAutoText = (int)$post->readBool("checkbox_hide_text_{$ID}");
      if ($noAutoText) {
        $input['Text']['CAABText'] = '&nbsp;';
      }

      $sql = " UPDATE {$this->table_prefix}contentitem_ca_area_box "
           . " SET CAABTitle = '" . $this->db->escape($input['Title']['CAABTitle']) . "', "
           . "     CAABText = '" . $this->db->escape($input['Text']['CAABText']) . "', "
           . "     CAABImage = '" . $this->db->escape($image) . "', "
           . "     CAABNoImage = $noImage, "
           . "     CAABLink = " . $this->db->escape($input['Link']['CAABLink']) . ", "
           . "     CAABExtlink = '{$this->db->escape($input['Extlink'])}' "
           . " WHERE CAABID = $ID ";
      $result = $this->db->query($sql);

      $this->_updateExtendedData($ID);

      $this->setMessage(Message::createSuccess($_LANG['ca_message_area_box_update_success']));
    }
  }

}


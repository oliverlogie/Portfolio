<?php

/**
 * Compendium areas contenttype class handling the areas
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemCA_Areas extends ContentItem
{
  protected $_configPrefix = 'ca'; // "_area" is added in ContenItemCA_Area_Boxes::_construct()
  protected $_contentPrefix = 'ca_area';
  protected $_columnPrefix = 'CAA';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
    'Link' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'CA'; // "_Areas" is added in ContenItemCA_Area_Boxes::_construct()

  /**
   * The parent contentitem ( ContentItemCA )
   *
   * @var ContentItemCA
   */
  private $_parent = null;

  /**
   * @var bool
   */
  private $_validationError = false;

  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix,
                              $action = '', $page_path = '', User $user = null,
                              Session $session = null, Navigation $navigation, ContentItemCA $parent)
  {
    $this->_parent = $parent;
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);
    $this->_configPrefix .= '_area';
    $this->_templateSuffix .= '_Areas';
  }

  public function delete_content()
  {
    // retrieve all area ids
    $sql = ' SELECT CAAID '
         . " FROM {$this->table_prefix}contentitem_ca_area "
         . " WHERE FK_CIID = $this->page_id ";
    $areas = $this->db->GetCol($sql);

    if ($areas) {
      $this->_deleteExtendedData($areas);
      foreach ($areas as $areaId) {
        $this->_deleteAreaBoxExtendedData($areaId);
      }
    }

    $areas = implode(',', $areas);

    // Delete Area_Boxes ( ContentItemCA_Area_Boxes::delete_content() not available )
    $sql = ' SELECT CAABImage '
         . " FROM {$this->table_prefix}contentitem_ca_area_box "
         . " WHERE FK_CAAID IN ( $areas ) ";
    $images = $this->db->GetCol($sql);
    // delete image files

    self::_deleteImageFiles($images);

    $this->db->query("DELETE FROM {$this->table_prefix}contentitem_ca_area_box WHERE FK_CAAID IN ( $areas )");

    // Delete areas
    $sql = ' SELECT CAAImage '
         . " FROM {$this->table_prefix}contentitem_ca_area "
         . " WHERE FK_CIID = $this->page_id ";
    $images = $this->db->GetCol($sql);

    // delete image files
    self::_deleteImageFiles($images);

    $this->db->query("DELETE FROM {$this->table_prefix}contentitem_ca_area WHERE FK_CIID = $this->page_id");
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT CAAID "
         . " FROM {$this->table_prefix}contentitem_ca_area "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      $newAreaParentId = parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
      /* @var $areaBox ContentItemCA_Area_Boxes */
      $areaBox = $this->_getSubelementByAreaId($id);
      $areaBox->duplicateContent($pageId, $newAreaParentId, '', $id);
    }
  }

  public function edit_content()
  {
    if ($this->_subelements->isProcessed()) {
      $this->_subelements->edit_content();
    }
    else {
      $this->_updateArea();
      $this->_deleteArea();
      $this->_changeActivation();
      $this->_deleteAreaImage();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG;

    $this->_checkDatabase();
    $this->_move();
    $items = array();
    $activePosition = 0;
    $invalidLinks = 0;
    $positionHelper = new PositionHelper(
      $this->db, "{$this->table_prefix}contentitem_ca_area",
      'CAAID', 'CAAPosition',
      'FK_CIID', $this->page_id
    );

    $sql = ' SELECT CAAID, CAATitle, CAAText, CAAImage, CAABoxType, CAAPosition, '
         . '        CAALink, CAAExtlink, CAADisabled, c_link.FK_SID AS Link_FK_SID, '
         . '        c_link.CIIdentifier AS Link_CIIdentifier, c_link.CIID AS Link_CIID '
         . " FROM {$this->table_prefix}contentitem_ca_area cica "
         . " JOIN {$this->table_prefix}contentitem ci "
         . '      ON cica.FK_CIID = ci.CIID '
         . " LEFT JOIN {$this->table_prefix}contentitem c_link "
         . '      ON CAALink = c_link.CIID '
         . " WHERE cica.FK_CIID = $this->page_id "
         . ' ORDER BY CAAPosition ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $position = (int)$row['CAAPosition'];
      $moveUpPosition = $positionHelper->getMoveUpPosition($position);
      $moveDownPosition = $positionHelper->getMoveDownPosition($position);
      $boxesContent = $this->_subelements[$position]->get_content();
      $boxesItems = $boxesContent['content'];
      $boxesCount = $boxesContent['count'];
      if ($boxesContent['message']) {
        $this->setMessage($boxesContent['message']);
      }
      $invalidLinks += $boxesContent['invalidLinks'];

      // Determine if current area is active.
      $request = new Input(Input::SOURCE_REQUEST);
      if ($request->readInt('area') == $row['CAAID'] || $count == 1) {
        $activePosition = $position;
      }

      // detect invalid links
      $class = 'normal';
      // if a link inside a block is invalid then the block is also marked invalid
      if ($boxesContent['invalidLinks']) {
        $class = 'invalid';
      }

      $areaId = $row['CAAID'];
      $internalLink = $this->getInternalLinkHelper($row['CAALink']);
      if ($this->_validationError && $activePosition) { // display unsaved data
        $areaTitle    = $request->readString("ca_area{$areaId}_title", Input::FILTER_CONTENT_TITLE);
        $areaText     = $request->readString("ca_area{$areaId}_text", Input::FILTER_CONTENT_TEXT);
        $areaExtlink  = $request->readString("ca_area{$areaId}_extlink", Input::FILTER_PLAIN);
        list($link, $linkID) = $request->readContentItemLink("ca_area{$areaId}_link");
      }
      else {
        $areaTitle   = $row['CAATitle'];
        $areaText    = $row['CAAText'];
        $areaExtlink = $row['CAAExtlink'];

        // Detect invalid and invisible area links.
        if ($class !== 'invalid')  {
          $class = $internalLink->getClass();
        }
        if ($internalLink->isInvalid()) {
          $invalidLinks++;
        }
        $link = $internalLink->getIdentifier();
        $linkID = $internalLink->getId();

        $areaLabel = isset($_LANG['ca_area_label'][$position]) ?
          $_LANG['ca_area_label'][$position] :
          $_LANG['ca_area_label'][0];

        $prefix = $this->_getPrefixImageConfig($position);
      }


      $items[] = array_merge($this->_getActivationData($row, array('urlParams' => "scrollToAnchor=a_areas")),
        $this->_getUploadedImageDetails($row['CAAImage'], $this->_contentPrefix, $this->getConfigPrefix()),
        $internalLink->getTemplateVars('ca_area'), array(
        'ca_area_title' => $areaTitle,
        'ca_area_title_plain' => strip_tags($areaTitle),
        'ca_area_text' => $areaText,
        'ca_area_extlink' => $areaExtlink,
        'ca_area_image' => $row['CAAImage'],
        'ca_area_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['CAAImage']),
        'ca_area_required_resolution_label' => $this->_getImageSizeInfo($prefix, 0),
        'ca_area_boxes' => $boxesItems,
        'ca_area_id' => $row['CAAID'],
        'ca_area_position' => intval($row["CAAPosition"]),
        'ca_area_class' => $class,
        'ca_area_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteAreaID={$row['CAAID']}&amp;scrollToAnchor=a_areas",
        'ca_area_label' => $areaLabel,
        'ca_area_link' => $link,
        'ca_area_link_id' => $linkID,
        "ca_area_move_up_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveAreaID={$row["CAAID"]}&amp;moveAreaTo=$moveUpPosition&amp;scrollToAnchor=a_areas",
        "ca_area_move_down_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveAreaID={$row["CAAID"]}&amp;moveAreaTo=$moveDownPosition&amp;scrollToAnchor=a_areas",
        'ca_area_button_save_label' => sprintf($_LANG['ca_area_button_save_label'], $position),
      ), $this->_getContentExtensionData($row['CAAID']));
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->_parseTemplateCommonParts();
    $this->tpl->parse_loop($tplName, $items, 'area_items');
    foreach ($items as $item) {
      $this->tpl->parse_if($tplName, "message{$item['ca_area_position']}", $item['ca_area_position'] == $activePosition && $this->_getMessage(), $this->_getMessageTemplateArray('ca_area'));
      $this->tpl->parse_if($tplName, "delete_area{$item['ca_area_position']}_image", $item['ca_area_image'], array(
        'ca_area_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$item['ca_area_id']}&amp;deleteAreaImage={$item['ca_area_id']}&amp;scrollToAnchor=a_area{$item['ca_area_position']}",
      ));
      $this->_parseTemplateCommonParts($tplName, $item['ca_area_id']);
    }
    $itemsOutput = $this->tpl->parsereturn($tplName, array(
      'ca_area_count' => $count,
      'ca_area_active_position' => $activePosition,
      'ca_area_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page={$this->page_id}&moveAreaID=#moveID#&moveAreaTo=#moveTo#&scrollToAnchor=a_areas",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $itemsOutput,
      'invalidLinks' => $invalidLinks,
    );
  }

  protected function _checkDatabase()
  {
    // Determine the amount of currently existing areas.
    $sql = ' SELECT COUNT(CAAID) '
         . " FROM {$this->table_prefix}contentitem_ca_area "
         . " WHERE FK_CIID = $this->page_id ";
    $existingAreas = $this->db->GetOne($sql);

    // "ca_number_of_boxes" specifies the amount of boxes in each area
    $numberOfItems = $this->_parent->getConfig('number_of_boxes');
    $typeOfItems = $this->_parent->getConfig('type_of_boxes');
    if (!is_array($numberOfItems) || !is_array($typeOfItems)) {
      return;
    }
    $numberOfAreas = count($numberOfItems);
    $boxTypes = $typeOfItems;

    if ($numberOfAreas != count($boxTypes)) {
      $message = "The configuration variables '"
               . $this->_parent->getConfigPrefix() . "_number_of_boxes' and '"
               . $this->_parent->getConfigPrefix() ."_type_of_boxes' "
               . "do not match (different amount of areas specified).";
      throw new Exception($message);
    }

    $created = false;
    // Create missing areas.
    for ($i = $existingAreas + 1; $i <= $numberOfAreas; $i++)
    {
      $boxType = $boxTypes[$i - 1];
      if (   $boxType != ContentItemCA_Area_Boxes::TYPE_LARGE
          && $boxType != ContentItemCA_Area_Boxes::TYPE_MEDIUM
          && $boxType != ContentItemCA_Area_Boxes::TYPE_SMALL
      ) {
        throw new Exception("The configured box type '$boxType' is unknown.");
      }

      $created = true;
      $sql = " INSERT INTO {$this->table_prefix}contentitem_ca_area "
           . " (CAABoxType, CAAPosition, FK_CIID) VALUES "
           . " ('$boxType', $i, $this->page_id) ";
      $result = $this->db->query($sql);
    }

    $sql = " SELECT CAAID "
         . " FROM {$this->table_prefix}contentitem_ca_area "
         . " WHERE FK_CIID = $this->page_id ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_createExtendedData($ids);
    }

    if ($created) { // new items have been created, so we read subelements again
      $this->_readSubElements();
    }
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $sql = " SELECT CAAID, CAABoxType, CAAPosition "
         . " FROM {$this->table_prefix}contentitem_ca_area cica "
         . " WHERE cica.FK_CIID = $this->page_id "
         . " ORDER BY CAAPosition ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $pos = (int)$row['CAAPosition'];
      $this->_subelements[$pos] = new ContentItemCA_Area_Boxes($this->site_id,
          $this->page_id, $this->tpl, $this->db, $this->table_prefix,
          $this->action, $this->page_path, $this->_user, $this->session,
          $this->_navigation, $this->_parent, $row['CAAID'], $pos,
          $row['CAABoxType']);
    }
  }

  protected function _processedValues()
  {
    return array('changeActivationID',
                 'deleteAreaID',
                 'deleteAreaImage',
                 'deleteBoxID',
                 'moveAreaID',
                 'process_all',
                 'process_ca_area',
                 'process_ca_area_box',
                 'process_ca_area_box_subelement',);
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

    $positionHelper = new PositionHelper(
      $this->db, "{$this->table_prefix}contentitem_ca_area",
      'CAAID', 'CAAPosition',
      'FK_CIID', $this->page_id
    );
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ca_message_area_box_move_success']));
    }
  }

  /**
   * Returns an array containing the image configuration prefix strings
   *
   * E.g. array(
   *        "ca_area$position",
   *        "ca_area"
   *      )
   * for area at $position.
   *
   *
   * @return array
   */
  private function _getPrefixImageConfig($position)
  {
    $prefix = array();

    foreach ($this->_parent->getConfigPrefix() as $str) {
      $prefix[] = $str . "_area$position";
      $prefix[] = $str . "_area";
    }

    return array_unique($prefix);
  }

  /**
   * Gets the subelement by area id.
   *
   * @param int $areaId
   *        the area id
   * @return ContentItem | null
   */
  private function _getSubelementByAreaId($areaId)
  {
    $contentItem = null;
    foreach ($this->_subelements as $item) {
      if ($item->getAreaId() == $areaId) {
        $contentItem = $item;
      }
    }
    return $contentItem;
  }

  /**
   * Updates areas if the POST parameter 'process_ca_area' or 'process_all' is set.
   */
  private function _updateArea()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_ca_area') && !$post->exists('process_all')) {
      return;
    }

    if ($post->exists('process_all')) {
      // If the POST parameter process_all is set we have to update all areas.
      $sql = ' SELECT CAAID '
           . " FROM {$this->table_prefix}contentitem_ca_area "
           . " WHERE FK_CIID = $this->page_id ";
      $IDs = $this->db->GetCol($sql);
    } else {
      // If only process_ca_area is set we have to update only one area.
      $IDs = (array)$post->readKey('process_ca_area');
    }

    foreach ($IDs as $ID)
    {
      $components = array($this->site_id, $this->page_id, $ID);

      // Read title, text and image content elements.
      $input['Title'] = $this->_readContentElementsTitles($ID);
      $input['Text'] = $this->_readContentElementsTexts($ID);
      $input['Link'] = $this->_readContentElementsLinks($ID);

      $extlink = $post->readString("ca_area{$ID}_extlink", Input::FILTER_PLAIN);
      if (!Validation::isEmpty($extlink) && !Validation::isUrl($extlink)) {
        $this->_validationError = true;
        $this->setMessage(Message::createFailure($_LANG['ca_message_area_invalid_extlink']));
        return;
      }

      // Perform the image uploads.
      // Do not use the ContentItem::_readContentElementsImages() method as area
      // images have special configuration options (considering area position)
      $sql = ' SELECT CAAImage, CAAPosition '
           . " FROM {$this->table_prefix}contentitem_ca_area "
           . " WHERE CAAID = $ID ";
      $row = $this->db->GetRow($sql);
      $existingImage = $row['CAAImage'];
      $position = $row['CAAPosition'];

      $prefix = $this->_getPrefixImageConfig($position);
      $image = $existingImage;
      if ($uploadedImage = $this->_storeImage($_FILES["ca_area{$ID}_image"], $existingImage, $prefix, 0, $components)) {
        $image = $uploadedImage;
      }

      $sql = " UPDATE {$this->table_prefix}contentitem_ca_area "
           . " SET CAATitle = '" . $this->db->escape($input['Title']['CAATitle']) . "', "
           . "     CAAText = '" . $this->db->escape($input['Text']['CAAText']) . "', "
           . "     CAAImage = '" . $image . "', "
           . "     CAALink = " . $input['Link']['CAALink'] . ", "
           . "     CAAExtlink = '" . $extlink . "'"
           . " WHERE CAAID = $ID ";
      $result = $this->db->query($sql);

      $this->_updateExtendedData($ID);

      // update images of linked content items
      if ($this->_structureLinksAvailable && $this->_structureLinks)
      {
        $currentPage = $this->_navigation->getCurrentPage();
        foreach ($this->_structureLinks as $pageID)
        {
          $page = $this->_navigation->getPageByID($pageID);
          $areas = new ContentItemCA_Areas($page->getSite()->getID(), $pageID, $this->tpl,
                                           $this->db, $this->table_prefix, '', '',
                                           $this->_user, $this->session, $this->_navigation,
                                           $this->_parent);
          $areas->updateStructureLinkSubContentImages($ID, array('CAATitle', 'CAAText', 'CAAImage'));
        }
      }

      $this->setMessage(Message::createSuccess($_LANG['ca_message_area_update_success']));
    }
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
    $sql = ' SELECT CAAImage '
         . " FROM {$this->table_prefix}contentitem_ca_area "
         . " WHERE CAAID = $ID "
         . ' UNION '
         . ' SELECT CAABImage '
         . " FROM {$this->table_prefix}contentitem_ca_area_box "
         . " WHERE FK_CAAID = $ID ";
    $images = $this->db->GetCol($sql);

    // Clear the area database entry.
    $sql = " UPDATE {$this->table_prefix}contentitem_ca_area "
         . " SET CAATitle = '', "
         . "     CAAText = '', "
         . "     CAAImage = '', "
         . "     CAALink = 0, "
         . "     CAAExtlink = '' "
         . " WHERE CAAID = $ID ";
    $result = $this->db->query($sql);

    // Clear the box database entries.
    $sql = " UPDATE {$this->table_prefix}contentitem_ca_area_box "
         . " SET CAABTitle = '', "
         . "     CAABText = '', "
         . "     CAABImage = '', "
         . '     CAABNoImage = 0, '
         . '     CAABLink = 0, '
         . "     CAABExtLink = '' "
         . " WHERE FK_CAAID = $ID ";
    $result = $this->db->query($sql);

    // Delete the image files.
    self::_deleteImageFiles($images);

    $this->_deleteExtendedData($ID);
    $this->_deleteAreaBoxExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['ca_message_area_delete_success']));
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
    $sql = 'SELECT CAAImage '
         . "FROM {$this->table_prefix}contentitem_ca_area "
         . "WHERE CAAID = $ID ";
    $image = $this->db->GetOne($sql);

    // Return if no image was found.
    if (!$image) {
      return;
    }

    // Update database entry before actually deleting the image file.
    $sql = " UPDATE {$this->table_prefix}contentitem_ca_area "
         . " SET CAAImage = '' "
         . " WHERE CAAID = $ID ";
    $this->db->query($sql);

    // Delete the image file.
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['ca_message_area_deleteimage_success']));
  }

  /**
   * @param $areaId
   *
   * @throws \Core\Db\Exceptions\QueryException
   * @throws \ReflectionException
   */
  private function _deleteAreaBoxExtendedData($areaId)
  {
    if (!ConfigHelper::get('m_extended_data')) {
      return;
    }

    /**
     * @var ContentItemCA_Area_Boxes $ci
     */
    foreach ($this->_subelements as $ci) {
      if ($ci->getAreaId() == $areaId) {
        $sql = " SELECT CAABID "
          . " FROM {$this->table_prefix}contentitem_ca_area_box "
          . " WHERE FK_CAAID = :FK_CAAID ";
        $results = $this->db->q($sql, array(
          'FK_CAAID' => $areaId,
        ))->fetchAll();

        foreach ($results as $row) {
          $this->_deleteExtendedDataByContentItem($ci, $row['CAABID']);
        }
      }
    }
  }
}

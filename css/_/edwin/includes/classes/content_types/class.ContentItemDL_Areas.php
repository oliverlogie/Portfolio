<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-07-07 07:13:33 +0200 (Fr, 07 Jul 2017) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
class ContentItemDL_Areas extends ContentItem
{
  protected $_configPrefix = 'dl'; // "_area" is added in $this->__construct()
  protected $_contentPrefix = 'dl_area';
  protected $_columnPrefix = 'DA';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'DL'; // "_Area" is added in $this->__construct()

  /**
   * If an area should have been updated but the update failed then this variable contains the ID of this area, otherwise 0.
   *
   * This should be used to determine which data should be filled into the form.
   * If this contains 0 then we just output the data from the database, but if it
   * contains the ID of an area we should output the posted data entered by the user.
   *
   * @var integer
   */
  private $updateAreaFailed = 0;

  /**
   * The parent contentitem ( ContentItemDL )
   *
   * @var ContentItemDL
   */
  private $_parent = null;

  /**
   * Returns true if an area has been changed
   * (activation status changed)
   *
   * @return boolean
   */
  public function hasAreaChanged()
  {
    if ($this->hasActivationChanged()) {
      return true;
    }
    return false;
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function _checkDatabase()
  {
    // Determine the amount of currently existing elements.
    $existingElements = $this->getElementCount();
    $numberOfElements = $this->_getMaxElements();

    $created = false;
    // Create at least one element.
    if (!$existingElements && $numberOfElements) {
      $created = true;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_dl_area "
           . '(DAPosition, FK_CIID) '
           . "VALUES(1, $this->page_id) ";
      $result = $this->db->query($sql);
    }

    if ($created) { // new items have been created, so we read subelements again
      $this->_readSubElements();
    }
  }

  protected function _processedValues()
  {
    return array( 'changeActivationID',
                  'deleteAreaID',
                  'deleteAreaImage',
                  'moveAreaID',
                  'process_dl_area',
                  'process_new_element',);
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $sql = " SELECT DAID, DAPosition "
         . " FROM {$this->table_prefix}contentitem_dl_area "
         . " WHERE FK_CIID = $this->page_id "
         . " ORDER BY DAPosition ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $this->_subelements[] = new ContentItemDL_Area_Files($this->tpl, $this->db,
          $this->table_prefix, $this->site_id, $this->page_id, $this->_user,
          $row['DAID'], $row['DAPosition'], $this->session, $this->_navigation,
          $this->_parent);
    }
    $this->db->free_result($result);
  }

  /**
   * Adss a new area
   *
   * @return void
   */
  private function _addElement()
  {
    global $_LANG;

    // Determine the amount of currently existing elements.
    $existingElements = $this->getElementCount();
    $numberOfElements = $this->_getMaxElements();

    if ($existingElements < $numberOfElements) {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_dl_area "
           . '(DAPosition, FK_CIID) '
           . "VALUES($pos, $this->page_id) ";
      $result = $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG["dl_message_area_create_success"]));
      $this->_readSubElements();
    }
  }

  /**
   * Updates an area if the POST parameter process_dl_area is set.
   */
  private function _updateArea()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_dl_area');
    if (!$ID) {
      return;
    }

    // Read title content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);

    // check required fields (title)
    if (!$input['Title']['DATitle']) {
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_insufficient_input']));
      $this->updateAreaFailed = $ID;
      return;
    }

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $areas = new ContentItemDL_Areas($page->getSite()->getID(), $pageID, $this->tpl,
                       $this->db, $this->table_prefix, $this->_user, $this->session, $this->_navigation, $this->_parent);
        $areas->updateStructureLinkSubContentImages($ID, array('DATitle'));
      }
    }

    // Read text and image content elements.
    $input['Text'] = $this->_readContentElementsTexts($ID);
    $components = array($this->site_id, $this->page_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'DAID');

    // Update the database.
    $sql = "UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
          ."SET DATitle = '" . $this->db->escape($input['Title']['DATitle']) . "', "
          ."    DAText = '" . $this->db->escape($input['Text']['DAText']) . "', "
          ."    DAImage = '" . $input['Image']['DAImage'] . "', "
          ."    DAImageTitles = '" . $this->db->escape($input['Image']['DAImageTitles']) . "' "
          ."WHERE FK_CIID = {$this->page_id} "
          ."AND DAID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_update_success']));
  }

  /**
   * Moves an area if the GET parameters moveAreaID and moveAreaTo are set.
   */
  private function _moveArea()
  {
    global $_LANG;

    if (empty($_GET["moveAreaID"]) || empty($_GET["moveAreaTo"])) {
      return;
    }

    $moveID = (int)$_GET["moveAreaID"];
    $moveTo = (int)$_GET["moveAreaTo"];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_dl_area",
                                         'DAID', 'DAPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['dl_message_area_move_success']));
    }
  }

  /**
   * Deletes (resets) an area if the GET parameter deleteAreaID is set.
   */
  private function _deleteArea()
  {
    global $_LANG;

    if (!isset($_GET["deleteAreaID"])) {
      return;
    }

    $id = (int)$_GET["deleteAreaID"];

    // determine image file
    $sql = 'SELECT DAImage '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE DAID = $id ";
    $image = $this->db->GetOne($sql);

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_dl_area",
                                     'DAID', 'DAPosition',
                                     'FK_CIID', $this->page_id);
    // move element to highest position to resort all other elements
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    // clear area database entry
    $sql = "DELETE FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE DAID = $id ";
    $this->db->query($sql);

    // determine files
    $sql = 'SELECT DFFile '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE FK_DAID = $id "
         . 'AND DFFile IS NOT NULL ';
    $files = $this->db->GetCol($sql);

    // delete file database entries
    $sql = "DELETE FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE FK_DAID = $id ";
    $this->db->query($sql);

    // delete files
    foreach ($files as $file) {
      unlinkIfExists("../$file");
    }
    // delete image files
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_delete_success']));
  }

  /**
   * Deletes an area image if the GET parameter deleteAreaImage is set.
   */
  private function _deleteAreaImage() {
    global $_LANG;

    if (!isset($_GET['deleteAreaImage'])) {
      return;
    }

    $id = (int)$_GET['deleteAreaImage'];

    // determine image file
    $sql = 'SELECT DAImage '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE DAID = $id ";
    $image = $this->db->GetOne($sql);

    // update area database entry before actually deleting the image file
    // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
    $sql = "UPDATE {$this->table_prefix}contentitem_dl_area "
         . "SET DAImage = '' "
         . "WHERE DAID = $id ";
    $this->db->query($sql);

    // delete image file
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_deleteimage_success']));
  }

  /**
   * Returns the maximum number of areas allowed
   *
   * @return int
   */
  private function _getMaxElements()
  {
    return (int)$this->_parent->getConfig('number_of_areas');
  }

  /**
   * Gets the subelement by ContentItem area id.
   *
   * @param int $id
   *        the download area id
   *
   * @return ContentItem | null
   */
  private function _getSubelementByAreaId($id)
  {
    $contentItem = null;
    foreach ($this->_subelements as $item) {
      if ($item->getAreaId() == $id) {
        $contentItem = $item;
      }
    }
    return $contentItem;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function __construct($site_id, $page_id, Template $tpl, Db $db,
                              $table_prefix, User $user, Session $session = null,
                              Navigation $navigation, ContentItemDL $parent)
  {
    $this->_parent = $parent;
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, '', '',
                        $user, $session, $navigation);
    $this->_configPrefix .= '_area';
    $this->_templateSuffix .= '_Area';
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    global $_LANG;

    $this->_subelements->delete_content();

    // determine image files
    $sql = 'SELECT DAImage '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = $this->page_id ";
    $images = $this->db->GetCol($sql);

    $sql = "DELETE FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);

    // delete image files
    self::_deleteImageFiles($images);
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT DAID "
         . " FROM {$this->table_prefix}contentitem_dl_area "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      $newBoxParentId = parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
      /* @var $areaFiles ContentItemDL_Area_Files */
      $areaFiles = $this->_getSubelementByAreaId($id);
      $areaFiles->duplicateContent($pageId, $newBoxParentId, '', $id);
    }
  }

  public function edit_content()
  {
    if ($this->_subelements->isProcessed()) {
      $this->_subelements->edit_content();
    }
    else {
      if (isset($_POST['process_new_element'])) {
        $this->_addElement();
      }
      $this->_updateArea();
      $this->_moveArea();
      $this->_deleteArea();
      $this->_changeActivation();
      $this->_deleteAreaImage();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $this->_checkDatabase();
    $post = new Input(Input::SOURCE_POST);
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_dl_area",
                                         'DAID', 'DAPosition',
                                         'FK_CIID', $this->page_id);

    // read areas
    $items = array();
    $sql = " SELECT DAID, DATitle, DAText, DAImage, DAImageTitles, DAPosition, "
         . "        DADisabled "
         . " FROM {$this->table_prefix}contentitem_dl_area "
         . " WHERE FK_CIID = $this->page_id "
         . " ORDER BY DAPosition ASC ";
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    $activePosition = 0;
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['DAPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['DAPosition']);

      $filesContent = $this->_getSubelementByAreaId($row['DAID'])->get_content();
      $filesItems = $filesContent['content'];
      $filesCount = $filesContent['count'];
      if ($filesContent['message']) {
        $this->setMessage($filesContent['message']);
      }
      $invalidLinks += $filesContent['invalidLinks'];

      $imageTitles = $this->explode_content_image_titles('dl_area', $row['DAImageTitles']);

      // determine if current area is active
      if (isset($_REQUEST['area']) && $_REQUEST['area'] == $row['DAID']) {
        $activePosition = $row['DAPosition'];
      }

      // detect invalid links
      $class = 'normal';
      // if a file inside an area is invalid then the area is also marked invalid
      if ($filesContent['invalidLinks']) {
        $class = 'invalid';
      }

      // show input again after a failed update
      // (if the user input is empty the database content should be displayed)
      $areaTitle = '';
      $areaText = '';
      if ($this->updateAreaFailed == $row['DAID']) {
        $post = new Input(Input::SOURCE_POST);
        $areaID = $row['DAID'];
        $areaTitle = parseOutput($post->readString("dl_area{$areaID}_title", Input::FILTER_PLAIN));
        $areaText = parseOutput($post->readString("dl_area{$areaID}_text", Input::FILTER_CONTENT_TEXT));
      }
      $areaTitle = empty($areaTitle) ? parseOutput($row['DATitle']) : $areaTitle;
      $areaText = empty($areaText) ? parseOutput($row['DAText']) : $areaText;

      $items[$row['DAID']] = array_merge($this->_getUploadedImageDetails($row['DAImage'], $this->_contentPrefix, $this->getConfigPrefix()),
         $this->_getActivationData($row), $imageTitles, array(
        'dl_area_title' => $areaTitle,
        'dl_area_text' => $areaText,
        'dl_area_image' => $row['DAImage'],
        'dl_area_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['DAImage']),
        'dl_area_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        'dl_area_files' => $filesItems,
        'dl_area_files_count' => $filesCount,
        'dl_area_id' => $row['DAID'],
        'dl_area_position' => $row['DAPosition'],
        'dl_area_class' => $class,
        'dl_area_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveAreaID={$row['DAID']}&amp;moveAreaTo=$moveUpPosition&amp;scrollToAnchor=a_areas",
        'dl_area_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveAreaID={$row['DAID']}&amp;moveAreaTo=$moveDownPosition&amp;scrollToAnchor=a_areas",
        'dl_area_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteAreaID={$row['DAID']}&amp;scrollToAnchor=a_areas",
        'dl_area_image_alt_label' => $_LANG['m_image_alt_label'],
      ), $_LANG2['dl']);
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $numberOfElements = $this->_getMaxElements();
    $subMsg = null;
    if ($count >= $numberOfElements) {
      $subMsg = Message::createFailure($_LANG['dl_message_area_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('dl') : array());
    $this->tpl->parse_if($tplName, 'dl_add_subelement', ($count < $numberOfElements), array());
    $this->tpl->parse_loop($tplName, $items, 'area_items');
    foreach ($items as $item) {
      $this->tpl->parse_if($tplName, "message{$item['dl_area_position']}", $item['dl_area_position'] == $activePosition && $this->_getMessage(), $this->_getMessageTemplateArray('dl_area'));
      $this->tpl->parse_if($tplName, "delete_image{$item['dl_area_position']}", $item['dl_area_image'], array(
        'dl_area_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$item['dl_area_id']}&amp;deleteAreaImage={$item['dl_area_id']}&amp;scrollToAnchor=a_area{$item['dl_area_position']}",
      ));
      $this->_parseTemplateCommonParts($tplName, $item['dl_area_id']);
    }
    $itemsOutput = $this->tpl->parsereturn($tplName, array(
      'dl_area_action' => "index.php?action=content&site=$this->site_id&page=$this->page_id",
      'dl_area_count' => $count,
      'dl_area_active_position' => $activePosition,
      'dl_area_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveAreaID=#moveID#&moveAreaTo=#moveTo#&scrollToAnchor=a_areas",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $itemsOutput,
      'invalidLinks' => $invalidLinks,
    );
  }

  /**
   * Determine the amount of currently existing download areas.
   * @return int the number of download areas
   */
  public function getElementCount() {
    $sql = 'SELECT COUNT(DAID) '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = {$this->page_id} ";

    return (int) $this->db->GetOne($sql);
  }
}

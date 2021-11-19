<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemCB_Boxes extends ContentItemCB
{
  protected $_configPrefix = 'cb'; // "_box" is added in ContentItemCB_Boxes::_construct()
  protected $_contentPrefix = 'cb_box';
  protected $_columnPrefix = 'CBB';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
    'Link' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'CB'; // "_Box" is added in ContentItemCB_Boxes::_construct()

  /**
   * If a box should have been updated but the update failed then this variable contains the ID of this box, otherwise 0.
   *
   * This should be used to determine which data should be filled into the form.
   * If this contains 0 then we just output the data from the database, but if it
   * contains the ID of a box we should output the posted data entered by the user.
   *
   * @var integer
   */
  private $updateBoxFailed = 0;

  /**
   * The parent contentitem ( ContentItemCB )
   *
   * @var ContentItemCB
   */
  private $_parent = null;

  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix,
                              $action = '', $page_path = '', User $user = null,
                              Session $session = null, Navigation $navigation, ContentItemCB $parent)
  {
    $this->_parent = $parent;
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);
    $this->_configPrefix .= '_box';
    $this->_templateSuffix .= '_Box';
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function checkDatabase()
  {
    // Determine the amount of currently existing boxes.
    $sql = 'SELECT COUNT(CBBID) '
         . "FROM {$this->table_prefix}contentitem_cb_box "
         . "WHERE FK_CIID = $this->page_id ";
    $existingBoxes = $this->db->GetOne($sql);

    $numberOfBoxes = (int)$this->_parent->getConfig('number_of_boxes');

    $created = false;
    // Create missing boxes.
    for ($i = $existingBoxes + 1; $i <= $numberOfBoxes; $i++) {
      $created = true;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_cb_box "
           . '(CBBPosition, FK_CIID) '
           . "VALUES($i, $this->page_id) ";
      $result = $this->db->query($sql);
    }

    $sql = " SELECT CBBID "
         . " FROM {$this->table_prefix}contentitem_cb_box "
         . " WHERE FK_CIID = $this->page_id ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_createExtendedData($ids);
    }

    if ($created) { // new items have been created, so we read subelements again
      $this->_readSubElements();
    }
  }

  protected function _processedValues()
  {
    return array('changeActivationID', // TODO: check if this global parameter does not cause confusion!
                 'deleteBoxID',
                 'deleteBoxImage',
                 'moveBoxID',
                 'process_cb_box',);
  }

  protected function _readSubElements()
  {
    // initialize empty list manually instead of calling
    // parent::_readSubElements() as ContentItemCB, which is already extended by
    // this class overwrites this method.
    $this->_subelements = new ContentItemSubelementList();

    $sql = " SELECT CBBID, CBBPosition "
         . " FROM {$this->table_prefix}contentitem_cb_box cicbb "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . "          ON CBBLink = CIID "
         . " WHERE cicbb.FK_CIID = $this->page_id "
         . " ORDER BY CBBPosition ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {

      $this->_subelements[] = new ContentItemCB_Box_BigLinks(
          $this->site_id, $this->page_id, $this->tpl, $this->db,
          $this->table_prefix, '', '', $this->_user, $this->session,
          $this->_navigation, $this->_parent, $row['CBBID'], $row['CBBPosition']);

      $this->_subelements[] = new ContentItemCB_Box_SmallLinks(
          $this->site_id, $this->page_id, $this->tpl, $this->db,
          $this->table_prefix, '', '', $this->_user, $this->session,
          $this->_navigation, $this->_parent, $row['CBBID'], $row['CBBPosition']);
    }
    $this->db->free_result($result);
  }

  /**
   * Checks if link already exists
   * @param int $linkId
   *            Link id of content item
   * @param int $editId
   *            Id of the link that should be edited
   * @return boolean true if link already exists
   */
  private function _checkMultipleLinks($linkId, $editId = 0)
  {
    global $_LANG;

    $editId = ($editId) ? 'AND CBBID != '.$editId : '';
    $sql = 'SELECT CBBID '
         . "FROM {$this->table_prefix}contentitem_cb_box "
         . "WHERE CBBLink   = {$linkId} "
         . "  AND FK_CIID = {$this->page_id} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Updates a box if the POST parameter process_cb_box is set.
   */
  private function updateBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_cb_box');
    if (!$ID) {
      return;
    }

    // Read link content elements.
    $input['Link'] = $this->_readContentElementsLinks($ID);

    if ($this->getConfig('box_link_required')) {
      // Check required fields (link).
      if (!$input['Link']['CBBLink']) {
        $this->setMessage(Message::createFailure($_LANG['cb_message_box_insufficient_input']));
        $this->updateBoxFailed = $ID;
        return;
      }
    }

    // if a link is provided, we check it for uniqueness as a content item
    // must not be linked more than once
    if ($input['Link']['CBBLink']) {
      if ($this->_checkMultipleLinks($input['Link']['CBBLink'], $ID)) {
        $this->updateBoxFailed = $ID;
        return;
      }
    }

    // Read title, text and image content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Text'] = $this->_readContentElementsTexts($ID);
    $components = array($this->site_id, $this->page_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'CBBID');

    $autoImage = $post->readBool("cb_box{$ID}_autoimage");

    // image from linked content item
    if ($autoImage && ($imageData = $this->getAutoImage($input['Link']['CBBLink'], $this->getConfigPrefix()))) {
      $sql = 'SELECT CBBImage '
           . "FROM {$this->table_prefix}contentitem_cb_box "
           . "WHERE CBBID = $ID ";
      $existingImage = $this->db->GetOne($sql);
      $components = array($this->site_id, $this->page_id, $ID);
      if ($image = $this->_storeImage($imageData, $existingImage, $this->getConfigPrefix(), 0, $components, $this->_contentBoxImage, $this->_contentThumbnails)) {
        $this->_deleteUploadedImages($input['Image'], $ID, 'CBBID');
        $input['Image']['CBBImage'] = $image;
      }

      // Delete the temporarily generated image.
      unlinkIfExists($imageData);
    }

    // Check required fields (title, text or image).
    if (   !$input['Title']['CBBTitle']
        && !$input['Text']['CBBText']
        && !$input['Image']['CBBImage']
    ) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_box_insufficient_input']));
      $this->updateBoxFailed = $ID;
      return;
    }

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $boxes = new ContentItemCB_Boxes($page->getSite()->getID(), $pageID, $this->tpl,
                                         $this->db, $this->table_prefix, '', '',
                                         $this->_user, $this->session, $this->_navigation,
                                         $this->_parent);
        $boxes->updateStructureLinkSubContentImages($ID, array('CBBTitle', 'CBBText', 'CBBImage'));
      }
    }

    // Update the database.
    $sql = "UPDATE {$this->table_prefix}contentitem_cb_box "
          ."SET CBBLink = " . $input['Link']['CBBLink'] . ", "
          ."    CBBTitle = '" . $this->db->escape($input['Title']['CBBTitle']) . "', "
          ."    CBBText = '" . $this->db->escape($input['Text']['CBBText']) . "', "
          ."    CBBImage = '" . $input['Image']['CBBImage'] . "', "
          ."    CBBImageTitles = '" . $this->db->escape($input['Image']['CBBImageTitles']) . "' "
          ."WHERE FK_CIID = {$this->page_id} "
          ."AND CBBID = $ID";
    $result = $this->db->query($sql);

    $this->_updateExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['cb_message_box_success']));
  }

  /**
   * Moves a box if the GET parameters moveBoxID and moveBoxTo are set.
   */
  private function moveBox() {
    global $_LANG;

    if (!isset($_GET['moveBoxID'], $_GET['moveBoxTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveBoxID'];
    $moveTo = (int)$_GET['moveBoxTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box",
                                         'CBBID', 'CBBPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['cb_message_box_success']));
    }
  }

  /**
   * Deletes (resets) a box if the GET parameter deleteBoxID is set.
   */
  private function deleteBox() {
    global $_LANG;

    if (isset($_GET["deleteBoxID"])) {
      $cbbid = (int)$_GET["deleteBoxID"];

      // determine image files
      $images = $this->db->GetCol(<<<SQL
SELECT CBBImage
FROM {$this->table_prefix}contentitem_cb_box
WHERE CBBID = $cbbid
UNION
SELECT BLImage
FROM {$this->table_prefix}contentitem_cb_box_biglink
WHERE FK_CBBID = $cbbid
SQL
      );

      // clear box database entry
      $sql = "UPDATE {$this->table_prefix}contentitem_cb_box "
           . "SET CBBTitle = '', "
           . "    CBBText = '', "
           . "    CBBImage = '', "
           . "    CBBImageTitles = '', "
           . '    CBBLink = 0 '
           . "WHERE CBBID = $cbbid ";
      $result = $this->db->query($sql);

      // clear biglink database entries
      $sql = "UPDATE {$this->table_prefix}contentitem_cb_box_biglink "
           . "SET BLTitle = '', "
           . "    BLText = '', "
           . "    BLImage = '', "
           . "    BLImageTitles = '', "
           . '    BLLink = 0 '
           . "WHERE FK_CBBID = $cbbid ";
      $result = $this->db->query($sql);

      // delete smalllink database entries
      $this->db->query(<<<SQL
DELETE FROM {$this->table_prefix}contentitem_cb_box_smalllink
WHERE FK_CBBID = $cbbid
SQL
      );

      // delete image files
      self::_deleteImageFiles($images);

      $this->_deleteExtendedData($cbbid);
      $this->_deleteBoxLinksExtendedData($cbbid);

      $this->setMessage(Message::createSuccess($_LANG["cb_message_box_success"]));
    }
  }

  /**
   * Deletes a box image if the GET parameter deleteBoxImage is set.
   */
  private function deleteBoxImage() {
    global $_LANG;

    if (isset($_GET['deleteBoxImage'])) {
      $cbbid = (int)$_GET['deleteBoxImage'];

      // determine image file
      $image = $this->db->GetOne(<<<SQL
SELECT CBBImage
FROM {$this->table_prefix}contentitem_cb_box
WHERE CBBID = $cbbid
SQL
      );

      // update box database entry before actually deleting the image file
      // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
      $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_cb_box
SET CBBImage = ''
WHERE CBBID = $cbbid
SQL
      );

      // delete image file
      self::_deleteImageFiles($image);

      $this->setMessage(Message::createSuccess($_LANG["cb_message_box_success"]));
    }
  }

  /**
   * Gets the subelement by ContentItem type and box id.
   *
   * @param Class $type
   *        use ContentItemCB_Box_BigLinks or ContentItemCB_Box_SmallLinks
   * @param int $boxId
   *        the box id
   *
   * @return ContentItem | null
   */
  private function _getSubelementByTypeAndBoxId($type, $boxId)
  {
    $contentItem = null;
    foreach ($this->_subelements as $item) {
      if ((get_class($item) === $type) && ($item->getBoxId() == $boxId)) {
        $contentItem = $item;
      }
    }
    return $contentItem;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content(){
    $cbbids = $this->db->GetCol(<<<SQL
SELECT CBBID
FROM {$this->table_prefix}contentitem_cb_box
WHERE FK_CIID = $this->page_id
SQL
    );

    if ($cbbids) {
      $this->_deleteExtendedData($cbbids);
      foreach ($cbbids as $boxId) {
        $this->_deleteBoxLinksExtendedData($boxId);
      }
    }

    $cbbids = implode(",", $cbbids);

    // determine image files
    $images = $this->db->GetCol(<<<SQL
SELECT CBBImage
FROM {$this->table_prefix}contentitem_cb_box
WHERE FK_CIID = $this->page_id
UNION
SELECT BLImage
FROM {$this->table_prefix}contentitem_cb_box_biglink
WHERE FK_CBBID IN ($cbbids)
SQL
    );

    $this->db->query("DELETE FROM {$this->table_prefix}contentitem_cb_box WHERE FK_CIID = $this->page_id");
    $this->db->query("DELETE FROM {$this->table_prefix}contentitem_cb_box_biglink WHERE FK_CBBID IN ($cbbids)");
    $this->db->query("DELETE FROM {$this->table_prefix}contentitem_cb_box_smalllink WHERE FK_CBBID IN ($cbbids)");

    // delete image files
    self::_deleteImageFiles($images);
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT CBBID "
         . " FROM {$this->table_prefix}contentitem_cb_box "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      $newBoxParentId = parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
      /* @var $boxBigLinks ContentItemCB_Box_BigLinks */
      $boxBigLinks = $this->_getSubelementByTypeAndBoxId('ContentItemCB_Box_BigLinks', $id);
      $boxBigLinks->duplicateContent($pageId, $newBoxParentId, '', $id);
      /* @var $boxSmallLinks ContentItemCB_Box_SmallLinks */
      $boxSmallLinks = $this->_getSubelementByTypeAndBoxId('ContentItemCB_Box_SmallLinks', $id);
      $boxSmallLinks->duplicateContent($pageId, $newBoxParentId, '', $id);
    }
  }

  public function edit_content()
  {
    if ($this->_subelements->isProcessed()) {
      $this->_subelements->edit_content();
    }
    else {
      $this->_changeActivation();
      $this->deleteBox();
      $this->deleteBoxImage();
      $this->moveBox();
      $this->updateBox();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $this->checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box",
                                         'CBBID', 'CBBPosition',
                                         'FK_CIID', $this->page_id);

    // read boxes
    $cb_box_items = array();
    $sql = 'SELECT CBBID, CBBTitle, CBBText, CBBImage, CBBImageTitles, '
         . '       CBBPosition, CBBDisabled, CIID, CIIdentifier '
         . "FROM {$this->table_prefix}contentitem_cb_box cicbb "
         . "LEFT JOIN {$this->table_prefix}contentitem ci "
         . '          ON CBBLink = CIID '
         . "WHERE cicbb.FK_CIID = $this->page_id "
         . 'ORDER BY CBBPosition ASC ';
    $result = $this->db->query($sql);
    $cb_box_count = $this->db->num_rows($result);
    $cb_box_active_position = 0;
    $invalidLinks = 0;
    $invisibleLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['CBBPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['CBBPosition']);
      $boxId = (int)$row['CBBID'];

      $cb_box_biglinks = $this->_getSubelementByTypeAndBoxId(
          'ContentItemCB_Box_BigLinks', $boxId);
      $cb_box_biglinks_content = $cb_box_biglinks->get_content();
      $cb_box_biglinks_items = $cb_box_biglinks_content["content"];
      if ($cb_box_biglinks_content["message"]) {
        $this->setMessage($cb_box_biglinks_content["message"]);
      }
      $invalidLinks += $cb_box_biglinks_content['invalidLinks'];
      $invisibleLinks += $cb_box_biglinks_content['invisibleLinks'];

      // read smalllinks
      $cb_box_smalllinks = $this->_getSubelementByTypeAndBoxId(
          'ContentItemCB_Box_SmallLinks', $boxId);
      $cb_box_smalllinks_content = $cb_box_smalllinks->get_content();
      $cb_box_smalllinks_items = $cb_box_smalllinks_content["content"];
      if ($cb_box_smalllinks_content["message"]) {
        $this->setMessage($cb_box_smalllinks_content["message"]);
      }
      $invalidLinks += $cb_box_smalllinks_content['invalidLinks'];
      $invisibleLinks += $cb_box_smalllinks_content['invisibleLinks'];

      $cb_image_titles = $this->explode_content_image_titles("cb_box", $row["CBBImageTitles"]);

      // determine if current box is active
      if (isset($_REQUEST["box"]) && $_REQUEST["box"] == $row["CBBID"]) {
        $cb_box_active_position = $row['CBBPosition'];
      }

      // $boxClass only considers the link inside the box, $boxCompleteClass
      // also considers the links inside the nested big and small links.
      $boxClass = 'normal';
      $boxCompleteClass = 'normal';
      // Detect invalid and invisible links.
      // If a big link or a small link inside a box is invalid/invisible then
      // the box is also marked invalid/invisible.
      if ($cb_box_biglinks_content['invalidLinks'] ||
          $cb_box_smalllinks_content['invalidLinks']) {
        $boxCompleteClass = 'invalid';
      } else if ($cb_box_biglinks_content['invisibleLinks'] ||
                 $cb_box_smalllinks_content['invisibleLinks']) {
        $boxCompleteClass = 'invisible';
      }
      if ($row['CIID']) {
        $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
        if (!$linkedPage->isVisible()) {
          $boxClass = 'invisible';
          $boxCompleteClass = 'invisible';
          $invisibleLinks++;
        }
      }
      else if (    $this->getConfig('box_link_required')
                && ($row['CBBTitle'] || $row['CBBText'] || $row['CBBImage'])
      ) {
        $boxClass = 'invalid';
        $boxCompleteClass = 'invalid';
        $invalidLinks++;
      }

      // show input again after a failed update
      // (if the user input is empty the database content should be displayed)
      $boxTitle = '';
      $boxText = '';
      $boxLink = '';
      $boxLinkID = 0;
      if ($this->updateBoxFailed == $row['CBBID']) {
        $post = new Input(Input::SOURCE_POST);
        $boxID = $row['CBBID'];
        $boxTitle = parseOutput($post->readString("cb_box{$boxID}_title", Input::FILTER_PLAIN));
        $boxText = parseOutput($post->readString("cb_box{$boxID}_text", Input::FILTER_CONTENT_TEXT));
        $boxLink = parseOutput($post->readString("cb_box{$boxID}_link", Input::FILTER_PLAIN));
        $boxLinkID = (int)$_POST["cb_box{$boxID}_link_id"];
      }
      $boxTitle = empty($boxTitle) ? parseOutput($row['CBBTitle']) : $boxTitle;
      $boxText = empty($boxText) ? parseOutput($row['CBBText']) : $boxText;
      $boxLink = empty($boxLink) ? $row['CIIdentifier'] : $boxLink;
      $boxLinkID = empty($boxLinkID) ? $row['CIID'] : $boxLinkID;

      $cb_box_items[$row["CBBID"]] = array_merge($this->_getActivationData($row), $cb_image_titles,
        $this->_getUploadedImageDetails($row['CBBImage'], $this->_contentPrefix, $this->getConfigPrefix()), array(
        "cb_box_title" => $boxTitle,
        "cb_box_text" => $boxText,
        "cb_box_image" => $row["CBBImage"],
        'cb_box_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['CBBImage']),
        "cb_box_link" => $boxLink,
        "cb_box_link_id" => $boxLinkID,
        "cb_box_required_resolution_label" => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        "cb_box_biglinks" => $cb_box_biglinks_items,
        "cb_box_smalllinks" => $cb_box_smalllinks_items,
        "cb_box_id" => $row["CBBID"],
        "cb_box_position" => $row['CBBPosition'],
        "cb_box_icon" => (($row['CBBPosition']%6) == 0 ? 6 : $row['CBBPosition']%6),
        'cb_box_class' => $boxClass,
        'cb_box_complete_class' => $boxCompleteClass,
        "cb_box_move_up_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveBoxID={$row["CBBID"]}&amp;moveBoxTo=$moveUpPosition&amp;scrollToAnchor=a_boxes",
        "cb_box_move_down_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveBoxID={$row["CBBID"]}&amp;moveBoxTo=$moveDownPosition&amp;scrollToAnchor=a_boxes",
        "cb_box_delete_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteBoxID={$row["CBBID"]}&amp;scrollToAnchor=a_boxes",
        "cb_box_image_alt_label" => $_LANG["m_image_alt_label"],
        'cb_box_button_save_label' => sprintf($_LANG['cb_box_button_save_label'], $row['CBBPosition']),
      ), $this->_getContentExtensionData($row['CBBID']), $_LANG2["cb"]);
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_loop($tplName, $cb_box_items, "box_items");
    foreach ($cb_box_items as $cb_box_item) {
      $this->tpl->parse_if($tplName, "message{$cb_box_item['cb_box_position']}", $cb_box_item['cb_box_position'] == $cb_box_active_position && $this->_getMessage(), $this->_getMessageTemplateArray('cb_box'));
      $this->tpl->parse_if($tplName, "delete_image{$cb_box_item['cb_box_position']}", $cb_box_item['cb_box_image'], array(
        'cb_box_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$cb_box_item['cb_box_id']}&amp;deleteBoxImage={$cb_box_item['cb_box_id']}&amp;scrollToAnchor=a_box{$cb_box_item['cb_box_position']}",
      ));
      $this->tpl->parse_if($tplName, "autoimage_button{$cb_box_item['cb_box_position']}", $cb_box_item['cb_box_link_id']);
      $this->_parseTemplateCommonParts($tplName, $cb_box_item['cb_box_id']);
    }
    $cb_box_items_output = $this->tpl->parsereturn($tplName, array(
      "cb_box_count" => $cb_box_count,
      "cb_box_active_position" => $cb_box_active_position,
      'cb_box_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveBoxID=#moveID#&moveBoxTo=#moveTo#&scrollToAnchor=a_boxes",
    ));

    return array(
      "message" => $this->_getMessage(),
      "content" => $cb_box_items_output,
      'invalidLinks' => $invalidLinks,
      'invisibleLinks' => $invisibleLinks,
    );
  }

  /**
   * @param int $boxId
   *
   * @throws \Core\Db\Exceptions\QueryException
   * @throws \ReflectionException
   */
  private function _deleteBoxLinksExtendedData($boxId)
  {
    if (!ConfigHelper::get('m_extended_data')) {
      return;
    }

    /**
     * @var ContentItemCA_Area_Boxes $ci
     */
    foreach ($this->_subelements as $ci) {
      if ($ci->getBoxId() == $boxId) {

        if ($ci instanceof ContentItemCB_Box_BigLinks) {
          $sql = " SELECT BLID AS ID "
               . " FROM {$this->table_prefix}contentitem_cb_box_biglink"
               . " WHERE FK_CBBID = :Parent ";
        }
        else if ($ci instanceof ContentItemCB_Box_SmallLinks) {
          $sql = " SELECT SLID AS ID "
            . " FROM {$this->table_prefix}contentitem_cb_box_smalllink "
            . " WHERE FK_CBBID = :Parent ";
        }
        else {
          throw Exception("Unknown subelement class %s for %s", get_class($ci), get_class($this));
        }

        $results = $this->db->q($sql, array(
          'Parent' => $boxId,
        ))->fetchAll();

        foreach ($results as $row) {
          $this->_deleteExtendedDataByContentItem($ci, $row['ID']);
        }
      }
    }
  }
}


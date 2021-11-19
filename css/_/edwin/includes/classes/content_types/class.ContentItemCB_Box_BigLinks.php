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
class ContentItemCB_Box_BigLinks extends ContentItemCB
{
  protected $_configPrefix = 'cb'; // "_box_biglink" is added in $this->_construct()
  protected $_contentPrefix = 'cb_box_biglink';
  protected $_columnPrefix = 'BL';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
    'Link' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'CB'; // "_Box_BigLink" is added in $this->_construct()

  /**
   * The box id.
   *
   * @var integer
   */
  private $box_id;
  /**
   * The box position (starting with 1).
   *
   * @var integer
   */
  private $box_position;

  /**
   * If a biglink should have been updated but the update failed then this variable contains the ID of this biglink, otherwise 0.
   *
   * This should be used to determine which data should be filled into the form.
   * If this contains 0 then we just output the data from the database, but if it
   * contains the ID of a biglink we should output the posted data entered by the user.
   *
   * @var integer
   */
  private $updateBigLinkFailed = 0;

  /**
   * The parent contentitem ( ContentItemCB )
   *
   * @var ContentItemCB
   */
  private $_parent = null;

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function checkDatabase()
  {
    // Determine the amount of currently existing big links.
    $existingElements = $this->_getElementCount();
    $numberOfElements = (int)$this->_parent->getConfig('number_of_biglinks');

    // Create at least one element.
    if (!$existingElements && $numberOfElements) {
      $sql = "INSERT INTO {$this->table_prefix}contentitem_cb_box_biglink "
           . '(BLPosition, FK_CBBID) '
           . "VALUES(1, {$this->box_id}) ";
      $result = $this->db->query($sql);
    }

    $sql = " SELECT BLID "
         . " FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . " WHERE FK_CBBID = $this->box_id ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_createExtendedData($ids);
    }
  }

  protected function _processedValues()
  {
    return array( 'deleteBigLinkID',
                  'deleteBigLinkImage',
                  'moveBigLinkID',
                  'process_cb_box_biglink',
                  'process_cb_box_subelement',);
  }

  /**
   * Overwrites ContentItemCB::_readSubElements() - no subelements for this
   * class.
   */
  protected function _readSubElements()
  {
    $this->_subelements = new ContentItemSubelementList();
  }

  /**
   * May adds a new element, if corresponding process button was clicked
   */
  private function _addElement()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_cb_box_subelement');
    if (!$ID) {
      return;
    }
    if ($post->readInt('box') != $this->box_id) {
      return;
    }

    // Determine the amount of currently existing big links.
    $existingElements = $this->_getElementCount();

    if ($existingElements < $this->_getMaxElements()) {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_cb_box_biglink "
           . '(BLPosition, FK_CBBID) '
           . "VALUES($pos, $this->box_id) ";
      $result = $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG["cb_message_box_biglink_create_success"]));
    }
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

    $editId = ($editId) ? 'AND BLID != '.$editId : '';
    $sql = 'SELECT BLID '
         . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . "WHERE BLLink   = {$linkId} "
         . "  AND FK_CBBID = {$this->box_id} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Determine the amount of currently existing biglinks.
   * @return int the number of biglinks
   */
  private function _getElementCount() {
    $sql = 'SELECT COUNT(BLID) '
         . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . "WHERE FK_CBBID = {$this->box_id} ";

    return (int) $this->db->GetOne($sql);
  }

  /**
   * Returns the maximum amount of biglinks that can be created
   *
   * @return int
   */
  private function _getMaxElements()
  {
    return $this->_parent->getConfig('number_of_biglinks');
  }

  /**
   * Updates a big link if the POST parameter process_cb_box_biglink is set.
   */
  private function updateBigLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_cb_box_biglink');
    if (!$ID) {
      return;
    }
    if ($post->readInt('box') != $this->box_id) {
      return;
    }

    // Read link content elements.
    $input['Link'] = $this->_readContentElementsLinks($ID);

    if ($this->getConfig('box_biglink_link_required')) {
      // Check required fields (link)
      if (!$input['Link']['BLLink']) {
        $this->setMessage(Message::createFailure($_LANG['cb_message_box_biglink_insufficient_input']));
        $this->updateBigLinkFailed = $ID;
        return;
      }
    }

    // A content item must not be linked more than once
    if ($this->getConfig('box_biglink_link_required')
       && $this->_checkMultipleLinks($input['Link']['BLLink'], $ID)) {
      return false;
    }

    // Read title, text and image content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Text'] = $this->_readContentElementsTexts($ID);
    $components = array($this->site_id, $this->page_id, $this->box_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'BLID');

    $autoImage = $post->readBool("cb_box_biglink{$ID}_autoimage");

    // image from linked content item
    if ($autoImage && ($imageData = $this->getAutoImage($input['Link']['BLLink'], 'cb_box_biglink'))) {
      $sql = 'SELECT BLImage '
           . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
           . "WHERE BLID = $ID ";
      $existingImage = $this->db->GetOne($sql);
      $components = array($this->site_id, $this->page_id, $this->box_id, $ID);
      if ($image = $this->_storeImage($imageData, $existingImage, $this->getConfigPrefix(), 0, $components, false)) {
        $this->_deleteUploadedImages($input['Image'], $ID, 'BLID');
        $input['Image']['BLImage'] = $image;
      }

      // Delete the temporarily generated image.
      unlinkIfExists($imageData);
    }

    // Check required fields (title, text or image).
    if (   !$input['Title']['BLTitle']
        && !$input['Text']['BLText']
        && !$input['Image']['BLImage']
    ) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_box_biglink_insufficient_input']));
      $this->updateBigLinkFailed = $ID;
      return;
    }

    // update biglink images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $sql = ' SELECT FK_SID, CIID, CBBID, CBBPosition '
           . " FROM {$this->table_prefix}contentitem_cb_box_biglink cbbl "
           . " JOIN {$this->table_prefix}contentitem_cb_box cbb "
           . "      ON FK_CBBID = CBBID AND CBBPosition = $this->box_position "
           . " JOIN {$this->table_prefix}contentitem ci "
           . '      ON cbb.FK_CIID = ci.CIID '
           . ' WHERE CIID IN (' . implode(',', $this->_structureLinks) . ') ';
      $result = $this->db->query($sql);

      while ($row = $this->db->fetch_row($result))
      {
        $biglinks = new ContentItemCB_Box_BigLinks($row['FK_SID'], $row['CIID'],
                          $this->tpl, $this->db, $this->table_prefix, '', '',
                          $this->_user, $this->session, $this->_navigation,
                          $this->_parent, $row["CBBID"], $row['CBBPosition']);
        $biglinks->updateStructureLinkSubContentImages($ID, array('BLTitle', 'BLText', 'BLImage'));
      }
    }

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = "UPDATE {$this->table_prefix}contentitem_cb_box_biglink "
         . "SET BLTitle = '{$this->db->escape($input['Title']['BLTitle'])}', "
         . "    BLText = '{$this->db->escape($input['Text']['BLText'])}', "
         . "    BLImage = '{$input['Image']['BLImage']}', "
         . "    BLImageTitles = '{$this->db->escape($input['Image']['BLImageTitles'])}', "
         . "    BLLink = {$input['Link']['BLLink']} "
         . "WHERE BLID = $ID ";
    $result = $this->db->query($sql);

    $this->_updateExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['cb_message_box_biglink_success']));
  }

  /**
   * Moves a big link if the GET parameters moveBigLinkID and moveBigLinkTo are set.
   */
  private function moveBigLink() {
    global $_LANG;

    if (!isset($_GET['box']) || (int)$_GET['box'] != $this->box_id) {
      return;
    }
    if (!isset($_GET['moveBigLinkID'], $_GET['moveBigLinkTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveBigLinkID'];
    $moveTo = (int)$_GET['moveBigLinkTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box_biglink",
                                         'BLID', 'BLPosition',
                                         'FK_CBBID', $this->box_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['cb_message_box_biglink_success']));
    }
  }

  /**
   * Deletes (resets) a big link if the GET parameter deleteBigLinkID is set.
   */
  private function deleteBigLink() {
    global $_LANG;

    if (isset($_GET["box"], $_GET["deleteBigLinkID"]) && (int)$_GET["box"] == $this->box_id) {
      $blid = (int)$_GET["deleteBigLinkID"];

      // determine image files
      $image = $this->db->GetOne(<<<SQL
SELECT BLImage
FROM {$this->table_prefix}contentitem_cb_box_biglink
WHERE BLID = $blid
SQL
      );

      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box_biglink",
                                            'BLID', 'BLPosition',
                                            'FK_CBBID', $this->box_id);
      // move element to highest position to resort all other elements
      $positionHelper->move($blid, $positionHelper->getHighestPosition());

      // delete big link database entry
      $sql = "DELETE FROM {$this->table_prefix}contentitem_cb_box_biglink "
           . "WHERE BLID = $blid ";
      $this->db->query($sql);

      // delete image files
      self::_deleteImageFiles($image);

      $this->_deleteExtendedData($blid);

      $this->setMessage(Message::createSuccess($_LANG["cb_message_box_biglink_delete_success"]));
    }
  }

  /**
   * Deletes a big link image if the GET parameter deleteBigLinkImage is set.
   */
  private function deleteBigLinkImage() {
    global $_LANG;

    if (isset($_GET["box"], $_GET["deleteBigLinkImage"]) && (int)$_GET["box"] == $this->box_id) {
      $blid = (int)$_GET['deleteBigLinkImage'];

      // determine image file
      $image = $this->db->GetOne(<<<SQL
SELECT BLImage
FROM {$this->table_prefix}contentitem_cb_box_biglink
WHERE BLID = $blid
SQL
      );

      // update big link database entry before actually deleting the image file
      // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
      $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_cb_box_biglink
SET BLImage = ''
WHERE BLID = $blid
SQL
      );

      // delete image file
      self::_deleteImageFiles($image);

      $this->setMessage(Message::createSuccess($_LANG["cb_message_box_biglink_success"]));
    }
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix, $action = '', $page_path = '', User $user = null, Session $session = null, Navigation $navigation, ContentItemCB $parent, $box_id, $box_position)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);
    $this->box_id = $box_id;
    $this->box_position = $box_position;
    $this->_configPrefix .= '_box_biglink';
    $this->_templateSuffix .= '_Box_BigLink';
    $this->_parent = $parent;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content(){
    // not used
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CBBID = {$id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      parent::duplicateContent($pageId, $newParentId, "FK_CBBID", $id, "{$this->_columnPrefix}ID");
    }
  }

  public function edit_content()
  {
    $this->_addElement();
    $this->updateBigLink();
    $this->moveBigLink();
    $this->deleteBigLink();
    $this->deleteBigLinkImage();
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $this->checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box_biglink",
                                         'BLID', 'BLPosition',
                                         'FK_CBBID', $this->box_id);
    // read box big links
    $cb_box_biglink_items = array();
    $sql = 'SELECT BLID, BLTitle, BLText, BLImage, BLImageTitles, BLPosition, '
         . '       CIID, CIIdentifier '
         . "FROM {$this->table_prefix}contentitem_cb_box_biglink bl "
         . "LEFT JOIN {$this->table_prefix}contentitem ci "
         . '          ON BLLink = CIID '
         . "WHERE bl.FK_CBBID = $this->box_id "
         . 'ORDER BY BLPosition ASC ';
    $result = $this->db->query($sql);
    $cb_box_biglink_count = $this->db->num_rows($result);
    $cb_box_biglink_active_position = 0;
    $invalidLinks = 0;
    $invisibleLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['BLPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['BLPosition']);

      $cb_box_biglink_image_titles = $this->explode_content_image_titles("cb_box_biglink", $row["BLImageTitles"]);

      // determine if current biglink is active
      if (isset($_REQUEST["box"], $_REQUEST["bigLink"]) && $_REQUEST["box"] == $this->box_id && $_REQUEST["bigLink"] == $row["BLID"]) {
        $cb_box_biglink_active_position = $row['BLPosition'];
      }

      $bigLinkClass = 'normal';
      // Detect invalid and invisible links.
      if ($row['CIID']) {
        $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
        if (!$linkedPage->isVisible()) {
          $bigLinkClass = 'invisible';
          $invisibleLinks++;
        }
      }
      else if (   $this->getConfig('box_biglink_link_required')
               && ($row['BLTitle'] || $row['BLText'] || $row['BLImage'])
      ) {
        $bigLinkClass = 'invalid';
        $invalidLinks++;
      }

      // show input again after a failed update
      // (if the user input is empty the database content should be displayed
      $bigLinkTitle = '';
      $bigLinkText = '';
      $bigLinkLink = '';
      $bigLinkLinkID = 0;
      if ($this->updateBigLinkFailed == $row['BLID']) {
        $post = new Input(Input::SOURCE_POST);
        $bigLinkID = $row['BLID'];
        $bigLinkTitle = parseOutput($post->readString("cb_box_biglink{$bigLinkID}_title", Input::FILTER_PLAIN));
        $bigLinkText = parseOutput($post->readString("cb_box_biglink{$bigLinkID}_text", Input::FILTER_CONTENT_TEXT));
        $bigLinkLink = parseOutput($post->readString("cb_box_biglink{$bigLinkID}_link", Input::FILTER_PLAIN));
        $bigLinkLinkID = (int)$_POST["cb_box_biglink{$bigLinkID}_link_id"];
      }
      $bigLinkTitle = empty($bigLinkTitle) ? parseOutput($row['BLTitle']) : $bigLinkTitle;
      $bigLinkText = empty($bigLinkText) ? parseOutput($row['BLText']) : $bigLinkText;
      $bigLinkLink = empty($bigLinkLink) ? $row['CIIdentifier'] : $bigLinkLink;
      $bigLinkLinkID = empty($bigLinkLinkID) ? $row['CIID'] : $bigLinkLinkID;

      $cb_box_biglink_items[$row["BLID"]] = array_merge($cb_box_biglink_image_titles,
        $this->_getUploadedImageDetails($row['BLImage'], $this->_contentPrefix, $this->getConfigPrefix()), array(
        "cb_box_biglink_title" => $bigLinkTitle,
        "cb_box_biglink_text" => $bigLinkText,
        "cb_box_biglink_image" => $row["BLImage"],
        'cb_box_biglink_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['BLImage']),
        "cb_box_biglink_link" => $bigLinkLink,
        "cb_box_biglink_link_id" => $bigLinkLinkID,
        "cb_box_biglink_required_resolution_label" => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        "cb_box_biglink_id" => $row["BLID"],
        "cb_box_biglink_position" => $row['BLPosition'],
        'cb_box_biglink_class' => $bigLinkClass,
        'cb_box_biglink_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;moveBigLinkID={$row["BLID"]}&amp;moveBigLinkTo=$moveUpPosition&amp;scrollToAnchor=a_box{$this->box_position}_biglinks",
        'cb_box_biglink_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;moveBigLinkID={$row["BLID"]}&amp;moveBigLinkTo=$moveDownPosition&amp;scrollToAnchor=a_box{$this->box_position}_biglinks",
        "cb_box_biglink_delete_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;deleteBigLinkID={$row["BLID"]}&amp;scrollToAnchor=a_box{$this->box_position}_biglinks",
        "cb_box_biglink_image_alt_label" => $_LANG["m_image_alt_label"],
        'cb_box_biglink_button_save_label' => sprintf($_LANG['cb_box_biglink_button_save_label'], $row['BLPosition']),
      ), $this->_getContentExtensionData($row['BLID']));
    }
    $this->db->free_result($result);

    $numberOfBiglinks = $this->_getMaxElements();
    $tplName = $this->_getStandardTemplateName();
    $tplPath = $this->_getTemplatePath();
    $this->tpl->load_tpl($tplName, $tplPath);
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('cb_box_biglink'));
    $this->tpl->parse_loop($tplName, $cb_box_biglink_items, "box_biglink_items");
    foreach ($cb_box_biglink_items as $cb_box_biglink_item) {
      $this->tpl->parse_if($tplName, "delete_image{$cb_box_biglink_item['cb_box_biglink_position']}", $cb_box_biglink_item['cb_box_biglink_image'], array(
        'cb_box_biglink_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;bigLink={$cb_box_biglink_item['cb_box_biglink_id']}&amp;deleteBigLinkImage={$cb_box_biglink_item['cb_box_biglink_id']}&amp;scrollToAnchor=a_box{$this->box_position}_biglinks",
      ));
      $this->tpl->parse_if($tplName, "autoimage_button{$cb_box_biglink_item['cb_box_biglink_position']}", $cb_box_biglink_item['cb_box_biglink_link_id']);
      $this->_parseTemplateCommonParts(null, $cb_box_biglink_item['cb_box_biglink_id']);
    }
    $subMsg = null;
    if ($cb_box_biglink_count >= $numberOfBiglinks) {
      $subMsg = Message::createFailure($_LANG['cb_message_box_biglink_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('cb_box_biglink') : array());
    $this->tpl->parse_if($tplName, 'cb_box_add_subelement', $cb_box_biglink_count < $numberOfBiglinks);
    $cb_box_biglink_items_output = $this->tpl->parsereturn($tplName, array(
      "cb_box_biglink_count" => $cb_box_biglink_count,
      "cb_box_biglink_active_position" => $cb_box_biglink_active_position,
      'cb_box_biglink_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&box=$this->box_id&moveBigLinkID=#moveID#&moveBigLinkTo=#moveTo#&scrollToAnchor=a_box{$this->box_position}_biglinks",
    ));

    return array(
      "message" => $this->_getMessage(),
      "content" => $cb_box_biglink_items_output,
      'invalidLinks' => $invalidLinks,
      'invisibleLinks' => $invisibleLinks,
    );
  }

  public function updateStructureLinkSubContentImages($subID, $fields = array())
  {
    // Retrieve box at position equal to box $sourceID
    $sql = ' SELECT BLID ' . ($fields ? (', ' . implode(',', $fields)) : '')
         . " FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . " WHERE BLPosition IN ( "
         . '         SELECT BLPosition '
         . "         FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . "         WHERE BLID = $subID "
         . '       ) '
         . "   AND FK_CBBID = $this->box_id ";
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

    // box link id
    $ID = $row['BLID'];
    $components = array($this->site_id, $this->page_id, $ID);
    // Retrieve and store new images (only linked images)
    $input['Image'] = $this->_readContentElementsImages($components, $subID, $ID, 'BLID', true);

    if (empty($input['Image'])) {
      return false;
    }

    // Update the database.
    $sql = " UPDATE {$this->table_prefix}contentitem_cb_box_biglink "
         . " SET BLImage = '" . $input['Image']['BLImage'] . "' "
         . " WHERE FK_CBBID = {$this->box_id} "
         . "   AND BLID = $ID ";
    $result = $this->db->query($sql);

    return true;
  }

  /**
   * Returns the id of the box this biglink belongs to
   *
   * @return int
   */
  public function getBoxId()
  {
    return $this->box_id;
  }
}


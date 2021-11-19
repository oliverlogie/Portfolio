<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */

class ContentItemQP_Statements extends ContentItem
{
  protected $_configPrefix = 'qp'; // "_statement" is added in $this->__construct()
  protected $_contentPrefix = 'qp_statement';
  protected $_columnPrefix = 'QPS';
  protected $_contentElements = array(
    'Title' => 4,
    'Text' => 4,
    'Image' => 11,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'QP';  // "_Statement" is added in $this->__construct()

  /**
   * If a statement should have been updated but the update failed then this variable contains the ID of this statement, otherwise 0.
   *
   * This should be used to determine which data should be filled into the form.
   * If this contains 0 then we just output the data from the database, but if it
   * contains the ID of a statement we should output the posted data entered by the user.
   *
   * @var integer
   */
  private $updateStatementFailed = 0;

  /**
   * The parent contentitem ( ContentItemQP )
   *
   * @var ContentItemQP
   */
  private $_parent = null;

  /**
   * The position of an element recently added.
   *
   * @var int
   */
  private $_addedElementPosition = 0;

  /**
   * True if a statement was deleted.
   *
   * @var boolean
   */
  private $_statementDeleted = false;

  /**
   * True if statement has been moved to another position.
   *
   * @var boolean
   */
  private $_statementMoved = false;

  /**
   * Returns true if a statement has been changed
   * (statement deleted, moved, added or activation status changed)
   *
   * @return boolean
   */
  public function hasStatementChanged()
  {
    if ($this->hasActivationChanged() || $this->_statementDeleted || $this->_addedElementPosition || $this->_statementMoved) {
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
    $existingElements = $this->_getElementCount();
    $numberOfElements = $this->_getMaxElements();

    // Create at least one element.
    if (!$existingElements && $numberOfElements) {
      $sql = "INSERT INTO {$this->table_prefix}contentitem_qp_statement "
           . '(QPSPosition, FK_CIID) '
           . "VALUES(1, $this->page_id) ";
      $result = $this->db->query($sql);
    }

    $sql = " SELECT QPSID "
         . " FROM {$this->table_prefix}contentitem_qp_statement "
         . " WHERE FK_CIID = {$this->page_id} ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_createExtendedData($ids);
    }
  }

  protected function _processedValues()
  {
    return array(
      'changeActivationID',
      'deleteStatementID',
      'deleteStatementImage',
      'moveStatementID',
      'process_new_element',
      'process_qp_statement',
    );
  }

  /**
   * Updates a statement if the POST parameter process_qp_statement is set.
   */
  private function updateStatement()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_qp_statement');
    if (!$ID) {
      return;
    }

    // Read all content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Text'] = $this->_readContentElementsTexts($ID);
    $components = array($this->site_id, $this->page_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'QPSID');

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $statements = new ContentItemQP_Statements($page->getSite()->getID(), $pageID,
                            $this->tpl, $this->db, $this->table_prefix, '', '',
                            $this->_user, $this->session, $this->_navigation,
                            $this->_parent);
        $statements->updateStructureLinkSubContentImages($ID, array());
      }
    }

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = "UPDATE {$this->table_prefix}contentitem_qp_statement "
         . "SET QPSTitle1 = '{$this->db->escape($input['Title']['QPSTitle1'])}', "
         . "    QPSTitle2 = '{$this->db->escape($input['Title']['QPSTitle2'])}', "
         . "    QPSTitle3 = '{$this->db->escape($input['Title']['QPSTitle3'])}', "
         . "    QPSTitle4 = '{$this->db->escape($input['Title']['QPSTitle4'])}', "
         . "    QPSText1 = '{$this->db->escape($input['Text']['QPSText1'])}', "
         . "    QPSText2 = '{$this->db->escape($input['Text']['QPSText2'])}', "
         . "    QPSText3 = '{$this->db->escape($input['Text']['QPSText3'])}', "
         . "    QPSText4 = '{$this->db->escape($input['Text']['QPSText4'])}', "
         . "    QPSImage1 = '{$input['Image']['QPSImage1']}', "
         . "    QPSImage2 = '{$input['Image']['QPSImage2']}', "
         . "    QPSImage3 = '{$input['Image']['QPSImage3']}', "
         . "    QPSImage4 = '{$input['Image']['QPSImage4']}', "
         . "    QPSImage5 = '{$input['Image']['QPSImage5']}', "
         . "    QPSImage6 = '{$input['Image']['QPSImage6']}', "
         . "    QPSImage7 = '{$input['Image']['QPSImage7']}', "
         . "    QPSImage8 = '{$input['Image']['QPSImage8']}', "
         . "    QPSImage9 = '{$input['Image']['QPSImage9']}', "
         . "    QPSImage10 = '{$input['Image']['QPSImage10']}', "
         . "    QPSImage11 = '{$input['Image']['QPSImage11']}', "
         . "    QPSImageTitles = '{$this->db->escape($input['Image']['QPSImageTitles'])}' "
         . "WHERE QPSID = $ID ";
    $result = $this->db->query($sql);

    $this->_updateExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['qp_message_statement_success']));
  }

  /**
   * Moves a statement if the GET parameters moveStatementID and moveStatementTo are set.
   */
  private function moveStatement() {
    global $_LANG;

    if (!isset($_GET['moveStatementID'], $_GET['moveStatementTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveStatementID'];
    $moveTo = (int)$_GET['moveStatementTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_qp_statement",
                                         'QPSID', 'QPSPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['qp_message_statement_success']));
      $this->_statementMoved = true;
    }
  }

  /**
   * Deletes (resets) a statement if the GET parameter deleteStatementID is set.
   */
  private function deleteStatement() {
    global $_LANG;

    if (isset($_GET['deleteStatementID'])) {
      $qpsid = (int)$_GET['deleteStatementID'];

      // determine image files
      $images = $this->db->GetRow(<<<SQL
SELECT QPSImage1, QPSImage2, QPSImage3, QPSImage4, QPSImage5, QPSImage6, QPSImage7, QPSImage8, QPSImage9, QPSImage10, QPSImage11
FROM {$this->table_prefix}contentitem_qp_statement
WHERE QPSID = $qpsid
SQL
      );

      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_qp_statement ",
                                            'QPSID', 'QPSPosition',
                                            'FK_CIID', $this->page_id);
      // move element to highest position to resort all other elements
      $positionHelper->move($qpsid, $positionHelper->getHighestPosition());
      // clear statement database entry
      $this->db->query(<<<SQL
DELETE FROM {$this->table_prefix}contentitem_qp_statement
WHERE QPSID = $qpsid
SQL
      );

      // delete image files
      self::_deleteImageFiles(array(
        $images['QPSImage1'], $images['QPSImage2'], $images['QPSImage3'], $images['QPSImage4'],
        $images['QPSImage5'], $images['QPSImage6'], $images['QPSImage7'], $images['QPSImage8'], $images['QPSImage9'],
        $images['QPSImage10'], $images['QPSImage11']));

      $this->_deleteExtendedData($qpsid);

      $this->_statementDeleted = true;
      $this->setMessage(Message::createSuccess($_LANG['qp_message_statement_delete_success']));
    }
  }

  /**
   * Deletes a statement image if the GET parameter deleteStatementImage is set.
   */
  private function deleteStatementImage() {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('deleteStatementImage') && $get->readInt('img'))
    {
      $qpsid = (int)$_GET['deleteStatementImage'];
      $imgNumber = $get->readInt('img');
      // determine image files
      $image = $this->db->GetOne(<<<SQL
SELECT QPSImage$imgNumber
FROM {$this->table_prefix}contentitem_qp_statement
WHERE QPSID = $qpsid
SQL
      );

      // update statement database entry before actually deleting the image file
      // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
      $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_qp_statement
SET QPSImage$imgNumber = ''
WHERE QPSID = $qpsid
SQL
      );

      // delete image file
      self::_deleteImageFiles($image);

      $this->setMessage(Message::createSuccess($_LANG['qp_message_statement_success']));
    }
  }

  /**
   * May adds a new element, if corresponding process button was clicked
   */
  private function _addElement()
  {
    global $_LANG;

    // Determine the amount of currently existing elements.
    $existingElements = $this->_getElementCount();
    $numberOfElements = $this->_getMaxElements();

    if ($existingElements < $numberOfElements)
    {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_qp_statement "
           . '(QPSPosition, FK_CIID) '
           . "VALUES($pos, $this->page_id) ";
      $result = $this->db->query($sql);

      $this->_addedElementPosition = $pos;
      $this->setMessage(Message::createSuccess($_LANG['qp_message_statement_create_success']));
    }
  }


  /**
   * Determine the amount of currently existing statements.
   * @return int the number of statements
   */
  private function _getElementCount() {
    $sql = 'SELECT COUNT(QPSID) '
         . "FROM {$this->table_prefix}contentitem_qp_statement "
         . "WHERE FK_CIID = {$this->page_id} ";

    return (int) $this->db->GetOne($sql);
  }

  /**
   * Returns the maximum amount of elements that can be created
   *
   * @return int
   */
  private function _getMaxElements()
  {
    return $this->_parent->getConfig('number_of_statements');
  }

  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix,
                              $action = '', $page_path = '', User $user = null,
                              Session $session = null, Navigation $navigation,
                              ContentItemQP $parent)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);
    $this->_parent = $parent;
    $this->_configPrefix .= '_statement';
    $this->_templateSuffix .= '_Statement';
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content(){
    global $_LANG;

    $sql = " SELECT QPSID "
         . " FROM {$this->table_prefix}contentitem_qp_statement "
         . " WHERE FK_CIID = $this->page_id ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_deleteExtendedData($ids);
    }

    // determine image files
    $images = $this->db->GetRow(<<<SQL
SELECT QPSImage1, QPSImage2, QPSImage3, QPSImage4, QPSImage5, QPSImage6, QPSImage7, QPSImage8, QPSImage9, QPSImage10, QPSImage11
FROM {$this->table_prefix}contentitem_qp_statement
WHERE FK_CIID = $this->page_id
SQL
      );

    self::_deleteImageFiles(array(
      $images['QPSImage1'], $images['QPSImage2'], $images['QPSImage3'], $images['QPSImage4'],
      $images['QPSImage5'], $images['QPSImage6'], $images['QPSImage7'], $images['QPSImage8'], $images['QPSImage9'],
      $images['QPSImage10'], $images['QPSImage11']));

    $result = $this->db->query("DELETE FROM ".$this->table_prefix."contentitem_qp_statement WHERE FK_CIID=".$this->page_id);
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
    }
  }

  public function edit_content()
  {
    if (isset($_POST['process_new_element'])) {
      $this->_addElement();
    }
    $this->updateStatement();
    $this->moveStatement();
    $this->deleteStatement();
    $this->_changeActivation();
    $this->deleteStatementImage();
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $this->_checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_qp_statement",
                                         'QPSID', 'QPSPosition',
                                         'FK_CIID', $this->page_id);
    // read statements
    $statementItems = array();
    $result = $this->db->query(<<<SQL
SELECT QPSID, QPSTitle1, QPSTitle2, QPSTitle3, QPSTitle4,
QPSText1, QPSText2, QPSText3, QPSText4,
QPSImage1, QPSImage2, QPSImage3, QPSImage4,
QPSImage5, QPSImage6, QPSImage7, QPSImage8,
QPSImage9, QPSImage10, QPSImage11,
QPSImageTitles, QPSPosition, QPSDisabled
FROM {$this->table_prefix}contentitem_qp_statement
WHERE FK_CIID = $this->page_id
ORDER BY QPSPosition ASC
SQL
    );
    $statementCount = $this->db->num_rows($result);
    $statementActivePosition = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['QPSPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['QPSPosition']);

      $statementImageTitles = $this->explode_content_image_titles('qp_statement', $row['QPSImageTitles']);

      // determine if current statement is active
      if (isset($_REQUEST['statement']) && $_REQUEST['statement'] == $row['QPSID']) {
        $statementActivePosition = $row['QPSPosition'];
      }

      $data = $row;
      if ($this->updateStatementFailed == $row['QPSID'])
      {
        $post = new Input(Input::SOURCE_POST);
        $qpsid = $row['QPSID'];
        $data['QPSTitle1'] = parseOutput($post->readString("qp_statement{$qpsid}_title1", Input::FILTER_PLAIN));
        $data['QPSTitle2'] = parseOutput($post->readString("qp_statement{$qpsid}_title2", Input::FILTER_PLAIN));
        $data['QPSTitle3'] = parseOutput($post->readString("qp_statement{$qpsid}_title3", Input::FILTER_PLAIN));
        $data['QPSTitle4'] = parseOutput($post->readString("qp_statement{$qpsid}_title4", Input::FILTER_PLAIN));
        $data['QPSText1'] = parseOutput($post->readString("qp_statement{$qpsid}_text1", Input::FILTER_CONTENT_TEXT));
        $data['QPSText2'] = parseOutput($post->readString("qp_statement{$qpsid}_text2", Input::FILTER_CONTENT_TEXT));
        $data['QPSText3'] = parseOutput($post->readString("qp_statement{$qpsid}_text3", Input::FILTER_CONTENT_TEXT));
        $data['QPSText4'] = parseOutput($post->readString("qp_statement{$qpsid}_text4", Input::FILTER_CONTENT_TEXT));
      }

      $item = array_merge($statementImageTitles, $this->_getActivationData($row),
        $this->_getUploadedImageDetails($row['QPSImage1'], $this->_contentPrefix, $this->getConfigPrefix(), 1),
        $this->_getUploadedImageDetails($row['QPSImage2'], $this->_contentPrefix, $this->getConfigPrefix(), 2),
        $this->_getUploadedImageDetails($row['QPSImage3'], $this->_contentPrefix, $this->getConfigPrefix(), 3),
        $this->_getUploadedImageDetails($row['QPSImage4'], $this->_contentPrefix, $this->getConfigPrefix(), 4),
        $this->_getUploadedImageDetails($row['QPSImage5'], $this->_contentPrefix, $this->getConfigPrefix(), 5),
        $this->_getUploadedImageDetails($row['QPSImage6'], $this->_contentPrefix, $this->getConfigPrefix(), 6),
        $this->_getUploadedImageDetails($row['QPSImage7'], $this->_contentPrefix, $this->getConfigPrefix(), 7),
        $this->_getUploadedImageDetails($row['QPSImage8'], $this->_contentPrefix, $this->getConfigPrefix(), 8),
        $this->_getUploadedImageDetails($row['QPSImage9'], $this->_contentPrefix, $this->getConfigPrefix(), 9),
        $this->_getUploadedImageDetails($row['QPSImage10'], $this->_contentPrefix, $this->getConfigPrefix(), 10),
        $this->_getUploadedImageDetails($row['QPSImage11'], $this->_contentPrefix, $this->getConfigPrefix(), 11),
        $this->_loadContentElementOutput('Title', $data),
        $this->_loadContentElementOutput('Text', $data),
        $this->_loadContentElementOutput('Image', $row),
        $this->_getContentExtensionData($row['QPSID']),
        array(
        'qp_statement_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;statement={$row['QPSID']}&amp;deleteStatementImage={$row['QPSID']}",
        'qp_statement_large_image1_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage1']),
        'qp_statement_large_image2_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage2']),
        'qp_statement_large_image3_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage3']),
        'qp_statement_large_image4_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage4']),
        'qp_statement_large_image5_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage5']),
        'qp_statement_large_image6_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage6']),
        'qp_statement_large_image7_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage7']),
        'qp_statement_large_image8_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage8']),
        'qp_statement_large_image9_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage9']),
        'qp_statement_large_image10_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage10']),
        'qp_statement_large_image11_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QPSImage11']),
        'qp_statement_required_resolution_label1' => $this->_getImageSizeInfo($this->getConfigPrefix(), 1),
        'qp_statement_required_resolution_label2' => $this->_getImageSizeInfo($this->getConfigPrefix(), 2),
        'qp_statement_required_resolution_label3' => $this->_getImageSizeInfo($this->getConfigPrefix(), 3),
        'qp_statement_required_resolution_label4' => $this->_getImageSizeInfo($this->getConfigPrefix(), 4),
        'qp_statement_required_resolution_label5' => $this->_getImageSizeInfo($this->getConfigPrefix(), 5),
        'qp_statement_required_resolution_label6' => $this->_getImageSizeInfo($this->getConfigPrefix(), 6),
        'qp_statement_required_resolution_label7' => $this->_getImageSizeInfo($this->getConfigPrefix(), 7),
        'qp_statement_required_resolution_label8' => $this->_getImageSizeInfo($this->getConfigPrefix(), 8),
        'qp_statement_required_resolution_label9' => $this->_getImageSizeInfo($this->getConfigPrefix(), 9),
        'qp_statement_required_resolution_label10' => $this->_getImageSizeInfo($this->getConfigPrefix(), 10),
        'qp_statement_required_resolution_label11' => $this->_getImageSizeInfo($this->getConfigPrefix(), 11),
        'qp_statement_id' => $row['QPSID'],
        'qp_statement_position' => $row['QPSPosition'],
        'qp_statement_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveStatementID={$row['QPSID']}&amp;moveStatementTo=$moveUpPosition",
        'qp_statement_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveStatementID={$row['QPSID']}&amp;moveStatementTo=$moveDownPosition",
        'qp_statement_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteStatementID={$row['QPSID']}",
        'qp_statement_image_alt_label' => $_LANG['m_image_alt_label'],
        'qp_statement_button_save_label' => sprintf($_LANG['qp_statement_button_save_label'], $row['QPSPosition']),
      ));

      $tplName = $this->_getStandardTemplateName() . '_item';
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath('Item'));
      // we have to parse variables first in order to make the ContentItem::_parseTemplateCommonParts()
      // work with IFs and LOOPs containing subitem IDs
      $this->tpl->parse_vars($tplName, $item);
      $this->tpl->parse_if($tplName, "message", $item['qp_statement_position'] == $statementActivePosition && $this->_getMessage(), $this->_getMessageTemplateArray('qp_statement'));
      $this->tpl->parse_if($tplName, "delete_statement_image1", $item['qp_statement_image1']);
      $this->tpl->parse_if($tplName, "delete_statement_image2", $item['qp_statement_image2']);
      $this->tpl->parse_if($tplName, "delete_statement_image3", $item['qp_statement_image3']);
      $this->tpl->parse_if($tplName, "delete_statement_image4", $item['qp_statement_image4']);
      $this->tpl->parse_if($tplName, "delete_statement_image5", $item['qp_statement_image5']);
      $this->tpl->parse_if($tplName, "delete_statement_image6", $item['qp_statement_image6']);
      $this->tpl->parse_if($tplName, "delete_statement_image7", $item['qp_statement_image7']);
      $this->tpl->parse_if($tplName, "delete_statement_image8", $item['qp_statement_image8']);
      $this->tpl->parse_if($tplName, "delete_statement_image9", $item['qp_statement_image9']);
      $this->tpl->parse_if($tplName, "delete_statement_image10", $item['qp_statement_image10']);
      $this->tpl->parse_if($tplName, "delete_statement_image11", $item['qp_statement_image11']);
      $this->_parseTemplateCommonParts($tplName, $item['qp_statement_id']);

      $statementItems[] = array('qp_statement_item' => $this->tpl->parsereturn($tplName));
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $numberOfElements = $this->_getMaxElements();
    $this->tpl->parse_if($tplName, 'qp_add_subelement', ($statementCount < $numberOfElements), array());
    $subMsg = null;
    if ($statementCount >= $numberOfElements) {
      $subMsg = Message::createFailure($_LANG['qp_message_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('qp') : array());
    $this->tpl->parse_loop($tplName, $statementItems, 'statement_items');
    $statementItemsOutput = $this->tpl->parsereturn($tplName, array(
      'qp_actionform_action' => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id",
      'qp_statement_count' => $statementCount,
      'qp_statement_active_position' => $statementActivePosition,
      'qp_statement_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveStatementID=#moveID#&moveStatementTo=#moveTo#",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $statementItemsOutput,
    );
  }
}


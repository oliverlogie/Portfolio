<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemQS_Statements extends ContentItem
{
  protected $_configPrefix = 'qs'; // "_statement" is added in $this->__construct()
  protected $_contentPrefix = 'qs_statement';
  protected $_columnPrefix = 'QS';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'QS';  // "_Statement" is added in $this->__construct()

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
   * The parent contentitem ( ContentItemQS )
   *
   * @var ContentItemQS
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
      $sql = "INSERT INTO {$this->table_prefix}contentitem_qs_statement "
           . '(QSPosition, FK_CIID) '
           . "VALUES(1, $this->page_id) ";
      $result = $this->db->query($sql);
    }
    
    $sql = " SELECT QSID "
         . " FROM {$this->table_prefix}contentitem_qs_statement "
         . " WHERE FK_CIID = {$this->page_id} ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_createExtendedData($ids);
    }
  }

  /**
   * Updates a statement if the POST parameter process_qs_statement is set.
   */
  private function updateStatement()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_qs_statement');
    if (!$ID) {
      return;
    }

    // Read all content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Text'] = $this->_readContentElementsTexts($ID);
    $components = array($this->site_id, $this->page_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'QSID');

    // check required fields (title, text or image)
    if (!$input['Title']['QSTitle'] && !$input['Text']['QSText'] && !$input['Image']['QSImage']) {
      $this->setMessage(Message::createFailure($_LANG['qs_message_statement_insufficient_input']));
      $this->updateStatementFailed = $ID;
      return;
    }

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $statements = new ContentItemQS_Statements($page->getSite()->getID(), $pageID,
                            $this->tpl, $this->db, $this->table_prefix, '', '',
                            $this->_user, $this->session, $this->_navigation,
                            $this->_parent);
        $statements->updateStructureLinkSubContentImages($ID, array('QSTitle', 'QSText', 'QSImage'));
      }
    }

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = "UPDATE {$this->table_prefix}contentitem_qs_statement "
         . "SET QSTitle = '{$this->db->escape($input['Title']['QSTitle'])}', "
         . "    QSText = '{$this->db->escape($input['Text']['QSText'])}', "
         . "    QSImage = '{$input['Image']['QSImage']}', "
         . "    QSImageTitles = '{$this->db->escape($input['Image']['QSImageTitles'])}' "
         . "WHERE QSID = $ID ";
    $result = $this->db->query($sql);

    $this->_updateExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['qs_message_statement_success']));
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

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_qs_statement",
                                         'QSID', 'QSPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['qs_message_statement_success']));
      $this->_statementMoved = true;
    }
  }

  /**
   * Deletes (resets) a statement if the GET parameter deleteStatementID is set.
   */
  private function deleteStatement() {
    global $_LANG;

    if (isset($_GET["deleteStatementID"])) {
      $qsid = (int)$_GET["deleteStatementID"];

      // determine image files
      $image = $this->db->GetOne(<<<SQL
SELECT QSImage
FROM {$this->table_prefix}contentitem_qs_statement
WHERE QSID = $qsid
SQL
      );

      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_qs_statement ",
                                            'QSID', 'QSPosition',
                                            'FK_CIID', $this->page_id);
      // move element to highest position to resort all other elements
      $positionHelper->move($qsid, $positionHelper->getHighestPosition());
      // clear statement database entry
      $this->db->query(<<<SQL
DELETE FROM {$this->table_prefix}contentitem_qs_statement
WHERE QSID = $qsid
SQL
      );

      // delete image files
      self::_deleteImageFiles($image);

      $this->_deleteExtendedData($qsid);

      $this->_statementDeleted = true;
      $this->setMessage(Message::createSuccess($_LANG["qs_message_statement_delete_success"]));
    }
  }

  /**
   * Deletes a statement image if the GET parameter deleteStatementImage is set.
   */
  private function deleteStatementImage() {
    global $_LANG;

    if (isset($_GET["deleteStatementImage"])) {
      $qsid = (int)$_GET['deleteStatementImage'];

      // determine image file
      $image = $this->db->GetOne(<<<SQL
SELECT QSImage
FROM {$this->table_prefix}contentitem_qs_statement
WHERE QSID = $qsid
SQL
      );

      // update statement database entry before actually deleting the image file
      // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
      $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_qs_statement
SET QSImage = ''
WHERE QSID = $qsid
SQL
      );

      // delete image file
      self::_deleteImageFiles($image);

      $this->setMessage(Message::createSuccess($_LANG["qs_message_statement_success"]));
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

    if ($existingElements < $numberOfElements) {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_qs_statement "
           . '(QSPosition, FK_CIID) '
           . "VALUES($pos, $this->page_id) ";
      $result = $this->db->query($sql);

      $this->_addedElementPosition = $pos;
      $this->setMessage(Message::createSuccess($_LANG["qs_message_statement_create_success"]));
    }
  }


  /**
   * Determine the amount of currently existing statements.
   * @return int the number of statements
   */
  private function _getElementCount() {
    $sql = 'SELECT COUNT(QSID) '
         . "FROM {$this->table_prefix}contentitem_qs_statement "
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
                              ContentItemQS $parent)
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

    $sql = " SELECT QSID "
         . " FROM {$this->table_prefix}contentitem_qs_statement "
         . " WHERE FK_CIID = $this->page_id ";
    $ids = $this->db->GetCol($sql);
    if ($ids) {
      $this->_deleteExtendedData($ids);
    }

    $images = $this->db->GetCol("SELECT QSImage FROM {$this->table_prefix}contentitem_qs_statement WHERE FK_CIID = $this->page_id");
    self::_deleteImageFiles($images);

    $result = $this->db->query("DELETE FROM ".$this->table_prefix."contentitem_qs_statement WHERE FK_CIID=".$this->page_id);
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

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $this->_checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_qs_statement",
                                         'QSID', 'QSPosition',
                                         'FK_CIID', $this->page_id);

    // read statements
    $qs_statement_items = array();
    $result = $this->db->query(<<<SQL
SELECT QSID, QSTitle, QSText, QSImage, QSImageTitles, QSPosition, QSDisabled
FROM {$this->table_prefix}contentitem_qs_statement
WHERE FK_CIID = $this->page_id
ORDER BY QSPosition ASC
SQL
    );
    $qs_statement_count = $this->db->num_rows($result);
    $qs_statement_active_position = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['QSPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['QSPosition']);

      $qs_statement_image_titles = $this->explode_content_image_titles('qs_statement', $row['QSImageTitles']);

      // determine if current statement is active
      if (isset($_REQUEST['statement']) && $_REQUEST['statement'] == $row['QSID']) {
        $qs_statement_active_position = $row['QSPosition'];
      }

      // show input again after a failed update
      // (if the user input is empty the database content should be displayed)
      $statementTitle = '';
      $statementText = '';
      if ($this->updateStatementFailed == $row['QSID']) {
        $post = new Input(Input::SOURCE_POST);
        $qsid = $row['QSID'];
        $statementTitle = parseOutput($post->readString("qs_statement{$qsid}_title", Input::FILTER_PLAIN));
        $statementText = parseOutput($post->readString("qs_statement{$qsid}_text", Input::FILTER_CONTENT_TEXT));
      }
      $statementTitle = empty($statementTitle) ? parseOutput($row['QSTitle']) : $statementTitle;
      $statementText = empty($statementText) ? parseOutput($row['QSText']) : $statementText;

      $qs_statement_items[$row['QSID']] = array_merge($qs_statement_image_titles, $this->_getActivationData($row),
        $this->_getUploadedImageDetails($row['QSImage'], $this->_contentPrefix, $this->getConfigPrefix()), array(
        'qs_statement_title' => $statementTitle,
        'qs_statement_text' => $statementText,
        'qs_statement_image' => $row['QSImage'],
        'qs_statement_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['QSImage']),
        'qs_statement_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        'qs_statement_id' => $row['QSID'],
        'qs_statement_position' => $row['QSPosition'],
        'qs_statement_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveStatementID={$row['QSID']}&amp;moveStatementTo=$moveUpPosition",
        'qs_statement_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveStatementID={$row['QSID']}&amp;moveStatementTo=$moveDownPosition",
        'qs_statement_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteStatementID={$row['QSID']}",
        'qs_statement_image_alt_label' => $_LANG['m_image_alt_label'],
        'qs_statement_button_save_label' => sprintf($_LANG['qs_statement_button_save_label'], $row['QSPosition']),
      ), $this->_getContentExtensionData($row['QSID']));
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $numberOfElements = $this->_getMaxElements();
    $this->tpl->parse_if($tplName, 'qs_add_subelement', ($qs_statement_count < $numberOfElements), array());
    $subMsg = null;
    if ($qs_statement_count >= $numberOfElements) {
      $subMsg = Message::createFailure($_LANG['qs_message_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('qs') : array());
    $this->tpl->parse_loop($tplName, $qs_statement_items, 'statement_items');
    foreach ($qs_statement_items as $qs_statement_item) {
      $this->tpl->parse_if($tplName, "message{$qs_statement_item['qs_statement_position']}", $qs_statement_item['qs_statement_position'] == $qs_statement_active_position && $this->_getMessage(), $this->_getMessageTemplateArray('qs_statement'));
      $this->tpl->parse_if($tplName, "delete_image{$qs_statement_item['qs_statement_position']}", $qs_statement_item['qs_statement_image'], array(
        'qs_statement_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;statement={$qs_statement_item['qs_statement_id']}&amp;deleteStatementImage={$qs_statement_item['qs_statement_id']}",
      ));
      $this->_parseTemplateCommonParts($tplName, $qs_statement_item['qs_statement_id']);
    }
    $qs_statement_items_output = $this->tpl->parsereturn($tplName, array(
      'qs_actionform_action' => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id",
      'qs_statement_count' => $qs_statement_count,
      'qs_statement_active_position' => $qs_statement_active_position,
      'qs_statement_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveStatementID=#moveID#&moveStatementTo=#moveTo#",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $qs_statement_items_output,
    );
  }

  protected function _processedValues()
  {
    return array('changeActivationID',
                 'deleteStatementID',
                 'deleteStatementImage',
                 'moveStatementID',
                 'process_new_element',
                 'process_qs_statement',);
  }
}
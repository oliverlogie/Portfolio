<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-08-21 08:34:39 +0200 (Mo, 21 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

class ContentItemFQ_Questions extends ContentItem
{
  protected $_configPrefix = 'fq'; // "_question" is added in $this->__construct()
  protected $_contentPrefix = 'fq_question';
  protected $_columnPrefix = 'FQQ';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'FQ'; // "_Question" is added in $this->__construct()

  /**
   * If a question should have been updated but the update failed then this
   * variable contains the ID of this question, otherwise 0.
   *
   * This should be used to determine which data should be filled into the form.
   * If this contains 0 then we just output the data from the database, but if it
   * contains the ID of a question we should output the posted data entered by the user.
   *
   * @var integer
   */
  private $_updateQuestionFailed = 0;


  /**
   * The parent contentitem ( ContentItemFQ )
   *
   * @var ContentItemFQ
   */
  private $_parent = null;

  /**
   * The position of an element recently added.
   *
   * @var int
   */
  private $_addedElementPosition = 0;

  /**
   * True if a question was deleted.
   *
   * @var boolean
   */
  private $_questionDeleted = false;

  /**
   * True if question has been moved to another position.
   *
   * @var boolean
   */
  private $_questionMoved = false;

  /**
   * Returns true if a question has been changed
   * (question deleted, moved, added or activation status changed)
   *
   * @return boolean
   */
  public function hasQuestionChanged()
  {
    if ($this->hasActivationChanged() || $this->_questionDeleted || $this->_addedElementPosition || $this->_questionMoved) {
      return true;
    }
    return false;
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function _checkDatabase()
  {
    // Determine the amount of currently existing questions.
    $existingQuestions = $this->_getElementCount();
    $numberOfElements = $this->_getMaxElements();

    // Create at least one question
    if (!$existingQuestions && $numberOfElements) {
      $sql = "INSERT INTO {$this->table_prefix}contentitem_fq_question "
           . '(FQQPosition, FK_CIID) '
           . "VALUES(1, $this->page_id) ";
      $result = $this->db->query($sql);
    }
  }

  protected function _processedValues()
  {
    return array( 'changeActivationID',
                  'deleteQuestionID',
                  'deleteQuestionImage',
                  'moveQuestionID',
                  'process_fq_question',
                  'process_new_element',);
  }

  /**
   * Updates a question if the POST parameter process_fq_question is set.
   */
  private function updateQuestion()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_fq_question');
    if (!$ID) {
      return;
    }

    // Read all content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Text'] = $this->_readContentElementsTexts($ID);
    $components = array($this->site_id, $this->page_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'FQQID');

    // check required fields (title, text or image)
    if (!$input['Title']['FQQTitle'] && !$input['Text']['FQQText'] && !$input['Image']['FQQImage']) {
      $this->setMessage(Message::createFailure($_LANG['fq_message_question_insufficient_input']));
      $this->_updateQuestionFailed = $ID;
      return;
    }

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $questions = new ContentItemFQ_Questions($page->getSite()->getID(), $pageID,
                       $this->tpl, $this->db, $this->table_prefix, '', '',
                       $this->_user, $this->session, $this->_navigation, $this->_parent);
        $questions->updateStructureLinkSubContentImages($ID, array());
      }
    }

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateQuestion
    $sql = "UPDATE {$this->table_prefix}contentitem_fq_question "
         . "SET FQQTitle = '{$this->db->escape($input['Title']['FQQTitle'])}', "
         . "    FQQText = '{$this->db->escape($input['Text']['FQQText'])}', "
         . "    FQQImage = '{$input['Image']['FQQImage']}', "
         . "    FQQImageTitles = '{$this->db->escape($input['Image']['FQQImageTitles'])}' "
         . "WHERE FQQID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['fq_message_question_success']));
  }

  /**
   * Moves a question if the GET parameters moveQuestionID and moveQuestionTo are set.
   */
  private function moveQuestion() {
    global $_LANG;

    if (!isset($_GET['moveQuestionID'], $_GET['moveQuestionTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveQuestionID'];
    $moveTo = (int)$_GET['moveQuestionTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_fq_question",
                                         'FQQID', 'FQQPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['fq_message_question_success']));
      $this->_questionMoved = true;
    }
  }

  /**
   * Deletes (resets) a question if the GET parameter deleteQuestionID is set.
   */
  private function deleteQuestion() {
    global $_LANG;

    if (isset($_GET["deleteQuestionID"])) {
      $qsid = (int)$_GET["deleteQuestionID"];
      // determine image files
      $image = $this->db->GetOne(<<<SQL
SELECT FQQImage
FROM {$this->table_prefix}contentitem_fq_question
WHERE FQQID = $qsid
SQL
      );

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_fq_question ",
                                          'FQQID', 'FQQPosition',
                                          'FK_CIID', $this->page_id);
    // move element to highest position to resort all other elements
    $positionHelper->move($qsid, $positionHelper->getHighestPosition());

      // clear question database entry
      $this->db->query(<<<SQL
DELETE FROM {$this->table_prefix}contentitem_fq_question
WHERE FQQID = $qsid
SQL
      );

      // delete image files
      self::_deleteImageFiles($image);

      $this->_questionDeleted = true;
      $this->setMessage(Message::createSuccess($_LANG["fq_message_question_deleted_successfully"]));

    }
  }

  /**
   * Deletes a question image if the GET parameter deleteQuestionImage is set.
   */
  private function deleteQuestionImage() {
    global $_LANG;

    if (isset($_GET["deleteQuestionImage"])) {
      $qsid = (int)$_GET['deleteQuestionImage'];

      // determine image file
      $image = $this->db->GetOne(<<<SQL
SELECT FQQImage
FROM {$this->table_prefix}contentitem_fq_question
WHERE FQQID = $qsid
SQL
      );

      // update question database entry before actually deleting the image file
      // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
      $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_fq_question
SET FQQImage = ''
WHERE FQQID = $qsid
SQL
      );

      // delete image file
      self::_deleteImageFiles($image);

      $this->setMessage(Message::createSuccess($_LANG["fq_message_question_success"]));
    }
  }

  /**
   * May adds a new element, if corresponding process button was clicked
   */
  private function _addElement()
  {
    global $_LANG;

    // Determine the amount of currently existing elements
    $existingElements = $this->_getElementCount();
    $numberOfElements = $this->_getMaxElements();

    if ($existingElements < $numberOfElements) {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_fq_question "
           . '(FQQPosition, FK_CIID) '
           . "VALUES($pos, $this->page_id) ";
      $result = $this->db->query($sql);

      $this->_addedElementPosition = $pos;
      $this->setMessage(Message::createSuccess($_LANG["fq_message_question_create_success"]));
    }
  }

  /**
   * Determine the amount of currently existing questions.
   * @return int the number of questions
   */
  private function _getElementCount() {
    $sql = 'SELECT COUNT(FQQID) '
         . "FROM {$this->table_prefix}contentitem_fq_question "
         . "WHERE FK_CIID = {$this->page_id} ";

    return (int) $this->db->GetOne($sql);
  }

  /**
   * Returns the maximum number of areas allowed
   *
   * @return int
   */
  private function _getMaxElements()
  {
    return (int)$this->_parent->getConfig('number_of_questions');
  }

  public function __construct($site_id, $page_id, Template $tpl, db $db,
                              $table_prefix, $action = '', $page_path = '',
                              User $user = null, Session $session = null,
                              Navigation $navigation, ContentItemFQ $parent)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, '', '',
                        $user, $session, $navigation);
    $this->_configPrefix .= '_question';
    $this->_templateSuffix .= '_Question';
    $this->_parent = $parent;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content(){
    global $_LANG;

    $images = $this->db->GetCol("SELECT FQQImage FROM {$this->table_prefix}contentitem_fq_question WHERE FK_CIID = $this->page_id");
    self::_deleteImageFiles($images);

    $result = $this->db->query("DELETE FROM ".$this->table_prefix."contentitem_fq_question WHERE FK_CIID=".$this->page_id);
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
    $this->updateQuestion();
    $this->moveQuestion();
    $this->deleteQuestion();
    $this->_changeActivation();
    $this->deleteQuestionImage();
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);
    $this->_checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_fq_question",
                                         'FQQID', 'FQQPosition',
                                         'FK_CIID', $this->page_id);
    // read questions
    $fq_question_items = array();
    $result = $this->db->query(<<<SQL
SELECT FQQID, FQQTitle, FQQText, FQQImage, FQQImageTitles, FQQPosition, FQQDisabled
FROM {$this->table_prefix}contentitem_fq_question
WHERE FK_CIID = $this->page_id
ORDER BY FQQPosition ASC
SQL
    );
    $fq_question_count = $this->db->num_rows($result);
    $fq_question_active_position = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['FQQPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['FQQPosition']);

      $fq_question_image_titles = $this->explode_content_image_titles('fq_question', $row['FQQImageTitles']);

      // determine if current question is active
      if (isset($_REQUEST['question']) && $_REQUEST['question'] == $row['FQQID']) {
        $fq_question_active_position = $row['FQQPosition'];
      }

      // show input again after a failed update
      // (if the user input is empty the database content should be displayed)
      $questionTitle = '';
      $questionText = '';
      if ($this->_updateQuestionFailed == $row['FQQID']) {
        $post = new Input(Input::SOURCE_POST);
        $qsid = $row['FQQID'];
        $questionTitle = parseOutput($post->readString("fq_question{$qsid}_title", Input::FILTER_PLAIN));
        $questionText = parseOutput($post->readString("fq_question{$qsid}_text", Input::FILTER_CONTENT_TEXT));
      }
      $questionTitle = empty($questionTitle) ? parseOutput($row['FQQTitle']) : $questionTitle;
      $questionText = empty($questionText) ? parseOutput($row['FQQText']) : $questionText;

      $fq_question_items[$row['FQQID']] = array_merge($fq_question_image_titles, $this->_getActivationData($row),
        $this->_getUploadedImageDetails($row['FQQImage'], $this->_contentPrefix, $this->getConfigPrefix()), array(
        'fq_question_title' => $questionTitle,
        'fq_question_text' => $questionText,
        'fq_question_image' => $row['FQQImage'],
        'fq_question_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['FQQImage']),
        'fq_question_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        'fq_question_id' => $row['FQQID'],
        'fq_question_position' => $row['FQQPosition'],
        'fq_question_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveQuestionID={$row['FQQID']}&amp;moveQuestionTo=$moveUpPosition",
        'fq_question_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveQuestionID={$row['FQQID']}&amp;moveQuestionTo=$moveDownPosition",
        'fq_question_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteQuestionID={$row['FQQID']}",
        'fq_question_image_alt_label' => $_LANG['m_image_alt_label'],
        'fq_question_button_save_label' => sprintf($_LANG['fq_question_button_save_label'], $row['FQQPosition']),
      ));
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $numberOfAreas = $this->_getMaxElements();
    $this->tpl->parse_if($tplName, 'fq_add_subelement', ($fq_question_count < $numberOfAreas), array());
    $subMsg = null;
    if ($fq_question_count >= $numberOfAreas) {
      $subMsg = Message::createFailure($_LANG['fq_message_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('fq') : array());
    $this->tpl->parse_loop($tplName, $fq_question_items, 'question_items');
    foreach ($fq_question_items as $fq_question_item) {
      $this->tpl->parse_if($tplName, "message{$fq_question_item['fq_question_position']}", $fq_question_item['fq_question_position'] == $fq_question_active_position && $this->_getMessage(), $this->_getMessageTemplateArray('fq_question'));
      $this->tpl->parse_if($tplName, "delete_image{$fq_question_item['fq_question_position']}", $fq_question_item['fq_question_image'], array(
        'fq_question_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;question={$fq_question_item['fq_question_id']}&amp;deleteQuestionImage={$fq_question_item['fq_question_id']}",
      ));
      $this->_parseTemplateCommonParts($tplName, $fq_question_item['fq_question_id']);
    }
    $fq_question_items_output = $this->tpl->parsereturn($tplName, array(
      'fq_actionform_action' => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id",
      'fq_question_count' => $fq_question_count,
      'fq_question_active_position' => $fq_question_active_position,
      'fq_question_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveQuestionID=#moveID#&moveQuestionTo=#moveTo#",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $fq_question_items_output,
    );
  }
}


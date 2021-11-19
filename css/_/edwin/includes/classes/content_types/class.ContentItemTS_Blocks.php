<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-09-12 11:28:49 +0200 (Di, 12 Sep 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemTS_Blocks extends ContentItem
{
  protected $_configPrefix = 'ts_block';  // "_block" is added in $this->__construct()
  protected $_contentPrefix = 'ts_block';
  protected $_columnPrefix = 'TB';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 1,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'TS';  // "_Block" is added in $this->__construct()

  /**
   * If a block should have been updated but the update failed then this variable contains the ID of this block, otherwise 0.
   *
   * This should be used to determine which data should be filled into the form.
   * If this contains 0 then we just output the data from the database, but if it
   * contains the ID of a block we should output the posted data entered by the user.
   *
   * @var integer
   */
  private $updateBlockFailed = 0;

  /**
   * The parent contentitem ( ContentItemTS )
   *
   * @var ContentItemTS
   */
  private $_parent = null;

  public function __construct($site_id, $page_id, Template $tpl, Db $db,
                              $table_prefix, User $user, Session $session = null,
                              Navigation $navigation, ContentItemTS $parent)
  {
    $this->_parent = $parent;
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, '', '',
                        $user, $session, $navigation);
    $this->_configPrefix .= '_block';
    $this->_templateSuffix .= '_Block';
  }

  /**
   * Returns true if an area has been changed
   * (activation status changed)
   *
   * @return boolean
   */
  public function hasBlockChanged()
  {
    if ($this->hasActivationChanged()) {
      return true;
    }
    return false;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    $this->_subelements->delete_content();

    // determine image files
    $sql = 'SELECT TBImage '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = $this->page_id ";
    $images = $this->db->GetCol($sql);

    $sql = "DELETE FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);

    // delete image files
    self::_deleteImageFiles($images);
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      $newBlockParentId = parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
      /* @var $boxBigLinks ContentItemCB_Box_BigLinks */
      $boxBigLinks = $this->_getSubElementByBlockId($id);
      $boxBigLinks->duplicateContent($pageId, $newBlockParentId, '', $id);
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
      $this->_updateBlock();
      $this->_moveBlock();
      $this->_deleteBlock();
      $this->_changeActivation();
      $this->_deleteBlockImage();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG;

    $this->_checkDatabase();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ts_block",
                                         'TBID', 'TBPosition',
                                         'FK_CIID', $this->page_id);

    // read blocks
    $items = array();
    $sql = 'SELECT TBID, TBTitle, TBText, TBImage, TBImageTitles, TBPosition, TBDisabled '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = $this->page_id "
         . 'ORDER BY TBPosition ASC ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    $activePosition = 0;
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['TBPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['TBPosition']);

      $linksContent = $this->_getSubElementByBlockId($row['TBID'])->get_content();
      $linksItems = $linksContent['content'];
      $linksCount = $linksContent['count'];
      if ($linksContent['message']) {
        $this->setMessage($linksContent['message']);
      }
      $invalidLinks += $linksContent['invalidLinks'];

      $imageTitles = $this->explode_content_image_titles('ts_block', $row['TBImageTitles']);

      // determine if current block is active
      $request = new Input(Input::SOURCE_REQUEST);
      if ($request->readInt('block') == $row['TBID']) {
        $activePosition = $row['TBPosition'];
      }

      // detect invalid links
      $class = 'normal';
      // if a link inside a block is invalid then the block is also marked invalid
      if ($linksContent['invalidLinks']) {
        $class = 'invalid';
      }

      // show input again after a failed update
      // (if the user input is empty the database content should be displayed)
      $blockTitle = '';
      $blockText = '';
      if ($this->updateBlockFailed == $row['TBID']) {
        $post = new Input(Input::SOURCE_POST);
        $blockID = $row['TBID'];
        $blockTitle = parseOutput($post->readString("ts_block{$blockID}_title", Input::FILTER_PLAIN));
        $blockText = parseOutput($post->readString("ts_block{$blockID}_text", Input::FILTER_CONTENT_TEXT));
      }
      $blockTitle = empty($blockTitle) ? parseOutput($row['TBTitle']) : $blockTitle;
      $blockText = empty($blockText) ? parseOutput($row['TBText']) : $blockText;

      $items[$row['TBID']] = array_merge($imageTitles, $this->_getActivationData($row),
        $this->_getUploadedImageDetails($row['TBImage'], $this->_contentPrefix, $this->getConfigPrefix()), array(
        'ts_block_title' => $blockTitle,
        'ts_block_text' => $blockText,
        'ts_block_image' => $row['TBImage'],
        'ts_block_large_image_available' => $this->_getImageZoomLink($this->_contentPrefix, $row['TBImage']),
        'ts_block_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        'ts_block_links' => $linksItems,
        'ts_block_links_count' => $linksCount,
        'ts_block_id' => $row['TBID'],
        'ts_block_position' => $row['TBPosition'],
        'ts_block_class' => $class,
        'ts_block_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveBlockID={$row['TBID']}&amp;moveBlockTo=$moveUpPosition&amp;scrollToAnchor=a_blocks",
        'ts_block_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveBlockID={$row['TBID']}&amp;moveBlockTo=$moveDownPosition&amp;scrollToAnchor=a_blocks",
        'ts_block_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteBlockID={$row['TBID']}&amp;scrollToAnchor=a_blocks",
        'ts_block_image_alt_label' => $_LANG['m_image_alt_label'],
        'ts_block_button_save_label' => sprintf($_LANG['ts_block_button_save_label'], $row['TBPosition']),
      ));
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $numberOfElements = (int)$this->_parent->getConfig('number_of_blocks');
    $subMsg = null;
    if ($count >= $numberOfElements) {
      $subMsg = Message::createFailure($_LANG['ts_message_block_max_elements']);
    }
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('ts') : array());
    $this->tpl->parse_if($tplName, 'ts_add_subelement', ($count < $numberOfElements), array());
    $this->tpl->parse_loop($tplName, $items, 'block_items');
    foreach ($items as $item) {
      $this->tpl->parse_if($tplName, "message{$item['ts_block_position']}", $item['ts_block_position'] == $activePosition && $this->_getMessage(), $this->_getMessageTemplateArray('ts_block'));
      $this->tpl->parse_if($tplName, "delete_image{$item['ts_block_position']}", $item['ts_block_image'], array(
        'ts_block_delete_image_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;block={$item['ts_block_id']}&amp;deleteBlockImage={$item['ts_block_id']}&amp;scrollToAnchor=a_block{$item['ts_block_position']}",
      ));
      $this->_parseTemplateCommonParts($tplName, $item['ts_block_id']);
    }
    $itemsOutput = $this->tpl->parsereturn($tplName, array(
      'ts_block_action' => "index.php?action=content&site=$this->site_id&page=$this->page_id",
      'ts_block_count' => $count,
      'ts_block_active_position' => $activePosition,
      'ts_block_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveBlockID=#moveID#&moveBlockTo=#moveTo#&scrollToAnchor=a_blocks",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $itemsOutput,
      'invalidLinks' => $invalidLinks,
    );
  }

  /**
   * Determine the amount of currently existing blocks.
   * @return int the number of blocks
   */
  public function getElementCount() {
    $sql = 'SELECT COUNT(TBID) '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = {$this->page_id} ";

    return (int) $this->db->GetOne($sql);
  }

  /**
   * Ensures that all necessary database entries exist.
   */
  protected function _checkDatabase()
  {
    // Determine the amount of currently existing elements.
    $existingElements = $this->getElementCount();
    $numberOfElements = (int)$this->_parent->getConfig('number_of_blocks');

    $created = false;
    // Create at least one element.
    if (!$existingElements && $numberOfElements) {
      $created = true;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_ts_block "
           . '(TBPosition, FK_CIID) '
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
                  'deleteBlockID',
                  'deleteBlockImage',
                  'moveBlockID',
                  'process_new_element',
                  'process_ts_block', );
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $sql = " SELECT TBID, TBPosition "
         . " FROM {$this->table_prefix}contentitem_ts_block "
         . " WHERE FK_CIID = $this->page_id "
         . " ORDER BY TBPosition ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $this->_subelements[] = new ContentItemTS_Block_Links($this->tpl,
          $this->db, $this->table_prefix, $this->site_id, $this->page_id,
          $this->_navigation, $this->_parent, $row['TBID'], $row['TBPosition']);
    }
    $this->db->free_result($result);
  }

  /**
   * May adds a new element, if corresponding process button was clicked
   */
  private function _addElement()
  {
    global $_LANG;

    // Determine the amount of currently existing elements.
    $existingElements = $this->getElementCount();
    $numberOfElements = (int)$this->_parent->getConfig('number_of_blocks');

    if ($existingElements < $numberOfElements) {
      $pos = $existingElements + 1;
      $sql = "INSERT INTO {$this->table_prefix}contentitem_ts_block "
           . '(TBPosition, FK_CIID) '
           . "VALUES($pos, $this->page_id) ";
      $result = $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG["ts_message_block_create_success"]));
      $this->_readSubElements();
    }
  }

  /**
   * Deletes (resets) a block if the GET parameter deleteBlockID is set.
   */
  private function _deleteBlock()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteBlockID');

    if (!$ID) {
      return;
    }

    // determine image file
    $sql = 'SELECT TBImage '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE TBID = $ID ";
    $image = $this->db->GetOne($sql);

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ts_block ",
                                          'TBID', 'TBPosition',
                                          'FK_CIID', $this->page_id);
    // move element to highest position to resort all other elements
    $positionHelper->move($ID, $positionHelper->getHighestPosition());

    // clear block database entry
    $sql = "DELETE FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE TBID = $ID ";
    $result = $this->db->query($sql);

    // delete link database entries
    $sql = "DELETE FROM {$this->table_prefix}contentitem_ts_block_link "
         . "WHERE FK_TBID = $ID ";
    $result = $this->db->query($sql);

    // delete image files
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['ts_message_block_delete_success']));
  }

  /**
   * Deletes a block image if the GET parameter deleteBlockImage is set.
   */
  private function _deleteBlockImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteBlockImage');

    if (!$ID) {
      return;
    }

    // determine image file
    $sql = 'SELECT TBImage '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE TBID = $ID ";
    $image = $this->db->GetOne($sql);

    // update block database entry before actually deleting the image file
    // (if it was the other way around there could be a reference to a non-existing file in case of a crash)
    $sql = "UPDATE {$this->table_prefix}contentitem_ts_block "
         . "SET TBImage = '' "
         . "WHERE TBID = $ID ";
    $result = $this->db->query($sql);

    // delete image file
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['ts_message_block_deleteimage_success']));
  }

  private function _getSubElementByBlockId($id)
  {
    $contentItem = null;
    foreach ($this->_subelements as $item) {
      if ($item->getBlockId() == $id) {
        $contentItem = $item;
      }
    }
    return $contentItem;
  }

  /**
   * Moves a block if the GET parameters moveBlockID and moveBlockTo are set.
   */
  private function _moveBlock()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveBlockID');
    $moveTo = $get->readInt('moveBlockTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ts_block",
                                         'TBID', 'TBPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ts_message_block_move_success']));
    }
  }

  /**
   * Updates a block if the POST parameter process_ts_block is set.
   */
  private function _updateBlock()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_ts_block');
    if (!$ID) {
      return;
    }

    // Read title and text content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Text'] = $this->_readContentElementsTexts($ID);

    // check required fields (title)
    if (!$input['Title']['TBTitle']) {
      $this->setMessage(Message::createFailure($_LANG['ts_message_block_insufficient_input']));
      $this->updateBlockFailed = $ID;
      return;
    }

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $blocks = new ContentItemTS_Blocks($page->getSite()->getID(), $pageID,
                        $this->tpl, $this->db, $this->table_prefix, $this->_user, $this->session, $this->_navigation, $this->_parent);
        $blocks->updateStructureLinkSubContentImages($ID, array('TBTitle'));
      }
    }

    // Read image content elements.
    $components = array($this->site_id, $this->page_id, $ID);
    $input['Image'] = $this->_readContentElementsImages($components, $ID, $ID, 'TBID');

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = "UPDATE {$this->table_prefix}contentitem_ts_block "
         . "SET TBTitle = '{$this->db->escape($input['Title']['TBTitle'])}', "
         . "    TBText = '{$this->db->escape($input['Text']['TBText'])}', "
         . "    TBImage = '{$input['Image']['TBImage']}', "
         . "    TBImageTitles = '{$this->db->escape($input['Image']['TBImageTitles'])}' "
         . "WHERE TBID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['ts_message_block_update_success']));
  }

}


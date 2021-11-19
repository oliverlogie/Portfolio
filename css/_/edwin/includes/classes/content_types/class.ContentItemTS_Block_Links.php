<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemTS_Block_Links extends ContentItem
{
  protected $_configPrefix = 'ts'; // "_block_link" is added in $this->__construct()
  protected $_contentPrefix = 'ts_block_link';
  protected $_columnPrefix = 'TL';
  protected $_contentElements = array(
    'Title' => 1,
    'Link' => 1,
  );
  protected $_templateSuffix = 'TS'; // "_Block_Link" is added in $this->__construct()

  /**
   * The parent contentitem ( ContentItemTS )
   *
   * @var ContentItemTS
   */
  private $_parent = null;

  /**
   * The block id.
   *
   * @var integer
   */
  private $_blockID;
  /**
   * The block position (starting with 1).
   *
   * @var integer
   */
  private $_blockPosition;

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

    $editId = ($editId) ? 'AND TLID != '.$editId : '';
    $sql = 'SELECT TLID '
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "WHERE TLLink   = {$linkId} "
         . "  AND FK_TBID   = {$this->_blockID} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['ts_message_block_link_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Creates a link if the POST parameter process_ts_block_link_create is set.
   */
  private function _createLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $blockID = $post->readKey('process_ts_block_link_create');
    if (!$blockID) {
      return;
    }
    if ($post->readInt('block') != $this->_blockID) {
      return;
    }

    $title = $post->readString("ts_block{$blockID}_link_title", Input::FILTER_RIGHT_TITLE);
    list($link, $linkID) = $post->readContentItemLink("ts_block{$blockID}_link_link");

    // A content item must not be linked more than once
    if ($this->_checkMultipleLinks($linkID))
      return false;

    if (!$title || !$linkID) {
      $this->setMessage(Message::createFailure($_LANG['ts_message_block_link_insufficient_input']));
      return;
    }

    $sql = 'SELECT COUNT(TLID) + 1 '
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "WHERE FK_TBID = $blockID ";
    $position = $this->db->GetOne($sql);
    $sql = "INSERT INTO {$this->table_prefix}contentitem_ts_block_link "
         . '(TLTitle, TLLink, TLPosition, FK_TBID) '
         . "VALUES('{$this->db->escape($title)}', $linkID, $position, $blockID) ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['ts_message_block_link_create_success']));

    unset($_POST["ts_block{$blockID}_link_title"]);
    unset($_POST["ts_block{$blockID}_link_link"]);
    unset($_POST["ts_block{$blockID}_link_link_id"]);
  }

  /**
   * Updates a link if the POST parameter process_ts_block_link_edit is set.
   */
  private function _updateLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_ts_block_link_edit');
    if (!$ID) {
      return;
    }
    if ($post->readInt('block') != $this->_blockID) {
      return;
    }

    // Read all content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Link'] = $this->_readContentElementsLinks($ID);

    // Check required fields (title and link)
    if (!$input['Title']['TLTitle'] || !$input['Link']['TLLink']) {
      $this->setMessage(Message::createFailure($_LANG['ts_message_block_link_insufficient_input']));
      $_GET['editLinkID'] = $ID;
      return;
    }

    // A content item must not be linked more than once
    if ($this->_checkMultipleLinks($input['Link']['TLLink'], $ID))
      return false;

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = " UPDATE {$this->table_prefix}contentitem_ts_block_link "
         . " SET TLTitle = '{$this->db->escape($input['Title']['TLTitle'])}', "
         . "     TLLink = {$input['Link']['TLLink']} "
         . " WHERE TLID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['ts_message_block_link_update_success']));
  }

  /**
   * Moves a link if the GET parameters moveLinkID and moveLinkTo are set.
   */
  private function _moveLink()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('block') != $this->_blockID) {
      return;
    }

    $moveID = $get->readInt('moveLinkID');
    $moveTo = $get->readInt('moveLinkTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ts_block_link",
                                         'TLID', 'TLPosition',
                                         'FK_TBID', $this->_blockID);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ts_message_block_link_move_success']));
    }
  }

  /**
   * Deletes a link if the GET parameter deleteLinkID is set.
   */
  private function _deleteLink()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('block') != $this->_blockID) {
      return;
    }

    $ID = $get->readInt('deleteLinkID');

    if (!$ID) {
      return;
    }

    // determine position of deleted link
    $sql = 'SELECT TLPosition '
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "WHERE TLID = $ID ";
    $deletedPosition = $this->db->GetOne($sql);

    // delete link
    $sql = "DELETE FROM {$this->table_prefix}contentitem_ts_block_link "
         . "WHERE TLID = $ID ";
    $result = $this->db->query($sql);

    // move following links one position up
    $sql = "UPDATE {$this->table_prefix}contentitem_ts_block_link "
         . 'SET TLPosition = TLPosition - 1 '
         . "WHERE FK_TBID = $this->_blockID "
         . "AND TLPosition > $deletedPosition "
         . 'ORDER BY TLPosition ASC ';
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['ts_message_block_link_delete_success']));
  }

  protected function _processedValues()
  {
    return array( 'deleteLinkID',
                  'moveLinkID',
                  'process_ts_block_link_create',
                  'process_ts_block_link_edit', );
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function __construct(Template $tpl, db $db, $table_prefix, $site_id, $page_id, Navigation $navigation, ContentItemTS $parent, $blockID, $blockPosition)
  {
    $this->db = $db;
    $this->table_prefix = $table_prefix;
    $this->tpl = $tpl;
    $this->site_id = $site_id;
    $this->page_id = $page_id;
    $this->_blockID = $blockID;
    $this->_blockPosition = $blockPosition;
    $this->_navigation = $navigation;
    $this->_configPrefix .= '_block_link';
    $this->_templateSuffix .= '_Block_Link';
    $this->_parent = $parent;
    $this->_readSubElements();
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // not used
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_TBID = {$id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      parent::duplicateContent($pageId, $newParentId, "FK_TBID", $id, "{$this->_columnPrefix}ID");
    }
  }

  public function edit_content()
  {
    $this->_createLink();
    $this->_updateLink();
    $this->_moveLink();
    $this->_deleteLink();
  }

  public function get_content($params = array())
  {
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_ts_block_link",
                                         'TLID', 'TLPosition',
                                         'FK_TBID', $this->_blockID);
    $post = new Input(Input::SOURCE_POST);
    $get = new Input(Input::SOURCE_GET);

    $editLinkID = $get->readInt('editLinkID');
    $editLinkData = array();

    // read block links
    $linkItems = array();
    $sql = 'SELECT TLID, TLTitle, TLLink, TLPosition, CIID, CIIdentifier '
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "LEFT JOIN {$this->table_prefix}contentitem "
         . '          ON TLLink = CIID '
         . "WHERE FK_TBID = $this->_blockID "
         . 'ORDER BY TLPosition ASC ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['TLPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['TLPosition']);

      // Detect invalid and invisible links.
      $internalLink = $this->getInternalLinkHelper($row['TLLink']);
      if ($internalLink->isInvalid()) {
        $invalidLinks++;
      }
      $class = $internalLink->getClass();
      if ($row['TLID'] == $editLinkID) {
        $class = 'edit';
      }

      $linkItems[$row['TLID']] = array_merge($internalLink->getTemplateVars('ts_block_link'), array(
        'ts_block_link_title' => parseOutput($row['TLTitle']),
        'ts_block_link_id' => $row['TLID'],
        'ts_block_link_position' => $row['TLPosition'],
        'ts_block_link_class' => $class,
        'ts_block_link_edit_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;block={$this->_blockID}&amp;editLinkID={$row['TLID']}&amp;scrollToAnchor=a_block{$this->_blockPosition}_links",
        'ts_block_link_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;block={$this->_blockID}&amp;moveLinkID={$row['TLID']}&amp;moveLinkTo=$moveUpPosition&amp;scrollToAnchor=a_block{$this->_blockPosition}_links",
        'ts_block_link_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;block={$this->_blockID}&amp;moveLinkID={$row['TLID']}&amp;moveLinkTo=$moveDownPosition&amp;scrollToAnchor=a_block{$this->_blockPosition}_links",
        'ts_block_link_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;block={$this->_blockID}&amp;deleteLinkID={$row['TLID']}&amp;scrollToAnchor=a_block{$this->_blockPosition}_links",
      ));

      // this row has to be edited
      if ($row['TLID'] == $editLinkID) {
        $title = $linkItems[$row['TLID']]['ts_block_link_title'];
        $link = $linkItems[$row['TLID']]['ts_block_link_link'];
        $linkID = $linkItems[$row['TLID']]['ts_block_link_link_id'];
        if (isset($_POST["ts_block_link{$editLinkID}_title"])) {
          $title = $post->readString("ts_block_link{$editLinkID}_title", Input::FILTER_PLAIN);
        }
        if (isset($_POST["ts_block_link{$editLinkID}_link"])) {
          $link = trim($post->readString("ts_block_link{$editLinkID}_link", Input::FILTER_PLAIN));
        }
        if (isset($_POST["ts_block_link{$editLinkID}_link_id"])) {
          $linkID = (int)$_POST["ts_block_link{$editLinkID}_link_id"];
        }
        $editLinkData = array(
          'ts_block_link_id' => $row['TLID'],
          'ts_block_link_title_edit' => $title,
          'ts_block_link_link_edit' => $link,
          'ts_block_link_link_id_edit' => $linkID,
        );
      }
    }
    $this->db->free_result($result);

    $maximumReached = $count >= (int)$this->_parent->getConfig('number_of_links');

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    // Fill the new entry form with previously input data (in case of an error).
    $newTitle = $post->readString("ts_block{$this->_blockID}_link_title", Input::FILTER_RIGHT_TITLE);
    list($newLink, $newLinkID) = $post->readContentItemLink("ts_block{$this->_blockID}_link_link");
    $this->tpl->parse_if($tplName, 'entry_create', !$maximumReached, array(
      'ts_block_link_title' => parseOutput($newTitle),
      'ts_block_link_link' => parseOutput($newLink),
      'ts_block_link_link_id' => $newLinkID,
    ));
    // END new entry form
    $this->tpl->parse_if($tplName, 'entries_maximum_reached', $maximumReached);
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ts_block_link'));
    // fill edit entry form with existing data
    $this->tpl->parse_if($tplName, 'entry_edit', $editLinkData, $editLinkData);
    // END edit entry form
    $this->tpl->parse_loop($tplName, $linkItems, 'entries');
    $content = $this->tpl->parsereturn($tplName, array(
      'ts_block_link_count' => $count,
      'ts_block_link_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&block=$this->_blockID&moveLinkID=#moveID#&moveLinkTo=#moveTo#&scrollToAnchor=a_block{$this->_blockPosition}_links",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $content,
      'count' => $count,
      'invalidLinks' => $invalidLinks,
    );
  }

  /**
   * Returns the id of block this link belongs to
   *
   * @return int
   */
  public function getBlockId()
  {
    return $this->_blockID;
  }
}


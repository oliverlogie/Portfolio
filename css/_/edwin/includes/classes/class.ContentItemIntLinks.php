<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

class ContentItemIntLinks extends ContentItem
{


  /**
   * Checks if a internal link already exists
   * @param int $linkId
   *            Link id of content item
   * @param int $editId
   *            Id of the link that should be edited
   * @return boolean true if internal link already exists
   */
  private function _checkMultipleLinks($linkId, $editId = 0)
  {
    global $_LANG;

    $editId = ($editId) ? 'AND ILID != '.$editId : '';
    $sql = 'SELECT ILID '
         . "FROM {$this->table_prefix}internallink "
         . "WHERE FK_CIID_Link = {$linkId} "
         . " AND FK_CIID  = {$this->page_id} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['il_message_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Creates an internal link if the POST parameter process_il_create is set.
   */
  private function _createInternalLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_il_create'])) {
      return;
    }

    $title = $post->readString('il_title', Input::FILTER_RIGHT_TITLE);
    list($link, $linkID) = $post->readContentItemLink('il_link');

    if (!$title || !$linkID) {
      $this->setMessage(Message::createFailure($_LANG['il_message_insufficient_input']));
      return;
    }

    // A content item must not be linked more than once
    if ($this->_checkMultipleLinks($linkID))
      return false;

    $sql = 'SELECT COUNT(ILID) + 1 '
         . "FROM {$this->table_prefix}internallink "
         . "WHERE FK_CIID = $this->page_id ";
    $position = $this->db->GetOne($sql);

    $sql = "INSERT INTO {$this->table_prefix}internallink "
         . '(ILTitle, ILPosition, FK_CIID_Link, FK_CIID) '
         . "VALUES('{$this->db->escape($title)}', $position, $linkID, $this->page_id) ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['il_message_create_success']));

    unset($_POST['il_title'],
          $_POST['il_link'],
          $_POST['il_link_id']);
  }

  /**
   * Updates an internal link if the POST parameter process_il_edit is set.
   */
  private function _updateInternalLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_il_edit'])) {
      return;
    }

    $ID = $post->readKey('process_il_edit');
    $title = $post->readString("il{$ID}_title", Input::FILTER_RIGHT_TITLE);
    list($link, $linkID) = $post->readContentItemLink("il{$ID}_link");

    if (!$title || !$linkID) {
      $this->setMessage(Message::createFailure($_LANG['il_message_insufficient_input']));
      $_GET['editInternalLinkID'] = $ID;
      return;
    }

    // A content item must not be linked more than once
    if ($this->_checkMultipleLinks($linkID, $ID))
      return false;

    $sql = "UPDATE {$this->table_prefix}internallink "
         . "SET ILTitle = '{$this->db->escape($title)}', "
         . "    FK_CIID_Link = $linkID "
         . "WHERE ILID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['il_message_update_success']));
  }

  /**
   * Moves an internal link if the GET parameters moveInternalLinkID and moveInternalLinkTo are set.
   */
  private function _moveInternalLink()
  {
    global $_LANG;

    if (!isset($_GET['moveInternalLinkID'], $_GET['moveInternalLinkTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveInternalLinkID'];
    $moveTo = (int)$_GET['moveInternalLinkTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}internallink",
                                         'ILID', 'ILPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['il_message_move_success']));
    }
  }

  /**
   * Deletes an internal link if the GET parameter deleteInternalLinkID is set.
   */
  private function _deleteInternalLink()
  {
    global $_LANG;

    if (!isset($_GET['deleteInternalLinkID'])) {
      return;
    }

    $id = (int)$_GET['deleteInternalLinkID'];

    // determine position of deleted internal link
    $sql = 'SELECT ILPosition '
         . "FROM {$this->table_prefix}internallink "
         . "WHERE ILID = $id ";
    $deletedPosition = $this->db->GetOne($sql);

    // delete internal link
    $sql = "DELETE FROM {$this->table_prefix}internallink "
         . "WHERE ILID = $id ";
    $result = $this->db->query($sql);

    // move following internal links one position up
    $sql = "UPDATE {$this->table_prefix}internallink "
         . 'SET ILPosition = ILPosition - 1 '
         . "WHERE FK_CIID = $this->page_id "
         . "AND ILPosition > $deletedPosition "
         . 'ORDER BY ILPosition ASC ';
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['il_message_delete_success']));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    // not used, see method _updateInternalLink()
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // not used, see method _deleteInternalLink()
  }

  public function get_content($params = array())
  {
    global $_LANG;

    // Perform create/update/move/delete of an internal link if necessary
    $this->_createInternalLink();
    $this->_updateInternalLink();
    $this->_moveInternalLink();
    $this->_deleteInternalLink();

    $editInternalLinkID = isset($_GET['editInternalLinkID']) ? (int)$_GET['editInternalLinkID'] : 0;
    $editInternalLinkData = array();

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}internallink",
                                         'ILID', 'ILPosition',
                                         'FK_CIID', $this->page_id);

    // read links
    $internalLinkItems = array();
    $sql = 'SELECT ILID, ILTitle, ILPosition, FK_CIID_Link, CIID, CIIdentifier '
         . "FROM {$this->table_prefix}internallink il "
         . "LEFT JOIN {$this->table_prefix}contentitem ci ON ci.CIID = il.FK_CIID_Link "
         . "WHERE il.FK_CIID = $this->page_id "
         . 'ORDER BY ILPosition ';
    $result = $this->db->query($sql);
    $internalLinkCount = $this->db->num_rows($result);
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['ILPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['ILPosition']);

      // Detect invalid and invisible links.
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID_Link']);
      if ($internalLink->isInvalid()) {
        $invalidLinks++;
      }

      $class = $internalLink->getClass();
      if ($row['ILID'] == $editInternalLinkID) {
        $class = 'edit';
      }

      $title = parseOutput($row['ILTitle']);
      $internalLinkItems[] = array_merge($internalLink->getTemplateVars('il'), array(
        'il_title' => $title,
        'il_id' => $row['ILID'],
        'il_position' => (int)$row['ILPosition'],
        'il_class' => $class,
        'il_edit_link' => "index.php?action=intlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;editInternalLinkID={$row['ILID']}",
        'il_move_up_link' => "index.php?action=intlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;moveInternalLinkID={$row['ILID']}&amp;moveInternalLinkTo=$moveUpPosition",
        'il_move_down_link' => "index.php?action=intlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;moveInternalLinkID={$row['ILID']}&amp;moveInternalLinkTo=$moveDownPosition",
        'il_delete_link' => "index.php?action=intlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;deleteInternalLinkID={$row['ILID']}",
      ));

      // this row has to be edited
      if ($row['ILID'] == $editInternalLinkID) {
        $post = new Input(Input::SOURCE_POST);
        if (isset($_POST["il{$editInternalLinkID}_title"])) {
          $title = $post->readString("il{$editInternalLinkID}_title", Input::FILTER_RIGHT_TITLE);
        }
        if (isset($_POST["il{$editInternalLinkID}_link"])) {
          $link = $post->readString("il{$editInternalLinkID}_link", Input::FILTER_PLAIN);
        }
        else {
          $link = $internalLink->getIdentifier();
        }
        if (isset($_POST["il{$editInternalLinkID}_link_id"])) {
          $linkID = (int)$_POST["il{$editInternalLinkID}_link_id"];
        }
        else {
          $linkID = $internalLink->getId();
        }
        $editInternalLinkData = array(
          'il_id' => $row['ILID'],
          'il_title_edit' => $title,
          'il_link_edit' => $link,
          'il_link_id_edit' => $linkID,
          'il_edit_label' => $_LANG['il_edit_label'],
          'il_button_cancel_label' => $_LANG['il_button_cancel_label'],
          'il_button_edit_label' => $_LANG['il_button_edit_label'],
        );
      }
    }
    $this->db->free_result($result);

    $contentLeft = '';
    $contentTop = $this->_getContentTop(self::ACTION_INTERNALLINKS);

    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['il_message_invalid_links'], $invalidLinks)));
    }

    $action = 'index.php';
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="' . $this->action . '" />';

    $this->tpl->load_tpl('content_links', 'content_intlinks.tpl');
    $this->tpl->parse_if('content_links', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('il'));
    $this->tpl->parse_if('content_links', 'entry_edit', $editInternalLinkData, $editInternalLinkData);
    $this->tpl->parse_loop('content_links', $internalLinkItems, 'entries');
    $content = $this->tpl->parsereturn('content_links', array(
      'il_action' => $action,
      'il_hidden_fields' => $hiddenFields,
      'il_function_label' => $_LANG['il_function_label'],
      'il_create_label' => $_LANG['il_create_label'],
      'il_title' => isset($_POST['il_title']) ? $_POST['il_title'] : '',
      'il_link' => isset($_POST['il_link']) ? $_POST['il_link'] : '',
      'il_link_id' => isset($_POST['il_link_id']) ? $_POST['il_link_id'] : 0,
      'il_button_create_label' => $_LANG['il_button_create_label'],
      'il_existing_label' => $_LANG['il_existing_label'],
      'il_count' => $internalLinkCount,
      'il_title_label' => $_LANG['il_title_label'],
      'il_link_label' => $_LANG['il_link_label'],
      'il_edit_label' => $_LANG['il_edit_label'],
      'il_move_up_label' => $_LANG['il_move_up_label'],
      'il_move_down_label' => $_LANG['il_move_down_label'],
      'il_move_label' => $_LANG['il_move_label'],
      'il_delete_label' => $_LANG['il_delete_label'],
      'il_site' => $this->site_id,
      'il_delete_question_label' => $_LANG['il_delete_question_label'],
      'il_autocomplete_contentitem_url' => "index.php?action=response&site=$this->site_id&page=$this->page_id&request=ContentItemAutoComplete&excludeContentItems=$this->page_id",
      'il_dragdrop_link_js' => "index.php?action=intlinks&site=$this->site_id&page=$this->page_id&moveInternalLinkID=#moveID#&moveInternalLinkTo=#moveTo#",
    ));

    return array('content' => $content,
                 'content_left' => $contentLeft,
                 'content_top' => $contentTop,
                 'content_contenttype' => 'ContentItemIntLinks',
    );
  }
}


<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2016-03-18 09:23:59 +0100 (Fr, 18 Mär 2016) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

class ContentItemExtLinks extends ContentItem
{
  /**
   * Creates an external link if the POST parameter process_el_create is set.
   */
  private function _createExternalLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_el_create'])) {
      return;
    }

    $title = $post->readString('el_title', Input::FILTER_RIGHT_TITLE);
    $url = $post->readString('el_url', Input::FILTER_PLAIN);

    if (!$title || !$url) {
      $this->setMessage(Message::createFailure($_LANG['el_message_insufficient_input']));
      return;
    }

    // validate url protocol
    $valid = false;
    $protocols = $this->_configHelper->getVar('url_protocols', 'el');
    foreach ($protocols as $protocol)
    {
      if (mb_substr($url, 0, mb_strlen($protocol)) === $protocol) {
        $valid = true;
        break;
      }
    }

    if (!$valid) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['el_message_invalid_url_protocol'], implode(', ', $protocols))));
      return;
    }

    $sql = 'SELECT COUNT(ELID) + 1 '
         . "FROM {$this->table_prefix}externallink "
         . "WHERE FK_CIID = $this->page_id ";
    $position = $this->db->GetOne($sql);

    $sql = "INSERT INTO {$this->table_prefix}externallink "
         . '(ELTitle, ELUrl, ELPosition, FK_CIID) '
         . "VALUES('{$this->db->escape($title)}', '{$this->db->escape($url)}', $position, $this->page_id) ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['el_message_create_success']));

    unset($_POST['el_title'],
          $_POST['el_url']);
  }

  /**
   * Updates an external link if the POST parameter process_el_edit is set.
   */
  private function _updateExternalLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_el_edit'])) {
      return;
    }

    $ID = $post->readKey('process_el_edit');
    $title = $post->readString("el{$ID}_title", Input::FILTER_RIGHT_TITLE);
    $url = $post->readString("el{$ID}_url", Input::FILTER_PLAIN);

    if (!$title || !$url) {
      $this->setMessage(Message::createFailure($_LANG['el_message_insufficient_input']));
      $_GET['editExternalLinkID'] = $ID;
      return;
    }

    $sql = "UPDATE {$this->table_prefix}externallink "
         . "SET ELTitle = '{$this->db->escape($title)}', "
         . "    ELUrl = '{$this->db->escape($url)}' "
         . "WHERE ELID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['el_message_update_success']));
  }

  /**
   * Moves an external link if the GET parameters moveExternalLinkID and moveExternalLinkTo are set.
   */
  private function _moveExternalLink()
  {
    global $_LANG;

    if (!isset($_GET['moveExternalLinkID'], $_GET['moveExternalLinkTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveExternalLinkID'];
    $moveTo = (int)$_GET['moveExternalLinkTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}externallink",
                                         'ELID', 'ELPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['el_message_move_success']));
    }
  }

  /**
   * Deletes an external link if the GET parameter deleteExternalLinkID is set.
   */
  private function _deleteExternalLink()
  {
    global $_LANG;

    if (!isset($_GET['deleteExternalLinkID'])) {
      return;
    }

    $id = (int)$_GET['deleteExternalLinkID'];

    // determine position of deleted external link
    $sql = 'SELECT ELPosition '
         . "FROM {$this->table_prefix}externallink "
         . "WHERE ELID = $id ";
    $deletedPosition = $this->db->GetOne($sql);

    // delete external link
    $sql = "DELETE FROM {$this->table_prefix}externallink "
         . "WHERE ELID = $id ";
    $result = $this->db->query($sql);

    // move following external links one position up
    $sql = "UPDATE {$this->table_prefix}externallink "
         . 'SET ELPosition = ELPosition - 1 '
         . "WHERE FK_CIID = $this->page_id "
         . "AND ELPosition > $deletedPosition "
         . 'ORDER BY ELPosition ASC ';
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['el_message_delete_success']));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    // not used, see method _updateExternalLink()
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // not used, see method _deleteExternalLink()
  }

  public function get_content($params = array())
  {
    global $_LANG;

    // Perform create/update/move/delete of an external link if necessary
    $this->_createExternalLink();
    $this->_updateExternalLink();
    $this->_moveExternalLink();
    $this->_deleteExternalLink();

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}externallink",
                                         'ELID', 'ELPosition',
                                         'FK_CIID', $this->page_id);

    $editExternalLinkID = isset($_GET['editExternalLinkID']) ? (int)$_GET['editExternalLinkID'] : 0;
    $editExternalLinkData = array();

    // read links
    $externalLinkItems = array();
    $sql = 'SELECT ELID, ELTitle, ELUrl, ELPosition '
         . "FROM {$this->table_prefix}externallink "
         . "WHERE FK_CIID = $this->page_id "
         . 'ORDER BY ELPosition ';
    $result = $this->db->query($sql);
    $externalLinkCount = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['ELPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['ELPosition']);

      $class = 'normal';
      if ($row['ELID'] == $editExternalLinkID) {
        $class = 'edit';
      }

      $title = parseOutput($row['ELTitle']);
      $url = parseOutput($row['ELUrl']);
      $externalLinkItems[] = array(
        'el_title' => $title,
        'el_url' => $url,
        'el_id' => $row['ELID'],
        'el_position' => (int)$row['ELPosition'],
        'el_class' => $class,
        'el_edit_link' => "index.php?action=extlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;editExternalLinkID={$row['ELID']}",
        'el_move_up_link' => "index.php?action=extlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;moveExternalLinkID={$row['ELID']}&amp;moveExternalLinkTo=$moveUpPosition",
        'el_move_down_link' => "index.php?action=extlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;moveExternalLinkID={$row['ELID']}&amp;moveExternalLinkTo=$moveDownPosition",
        'el_delete_link' => "index.php?action=extlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;deleteExternalLinkID={$row['ELID']}",
      );

      // this row has to be edited
      if ($row['ELID'] == $editExternalLinkID) {
        $post = new Input(Input::SOURCE_POST);
        if (isset($_POST["el{$editExternalLinkID}_title"])) {
          $title = $post->readString("el{$editExternalLinkID}_title", Input::FILTER_RIGHT_TITLE);
        }
        if (isset($_POST["el{$editExternalLinkID}_url"])) {
          $url = $post->readString("el{$editExternalLinkID}_url", Input::FILTER_PLAIN);
        }
        $editExternalLinkData = array(
          'el_id' => $row['ELID'],
          'el_title_edit' => $title,
          'el_url_edit' => $url,
          'el_edit_label' => $_LANG['el_edit_label'],
          'el_button_cancel_label' => $_LANG['el_button_cancel_label'],
          'el_button_edit_label' => $_LANG['el_button_edit_label'],
        );
      }
    }
    $this->db->free_result($result);

    $contentLeft = '';
    $contentTop = $this->_getContentTop(self::ACTION_EXTERNALLINKS);

    $action = 'index.php';
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="' . $this->action . '" />';

    $this->tpl->load_tpl('content_links', 'content_extlinks.tpl');
    $this->tpl->parse_if('content_links', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('el'));
    $this->tpl->parse_if('content_links', 'entry_edit', $editExternalLinkData, $editExternalLinkData);
    $this->tpl->parse_loop('content_links', $externalLinkItems, 'entries');
    $content = $this->tpl->parsereturn('content_links', array(
      'el_action' => $action,
      'el_hidden_fields' => $hiddenFields,
      'el_function_label' => $_LANG['el_function_label'],
      'el_create_label' => $_LANG['el_create_label'],
      'el_title' => isset($_POST['el_title']) ? $_POST['el_title'] : '',
      'el_url' => isset($_POST['el_url']) ? $_POST['el_url'] : 'http://',
      'el_button_create_label' => $_LANG['el_button_create_label'],
      'el_existing_label' => $_LANG['el_existing_label'],
      'el_count' => $externalLinkCount,
      'el_title_label' => $_LANG['el_title_label'],
      'el_url_label' => $_LANG['el_url_label'],
      'el_edit_label' => $_LANG['el_edit_label'],
      'el_move_up_label' => $_LANG['el_move_up_label'],
      'el_move_down_label' => $_LANG['el_move_down_label'],
      'el_move_label' => $_LANG['el_move_label'],
      'el_delete_label' => $_LANG['el_delete_label'],
      'el_site' => $this->site_id,
      'el_delete_question_label' => $_LANG['el_delete_question_label'],
      'el_dragdrop_link_js' => "index.php?action=extlinks&site=$this->site_id&page=$this->page_id&moveExternalLinkID=#moveID#&moveExternalLinkTo=#moveTo#",
    ));

    return array('content' => $content,
                 'content_left' => $contentLeft,
                 'content_top' => $contentTop,
                 'content_contenttype' => 'ContentItemExtLinks',
    );
  }
}


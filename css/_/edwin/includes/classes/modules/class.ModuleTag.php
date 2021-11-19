<?php

/**
 * Tag Module Class
 *
 * $LastChangedDate: 2019-11-04 07:30:27 +0100 (Mo, 04 Nov 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ModuleTag extends Module
{
  protected $_prefix = 'ta';

  /**
   * The id of the selected tag group.
   *
   * @var int
   */
  private $_typeID = 0;

  /**
   * The tag global db row data object for current type id ModuleTag::$_typeID
   *
   * @see ModuleTag::_getTagGlobal()
   * @var stdClass|null
   */
  private $_tagGlobal = null;

  /**
   * Create a new tag item.
   *
   * @param Db $db
   *        The database object.
   * @param string $tablePrefix
   *        The table prefix
   * @param int $typeID
   *        The id of the tag group (tag_global) the new item belongs to.
   * @param string $title
   *        The tag title.
   * @param string $image1 [optional]
   *        The tag image path if available.
   *
   * @return int
   *         1 - Tag has been created successfully.
   *         0 - There has not been provided a valid title or tag type (group) id.
   *        -1 - There exists a tag with the specified title.
   */
  public static function createTag(Db $db, $tablePrefix, $typeID, $title, $image1 = '')
  {
    if (!$typeID || !$title) {
      return 0;
    }

    $sql = ' SELECT TAID '
         . " FROM {$tablePrefix}module_tag "
         . " WHERE TATitle LIKE '" . $title . "' ";
    $exists = $db->GetOne($sql);

    // There exists an attribute with the same title (ignoring uppercase/lowercase)
    if ($exists) {
      return -1;
    }

    $sql = ' SELECT max(TAPosition) AS max_position '
         . " FROM {$tablePrefix}module_tag "
         . ' WHERE FK_TAGID = ' . $typeID;
    $maxPos = (int)$db->GetOne($sql);
    $position = $maxPos + 1;

    $sql = " INSERT INTO {$tablePrefix}module_tag "
         . ' (TATitle, TAImage1, TAPosition, FK_TAGID) '
         . ' VALUES '
         . " ('{$db->escape($title)}', '{$db->escape($image1)}', $position, $typeID) ";
    $result = $db->query($sql);

    if ($result) {
      return 1;
    }

    return 0;
  }

  /**
   * Content handler
   */
  public function show_innercontent()
  {
    // Read the currently selected tag group from request or session and store
    // it within the session.
    $request = new Input(Input::SOURCE_REQUEST);
    $this->_typeID = $request->readInt('type');

    if (isset($_POST['process']) && $this->action[0]=='new') {
      $this->_createContent();
    }
    if (isset($_POST['process']) && isset($_POST['group_edit'])) {
      $this->_editGroup();
    }
    if ($request->readInt('did')) {
      $this->_deleteItem($request->readInt('did'));
    }
    if (isset($_POST['move_tags'])) {
      $this->_moveTags();
    }

    // Perform move of a tag if necessary.
    $this->_moveItem();

    if (empty($this->action[0])) {
      return $this->_listContent();
    } else {
      return $this->_getContent();
    }
  }

  protected function _getModuleUrlParts()
  {
    return array_merge(parent::_getModuleUrlParts(), array(
        'type' => $this->_typeID,
    ));
  }

  /**
   * Create content
   */
  private function _createContent()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $title = $post->readString('ta_title', Input::FILTER_PLAIN);
    $image1 = '';

    if ($this->_getTagGlobal() && $this->_getTagGlobal()->TAGNeedsImage) {
      $image1 = isset($_FILES['ta_image1']) ?
        $this->_storeImage($_FILES['ta_image1'], null, array($this->_prefix, 'ta'), 1, null, true, false) : '';

      if ($this->_getMessage()) { // failure while processing image
        return;
      }
    }

    $result = self::createTag($this->db, $this->table_prefix, $this->_typeID, $title, $image1);

    if ($result === 1)
    {
      // Clear the post data.
      $_POST['ta_title'] = '';
      $this->item_id = 0;

      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['ta_message_newitem_success']));
      }
      else {
        $this->setMessage(Message::createSuccess($_LANG['ta_message_newitem_success']));
      }
    }
    else if ($result === -1) {
      $this->setMessage(Message::createFailure($_LANG['ta_message_duplicate_title']));
    }
    else if ($result === 0) {
      $this->setMessage(Message::createFailure($_LANG['ta_message_insufficient_input']));
    }
  }

  /**
   * Edit a tag group
   */
  private function _editGroup()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $title = $post->readString('ta_type_title', Input::FILTER_PLAIN);
    $text = $post->readString('ta_type_text', Input::FILTER_CONTENT_TEXT);
    $availableOnContent = $post->exists('ta_type_content') ? 1 : 0;
    $needsImage = $post->exists('ta_type_needs_image') ? 1 : 0;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['ta_message_type_insufficient_input']));
    }
    else {
      $sql = " UPDATE {$this->table_prefix}module_tag_global "
           . " SET TAGTitle = '{$this->db->escape($title)}', "
           . "     TAGText = '{$this->db->escape($text)}', "
           . "     TAGContent = '$availableOnContent', "
           . "     TAGNeedsImage = '$needsImage' "
           . " WHERE TAGID = $this->_typeID ";
      $result = $this->db->query($sql);

      if ($result) {
        $this->setMessage(Message::createSuccess($_LANG["ta_message_edittype_success"]));
      }
    }
  }

  /**
   * Delete item.
   *
   * @param int $ID
   *        The item id.
   */
  private function _deleteItem($ID)
  {
    global $_LANG;

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}module_tag "
         . " WHERE TAID = :id";
    $row = $this->db->q($sql, array('id' => $ID))->fetch();

    if (!$row) {
      return;
    }

    if ($row['TAImage1']) {
      $this->_deleteImageFiles($row['TAImage1']);
    }

    $sql = " DELETE FROM {$this->table_prefix}module_tag "
         . ' WHERE TAID = ' . $ID;
    $this->db->query($sql);

    $sql = " UPDATE {$this->table_prefix}module_tag "
         . " SET TAPosition = TAPosition - 1 "
         . " WHERE TAPosition > :position "
         . "   AND FK_TAGID = :group";
    $this->db->q($sql, array(
      'position' => (int)$row['TAPosition'],
      'group'    => (int)$row['FK_TAGID'],
    ));

    $sql = " DELETE FROM {$this->table_prefix}contentitem_tg_image_tags "
         . ' WHERE FK_TAID = ' . $ID;
    $this->db->query($sql);

    $sql = " DELETE FROM {$this->table_prefix}contentitem_tag "
         . ' WHERE FK_TAID = ' . $ID;
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['ta_message_deleteitem_success']));
  }

  /**
   * Get content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    $title = $post->readString('ta_title', Input::FILTER_PLAIN, '');
    $function = 'new';

    $hiddenFields = '<input type="hidden" name="action" value="mod_tag" />'
                  . '<input type="hidden" name="action2" value="main;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="type" value="' . $this->_typeID . '" />';

    $this->tpl->load_tpl('content_tag', 'modules/ModuleTag.tpl');
    $this->tpl->parse_if('content_tag', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ta'));
    $this->tpl->parse_if('content_tag', 'needs_image', $this->_getTagGlobal() && $this->_getTagGlobal()->TAGNeedsImage);
    $content = $this->tpl->parsereturn('content_tag', array_merge(array (
      'ta_title'                      => $title,
      'ta_function_label'             => ($this->item_id ? $_LANG['ta_function_edit_label'] : $_LANG['ta_function_new_label']),
      'ta_function2_label'            => sprintf($_LANG['ta_function2_label'], $this->_getTagGlobal() ? $this->_getTagGlobal()->TAGTitle : ''),
      'ta_action'                     => 'index.php',
      'ta_required_resolution_label1' => $this->_getImageSizeInfo(array($this->_prefix, 'ta'), 1),
      'ta_hidden_fields'              => $hiddenFields,
      'module_action_boxes'           => $this->_getContentActionBoxes(),
    ), $_LANG2['ta']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  private function _deleteTagImage()
  {
    global $_LANG;

    $id = ed_http_input()->request()->readInt('editTagID');

    if ($id && ed_http_input()->request()->readInt('delete_image') == 1) {
      $sql = " SELECT * "
           . " FROM {$this->table_prefix}module_tag "
           . " WHERE TAID = :taid ";
      $result = $this->db->q($sql, array('taid' => $id));
      $row = $result->fetchObject();

      if(!$row) {
        return;
      }

      if ($row->TAImage1) {
        $sql = " UPDATE {$this->table_prefix}module_tag "
             . " SET TAImage1 = '' "
             . " WHERE TAID = :taid ";
        $this->db->q($sql, array('taid' => $id));

        $this->_deleteImageFiles($row->TAImage1);
        $this->setMessage(Message::createSuccess($_LANG['ta_message_success_image_deleted']));
      }
    }
  }

  /**
   * Updates a tag if the POST parameter process_ta_edit is set.
   */
  private function _updateTag()
  {
    global $_LANG;

    $id = ed_http_input()->post()->readKey('process_ta_edit');

    if (!$id) {
      return;
    }

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}module_tag "
         . " WHERE TAID = :taid ";
    $result = $this->db->q($sql, array('taid' => $id));
    $row = $result->fetchObject();

    if (!$row) {
      return;
    }

    $title = ed_http_input()->post()->readString('ta'.$id.'_title', Input::FILTER_PLAIN);

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['ta_message_insufficient_input']));
    }

    $image1 = $row->TAImage1;

    if ($this->_getTagGlobal() && $this->_getTagGlobal()->TAGNeedsImage) {
      $image1 = isset($_FILES['ta'.$id.'_image1']) ?
        $this->_storeImage($_FILES['ta'.$id.'_image1'], null, array($this->_prefix, 'ta'), 1, null, true, false) : '';

      if ($this->_getMessage()) { // failure while processing image
        return;
      }
    }

    $sql = " UPDATE {$this->table_prefix}module_tag "
         . " SET TATitle = '{$this->db->escape($title)}', "
         . "     TAImage1 = '{$this->db->escape($image1)}' "
         . ' WHERE TAID = ' . $id ;
    $result = $this->db->query($sql);

    $this->_redirect(
      $this->_parseUrl(null, array('editTagID' => $id)),
      Message::createSuccess($_LANG['ta_message_edititem_success'])
    );
  }

  /**
   * Show list
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $this->_updateTag();
    $this->_deleteTagImage();

    $editTagID = ed_http_input()->request()->readInt('editTagID', 0);
    $editTagData = array();
    // Read available tag groups
    $sql = ' SELECT TAGID, TAGTitle, TAGText, TAGContent, TAGNeedsImage '
         . " FROM {$this->table_prefix}module_tag_global "
         . ' WHERE FK_SID = ' . $this->site_id
         . ' ORDER BY TAGPosition ASC ';
    $resultTagGroups = $this->db->query($sql);
    $groupsAvailable = $this->db->num_rows($resultTagGroups);
    $chooseType = '';
    $typeTitle = '';
    $typeText = '';
    $typeContent = 0;
    $typeNeedsImage = 0;
    $chooseGroup = '';
    while ($row = $this->db->fetch_row($resultTagGroups)) {
      $chooseType .= "<option value='" . $row['TAGID'] . "'";

      if ($this->_typeID == $row['TAGID'] || !$this->_typeID) {
        $chooseType .= " selected='selected' ";
        $this->_typeID = $row['TAGID'];
        $typeTitle = parseOutput($row['TAGTitle']);
        $typeText = parseOutput($row['TAGText']);
        $typeContent = (int)$row['TAGContent'];
        $typeNeedsImage = (int)$row['TAGNeedsImage'];
      }
      $chooseType .= ">" . $row['TAGTitle'] . "</option>\n";

      if ($this->_typeID != $row['TAGID']) {
        $chooseGroup .= '<option value="'.$row['TAGID'].'">'.parseOutput($row['TAGTitle']).'</option>'."\n";
      }
    }
    $chooseType = "<select name='type' "
                . "        class='form-control ed_from_control_minimal' "
                . '        onChange="if (this.options[this.selectedIndex].value != ' . $this->_typeID . ')'
                . '                  { document.forms.choose_form.submit(); }">'
                . "\n" . $chooseType
                . '</select>';

    if (!$groupsAvailable) {
      $this->setMessage(Message::createFailure($_LANG["ta_message_no_tag_types"]));
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tag",
                                         'TAID', 'TAPosition',
                                         'FK_TAGID', $this->_typeID);

    $action = "index.php?action=mod_tag";
    $itemAction = "index.php?action=mod_tag&amp;type=$this->_typeID";

    // Read tag values
    $items = array ();
    $sql = ' SELECT TAID, TATitle, TAImage1, TAPosition '
         . " FROM {$this->table_prefix}module_tag "
         . ' WHERE FK_TAGID = ' . $this->_typeID
         . ' ORDER BY TAPosition ASC ';
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['TAPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['TAPosition']);

      $class = 'normal';
      if ($row['TAID'] == $editTagID) {
        $class = 'edit';
      }

      $items[] = array(
        'ta_title' => parseOutput($row['TATitle']),
        'ta_id' => $row['TAID'],
        'ta_class' => $class,
        'ta_position' => $row['TAPosition'],
        'ta_content_link' => $itemAction . '&amp;action2=edit&amp;page=' . $row['TAID'],
        'ta_delete_link' => $itemAction . '&amp;did=' . $row['TAID'],
        'ta_move_up_link' => $itemAction . "&amp;moveItemID={$row['TAID']}&amp;moveItemTo=$moveUpPosition",
        'ta_move_down_link' => $action . "&amp;moveItemID={$row['TAID']}&amp;moveItemTo=$moveDownPosition",
        'ta_edit_link' => $itemAction."&amp;editTagID={$row['TAID']}",
      );

      // this row has to be edited
      if ($row['TAID'] == $editTagID) {
        $editTagData = array(
          'ta_action_update'                    => $this->_parseUrl(null, array('editTagID' => $row['TAID'])),
          'ta_id_edit'                          => $row['TAID'],
          'ta_title_edit'                       => parseOutput($row['TATitle']),
          'ta_image1_edit'                      => $row['TAImage1'] ? 1 : 0,
          'ta_image1_src_edit'                  => $row['TAImage1'] ? '../' . $row['TAImage1'] : 'img/no_image.png',
          'ta_image1_required_resolution_label' => $this->_getImageSizeInfo(array($this->_prefix, 'ta'), 1),
          'ta_image1_delete_url'                => $row['TAImage1'] ? $this->_parseUrl('', array('editTagID' => $editTagID, 'delete_image' => 1)) : '',
          'ta_button_cancel_label'              => $_LANG['ta_button_cancel_label'],
          'ta_button_edit_label'                => $_LANG['ta_button_edit_label'],
        );
      }
    }
    $this->db->free_result($result);

    if (!$items) {
      $this->setMessage(Message::createFailure($_LANG['ta_message_no_tag']));
    }

    $hiddenFields = '';
    $taTypeHiddenFields = $hiddenFields.'<input type="hidden" name="group_edit" value="1" /><input type="hidden" name="type" value="'.$this->_typeID.'" /><input type="hidden" name="site" value="'.$this->site_id.'" />';

    $this->tpl->load_tpl('content_tag', 'modules/ModuleTag_list.tpl');
    // fill edit entry form with existing data
    $this->tpl->parse_if('content_tag', 'entry_edit', $editTagData, $editTagData);
    $this->tpl->parse_if('content_tag', 'entry_edit_image_available', $this->_getTagGlobal() && $this->_getTagGlobal()->TAGNeedsImage || $editTagData && $editTagData['ta_image1_edit']);
    $this->tpl->parse_if('content_tag', 'entry_edit_image_delete', $editTagData && $editTagData['ta_image1_edit']);
    $this->tpl->parse_if('content_tag', 'entry_edit_upload_available', $this->_getTagGlobal() && $this->_getTagGlobal()->TAGNeedsImage);

    $this->tpl->parse_if('content_tag', 'ta_tag_global_available', $groupsAvailable);
    $this->tpl->parse_if('content_tag', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ta'));
    $this->tpl->parse_loop('content_tag', $items, 'tag_items');
    $content = $this->tpl->parsereturn('content_tag', array_merge(array (
      'ta_action'             => $action,
      'ta_hidden_fields'      => $hiddenFields,
      'ta_type_hidden_fields' => $taTypeHiddenFields,
      'ta_type_title'         => $typeTitle,
      'ta_type_text'          => $typeText,
      'ta_type_content'       => $typeContent ? 'checked="checked"' : '',
      'ta_type_needs_image'   => $typeNeedsImage ? 'checked="checked"' : '',
      'ta_site_selection'     => parent::_parseModuleSiteSelection('tag', $_LANG['ta_site_label']),
      'ta_dragdrop_link_js'   => "index.php?action=mod_tag&site=$this->site_id&type=$this->_typeID&moveItemID=#moveID#&moveItemTo=#moveTo#",
      'ta_choose_type'        => $chooseType,
      // only show action box if at least one taggroup and tag item is available
      'ta_module_action_box'  => ($groupsAvailable > 1 && $items) ? $this->_getListContentActionBoxes($chooseGroup) : '',
    ), $_LANG2['ta']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Loads action box of module tag
   * @param $chooseGroup
   *        drop down list options - contains tag groups
   * @return parsed template of action box
   */
  private function _getListContentActionBoxes($chooseGroup)
  {
    $this->tpl->load_tpl('module_action_box', 'modules/ModuleTag_action_box.tpl');
    $actionBox = $this->tpl->parsereturn('module_action_box', array(
      'ta_choose_group' => $chooseGroup,
      )
    );

    return $actionBox;
  }

  /**
   * Moves selected tags to selected taggroup
   */
  private function _moveTags() {
    global $_LANG;

    $post = new Input(Input::SOURCE_REQUEST);

    $tagGroupId = $post->readInt('ta_choose_group');
    $tagIds = $post->readArrayIntToInt('ta_tags');
    if ($tagGroupId && !empty($tagIds)) {
      foreach ($tagIds as $tagId) {
        $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tag",
                                             'TAID', 'TAPosition',
                                             'FK_TAGID', $this->_typeID);
        // first, move tag of current group to the end (last position)
        $moved = $positionHelper->move($tagId, $positionHelper->getHighestPosition());

        $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tag",
                                             'TAID', 'TAPosition',
                                             'FK_TAGID', $tagGroupId);
        // then move tag to selected group
        $position = ($positionHelper->getHighestPosition()) ? ($positionHelper->getHighestPosition() + 1) : 1;
        $sql = "UPDATE {$this->table_prefix}module_tag "
             . "SET FK_TAGID = {$tagGroupId}, "
             . "    TAPosition = {$position} "
             . "WHERE TAID = {$tagId}";
        $this->db->query($sql);
      }
      $this->setMessage(Message::createSuccess($_LANG['ta_message_tags_moved']));
    } else {
      $this->setMessage(Message::createFailure($_LANG['ta_message_nothing_moved']));
    }
  }

  /**
   * Move an item if the GET parameters moveItemID and moveItemTo are set.
   */
  private function _moveItem()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveItemID');
    $moveTo = $get->readInt('moveItemTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tag",
                                         'TAID', 'TAPosition',
                                         'FK_TAGID', $this->_typeID);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ta_message_move_success']));
    }
  }

  /**
   * @return false|\stdClass|null
   */
  private function _getTagGlobal()
  {
    if ($this->_tagGlobal === null && (int)$this->_typeID) {

      $sql = " SELECT TAGID, TAGTitle, TAGText, TAGPosition, TAGContent, TAGNeedsImage "
           . " FROM {$this->table_prefix}module_tag_global "
           . " WHERE TAGID = :id";

      $this->_tagGlobal = $this->db->q($sql, array('id' => $this->_typeID))
        ->fetchObject();
    }

    return $this->_tagGlobal;
  }
}
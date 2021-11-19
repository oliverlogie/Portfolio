<?php

/**
 * ModuleAttributeGlobal
 *
 * $LastChangedDate: 2017-08-21 13:04:29 +0200 (Mo, 21 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleAttributeGlobal extends Module
{
  protected $_prefix = 'ag';

  public function show_innercontent ()
  {
    $this->_move();
    $this->_create();
    $this->_edit();

    if (empty($this->action[0])) {
      return $this->_showList();
    } else {
      return $this->_getContent();
    }
  }

  /**
   * Creates a new attribute group
   */
  private function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    // new attribute group
    if (!$post->exists('process') || $this->action[0] != 'new')
      return;

    $title = $post->readString('ag_title', Input::FILTER_PLAIN);
    $type = $post->readInt('ag_content_type');
    $image = ($post->exists('ag_images')) ? 1 : 0;
    $text = $post->readString('ag_text', Input::FILTER_PLAIN);
    $group = $post->readInt('ag_group', 0);
    $relationship = $post->readInt('ag_relationship', 0);

    if (!$title || !$type) {
      $this->setMessage(Message::createFailure($_LANG["ag_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT AID "
         . " FROM {$this->table_prefix}module_attribute_global "
         . " WHERE ATitle LIKE '$title' ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["ag_message_failure_existing"]));
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_attribute_global",
                                         'AID', 'APosition',
                                         'FK_SID', $this->site_id);
    $position = $positionHelper->getHighestPosition() + 1;

    $sql = " INSERT INTO {$this->table_prefix}module_attribute_global "
         . " ( ATitle, AText, APosition, FK_SID, FK_CTID, AImages ) "
         . " VALUES( '{$this->db->escape($title)}', "
         . " '{$this->db->escape($text)}', '$position', '$this->site_id', '$type', '$image') ";
    $this->db->query($sql);
    $id = $this->db->insert_id();

    $item = array(
      'id' => $id,
      'siteId' => $this->site_id,
    );
    $success = $this->_updateRelationship($item, $relationship, $group);

    if (!$success) {
      $this->setMessage(Message::createFailure($_LANG['ag_message_failure_existing_relations']));
      return;
    }

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_parseUrl(''),
          Message::createSuccess($_LANG['ag_message_new_item_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $id)),
          Message::createSuccess($_LANG['ag_message_new_item_success']));
    }

    $this->setMessage(Message::createSuccess($_LANG['ag_message_new_item_success']));
  }

  /**
   * Edit an attribute group
   */
  private function _edit()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    // new attribute group
    if (!$post->exists('process') || $this->action[0] != 'edit')
      return;

    $title = $post->readString('ag_title', Input::FILTER_PLAIN);
    $images = ($post->exists('ag_images')) ? 1 : 0;
    $text = $post->readString('ag_text', Input::FILTER_PLAIN);
    $group = $post->readInt('ag_group', 0);
    $relationship = $post->readInt('ag_relationship', 0);

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["ag_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT AID "
         . " FROM {$this->table_prefix}module_attribute_global "
         . " WHERE ATitle LIKE '$title' "
         . "   AND AID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["ag_message_failure_existing"]));
      return;
    }

    $sql = " UPDATE {$this->table_prefix}module_attribute_global "
         . "    SET ATitle = '{$this->db->escape($title)}', "
         . "        AText = '{$this->db->escape($text)}', "
         . "        AImages = $images "
         . " WHERE AID = $this->item_id ";
    $result = $this->db->query($sql);

    $item = array(
      'id' => $this->item_id,
      'siteId' => $this->site_id,
    );
    $success = $this->_updateRelationship($item, $relationship, $group);

    if (!$success)
      $this->setMessage(Message::createFailure($_LANG['ag_message_failure_existing_relations']));
    else if (!$this->_getMessage() && $result)
    {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_parseUrl(''),
            Message::createSuccess($_LANG['ag_message_edit_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['ag_message_edit_item_success']));
      }
    }
  }


  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    // edit attribute -> load data
    if ($this->item_id) {
      $sql = " SELECT AID, ATitle, AText, AImages, FK_CTID, FK_AGID "
           . " FROM {$this->table_prefix}module_attribute_global "
           . " WHERE AID = $this->item_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);

      $title = $row['ATitle'];
      $text = $row['AText'];
      $images = $row['AImages'];
      $type = $row['FK_CTID'];
      $relationship = (int)$row['FK_AGID'];
      $linkedItem = 0;

      $this->db->free_result($result);
      $function = 'edit';
    }
    else // new attribute
    {
      $title = $post->readString('ag_title', Input::FILTER_PLAIN);
      $text = $post->readString('ag_text', Input::FILTER_PLAIN);
      $images = $post->exists('ag_images') ? 1 : 0;
      $type = $post->readInt('ag_content_type', 0);
      $relationship = $post->readInt('ag_link_group', 0);
      $linkedItem = $post->readInt('ag_link_item', 0);
      $function = 'new';
    }

    $typeSelect = '';
    $tmpOptions = $_LANG['ag_content_type_options'];
    foreach ($tmpOptions as $key => $val) {
      // display all available content types for new items only
      if ($function == 'new')
      {
        $typeSelect .= '<option value="' . $key . '"';
        if ($type == $key)
          $typeSelect .= ' selected="selected" ';

        $typeSelect .= '>' . $val . '</option>';
      }
      // for edited attribute group, display selected content type only
      else if ($type == $key) {
        $typeSelect .= '<option value="' . $key . '" selected="selected" >' . $val . '</option>';
      }
    }
    $typeSelect = '<select id="ag_content_type" name="ag_content_type" class="form-control">'.$typeSelect.'</select>';

    // get attribute global link group select //////////////////////////////////
    $sql = " SELECT AID, ATitle, FK_SID, FK_AGID "
         . " FROM {$this->table_prefix}module_attribute_global "
         . " ORDER BY FK_SID, ATitle ASC ";
    $result = $this->db->query($sql);

    $relationships = array();  // groups of linked attribute groups
    $groupOptions = ''; // attribute groups not yet linked
    while ($row = $this->db->fetch_row($result)) {
      $id = $row['AID'];
      $groupId = $row['FK_AGID'];
      if (!$groupId) {
        if ($id == $this->item_id || $row['FK_SID'] == $this->site_id)
          continue;
        $groupOptions .= '<option value="' . $id. '">' . parseOutput($row['ATitle']) . '</option>';
      }
      else {
        $relationships[$groupId][] = $row;
      }
    }
    $this->db->free_result($result);

    $groupOptions = '<option value="0">' . $_LANG['ag_option_none_label'] . '</option>' . $groupOptions;

    $relationshipOptions = '<option value="0">' . $_LANG['ag_option_none_label'] . '</option>';
    if ($relationships) {
      foreach ($relationships as $key => $tmpGroups) {
        $tmp = array();
        foreach ($tmpGroups as $row ) {
          $tmp[] = $row['ATitle'];
        }
        $relationshipOptions .= '<option value="' . $row['FK_AGID'] . '" '
                            . ( $key == $relationship ? 'selected="selected"' : '' )
                            . '>' . parseOutput(implode(' - ', $tmp)) . '</option>';
      }
    }
    ////////////////////////////////////////////////////////////////////////////

    $hiddenFields = '<input type="hidden" name="action" value="mod_attribute" />'
                  . '<input type="hidden" name="action2" value="global;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_attribute', 'modules/ModuleAttributeGlobal.tpl');
    $this->tpl->parse_if('content_attribute', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ag'));
    $content = $this->tpl->parsereturn('content_attribute', array_merge(array (
      'ag_title'            => parseOutput($title),
      'ag_text'             => parseOutput($text),
      'ag_images_checked'   => $images ? 'checked="checked"' : '',
      'ag_content_type'     => $typeSelect,
      'ag_relationship_options' => $relationshipOptions,
      'ag_group_options'    => $groupOptions,
      'ag_hidden_fields'    => $hiddenFields,
      'ag_function_label'   => $this->item_id ? $_LANG['ag_function_edit_label'] : $_LANG['ag_function_new_label'],
      'ag_action'           => "index.php",
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['ag']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  /**
   * Moves an attribute group if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if (!$get->exists('moveID', 'moveTo')) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_attribute_global",
                                         'AID', 'APosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ag_message_move_success']));
    }
  }

  /**
   * Shows a list containing all attribute groups.
   *
   * @return array
   *         Contains backend content.
   */
  private function _showList()
  {
    global $_LANG, $_LANG2;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_attribute_global",
                                         'AID', 'APosition',
                                         'FK_SID', $this->site_id);

    $sql = " SELECT AID, ATitle, AText, APosition "
         . " FROM {$this->table_prefix}module_attribute_global "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY APosition ASC ";
    $result = $this->db->query($sql);

    $attrGlobalCount = $this->db->num_rows($result);
    $attrGlobals = array();
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result))
    {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['APosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['APosition']);

      $attrGlobals[$row['AID']] = array(
        'ag_id'             => $row['AID'],
        'ag_position'       => $row['APosition'],
        'ag_title'          => parseOutput($row['ATitle']),
        'ag_text'           => parseOutput($row['AText']),
        'ag_edit_link'      => "index.php?action=mod_attribute&amp;action2=global;edit&amp;site=$this->site_id&amp;page={$row['AID']}",
        'ag_move_up_link'   => "index.php?action=mod_attribute&amp;action2=global&amp;site=$this->site_id&amp;moveID={$row['AID']}&amp;moveTo=$moveUpPosition",
        'ag_move_down_link' => "index.php?action=mod_attribute&amp;action2=global&amp;site=$this->site_id&amp;moveID={$row['AID']}&amp;moveTo=$moveDownPosition",
        'ag_delete_link'    => "index.php?action=mod_attribute&amp;action2=global&amp;site=$this->site_id&amp;deleteID={$row['AID']}",
      );
    }
    $this->db->free_result($result);
    if (!$attrGlobals)
      $this->setMessage(Message::createFailure($_LANG['ag_message_no_attribute_groups']));

    // parse list template
    $this->tpl->load_tpl('attribute_global', 'modules/ModuleAttributeGlobal_list.tpl');

    $this->tpl->parse_if('attribute_global', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ag'));
    $this->tpl->parse_loop('attribute_global', $attrGlobals, 'entries');
    $ag_content = $this->tpl->parsereturn('attribute_global', array_merge(array(
      'ag_site' => $this->site_id,
      'ag_action' => 'index.php?action=mod_attribute&amp;action2=global',
      'ag_site_selection' => $this->_parseModuleSiteSelection('attribute', $_LANG['ag_site_label'], 'global'),
      'ag_list_label' => $_LANG['ag_function_list_label'],
      'ag_list_label2' => $_LANG['ag_function_list_label2'],
      'ag_dragdrop_link_js' => "index.php?action=mod_attribute&action2=global&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
    ), $_LANG2['ag']));

    return array(
      'content'      => $ag_content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Update attribute group relationship, if both parameters $relationship and
   * $targetItem are specified, the group will be added to relationship,
   * while $targetItem will be ignored.
   *
   * @param array $item
   *        - id : the id of the attribute group to update relationship for
   *        - siteId : the site id of the site the group belongs to
   * @param int $relationship
   *        The id of the relationship to add attribute group to
   * @param int $targetItem
   *        The id of the group to create a new relation with
   *
   * @return void
   */
  private function _updateRelationship($item, $relationship, $targetItem)
  {
    global $_LANG;

    $id = $item['id'];
    $siteId = $item['siteId'];

    // get group relation data
    $sql = " SELECT FK_AGID "
         . " FROM {$this->table_prefix}module_attribute_global "
         . " WHERE AID = $this->item_id ";
    $old = $this->db->GetOne($sql);

    // do some security checks /////////////////////////////////////////////////
    // check if attribute relations exist for current attribute group's relation
    if ($old) {
      $sql = " SELECT COUNT(*) "
           . " FROM {$this->table_prefix}module_attribute "
           . " JOIN {$this->table_prefix}module_attribute_global "
           . "      ON FK_AID = AID "
           . " WHERE FK_ALID != 0 "
           . "   AND FK_AGID = $old ";
      $count = (int)$this->db->GetOne($sql);
      if ($count)
        return false;
    }

    // check if target relation contains attributes with relations defined
    if ($relationship) {
      $sql = " SELECT COUNT(*) "
           . " FROM {$this->table_prefix}module_attribute "
           . " JOIN {$this->table_prefix}module_attribute_global "
           . "      ON FK_AID = AID "
           . " WHERE FK_ALID != 0 "
           . "   AND FK_AGID = $relationship ";
      $count = (int)$this->db->GetOne($sql);
      if ($count)
        return false;
    }
    ////////////////////////////////////////////////////////////////////////////

    // if the group's relationship changed / was removed
    if ($old && $old != $relationship) {
      $sql = " SELECT AID, FK_SID, FK_AGID "
           . " FROM {$this->table_prefix}module_attribute_global "
           . " WHERE FK_AGID = $old ";
      $oldItems = $this->db->GetAssoc($sql);
      // there will be only one group left, after deleting current group, so we
      // delete relationship and remove all group items
      if (count($oldItems) <= 2) {
        $sql = " SELECT AGPosition "
             . " FROM {$this->table_prefix}module_attribute_global_link_group "
             . " WHERE AGID = $old ";
        $pos = $this->db->GetOne($sql);

        $sql = " DELETE "
             . " FROM {$this->table_prefix}module_attribute_global_link_group "
             . " WHERE AGID = $old ";
        $this->db->query($sql);

        $sql = " UPDATE {$this->table_prefix}module_attribute_global_link_group "
             . " SET AGPosition = AGPosition - 1 "
             . " WHERE AGPosition > $pos ";
        $this->db->query($sql);

        $sql = " UPDATE {$this->table_prefix}module_attribute_global "
             . " SET FK_AGID = 0 "
             . " WHERE FK_AGID = $old ";
        $this->db->query($sql);
      }
      // remove current group from relationship
      else {
        $sql = " UPDATE {$this->table_prefix}module_attribute_global "
             . " SET FK_AGID = 0 "
             . " WHERE AID = $id ";
        $this->db->query($sql);
      }
    }

    // existing relationship
    if ($relationship) {
      // remove group from same site from relationship as only items from
      // different pages should be linked
      $sql = " UPDATE {$this->table_prefix}module_attribute_global "
           . " SET FK_AGID = 0 "
           . " WHERE FK_AGID = $relationship "
           . "   AND FK_SID = $siteId ";
      $this->db->query($sql);

      // add new group item to relationship
      $sql = " UPDATE {$this->table_prefix}module_attribute_global "
           . " SET FK_AGID = $relationship "
           . " WHERE AID = $id "
           . "   AND FK_SID = $siteId ";
      $this->db->query($sql);
    }
    // new relationship
    else if ($targetItem) {
      $sql = " SELECT MAX(AGPosition) "
           . " FROM {$this->table_prefix}module_attribute_global_link_group ";
      $pos = (int)$this->db->GetOne($sql) + 1;

      $sql = " INSERT INTO {$this->table_prefix}module_attribute_global_link_group "
           . " (AGPosition) VALUES ($pos) ";
      $this->db->query($sql);
      $newRelation = $this->db->insert_id();

      // add new group item to relationship
      $sql = " UPDATE {$this->table_prefix}module_attribute_global "
           . " SET FK_AGID = $newRelation "
           . " WHERE AID IN ( $id, $targetItem ) ";
      $this->db->query($sql);
    }

    return true;
  }
}
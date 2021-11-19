<?php

/**
 * ModuleMultimediaLibraryCategory
 *
 * $LastChangedDate: 2015-09-28 10:25:48 +0200 (Mo, 28 Sep 2015) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleMultimediaLibraryCategory extends Module
{
  protected $_prefix = 'mc';

  /**
   * The shortname of the submodule.
   *
   * @var string
   */
  private $_subModuleShortname = 'category';

  /**
   * @see Module::show_innercontent()
   */
  public function show_innercontent ()
  {
    $this->_create();
    $this->_edit();
    $this->_delete();
    $this->_move();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->_getContent();
    }
    else {
      return $this->_listContent();
    }
  }

  /**
   * Creates a new item
   */
  private function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new')
      return;

    $title = $post->readString('mc_title', Input::FILTER_PLAIN);

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['mc_message_failure_no_title']));
      return;
    }

    $sql = " SELECT MCID "
         . " FROM {$this->table_prefix}module_medialibrary_category "
         . " WHERE MCTitle LIKE '$title' "
         . "   AND FK_SID = $this->site_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['mc_message_failure_existing']));
      return;
    }
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category",
                                         'MCID', 'MCPosition',
                                         'FK_SID', $this->site_id);
    $position = $positionHelper->getHighestPosition() + 1;

    $sqlArgs = array (
      'MCTitle' => "'{$this->db->escape($title)}'",
      'MCIdentifier' => "'{$this->db->escape($this->_generateUrlIdentifier($title))}'",
      'FK_SID'  => $this->site_id,
      'MCPosition' => $position,
    );

    $sqlFields = implode(',', array_keys($sqlArgs));
    $sqlValues = implode(',', array_values($sqlArgs));

    $sql = " INSERT INTO {$this->table_prefix}module_medialibrary_category ($sqlFields) "
         . " VALUES ($sqlValues)";
    $result = $this->db->query($sql);
    $this->item_id = $this->db->insert_id();

    if ($result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['mc_message_new_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['mc_message_new_item_success']));
      }
    }
  }

  /**
   * Delete an item and all related multimedia boxes,
   * if $_GET parameter 'deleteID' is set
   */
  private function _delete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('deleteID');

    if (!$id)
      return;

    $sql = " SELECT MCID "
         . " FROM {$this->table_prefix}module_medialibrary_category "
         . " WHERE FK_SID = $this->site_id "
         . "   AND MCID = $id ";
    $exists = $this->db->GetOne($sql);

    if (!$exists)
      return;

    $sql = " DELETE mc.*, ca.* "
         . " FROM  {$this->table_prefix}module_medialibrary_category mc "
         . " LEFT JOIN {$this->table_prefix}module_medialibrary_category_assignment ca "
         . "   ON ca.FK_MCID = $id "
         . " WHERE MCID = $id ";
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['mc_message_delete_item_success']));
  }

  /**
   * Edit an attribute group
   */
  private function _edit()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit')
      return;

    $title = $post->readString('mc_title', Input::FILTER_PLAIN);

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["mc_message_failure_no_title"]));
      return;
    }

      $sql = " SELECT MCID "
           . " FROM {$this->table_prefix}module_medialibrary_category "
           . " WHERE MCTitle LIKE '$title' "
           . "   AND FK_SID = $this->site_id "
           . "   AND MCID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['mc_message_failure_existing']));
      return;
    }

    $sql = " UPDATE {$this->table_prefix}module_medialibrary_category "
         . "    SET MCTitle = '{$this->db->escape($title)}', "
         . "        MCIdentifier = '{$this->db->escape($this->_generateUrlIdentifier($title))}' "
         . " WHERE MCID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $this->db->query($sql);

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['mc_message_edit_item_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
          Message::createSuccess($_LANG['mc_message_edit_item_success']));
    }
  }

  /**
   * Converts the category title into an identifier, that can be used in an URL.
   * The purpose is the generation of an SEO URL matching the title.
   *
   * @param string $titleToPrepare
   * @return string
   *         The prepared identifier.
   */
  private function _generateUrlIdentifier($titleToPrepare)
  {
    $newTitle = ResourceNameGenerator::directory($titleToPrepare);

    $serial = 0;
    $existingItem = false;
    while ($existingItem !== null)
    {
      $title = $newTitle;

      // add a serial number to the path (if we have tried without a serial number already)
      if ($serial) {
        $title .= $serial;
      }
      $serial ++;

      // check if there already exists another content item with this path
      // check for all paths on this site
      $sql = ' SELECT MCID '
           . " FROM {$this->table_prefix}module_medialibrary_category "
           . " WHERE FK_SID = $this->site_id "
           . "   AND MCIdentifier = '$title'";

      $existingItem = $this->db->GetOne($sql);
    }

    return $title;
  }

  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    // edit item -> load data
    if ($this->item_id) {
      $sql = " SELECT MCTitle "
           . " FROM {$this->table_prefix}module_medialibrary_category "
           . " WHERE FK_SID = $this->site_id "
           . "   AND MCID = $this->item_id ";
      $row = $this->db->GetRow($sql);
      $title = $row['MCTitle'];

      $function = 'edit';
    }
    else { // new item
      $title = $post->readString('mc_title', Input::FILTER_PLAIN);

      $function = 'new';
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_medialibrary" />'
                  . '<input type="hidden" name="action2" value="'.$this->_subModuleShortname.';' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_mc', 'modules/ModuleMultimediaLibraryCategory.tpl');
    $this->tpl->parse_if('content_mc', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('mc'));
    $mc_content = $this->tpl->parsereturn('content_mc', array_merge(array (
      'mc_title'            => parseOutput($title),
      'mc_hidden_fields'    => $hiddenFields,
      'mc_function_label'   => $this->item_id ? $_LANG['mc_function_edit_label'] : $_LANG['mc_function_new_label'],
      'mc_function_label2'  => $this->item_id ? $_LANG['mc_function_edit_label2'] : $_LANG['mc_function_new_label2'],
      'mc_action'           => "index.php",
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['mc']));

    return array(
        'content'      => $mc_content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  /**
   * Shows a list containing all multimedia library types
   *
   * @return array
   *         Contains backend content.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category",
                                         'MCID', 'MCPosition',
                                         'FK_SID', $this->site_id);

    $sql = " SELECT MCID, MCTitle, MCPosition "
         . " FROM {$this->table_prefix}module_medialibrary_category "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY MCPosition ASC ";
    $result = $this->db->query($sql);

    $count = $this->db->num_rows($result);
    $items = array();
    while ($row = $this->db->fetch_row($result))
    {
      $tmpId = (int)$row['MCID'];

      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['MCPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['MCPosition']);

      $items[$tmpId] = array(
        'mc_id'             => $tmpId,
        'mc_title'          => parseOutput($row['MCTitle']),
        'mc_position'       => $row['MCPosition'],
        'mc_delete_link'    => "index.php?action=mod_medialibrary&amp;action2=".$this->_subModuleShortname."&amp;site=$this->site_id&amp;deleteID={$tmpId}",
        'mc_edit_link'      => "index.php?action=mod_medialibrary&amp;action2=".$this->_subModuleShortname.";edit&amp;site=$this->site_id&amp;page={$tmpId}",
        'mc_move_up_link'     => "index.php?action=mod_medialibrary&amp;action2=".$this->_subModuleShortname."&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveUpPosition",
        'mc_move_down_link'   => "index.php?action=mod_medialibrary&amp;action2=".$this->_subModuleShortname."&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveDownPosition",
      );
    }
    $this->db->free_result($result);

    if (!$items) {
      $this->setMessage(Message::createFailure($_LANG['mc_message_no_items']));
    }

    // parse list template
    $this->tpl->load_tpl('content_mc', 'modules/ModuleMultimediaLibraryCategory_list.tpl');
    $this->tpl->parse_if('content_mc', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('mc'));
    $this->tpl->parse_loop('content_mc', $items, 'entries');
    $mc_content = $this->tpl->parsereturn('content_mc', array_merge(array(
      'mc_site'             => $this->site_id,
      'mc_action'           => 'index.php?action=mod_medialibrary&amp;action2='.$this->_subModuleShortname,
      'mc_site_selection'   => $this->_parseModuleSiteSelection('medialibrary', $_LANG['mc_site_label'], $this->_subModuleShortname),
      'mc_dragdrop_link_js' => "index.php?action=mod_medialibrary&action2=".$this->_subModuleShortname."&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
    ), $_LANG2['mc']));

    return array(
      'content'      => $mc_content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Moves a category if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveID');
    $moveTo = $get->readInt('moveTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category",
                                         'MCID', 'MCPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['mc_message_move_success']));
    }
  }
}

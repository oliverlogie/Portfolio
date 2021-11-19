<?php

/**
 * ModuleCustomText
 *
 * Allows browsing and editing custom texts. So-called "custom texts" can be
 * used by customer specific EDWIN CMS features, not available within the EDWIN
 * CMS core.
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleCustomText extends Module
{
  protected $_prefix = 'ct';

  /**
   * @see ModuleCustomText::_input()
   * @var Input
   */
  private $_input;

  public function show_innercontent()
  {
    $this->_edit();
    $this->_move();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->_getContent();
    }
    else
      return $this->_listContent();
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }

  /***
   * @return Input
   */
  protected function _input()
  {
    if ($this->_input === null) {
      $this->_input = new Input(Input::SOURCE_REQUEST);
    }

    return $this->_input;
  }

  /**
   * Edit an item
   */
  private function _edit()
  {
    global $_LANG;

    if (!$this->_input()->exists('process') || $this->action[0] != 'edit')
      return;

    $sql = " SELECT CTHtml "
         . " FROM {$this->table_prefix}module_customtext "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CTID = $this->item_id ";
    $htmlAllowed = (bool)$this->db->GetOne($sql);
    $filter = $htmlAllowed ? Input::FILTER_NONE : Input::FILTER_PLAIN;

    $title = $this->_input()->readString('ct_title', Input::FILTER_PLAIN);
    $text = $this->_input()->readString('ct_text', $filter);

    $sql = " UPDATE {$this->table_prefix}module_customtext "
         . "    SET CTTitle = '{$this->db->escape($title)}', "
         . "        CTText = '{$this->db->escape($text)}' "
         . " WHERE CTID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $this->db->query($sql);

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_parseUrl(''),
          Message::createSuccess($_LANG['ct_message_edit_item_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
          Message::createSuccess($_LANG['ct_message_edit_item_success']));
    }
  }


  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    if (!$this->item_id) {
      header("Location: index.php?action=mod_customtext");
      exit();
    }

    $sql = " SELECT CTID, CTName, CTDescription, CTTitle, CTText, CTHtml "
         . " FROM {$this->table_prefix}module_customtext "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CTID = $this->item_id ";
    $result = $this->db->query($sql);
    $row = $this->db->fetch_row($result);

    $description = $row['CTDescription'];
    $name = $row['CTName'];
    $title = $row['CTTitle'];
    $text = $row['CTText'];
    $allowHtml = (int)$row['CTHtml'];

    $this->db->free_result($result);
    $function = 'edit';

    $hiddenFields = '<input type="hidden" name="action" value="mod_customtext" />'
                  . '<input type="hidden" name="action2" value="main;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_ct', 'modules/ModuleCustomText.tpl');
    $this->tpl->parse_if('content_ct', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ct'));
    $ct_content = $this->tpl->parsereturn('content_ct', array_merge(array (
      'ct_allow_html_cls'   => !$allowHtml ? 'ed_no_editor' : '',
      'ct_description'      => parseOutput($description, 1),
      'ct_name'             => parseOutput($name),
      'ct_text'             => parseOutput($text),
      'ct_title'            => parseOutput($title),
      'ct_hidden_fields'    => $hiddenFields,
      'ct_function_label'   => $_LANG['ct_function_edit_label'],
      'ct_function_label2'  => $_LANG['ct_function_edit_label2'],
      'ct_action'           => "index.php",
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['ct']));

    return array(
        'content'      => $ct_content,
        'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Shows a list containing items
   *
   * @return array
   *         Contains backend content.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_customtext",
                                         'CTID', 'CTPosition',
                                         'FK_SID', $this->site_id);

    $items = array();
    $sql = " SELECT CTID, CTName, CTTitle, CTPosition "
         . " FROM {$this->table_prefix}module_customtext "
         . " WHERE FK_SID = $this->site_id "
         . ($this->_getCurrentCategoryId() ? " AND FK_CTCID_CustomtextCategory = '{$this->_getCurrentCategoryId()}' " : '')
         . " ORDER BY CTPosition ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $tmpId = (int)$row['CTID'];
      $tmpPos = (int)$row['CTPosition'];
      $moveUpPosition = $positionHelper->getMoveUpPosition($tmpPos);
      $moveDownPosition = $positionHelper->getMoveDownPosition($tmpPos);

      $items[$tmpId] = array(
        'ct_id'             => $tmpId,
        'ct_edit_link'      => "index.php?action=mod_customtext&amp;action2=main;edit&amp;site=$this->site_id&amp;page={$tmpId}&amp;",
        'ct_move_up_link'   => "index.php?action=mod_customtext&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveUpPosition",
        'ct_move_down_link' => "index.php?action=mod_customtext&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveDownPosition",
        'ct_name'           => parseOutput($row['CTName']),
        'ct_position'       => $tmpPos,
        'ct_title'          => parseOutput($row['CTTitle']),
      );
    }
    $this->db->free_result($result);

    $categoryOptions = $this->_getCategoryOptionsTemplateVar();

    if (!$items)
      $this->setMessage(Message::createFailure($_LANG['ct_message_no_items']));

    // parse list template
    $this->tpl->load_tpl('content_ct', 'modules/ModuleCustomText_list.tpl');
    $this->tpl->parse_if('content_ct', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ct'));
    $this->tpl->parse_if('content_ct', 'categories', $categoryOptions, array(
      'ct_choose_category_options' => $categoryOptions,
    ));
    $this->tpl->parse_loop('content_ct', $items, 'entries');
    $ct_content = $this->tpl->parsereturn('content_ct', array_merge(array(
      'ct_site'             => $this->site_id,
      'ct_action'           => 'index.php?action=mod_customtext',
      'ct_site_selection'   => $this->_parseModuleSiteSelection('customtext', $_LANG['ct_site_label']),
      'ct_dragdrop_link_js' => "index.php?action=mod_customtext&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'ct_choose_category_url' => "index.php?action=mod_customtext&site=$this->site_id&category_id=#categoryId#",
      'ct_category_filter_active_class' => $this->_getCurrentCategoryId() ? 'category_filter_active' : '',
    ), $_LANG2['ct']));

    return array(
      'content' => $ct_content,
    );
  }

  /**
   * Moves an item if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if (!$get->exists('moveID', 'moveTo')) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_customtext",
                                         'CTID', 'CTPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ct_message_move_success']));
    }
  }

  /**
   * @return int
   */
  private function _getCurrentCategoryId()
  {
    if ($this->_input()->exists('category_id')) {
      $id = $this->_input->readInt('category_id');
    }
    else {
      $id = (int)$this->session->read('ct_' . $this->site_id . 'category_id');
    }

    $this->session->save('ct_' . $this->site_id . 'category_id', $id);

    return $id;
  }

  /**
   * @return string
   */
  private function _getCategoryOptionsTemplateVar()
  {
    $options = '';
    $categories = $this->_getCategoriesBySiteId($this->site_id);

    foreach ($categories as $category) {
      $options .= sprintf(
        '<option value="%s" %s>%s</option>',
        $category['CTCID'],
        $category['CTCID'] == $this->_getCurrentCategoryId() ? 'selected="selected"' : '',
        parseOutput($category['CTCName'], 0)
      );
    }

    return $options;
  }

  /**
   * @param int $siteId
   * @return array
   */
  private function _getCategoriesBySiteId($siteId)
  {
    $siteId = (int)$siteId;
    $sql = " SELECT * "
         . " FROM {$this->table_prefix}module_customtext_category "
         . " WHERE FK_SID = '$siteId' ";
    return $this->db->GetAssoc($sql);
  }
}
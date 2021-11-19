<?php

/**
 * ModuleShopPlusManagementCartSettings
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleShopPlusManagementCartSettings extends Module
{
  protected $_prefix = 'oc';

  /**
   * The currency
   *
   * @var string
   */
  private $_currency = '';

  public function show_innercontent ()
  {
    $this->_currency = ConfigHelper::get('site_currencies', '', $this->site_id);

    $this->_create();
    $this->_delete();
    $this->_edit();
    $this->_move();

    if (isset($this->action[0]) && $this->action[0])
      return $this->_getContent();
    else
      return $this->_listContent();
  }

  /**
   * Creates an item
   */
  private function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new')
      return;

    $title = $post->readString('oc_title', Input::FILTER_PLAIN);
    $text = $post->readString('oc_text', Input::FILTER_PLAIN);
    $price = $post->readFloat('oc_price');

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["oc_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT CPCID "
         . " FROM {$this->table_prefix}contentitem_cp_cartsetting "
         . " WHERE CPCTitle LIKE '$title' "
         . "   AND FK_SID = $this->site_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["oc_message_failure_existing"]));
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_cartsetting",
                                         'CPCID', 'CPCPosition',
                                         'FK_SID', $this->site_id);
    $position = $positionHelper->getHighestPosition() + 1;

    $sqlArgs = array(
      'CPCTitle'    => "'{$this->db->escape($title)}'",
      'CPCText'     => "'{$this->db->escape($text)}'",
      'CPCPrice'    => $price,
      'CPCPosition' => $position,
      'FK_SID'      => $this->site_id,
    );

    $sqlFields = implode(',', array_keys($sqlArgs));
    $sqlValues = implode(',', array_values($sqlArgs));

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cp_cartsetting ($sqlFields) "
         . " VALUES ($sqlValues)";
    $result = $this->db->query($sql);

    if ($result) {
      $this->item_id = $this->db->insert_id();
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['oc_message_new_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['oc_message_new_item_success']));
      }
    }
  }

  /**
   * Edit an item
   */
  private function _edit()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    // new attribute group
    if (!$post->exists('process') || $this->action[0] != 'edit')
      return;

    $title = $post->readString('oc_title', Input::FILTER_PLAIN);
    $text = $post->readString('oc_text', Input::FILTER_PLAIN);
    $price = $post->readFloat('oc_price');

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["oc_message_failure_no_title"]));
      return;
    }

      $sql = " SELECT CPCID "
         . " FROM {$this->table_prefix}contentitem_cp_cartsetting "
         . " WHERE CPCTitle LIKE '$title' "
         . "   AND FK_SID = $this->site_id "
         . "   AND CPCID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["oc_message_failure_existing"]));
      return;
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_cp_cartsetting "
         . "    SET CPCTitle = '{$this->db->escape($title)}', "
         . "        CPCText = '{$this->db->escape($text)}', "
         . "        CPCPrice = $price "
         . " WHERE CPCID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    if ($result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['oc_message_edit_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['oc_message_edit_item_success']));
      }
    }
  }

  /**
   * Delete an item, if $_GET parameter 'deleteID' is set
   */
  private function _delete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('deleteID');

    if (!$id)
      return;

    $sql = " SELECT CPCID, CPCPosition "
         . " FROM {$this->table_prefix}contentitem_cp_cartsetting "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CPCID = $id ";
    $row = $this->db->GetRow($sql);

    if (!$row)
      return;

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_cartsetting",
                                         'CPCID', 'CPCPosition',
                                         'FK_SID', $this->site_id);
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    $sql = " DELETE FROM {$this->table_prefix}contentitem_cp_cartsetting "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CPCID = $id ";
    $result = $this->db->query($sql);

    // delete cart setting from products it has been used so far
    $sql = " SELECT FK_CIID, PPCPosition "
         . " FROM {$this->table_prefix}contentitem_pp_cartsetting "
         . " WHERE FK_CPCID = $id ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $tmpId = $row['FK_CIID'];
      $tmpPos = $row['PPCPosition'];

      $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_cartsetting "
           . " WHERE FK_CIID = $tmpId "
           . "   AND FK_CPCID = $id ";
      $this->db->query($sql);

      // rearrange positions after deleting item from product
      $sql = " UPDATE {$this->table_prefix}contentitem_pp_cartsetting "
           . " SET PPCPosition = PPCPosition - 1 "
           . " WHERE FK_CIID = $tmpId "
           . "   AND PPCPosition > $tmpPos ";
      $this->db->query($sql);
    }

    $this->setMessage(Message::createSuccess($_LANG['oc_message_delete_item_success']));
  }


  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    // edit setting -> load data
    if ($this->item_id) {
      $sql = " SELECT CPCID, CPCTitle, CPCText, CPCPrice "
           . " FROM {$this->table_prefix}contentitem_cp_cartsetting "
           . " WHERE FK_SID = $this->site_id "
           . "   AND CPCID = $this->item_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);

      $title = $row['CPCTitle'];
      $text = $row['CPCText'];
      $price = $row['CPCPrice'];

      $this->db->free_result($result);
      $function = 'edit';
    }
    else // new option
    {
      $title = $post->readString('oc_title', Input::FILTER_PLAIN);
      $text = $post->readString('oc_text', Input::FILTER_PLAIN);
      $price = $post->readFloat('oc_price');

      $function = 'new';
    }

    $action = "index.php";
    $hiddenFields = '<input type="hidden" name="action" value="mod_shopplusmgmt" />'
                  . '<input type="hidden" name="action2" value="cart;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_oo', 'modules/ModuleShopPlusManagementCartSettings.tpl');
    $this->tpl->parse_if('content_oo', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('oc'));
    $oc_content = $this->tpl->parsereturn('content_oo', array_merge(array (
      'oc_title'            => parseOutput($title),
      'oc_text'             => parseOutput($text),
      'oc_price'            => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $price), 99),
      'oc_hidden_fields'    => $hiddenFields,
      'oc_function_label'   => $this->item_id ? $_LANG['oc_function_edit_label'] : $_LANG['oc_function_new_label'],
      'oc_function_label2'  => $this->item_id ? $_LANG['oc_function_edit_label2'] : $_LANG['oc_function_new_label2'],
      'oc_action'           => $action,
      'oc_currency'         => $this->_currency,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['oc']));

    return array(
        'content'      => $oc_content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  /**
   * Shows a list containing all cart settings
   *
   * @return array
   *         Contains backend content.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_cartsetting",
                                         'CPCID', 'CPCPosition',
                                         'FK_SID', $this->site_id);

    $sql = " SELECT CPCID, CPCTitle, CPCText, CPCPrice, CPCPosition "
         . " FROM {$this->table_prefix}contentitem_cp_cartsetting "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY CPCPosition ASC ";
    $result = $this->db->query($sql);

    $count = $this->db->num_rows($result);
    $items = array();
    while ($row = $this->db->fetch_row($result))
    {
      $tmpId = (int)$row['CPCID'];
      $tmpPos = (int)$row['CPCPosition'];
      $moveUpPosition = $positionHelper->getMoveUpPosition($tmpPos);
      $moveDownPosition = $positionHelper->getMoveDownPosition($tmpPos);

      $items[$tmpId] = array(
        'oc_id'             => $tmpId,
        'oc_position'       => $tmpPos,
        'oc_title'          => parseOutput($row['CPCTitle']),
        'oc_text'           => parseOutput($row['CPCText']),
        'oc_price'          => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $row['CPCPrice']), 99),
        'oc_edit_link'      => "index.php?action=mod_shopplusmgmt&amp;action2=cart;edit&amp;site=$this->site_id&amp;page={$tmpId}",
        'oc_move_up_link'   => "index.php?action=mod_shopplusmgmt&amp;action2=cart&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveUpPosition",
        'oc_move_down_link' => "index.php?action=mod_shopplusmgmt&amp;action2=cart&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveDownPosition",
        'oc_delete_link'    => "index.php?action=mod_shopplusmgmt&amp;action2=cart&amp;site=$this->site_id&amp;deleteID={$tmpId}",
      );
    }
    $this->db->free_result($result);

    if (!$items)
      $this->setMessage(Message::createFailure($_LANG['oc_message_no_items']));

    // parse list template
    $this->tpl->load_tpl('content_oc', 'modules/ModuleShopPlusManagementCartSettings_list.tpl');
    $this->tpl->parse_if('content_oc', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('oc'));
    $this->tpl->parse_loop('content_oc', $items, 'entries');
    $oc_content = $this->tpl->parsereturn('content_oc', array_merge(array(
      'oc_site'             => $this->site_id,
      'oc_action'           => 'index.php?action=mod_shopplusmgmt&amp;action2=cart',
      'oc_site_selection'   => $this->_parseModuleSiteSelection('shopplusmgmt', $_LANG['oc_site_label'], 'cart'),
      'oc_dragdrop_link_js' => "index.php?action=mod_shopplusmgmt&action2=cart&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'oc_currency'         => $this->_currency,
    ), $_LANG2['oc']));

    return array(
      'content'      => $oc_content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Moves a cart setting if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if (!$get->exists('moveID', 'moveTo')) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_cartsetting",
                                         'CPCID', 'CPCPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['oc_message_move_success']));
    }
  }
}
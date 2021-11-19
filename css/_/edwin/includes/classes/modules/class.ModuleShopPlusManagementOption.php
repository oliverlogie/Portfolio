<?php

/**
 * ModuleShopPlusManagementOption
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleShopPlusManagementOption extends Module
{
  protected $_prefix = 'oo';

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
   * Creates a new option
   */
  private function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new')
      return;

    $code = $post->readString('oo_code', Input::FILTER_PLAIN);
    $title = $post->readString('oo_title', Input::FILTER_PLAIN);
    $text = $post->readString('oo_text', Input::FILTER_PLAIN);
    $price = $post->readFloat('oo_price');
    $product = $post->exists('oo_product') ? 1 : 0;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["oo_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT OPID "
         . " FROM {$this->table_prefix}contentitem_pp_option_global "
         . " WHERE OPName LIKE '$title' "
         . "   AND FK_SID = $this->site_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["oo_message_failure_existing"]));
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option_global",
                                         'OPID', 'OPPosition',
                                         'FK_SID', $this->site_id);
    $position = $positionHelper->getHighestPosition() + 1;

    $image = $this->_storeImage($_FILES['oo_image'], false, 'oo', 0, null, false, true);

    $sqlArgs = array(
      'OPCode'    => "'{$this->db->escape($code)}'",
      'OPName'    => "'{$this->db->escape($title)}'",
      'OPText'    => "'{$this->db->escape($text)}'",
      'OPImage'   => "'$image'",
      'OPPrice'   => $price,
      'OPProduct' => $product,
      'OPPosition'=> $position,
      'FK_SID'    => $this->site_id,
    );

    $sqlFields = implode(',', array_keys($sqlArgs));
    $sqlValues = implode(',', array_values($sqlArgs));

    $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_option_global ($sqlFields) "
         . " VALUES ($sqlValues)";
    $result = $this->db->query($sql);

    if ($result) {
      $this->item_id = $this->db->insert_id();
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['oo_message_new_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['oo_message_new_item_success']));
      }
    }
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

    $code = $post->readString('oo_code', Input::FILTER_PLAIN);
    $title = $post->readString('oo_title', Input::FILTER_PLAIN);
    $text = $post->readString('oo_text', Input::FILTER_PLAIN);
    $price = $post->readFloat('oo_price');
    $product = $post->exists('oo_product') ? 1 : 0;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["oo_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT OPID "
         . " FROM {$this->table_prefix}contentitem_pp_option_global "
         . " WHERE OPName LIKE '$title' "
         . "   AND FK_SID = $this->site_id "
         . "   AND OPID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["oo_message_failure_existing"]));
      return;
    }

    $image = '';
    if (isset($_FILES['oo_image']) && $_FILES['oo_image']['size'] > 0) {
      $sql = " SELECT OPImage "
           . " FROM {$this->table_prefix}contentitem_pp_option_global "
           . " WHERE OPID = $this->item_id "
           . "   AND FK_SID = $this->site_id ";
      $existingImage = $this->db->GetOne($sql);
      $image = $this->_storeImage($_FILES['oo_image'], $existingImage, 'oo', 0, null, false, true);
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_pp_option_global "
         . "    SET OPCode = '{$this->db->escape($code)}', "
         . "        OPName = '{$this->db->escape($title)}', "
         . "        OPText = '{$this->db->escape($text)}', "
         . "        OPPrice = $price, "
         . "        OPProduct = $product "
         . ( $image ? ", OPImage = '$image' " : "" )
         . " WHERE OPID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    if (!$this->_getMessage() && $result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['oo_message_edit_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['oo_message_edit_item_success']));
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

    $sql = " SELECT OPID, OPPosition "
         . " FROM {$this->table_prefix}contentitem_pp_option_global "
         . " WHERE FK_SID = $this->site_id "
         . "   AND OPID = $id ";
    $row = $this->db->GetRow($sql);

    if (!$row)
      return;

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option_global",
                                         'OPID', 'OPPosition',
                                         'FK_SID', $this->site_id);
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_option_global "
         . " WHERE FK_SID = $this->site_id "
         . "   AND OPID = $id ";
    $result = $this->db->query($sql);

    // delete option from products it has been used so far
    $sql = " SELECT FK_CIID, PPOPosition "
         . " FROM {$this->table_prefix}contentitem_pp_option "
         . " WHERE FK_OPID = $id ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $tmpId = $row['FK_CIID'];
      $tmpPos = $row['PPOPosition'];

      $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_option "
           . " WHERE FK_CIID = $tmpId "
           . "   AND FK_OPID = $id ";
      $this->db->query($sql);

      // rearrange positions after deleting an option item from product
      $sql = " UPDATE {$this->table_prefix}contentitem_pp_option "
           . " SET PPOPosition = PPOPosition - 1 "
           . " WHERE FK_CIID = $tmpId "
           . "   AND PPOPosition > $tmpPos ";
      $this->db->query($sql);
    }

    $this->setMessage(Message::createSuccess($_LANG['oo_message_delete_item_success']));
  }


  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    // edit option -> load data
    if ($this->item_id) {
      $sql = " SELECT OPCode, OPName, OPText, OPImage, OPPrice, OPProduct "
           . " FROM {$this->table_prefix}contentitem_pp_option_global "
           . " WHERE FK_SID = $this->site_id "
           . "   AND OPID = $this->item_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);

      $code = $row['OPCode'];
      $title = $row['OPName'];
      $text = $row['OPText'];
      $price = $row['OPPrice'];
      $product = $row['OPProduct'];
      $image = $row['OPImage'];

      $this->db->free_result($result);
      $function = 'edit';
    }
    else // new option
    {
      $code = $post->readString('oo_code', Input::FILTER_PLAIN);
      $title = $post->readString('oo_title', Input::FILTER_PLAIN);
      $text = $post->readString('oo_text', Input::FILTER_PLAIN);
      $price = $post->readFloat('oo_price');
      $product = $post->exists('oo_product') ? 1 : 0;
      $image = '';

      $function = 'new';
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_shopplusmgmt" />'
                  . '<input type="hidden" name="action2" value="option;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_oo', 'modules/ModuleShopPlusManagementOption.tpl');
    $this->tpl->parse_if('content_oo', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('oo'));
    $oo_content = $this->tpl->parsereturn('content_oo', array_merge(array (
      'oo_code'             => parseOutput($code),
      'oo_title'            => parseOutput($title),
      'oo_text'             => parseOutput($text),
      'oo_image_src'        => $this->get_large_image('oo', $image),
      'oo_required_resolution_label' => $this->_getImageSizeInfo('oo', 0),
      'oo_price'            => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $price), 99),
      'oo_product_checked'  => $product ? 'checked="checked"' : '',
      'oo_hidden_fields'    => $hiddenFields,
      'oo_function_label'   => $this->item_id ? $_LANG['oo_function_edit_label'] : $_LANG['oo_function_new_label'],
      'oo_function_label2'  => $this->item_id ? $_LANG['oo_function_edit_label2'] : $_LANG['oo_function_new_label2'],
      'oo_action'           => "index.php",
      'oo_currency'         => $this->_currency,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['oo']));

    return array(
        'content'      => $oo_content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  /**
   * Shows a list containing all options
   *
   * @return array
   *         Contains backend content.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option_global",
                                         'OPID', 'OPPosition',
                                         'FK_SID', $this->site_id);

    $sql = " SELECT OPID, OPCode, OPName, OPText, OPPrice, OPProduct, OPPosition "
         . " FROM {$this->table_prefix}contentitem_pp_option_global "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY OPPosition ASC ";
    $result = $this->db->query($sql);

    $count = $this->db->num_rows($result);
    $items = array();
    while ($row = $this->db->fetch_row($result))
    {
      $tmpId = (int)$row['OPID'];
      $tmpPos = (int)$row['OPPosition'];
      $moveUpPosition = $positionHelper->getMoveUpPosition($tmpPos);
      $moveDownPosition = $positionHelper->getMoveDownPosition($tmpPos);

      $items[$tmpId] = array(
        'oo_id'             => $tmpId,
        'oo_position'       => $tmpPos,
        'oo_title'          => parseOutput($row['OPName']),
        'oo_text'           => parseOutput($row['OPText']),
        'oo_price'          => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $row['OPPrice']), 99),
        'oo_product'        => (int)($row['OPProduct']),
        'oo_edit_link'      => "index.php?action=mod_shopplusmgmt&amp;action2=option;edit&amp;site=$this->site_id&amp;page={$tmpId}",
        'oo_move_up_link'   => "index.php?action=mod_shopplusmgmt&amp;action2=option&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveUpPosition",
        'oo_move_down_link' => "index.php?action=mod_shopplusmgmt&amp;action2=option&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveDownPosition",
        'oo_delete_link'    => "index.php?action=mod_shopplusmgmt&amp;action2=option&amp;site=$this->site_id&amp;deleteID={$tmpId}",
      );
    }
    $this->db->free_result($result);

    if (!$items)
      $this->setMessage(Message::createFailure($_LANG['oo_message_no_items']));

    // parse list template
    $this->tpl->load_tpl('content_oo', 'modules/ModuleShopPlusManagementOption_list.tpl');
    $this->tpl->parse_if('content_oo', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('oo'));
    $this->tpl->parse_loop('content_oo', $items, 'entries');
    $oo_content = $this->tpl->parsereturn('content_oo', array_merge(array(
      'oo_site'             => $this->site_id,
      'oo_action'           => 'index.php?action=mod_shopplusmgmt&amp;action2=option',
      'oo_site_selection'   => $this->_parseModuleSiteSelection('shopplusmgmt', $_LANG['oo_site_label'], 'option'),
      'oo_dragdrop_link_js' => "index.php?action=mod_shopplusmgmt&action2=option&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'oo_currency'         => $this->_currency,
    ), $_LANG2['oo']));

    return array(
      'content'      => $oo_content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Moves an option if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if (!$get->exists('moveID', 'moveTo')) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option_global",
                                         'OPID', 'OPPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['oo_message_move_success']));
    }
  }
}
<?php

/**
 * ModuleShopPlusManagementPayment
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleShopPlusManagementPayment extends Module
{
  protected $_prefix = 'oy';

  /**
   * The currency
   *
   * @var string
   */
  private $_currency = '';

  public function show_innercontent ()
  {
    $this->_currency = ConfigHelper::get('site_currencies', '', $this->site_id);

    $this->_move();
    $this->_edit();

    // No new items can be created, so we check for Module::item_id. If it is
    // available, we know there is an item edited.
    if ($this->item_id)
      return $this->_getContent();
    else
      return $this->_listContent();
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }

  /**
   * Edit an item
   */
  private function _edit()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit')
      return;

    $title = $post->readString('oy_title', Input::FILTER_PLAIN);
    $text = $post->readString('oy_text', Input::FILTER_PLAIN);
    $costs = $post->readFloat('oy_costs');
    $countries = $post->readArrayIntToInt('oy_countries');
    $countries = array_keys($countries);
    $prices = $post->readArrayIntToString('oy_prices');

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["oy_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT CYID "
         . " FROM {$this->table_prefix}contentitem_cp_payment_type "
         . " WHERE CYName LIKE '$title' "
         . "   AND FK_SID = $this->site_id "
         . "   AND CYID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["oy_message_failure_existing"]));
      return;
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_cp_payment_type "
         . "    SET CYName = '{$this->db->escape($title)}', "
         . "        CYText = '{$this->db->escape($text)}', "
         . "        CYCosts = $costs "
         . " WHERE CYID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    // delete old country payment assignments
    $sql = " DELETE FROM {$this->table_prefix}contentitem_cp_payment_type_country "
         . " WHERE FK_CYID = $this->item_id ";
    $this->db->query($sql);

    // insert selected countries including price
    $sqlValues = array();
    foreach ($countries as $val) {
      $tmp = (float)$prices[$val];
      $sqlValues[] = "($this->item_id, $val, $tmp)";
    }
    if (!empty($sqlValues)) {
      $sqlValues = implode(',', $sqlValues);
      $sql = " INSERT INTO {$this->table_prefix}contentitem_cp_payment_type_country (FK_CYID, FK_COID, CPYCPrice) "
           . " VALUES $sqlValues";
      $this->db->query($sql);
    }

    if ($result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['oy_message_edit_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['oy_message_edit_item_success']));
      }
    }
  }


  /**
   * Get edit content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    $sql = " SELECT CYName, CYClass, CYText, CYCosts "
         . " FROM {$this->table_prefix}contentitem_cp_payment_type "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CYID = $this->item_id ";
    $result = $this->db->query($sql);
    $row = $this->db->fetch_row($result);
    $this->db->free_result($result);

    $class = $row['CYClass'];
    $title = $row['CYName'];
    $text = $row['CYText'];
    $costs = $row['CYCosts'];

    $hiddenFields = '<input type="hidden" name="action" value="mod_shopplusmgmt" />'
                  . '<input type="hidden" name="action2" value="pay;edit" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_oy', 'modules/ModuleShopPlusManagementPayment.tpl');
    $this->tpl->parse_if('content_oy', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('oy'));
    $this->tpl->parse_loop('content_oy', $this->_getFormCountries($this->item_id), 'country_items');
    $oy_content = $this->tpl->parsereturn('content_oy', array_merge(array (
      'oy_class'            => parseOutput($class),
      'oy_title'            => parseOutput($title),
      'oy_text'             => parseOutput($text),
      'oy_costs'            => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $costs), 99),
      'oy_hidden_fields'    => $hiddenFields,
      'oy_function_label'   => $this->item_id ? $_LANG['oy_function_edit_label'] : $_LANG['oy_function_new_label'],
      'oy_function_label2'  => $this->item_id ? $_LANG['oy_function_edit_label2'] : $_LANG['oy_function_new_label2'],
      'oy_action'           => "index.php",
      'oy_currency'         => $this->_currency,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['oy']));

    return array(
        'content'      => $oy_content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  /**
   * Shows a list containing all payment types
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

    $sql = " SELECT CYID, CYName, CYClass, CYText, CYCosts, CYPosition "
         . " FROM {$this->table_prefix}contentitem_cp_payment_type "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY CYPosition ASC ";
    $result = $this->db->query($sql);

    $count = $this->db->num_rows($result);
    $items = array();
    while ($row = $this->db->fetch_row($result))
    {
      $tmpId = (int)$row['CYID'];
      $tmpPos = (int)$row['CYPosition'];
      $moveUpPosition = $positionHelper->getMoveUpPosition($tmpPos);
      $moveDownPosition = $positionHelper->getMoveDownPosition($tmpPos);

      $items[$tmpId] = array(
        'oy_id'             => $tmpId,
        'oy_position'       => $tmpPos,
        'oy_title'          => parseOutput($row['CYName']),
        'oy_text'           => parseOutput($row['CYText']),
        'oy_costs'          => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $row['CYCosts']), 99),
        'oy_class'          => parseOutput($row['CYClass']),
        'oy_edit_link'      => "index.php?action=mod_shopplusmgmt&amp;action2=pay;edit&amp;site=$this->site_id&amp;page={$tmpId}",
        'oy_move_up_link'   => "index.php?action=mod_shopplusmgmt&amp;action2=pay&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveUpPosition",
        'oy_move_down_link' => "index.php?action=mod_shopplusmgmt&amp;action2=pay&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveDownPosition",
      );
    }
    $this->db->free_result($result);

    if (!$items)
      $this->setMessage(Message::createFailure($_LANG['oy_message_no_items']));

    // parse list template
    $this->tpl->load_tpl('content_oy', 'modules/ModuleShopPlusManagementPayment_list.tpl');
    $this->tpl->parse_if('content_oy', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('oy'));
    $this->tpl->parse_loop('content_oy', $items, 'entries');
    $oy_content = $this->tpl->parsereturn('content_oy', array_merge(array(
      'oy_site'             => $this->site_id,
      'oy_action'           => 'index.php?action=mod_shopplusmgmt&amp;action2=pay',
      'oy_site_selection'   => $this->_parseModuleSiteSelection('shopplusmgmt', $_LANG['oy_site_label'], 'pay'),
      'oy_dragdrop_link_js' => "index.php?action=mod_shopplusmgmt&action2=pay&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'oy_currency'         => $this->_currency,
    ), $_LANG2['oy']));

    return array(
      'content' => $oy_content,
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

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_payment_type",
                                         'CYID', 'CYPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['oy_message_move_success']));
    }
  }

  private function _getFormCountries($paymentTypeId)
  {
    $countries = $this->_configHelper->getCountries('c_cp_countries', false, $this->site_id, 43);
    $countryItems = array();
    foreach ($countries as $key => $val) {
      $countryItems[$key] = array(
        'oy_country_item_id'      => $key,
        'oy_country_item_title'   => $val,
        'oy_country_item_checked' => '',
        'oy_country_item_price'   => ''
      );
    }

    $sql = " SELECT FK_COID, CPYCPrice "
         . " FROM {$this->table_prefix}contentitem_cp_payment_type_country "
         . " WHERE FK_CYID = $this->item_id ";
    $col = $this->db->GetAssoc($sql);

    // set checked countries
    foreach ($col as $key => $val) {
      $val = (int)$val;
      $countryItems[$key]['oy_country_item_checked'] = 'checked="checked"';
      $countryItems[$key]['oy_country_item_price'] = $val ?
          parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $val), 99) : '';
    }

    return $countryItems;
  }
}
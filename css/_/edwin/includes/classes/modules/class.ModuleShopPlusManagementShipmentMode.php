<?php

/**
 * ModuleShopPlusManagementShipmentMode
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleShopPlusManagementShipmentMode extends Module
{
  protected $_prefix = 'os';

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
    $this->_edit();
    $this->_delete();
    $this->_move();

    if (isset($this->action[0]) && $this->action[0])
      return $this->_getContent();
    else
      return $this->_listContent();
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

    $title = $post->readString('os_title', Input::FILTER_PLAIN);
    $price = $post->readFloat('os_price');
    $countries = $post->readArrayIntToInt('os_countries');
    $countries = array_keys($countries);
    $prices = $post->readArrayIntToString('os_countries');

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["os_message_failure_no_title"]));
      return;
    }
    else if (empty($countries)) {
      $this->setMessage(Message::createFailure($_LANG["os_message_failure_no_country"]));
      return;
    }

    $sql = " SELECT CPSID "
         . " FROM {$this->table_prefix}contentitem_cp_shipment_mode "
         . " WHERE CPSName LIKE '$title' "
         . "   AND FK_SID = $this->site_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["os_message_failure_existing"]));
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_shipment_mode",
                                         'CPSID', 'CPSPosition',
                                         'FK_SID', $this->site_id);

    $sqlArgs = array(
      'CPSName'    => "'{$this->db->escape($title)}'",
      'CPSPrice'   => $price,
      'FK_SID'     => $this->site_id,
      'CPSPosition' => $positionHelper->getHighestPosition() + 1,
    );

    $sqlFields = implode(',', array_keys($sqlArgs));
    $sqlValues = implode(',', array_values($sqlArgs));

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cp_shipment_mode ($sqlFields) "
         . " VALUES ($sqlValues)";
    $result = $this->db->query($sql);

    if ($result) {
      $this->item_id = $this->db->insert_id();

      $sqlValues = array();
      foreach ($countries as $val) {
        $tmp = (float)$prices[$val];
        $sqlValues[] = "($this->item_id, $val, $tmp)";
      }
      $sqlValues = implode(',', $sqlValues);

      $sql = " INSERT INTO {$this->table_prefix}contentitem_cp_shipment_mode_country (FK_CPSID, FK_COID, CPSCPrice) "
           . " VALUES $sqlValues";
      $this->db->query($sql);

      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['os_message_new_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['os_message_new_item_success']));
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

    if (!$post->exists('process') || $this->action[0] != 'edit')
      return;

    $title = $post->readString('os_title', Input::FILTER_PLAIN);
    $price = $post->readFloat('os_price');
    $countries = $post->readArrayIntToInt('os_countries');
    $countries = array_keys($countries);
    $prices = $post->readArrayIntToString('os_prices');

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG["os_message_failure_no_title"]));
      return;
    }

    $sql = " SELECT CPSID "
         . " FROM {$this->table_prefix}contentitem_cp_shipment_mode "
         . " WHERE CPSName LIKE '$title' "
         . "   AND FK_SID = $this->site_id "
         . "   AND CPSID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["os_message_failure_existing"]));
      return;
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_cp_shipment_mode "
         . "    SET CPSName = '{$this->db->escape($title)}', "
         . "        CPSPrice = $price "
         . " WHERE CPSID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $this->db->query($sql);

    // delete old country shipment mode settings
    $sql = " DELETE FROM {$this->table_prefix}contentitem_cp_shipment_mode_country "
         . " WHERE FK_CPSID = $this->item_id ";
    $this->db->query($sql);

    // insert selected countries including price
    $sqlValues = array();
    foreach ($countries as $val) {
      $tmp = (float)$prices[$val];
      $sqlValues[] = "($this->item_id, $val, $tmp)";
    }
    $sqlValues = implode(',', $sqlValues);

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cp_shipment_mode_country (FK_CPSID, FK_COID, CPSCPrice) "
         . " VALUES $sqlValues";
    $this->db->query($sql);

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['os_message_edit_item_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
          Message::createSuccess($_LANG['os_message_edit_item_success']));
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

    $sql = " SELECT CPSID "
         . " FROM {$this->table_prefix}contentitem_cp_shipment_mode "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CPSID = $id ";
    $exists = $this->db->GetOne($sql);

    if (!$exists)
      return;

    $sql = " DELETE FROM {$this->table_prefix}contentitem_cp_shipment_mode "
         . " WHERE CPSID = $id ";
    $this->db->query($sql);

    $sql = " DELETE FROM {$this->table_prefix}contentitem_cp_shipment_mode_country "
         . " WHERE FK_CPSID = $id ";
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['os_message_delete_item_success']));
  }

  /**
   * Moves an item if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    if (!isset($_GET['moveID'], $_GET['moveTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveID'];
    $moveTo = (int)$_GET['moveTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cp_shipment_mode",
                                         'CPSID', 'CPSPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['os_message_move_success']));
    }
  }


  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    // get ContentItem CP country config
    // shipment mode country settings are only required for countries available
    // at the frontend
    $countries = $this->_configHelper->getCountries('c_cp_countries', false, $this->site_id, 43);

    $countryItems = array();
    foreach ($countries as $key => $val) {
      $countryItems[$key] = array(
        'os_country_item_id'      => $key,
        'os_country_item_title'   => $val,
        'os_country_item_checked' => '',
        'os_country_item_price'   => ''
      );
    }

    // edit item -> load data
    if ($this->item_id) {
      $sql = " SELECT CPSID, CPSName, CPSPrice "
           . " FROM {$this->table_prefix}contentitem_cp_shipment_mode "
           . " WHERE FK_SID = $this->site_id "
           . "   AND CPSID = $this->item_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);

      $title = $row['CPSName'];
      $price = $row['CPSPrice'];

      $sql = " SELECT FK_COID, CPSCPrice "
           . " FROM {$this->table_prefix}contentitem_cp_shipment_mode_country "
           . " WHERE FK_CPSID = $this->item_id ";
      $col = $this->db->GetAssoc($sql);

      // set checked countries
      foreach ($col as $key => $val) {
        $countryItems[$key]['os_country_item_checked'] = 'checked="checked"';
        $countryItems[$key]['os_country_item_price'] = parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $val), 99);
      }

      $this->db->free_result($result);
      $function = 'edit';
    }
    else // new item
    {
      $title = $post->readString('os_title', Input::FILTER_PLAIN);
      $price = $post->readFloat('os_price');

      $checked = $post->readArrayIntToInt('os_countries');
      $prices = $post->readArrayIntToString('os_prices');

       // set checked countries
      foreach ($checked as $key => $val) {
        $countryItems[$key]['os_country_item_checked'] = 'checked="checked"';
        $countryItems[$key]['os_country_item_price'] = parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), (float)$prices[$key]), 99);
      }

      $function = 'new';
    }

    $action = "index.php";
    $hiddenFields = '<input type="hidden" name="action" value="mod_shopplusmgmt" />'
                  . '<input type="hidden" name="action2" value="ship;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_oo', 'modules/ModuleShopPlusManagementShipmentMode.tpl');
    $this->tpl->parse_if('content_oo', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('os'));
    $this->tpl->parse_loop('content_oo', $countryItems, 'country_items');
    $os_content = $this->tpl->parsereturn('content_oo', array_merge(array (
      'os_title'            => parseOutput($title),
      'os_price'            => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $price), 99),
      'os_hidden_fields'    => $hiddenFields,
      'os_function_label'   => $this->item_id ? $_LANG['os_function_edit_label'] : $_LANG['os_function_new_label'],
      'os_function_label2'  => $this->item_id ? $_LANG['os_function_edit_label2'] : $_LANG['os_function_new_label2'],
      'os_action'           => $action,
      'os_currency'         => $this->_currency,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['os']));

    return array(
        'content'      => $os_content,
        'content_left' => $this->_getContentLeft(true),
    );

  }

  /**
   * Shows a list containing all shipment modes
   *
   * @return array
   *         Contains backend content.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $positionHelper = new PositionHelper(
      $this->db, "{$this->table_prefix}contentitem_cp_shipment_mode",
      'CPSID', 'CPSPosition',
      'FK_SID', $this->site_id
    );

    $sql = " SELECT CPSID, CPSName, CPSPrice, CPSPosition "
         . " FROM {$this->table_prefix}contentitem_cp_shipment_mode "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY CPSPosition ASC ";
    $result = $this->db->query($sql);

    $count = $this->db->num_rows($result);
    $items = array();
    while ($row = $this->db->fetch_row($result))
    {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['CPSPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['CPSPosition']);
      $tmpId = (int)$row['CPSID'];

      $items[$tmpId] = array(
        'os_id'             => $tmpId,
        'os_title'          => parseOutput($row['CPSName']),
        'os_price'          => parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $row['CPSPrice']), 99),
        'os_delete_link'    => "index.php?action=mod_shopplusmgmt&amp;action2=ship&amp;site=$this->site_id&amp;deleteID={$tmpId}",
        'os_edit_link'      => "index.php?action=mod_shopplusmgmt&amp;action2=ship;edit&amp;site=$this->site_id&amp;page={$tmpId}",
        'os_position'       => $row['CPSPosition'],
        'os_move_up_link'   => "index.php?action=mod_shopplusmgmt&amp;action2=ship&amp;site={$this->site_id}&amp;moveID={$row["CPSID"]}&amp;moveTo=$moveUpPosition",
        'os_move_down_link' => "index.php?action=mod_shopplusmgmt&amp;action2=ship&amp;site={$this->site_id}&amp;moveID={$row["CPSID"]}&amp;moveTo=$moveDownPosition",
      );
    }
    $this->db->free_result($result);

    if (!$items)
      $this->setMessage(Message::createFailure($_LANG['os_message_no_items']));

    // parse list template
    $this->tpl->load_tpl('content_os', 'modules/ModuleShopPlusManagementShipmentMode_list.tpl');
    $this->tpl->parse_if('content_os', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('os'));
    $this->tpl->parse_loop('content_os', $items, 'entries');
    $os_content = $this->tpl->parsereturn('content_os', array_merge(array(
      'os_site'             => $this->site_id,
      'os_action'           => 'index.php?action=mod_shopplusmgmt&amp;action2=ship',
      'os_site_selection'   => $this->_parseModuleSiteSelection('shopplusmgmt', $_LANG['os_site_label'], 'ship'),
      'os_dragdrop_link_js' => "index.php?action=mod_shopplusmgmt&action2=ship&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'os_currency'         => $this->_currency,
    ), $_LANG2['os']));

    return array(
      'content'      => $os_content,
      'content_left' => $this->_getContentLeft(),
    );
  }
}
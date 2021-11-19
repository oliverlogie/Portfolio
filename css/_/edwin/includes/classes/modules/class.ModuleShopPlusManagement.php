<?php

/**
 * Shop plus order management class
 *
 * $LastChangedDate: 2018-07-13 09:25:43 +0200 (Fr, 13 Jul 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleShopPlusManagement extends Module
{
  public static $subClasses = array(
      'option' => 'ModuleShopPlusManagementOption',
      'pay'    => 'ModuleShopPlusManagementPayment',
      'ship'   => 'ModuleShopPlusManagementShipmentMode',
      'cart'   => 'ModuleShopPlusManagementCartSettings',
      'pref'   => 'ModuleShopPlusManagementPreferences',
  );

  protected $_prefix = 'op';

  /**
   * The currency
   *
   * @var string
   */
  private $_currency = '';

  /**
   * The order number cache, stores formatted order numbers ( saves DB queries )
   *
   * @var array
   */
  private $_formattedOrderNumbers = array();

  /**
   * Content handler
   */
  public function show_innercontent()
  {
    $this->_currency = ConfigHelper::get('site_currencies', '', $this->site_id);

    if (isset($_POST['process_reset'])) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }

    if (isset($this->action[0]) && $this->action[0] == "export")
      return $this->_getCsv();
    else if ($this->item_id)
      return $this->_getContent();
    else
      return $this->_listContent();
  }

  /**
   * Initializes the grid
   * Do not call this method manually, it should called by _grid() only
   * @return void
   */
  protected function _initGrid()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    // 1. grid sql
    $gridSql = " SELECT CPOID, CPOCreateDateTime, CPOTotalPrice, "
             . "        CPOTransactionStatus, CPOCFirstname, CPOCLastname, "
             . "        CPOCCountry, CPOCAddress, CPOCCity, CPOCZIP "
             . " FROM {$this->table_prefix}contentitem_cp_order o "
             . " JOIN {$this->table_prefix}contentitem_cp_order_customer oc "
             . "      ON CPOID = oc.FK_CPOID "
             . " WHERE CPOTransactionStatus > 0 ";

    // 2. fields = columns
    $filterSelective = array('CFCreated', 'CFModified');
    $queryFields[1] = array('type' => 'text', 'value' => 'CPOID', 'lazy' => true);
    $queryFields[2] = array('type' => 'text', 'value' => 'CPOCreateDateTime', 'lazy' => true);
    $queryFields[3] = array('type' => 'text', 'value' => 'CPOCFirstname', 'lazy' => true);
    $queryFields[4] = array('type' => 'text', 'value' => 'CPOCLastname', 'lazy' => true);
    $queryFields[5] = array('type' => 'text', 'value' => 'CPOCZIP', 'lazy' => true);
    $queryFields[6] = array('type' => 'text', 'value' => 'CPOCCity', 'lazy' => true);
    $queryFields[7] = array('type' => 'selective', 'value' => 'CPOCCountry');

    // 3. filter fields = query fields as we do not need additional fields to be
    // filterable
    $filterFields = $queryFields;

    // 4. filter types
    $filterTypes = array(
      'CPOID'             => 'text',
      'CPOCreateDateTime' => 'text',
      'CPOCFirstname'     => 'text',
      'CPOCLastname'      => 'text',
      'CPOCZIP'           => 'text',
      'CPOCCity'          => 'text',
      'CPOCCountry'       => 'selective',
    );

    // 5. order options
    $ordersValuelist = array(
      1 => array('field' => 'CPOCreateDateTime', 'order' => 'ASC'),
      2 => array('field' => 'CPOCreateDateTime', 'order' => 'DESC'),
      3 => array('field' => 'CPOCLastname',      'order' => 'ASC'),
      4 => array('field' => 'CPOCLastname',      'order' => 'DESC'),
      5 => array('field' => 'CPOCCity',          'order' => 'ASC'),
      6 => array('field' => 'CPOCCity',          'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;

    $presetOrders = array(1 => 2);

    // 6. selective data for dropdown
    $countries = array();
    foreach ($this->_configHelper->getCountries('countries', true, $this->site_id, 0) as $id => $value) {
      $countries[$id]['label'] = $value;
    }

    $selectiveData = array('CPOCCountry' => $countries);

    // 7. page
    $page = ($get->exists('op_page')) ? $get->readInt('op_page') : ($this->session->read('op_page') ? $this->session->read('op_page') : 1);
    $this->session->save('op_page', $page);

    // 8. prefix
    $prefix = array('config'  => $this->_prefix,
                    'lang'    => $this->_prefix,
                    'session' => $this->_prefix,
                    'tpl'     => $this->_prefix);

    //---------------------------------------------------------------------- //
    $grid = new DataGrid($this->db, $this->session, $prefix);
    $grid->setSelectiveData($selectiveData);
    $grid->load($gridSql, $queryFields, $filterFields, $filterTypes,
                $orders, $page, false, null, $presetOrders, null, null,
                ConfigHelper::get($this->_prefix . '_results_per_page'),
                null, null);
    return $grid;
  }

  protected function _getContentLeftLinks()
  {
    $links = array();
    if (empty($this->action[0])) { // list view displayed
      $links[] = array($this->_parseUrl('export'), $this->_langVar('export_label'));
    }

    return $links;
  }

  /**
   * Format price for output
   *
   * @param float $value
   *        The price to format
   *
   * @return string
   *         The formatted price string
   */
  private function _formatPrice($value)
  {
    return parseOutput(sprintf(ConfigHelper::get('cp_currency_format'), $value), 99);
  }

  private function _getContent()
  {
    global $_LANG, $_LANG2;

    // there can not be created a new order from backend, so edit is the only function
    $function = 'edit';

    $countries = $this->_configHelper->getCountries('countries', true, $this->site_id, 0);
    $datetimeFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'op');
    $dateFormat = $this->_configHelper->getDateFormat($this->_user->getLanguage(), 'op');

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}contentitem_cp_order o "
         . " JOIN {$this->table_prefix}contentitem_cp_order_customer oc "
         . "      ON CPOID = oc.FK_CPOID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_order_shipping_address os "
         . "      ON CPOID = os.FK_CPOID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_payment_type "
         . "      ON o.FK_CYID = CYID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_shipment_mode sm "
         . "      ON o.FK_CPSID = CPSID "
         . " JOIN {$this->table_prefix}contentitem_cp_order_item oi "
         . "      ON CPOID = oi.FK_CPOID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_order_item_option io "
         . "      ON CPOIID = io.FK_CPOIID "
         . " WHERE CPOID = $this->item_id "
         . " ORDER BY CPOIPosition ASC, CPOIOPosition ASC ";
    $result = $this->db->query($sql);

    $orderData = array();
    $orderItems = array();
    while ($row = $this->db->fetch_row($result)) {
      $tmpOrderItemId = (int)$row['CPOIID'];
      if (!isset($orderItems[$tmpOrderItemId])) {
        $orderItems[$tmpOrderItemId] = array(
          'op_order_item_id'         => $tmpOrderItemId,
          'op_order_item_title'      => parseOutput($row['CPOITitle']),
          'op_order_item_number'     => parseOutput($row['CPOINumber']),
          'op_order_item_position'   => $row['CPOIPosition'],
          'op_order_item_quantity'   => $row['CPOIQuantity'],
          'op_order_item_sum'        => $this->_formatPrice($row['CPOISum']),
          'op_order_item_unit_price' => $this->_formatPrice($row['CPOIUnitPrice']),
          'op_order_item_price'      => $this->_formatPrice($row['CPOIProductPrice']),
          'op_order_item_options'    => '',
        );
      }

      // add selected order item option
      if ($row['FK_CPOIID']) {
        $tmpPrice = $this->_formatPrice($row['CPOIOPrice']);
        $orderItems[$tmpOrderItemId]['op_order_item_options'] .= sprintf($_LANG['op_order_item_option_line'], parseOutput($row['CPOIOName']), $tmpPrice, $this->_currency);
      }

      // order data has to be stored once only, continue if set before
      if (!empty($orderData))
        continue;

      $orderData = array(
        // order data
        'op_order_id_plain' => $row['CPOID'],
        'op_order_id' => $this->_getOrderNumber($row['CPOID'], ConfigHelper::get('cp_order_number_invoice_format')),
        'op_order_date_create' => date($datetimeFormat, ContentBase::strToTime($row['CPOCreateDateTime'])),
        'op_order_date_change' => date($datetimeFormat, ContentBase::strToTime($row['CPOChangeDateTime'])),
        'op_order_total_price' => $this->_formatPrice($row['CPOTotalPrice']),
        'op_order_total_tax' => $this->_formatPrice($row['CPOTotalTax']),
        'op_order_total_price_without_tax' => $this->_formatPrice($row['CPOTotalPriceWithoutTax']),
        'op_order_status' => $_LANG['op_status_label'][$row['CPOTransactionStatus']],
        'op_order_shipping_cost' => $this->_formatPrice($row['CPOShippingCost']),
        'op_order_shipping_type' => parseOutput($row['CPSName']),
        'op_order_payment_cost' => $this->_formatPrice($row['CPOPaymentCost']),
        'op_order_payment_type' => parseOutput($row['CYName']),
        'op_order_sum' => $this->_formatPrice($row['CPOTotalPrice'] + $row['CPOShippingCost'] + $row['CPOPaymentCost']),
        'op_order_pdf_title' => $this->_getInvoiceFilename($row['CPOID']),
        'op_order_pdf_link'  => $this->_getInvoicePath($row['CPOID']), // Get FE file path

        'op_order_customer_company' => parseOutput($row['CPOCCompany']),
        'op_order_customer_position' => parseOutput($row['CPOCPosition']),
        'op_order_customer_foa' => isset($_LANG['op_foas'][$row['FK_FID']]) ? $_LANG['op_foas'][$row['FK_FID']] : '',
        'op_order_customer_title' => parseOutput($row['CPOCTitle']),
        'op_order_customer_firstname' => parseOutput($row['CPOCFirstname']),
        'op_order_customer_lastname' => parseOutput($row['CPOCLastName']),
        'op_order_customer_birthday' => ContentBase::strToTime($row['CPOCBirthday']) ? date($dateFormat, ContentBase::strToTime($row['CPOCBirthday'])) : '',
        'op_order_customer_country' => parseOutput($countries[$row['CPOCCountry']]),
        'op_order_customer_zip' => parseOutput($row['CPOCZIP']),
        'op_order_customer_city' => parseOutput($row['CPOCCity']),
        'op_order_customer_address' => parseOutput($row['CPOCAddress']),
        'op_order_customer_phone' => parseOutput($row['CPOCPhone']),
        'op_order_customer_email' => parseOutput($row['CPOCEmail']),
        'op_order_customer_text1' => parseOutput($row['CPOCText1']),
        'op_order_customer_text2' => parseOutput($row['CPOCText2']),
        'op_order_customer_text3' => parseOutput($row['CPOCText3']),
        'op_order_customer_text4' => parseOutput($row['CPOCText4']),
        'op_order_customer_text5' => parseOutput($row['CPOCText5']),

        'op_order_shipping_id' => (int)$row['CPOSID'],
        'op_order_shipping_company' => parseOutput($row['CPOSCompany']),
        'op_order_shipping_position' => parseOutput($row['CPOSPosition']),
        'op_order_shipping_foa' => parseOutput($row['CPOSFoa']),
        'op_order_shipping_title' => parseOutput($row['CPOSTitle']),
        'op_order_shipping_firstname' => parseOutput($row['CPOSFirstname']),
        'op_order_shipping_lastname' => parseOutput($row['CPOSLastName']),
        'op_order_shipping_birthday' => ContentBase::strToTime($row['CPOSBirthday']) ? date($dateFormat, ContentBase::strToTime($row['CPOSBirthday'])) : '',
        'op_order_shipping_zip' => parseOutput($row['CPOSZIP']),
        'op_order_shipping_city' => parseOutput($row['CPOSCity']),
        'op_order_shipping_address' => parseOutput($row['CPOSAddress']),
        'op_order_shipping_phone' => parseOutput($row['CPOSPhone']),
        'op_order_shipping_email' => parseOutput($row['CPOSEmail']),
        'op_order_shipping_text1' => parseOutput($row['CPOSText1']),
        'op_order_shipping_text2' => parseOutput($row['CPOSText2']),
        'op_order_shipping_text3' => parseOutput($row['CPOSText3']),
        'op_order_shipping_text4' => parseOutput($row['CPOSText4']),
        'op_order_shipping_text5' => parseOutput($row['CPOSText5']),
      );
    }
    $this->db->free_result($result);

    $orderInfo = array();
    $sql = " SELECT CPIID, CPIName, CPIPosition, CPIType, CPOIValue "
         . " FROM {$this->table_prefix}contentitem_cp_order o "
         . " JOIN {$this->table_prefix}contentitem_cp_order_info of "
         . "      ON of.FK_CPOID = CPOID "
         . " JOIN {$this->table_prefix}contentitem_cp_info ci "
         . "      ON of.FK_CPIID = CPIID "
         . " WHERE CPOID = $this->item_id "
         . " ORDER BY CPIPosition ASC ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $value = $row['CPOIValue'];
      switch ( $row['CPIType'] ) {
        case 'checkbox':
          $value = $_LANG['op_order_info_checkbox_'.(int)$value.'_label'];
          break;
        default :
          $value = parseOutput($value);
      }

      $orderInfo[] = array(
        'op_order_info_name'     => parseOutput($row['CPIName']),
        'op_order_info_position' => (int)$row['CPIPosition'],
        'op_order_info_value'    => $value,
      );
    }
    $this->db->free_result($result);

    $orderCartSettings = array();
    $sql = " SELECT CPOCTitle, CPOCPosition, CPOCQuantity, CPOCSum, CPOCUnitPrice "
         . " FROM {$this->table_prefix}contentitem_cp_order_cartsetting "
         . " WHERE FK_CPOID = $this->item_id "
         . " ORDER BY CPOCPosition ASC ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $orderCartSettings[] = array(
        'op_order_cartsetting_title'      => parseOutput($row['CPOCTitle']),
        'op_order_cartsetting_position'   => (int)$row['CPOCPosition'],
        'op_order_cartsetting_quantity'   => (int)$row['CPOCQuantity'],
        'op_order_cartsetting_sum'        => (float)$row['CPOCSum'],
        'op_order_cartsetting_unit_price' => (float)$row['CPOCUnitPrice'],
      );
    }
    $this->db->free_result($result);

    $hiddenFields = '<input type="hidden" name="action" value="mod_shopplusmgmt" />'
                  . '<input type="hidden" name="action2" value="main;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $this->tpl->load_tpl('content_op', 'modules/ModuleShopPlusManagement.tpl');
    $this->tpl->parse_if('content_op', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('op'));
    $this->tpl->parse_if('content_op', 'shipping_address', $orderData['op_order_shipping_id']);
    $this->tpl->parse_loop('content_op', $orderItems, 'order_items');
    $this->tpl->parse_loop('content_op', $orderInfo, 'order_info');
    $this->tpl->parse_loop('content_op', $orderCartSettings, 'order_cartsettings');
    $op_content = $this->tpl->parsereturn('content_op', array_merge(array(
      'op_function_label'      => $_LANG['op_function_'.$function.'_label'],
      'op_function_label2'     => $_LANG['op_function_'.$function.'_label2'],
      'op_action'              => 'index.php',
      'op_hidden_fields'       => $hiddenFields,
      'op_module_action_boxes' => '',
      'op_currency'            => $this->_currency,
    ), $orderData, $_LANG2['op']));

    return array(
      'content'      => $op_content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Get csv
   */
  private function _getCsv()
  {
    global $_LANG;

    // save log information
    $this->db->query("INSERT INTO ".$this->table_prefix."module_shopplusmgmt_log (LDateTime,FK_UID,LAction) VALUES ('".date("Y-m-d H:i")."', '".$this->_user->getID()."', 'export')");

    // Retrieve country settings for content type SC (shopping cart)
    $countries = $this->_configHelper->getCountries('countries', false, $this->site_id, 0);
    $datetimeFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'op');
    $dateFormat = $this->_configHelper->getDateFormat($this->_user->getLanguage(), 'op');

    //$separator = ";";
    $separator = "\t";
    //header('Content-Type: text/x-csv');
    header("Content-Type: application/vnd.ms-excel");
    //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
    header("Content-Disposition: inline; filename=\"orders_export".date("Y-m-d").".xls\"");
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');

    // output title rows
    // commmon data first
    echo parseCSVOutput($_LANG["op_order_id_label"]).$separator
        .parseCSVOutput($_LANG["op_order_status_label"]).$separator
        .parseCSVOutput($_LANG["op_order_date_create_label"]).$separator
        .parseCSVOutput($_LANG["op_order_date_change_label"]).$separator
        .parseCSVOutput($_LANG["op_order_sum_label"]).$separator
        .parseCSVOutput($_LANG["op_order_total_price_label"]).$separator
        .parseCSVOutput($_LANG["op_order_total_tax_label"]).$separator
        .parseCSVOutput($_LANG["op_order_total_price_without_tax_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_type_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_cost_label"]).$separator
        .parseCSVOutput($_LANG["op_order_payment_type_label"]).$separator
        .parseCSVOutput($_LANG["op_order_payment_cost_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_company_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_position_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_foa_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_title_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_firstname_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_lastname_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_birthday_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_country_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_zip_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_city_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_address_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_phone_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_email_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_text1_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_text2_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_text3_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_text4_label"]).$separator
        .parseCSVOutput($_LANG["op_order_customer_text5_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_company_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_position_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_foa_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_title_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_firstname_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_lastname_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_birthday_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_zip_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_city_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_address_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_phone_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_email_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_text1_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_text2_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_text3_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_text4_label"]).$separator
        .parseCSVOutput($_LANG["op_order_shipping_text5_label"]).$separator;

    // order info
    $sql = " SELECT CPIID, CPIName "
         . " FROM {$this->table_prefix}contentitem_cp_order_info of "
         . " JOIN {$this->table_prefix}contentitem_cp_info ci "
         . "      ON of.FK_CPIID = CPIID "
         . " GROUP BY CPIID, CPIName "
         . " ORDER BY CPIPosition ASC ";
    $assoc = $this->db->GetAssoc($sql);

    foreach ($assoc as $val)
      echo  parseCSVOutput($val).$separator;

    // order item
    echo parseCSVOutput($_LANG["op_order_item_number_label"]).$separator
        .parseCSVOutput($_LANG["op_order_item_title_label"]).$separator
        .parseCSVOutput($_LANG["op_order_item_quantity_label"]).$separator
        .parseCSVOutput($_LANG["op_order_item_sum_label"]).$separator
        .parseCSVOutput($_LANG["op_order_item_unit_price_label"]).$separator
        .parseCSVOutput($_LANG["op_order_item_price_label"]).$separator;

    // options selected for order item
    $numberOfOptions = (int)ConfigHelper::get('pp_number_of_options');
    for ($i = 1; $i <= $numberOfOptions; $i++) {
      echo  parseCSVOutput($_LANG["op_option_title_label"]." $i").$separator
           .parseCSVOutput($_LANG["op_option_price_label"]." $i").$separator;
    }

    echo "\n";

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}contentitem_cp_order o "
         . " JOIN {$this->table_prefix}contentitem_cp_order_customer oc "
         . "      ON CPOID = oc.FK_CPOID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_order_shipping_address os "
         . "      ON CPOID = os.FK_CPOID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_payment_type "
         . "      ON o.FK_CYID = CYID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_shipment_mode sm "
         . "      ON o.FK_CPSID = CPSID "
         . " JOIN {$this->table_prefix}contentitem_cp_order_item oi "
         . "      ON CPOID = oi.FK_CPOID "
         . " LEFT JOIN {$this->table_prefix}contentitem_cp_order_item_option io "
         . "      ON CPOIID = io.FK_CPOIID "
         . " WHERE CPOTransactionStatus > 0 "
         . " ORDER BY CPOCreateDateTime DESC, CPOID DESC, CPOIPosition ASC, CPOIOPosition ASC ";
    $result = $this->db->query($sql);

    $orderData = array();
    $orderItems = array();
    $orderInfo = array();
    $cachedId = null;
    while ($row = $this->db->fetch_row($result)) {
      $orderId = (int)$row['CPOID'];
      // init cache for first call
      if ($cachedId === null) $cachedId = $orderId;

      // the order id changed, we know that data for previous order ( = cachedId )
      // has been successfully read, so fetch the csv line for previous order
      if ($cachedId != $orderId) {
        // handle cart settings same as products
        $sql = " SELECT CPOCTitle, CPOCPosition, CPOCQuantity, CPOCSum, CPOCUnitPrice "
             . " FROM {$this->table_prefix}contentitem_cp_order_cartsetting "
             . " WHERE FK_CPOID = $cachedId "
             . " ORDER BY CPOCPosition ASC ";
        $result1 = $this->db->query($sql);

        while ($row1 = $this->db->fetch_row($result1)) {
          $orderItems[] = array(
            'op_order_item_id'         => 0,
            'op_order_item_title'      => parseOutput($row1['CPOCTitle']),
            'op_order_item_number'     => '',
            'op_order_item_position'   => $row1['CPOCPosition'],
            'op_order_item_quantity'   => $row1['CPOCQuantity'],
            'op_order_item_sum'        => $this->_formatPrice($row1['CPOCSum']),
            'op_order_item_unit_price' => $this->_formatPrice($row1['CPOCUnitPrice']),
            'op_order_item_price'      => '',
            'op_order_item_options'    => array(),
          );
        }

        echo $this->_getCsvLine($separator, $orderData, $orderItems, $orderInfo);
        // reset data
        $orderData = array();
        $orderItems = array();
        $orderInfo = array();
      }
      // cache the id of current order
      $cachedId = $orderId;

      $tmpOrderItemId = (int)$row['CPOIID'];
      if (!isset($orderItems[$tmpOrderItemId])) {
        $orderItems[$tmpOrderItemId] = array(
          'op_order_item_id'         => $tmpOrderItemId,
          'op_order_item_title'      => parseOutput($row['CPOITitle']),
          'op_order_item_number'     => parseOutput($row['CPOINumber']),
          'op_order_item_position'   => $row['CPOIPosition'],
          'op_order_item_quantity'   => $row['CPOIQuantity'],
          'op_order_item_sum'        => $this->_formatPrice($row['CPOISum']),
          'op_order_item_unit_price' => $this->_formatPrice($row['CPOIUnitPrice']),
          'op_order_item_price'      => $this->_formatPrice($row['CPOIProductPrice']),
          'op_order_item_options'    => array(),
        );
      }

      // add selected order item option
      if ($row['FK_CPOIID']) {
        $tmpPrice = $this->_formatPrice($row['CPOIOPrice']);
        $orderItems[$tmpOrderItemId]['op_order_item_options'][] = array(
          'name' => $row['CPOIOName'],
          'price' => $tmpPrice
        );
      }

      // order data has to be stored once only, continue if set before
      if (!empty($orderData))
        continue;

      $orderData = array(
        // order data
        'op_order_id' => $this->_getOrderNumber($row['CPOID'], ConfigHelper::get('cp_order_number_invoice_format')),
        'op_order_date_create' => date($datetimeFormat, ContentBase::strToTime($row['CPOCreateDateTime'])),
        'op_order_date_change' => date($datetimeFormat, ContentBase::strToTime($row['CPOChangeDateTime'])),
        'op_order_total_price' => $this->_formatPrice($row['CPOTotalPrice']),
        'op_order_total_tax' => $this->_formatPrice($row['CPOTotalTax']),
        'op_order_total_price_without_tax' => $this->_formatPrice($row['CPOTotalPriceWithoutTax']),
        'op_order_status' => $_LANG['op_status_label'][$row['CPOTransactionStatus']],
        'op_order_shipping_cost' => $this->_formatPrice($row['CPOShippingCost']),
        'op_order_shipping_type' => parseOutput($row['CPSName']),
        'op_order_payment_cost' => $this->_formatPrice($row['CPOPaymentCost']),
        'op_order_payment_type' => parseOutput($row['CYName']),
        'op_order_sum' => $this->_formatPrice($row['CPOTotalPrice'] + $row['CPOShippingCost'] + $row['CPOPaymentCost']),
        'op_order_pdf_title' => $this->_getInvoiceFilename($row['CPOID']),
        'op_order_pdf_link'  => $this->_getInvoicePath($row['CPOID']), // Get FE file path

        'op_order_customer_company' => parseOutput($row['CPOCCompany']),
        'op_order_customer_position' => parseOutput($row['CPOCPosition']),
        'op_order_customer_foa' => isset($_LANG['op_foas'][$row['FK_FID']]) ? $_LANG['op_foas'][$row['FK_FID']] : '',
        'op_order_customer_title' => parseOutput($row['CPOCTitle']),
        'op_order_customer_firstname' => parseOutput($row['CPOCFirstname']),
        'op_order_customer_lastname' => parseOutput($row['CPOCLastName']),
        'op_order_customer_birthday' => ContentBase::strToTime($row['CPOCBirthday']) ? date($dateFormat, ContentBase::strToTime($row['CPOCBirthday'])) : '',
        'op_order_customer_country' => parseOutput($countries[$row['CPOCCountry']]),
        'op_order_customer_zip' => parseOutput($row['CPOCZIP']),
        'op_order_customer_city' => parseOutput($row['CPOCCity']),
        'op_order_customer_address' => parseOutput($row['CPOCAddress']),
        'op_order_customer_phone' => parseOutput($row['CPOCPhone']),
        'op_order_customer_email' => parseOutput($row['CPOCEmail']),
        'op_order_customer_text1' => parseOutput($row['CPOCText1']),
        'op_order_customer_text2' => parseOutput($row['CPOCText2']),
        'op_order_customer_text3' => parseOutput($row['CPOCText3']),
        'op_order_customer_text4' => parseOutput($row['CPOCText4']),
        'op_order_customer_text5' => parseOutput($row['CPOCText5']),

        'op_order_shipping_company' => parseOutput($row['CPOSCompany']),
        'op_order_shipping_position' => parseOutput($row['CPOSPosition']),
        'op_order_shipping_foa' => parseOutput($row['CPOSFoa']),
        'op_order_shipping_title' => parseOutput($row['CPOSTitle']),
        'op_order_shipping_firstname' => parseOutput($row['CPOSFirstname']),
        'op_order_shipping_lastname' => parseOutput($row['CPOSLastName']),
        'op_order_shipping_birthday' => ContentBase::strToTime($row['CPOSBirthday']) ? date($dateFormat, ContentBase::strToTime($row['CPOSBirthday'])) : '',
        'op_order_shipping_zip' => parseOutput($row['CPOSZIP']),
        'op_order_shipping_city' => parseOutput($row['CPOSCity']),
        'op_order_shipping_address' => parseOutput($row['CPOSAddress']),
        'op_order_shipping_phone' => parseOutput($row['CPOSPhone']),
        'op_order_shipping_email' => parseOutput($row['CPOSEmail']),
        'op_order_shipping_text1' => parseOutput($row['CPOSText1']),
        'op_order_shipping_text2' => parseOutput($row['CPOSText2']),
        'op_order_shipping_text3' => parseOutput($row['CPOSText3']),
        'op_order_shipping_text4' => parseOutput($row['CPOSText4']),
        'op_order_shipping_text5' => parseOutput($row['CPOSText5']),
      );

      $sql = " SELECT CPIID, CPIName, CPIPosition, CPIType, CPOIValue "
           . " FROM {$this->table_prefix}contentitem_cp_order o "
           . " JOIN {$this->table_prefix}contentitem_cp_order_info of "
           . "      ON of.FK_CPOID = CPOID "
           . " JOIN {$this->table_prefix}contentitem_cp_info ci "
           . "      ON of.FK_CPIID = CPIID "
           . " WHERE CPOID = $orderId "
           . " ORDER BY CPIPosition ASC ";
      $result0 = $this->db->query($sql);

      while ($row0 = $this->db->fetch_row($result0)) {
        $value = $row0['CPOIValue'];
        switch ( $row0['CPIType'] ) {
          case 'checkbox':
            $value = $_LANG['op_order_info_checkbox_'.(int)$value.'_label'];
            break;
          default :
            $value = parseOutput($value);
        }
        $orderInfo[]['op_order_info_value'] = $value;
      }
      $this->db->free_result($result0);

    }
    $this->db->free_result($result);

    exit();
  }

  /**
   * Get a csv product order line ( or multiple lines )
   *
   * @param string $separator
   * @param array $data
   * @param array $items
   * @param array $info
   *
   * @return string
   *         the csv line(s)
   */
  private function _getCsvLine($separator, $data, $items, $info)
  {
    global $_LANG;

    $line = '';

    $dataLine = parseCSVOutput($data['op_order_id']).$separator
              . parseCSVOutput($data['op_order_status']).$separator
              . parseCSVOutput($data['op_order_date_create']).$separator
              . parseCSVOutput($data['op_order_date_change']).$separator
              . parseCSVOutput($data['op_order_sum']).$separator
              . parseCSVOutput($data['op_order_total_price']).$separator
              . parseCSVOutput($data['op_order_total_tax']).$separator
              . parseCSVOutput($data['op_order_total_price_without_tax']).$separator
              . parseCSVOutput($data['op_order_shipping_type']).$separator
              . parseCSVOutput($data['op_order_shipping_cost']).$separator
              . parseCSVOutput($data['op_order_payment_type']).$separator
              . parseCSVOutput($data['op_order_payment_cost']).$separator

              . parseCSVOutput($data['op_order_customer_company']).$separator
              . parseCSVOutput($data['op_order_customer_position']).$separator
              . parseCSVOutput($data['op_order_customer_foa']).$separator
              . parseCSVOutput($data['op_order_customer_title']).$separator
              . parseCSVOutput($data['op_order_customer_firstname']).$separator
              . parseCSVOutput($data['op_order_customer_lastname']).$separator
              . parseCSVOutput($data['op_order_customer_birthday']).$separator
              . parseCSVOutput($data['op_order_customer_country']).$separator
              . parseCSVOutput($data['op_order_customer_zip']).$separator
              . parseCSVOutput($data['op_order_customer_city']).$separator
              . parseCSVOutput($data['op_order_customer_address']).$separator
              . parseCSVOutput($data['op_order_customer_phone']).$separator
              . parseCSVOutput($data['op_order_customer_email']).$separator
              . parseCSVOutput($data['op_order_customer_text1']).$separator
              . parseCSVOutput($data['op_order_customer_text2']).$separator
              . parseCSVOutput($data['op_order_customer_text3']).$separator
              . parseCSVOutput($data['op_order_customer_text4']).$separator
              . parseCSVOutput($data['op_order_customer_text5']).$separator

              . parseCSVOutput($data['op_order_shipping_company']).$separator
              . parseCSVOutput($data['op_order_shipping_position']).$separator
              . parseCSVOutput($data['op_order_shipping_foa']).$separator
              . parseCSVOutput($data['op_order_shipping_title']).$separator
              . parseCSVOutput($data['op_order_shipping_firstname']).$separator
              . parseCSVOutput($data['op_order_shipping_lastname']).$separator
              . parseCSVOutput($data['op_order_shipping_birthday']).$separator
              . parseCSVOutput($data['op_order_shipping_zip']).$separator
              . parseCSVOutput($data['op_order_shipping_city']).$separator
              . parseCSVOutput($data['op_order_shipping_address']).$separator
              . parseCSVOutput($data['op_order_shipping_phone']).$separator
              . parseCSVOutput($data['op_order_shipping_email']).$separator
              . parseCSVOutput($data['op_order_shipping_text1']).$separator
              . parseCSVOutput($data['op_order_shipping_text2']).$separator
              . parseCSVOutput($data['op_order_shipping_text3']).$separator
              . parseCSVOutput($data['op_order_shipping_text4']).$separator
              . parseCSVOutput($data['op_order_shipping_text5']).$separator;

    foreach ($info as $val)
      $dataLine .= parseCSVOutput($val['op_order_info_value']).$separator;

    $commonColCount = count($data) + count($info);
    $emptyDataLine = '';
    for ($j = 0; $j < $commonColCount; $j++)
      $emptyDataLine .= $separator;

    $optionCount = ConfigHelper::get('pp_number_of_options');
    $skip = false;
    foreach ($items as $val) {
      $tmp = parseCSVOutput($val["op_order_item_number"]).$separator
            .parseCSVOutput($val["op_order_item_title"]).$separator
            .parseCSVOutput($val["op_order_item_quantity"]).$separator
            .parseCSVOutput($val["op_order_item_sum"]).$separator
            .parseCSVOutput($val["op_order_item_unit_price"]).$separator
            .parseCSVOutput($val["op_order_item_price"]).$separator;

      foreach ($val['op_order_item_options'] as $option) {
        $tmp .=parseCSVOutput($option["name"]).$separator
              .parseCSVOutput($option["price"]).$separator;
      }

      if (count($val['op_order_item_options']) < $optionCount) {
        $difference = $optionCount - count($val['op_order_item_options']);
        for ($j = 0; $j < $difference; $j++ ) {
          $tmp .= $separator.$separator;
        }
      }

      $orderLines[] = $tmp;
    }

    // create csv lines - only the first line contains common order date,
    // additional lines contain order item data only
    $first = true;
    foreach ($orderLines as $val) {
      if ($first) {
        $line .= $dataLine.$val."\n";
        $first = false;
      }
      else
        $line .= $emptyDataLine.$val."\n";
    }

    return $line;
  }

  /**
   * Returns the invoice file name.
   *
   * @param int $orderId
   *        The id of order to retrieve invoice for
   *
   * @return string
   *         The invoice path
   */
  private function _getInvoiceFilename($orderId)
  {
    $pathParts = explode('/', ConfigHelper::get('cp_order_invoice_file'));

    return $pathParts[count($pathParts) - 1]
         . $this->_getOrderNumber($orderId, ConfigHelper::get('cp_order_number_file_format'))
         . '.pdf';
  }

  /**
   * Returns the invoice file path.
   *
   * @param int $orderId
   *        The id of order to retrieve invoice for
   *
   * @return string
   *         The invoice path
   */
  private function _getInvoicePath($orderId)
  {
    $path = '../' . ConfigHelper::get('cp_order_invoice_file');

    $path = $this->_fileUrlForBackendUser($path);

    return $path
         . $this->_getOrderNumber($orderId, ConfigHelper::get('cp_order_number_file_format'))
         . '.pdf';
  }

  /**
   * Returns the order number formatted from order id.
   *
   * The format used is retrieved from $_CONFIG["cp_order_number_invoice_format"]
   *
   * @param int $id
   *        The order id from database
   * @param string $format
   *        An order number format string. Use
   *        - $_CONFIG["cp_order_number_invoice_format"]
   *        - $_CONFIG["cp_order_number_file_format"]
   *        of format "< chars >/< date >/< chars >/< number >/< chars >".
   *
   * @return string
   *         The formatted order number, an empty string if order number could
   *         not be created
   */
  private function _getOrderNumber($id, $format)
  {
    $number = '';

    if (isset($this->_formattedOrderNumbers[$id])) {
      return $this->_formattedOrderNumbers[$id];
    }

    // special format i.e.
    // 20111015_125, 20111015_126, ...
    // whereas the date is retrieved from order datetime and the number
    // is the order rank i.e. 12th order of today added to a defined starting
    // value ( $_CONFIG["cp_order_number_offset_value"] )

    $sql = " SELECT CPOCreateDateTime "
         . " FROM {$this->table_prefix}contentitem_cp_order "
         . " WHERE CPOID = {$this->db->escape($id)} ";
    $datetime = $this->db->GetOne($sql);

    $timestamp = ContentBase::strToTime($datetime);

    if ($timestamp) { // order exists
      $sqlDate = date('Y-m-d', $timestamp);

      $offsetValue = ConfigHelper::get('cp_order_number_offset_value');
      // special number = number per order, beginning with defined value each day
      if ($offsetValue !== 0) {
        // get all orders from day the requested order was made
        $sql = " SELECT CPOID "
             . " FROM {$this->table_prefix}contentitem_cp_order "
             . " WHERE CPOCreateDateTime LIKE '$sqlDate%' "
             . " ORDER BY CPOCreateDateTime ASC ";
        $orders = $this->db->GetCol($sql);

        $count = 0;
        $pos = 0;
        foreach ($orders as $tmpId) {
          $count++;                         // count orders
          if ($id == $tmpId) $pos = $count; // store position of requested order
        }

        $numberPart = $offsetValue + ($pos - 1);
      }
      else { // number part is id from db
        $numberPart = $id;
      }

      $parts = explode("/", $format); //<chars>/<date>/<chars>/<number>/<chars>

      $parts[1] = date($parts[1], ContentBase::strToTime($datetime));
      $parts[3] = sprintf($parts[3], $numberPart);

      $number = implode("", $parts);
    }

    $this->_formattedOrderNumbers[$id] = $number; // cache formatted order number
    return $number;

  }

  /**
   * List content
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    $get = new Input(Input::SOURCE_GET);
    // check if order payment status has to be set to paid
    if ($get->readInt('item') && $get->readInt('status') && $get->readInt('status') == 1) {
      // check if item has already been paid
      $sql = " SELECT CPOTransactionStatus "
           . " FROM {$this->table_prefix}contentitem_cp_order "
           . " WHERE CPOID = {$get->readInt('item')} ";
      $tmpStatus = $this->db->GetOne($sql);

      // not paid yet
      if ($tmpStatus == 2) {
        $this->db->query("INSERT INTO ".$this->table_prefix."module_shopplusmgmt_log (LDateTime,FK_UID,LAction) VALUES ('".date("Y-m-d H:i")."', '".$this->_user->getID()."', 'status 1')");

        $now = date('Y-m-d H:i:s');
        $sql = " UPDATE {$this->table_prefix}contentitem_cp_order "
             . " SET CPOTransactionStatus = 1, "
             . "     CPOChangeDateTime = '$now' "
             . " WHERE CPOID = {$get->readInt('item')} ";
        $this->db->query($sql);

        $this->setMessage(Message::createSuccess($_LANG['op_message_item_paid']));
      }
    }

    $data = $this->_grid()->get_result();
    if (is_array($data)) {
      $i = 1;
      foreach ($data as $key => &$value) {
        $row = $this->_grid()->get_grid_data($key);
        $id = $row['CPOID'];

        $value['op_date']             = date('Y-m-d', ContentBase::strToTime($row['CPOCreateDateTime']));
        $value['op_id_plain']         = $row['CPOID'];
        $value['op_id']               = $this->_getOrderNumber($row['CPOID'], ConfigHelper::get('cp_order_number_invoice_format'));
        $value['op_price']            = $this->_formatPrice($row['CPOTotalPrice']);
        $value['op_status']           = $_LANG['op_status_label'][$row['CPOTransactionStatus']];
        $value['op_status_id']        = $row['CPOTransactionStatus'];
        $value['op_address']          = parseOutput($row['CPOCAddress']);
        $value['op_city']             = parseOutput($row['CPOCCity']);
        $value['op_country']          = isset($countries[$row['CPOCCountry']]) ? $countries[$row['CPOCCountry']] : '';
        $value['op_firstname']        = parseOutput($row['CPOCFirstname']);
        $value['op_lastname']         = parseOutput($row['CPOCLastname']);
        $value['op_zip']              = parseOutput($row['CPOCZIP']);
        $value['op_content_link']     = $this->_parseUrl('edit', array('page' => $id));
        $value['op_status_paid_link'] = $this->_parseUrl('', array('item' => $id, 'status' => 1));
        $value['op_row_bg']           = ( $i++ %2 ) ? 'even' : 'odd';
      }
    }
    else {
      $this->setMessage($data);
    }

    $currentSel = $this->_grid()->get_page_selection();
    $currentRows = $this->_grid()->get_quantity_selected_rows();
    $showResetButton = $this->_grid()->isFilterSet() ||
      $this->_grid()->isOrderSet() ||
      $this->_grid()->isOrderControlsSet();

    $tplName = 'module_shopplusmgmt_list';
    $this->tpl->load_tpl($tplName, 'modules/ModuleShopPlusManagement_list.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(),
        $this->_getMessageTemplateArray('op'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');
    foreach ($data as $val) {
      $this->tpl->parse_if($tplName, 'status_paid_'.$val['op_id_plain'], $val['op_status_id'] == 1);
      $this->tpl->parse_if($tplName, 'status_missing_'.$val['op_id_plain'], $val['op_status_id'] == 2);
    }

    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl()), array (
        'op_action'                => $this->_parseUrl(),
        'op_count_all'             => $this->_grid()->get_quantity_total_rows(),
        'op_count_current'         => $currentRows,
        'op_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl() . '&amp;op_page=','_bottom'),
        'op_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
        'op_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl() . '&amp;op_page=','_top'),
        'op_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['op']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(),
    );
  }
}
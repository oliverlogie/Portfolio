<?php

/**
 * Order management module class
 *
 * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleOrderManagement extends Module
{

  protected $_prefix = 'om';
  /**
   * The currency
   *
   * @var string
   */
  private $_currency = '';
  /**
   * Content handler
   */
  public function show_innercontent()
  {
    $this->_currency = ConfigHelper::get('site_currencies', '', $this->site_id);

    if (isset($this->action[0]) && $this->action[0] == "export")
      return $this->_getCsv();
    else
      return $this->_listContent();
  }

  protected function _getContentLeftLinks()
  {
    return array(
        array($this->_parseUrl('export'), $this->_langVar('export_label'))
    );
  }

  private function _formatOrderId($id)
  {
    return sprintf(ConfigHelper::get('sc_order_id_format'), (int)$id);
  }

  /**
   * Formats a price value
   *
   * @param int | float $value
   *        the value to format as price
   *
   * @return string
   *         the formatted price value
   */
  private function _formatPrice($value)
  {
    return sprintf(ConfigHelper::get('om_currency_format'), $value);
  }

  /**
   * Get csv
   */
  private function _getCsv()
  {
    global $_LANG;

    // save log information
    $result0 = $this->db->query("INSERT INTO ".$this->table_prefix."module_order_export (EDateTime,FK_UID) VALUES ('".date("Y-m-d H:i")."', '".$this->_user->getID()."')");

    // Retrieve country settings for content type SC (shopping cart)
    $countries = $this->_configHelper->getCountries('c_sc_countries', false, $this->site_id, 19);
    $itemCsvFormat = ConfigHelper::get('om_item_csv_format');

    //header('Content-Type: text/x-csv');
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: inline; filename=\"orders_export".date("Y-m-d").".xls\"");
    //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');

    // output title row
    echo parseCSVOutput($_LANG["om_id_label"])."\t"
        .parseCSVOutput($_LANG["om_total_price_including_shipping_cost_label"])."\t"
        .parseCSVOutput($_LANG["om_total_price_label"])."\t"
        .parseCSVOutput($_LANG["om_total_tax_label"])."\t"
        .parseCSVOutput($_LANG["om_total_price_without_tax_label"])."\t"
        .parseCSVOutput($_LANG["om_shipping_cost_label"])."\t"
        .parseCSVOutput($_LANG["om_payment_type_label"])."\t"
        .parseCSVOutput($_LANG["om_createdate_label"])."\t"
        .parseCSVOutput($_LANG["om_changedate_label"])."\t"
        .parseCSVOutput($_LANG["om_items_label"])."\t"
        .parseCSVOutput($_LANG["om_company_label"])."\t"
        .parseCSVOutput($_LANG["om_position_label"])."\t"
        .parseCSVOutput($_LANG["om_foa_label"])."\t"
        .parseCSVOutput($_LANG["om_title_label"])."\t"
        .parseCSVOutput($_LANG["om_firstname_label"])."\t"
        .parseCSVOutput($_LANG["om_lastname_label"])."\t"
        .parseCSVOutput($_LANG["om_address_label"])."\t"
        .parseCSVOutput($_LANG["om_country_label"])."\t"
        .parseCSVOutput($_LANG["om_zip_label"])."\t"
        .parseCSVOutput($_LANG["om_city_label"])."\t"
        .parseCSVOutput($_LANG["om_birthday_label"])."\t"
        .parseCSVOutput($_LANG["om_phone_label"])."\t"
        .parseCSVOutput($_LANG["om_mobile_phone_label"])."\t"
        .parseCSVOutput($_LANG["om_email_label"])."\t\n";

    // read and output customer data (frontend user / client)
    $sql = ' SELECT SOID, SOCreateDateTime, SOChangeDateTime, SOTotalPrice, '
         . '        SOTotalTax, SOTotalPriceWithoutTax, SOTransactionStatus, '
         . '        SOStatus, SOPaymentType, SOShippingCost, SOShippingDiscount, '
         . '        SOShippingInsurance, FK_FUID, FK_CID, '
         // frontend user order
         . '        FUCompany, FUPosition, fu.FK_FID AS USerFK_FID, FUTitle, FUFirstname, '
         . '        FULastname, FUBirthday, FUCountry, FUZIP, FUCity, FUAddress,'
         . '        FUPhone, FUEmail, '
         // client order
         . '        CCompany, CPosition, cl.FK_FID AS ClientFK_FID, '
         . '        CTitlePre, CTitlePost, CFirstname, CLastname, '
         . '        CBirthday, CCountry, CZIP, CCity, CAddress, CPhone, CMobilePhone, CEmail, '
         // ordered items
         . '        ( '
         . "          SELECT GROUP_CONCAT(CAST(CONCAT_WS('-', FK_CIID, SOIQuantity) AS CHAR)) "
         . "          FROM {$this->table_prefix}contentitem_sc_order_item "
         . "          WHERE FK_SOID = SOID "
         . '          GROUP BY SOID '
         . '        ) AS OrderedItems '
         . " FROM {$this->table_prefix}contentitem_sc_order "
         . " LEFT JOIN {$this->table_prefix}frontend_user fu "
         . '        ON FK_FUID = FUID '
         . " LEFT JOIN {$this->table_prefix}client cl "
         . '        ON FK_CID = CID '
         . " WHERE cl.FK_SID = $this->site_id "
         . '   AND SOStatus = 1 '
         . " ORDER BY SOCreateDateTime DESC ";
    $result = $this->db->query($sql);

    $sql = ' SELECT po.FK_CIID, PNumber '
         . " FROM {$this->table_prefix}contentitem_po po "
         . " JOIN {$this->table_prefix}contentitem ci "
         . '        ON po.FK_CIID = ci.CIID '
         . " WHERE ci.FK_SID = $this->site_id ";
    $tmpItems = $this->db->GetAssoc($sql);

    while ($row = $this->db->fetch_row($result))
    {
      $output = parseCSVOutput($this->_formatOrderId($row['SOID']))."\t"
              . parseCSVOutput(parseOutput($this->_formatPrice($row["SOTotalPrice"]+$row["SOShippingCost"]),99)." ".$this->_currency)."\t"
              . parseCSVOutput(parseOutput($this->_formatPrice($row["SOTotalPrice"]),99)." ".$this->_currency)."\t"
              . parseCSVOutput(parseOutput($this->_formatPrice($row["SOTotalTax"]),99)." ".$this->_currency)."\t"
              . parseCSVOutput(parseOutput($this->_formatPrice($row["SOTotalPriceWithoutTax"]),99)." ".$this->_currency)."\t"
              . parseCSVOutput(parseOutput($this->_formatPrice($row["SOShippingCost"]),99)." ".$this->_currency)."\t"
              . parseCSVOutput($_LANG["om_payment_type_labels"][$row["SOPaymentType"]])."\t"
              . date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'om'),strtotime($row["SOCreateDateTime"]))."\t"
              . ($row["SOChangeDateTime"] ? date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'om'),strtotime($row["SOChangeDateTime"])) : "")."\t";

      if ($row["OrderedItems"])
      {
        // product id and quantity (e.g 981-2)
        foreach (explode(',', $row["OrderedItems"]) as $key => $val)
        {
          // split product id and quantity
          list($id, $quantity) = explode('-', $val);
          $output .= parseCSVOutput(sprintf($itemCsvFormat, $quantity, $tmpItems[$id]));
        }
      }
      $output .= "\t";

      $foa = coalesce($row["USerFK_FID"], $row["ClientFK_FID"]);
      $country = coalesce($row["FUCountry"], $row["CCountry"]);
      $output .= parseCSVOutput(coalesce($row["FUCompany"], $row["CCompany"]))."\t"
              . parseCSVOutput(coalesce($row["FUPosition"], $row["CPosition"]))."\t"
              . ($foa ? (parseCSVOutput($_LANG["om_foas"][$foa])) : "")."\t"
              . parseCSVOutput(coalesce($row["FUTitle"], $row["CTitlePre"], $row["CTitlePost"]))."\t"
              . parseCSVOutput(coalesce($row["FUFirstname"], $row["CFirstname"]))."\t"
              . parseCSVOutput(coalesce($row["FULastname"], $row["CLastname"]))."\t"
              . parseCSVOutput(coalesce($row["FUAddress"], $row["CAddress"]))."\t"
              . (isset($countries[$country]) ? parseCSVOutput($countries[$country]) : '')."\t"
              . parseCSVOutput(coalesce($row["FUZIP"], $row["CZIP"]))."\t"
              . parseCSVOutput(coalesce($row["FUCity"], $row["CCity"]))."\t"
              . parseCSVOutput(coalesce($row["FUBirthday"], $row["CBirthday"]))."\t"
              . parseCSVOutput(coalesce($row["FUPhone"], $row["CPhone"]))."\t"
              . parseCSVOutput(coalesce($row["FUMobilePhone"], $row["CMobilePhone"]))."\t"
              . parseCSVOutput(coalesce($row["FUEmail"], $row["CEmail"]))."\t\n";
      echo $output;
    }
    $this->db->free_result($result);

    exit();
  }

  /**
   * List content
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    // read orders
    $sql = " SELECT SOID, SOCreateDateTime, SOChangeDateTime, SOTotalPrice, SOShippingCost, "
         . "        CFirstName, CLastName, CEmail, "
         . "        FUFirstname, FULastname, FUEmail "
         . " FROM {$this->table_prefix}contentitem_sc_order o "
         . " LEFT JOIN {$this->table_prefix}client "
         . "      ON FK_CID = CID "
         . " LEFT JOIN {$this->table_prefix}frontend_user "
         . "      ON FK_FUID = FUID "
         . " WHERE SOStatus = 1 "
         . "   AND o.FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    $orderItems = array ();
    while ($row = $this->db->fetch_row($result)){
      $orderItems[] = array(
        'om_id' => $this->_formatOrderId($row['SOID']),
        'om_total_price' => parseOutput($this->_formatPrice($row['SOTotalPrice']+$row['SOShippingCost']),99),
        'om_firstname' => parseOutput(coalesce($row['FUFirstname'], $row['CFirstName'])),
        'om_lastname' => parseOutput(coalesce($row['FULastname'], $row['CLastName'])),
        'om_email' => parseOutput(coalesce($row['FUEmail'], $row['CEmail'])),
      );
    }
    $this->db->free_result($result);

    $om_last_export_date = "";
    $om_last_export_time = "";
    $om_last_export_nick = "";
    $result = $this->db->query("SELECT EDateTime,UNick FROM ".$this->table_prefix."module_order_export moe LEFT JOIN ".$this->table_prefix."user u ON moe.FK_UID=u.UID ORDER BY EID DESC LIMIT 0,1");
    $row = $this->db->fetch_row($result);
    if ($row)
    {
      $om_last_export_date = date("d.m.Y", strtotime($row["EDateTime"]));
      $om_last_export_time = date("H:i", strtotime($row["EDateTime"]));
      $om_last_export_nick = parseOutput($row["UNick"]);
    }
    $this->db->free_result($result);

    if (!$orderItems){
      $this->setMessage(Message::createFailure($_LANG['om_message_no_client']));
    }

    $this->tpl->load_tpl('content_ordermgmt', 'modules/ModuleOrderManagement.tpl');
    $this->tpl->parse_if('content_ordermgmt', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('om'));
    $this->tpl->parse_loop('content_ordermgmt', $orderItems, 'order_items');
    $om_content = $this->tpl->parsereturn('content_ordermgmt', array_merge(array (
      'om_site_selection' => $this->_parseModuleSiteSelection('ordermgmt', $_LANG["om_site_label"]),
      'om_currency_label' => parseOutput($this->_currency),
      'om_id_label' => $_LANG['om_id_label'],
      'om_firstname_label' => $_LANG['om_firstname_label'],
      'om_lastname_label' => $_LANG['om_lastname_label'],
      'om_email_label' => $_LANG['om_email_label'],
      'om_total_price_including_shipping_cost_label' => $_LANG['om_total_price_including_shipping_cost_label'],
      'om_last_export_date' => $om_last_export_date,
      'om_last_export_time' => $om_last_export_time,
      'om_last_export_nick' => $om_last_export_nick,
      'om_last_export_label' => ($om_last_export_date ? sprintf($_LANG["om_last_export_label"],$om_last_export_date,$om_last_export_time,$om_last_export_nick) : ""),
    ), $_LANG2['om']));

    return array(
        'content'      => $om_content,
        'content_left' => $this->_getContentLeft(),
    );
  }
}
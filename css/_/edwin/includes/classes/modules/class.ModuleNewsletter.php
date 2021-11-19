<?php

  /**
   * Newsletter Module Class
   *
   * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ModuleNewsletter extends Module
  {

    const OUTPUT_CSV = 1;

    const OUTPUT_CSV_DMAIL = 2;

    protected $_prefix = 'nl';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent()
    {
      if (isset($_GET["did"])) $this->delete_content((int)$_GET["did"]);

      if ($this->action[0] == "export")
        return $this->get_csv();
      else if ($this->action[0] == "export_dmail")
        return $this->get_csv_dmail();
      else
        return $this->list_content();
    }

    protected function _getContentLeftLinks()
    {
      $links = array();
      if (empty($this->action[0])) { // list view displayed
        $links[] = array($this->_parseUrl('export'), $this->_langVar('export_label'));
      }

      return $links;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function delete_content($did)
    {
      global $_LANG;

      $sql = " SELECT CEmail FROM ".$this->table_prefix."client "
           . " WHERE CID=".$did;
      $mail = $this->db->GetOne($sql);
      $now = date('Y-m-d H:i:s');

      $sql = " UPDATE ".$this->table_prefix."client "
           . "    SET CNewsletterConfirmedRecipient = 0, "
           . "        CChangeDateTime = '$now' "
           . " WHERE CEmail = '$mail'";
      $this->db->query($sql);
      $this->setMessage(Message::createSuccess($_LANG['nl_message_deleteclient_success']));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Content as CSV                                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_csv()
    {
      global $_LANG;

      // save log information
      $this->db->query("INSERT INTO ".$this->table_prefix."module_newsletter_export (EDateTime,EName,FK_UID) VALUES ('".date("Y-m-d H:i")."','".$this->_user->getNick()."','".$this->_user->getID()."')");

      //header('Content-Type: text/x-csv');
      header("Content-Type: application/vnd.ms-excel");
      header("Content-Disposition: inline; filename=\"kundendaten_export".date("Y-m-d").".xls\"");
      //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache');

      // output title row
      echo $_LANG["nl_company_label"]."\t".$_LANG["nl_position_label"]."\t".$_LANG["nl_foa_label"]."\t".$_LANG["nl_title_pre_label"]."\t".$_LANG["nl_firstname_label"]."\t".$_LANG["nl_lastname_label"]."\t".$_LANG["nl_title_post_label"]."\t".$_LANG["nl_address_label"]."\t".$_LANG["nl_country_label"]."\t".$_LANG["nl_zip_label"]."\t".$_LANG["nl_city_label"]."\t".$_LANG["nl_birthday_label"]."\t".$_LANG["nl_phone_label"]."\t".$_LANG["nl_mobile_phone_label"]."\t".$_LANG["nl_email_label"]."\t".$_LANG["nl_createdate_label"]."\t".$_LANG["nl_changedate_label"]."\t".$_LANG["nl_campaign_actions_label"]."\t".$_LANG["nl_campaign_texts_label"]."\t\n";

      $this->_csvOutput(self::OUTPUT_CSV);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Content as CSV - Dialog-Mail Import Format                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_csv_dmail()
    {
      global $_LANG;

      //header('Content-Type: text/x-csv');
      header("Content-Type: application/vnd.ms-excel");
      //header("Content-Disposition: inline; filename=\"kundendaten_export_dmail".date("Y-m-d").".xls\"");
      header('Content-Disposition: attachment; filename=kundendaten_export_dmail.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache');

      $this->_csvOutput(self::OUTPUT_CSV_DMAIL);
    }

    /**
     * Writes the data into a file.
     *
     * @param int $mode
     * @throws Exception
     */
    private function _csvOutput($mode)
    {
      global $_LANG;

      $client_items = $this->_readClientData();
      // Add additional fields / prepare field values for output
      if ($client_items) {
        $countries = $this->_configHelper->getCountries('countries', false, $this->site_id); // country data
        foreach ($client_items as $key => $row) {
          $tmp_campaign_data = array("texts" => array(), "actions" => array());
          $sql = " SELECT CADateTime, CAAction, CAActionID, CAActionText "
               . " FROM ".$this->table_prefix."client_actions "
               . " WHERE FK_CID = ".$row["CID"];
          $result1 = $this->db->query($sql);
          while ($row1 = $this->db->fetch_row($result1)){
            $tmp_campaign_data["texts"][] = $row1["CAActionText"];
            $tmp_campaign_data["actions"][] = $row1["CAAction"];
          }
          $this->db->free_result($result1);
          $foa = isset($_LANG["nl_foas"][$row["FK_FID"]]) ? $_LANG["nl_foas"][$row["FK_FID"]] : '';
          $country = isset($countries[$row["CCountry"]]) ? $countries[$row["CCountry"]] : '';

          switch ($mode) {
            case self::OUTPUT_CSV:
              echo parseCSVOutput($row["CCompany"])."\t".parseCSVOutput($row["CPosition"])."\t".parseCSVOutput($foa)."\t".parseCSVOutput($row["CTitlePre"])."\t".parseCSVOutput($row["CFirstName"])."\t".parseCSVOutput($row["CLastName"]).parseCSVOutput($row["CTitlePost"])."\t"."\t".parseCSVOutput($row["CAddress"])."\t".parseCSVOutput($country)."\t".parseCSVOutput($row["CZIP"])."\t".parseCSVOutput($row["CCity"])."\t".parseCSVOutput($row["CBirthday"])."\t".parseCSVOutput($row["CPhone"])."\t".parseCSVOutput($row["CMobilePhone"])."\t".parseCSVOutput($row["CEmail"])."\t".date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'nl'),strtotime($row["CCreateDateTime"]))."\t".($row["CChangeDateTime"] ? date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage()),strtotime($row["CChangeDateTime"])) : "")."\t".implode(";",$tmp_campaign_data["actions"])."\t".implode(";",$tmp_campaign_data["texts"])."\t\n";
              break;
            case self::OUTPUT_CSV_DMAIL:
              echo ";".parseCSVOutput($row["CID"]).";".parseCSVOutput($row["CFirstName"]).";".parseCSVOutput($row["CLastName"]).";".parseCSVOutput($row["CEmail"]).";".parseCSVOutput($foa).";".parseCSVOutput($row["CTitlePre"]).";".parseCSVOutput($row["CTitlePost"]).";".parseCSVOutput($row["CCompany"]).";".parseCSVOutput($row["CAddress"]).";".parseCSVOutput($row["CZIP"]).";".parseCSVOutput($row["CCity"]).";".parseCSVOutput($country).";".parseCSVOutput($row["CPhone"]).";".parseCSVOutput($row["CMobilePhone"]).";;;;".parseCSVOutput($row["CBirthday"]).";;;;;".date("d.m.Y H:i",strtotime($row["CCreateDateTime"])).";;".($row["CChangeDateTime"] ? date("d.m.Y H:i",strtotime($row["CChangeDateTime"])) : "").";;;".implode("/",array_unique($tmp_campaign_data["actions"])).";".implode("/",array_unique($tmp_campaign_data["texts"])).";\n";
              break;
            default:
              throw new Exception('CSV output mode not available!');
          }
        }
      }

      exit();
    }

    /**
     * Reads all client data where newsletter flag was set.
     *
     * @return array
     */
    private function _readClientData()
    {
      $client_items = array ();
      $sql = " SELECT CID, CCompany, CPosition, FK_FID, CFirstName, "
           . "        CLastName, CBirthday, CCountry, CZIP, CCity, CAddress, CPhone, CMobilePhone, "
           . "        CEmail, CCreateDateTime, CChangeDateTime, "
           . "        CTitlePre, CTitlePost, "
           . "        COALESCE(CChangeDateTime, CCreateDateTime) AS DateTime "
           . " FROM ".$this->table_prefix."client "
           . " WHERE CNewsletterConfirmedRecipient = 1 "
           . " ORDER BY CID ASC ";
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)) {

        // We want to display data rows with unique E-Mail address.
        // So find the newest data row by comparing data row's data-time.
        if (in_array($row["CEmail"], $client_items)) {
          $newDateTime = ContentBase::strToTime($row["DateTime"]);
          $currentDateTime = $client_items[$row["CEmail"]]["datetime"];
          // If there is no new date, ignore this row
          if ((!$newDateTime)
             || ($currentDateTime && $newDateTime && $newDateTime < $currentDateTime)) {
            continue;
          }
        }

        $client_items[$row["CEmail"]] = array_merge($row, array(
          'datetime' => ContentBase::strToTime($row["DateTime"]),
        ));
      }
      $this->db->free_result($result);

      return $client_items;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Contents in a List                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function list_content(){
      global $_LANG;

      // read clients
      $client_items = $this->_readClientData();

      // Add additional fields / prepare field values for output
      if ($client_items) {
        foreach ($client_items as $key => $row) {
          $client_items[$key]['nl_firstname'] = parseOutput($row['CFirstName']);
          $client_items[$key]['nl_lastname'] = parseOutput($row['CLastName']);
          $client_items[$key]['nl_email'] = parseOutput($row['CEmail']);
          $client_items[$key]['nl_delete_link'] = "index.php?action=mod_newsletter&amp;did=".$row['CID'];
          $client_items[$key]['nl_delete_label'] = $_LANG["nl_delete_label"];
        }
      }

      $nl_last_export_date = "";
      $nl_last_export_time = "";
      $nl_last_export_nick = "";
      $sql = " SELECT EDateTime, EName, UNick "
           . " FROM ".$this->table_prefix."module_newsletter_export mne "
           . " LEFT JOIN ".$this->table_prefix."user u "
           . "   ON mne.FK_UID = u.UID "
           . " ORDER BY EID DESC LIMIT 0,1 ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);
      if ($row){
        $nl_last_export_date = date("d.m.Y", strtotime($row["EDateTime"]));
        $nl_last_export_time = date("H:i", strtotime($row["EDateTime"]));
        if (mb_strlen($row["UNick"]))
          $nl_last_export_nick = parseOutput($row["UNick"]);
        else
          $nl_last_export_nick = parseOutput($row["EName"]);
      }
      $this->db->free_result($result);

      if (!$client_items){
        $this->setMessage(Message::createFailure($_LANG['nl_message_no_client']));
      }

      $this->tpl->load_tpl('content_newsletter', 'modules/ModuleNewsletter.tpl');
      $this->tpl->parse_if('content_newsletter', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('nl'));
      $this->tpl->parse_loop('content_newsletter', $client_items, 'client_items');
      $nl_content = $this->tpl->parsereturn('content_newsletter', array (
        'nl_last_export_date' => $nl_last_export_date,
        'nl_last_export_time' => $nl_last_export_time,
        'nl_last_export_nick' => $nl_last_export_nick,
        'nl_last_export_label' => ($nl_last_export_date ? sprintf($_LANG["nl_last_export_label"],$nl_last_export_date,$nl_last_export_time,$nl_last_export_nick) : ""),
        'nl_firstname_label' => $_LANG["nl_firstname_label"],
        'nl_lastname_label' => $_LANG["nl_lastname_label"],
        'nl_email_label' => $_LANG["nl_email_label"],
        'nl_list_label' => $_LANG["nl_list_label"],
        'nl_list_label2' => $_LANG["nl_list_label2"],
        'nl_deleteitem_question_label' => $_LANG["nl_deleteitem_question_label"]
      ));

      return array(
        'content'      => $nl_content,
        'content_left' => $this->_getContentLeft(),
      );
    }
  }

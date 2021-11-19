<?php

  /**
   * Newsletter Module Class
   *
   * $LastChangedDate: 2017-10-11 09:23:34 +0200 (Mi, 11 Okt 2017) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  $content_types_dir = "includes/classes/content_types";

  // Include ContentItem Files in Dir
  if ($handle_export = opendir($content_types_dir)) {
     while (false !== ($file_export = readdir($handle_export))) {
       if (mb_substr($file_export, 0, 17) == 'class.ContentItem' && mb_substr($file_export, mb_strlen($file_export) - 4) == '.php' && !mb_strstr($file_export, '.bak')) {
         include_once "$content_types_dir/$file_export";
       }
     }
     closedir($handle_export);
  }

  class ModuleExportData extends Module {

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){

      if (isset($_POST["export"]))
        return $this->get_csv();
      else if (isset($_POST["export_intlinks"]))
        return $this->get_csv_intlinks();
      else if (isset($_POST["export_extlinks"]))
        return $this->get_csv_extlinks();
      else if (isset($_POST["export_downloads"]))
        return $this->get_csv_downloads();
      else
        return $this->list_content();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Content as CSV                                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_csv(){
      global $_LANG;

      header('Content-Type: text/x-csv');
      header('Content-Disposition: attachment; filename=inhaltsdaten_export.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache'); //*/

      // output title row
      echo $_LANG["ed_path_label"].";".$_LANG["ed_path_title_label"].";".$_LANG["ed_type_label"].";".$_LANG["ed_c_title1_label"].";".$_LANG["ed_c_title2_label"].";".$_LANG["ed_c_title3_label"].";".$_LANG["ed_c_text1_label"].";".$_LANG["ed_c_text2_label"].";".$_LANG["ed_c_text3_label"].";".$_LANG["ed_c_image_title1_label"].";".$_LANG["ed_c_image_title2_label"].";".$_LANG["ed_c_image_title3_label"]."\n";

      $cms_content = array();
      $c_types = array();
      // read all content from contentitem-classes
      $result = $this->db->query("SELECT CTID, CTClass FROM ".$this->table_prefix."contenttype ");
      while ($row = $this->db->fetch_row($result)){
        if (file_exists(ConfigHelper::get('INCLUDE_DIR') . "includes/classes/content_types/class.".$row["CTClass"].".php")){
          $c_classdata = new $row["CTClass"]($this->site_id,0,$this->tpl,$this->db,$this->table_prefix,"","", $this->_user, $this->session, $this->_navigation);
          $cms_content = array_merge($cms_content,$c_classdata->return_class_content());
          $c_types[$row["CTID"]] = isset($_LANG["global_{$row['CTClass']}_intlabel"]) ? $_LANG["global_{$row['CTClass']}_intlabel"] : '';
        }
      }
      $this->db->free_result($result);

      // make path key
      $cms_content_output = array();
      foreach ($cms_content as $ciid => $c_array){
        $c_array["ciid"] = $ciid;
        $cms_content_output[$c_array["path"]] = $c_array;
      }

      // sort array and output the data
      asort($cms_content_output);
      foreach ($cms_content_output as $path => $c_array){
        echo '"'.$this->parseOutput($c_array["path"]).'";"'.$this->parseOutput($c_array["path_title"]).'";"'.$this->parseOutput($c_types[$c_array["type"]]).'";"'.$this->parseOutput($c_array["c_title1"]).'";"'.$this->parseOutput($c_array["c_title2"]).'";"'.$this->parseOutput($c_array["c_title3"]).'";"'.$this->parseOutput($c_array["c_text1"]).'";"'.$this->parseOutput($c_array["c_text2"]).'";"'.$this->parseOutput($c_array["c_text3"]).'";"'.$this->parseOutput($c_array["c_image_title1"]).'";"'.$this->parseOutput($c_array["c_image_title2"]).'";"'.$this->parseOutput($c_array["c_image_title3"]).'"'."\n";
        if (isset ($c_array["c_sub"]) && is_array($c_array["c_sub"])){
          foreach ($c_array["c_sub"] as $c_subarray)
            echo '"'.$this->parseOutput($c_array["path"]).'";"'.$this->parseOutput($c_array["path_title"]).'";"'.$this->parseOutput($c_types[$c_array["type"]]).'";"'.(isset($c_subarray["cs_title"]) ? $this->parseOutput($c_subarray["cs_title"]) : "").'";"";"";"'.(isset($c_subarray["cs_text"]) ? $this->parseOutput($c_subarray["cs_text"]) : "").'";"";"";"'. (isset($c_subarray["cs_image_title"]) ? $this->parseOutput($c_subarray["cs_image_title"]) : "") . '"'."\n";
        }
      }

      exit();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Internal Links as CSV                                                             //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_csv_intlinks(){
      global $_LANG;

      header('Content-Type: text/x-csv');
      header('Content-Disposition: attachment; filename=inhaltsdaten_export_interne_links.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache'); //*/

      // output title row
      echo $_LANG["ed_path_label"].";".$_LANG["ed_path_title_label"].";".$_LANG["ed_type_label"].";".$_LANG["ed_intlink_title_label"].";".$_LANG["ed_intlink_link_label"]."\n";

      $cms_content = array();
      $c_types = array();
      // read all content from contentitem-classes
      $result = $this->db->query("SELECT CTID,CTClass FROM ".$this->table_prefix."contenttype");
      while ($row = $this->db->fetch_row($result)){
        if (file_exists(ConfigHelper::get('INCLUDE_DIR') . "includes/classes/content_types/class.".$row["CTClass"].".php")){
          $c_classdata = new $row["CTClass"]($this->site_id,0,$this->tpl,$this->db,$this->table_prefix,"","", $this->_user, $this->session, $this->_navigation);
          $cms_content = $cms_content + $c_classdata->return_class_content();
          $c_types[$row["CTID"]] = isset($_LANG["global_{$row['CTClass']}_intlabel"]) ? $_LANG["global_{$row['CTClass']}_intlabel"] : '';
        }
      }
      $this->db->free_result($result);

      // make path key
      $cms_content_output = array();
      foreach ($cms_content as $ciid => $c_array){
        $c_array["ciid"] = $ciid;
        $cms_content_output[$c_array["path"]] = $c_array;
      }

      // sort array and output the data
      $sid = 1;
      if (!empty($_GET["site"])) {
        $sid = (int)$_GET['site'];
      }
      asort($cms_content_output);
      foreach ($cms_content_output as $path => $c_array) {
        $sql = 'SELECT ILID, CIIdentifier, ILTitle '
             . "FROM {$this->table_prefix}internallink il "
             . "JOIN {$this->table_prefix}contentitem ci ON ci.CIID = il.FK_CIID "
             . "WHERE FK_SID = $sid "
             . "AND il.FK_CIID = {$c_array['ciid']} ";
        $result = $this->db->query($sql);
        while ($row = $this->db->fetch_row($result)) {
          echo '"'.$this->parseOutput($c_array["path"]).'";"'.$this->parseOutput($c_array["path_title"]).'";"'.$this->parseOutput($c_types[$c_array["type"]]).'";"'.$row["ILTitle"].'";"'.$row["CIIdentifier"].'"'."\n";
        }
        $this->db->free_result($result);
      }

      exit();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get External Links as CSV                                                             //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_csv_extlinks(){
      global $_LANG;

      header('Content-Type: text/x-csv');
      header('Content-Disposition: attachment; filename=inhaltsdaten_export_externe_links.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache'); //*/

      // output title row
      echo $_LANG["ed_path_label"].";".$_LANG["ed_path_title_label"].";".$_LANG["ed_type_label"].";".$_LANG["ed_extlink_title_label"].";".$_LANG["ed_extlink_link_label"]."\n";

      $cms_content = array();
      $c_types = array();
      // read all content from contentitem-classes
      $result = $this->db->query("SELECT CTID,CTClass FROM ".$this->table_prefix."contenttype ");
      while ($row = $this->db->fetch_row($result)){
        if (file_exists(ConfigHelper::get('INCLUDE_DIR') . "includes/classes/content_types/class.".$row["CTClass"].".php")){
          $c_classdata = new $row["CTClass"]($this->site_id,0,$this->tpl,$this->db,$this->table_prefix,"","", $this->_user, $this->session, $this->_navigation);
          $cms_content = $cms_content + $c_classdata->return_class_content();
          $c_types[$row['CTID']] = isset($_LANG["global_{$row['CTClass']}_intlabel"]) ? $_LANG["global_{$row['CTClass']}_intlabel"] : '';
        }
      }
      $this->db->free_result($result);

      // make path key
      $cms_content_output = array();
      foreach ($cms_content as $ciid => $c_array){
        $c_array["ciid"] = $ciid;
        $cms_content_output[$c_array["path"]] = $c_array;
      }

      // sort array and output the data
      asort($cms_content_output);
      foreach ($cms_content_output as $path => $c_array) {
        $result = $this->db->query("SELECT ELID,ELUrl,ELTitle FROM ".$this->table_prefix."externallink WHERE FK_CIID=".$c_array["ciid"]);
        while ($row = $this->db->fetch_row($result)) {
          echo '"'.$this->parseOutput($c_array["path"]).'";"'.$this->parseOutput($c_array["path_title"]).'";"'.$this->parseOutput($c_types[$c_array["type"]]).'";"'.$row["ELTitle"].'";"'.$row["ELUrl"].'"'."\n";
        }
        $this->db->free_result($result);
      }

      exit();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Downloads as CSV                                                                  //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_csv_downloads(){
      global $_LANG;

      header('Content-Type: text/x-csv');
      header('Content-Disposition: attachment; filename=inhaltsdaten_export_downloads.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache'); //*/

      // output title row
      echo $_LANG["ed_path_label"].";".$_LANG["ed_path_title_label"].";".$_LANG["ed_type_label"].";".$_LANG["ed_downloads_title_label"].";".$_LANG["ed_downloads_file_label"]."\n";

      $cms_content = array();
      $c_types = array();
      // read all content from contentitem-classes
      $result = $this->db->query("SELECT CTID, CTClass FROM ".$this->table_prefix."contenttype");
      while ($row = $this->db->fetch_row($result)){
        if (file_exists(ConfigHelper::get('INCLUDE_DIR') . "includes/classes/content_types/class.".$row["CTClass"].".php")){
          $c_classdata = new $row["CTClass"]($this->site_id,0,$this->tpl,$this->db,$this->table_prefix,"","", $this->_user, $this->session, $this->_navigation);
          $cms_content = $cms_content + $c_classdata->return_class_content();
          $c_types[$row['CTID']] = isset($_LANG["global_{$row['CTClass']}_intlabel"]) ? $_LANG["global_{$row['CTClass']}_intlabel"] : '';
        }
      }
      $this->db->free_result($result);

      // make path key
      $cms_content_output = array();
      foreach ($cms_content as $ciid => $c_array){
        $c_array["ciid"] = $ciid;
        $cms_content_output[$c_array["path"]] = $c_array;
      }

      // sort array and output the data
      asort($cms_content_output);
      foreach ($cms_content_output as $path => $c_array) {
        $sql = 'SELECT FTitle, CFTitle, COALESCE(FFile, CFFile) AS File '
             . "FROM {$this->table_prefix}file "
             . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
             . "WHERE FK_CIID = {$c_array['ciid']} "
             . 'AND ( '
             . '  FFile IS NOT NULL OR '
             . '  CFFile IS NOT NULL '
             . ') '
             . 'ORDER BY FPosition ';
        $result = $this->db->query($sql);
        while ($row = $this->db->fetch_row($result)) {
          echo '"'.$this->parseOutput($c_array["path"]).'";"'.$this->parseOutput($c_array["path_title"]).'";"'.$this->parseOutput($c_types[$c_array["type"]]).'";"'.coalesce($row['FTitle'], $row['CFTitle']).'";"'.$row["File"].'"'."\n";
        }
        $this->db->free_result($result);
      }

      exit();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Contents in a List                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function list_content(){
      global $_LANG;

      // create site dropdown
      $tmp_site_select = '<select name="site" class="form-control" onchange="if (this.options[this.selectedIndex].value > 0){ document.forms[0].submit(); }">';
      foreach ($this->_allSites as $siteID => $siteTitle) {
        if ($this->_user->AvailableSite($siteID)) {
          $siteTitle = ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($siteID));
          $tmp_site_select .= '<option value="' . $siteID . '"';
          if ($this->site_id == $siteID) $tmp_site_select .= ' selected="selected"';
          $tmp_site_select .= ">".parseOutput($siteTitle)."</option>";
        }
      }
      $ed_choose_site = $tmp_site_select . '</select>';

      $ed_action = "index.php";
      $ed_hidden_fields = '<input type="hidden" name="action" value="mod_exportdata" />';

      $this->tpl->load_tpl('content_exportdata', 'modules/ModuleExportData.tpl');
      //$this->tpl->parse_if('content_exportdata', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ed'));
      $ed_content = $this->tpl->parsereturn('content_exportdata', array ( 'ed_function_label' => $_LANG["ed_function_label"],
                                                                          'ed_description' => $_LANG["ed_description"],
                                                                          'ed_description_label' => $_LANG["ed_description_label"],
                                                                          'ed_export_functions_label' => $_LANG["ed_export_functions_label"],
                                                                          'ed_choose_site_label' => $_LANG["ed_choose_site_label"],
                                                                          'ed_choose_site' => $ed_choose_site,
                                                                          'ed_action' => $ed_action,
                                                                          'ed_hidden_fields' => $ed_hidden_fields,
                                                                          'ed_export_button' => '<input type="submit" class="btn btn-success" name="export" value="'.$_LANG["ed_button_export_label"].'" />',
                                                                          'ed_export_label' => $_LANG["ed_export_label"],
                                                                          'ed_export_intlinks_button' => '<input type="submit" class="btn btn-success" name="export_intlinks" value="'.$_LANG["ed_button_export_intlinks_label"].'" />',
                                                                          'ed_export_intlinks_label' => $_LANG["ed_export_intlinks_label"],
                                                                          'ed_export_extlinks_button' => '<input type="submit" class="btn btn-success" name="export_extlinks" value="'.$_LANG["ed_button_export_extlinks_label"].'" />',
                                                                          'ed_export_extlinks_label' => $_LANG["ed_export_extlinks_label"],
                                                                          'ed_export_downloads_button' => '<input type="submit" class="btn btn-success" name="export_downloads" value="'.$_LANG["ed_button_export_downloads_label"].'" />',
                                                                          'ed_export_downloads_label' => $_LANG["ed_export_downloads_label"] ));

      return array(
          'content' => $ed_content,
      );

    }

    private function parseOutput ($text){
      $text = preg_replace("/\"/u","'",$text);
      //$text = strip_tags($text);

      return $text;
    }
  }


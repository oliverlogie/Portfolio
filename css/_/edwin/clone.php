<?php

/**
 * Edwin clone script
 *
 * FIXME:
 * - BG: Bilder werden nicht korrekt neu erzeugt.
 * - CX *link* elements have internal links set to old target, because the
 *   ciid cache is not fully generated during clone process
 * - CX *alternative* elements or generally elements with subelements are not
 *   processed correctly
 *
 * TODO:
 * - allow cloning tags, replace assigned tags for content items.
 * - contentitem_log: copy entries, when cloning content items in order to aviod
 *   issues in BE-level, which joins on the log table for children when fetching
 *   them and thus does not display items when currently cloned.
 * - cp_cartsetting
 * - cp_info
 * - tg
 * - Lead Management Formulare
 * - ModuleCustomText > clone mc_module_customtext_category if used
 *
 * $LastChangedDate: 2010-07-01 16:48:45 +0200 (Do, 01 Jul 2010) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

include 'includes/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

// enable error reporting
error_reporting(E_ALL ^E_DEPRECATED ^E_NOTICE ^E_STRICT ^E_WARNING);
// error_reporting(E_ALL); // uncomment for development
ini_set('display_errors', 1);

// remove line before executing script.
exit;

// set configuration
/**
 * If set to true, this script just makes a dry run without cloning any files or
 * images in filesystem. Use to test the clone before executing for real.
 *
 * Set to `false` for real clone.
 *
 * @var bool
 */
$_DATA['clone_dry_run'] = true;
$_DATA["clone_from"] = 1;
$_DATA["clone_to"] = 2;
$_DATA["clone_tree"] = array("main", "footer", "hidden", "pages");
$_DATA["clone_structure_links"] = false;
$_DATA["create_structure_links"] = false;
$_DATA["clone_external_links"] = true;
$_DATA["clone_module_customtext"] = true;
$_DATA["clone_module_medialibrary"] = true;
$_DATA["clone_module_medialibrary_keep_issuu_document"] = false;
$_DATA["clone_module_sidebox"] = true;
$_DATA["clone_files"] = true;
$_DATA["clone_frontend_user_group"] = false;
/**
 * Set to ID of backend user, that should be set as page author for cloned
 * pages and contents.
 *
 * @var int
 */
$_DATA["user_id_for_contentitem_create_log_entry"] = 0;

// check valid configuration for this script
//
// ensure a valid user id for log entry creation
if (!$db->GetOne("SELECT UID FROM {$_CONFIG['table_prefix']}user WHERE UID = '{$_DATA['user_id_for_contentitem_create_log_entry']}' AND UDeleted = 0")) {
  throw new Exception("Please choose a valid user id for \$_DATA['user_id_for_contentitem_create_log_entry'].");
}

// initialize vars
$_DATA["contenttype_suffix"] = array();
$_DATA["ciid_cache"] = array();
$_DATA["ciid_root_nodes"] = array();
foreach ($_DATA["clone_tree"] as $clone_tree) {
  // find old root node
  $sql = "SELECT CIID FROM {$_CONFIG['table_prefix']}contentitem WHERE CTree='".$clone_tree."' AND FK_SID=".$_DATA["clone_from"]." AND FK_CIID IS NULL";
  $result = $db->query($sql);
  $row = $db->fetch_row($result);
  $root_node = $row["CIID"];
  $db->free_result($result);

  // find new root node
  $sql = "SELECT CIID FROM {$_CONFIG['table_prefix']}contentitem WHERE CTree='".$clone_tree."' AND FK_SID=".$_DATA["clone_to"]." AND FK_CIID IS NULL";
  $result = $db->query($sql);
  $row = $db->fetch_row($result);
  $new_root_node = $row["CIID"];
  $db->free_result($result);

  if (!$new_root_node)
    throw new Exception("missing root node on site ".$_DATA["clone_to"]." (tree ".$clone_tree.")");

  $_DATA["ciid_cache"][$root_node] = $new_root_node;
  $_DATA["ciid_root_nodes"][$root_node] = $new_root_node;
}
$_DATA["new_ciid"] = $new_node_id = $db->GetOne("SELECT MAX(CIID) FROM {$_CONFIG['table_prefix']}contentitem")+1;
$_DATA["new_ilid"] = $db->GetOne("SELECT MAX(ILID) FROM {$_CONFIG['table_prefix']}internallink")+1;
$_DATA["new_elid"] = $db->GetOne("SELECT MAX(ELID) FROM {$_CONFIG['table_prefix']}externallink")+1;
$_DATA["new_slid"] = $db->GetOne("SELECT MAX(SLID) FROM {$_CONFIG['table_prefix']}structurelink")+1;
$_DATA["new_id_log"] = $new_node_id = $db->GetOne("SELECT MAX(LID) FROM {$_CONFIG['table_prefix']}contentitem_log")+1;
$_DATA["new_id_be"] = $db->GetOne("SELECT MAX(BID) FROM {$_CONFIG['table_prefix']}contentitem_be")+1;
$_DATA["new_id_bg"] = $db->GetOne("SELECT MAX(GID) FROM {$_CONFIG['table_prefix']}contentitem_bg")+1;
$_DATA["new_id_bg_sub1"] = $db->GetOne("SELECT MAX(BIID) FROM {$_CONFIG['table_prefix']}contentitem_bg_image")+1;
$_DATA["new_id_bi"] = $db->GetOne("SELECT MAX(BID) FROM {$_CONFIG['table_prefix']}contentitem_bi")+1;
$_DATA["new_id_ca"] = $db->GetOne("SELECT MAX(CAID) FROM {$_CONFIG['table_prefix']}contentitem_ca")+1;
$_DATA["new_id_ca_sub1"] = $db->GetOne("SELECT MAX(CAAID) FROM {$_CONFIG['table_prefix']}contentitem_ca_area")+1;
$_DATA["new_id_ca_sub2"] = $db->GetOne("SELECT MAX(CAABID) FROM {$_CONFIG['table_prefix']}contentitem_ca_area_box")+1;
$_DATA["new_id_cx"] = $db->GetOne("SELECT MAX(CXID) FROM {$_CONFIG['table_prefix']}contentitem_cx")+1;
$_DATA["new_id_cx_sub1"] = $db->GetOne("SELECT MAX(CXAID) FROM {$_CONFIG['table_prefix']}contentitem_cx_area")+1;
$_DATA["new_id_cx_sub2"] = $db->GetOne("SELECT MAX(CXAEID) FROM {$_CONFIG['table_prefix']}contentitem_cx_area_element")+1;
$_DATA["new_id_cb"] = $db->GetOne("SELECT MAX(CBID) FROM {$_CONFIG['table_prefix']}contentitem_cb")+1;
$_DATA["new_id_cb_sub1"] = $db->GetOne("SELECT MAX(CBBID) FROM {$_CONFIG['table_prefix']}contentitem_cb_box")+1;
$_DATA["new_id_cb_sub21"] = $db->GetOne("SELECT MAX(BLID) FROM {$_CONFIG['table_prefix']}contentitem_cb_box_biglink")+1;
$_DATA["new_id_cb_sub22"] = $db->GetOne("SELECT MAX(SLID) FROM {$_CONFIG['table_prefix']}contentitem_cb_box_smalllink")+1;
$_DATA["new_id_dl"] = $db->GetOne("SELECT MAX(DLID) FROM {$_CONFIG['table_prefix']}contentitem_dl")+1;
$_DATA["new_id_dl_sub1"] = $db->GetOne("SELECT MAX(DAID) FROM {$_CONFIG['table_prefix']}contentitem_dl_area")+1;
$_DATA["new_id_dl_sub2"] = $db->GetOne("SELECT MAX(DFID) FROM {$_CONFIG['table_prefix']}contentitem_dl_area_file")+1;
$_DATA["new_id_ec"] = $db->GetOne("SELECT MAX(ECID) FROM {$_CONFIG['table_prefix']}contentitem_ec")+1;
$_DATA["new_id_es"] = $db->GetOne("SELECT MAX(EID) FROM {$_CONFIG['table_prefix']}contentitem_es")+1;
$_DATA["new_id_ib"] = $db->GetOne("SELECT MAX(IID) FROM {$_CONFIG['table_prefix']}contentitem_ib")+1;
$_DATA["new_id_ip"] = $db->GetOne("SELECT MAX(IID) FROM {$_CONFIG['table_prefix']}contentitem_ip")+1;
$_DATA["new_id_login"] = $db->GetOne("SELECT MAX(LID) FROM {$_CONFIG['table_prefix']}contentitem_login")+1;
$_DATA["new_id_ls"] = $db->GetOne("SELECT MAX(SID) FROM {$_CONFIG['table_prefix']}contentitem_ls")+1;
$_DATA["new_id_nl"] = $db->GetOne("SELECT MAX(NLID) FROM {$_CONFIG['table_prefix']}contentitem_nl")+1;
$_DATA["new_id_pa"] = $db->GetOne("SELECT MAX(PID) FROM {$_CONFIG['table_prefix']}contentitem_pa")+1;
$_DATA["new_id_pb"] = $db->GetOne("SELECT MAX(PBID) FROM {$_CONFIG['table_prefix']}contentitem_pb")+1;
$_DATA["new_id_pi"] = $db->GetOne("SELECT MAX(PID) FROM {$_CONFIG['table_prefix']}contentitem_pi")+1;
$_DATA["new_id_po"] = $db->GetOne("SELECT MAX(PID) FROM {$_CONFIG['table_prefix']}contentitem_po")+1;
$_DATA["new_id_pp"] = $db->GetOne("SELECT MAX(PPID) FROM {$_CONFIG['table_prefix']}contentitem_pp")+1;
$_DATA["new_id_pp_sub1"] = $db->GetOne("SELECT MAX(PPPID) FROM {$_CONFIG['table_prefix']}contentitem_pp_product")+1;
$_DATA["new_id_pp_sub2"] = $db->GetOne("SELECT MAX(OPID) FROM {$_CONFIG['table_prefix']}contentitem_pp_option_global")+1;
$_DATA["new_id_pt"] = $db->GetOne("SELECT MAX(PID) FROM {$_CONFIG['table_prefix']}contentitem_pt")+1;
$_DATA["new_id_qs"] = $db->GetOne("SELECT MAX(QID) FROM {$_CONFIG['table_prefix']}contentitem_qs")+1;
$_DATA["new_id_qs_sub1"] = $db->GetOne("SELECT MAX(QSID) FROM {$_CONFIG['table_prefix']}contentitem_qs_statement")+1;
$_DATA["new_id_qp"] = $db->GetOne("SELECT MAX(QPID) FROM {$_CONFIG['table_prefix']}contentitem_qp")+1;
$_DATA["new_id_qp_sub1"] = $db->GetOne("SELECT MAX(QPSID) FROM {$_CONFIG['table_prefix']}contentitem_qp_statement")+1;
$_DATA["new_id_sc"] = $db->GetOne("SELECT MAX(SID) FROM {$_CONFIG['table_prefix']}contentitem_sc")+1;
$_DATA["new_id_se"] = $db->GetOne("SELECT MAX(SID) FROM {$_CONFIG['table_prefix']}contentitem_se")+1;
$_DATA["new_id_sp"] = $db->GetOne("SELECT MAX(PID) FROM {$_CONFIG['table_prefix']}contentitem_sp")+1;
$_DATA["new_id_st"] = $db->GetOne("SELECT MAX(STID) FROM {$_CONFIG['table_prefix']}contentitem_st")+1;
$_DATA["new_id_su"] = $db->GetOne("SELECT MAX(SID) FROM {$_CONFIG['table_prefix']}contentitem_su")+1;
$_DATA["new_id_tg"] = $db->GetOne("SELECT MAX(TGID) FROM {$_CONFIG['table_prefix']}contentitem_tg")+1;
$_DATA["new_id_ti"] = $db->GetOne("SELECT MAX(TID) FROM {$_CONFIG['table_prefix']}contentitem_ti")+1;
$_DATA["new_id_to"] = $db->GetOne("SELECT MAX(TID) FROM {$_CONFIG['table_prefix']}contentitem_to")+1;
$_DATA["new_id_ts"] = $db->GetOne("SELECT MAX(TID) FROM {$_CONFIG['table_prefix']}contentitem_ts")+1;
$_DATA["new_id_ts_sub1"] = $db->GetOne("SELECT MAX(TBID) FROM {$_CONFIG['table_prefix']}contentitem_ts_block")+1;
$_DATA["new_id_ts_sub2"] = $db->GetOne("SELECT MAX(TLID) FROM {$_CONFIG['table_prefix']}contentitem_ts_block_link")+1;
$_DATA["new_id_va"] = $db->GetOne("SELECT MAX(VID) FROM {$_CONFIG['table_prefix']}contentitem_va")+1;
$_DATA["new_id_vc"] = $db->GetOne("SELECT MAX(VID) FROM {$_CONFIG['table_prefix']}contentitem_vc")+1;
$_DATA["new_id_xs"] = $db->GetOne("SELECT MAX(XSID) FROM {$_CONFIG['table_prefix']}contentitem_xs")+1;
$_DATA["new_id_xu"] = $db->GetOne("SELECT MAX(XUID) FROM {$_CONFIG['table_prefix']}contentitem_xu")+1;

$_DATA["new_id_cb_sub1_start"] = $_DATA["new_id_cb_sub1"];
$_DATA["new_id_cb_sub21_start"] = $_DATA["new_id_cb_sub21"];
$_DATA["new_id_cb_sub22_start"] = $_DATA["new_id_cb_sub22"];

// new element id and old elemt's link for cb subitems
$_DATA["cb_sub1_link_cache"] = array();
$_DATA["cb_sub21_link_cache"] = array();
$_DATA["cb_sub22_link_cache"] = array();

// new element id and old elemt's link for ca subitems
$_DATA["ca_link_cache"] = array();
$_DATA["ca_sub1_link_cache"] = array();
$_DATA["ca_sub2_link_cache"] = array();

// new element id and old elemt's link for ts block links
$_DATA["ts_sub2_link_cache"] = array();

$_DATA["pp_option_global_cache"] = array();

// modules
$_DATA["new_id_si"] = $db->GetOne("SELECT MAX(SIID) FROM {$_CONFIG['table_prefix']}module_siteindex_compendium")+1;
$_DATA["new_id_si_sub1"] = $db->GetOne("SELECT MAX(SAID) FROM {$_CONFIG['table_prefix']}module_siteindex_compendium_area")+1;
$_DATA["new_id_si_sub2"] = $db->GetOne("SELECT MAX(SBID) FROM {$_CONFIG['table_prefix']}module_siteindex_compendium_area_box")+1;
$_DATA["new_id_sb"] = $db->GetOne("SELECT MAX(BID) FROM {$_CONFIG['table_prefix']}module_sidebox")+1;
$_DATA["new_id_ms"] = $db->GetOne("SELECT MAX(MID) FROM {$_CONFIG['table_prefix']}module_medialibrary")+1;
$_DATA["new_id_ms_cat"] = $db->GetOne("SELECT MAX(MCID) FROM {$_CONFIG['table_prefix']}module_medialibrary_category")+1;
$_DATA["new_id_ag"] = $db->GetOne("SELECT MAX(AID) FROM {$_CONFIG['table_prefix']}module_attribute_global")+1;
$_DATA["new_id_ag_sub1"] = $db->GetOne("SELECT MAX(AGID) FROM {$_CONFIG['table_prefix']}module_attribute_global_link_group")+1;
$_DATA["new_pos_ag_sub1"] = $db->GetOne("SELECT MAX(AGPosition) FROM {$_CONFIG['table_prefix']}module_attribute_global_link_group")+1;
$_DATA["new_id_at"] = $db->GetOne("SELECT MAX(AVID) FROM {$_CONFIG['table_prefix']}module_attribute")+1;
$_DATA["new_id_at_sub1"] = $db->GetOne("SELECT MAX(ALID) FROM {$_CONFIG['table_prefix']}module_attribute_link_group")+1;
$_DATA["new_pos_at_sub1"] = $db->GetOne("SELECT MAX(ALPosition) FROM {$_CONFIG['table_prefix']}module_attribute_link_group")+1;

$_DATA["attribute_cache"] = array();
$_DATA["attribute_global_cache"] = array();

// files
$_DATA["cache_id_centralfile"] = array();
$_DATA["new_id_centralfile"] = $db->GetOne("SELECT MAX(CFID) FROM {$_CONFIG['table_prefix']}centralfile")+1;
$_DATA["new_id_file"] = $db->GetOne("SELECT MAX(FID) FROM {$_CONFIG['table_prefix']}file")+1;

// load content types
$sql = "SELECT * FROM {$_CONFIG['table_prefix']}contenttype";
$result = $db->query($sql);
while ($row = $db->fetch_row($result)){
  $_DATA["contenttype_suffix"][$row["CTID"]] = mb_strtolower(mb_substr($row["CTClass"],11));
}
$db->free_result($result);

// BEFORE cloning all pages, clone centralfiles
if ($_DATA["clone_files"]) {
  echo "## CENTRALFILES ####################################\n\n";
  clone_central_files();
}

// clone before cloning pages, as some require attributes (PP, PA, ...)
clone_module_attribute();
// clone options available for products
clone_contentitem_pp_option_global();

// call recursive function foreach root node
echo "## PAGES ####################################\n\n";
foreach ($_DATA["ciid_root_nodes"] as $old => $new) {
  clone_page($old);
}

// AFTER cloning all pages, clone links of subcontent e.g. cb_box_biglink
echo "## PAGE LINKS ####################################\n\n";
clone_page_links();

if ($_DATA["clone_frontend_user_group"] ) {
  clone_frontend_user_group_sites();
  clone_frontend_user_group_pages();
}

// AFTER cloning all pages, clone the module sidebox content
if ($_DATA["clone_module_sidebox"]) {
  clone_module_sidebox();
}

if ($_DATA["clone_module_customtext"]) {
  clone_module_customtext();
}

if ($_DATA["clone_module_medialibrary"]) {
  clone_module_medialibrary();
}

// AFTER cloning all pages, clone the siteindex compendium
echo "## SITEINDEX COMPENDIUM ####################################\n\n";
clone_siteindex_compendium();

// clone internal links
echo "\n## INTERNAL LINKS ####################################\n\n";
$sql = "SELECT * FROM {$_CONFIG['table_prefix']}internallink ORDER BY ILID ASC";
$result = $db->query($sql);
while ($row = $db->fetch_row($result)){
  if (isset($_DATA["ciid_cache"][$row["FK_CIID"]])){ // link from current site and tree
    $new_node_id = $_DATA["new_ilid"]++; // get link id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}internallink (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "ILID"){
        $value = $new_node_id; // output new id
      }
      else if ($col == "FK_CIID"){ // link origin
        $value = $_DATA["ciid_cache"][$value];
      }
      else if ($col == "FK_CIID_Link"){ // link target
        $value = ($_DATA["ciid_cache"][$value] ? $_DATA["ciid_cache"][$value] : "-1");
      }

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
}
$db->free_result($result);

// clone downloads
echo "\n## DOWNLOADS ####################################\n\n";
$sql = "SELECT * FROM {$_CONFIG['table_prefix']}file";
$result = $db->query($sql);
while ($row = $db->fetch_row($result)){
  if (isset($_DATA["ciid_cache"][$row["FK_CIID"]])){ // download from current site and tree
    $new_node_id = $_DATA["new_id_file"]++; // get file id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}file (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FID"){
        $value = $new_node_id; // output new id
      }
      else if ($col == "FK_CIID"){ // link origin
        $value = $_DATA["ciid_cache"][$value];
      }
      else if (($col == "FK_CFID") && $value){ // central file
        $value = ($_DATA["cache_id_centralfile"][$value] ? $_DATA["cache_id_centralfile"][$value] : "-1");
      }
      else if ($col == "FCreated"){ // file creation datetime
        $value = date("Y-m-d H:i:s");
      }
      else if (($col == "FFile") && $value ) $value = get_file_name(NULL, NULL, $value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
}
$db->free_result($result);

// clone structure links
if ($_DATA["clone_structure_links"]) {
  echo "\n## CLONE STRUCTURE LINKS ####################################\n\n";
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}structurelink ORDER BY SLID ASC";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)){
    if (isset($_DATA["ciid_cache"][$row["FK_CIID_Link"]])) { // link to current site and tree
      $new_node_id = $_DATA["new_slid"]++; // get link id
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}structurelink (";
      $sql2 = "VALUES (";
      foreach ($row as $col => $value){
        if ($col == "SLID"){
          $value = $new_node_id; // output new id
        }
        else if ($col == "FK_CIID_Link"){ // link target
          $value = ($_DATA["ciid_cache"][$value] ? $_DATA["ciid_cache"][$value] : "-1");
        }

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
  }
}

// create structure links
if ($_DATA["create_structure_links"]) {
  echo "\n## CREATE STRUCTURE LINKS ####################################\n\n";
  foreach ($_DATA["ciid_cache"] as $node_id => $new_node_id) {
    // old node = link source
    // new node = link target
    $sql = "INSERT INTO {$_CONFIG['table_prefix']}structurelink (FK_CIID, FK_CIID_Link) "
         . "VALUES ($node_id, $new_node_id)";
    echo $sql.";\n";
  }
}

// clone external links
if ($_DATA["clone_external_links"]) {
  echo "\n## CLONE EXTERNAL LINKS ####################################\n\n";
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}externallink ORDER BY ELID ASC";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)){
    if (isset($_DATA["ciid_cache"][$row["FK_CIID"]])) { // link to current site and tree
      $new_node_id = $_DATA["new_elid"]++; // get link id
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}externallink (";
      $sql2 = "VALUES (";
      foreach ($row as $col => $value){
        if ($col == "ELID"){
          $value = $new_node_id; // output new id
        }
        else if ($col == "FK_CIID"){ // link origin
          $value = $_DATA["ciid_cache"][$value];
        }

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
  }
}

$db->close();

// functions

function clone_central_files() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}centralfile WHERE FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_centralfile"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}centralfile (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "CFID") {
        $old_node_id = $value;
        $value = $new_node_id;
        $_DATA["cache_id_centralfile"][$old_node_id] = $new_node_id; // old id - new id
      }
      else if ($col == "CFFile") $value = get_file_name(NULL, NULL, $value);
      else if ($col == "CFCreated") $value = date("Y-m-d H:i:s");
      else if ($col == "FK_SID") $value = $to_id;

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);
}

function clone_frontend_user_group_sites() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  echo "## FRONTEND USER GROUP SITES ####################################\n\n";

  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}frontend_user_group_sites WHERE FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}frontend_user_group_sites (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_SID") $value = $to_id;

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_frontend_user_group_pages() {
  global $_CONFIG,$db,$_DATA;

  echo "## FRONTEND USER GROUP PAGES ####################################\n\n";

  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}frontend_user_group_pages";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}frontend_user_group_pages (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") {
        if ($value) { // valid
          $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '-1';
        }
      }

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_module_attribute() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  echo "## ATTRIBUTE GLOBAL ####################################\n\n";

  // attribute groups
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_attribute_global WHERE AIdentifier IS NULL AND FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_ag"]++; // get new id
    $old_node_id = 0;
    $new_sub_item_id = 0;
    if (!$row["FK_AGID"]) {
      $new_sub_item_id = $_DATA["new_id_ag_sub1"]++;
      $new_sub_item_pos = $_DATA["new_pos_ag_sub1"]++;
      echo "INSERT INTO {$_CONFIG['table_prefix']}module_attribute_global_link_group (AGID, AGPosition) VALUES ($new_sub_item_id, $new_sub_item_pos);\n";
      echo "UPDATE {$_CONFIG['table_prefix']}module_attribute_global SET FK_AGID = $new_sub_item_id WHERE AID = {$row['AID']};\n";
    }
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_attribute_global (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "AID") {
        $old_node_id = $value;
        $value = $new_node_id;
        $_DATA["attribute_global_cache"][$old_node_id] = $new_node_id; // old id - new id
      }
      else if ($col == "FK_AGID") $value = $value ? $value : $new_sub_item_id; // old or new attribute group link group
      else if ($col == "FK_SID") $value = $to_id;

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
  echo "## ATTRIBUTES ####################################\n\n";

  // attributes
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_attribute";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    if (isset($_DATA["attribute_global_cache"][$row["FK_AID"]])) {
      $new_node_id = $_DATA["new_id_at"]++; // get new id
      $old_node_id = 0;
      $new_sub_item_id = 0;
      if (!$row["FK_ALID"]) {
        $new_sub_item_id = $_DATA["new_id_at_sub1"]++;
        $new_sub_item_pos = $_DATA["new_pos_at_sub1"]++;
        echo "INSERT INTO {$_CONFIG['table_prefix']}module_attribute_link_group (ALID, ALPosition) VALUES ($new_sub_item_id, $new_sub_item_pos);\n";
        echo "UPDATE {$_CONFIG['table_prefix']}module_attribute SET FK_ALID = $new_sub_item_id WHERE AVID = {$row['AVID']};\n";
      }
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_attribute (";
      $sql2 = "VALUES (";
      foreach ($row as $col => $value) {
        if ($col == "AVID") {
          $old_node_id = $value;
          $value = $new_node_id;
          $_DATA["attribute_cache"][$old_node_id] = $new_node_id; // old id - new id
        }
        else if ($col == "FK_AID") {
          $value = $_DATA["attribute_global_cache"][$value];
        }
        else if ($col == "FK_ALID") $value = $value ? $value : $new_sub_item_id; // old or new attribute link group
        else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
          $value = get_image_name($old_node_id,$new_node_id,$value);

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_module_sidebox() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  echo "## SIDEBOX ####################################\n\n";

  // boxes
  $sidebox_cache_id = array();
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_sidebox WHERE FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_sb"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_sidebox (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "BID") {
        $old_node_id = $value;
        $value = $new_node_id;
        $sidebox_cache_id[$old_node_id] = $new_node_id; // old id - new id
      }
      else if ($col == "FK_CIID") {
        if ($value) { // valid
          $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '-1';
        }
      }
      else if ($col == "FK_CGAID") $value = 0; // no campaign attached, TODO: attach campaign as soon as campaigns are cloned too
      else if ($col == "FK_SID") $value = $to_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_node_id,$new_node_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
  echo "## SIDEBOX ASSIGNMENTS ####################################\n\n";

  // box assignations
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_sidebox_assignment";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    if (isset($sidebox_cache_id[$row["FK_BID"]]) && isset($_DATA["ciid_cache"][$row["FK_CIID"]])) {
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_sidebox_assignment (";
      $sql2 = "VALUES (";
      foreach ($row as $col => $value) {
        if ($col == "FK_BID") {
          $value = $sidebox_cache_id[$value];
        }
        else if ($col == "FK_CIID") $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '-1';

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    else {
      if (!isset($sidebox_cache_id[$row["FK_BID"]])) {
        echo "/* Could not find cloned sidebox for '{$row["FK_BID"]}' */";
      }
      if (!isset($_DATA["ciid_cache"][$row["FK_CIID"]])) {
        echo "/* Could not find cloned content item for '{$row["FK_CIID"]}' */";
      }
    }
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_module_medialibrary() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  echo "## Multimediaboxen ####################################\n\n";

  // boxes
  $sidebox_cache_id = array();
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_medialibrary WHERE FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_ms"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_medialibrary (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "MID") {
        $old_node_id = $value;
        $value = $new_node_id;
        $sidebox_cache_id[$old_node_id] = $new_node_id; // old id - new id
      }
      else if ($col == "FK_CIID") {
        if ($value) { // valid
          $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '-1';
        }
      }
      else if ($col == "FK_IDID" && ! $_DATA['clone_module_medialibrary_keep_issuu_document']) $value = 0; // no issuu document attached
      else if ($col == "FK_CGAID") $value = 0; // no campaign attached, TODO: attach campaign as soon as campaigns are cloned too
      else if ($col == "FK_SID") $value = $to_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_node_id,$new_node_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
  echo "## Multimediaboxen ASSIGNMENTS ####################################\n\n";

  // box assignations
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_medialibrary_assignment";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    if (isset($sidebox_cache_id[$row["FK_MID"]]) && isset($_DATA["ciid_cache"][$row["FK_CIID"]])) {
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_medialibrary_assignment (";
      $sql2 = "VALUES (";
      foreach ($row as $col => $value) {
        if ($col == "FK_MID") {
          $value = $sidebox_cache_id[$value];
        }
        else if ($col == "FK_CIID") $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '-1';

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    else {
      if (!isset($sidebox_cache_id[$row["FK_MID"]])) {
        echo "/* Could not find cloned medialibrary item for '{$row["FK_MID"]}' */";
      }
      if (!isset($_DATA["ciid_cache"][$row["FK_CIID"]])) {
        echo "/* Could not find cloned medialibrary item for '{$row["FK_CIID"]}' */";
      }
    }
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
  echo "## Multimediaboxen CATEGORIES ####################################\n\n";

  $medialibrary_category_cache_id = array();
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_medialibrary_category WHERE FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_ms_cat"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_medialibrary_category (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "MCID") {
        $old_node_id = $value;
        $value = $new_node_id;
        $medialibrary_category_cache_id[$old_node_id] = $new_node_id; // old id - new id
      }
      else if ($col == "FK_SID") $value = $to_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_node_id,$new_node_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
  echo "## Multimediaboxen CATEGORY ASSIGNMENTS ####################################\n\n";

  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_medialibrary_category_assignment";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    if (isset($sidebox_cache_id[$row["FK_MID"]]) && isset($medialibrary_category_cache_id[$row["FK_MCID"]])) {
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_medialibrary_category_assignment (";
      $sql2 = "VALUES (";
      foreach ($row as $col => $value) {
        if ($col == "MCAID") {
          $value = "";
        }
        else if ($col == "FK_MID") {
          $value = $sidebox_cache_id[$value];
        }
        else if ($col == "FK_MCID") {
          $value = $medialibrary_category_cache_id[$value];
        }

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    else {
      if (!isset($sidebox_cache_id[$row["FK_MID"]])) {
        echo "/* Could not find cloned medialibrary item for '{$row["FK_MID"]}' */";
      }
      if (!isset($medialibrary_category_cache_id[$row["FK_MCID"]])) {
        echo "/* Could not find cloned medialibrary category item for '{$row["FK_MCID"]}' */";
      }
    }
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_module_customtext() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  echo "## CUSTOMTEXT ####################################\n\n";

  // boxes
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_customtext WHERE FK_SID=".$from_id . " ORDER BY CTID ASC";
  $result = $db->query($sql);
  $idCounter = 1;
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $to_id . sprintf('%03d', $idCounter++); // get new id <site_id>001, <site_id>002, ...
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_customtext (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "CTID") {
        $value = $new_node_id;
      }
      else if ($col == "FK_SID") $value = $to_id;

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_contentitem_pp_option_global() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  echo "## PP Option Global ####################################\n\n";

  // boxes
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_pp_option_global WHERE FK_SID=".$from_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_pp_sub2"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_pp_option_global (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "OPID") {
        $old_node_id = $value;
        $value = $new_node_id;
        $_DATA["pp_option_global_cache"][$old_node_id] = $new_node_id;
      }
      else if ($col == "FK_SID") $value = $to_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_node_id,$new_node_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  echo "## --------------------------------------- ##\n\n";
}

function clone_page_links() {
  global $_CONFIG,$db,$_DATA;

  echo "\n## CA LINKS ####################################\n\n";
  foreach ($_DATA["ca_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_ca SET CALink = '$new_link_id' WHERE CAID = '$id';\n";
  }

  echo "\n## CA AREA LINKS ####################################\n\n";
  foreach ($_DATA["ca_sub1_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_ca_area SET CAALink = '$new_link_id' WHERE CAAID = '$id';\n";
  }

  echo "\n## CA AREA BOX LINKS ####################################\n\n";
  foreach ($_DATA["ca_sub2_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_ca_area_box SET CAABLink = '$new_link_id' WHERE CAABID = '$id';\n";
  }

  echo "\n## CB BOX LINKS ####################################\n\n";
  foreach ($_DATA["cb_sub1_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_cb_box SET CBBLink = '$new_link_id' WHERE CBBID = '$id';\n";
  }

  echo "\n## CB BOX BIGLINKS ####################################\n\n";
  foreach ($_DATA["cb_sub21_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_cb_box_biglink SET BLLink = '$new_link_id' WHERE BLID = '$id';\n";
  }

  echo "\n## CB BOX SMALLLINKS ####################################\n\n";
  foreach ($_DATA["cb_sub22_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_cb_box_smalllink SET SLLink = '$new_link_id' WHERE SLID = '$id';\n";
  }

  echo "\n## TS BLOCK LINK ####################################\n\n";
  foreach ($_DATA["ts_sub2_link_cache"] as $id => $val) {
    // no link
    if (!$val) continue;
    // link target page cloned?
    $new_link_id = isset($_DATA["ciid_cache"][$val]) ? $_DATA["ciid_cache"][$val] : '-1';
    echo "UPDATE {$_CONFIG['table_prefix']}contentitem_ts_block_link SET TLLink = '$new_link_id' WHERE TLID = '$id';\n";
  }

}

function clone_page($parent_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem WHERE FK_CIID=".$parent_id." ORDER BY CPosition ASC";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_ciid"]++; // get new id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_SID") $value = $_DATA["clone_to"];
      else if ($col == "CCreateDateTime") $value = date("Y-m-d H:i:s");
      else if ($col == "CChangeDateTime") $value = date("Y-m-d H:i:s");
      else if ($col == "CIID"){
        $node_id = $value; // get old id
        $_DATA["ciid_cache"][$node_id] = $new_node_id; // save new id with old id
        $value = $new_node_id; // output new id
      }
      else if ($col == "FK_CIID"){
        $value = $_DATA["ciid_cache"][$parent_id];
      }

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";

    // get SQL for contentitem "created" log entry
    $sql3 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_log "
          . "(LID, LDateTime, LType, FK_CIID, CIIdentifier, FK_UID) VALUES ("
          . "'" . $_DATA['new_id_log']++ . "',"
          . "'" . date('Y-m-d H:i:s') .  "',"
          . "'created',"
          . "'" . $new_node_id .  "',"
          . "'" . $row['CIIdentifier'] .  "',"
          . "'" . $_DATA['user_id_for_contentitem_create_log_entry'] .  "'"
          . ");\n";
    echo $sql3;

    // get abstract part from table contentabstract
    echo get_abstract($node_id,$new_node_id);
    // get page content
    echo get_page_content($node_id,$new_node_id,$row["FK_CTID"]);
    echo "## --------------------------------------- ##\n\n";

    // call recursive function
    clone_page($node_id);
  }
  $db->free_result($result);
}

function clone_siteindex_compendium() {
  global $_CONFIG,$db,$_DATA;

  $from_id = $_DATA["clone_from"];
  $to_id = $_DATA["clone_to"];

  // clone table module_siteindex_compendium
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_siteindex_compendium WHERE FK_CIID=0 AND FK_SID=".$from_id;
  $result = $db->query($sql);
  if ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_siteindex_compendium (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "SIID") {
        $old_siteindex_compendium_id = $value;
        $new_siteindex_compendium_id = $_DATA["new_id_si"]++;
        $value = $new_siteindex_compendium_id;
      }
      else if ($col == "FK_CIID") $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '0';
      else if ($col == "FK_SID") $value = $to_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name(NULL,NULL,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  $siteindex_compendium_areas = array();
  // clone table module_siteindex_compendium_area
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_siteindex_compendium_area WHERE FK_SID=".$from_id." ORDER BY SAPosition ASC ";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_siteindex_compendium_area (";
    $sql2 = "VALUES (";
    $new_area_id = $_DATA["new_id_si_sub1"]++;
    foreach ($row as $col => $value){
      if ($col == "SAID") {
        $siteindex_compendium_areas[$value] = $new_area_id;
        $value = $new_area_id;
      }
      else if ($col == "FK_SID") $value = $to_id;
      else if ($col == "FK_CIID") $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '0';
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name(NULL,$new_area_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  // old area ids
  $old_area_ids = array_keys($siteindex_compendium_areas);
  // clone table module_siteindex_compendium_area_box
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}module_siteindex_compendium_area_box WHERE FK_SAID IN (".implode(',', $old_area_ids).") ORDER BY FK_SAID, SBPosition ASC ";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}module_siteindex_compendium_area_box (";
    $sql2 = "VALUES (";
    $tmp_area_id = $row["FK_SAID"];
    $new_area_box_id = $_DATA["new_id_si_sub2"]++; // new area box id
    foreach ($row as $col => $value){
      if ($col == "SBID") {
        $value = $new_area_box_id;
      }
      else if ($col == "FK_SAID") $value = $siteindex_compendium_areas[$value]; // new area id
      else if ($col == "FK_CIID") $value = isset($_DATA["ciid_cache"][$value]) ? $_DATA["ciid_cache"][$value] : '0';
      else if ($col == "FK_SID") $value = $to_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && mb_strstr($col, 'SBNoImage') === FALSE && $value) {
        $value = get_image_name(NULL,$new_area_box_id,$value,$siteindex_compendium_areas[$tmp_area_id]);
      }

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    echo mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);
  echo "## --------------------------------------- ##\n\n";
}

function get_abstract($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentabstract
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentabstract WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  if ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentabstract (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if (mb_strstr($col,"LockImage") !== FALSE) $value = $value; // Take LockImage Value and do not try to get_image_name of it
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    return mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);
}

function get_page_content($old_id,$new_id,$contenttype){
  global $_CONFIG,$db,$_DATA;

  $ct_suffix = $_DATA["contenttype_suffix"][$contenttype];
  if (($ct_suffix == 'lo') || ($ct_suffix == 'lp') || ($ct_suffix == 'archive') || ($ct_suffix == 'mo'))
    return;
  $subcontent = array();
  // clone subcontent
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_{$ct_suffix} WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  if ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_".$ct_suffix]++; // get new id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_{$ct_suffix} (";
    $sql2 = "VALUES (";
    $i = 0;
    foreach ($row as $col => $value){
      if (!$i) $value = $new_node_id;
      else if ($col == "FK_CIID") $value = $new_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value);
      else if ($ct_suffix == 'ca' && $col == 'CALink') $_DATA["ca_link_cache"][$new_node_id] = $value;
      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      $i++;
    }
    switch($ct_suffix){
      case "ca":
        $subcontent = get_page_subcontent_ca($old_id,$new_id);
        break;
      case "cx":
        $subcontent = get_page_subcontent_cx($old_id,$new_id);
        break;
      case "bg":
        $subcontent = get_page_subcontent_bg($old_id,$new_id);
        break;
      case "qs":
        $subcontent = get_page_subcontent_qs($old_id,$new_id);
        break;
      case "qp":
        $subcontent = get_page_subcontent_qp($old_id,$new_id);
        break;
      case "dl":
        $subcontent = get_page_subcontent_dl($old_id,$new_id);
        break;
      case "cb":
        $subcontent = get_page_subcontent_cb($old_id,$new_id);
        break;
      case "pp":
        $subcontent = get_page_subcontent_pp($old_id,$new_id);
        break;
      case "ts":
        $subcontent = get_page_subcontent_ts($old_id,$new_id);
        break;
      /* TODO: tg */
    }

    return implode("",$subcontent).mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);
}

function get_page_subcontent_ca($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  $siteindex_compendium_areas = array();
  $output = array();
  // clone table module_siteindex_compendium_area
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_ca_area WHERE FK_CIID=".$old_id." ORDER BY CAAPosition ASC ";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_ca_area (";
    $sql2 = "VALUES (";
    $new_area_id = $_DATA["new_id_ca_sub1"]++;
    foreach ($row as $col => $value){
      if ($col == "CAAID") {
        $siteindex_compendium_areas[$value] = $new_area_id;
        $value = $new_area_id;
      }
      else if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "CAALink") $_DATA["ca_sub1_link_cache"][$new_area_id] = $value;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name(NULL,$new_area_id,$value,0,$new_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  // old area ids
  $old_area_ids = array_keys($siteindex_compendium_areas);
  // clone table module_siteindex_compendium_area_box
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_ca_area_box WHERE FK_CAAID IN (".implode(',', $old_area_ids).") ORDER BY FK_CAAID, CAABPosition ASC ";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_ca_area_box (";
    $sql2 = "VALUES (";
    $tmp_area_id = $row["FK_CAAID"];
    $new_area_box_id = $_DATA["new_id_ca_sub2"]++; // new area box id
    foreach ($row as $col => $value){
      if ($col == "CAABID") {
        $value = $new_area_box_id;
      }
      else if ($col == "FK_CAAID") $value = $siteindex_compendium_areas[$value]; // new area id
      else if ($col == "CAABLink") $_DATA["ca_sub2_link_cache"][$new_area_box_id] = $value;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && mb_strstr($col,"NoImage") === FALSE && $value)
        $value = get_image_name($old_id,$new_area_box_id,$value,$siteindex_compendium_areas[$tmp_area_id],$new_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_cx($old_id,$new_id) {
  global $_CONFIG,$db,$_DATA;

  $contentitem_cx_areas = array();
  $output = array();
  // clone table contentitem_cx_area
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_cx_area WHERE FK_CIID=".$old_id." ORDER BY CXAPosition ASC ";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_cx_area (";
    $sql2 = "VALUES (";
    $new_area_id = $_DATA["new_id_cx_sub1"]++;
    foreach ($row as $col => $value){
      if ($col == "CXAID") {
        $contentitem_cx_areas[$value] = $new_area_id;
        $value = $new_area_id;
      }
      else if ($col == "FK_CIID") $value = $new_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name(NULL,$new_area_id,$value,0,$new_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  // old area ids
  $old_area_ids = array_keys($contentitem_cx_areas);

  if (!$old_area_ids) {
    return $output;
  }

  // clone table contentitem_cx_area_element
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_cx_area_element WHERE FK_CXAID IN (".implode(',', $old_area_ids).") ORDER BY FK_CIID, FK_CXAID ASC ";
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_cx_area_element (";
    $sql2 = "VALUES (";
    $new_area_element_id = $_DATA["new_id_cx_sub2"]++; // new area element id
    foreach ($row as $col => $value){
      if ($col == "CXAEID") {
        $value = $new_area_element_id;
      }
      else if ($col == "CXAEElementableID") {
        if ($row['CXAEElementableType'] === 'contentitem_cx_area') {
          $value = $contentitem_cx_areas[$value]; // new area id
        }
        // FIXME: check for element subelements too..
      }
      else if ($col == "FK_CXAID") $value = $contentitem_cx_areas[$value]; // new area id
      else if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "CXAEContent") {
        $value = get_page_subcontent_cx_element_content(
          $row['CXAEType'], $value, $old_id, $new_id, $row['FK_CXAID'], $contentitem_cx_areas[$row['FK_CXAID']], $row['CXAEID'], $new_area_element_id
        );
      }

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_cx_element_content($type, $value, $old_id, $new_id, $old_area_id, $new_area_id, $old_area_element_id, $new_area_element_id) {
  $result = $value;

  switch ($type) {
    case 'image':
      $result = get_page_subcontent_cx_element_content_image($value, $old_id, $new_id, $old_area_id, $new_area_id, $old_area_element_id, $new_area_element_id);
      break;
    case 'link':
      $result = get_page_subcontent_cx_element_content_link($value, $old_id, $new_id, $old_area_id, $new_area_id, $old_area_element_id, $new_area_element_id);
      break;
    case 'alternatives':
    case 'text':
    case 'title':
    case 'video':
    default:
      break;
  }

  return $result;
}

function get_page_subcontent_cx_element_content_image($value, $old_id, $new_id, $old_area_id, $new_area_id, $old_area_element_id, $new_area_element_id)
{
  $original = $value;

  if (!$value) {
    return $value;
  }

  $value = json_decode($value, true);

  if (!$value) {
    return $original;
  }

  if ($value['image']) {
    $value['image'] = get_image_name($old_id, $new_id, $value['image']);
  }

  return json_encode($value);
}

function get_page_subcontent_cx_element_content_link($value, $old_id, $new_id, $old_area_id, $new_area_id, $old_area_element_id, $new_area_element_id)
{
  global $_DATA;

  $original = $value;

  if (!$value) {
    return $value;
  }

  $value = json_decode($value, true);

  if (!$value) {
    return $original;
  }

  if ($value['intlink']) {
    // TODO: fix this code as soon as possible, currently the  $_DATA['ciid_cache']
    //       is not filled during content item clone process, so the new intlink
    //       possibly points to the old site's page in many cases.
    // replace intlink id with id of cloned page if from same site
    // otherwise (it is from global scope) we preserve the current target id
    $value['intlink'] = isset($_DATA['ciid_cache'][$value['intlink']]) ?
      $_DATA['ciid_cache'][$value['intlink']] : $value['intlink'];
  }

  return json_encode($value);
}

function get_page_subcontent_bg($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem_bg_image
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_bg_image WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  $output = array();
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_bg_sub1"]++; // get new id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_bg_image (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "BIID") $value = $new_node_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_qs($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem_qs_statement
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_qs_statement WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  $output = array();
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_qs_sub1"]++; // get new id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_qs_statement (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "QSID") $value = $new_node_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value,$new_node_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_qp($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem_qs_statement
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_qp_statement WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  $output = array();
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_qp_sub1"]++; // get new id
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_qp_statement (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "QPSID") $value = $new_node_id;
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value,$new_node_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_dl($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem_dl_area
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_dl_area WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  $output = array();
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_dl_sub1"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_dl_area (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "DAID"){
        $old_node_id = $value;
        $value = $new_node_id;
      }
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value,$new_node_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";

    // clone table contentitem_dl_area_file
    $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_dl_area_file WHERE FK_DAID=".$old_node_id;
    $result_sub = $db->query($sql);
    while ($row_sub = $db->fetch_row($result_sub)) {
      $new_sub_node_id = $_DATA["new_id_dl_sub2"]++; // get new id
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_dl_area_file (";
      $sql2 = "VALUES (";
      foreach ($row_sub as $col => $value){
        if ($col == "FK_DAID") $value = $new_node_id;
        else if ($col == "DFID") $value = $new_sub_node_id;
        else if (($col == "FK_CFID") && $value)
          $value = ($_DATA["cache_id_centralfile"][$value] ? $_DATA["cache_id_centralfile"][$value] : "-1");
        else if (($col == "DFFile") && $value)
          $value = get_file_name(NULL, NULL, $value);
        else if ($col == "DFCreated") $value = date("Y-m-d H:i:s");

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    $db->free_result($result_sub);
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_ts($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem_ts_block
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_ts_block WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  $output = array();
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_ts_sub1"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_ts_block (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "TBID"){
        $old_node_id = $value;
        $value = $new_node_id;
      }
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value,$new_node_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";

    // clone table contentitem_ts_block_link
    $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_ts_block_link WHERE FK_TBID=".$old_node_id;
    $result_sub = $db->query($sql);
    while ($row_sub = $db->fetch_row($result_sub)) {
      $new_sub_node_id = $_DATA["new_id_ts_sub2"]++; // get new id
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_ts_block_link (";
      $sql2 = "VALUES (";
      foreach ($row_sub as $col => $value){
        if ($col == "FK_TBID") $value = $new_node_id;
        else if ($col == "TLID") $value = $new_sub_node_id;
        else if ($col == "TLLink") {
          $_DATA["ts_sub2_link_cache"][$new_sub_node_id] = $value;
        }

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    $db->free_result($result_sub);
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_cb($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  // clone table contentitem_cb_box
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_cb_box WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  $output = array();
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_cb_sub1"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_cb_box (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "CBBID"){
        $old_node_id = $value;
        $value = $new_node_id;
      }
      else if ($col == "CBBLink") {
        $_DATA["cb_sub1_link_cache"][$new_node_id] = $value;
      }
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value,$new_node_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";

    // clone table contentitem_cb_box_biglink
    $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_cb_box_biglink WHERE FK_CBBID=".$old_node_id;
    $result_sub = $db->query($sql);
    while ($row_sub = $db->fetch_row($result_sub)) {
      $new_sub_node_id1 = $_DATA["new_id_cb_sub21"]++; // get new id
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_cb_box_biglink (";
      $sql2 = "VALUES (";
      foreach ($row_sub as $col => $value){
        if ($col == "FK_CBBID") $value = $new_node_id;
        else if ($col == "BLID") $value = $new_sub_node_id1;
        else if ($col == "BLLink") {
          $_DATA["cb_sub21_link_cache"][$new_sub_node_id1] = $value;
        }

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    $db->free_result($result_sub);

    // clone table contentitem_cb_box_smalllink
    $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_cb_box_smalllink WHERE FK_CBBID=".$old_node_id;
    $result_sub = $db->query($sql);
    while ($row_sub = $db->fetch_row($result_sub)) {
      $new_sub_node_id2 = $_DATA["new_id_cb_sub22"]++; // get new id
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_cb_box_smalllink (";
      $sql2 = "VALUES (";
      foreach ($row_sub as $col => $value){
        if ($col == "FK_CBBID") $value = $new_node_id;
        else if ($col == "SLID") $value = $new_sub_node_id2;
        else if ($col == "SLLink") {
          $_DATA["cb_sub22_link_cache"][$new_sub_node_id2] = $value;
        }

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    $db->free_result($result_sub);
  }
  $db->free_result($result);

  return $output;
}

function get_page_subcontent_pp($old_id,$new_id){
  global $_CONFIG,$db,$_DATA;

  $output = array();
  // clone pp attribute groups
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_pp_attribute_global WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 =  "INSERT INTO {$_CONFIG['table_prefix']}contentitem_pp_attribute_global (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $_DATA["ciid_cache"][$value];
      else if ($col == "FK_AID") {
        // there are rows where attribute group id is not set
        $value = $value ? $_DATA["attribute_global_cache"][$value] : 0;
      }

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }

  // clone pp options
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_pp_option WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $sql1 =  "INSERT INTO {$_CONFIG['table_prefix']}contentitem_pp_option (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "PPOID") $value = '';
      if ($col == "FK_CIID") $value = $_DATA["ciid_cache"][$value];
      if ($col == "FK_OPID") $value = $_DATA["pp_option_global_cache"][$value];

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
  }

  // clone table contentitem_pp_product
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_pp_product WHERE FK_CIID=".$old_id;
  $result = $db->query($sql);
  while ($row = $db->fetch_row($result)) {
    $new_node_id = $_DATA["new_id_pp_sub1"]++; // get new id
    $old_node_id = 0;
    $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_pp_product (";
    $sql2 = "VALUES (";
    foreach ($row as $col => $value){
      if ($col == "FK_CIID") $value = $new_id;
      else if ($col == "PPPID"){
        $old_node_id = $value;
        $value = $new_node_id;
      }
      else if (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE && $value)
        $value = get_image_name($old_id,$new_id,$value,$new_node_id);

      $sql1 .= $col.",";
      $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
    }
    $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";

    // clone table contentitem_pp_product_attributes
    $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem_pp_product_attribute WHERE FK_PPPID=".$old_node_id;
    $result_sub = $db->query($sql);
    while ($row_sub = $db->fetch_row($result_sub)) {
      $sql1 = "INSERT INTO {$_CONFIG['table_prefix']}contentitem_pp_product_attribute (";
      $sql2 = "VALUES (";
      foreach ($row_sub as $col => $value){
        if ($col == "FK_PPPID") $value = $new_node_id;
        else if ($col == "FK_AVID") $value = $_DATA["attribute_cache"][$value];

        $sql1 .= $col.",";
        $sql2 .= isset($value) ? "'".$db->escape($value)."'"."," : (mb_strstr($col,"Image") !== FALSE && mb_strstr($col,"ImageTitles") === FALSE ? '"",' : "NULL,");
      }
      $output[] = mb_substr($sql1,0,mb_strlen($sql1)-1).") ".mb_substr($sql2,0,mb_strlen($sql2)-1).");\n";
    }
    $db->free_result($result_sub);
  }
  $db->free_result($result);

  return $output;
}

/**
 * @param int $old_id
 * @param int $new_id
 * @param string $image_name
 *        The full image name: 'img/xx-1-1-...jpg'
 * @param int $sub_id
 * @param int $new_ciid
 */
function get_image_name($old_id,$new_id,$image_name,$sub_id = 0,$new_ciid=0){
  global $_CONFIG,$db,$_DATA;

  if (mb_substr($image_name,4,3) == "bg_"){
    $tmp = explode("_",$image_name);
    $tmp2 = explode("-",$tmp[1]);
    $tmp_name = $tmp[0]."_gallery-".$_DATA["clone_to"]."-".$new_id."_".$tmp[2];
  }
  else if (mb_substr($image_name,4,3) == "dl_"){
    $tmp = explode("_",$image_name);
    $tmp2 = explode("-",$tmp[1]);
    $tmp_name = $tmp[0]."_area-".$_DATA["clone_to"]."-".$new_id."-".$sub_id."_".$tmp[2];
  }
  else if (mb_substr($image_name,4,3) == "qs_"){
    $tmp = explode("_",$image_name);
    $tmp2 = explode("-",$tmp[1]);
    $tmp_name = $tmp[0]."_statement-".$_DATA["clone_to"]."-".$new_id."-".$sub_id."_".$tmp[2];
  }
  else if (mb_substr($image_name,4,3) == "cb_"){
    $tmp = explode("_",$image_name);
    $tmp2 = explode("-",$tmp[1]);
    $tmp_name = $tmp[0]."_box-".$_DATA["clone_to"]."-".$new_id."-".$sub_id."_".$tmp[2];
  }
  else if ((mb_substr($image_name,4,7) == "si_site")){
    $tmp = explode("_",$image_name);
    if (count($tmp) === 3) {
      $tmp2 = explode("-",$tmp[1]);
      $tmp_name = $tmp[0]."_site".$_DATA["clone_to"]."-".$_DATA["clone_to"]."-".$tmp2[2]."_".$tmp[2];
    }
    else if (count($tmp) === 4) {
      $tmp2 = explode("-",$tmp[2]);
      $tmp_name = $tmp[0]."_site".$_DATA["clone_to"]."_".$tmp2[0]."-".$_DATA["clone_to"]."-".$new_id."_".$tmp[3];
    }
    else if (count($tmp) === 5) {
      $tmp2 = explode("-",$tmp[3]);
      $tmp_name = $tmp[0]."_site".$_DATA["clone_to"]."_".$tmp[2]."_".$tmp2[0]."-".$_DATA["clone_to"]."-".$new_id."_".$sub_id."-".$tmp[4];
    }
  }
  else if ((mb_substr($image_name,4,3) == "ca_")){
    $tmp = explode("_",$image_name);
    if (count($tmp) === 3) {
      $tmp2 = explode("-",$tmp[1]);
      // e.g. img/ca_area1-1-21281-13_45.jpg -> img/ca_area<AREA_POSITION>-<SITE_ID>-<CIID>-<AREA_ID>_<RANDOM>.<jpg|png>
      $tmp_name = $tmp[0].'_'.$tmp2[0].'-'.$_DATA["clone_to"]."-".$new_ciid."-".$new_id."_".$tmp[2];
    }
    else if (count($tmp) === 4) {
      $tmp2 = explode("-",$tmp[2]);
      // e.g. img/ca_area1_box1-1-21281-13-37_302.jpg -> img/ca_area<AREA_POSITION>_box<BOX_POSITION>-<SITE_ID>-<CIID>-<AREA_ID>-<BOX_ID>_<RANDOM>.<jpg|png>
      $tmp_name = $tmp[0].'_'.$tmp[1]."_".$tmp2[0]."-".$_DATA["clone_to"]."-".$new_ciid.'-'.$sub_id.'-'.$new_id."_".$tmp[3];
    }
  }
  else if ((mb_substr($image_name,4,3) == "cx_")) {
    // replace timestamp digits only
    // make sure it generates a unique filename
    do {
      $tmp_name = mb_substr_replace($image_name, mb_substr((string)time(), -3, 3), -7, 3);
    }
    while ($tmp_name === $image_name || is_file('../' . $tmp_name));
  }
  else if ((mb_substr($image_name,4,7) == "sb-")){
    $tmp = explode("-",$image_name);
    $tmp_name = $tmp[0]."-".$_DATA["clone_to"]."-".$new_id."-".$tmp2[3];
  }
  else{
    $tmp = explode("-",$image_name);
    if (count($tmp) == 3)
      $tmp_name = $tmp[0]."-".$_DATA["clone_to"]."-".$new_id."-".$tmp[2];
    else if (count($tmp) == 4)
      $tmp_name = $tmp[0]."-".$_DATA["clone_to"]."-".$new_id."-".$tmp[3];
    else if (count($tmp) == 5)
      $tmp_name = $tmp[0]."-".$_DATA["clone_to"]."-".$new_id."-".$tmp[3]."-".$tmp[4];
    else {
      die("Can not get new image name for " . $image_name);
    }
  }

  // clone image
  clone_image($image_name,$tmp_name);

  return $tmp_name;
}

function clone_image($old_name, $new_name)
{
  global $_DATA;

  if ($_DATA['clone_dry_run']) {
    return;
  }

  $tmp                      = explode(".", $old_name);
  $tmp_name_wo_filetype     = $tmp[0];
  $tmp_filetype             = $tmp[1];
  $tmp_new                  = explode(".", $new_name);
  $tmp_new_name_wo_filetype = $tmp_new[0];
  $tmp_new_filetype         = $tmp_new[1];

  $tmp_file_name     = "../" . $tmp_name_wo_filetype . "." . $tmp_filetype;
  $tmp_new_file_name = "../" . $tmp_new_name_wo_filetype . "." . $tmp_new_filetype;
  if (is_file($tmp_file_name)) {
    copy($tmp_file_name, $tmp_new_file_name);
    chmod($tmp_new_file_name, 0644);
  }
  $tmp_file_name     = "../" . $tmp_name_wo_filetype . "-l." . $tmp_filetype;
  $tmp_new_file_name = "../" . $tmp_new_name_wo_filetype . "-l." . $tmp_new_filetype;
  if (is_file($tmp_file_name)) {
    copy($tmp_file_name, $tmp_new_file_name);
    chmod($tmp_new_file_name, 0644);
  }
  $tmp_file_name     = "../" . $tmp_name_wo_filetype . "-b." . $tmp_filetype;
  $tmp_new_file_name = "../" . $tmp_new_name_wo_filetype . "-b." . $tmp_new_filetype;
  if (is_file($tmp_file_name)) {
    copy($tmp_file_name, $tmp_new_file_name);
    chmod($tmp_new_file_name, 0644);
  }
  $tmp_file_name     = "../" . $tmp_name_wo_filetype . "-b2." . $tmp_filetype;
  $tmp_new_file_name = "../" . $tmp_new_name_wo_filetype . "-b2." . $tmp_new_filetype;
  if (is_file($tmp_file_name)) {
    copy($tmp_file_name, $tmp_new_file_name);
    chmod($tmp_new_file_name, 0644);
  }
  $tmp_file_name     = "../" . $tmp_name_wo_filetype . "-be." . $tmp_filetype;
  $tmp_new_file_name = "../" . $tmp_new_name_wo_filetype . "-be." . $tmp_new_filetype;
  if (is_file($tmp_file_name)) {
    copy($tmp_file_name, $tmp_new_file_name);
    chmod($tmp_new_file_name, 0644);
  }
  $tmp_file_name     = "../" . $tmp_name_wo_filetype . "-th." . $tmp_filetype;
  $tmp_new_file_name = "../" . $tmp_new_name_wo_filetype . "-th." . $tmp_new_filetype;
  if (is_file($tmp_file_name)) {
    copy($tmp_file_name, $tmp_new_file_name);
    chmod($tmp_new_file_name, 0644);
  }
}

function get_file_name($old_id, $new_id, $file_name, $sub_id = 0)
{
  global $_DATA;

  if (!$_DATA['clone_files']) {
    return null;
  }

  // invalid file name
  if (!$file_name) {
    return null;
  }

  $tmp       = explode('.', $file_name);
  $extension = array_pop($tmp);
  $tmp_name  = implode('.', $tmp) . '-' . $_DATA['clone_to'] . '.' . $extension;

  // clone file
  clone_file($file_name, $tmp_name);

  return $tmp_name;
}

function clone_file($old_name, $new_name)
{
  global $_DATA;

  if ($_DATA['clone_dry_run']) {
    return;
  }

  $tmp_old_name = '../' . $old_name;
  $tmp_new_name = '../' . $new_name;

  if (is_file($tmp_old_name)) {
    copy($tmp_old_name, $tmp_new_name);
    chmod($tmp_new_name, 0644);
  }
}


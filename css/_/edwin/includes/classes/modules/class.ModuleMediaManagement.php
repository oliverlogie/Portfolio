<?php

/**
 * MediaManagement Module Class
 *
 * $LastChangedDate: 2018-12-14 10:26:08 +0100 (Fr, 14 Dez 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleMediaManagement extends Module
{
  public static $subClasses = array(
      'node' => 'ModuleMediaManagementNode', // mn
      'area' => 'ModuleMediaManagementArea', // ma
      'all'  => 'ModuleMediaManagementAll',  // ml
  );

  protected $_prefix = 'mm';

  /**
   * @see ModuleMediaManagement::_issuu()
   * @var Issuu
   */
  private $_issuu;

  const CONTENT_TYPE_DL_ID = 14;

  public function show_innercontent ()
  {
    if (isset($_POST["process"]) && $this->action[0] == "new") $this->create_content();
    if (isset($_POST["process"]) && $this->action[0] == "edit") $this->edit_content();
    if (isset($_POST["process_newrelation"]) && $this->action[0] == "edit") $this->create_relation();
    if (isset($_POST['process_upload_on_issuu']) && $this->action[0] == "edit" && (int)$this->getItemId()) $this->_uploadAsDocument((int)$this->getItemId());
    if (isset($_POST['process_reset'])) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }
    $this->_deleteCentralFile(isset($_GET['deleteCentralFileID']) ? $_GET['deleteCentralFileID'] : 0);
    $this->_deleteRelation();
    $this->_deleteAreaRelation();
    $this->_deleteIssuuDocument();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->show_form();
    }
    else {
      return $this->show_list();
    }
  }

  /**
   * Sends data to the client when the action is "response", handles special requests for ModuleMediaManagement.
   *
   * @param string $request
   *        The content of the "request" variable inside GET or POST data.
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'ContentItemDLAreas':
        $this->_sendResponseContentItemDLAreas();
        break;
      default:
        // Call the sendResponse() method of the parent Module class
        parent::sendResponse($request);
        break;
    }
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
    $gridSql = " SELECT CFID, CFTitle, CFFile, CFCreated, CFModified, CFSize, "
             . "        COUNT(cfr.FK_CFID) AS CNT_Relations "
             . " FROM {$this->table_prefix}centralfile cf "
             . " LEFT JOIN ( "
             . "   SELECT FK_CFID "
             . "   FROM {$this->table_prefix}file "
             . "   UNION ALL "
             . "   SELECT FK_CFID "
             . "   FROM {$this->table_prefix}contentitem_dl_area_file "
             . "   WHERE FK_CFID IS NOT NULL "
             . " ) cfr ON cf.CFID = cfr.FK_CFID "
             . " WHERE FK_SID = $this->site_id ";

    // 2. fields = columns
    $filterSelective = array('CFCreated', 'CFModified');
    $queryFields[1] = array('type' => 'text', 'value' => 'CFTitle', 'lazy' => true);
    $queryFields[2] = array('type' => 'text', 'value' => 'CFFile', 'lazy' => true);
    $queryFields[3] = array('type' => 'selective', 'value' => 'CFCreated',
                            'valuelist' => $filterSelective);

    // 3. filter fields = query fields as we do not need additional fields to be
    // filterable
    $filterFields = $queryFields;

    // 4. filter types
    $filterTypes = array(
      'CFTitle'    => 'text',
      'CFFile'     => 'text',
      'CFCreated'  => 'text',
      'CFModified' => 'text',
    );

    // 5. order options
    $ordersValuelist = array(
      1 => array('field' => 'CFTitle',    'order' => 'ASC'),
      2 => array('field' => 'CFTitle',    'order' => 'DESC'),
      3 => array('field' => 'CFFile',     'order' => 'ASC'),
      4 => array('field' => 'CFFile',     'order' => 'DESC'),
      5 => array('field' => 'CFCreated',  'order' => 'ASC'),
      6 => array('field' => 'CFCreated',  'order' => 'DESC'),
      7 => array('field' => 'CFModified', 'order' => 'ASC'),
      8 => array('field' => 'CFModified', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;

    $presetOrders = array(1 => 6);

    // 5. page
    $page = ($get->exists('mm_page')) ? $get->readInt('mm_page') : ($this->session->read('mm_page') ? $this->session->read('mm_page') : 1);
    $this->session->save('mm_page', $page);

    // 7. prefix
    $prefix = array('config'  => $this->_prefix,
                    'lang'    => $this->_prefix,
                    'session' => $this->_prefix,
                    'tpl'     => $this->_prefix);

    //---------------------------------------------------------------------- //
    $grid = new DataGrid($this->db, $this->session, $prefix);
    $grid->load($gridSql, $queryFields, $filterFields, $filterTypes,
                $orders, $page, false, null, $presetOrders, null, null,
                ConfigHelper::get($this->_prefix . '_results_per_page'),
                null, 'GROUP BY CFID, CFTitle, CFFile, CFCreated, CFModified, CFSize');
    return $grid;
  }

  /**
   * show a form for creating or editing central files
   *
   * @return unknown_type
   */
  private function show_form()
  {
    global $_LANG, $_LANG2;

    $titleSeparator = ConfigHelper::get('hierarchical_title_separator', 'mm');
    $relationsItems = array();
    $wordsFilelinkRelationsItems = array();
    $areaFileRelationsItems = array();
    if ($this->item_id) { // edit -> load data
      $row = $this->db->GetRow(<<<SQL
SELECT CFID, CFTitle, CFFile, CFCreated, CFModified, CFShowAlways, CFProtected
FROM {$this->table_prefix}centralfile cf
WHERE CFID = $this->item_id
SQL
      );

      $mm_title = parseOutput($row["CFTitle"]);
      $mm_file = "../".$row["CFFile"];
      // insert into file link user's current session id, we need this to
      // identify the backend user on the frontend
      $mm_filePath = $this->_fileUrlForBackendUser($row["CFFile"]);
      $mm_filename = parseOutput(mb_substr(mb_strrchr("../".$row["CFFile"], "/"), 1));
      $mm_created = date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'mm'), strtotime($row["CFCreated"]));
      $mm_modified = $row['CFModified'] ? date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'mm'), strtotime($row['CFModified'])) : '---';
      $mm_form_action = "edit";
      $mm_function = "edit";
      $mm_show_always_selected = ($row['CFShowAlways']) ? '' : 'checked="checked"';
      $mm_protected_selected = ($row['CFProtected']) ? 'checked="checked"' : '';

      // read all relations to content items for the current central file from the database
      $sql = 'SELECT FID, FTitle AS Title, FCreated, CIID, FK_SID, CIIdentifier '
           . "FROM {$this->table_prefix}file f "
           . "JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID "
           . "WHERE FK_CFID = $this->item_id ";
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)) {
        $relationScope = 'local';
        if ((int)$row['FK_SID'] != $this->site_id) {
          $relationScope = 'global';
        }
        $linkedSite = $this->_navigation->getSiteByID((int)$row['FK_SID']);
        $linkedSiteTitle = $linkedSite->getTitle();
        $relationScopeLabel = sprintf($_LANG["mm_relation_scope_{$relationScope}_label"], $linkedSiteTitle);

        $relationsItems[] = array(
          'mm_relation_title' => $row['Title'] ? parseOutput($row['Title']) : $mm_title,
          'mm_relation_title_type' => $row['Title'] ? 'overridden' : 'inherited',
          'mm_relation_created' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'mm'), strtotime($row['FCreated'])),
          'mm_relation_scope' => $relationScope,
          'mm_relation_scope_label' => $relationScopeLabel,
          'mm_relation_contentitem_title' => parseOutput($this->_getHierarchicalTitle($row['CIID'], $titleSeparator)),
          'mm_relation_contentitem_link' => "index.php?action=files&amp;site={$row['FK_SID']}&amp;page={$row['CIID']}",
          'mm_relation_delete_link' => "index.php?action=mod_mediamanagement&amp;action2=main;edit&amp;page=$this->item_id&amp;deleteRelationID={$row['FID']}",
        );
      }

      // read all relations to content pages with download links in text areas for the current central file from the database
      $sql = 'SELECT CFID, CFTitle AS Title, CFCreated, ciwfl.FK_CIID, ci.FK_SID, CIIdentifier, ciwfl.WFTextCount '
           . "FROM {$this->table_prefix}centralfile cf "
           . "JOIN {$this->table_prefix}contentitem_words_filelink ciwfl ON ciwfl.WFFile = cf.CFFile "
           . "JOIN {$this->table_prefix}contentitem ci ON ciwfl.FK_CIID = ci.CIID "
           . "WHERE CFID = $this->item_id "
           . 'ORDER BY CIIdentifier, Title';
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)) {
        $relationScope = 'local';
        if ((int)$row['FK_SID'] != $this->site_id) {
          $relationScope = 'global';
        }
        $linkedSite = $this->_navigation->getSiteByID((int)$row['FK_SID']);
        $linkedSiteTitle = $linkedSite->getTitle();
        $relationScopeLabel = sprintf($_LANG["mm_relation_scope_{$relationScope}_label"], $linkedSiteTitle);

        $wordsFilelinkRelationsItems[] = array(
          'mm_words_filelink_relation_title' => $row['Title'] ? parseOutput($row['Title']) : $mm_title,
          'mm_words_filelink_relation_title_type' => $row['Title'] ? 'overridden' : 'inherited',
          'mm_words_filelink_relation_created' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'mm'), strtotime($row['CFCreated'])),
          'mm_words_filelink_relation_scope' => $relationScope,
          'mm_words_filelink_relation_scope_label' => $relationScopeLabel,
          'mm_words_filelink_relation_contentitem_title' => parseOutput($this->_getHierarchicalTitle($row['FK_CIID'], $titleSeparator)),
          'mm_words_filelink_relation_contentitem_link' => "index.php?action=content&amp;site={$row['FK_SID']}&amp;page={$row['FK_CIID']}",
          'mm_words_filelink_relation_number_of_links' => $row['WFTextCount'],
        );
      }

      // read all relations to DL-areas for the current central file from the database
      $sql = 'SELECT DFID, DFTitle, DFCreated, DAID, DATitle, DAPosition, CIID, '
           . '       FK_SID '
           . "FROM {$this->table_prefix}contentitem_dl_area_file cidlaf "
           . "JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID "
           . "JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID "
           . "WHERE FK_CFID = $this->item_id "
           . 'ORDER BY CIIdentifier, DATitle, DFTitle ';
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)) {
        $areaRelationScope = 'local';
        if ((int)$row['FK_SID'] != $this->site_id) {
          $areaRelationScope = 'global';
        }
        $linkedSite = $this->_navigation->getSiteByID((int)$row['FK_SID']);
        $linkedSiteTitle = $linkedSite->getTitle();
        $areaRelationScopeLabel = sprintf($_LANG["mm_area_relation_scope_{$areaRelationScope}_label"], $linkedSiteTitle);

        $areaFileRelationsItems[] = array(
          'mm_area_relation_title' => $row['DFTitle'] ? parseOutput($row['DFTitle']) : $mm_title,
          'mm_area_relation_title_type' => $row['DFTitle'] ? 'overridden' : 'inherited',
          'mm_area_relation_created' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'mm'), strtotime($row['DFCreated'])),
          'mm_area_relation_scope' => $areaRelationScope,
          'mm_area_relation_scope_label' => $areaRelationScopeLabel,
          'mm_area_relation_contentitem_title' => parseOutput($this->_getHierarchicalTitle($row['CIID'], $titleSeparator)),
          'mm_area_relation_contentitem_area_title' => $row['DATitle'],
          'mm_area_relation_contentitem_link' => "index.php?action=content&amp;site={$row['FK_SID']}&amp;page={$row['CIID']}&amp;area={$row['DAID']}#a_area{$row['DAPosition']}_files",
          'mm_area_relation_delete_link' => "index.php?action=mod_mediamanagement&amp;action2=main;edit&amp;page=$this->item_id&amp;deleteAreaRelationID={$row['DFID']}",
        );
      }
    }
    else { // new
      $post = new Input(Input::SOURCE_POST);

      $mm_file = "";
      $mm_filename = "";
      $mm_created = "";
      $mm_modified = '';
      $mm_form_action = "new";
      $mm_function = "new";
      $mm_filePath = '';

      $mm_title = $post->readString('mm_title', Input::FILTER_PLAIN);
      if (!isset($_POST["process"])) {
        $mm_show_always_selected = 'checked="checked"';
      }
      else {
        $mm_show_always_selected = $post->exists('mm_show_always') ? 'checked="checked"' : '';
      }
      $mm_protected_selected = $post->exists('mm_protected') ? 'checked="checked"' : '';
    }

    // ermitteln, ob ContentItemDL aktiviert ist
    $sql = 'SELECT CTActive '
         . "FROM {$this->table_prefix}contenttype "
         . 'WHERE CTID = ' . self::CONTENT_TYPE_DL_ID;
    $areaActivated = $this->db->GetOne($sql);

    $mm_hidden_fields = '<input type="hidden" name="action" value="mod_mediamanagement" /><input type="hidden" name="action2" value="main;'.$mm_form_action.'" /><input type="hidden" name="page" value="'.$this->item_id.'" /><input type="hidden" name="site" value="'.$this->site_id.'" />';

    $this->tpl->load_tpl("content_mediamanagement", "modules/ModuleMediaManagement.tpl");
    $this->tpl->parse_if('content_mediamanagement', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('mm'));
    $this->tpl->parse_if("content_mediamanagement", "existing_centralfile", $mm_filename, array(
      "mm_file" => $mm_filePath,
      "mm_filename" => $mm_filename,
      "mm_created" => $mm_created,
      'mm_modified' => $mm_modified,
      'mm_size' => formatFileSize(filesize($mm_file)),
    ));
    $this->tpl->parse_if("content_mediamanagement", "show_relations", $this->item_id, array(
      "mm_site" => $this->site_id,
      "mm_cfid" => $this->item_id,
    ));
    $this->tpl->parse_if('content_mediamanagement', 'relations', $relationsItems);
    $this->tpl->parse_loop('content_mediamanagement', $relationsItems, 'relations_items');
    $this->tpl->parse_if('content_mediamanagement', 'no_relations', $this->item_id && !$relationsItems);
    $this->tpl->parse_if('content_mediamanagement', 'words_filelink_relations', $wordsFilelinkRelationsItems);
    $this->tpl->parse_loop('content_mediamanagement', $wordsFilelinkRelationsItems, 'words_filelink_relations_items');
    $this->tpl->parse_if('content_mediamanagement', 'no_words_filelink_relations', $this->item_id && !$wordsFilelinkRelationsItems);
    $this->tpl->parse_if('content_mediamanagement', 'area_activated', $areaActivated);
    $this->tpl->parse_if('content_mediamanagement', 'area_relations', $areaFileRelationsItems);
    $this->tpl->parse_loop('content_mediamanagement', $areaFileRelationsItems, 'area_relations_items');
    $this->tpl->parse_if('content_mediamanagement', 'no_area_relations', $this->item_id && !$areaFileRelationsItems);
    $includeContentTypes = ContentType::TYPE_NORMAL;
    $mm_content = $this->tpl->parsereturn("content_mediamanagement", array_merge(array(
      "mm_show_always_selected" => $mm_show_always_selected,
      "mm_protected_selected" => $mm_protected_selected,
      "mm_title" => $mm_title,
      "mm_function_label" => $_LANG["mm_function_".$mm_function."_label"],
      "mm_function_label2" => $_LANG["mm_function_".$mm_function."_label2"],
      "mm_box_label" => $_LANG["mm_box_".$mm_function."_label"],
      "mm_action" => "index.php",
      "mm_hidden_fields" => $mm_hidden_fields,
      "mm_module_action_boxes" => $this->_getContentActionBoxes(),
      'mm_upload_as_document' => $this->_getContentUploadAsDocument(),
      'mm_autocomplete_contentitem_url' => "index.php?action=mod_response_mediamanagement&site=$this->site_id&request=ContentItemAutoComplete&includeContentTypes=$includeContentTypes&scope=global",
      'mm_contentitem_dl_areas_url' => "index.php?action=mod_response_mediamanagement&request=ContentItemDLAreas",
      "mm_max_file_size" => ConfigHelper::get('mm_file_size'),
    ), $_LANG2["mm"]));

    return array(
        'content'      => $mm_content,
        'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * create a relation from the central file to a page
   */
  private function create_relation() {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ciid = (int)$_POST['mm_newrelation_ciid'];
    $daid = (int)$_POST['mm_newrelation_daid'];
    if (!$ciid && !$daid) {
      $this->setMessage(Message::createFailure($_LANG['mm_message_newrelation_no_page']));
      return;
    }

    $title = $post->readString('mm_newrelation_title', Input::FILTER_RIGHT_TITLE);
    $now = date("Y-m-d H:i:s");

    if ($daid) {
      // save area relation
      $sql = 'SELECT COUNT(DFID) + 1 '
           . "FROM {$this->table_prefix}contentitem_dl_area_file "
           . "WHERE FK_DAID = $daid ";
      $position = $this->db->GetOne($sql);
      $sql = "INSERT INTO {$this->table_prefix}contentitem_dl_area_file "
           . '(FK_DAID, FK_CFID, DFTitle, DFCreated, DFPosition) '
           . "VALUES($daid, $this->item_id, '{$this->db->escape($title)}', '$now', $position) ";
      $result = $this->db->query($sql);
    } else {
      // save relation
      $sql = 'SELECT COUNT(FID) + 1 '
           . "FROM {$this->table_prefix}file "
           . "WHERE FK_CIID = $ciid ";
      $position = $this->db->GetOne($sql);
      $sql = "INSERT INTO {$this->table_prefix}file "
           . '(FTitle, FPosition, FCreated, FK_CFID, FK_CIID) '
           . "VALUES('{$this->db->escape($title)}', $position, '$now', $this->item_id, $ciid) ";
      $result = $this->db->query($sql);

      // If the relation itself has no title we need to get the title of the central file for spidering.
      // Spidering only happens for relations with content items at the moment.
      $spiderTitle = $title;
      if (!$spiderTitle) {
        $sql = 'SELECT CFTitle '
             . "FROM {$this->table_prefix}centralfile "
             . "WHERE CFID = $this->item_id ";
        $spiderTitle = $this->db->GetOne($sql);
      }

      // Add the title of the relation (or the title of the file) to the search index.
      $this->_spiderDownload($spiderTitle, $ciid, true);
    }

    if ($result) {
      $this->setMessage(Message::createSuccess($_LANG['mm_message_newrelation_success']));
    }
  }

  /**
   * create a new central file
   */
  private function create_content() {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!mb_strlen(trim($_POST['mm_title']))) {
      $this->setMessage(Message::createFailure($_LANG['mm_message_insufficient_input']));
      return;
    }

    $fileArray = $_FILES['mm_file'];
    $maxSize = ConfigHelper::get('mm_file_size');
    $fileTypes = ConfigHelper::get('mm_file_types');
    $mm_title = $post->readString('mm_title', Input::FILTER_RIGHT_TITLE);
    $mm_file = $this->_storeFile($fileArray, $maxSize, $fileTypes);
    $mm_show_always = ($post->exists('mm_show_always')) ? 0 : 1;
    $mm_protected = ($post->exists('mm_protected')) ? 1 : 0;
    if (!$mm_file) {
      $this->setMessage(Message::createFailure($_LANG['mm_message_insufficient_input']));
      return;
    }

    // save centralfile data
    $now = date('Y-m-d H:i:s');
    $fileSize = (int)$fileArray['size'];
    $sql = "INSERT INTO {$this->table_prefix}centralfile (CFTitle, CFFile, CFCreated, CFShowAlways, CFProtected, CFSize, FK_SID) "
         . "VALUES ('{$this->db->escape($mm_title)}', '$mm_file', '$now', $mm_show_always, $mm_protected, $fileSize, $this->site_id) ";
    $result = $this->db->query($sql);
    $this->item_id = $this->db->insert_id();

    if ($result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['mm_message_newitem_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['mm_message_newitem_success']));
      }
    }
  }

  /**
   * modify a central file
   */
  private function edit_content() {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $mm_new_title = $post->readString('mm_title', Input::FILTER_RIGHT_TITLE);
    if (!$mm_new_title) {
      $this->setMessage(Message::createFailure($_LANG['mm_message_insufficient_input']));
      return;
    }

    // determine the name and title of the old central file
    $sql = " SELECT * "
         . " FROM {$this->table_prefix}centralfile cf "
         . " LEFT JOIN {$this->table_prefix}issuu_document id "
         . "        ON cf.FK_IDID_IssuuDocument = id.IDID "
         . " WHERE CFID = $this->item_id ";
    $oldFileDetails = $this->db->GetRow($sql);
    $mm_old_file = $oldFileDetails['CFFile'];
    $mm_old_title = $oldFileDetails['CFTitle'];

    $fileArray = $_FILES['mm_file'];
    $maxSize = ConfigHelper::get('mm_file_size');
    $fileTypes = ConfigHelper::get('mm_file_types');
    $mm_new_file = $this->_storeFile($fileArray, $maxSize, $fileTypes, $mm_old_file);

    $sqlUpdateFile = '';
    if ($mm_new_file) {
      $now = date('Y-m-d H:i:s');
      $fileSize = (int)$fileArray['size'];
      $sqlUpdateFile = " CFFile = '$mm_new_file', "
                     . " CFModified = '$now', "
                     . " CFSize = $fileSize, ";
    }

    // If a new file is uploaded and the old file was converted to an issuu
    // document, we have to replace the issue document by a new document too.
    // So we remove the old document first
    if ($mm_new_file && $oldFileDetails['FK_IDID_IssuuDocument']) {
      $this->_deleteDocument($oldFileDetails['IDName']);

      $sql = " UPDATE {$this->table_prefix}centralfile "
        . " SET FK_IDID_IssuuDocument = '' "
        . " WHERE CFID = $this->item_id ";
      $this->db->query($sql);
    }

    // if the title was changed we have to update the search index of all related content items with no custom title
    if ($mm_new_title != $mm_old_title) {
      $sql = 'SELECT FK_CIID '
           . "FROM {$this->table_prefix}file "
           . "WHERE FK_CFID = $this->item_id "
           . "AND COALESCE(FTitle, '') = '' "
           . 'UNION ALL '
           . 'SELECT FK_CIID '
           . "FROM {$this->table_prefix}contentitem_dl_area "
           . "JOIN {$this->table_prefix}contentitem_dl_area_file ON DAID = FK_DAID "
           . "WHERE FK_CFID = $this->item_id "
           . "AND COALESCE(DFTitle, '') = '' ";
      $related_contentitems = $this->db->GetCol($sql);
      foreach ($related_contentitems as $ciid) {
        $this->_spiderDownload($mm_old_title, $ciid, false);
        $this->_spiderDownload($mm_new_title, $ciid, true);
      }
    }

    $mm_show_always = ($post->exists('mm_show_always')) ? 0 : 1;
    $mm_protected = ($post->exists('mm_protected')) ? 1 : 0;

    $sql = "UPDATE {$this->table_prefix}centralfile "
         . "SET $sqlUpdateFile "
         . "    CFTitle = '{$this->db->escape($mm_new_title)}', "
         . "    CFShowAlways = {$mm_show_always}, "
         . "    CFProtected = {$mm_protected} "
         . "WHERE CFID = $this->item_id ";
    $result = $this->db->query($sql);

    if (isset($now)) {
      $sql = "UPDATE {$this->table_prefix}file "
           . "SET FModified = '{$now}' "
           . "WHERE FK_CFID = $this->item_id ";
      $this->db->query($sql);

      $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
           . "SET DFModified = '{$now}' "
           . "WHERE FK_CFID = $this->item_id ";
      $result = $this->db->query($sql);

      // The file was updated and there existed an old issue file, so we create
      // a new one here
      if ($oldFileDetails['FK_IDID_IssuuDocument']) {
        $this->_uploadAsDocument($this->getItemId());
      }
    }

    // if the filename was changed we have to delete the old file
    if ($mm_new_file && ($mm_new_file != $mm_old_file)) {
      unlinkIfExists("../$mm_old_file");
    }

    if (!$this->_getMessage() && $result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['mm_message_edititem_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['mm_message_edititem_success']));
      }
    }
  }

  /**
   * Delete a central file if the GET parameter deleteCentralFileID is set.
   */
  protected function _deleteCentralFile($fid)
  {
    global $_LANG;

    $fid = (int) $fid;

    if (!$fid) {
      return;
    }

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}centralfile cf "
         . " LEFT JOIN {$this->table_prefix}issuu_document id "
         . "        ON cf.FK_IDID_IssuuDocument = id.IDID "
         . " WHERE CFID = " . $this->db->escape($fid) . " ";
    $row = $this->db->GetRow($sql);

    if (!$row) {
      return;
    }

    if ($row['IDName']) {
      $this->_deleteDocument($row['IDName']);
    }

    parent::_deleteCentralFile($fid);

    $this->setMessage(Message::createSuccess($_LANG['mm_message_deleteitem_success']));
  }

  /**
   * @return Issuu
   */
  protected function _issuu()
  {
    if ($this->_issuu === null) {
      $this->_issuu = new Issuu($this->db, $this->table_prefix, $this->site_id, $this->_prefix);
    }

    return $this->_issuu;
  }

  /**
   * Deletes a relation if the GET parameter deleteRelationID is set.
   */
  private function _deleteRelation()
  {
    global $_LANG;

    if (!isset($_GET['deleteRelationID'])) {
      return;
    }

    $fid = (int)$_GET['deleteRelationID'];

    // determine title and position of deleted file
    $sql = 'SELECT FTitle, FPosition, FK_CIID, CFTitle '
         . "FROM {$this->table_prefix}file "
         . "JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE FID = $fid ";
    $file = $this->db->GetRow($sql);

    if (!$file) {
      return;
    }

    // delete relation
    $sql = "DELETE FROM {$this->table_prefix}file "
         . "WHERE FID = $fid ";
    $result = $this->db->query($sql);

    // move following relations one position up
    $sql = "UPDATE {$this->table_prefix}file "
         . 'SET FPosition = FPosition - 1 '
         . "WHERE FK_CIID = {$file['FK_CIID']} "
         . "AND FPosition > {$file['FPosition']} "
         . 'ORDER BY FPosition ASC ';
    $result = $this->db->query($sql);

    // remove the file title from the search index of the content item
    $this->_spiderDownload(coalesce($file['FTitle'], $file['CFTitle']), $file['FK_CIID'], false);

    $this->setMessage(Message::createSuccess($_LANG['mm_message_deleterelation_success']));
  }

  /**
   * Deletes a DL area relation if the GET parameter deleteAreaRelationID is set.
   */
  private function _deleteAreaRelation()
  {
    global $_LANG;

    if (!isset($_GET['deleteAreaRelationID'])) {
      return;
    }

    $dfid = (int)$_GET['deleteAreaRelationID'];

    // read details for the central file and the area file
    $sql = 'SELECT DFTitle, DFPosition, FK_DAID, FK_CIID, CFTitle '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "JOIN {$this->table_prefix}contentitem_dl_area ON FK_DAID = DAID "
         . "JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE DFID = $dfid ";
    $row = $this->db->GetRow($sql);

    if (!$row) {
      return;
    }

    // delete relation
    $sql = "DELETE FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE DFID = $dfid ";
    $result = $this->db->query($sql);

    // move following files one position up
    $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
         . 'SET DFPosition = DFPosition - 1 '
         . "WHERE FK_DAID = {$row['FK_DAID']} "
         . "AND DFPosition > {$row['DFPosition']} "
         . 'ORDER BY DFPosition ASC ';
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['mm_message_deletearearelation_success']));
  }

  private function _deleteIssuuDocument()
  {
    global $_LANG;

    $request = new Input(Input::SOURCE_REQUEST);
    $fileId = (int)$this->getItemId();

    if ($request->readString('delete_issuu_document') && $fileId) {
      $sql = " SELECT * "
           . " FROM {$this->table_prefix}centralfile cf "
           . " LEFT JOIN {$this->table_prefix}issuu_document id "
           . "        ON cf.FK_IDID_IssuuDocument = id.IDID "
           . " WHERE CFID = '$fileId' ";
      $document = $this->db->GetRow($sql);

      if ($document && $document['IDName']) {
        $result = $this->_deleteDocument($document['IDName']);

        if ($result) {
          $this->setMessage(Message::createSuccess($_LANG['mm_message_success_issuu_document_delete']));

          $sql = " UPDATE {$this->table_prefix}centralfile "
               . " SET FK_IDID_IssuuDocument = '' "
               . " WHERE CFID = $fileId ";
          $this->db->query($sql);
        }
        else {
          $this->setMessage(Message::createFailure($_LANG['mm_message_failure_issuu_document_delete']));
        }
      }
      else {
        $this->setMessage(Message::createFailure($_LANG['mm_message_failure_issuu_document_delete']));
      }
    }
  }

  /**
   * Sends a list of DL-Areas for a specific content item to the client.
   */
  private function _sendResponseContentItemDLAreas()
  {
    $ciid = 0;
    if (isset($_GET['ciid'])) {
      $ciid = (int)$_GET['ciid'];
    }

    if (!$ciid) {
      echo Json::Encode(array());
      return;
    }

    $sql = 'SELECT DAID, DATitle '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = $ciid "
         . "AND COALESCE(DATitle, '') != '' ";
    $result = $this->db->query($sql);

    $areas = array();
    while ($row = $this->db->fetch_row($result)) {
      $areas[] = array(
        'id'    => (int)$row['DAID'],
        'title' => $row['DATitle'],
      );
    }
    $this->db->free_result($result);

    echo Json::Encode($areas);
  }

  /**
   * Shows a list containing all central files.
   *
   * @return array
   *        contains backend content
   */
  private function show_list()
  {
    global $_LANG, $_LANG2;

    $data = $this->_grid()->get_result();
    if (is_array($data)) {
      $i = 1;

      $sql = ' SELECT WFFile, SUM(WFTextCount) AS WFTextCount '
           . " FROM {$this->table_prefix}contentitem_words_filelink "
           . ' GROUP BY WFFile ';
      $resFileLinkCount = $this->db->GetAssoc($sql);

      foreach ($data as $key => &$value) {
        $row = $this->_grid()->get_grid_data($key);
        $id = $row['CFID'];

        $relationCount = $row['CNT_Relations'];
        if (isset($resFileLinkCount[$row['CFFile']])) {
          $relationCount += $resFileLinkCount[$row['CFFile']];
        }

        // insert into file link user's current session id, we need this to
        // identify the backend user on the frontend
        $frontendFilePath = $this->_fileUrlForBackendUser($row['CFFile']);

        // store values for output
        $value['mm_title']           = parseOutput($row['CFTitle']);
        $value['mm_file']            = $frontendFilePath;
        $value['mm_filename']        = parseOutput(mb_substr(mb_strrchr("../".$row["CFFile"], "/"), 1));
        $value['mm_size']            = formatFileSize($row['CFSize']);
        $value['mm_col3_content']    = $value['mm_col3_content'] ? date('Y-m-d', strtotime($value['mm_col3_content'])) : '';
        $value['mm_relations_count'] = $relationCount;
        $value['mm_delete_link']     = $this->_parseUrl('', array('deleteCentralFileID' => $id));
        $value['mm_content_link']    = $this->_parseUrl('edit', array('page' => $id));
        $value['mm_row_bg']          = ( $i++ %2 ) ? 'even' : 'odd';
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

    $tplName = 'module_mediamanagement_list';
    $this->tpl->load_tpl($tplName, 'modules/ModuleMediaManagement_list.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(),
        $this->_getMessageTemplateArray('mm'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');

    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl()), array (
        'mm_action'                => $this->_parseUrl(),
        'mm_site_selection'        => $this->_parseModuleSiteSelection('mediamanagement', $_LANG['mm_site_label']),
        'mm_list_label'            => $_LANG['mm_function_list_label'],
        'mm_list_label2'           => $_LANG['mm_function_list_label2'],
        'mm_count_all'             => $this->_grid()->get_quantity_total_rows(),
        'mm_count_current'         => $currentRows,
        'mm_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl() . '&amp;mm_page=','_bottom'),
        'mm_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
        'mm_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl() . '&amp;mm_page=','_top'),
        'mm_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['mm']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(),
    );
  }

  private function _getContentUploadAsDocument()
  {
    $content = '';
    if ((int)$this->getItemId() && ConfigHelper::get('mm_file_upload_on_issuu')) {
      $vars = array();
      $fileId = (int)$this->getItemId();

      $sql = " SELECT FK_IDID_IssuuDocument "
            . " FROM {$this->table_prefix}centralfile "
            . " WHERE CFID = '$fileId' ";
      $documentId = $this->db->GetOne($sql);
      $documentState = false;

      if ($documentId) {
        $sql = " SELECT * "
             . " FROM {$this->table_prefix}issuu_document "
             . " WHERE IDID = '$documentId' ";
        $row = $this->db->GetRow($sql);

        if ($row) {
          if (($row['IDState'] == Issuu::STATE_PROCESSING || !$row['IDState']) && $row['IDID']) {
            $state = $this->_issuu()->determineState($row['IDDocumentId']);
            $this->_issuu()->updateState($row['IDID'], $state);
            $row['IDState'] = $state;
          }
        }

        $vars = array_merge($vars, array(
          'mm_issuu_document_delete_link' => "index.php?action=mod_mediamanagement&amp;action2=main;edit&amp;page={$fileId}&amp;site={$this->site_id}&amp;delete_issuu_document={$row['IDDocumentId']}",
          'mm_issuu_document_name'        => $row['IDName'],
          'mm_issuu_document_state'       => $row['IDState'],
          'mm_issuu_document_title'       => $row['IDTitle'],
          'mm_issuu_document_user'        => $row['IDUsername'],
        ));

        $documentState =  $row['IDState'];
      }

      $tplName = 'mm_content_upload_as_document';
      $this->tpl->load_tpl($tplName, 'modules/ModuleMediaManagement_upload_on_issuu.tpl');
      $this->tpl->parse_if($tplName, 'available', $documentId);
      $this->tpl->parse_if($tplName, 'unavailable', !$documentId);
      $this->tpl->parse_if($tplName, 'mm_issuu_document_active', $documentState == Issuu::STATE_ACTIVE);
      $this->tpl->parse_if($tplName, 'mm_issuu_document_delete', $documentState == Issuu::STATE_ACTIVE);
      $this->tpl->parse_if($tplName, 'mm_issuu_document_processing', $documentState == Issuu::STATE_PROCESSING);
      $this->tpl->parse_if($tplName, 'mm_issuu_document_failure', $documentState == Issuu::STATE_FAILURE);
      $content = $this->tpl->parsereturn($tplName, $vars);
    }

    return $content;
  }

  private function _uploadAsDocument($centralfileId)
  {
    $centralfileId = (int)$centralfileId;

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}centralfile cf "
         . " LEFT JOIN {$this->table_prefix}issuu_document id "
         . "        ON cf.FK_IDID_IssuuDocument = id.IDID "
         . " WHERE CFID = '$centralfileId' ";
    $centralfile = $this->db->GetRow($sql);

    if ($centralfile) {
      // remove current file before starting new upload
      if ($centralfile['IDID']) {
        $this->_deleteDocument($centralfile['IDName']);

        $sql = " UPDATE {$this->table_prefix}centralfile "
          . " SET FK_IDID_IssuuDocument = '' "
          . " WHERE CFID = $centralfileId ";
        $this->db->query($sql);
      }

      // Issuu document upload
      $this->_issuu()->requestUpload(array(
        'name'     => $centralfile['CFFile'],
        'tmp_name' => base_path() . $centralfile['CFFile'],
      ), basename($centralfile['CFFile']), basename($centralfile['CFFile']));

      if ($this->_issuu()->isResponseOk() === false) {
        $status = false;
      }
      else {
        $documentId = $this->_issuu()->saveToDb();

        $sql = " UPDATE {$this->table_prefix}centralfile "
          . " SET FK_IDID_IssuuDocument = $documentId "
          . " WHERE CFID = $centralfileId ";
        $this->db->query($sql);

        $status = true;
      }
    }
    else {
      $status = false;
    }


    if ($status !== true) {
      $msg = $this->_issuu()->getErrorMsg('mm');
      $this->setMessage(Message::createFailure($msg));
    }
  }

  /**
   * Deletes current document by name if available.
   *
   * @param string $name
   *        the id name string of document
   *
   * @return boolean
   *         True if current document was deleted.
   */
  private function _deleteDocument($name)
  {
    // If document is not available, return.
    if (!$name) {
      return false;
    }

    // Delete from Issuu server
    $this->_issuu()->requestDelete($name);
    if ($this->_issuu()->isResponseOk() === false) {
      return false;
    }

    // Delete database entry
    $this->_issuu()->deleteFromDb($name);
    return true;
  }
}
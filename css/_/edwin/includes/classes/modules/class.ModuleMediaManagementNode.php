<?php

/**
 * MediaManagementNode Module Class
 *
 * $LastChangedDate: 2018-12-14 10:26:08 +0100 (Fr, 14 Dez 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleMediaManagementNode extends Module
{
  /**
   * contains the available filter criteria including SQL WHERE clauses
   * the format is "name" => ( "DBColumn1 = '", "' OR DBColumn2 LIKE '%", "%'")
   * the filter expression is inserted between the array elements
   * @var array
   */
  private $listFilters = array(
    "title" => array("FTitle LIKE '%", "%'"),
    "filename" => array("FFile LIKE '%", "%'"),
    "identifier" => array("CIIdentifier LIKE '%", "%'"),
  );

  public function show_innercontent ()
  {
    if ($this->action[0] == "convert") $this->convert_content((int)$_GET["fid"]);
    if (isset($_GET["did"])) $this->delete_content((int)$_GET["did"]);

    return $this->show_list();
  }

  /**
   * determine the total amount of decentral files from the database
   *
   * @param string $filterType the index of the filter inside the $this->listFilters array
   * @param string $filterText the filter text
   * @return integer specifies the total amount of decentral files
   */
  private function get_decentralfiles_count($filterType, $filterText) {
    $sqlFilter = "";
    if ($filterText) {
      $sqlFilter = "AND " . implode($filterText, $this->listFilters[$filterType]);
    }

    return $this->db->GetOne(<<<SQL
SELECT COUNT(FID)
FROM {$this->table_prefix}file f
JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID
WHERE FK_SID = $this->site_id
AND FFile IS NOT NULL
$sqlFilter
SQL
    );
  }

  /**
   * read all decentral files from the database
   *
   * @param string $filterType the index of the filter inside the $this->listFilters array
   * @param string $filterText the filter text
   * @return array contains an unordered list of decentral files (FID -> FTitle, FFile, FCreated, FModified, CIID, CTitle, CPosition, FK_SID, FK_CIID)
   */
  private function get_decentralfiles($filterType, $filterText) {
    $sqlFilter = "";
    if ($filterText) {
      $sqlFilter = "AND " . implode($filterText, $this->listFilters[$filterType]);
    }

    return $this->db->GetAssoc(<<<SQL
SELECT FID, FTitle, FFile, FCreated, FModified, CIID, CTitle, CPosition, FK_SID, ci.FK_CIID
FROM {$this->table_prefix}file f JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID
WHERE FK_SID = $this->site_id
AND FFile IS NOT NULL
    $sqlFilter
ORDER BY COALESCE(FModified, FCreated)
SQL
    );
  }

  /**
   * recursively read all content items from the database that contain downloads somewhere below them
   *
   * @param string $filterType the index of the filter inside the $this->listFilters array
   * @param string $filterText the filter text
   * @param array $items (optional) contains already read content items
   * @return array contains an unordered list of content items (CIID -> CTitle, CPosition, FK_CIID)
   */
  private function get_download_contentitems_list($filterType, $filterText, array &$items = array()) {
    // if the array is not pre-populated
    if (!$items) {
      $sqlFilter = "";
      if ($filterText) {
        $sqlFilter = "AND " . implode($filterText, $this->listFilters[$filterType]);
      }

      // read all content items which directly contain downloads
      $items = $this->db->GetAssoc(<<<SQL
SELECT DISTINCT CIID, CTitle, CPosition, ci.FK_CIID
FROM {$this->table_prefix}contentitem ci JOIN {$this->table_prefix}file f ON ci.CIID = f.FK_CIID
WHERE FK_SID = $this->site_id
AND FFile IS NOT NULL
      $sqlFilter
SQL
      );

      // call myself recursively only if there were content items which directly contain downloads
      if ($items) {
        $this->get_download_contentitems_list($filterType, $filterText, $items);
      }
    }
    // if the array is pre-populated
    else {
      // determine parent IDs which have not been read
      $parent_ids = array();
      foreach ($items as $item) {
        if ($item["FK_CIID"]) {
          $parent_ids[] = $item["FK_CIID"];
        }
      }
      $parent_ids = array_diff(array_unique($parent_ids), array_keys($items));

      // if there are parent IDs which have not been read
      if ($parent_ids) {
        // read all content items with the specified parent IDs
        $ids_sql = implode(", ", $parent_ids);
        $items += $this->db->GetAssoc(<<<SQL
SELECT DISTINCT CIID, CTitle, CPosition, FK_CIID
FROM {$this->table_prefix}contentitem
WHERE CIID IN ($ids_sql)
SQL
        );

        // then call myself recursively
        $this->get_download_contentitems_list($filterType, $filterText, $items);
      }
    }

    return $items;
  }

  /**
   * recursively appends all content items from $unordered with parent ID $parent_id to $ordered
   *
   * @param array $ordered contains the already ordered content items
   * @param array $unordered contains the still unordered content items
   * @param int $parent_id the parent ID of the content items that should be appended to $ordered
   */
  private static function append_contentitems(array &$ordered, array &$unordered, $parent_id = null) {
    // filter content items from $unordered by parent ID
    $children = array_filter($unordered, function($item) use ($parent_id) {
      return $item['FK_CIID'] == $parent_id ? $parent_id : 'null';
    });

    // sort content items by position
    uasort($children, array(__CLASS__, "contentitem_position_sort"));

    foreach ($children as $ciid => $ci) {
      // append each child to $ordered and remove from $unordered
      $ordered[$ciid] = $ci;
      unset($unordered[$ciid]);
      // append children of child $ci to $ordered
      self::append_contentitems($ordered, $unordered, $ciid);
    }
  }

  /**
   * comparison function, sorts content items by CPosition
   */
  private static function contentitem_position_sort($item1, $item2) {
    return $item1["CPosition"] - $item2["CPosition"];
  }

  /**
   * recursively read all content items from the database that contain downloads somewhere below them
   *
   * @param string $filterType the index of the filter inside the $this->listFilters array
   * @param string $filterText the filter text
   * @return array contains an ordered list (1, 1A, 1Aa, 1Ab, 1B, 2, 2A, 2B) of content items (CIID -> CTitle, CPosition, FK_CIID)
   */
  private function get_download_contentitems_ordered_list($filterType, $filterText) {
    $contentitems_unordered_list = $this->get_download_contentitems_list($filterType, $filterText);

    $contentitems_ordered_list = array();
    self::append_contentitems($contentitems_ordered_list, $contentitems_unordered_list);
    return $contentitems_ordered_list;
  }

  /**
   * converts a decentral file to a central file
   *
   * @param int $fid ID of the decentral file
   */
  private function convert_content($fid){
    global $_LANG;

    if ($this->_convertDecentral2Central($fid)) {
      $this->setMessage(Message::createSuccess($_LANG['mn_message_convertitem_success']));
    } else {
      //TODO print error message
    }
  }

  /**
   * delete a decentral file
   *
   * @param int $fid ID of the decentral file
   */
  private function delete_content($fid){
    global $_LANG;

    if (!$this->_readDecentralFileById(($fid))) {
      return;
    }

    $this->_deleteDecentralFile($fid);

    $this->setMessage(Message::createSuccess($_LANG['mn_message_deleteitem_success']));
  }

  /**
   * adds a content item block (contains multiple files) to the $contentitems array (for processing with a template)
   *
   * @param array $contentitems contains the existing content item blocks
   * @param string $contentitem_title the title for the content item block
   * @param array $files contains the files that should be displayed inside the content item block
   * @param int $result_page the page result number
   */
  private function add_contentitem(array &$contentitems, $contentitem_title, $files, $result_page) {
    global $_LANG, $_LANG2;

    $mn_url_page = "offset=$result_page";
    if ($files) {
      $files_items = array();
      foreach ($files as $fid => $file) {
        $files_items[] = array(
          "mn_title" => parseOutput($file["FTitle"]),
          "mn_file" => $this->_fileUrlForBackendUser($file['FFile']),
          "mn_filename" => parseOutput(mb_substr(mb_strrchr("../".$file["FFile"], "/"), 1)),
          'mn_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'mn'), strtotime(coalesce($file['FModified'], $file['FCreated']))),
          'mn_size' => formatFileSize(filesize("../{$file['FFile']}")),
          "mn_delete_link" => "index.php?action=mod_mediamanagement&amp;action2=node&amp;$mn_url_page&amp;did=".$fid,
          "mn_convert_link" => "index.php?action=mod_mediamanagement&amp;action2=node;convert&amp;fid=".$fid,
        );
      }

      $this->tpl->load_tpl("content_decentralfilelistfiles", "modules/ModuleMediaManagementNode_list_files.tpl");
      $this->tpl->parse_loop("content_decentralfilelistfiles", $files_items, "files");
      $mn_files = $this->tpl->parsereturn("content_decentralfilelistfiles", array());

      $contentitems[] = array(
        "mn_contentitem_link" => "index.php?action=files&amp;site=".$file["FK_SID"]."&amp;page=".$file["CIID"],
        "mn_contentitem_title" => $contentitem_title,
        "mn_files" => $mn_files,
      );
    }
  }

  /**
   * gets the "hierarchical title" of a content item (Products -> Hardware -> CPUs -> Intel Xeon X3220)
   *
   * @param int $ciid the ID of the content item
   * @param array $contentitem_list contains all content items
   * @return string the "hierarchical title" of a content item
   */
  private static function get_hierarchical_title($ciid, $contentitem_list)
  {
    $title = array();
    while ($ciid) {
      array_unshift($title, parseOutput($contentitem_list[$ciid]["CTitle"]));
      $ciid = $contentitem_list[$ciid]["FK_CIID"];
    }
    return implode(ConfigHelper::get('hierarchical_title_separator', 'mn'), $title);
  }

  /**
   * show a list containing all decentral files
   *
   * @return array contains backend content
   */
  private function show_list()
  {
    global $_LANG, $_LANG2;

    // initialize paging
    $mn_result_page = 1;
    $mn_results_per_page = (int)ConfigHelper::get('mn_results_per_page');
    if (isset($_GET["offset"]) && intval($_GET["offset"])) $mn_result_page = intval($_GET["offset"]);

    // initialize filtering
    $request = new Input(Input::SOURCE_REQUEST);
    $listFilterKeys = array_keys($this->listFilters);
    $mn_filter_type = coalesce($request->readString('filter_type'),
                               $this->session->read('mn_filter_type'),
                               $listFilterKeys[0]);
    if (!isset($this->listFilters[$mn_filter_type])) {
      $mn_filter_type = $listFilterKeys[0];
    }
    // If filter_text was sent with the request it has to be used, even if it's empty.
    if ($request->exists('filter_text')) {
      $mn_filter_text = $request->readString('filter_text');
    } else {
      $mn_filter_text = coalesce($this->session->read('mn_filter_text'), '');
    }
    $this->session->save('mn_filter_type', $mn_filter_type);
    $this->session->save('mn_filter_text', $mn_filter_text);
    $mn_url_filter = "filter_type=$mn_filter_type&amp;filter_text=" . urlencode($mn_filter_text);
    $mn_filtermessage = "";

    // read total decentral files
    $mn_item_count = $this->get_decentralfiles_count($mn_filter_type, $mn_filter_text);
    // handle paging
    $mn_page_navigation = "";
    $mn_offset = 0;
    if ($mn_item_count > $mn_results_per_page) {
      // If requested result page is greater than the possible amount of pages, set it to the highest possible result page
      if ($mn_result_page > ($mn_item_count / $mn_results_per_page)) {
        $mn_result_page = ceil($mn_item_count / $mn_results_per_page);
      }
      $mn_page_navigation = create_page_navigation($mn_item_count, $mn_result_page, 5, $mn_results_per_page, '', '', "index.php?action=mod_mediamanagement&amp;action2=node&amp;site=$this->site_id&amp;$mn_url_filter&amp;offset=");
      $mn_offset = (($mn_result_page - 1) * $mn_results_per_page);
    }
    $mn_url_page = "offset=$mn_result_page";

    // handle filtering
    // create filter dropdown
    $tmp_filter_type_select = '<select name="filter_type" class="form-control">';
    foreach (array_keys($this->listFilters) as $filter) {
      $tmp_filter_type_select .= '<option value="'.$filter.'"';
      if ($mn_filter_type == $filter) $tmp_filter_type_select .= ' selected="selected"';
      $tmp_filter_type_select .= '>'.$_LANG["mn_filter_type_$filter"].'</option>';
    }
    $mn_filter_type_select = $tmp_filter_type_select."</select>";

    // read content items and decentral files from the database
    $contentitems_list = $this->get_download_contentitems_ordered_list($mn_filter_type, $mn_filter_text);
    $files_list = $this->get_decentralfiles($mn_filter_type, $mn_filter_text);

    $contentitems_items = array();
    if ($contentitems_list) {
      // do manual paging (no SQL LIMIT possible)
      $current_offset = 0;
      $current_row_count = 0;
      // add content items to $contentitems_items
      $titleSeparator = ConfigHelper::get('hierarchical_title_separator', 'mn');
      foreach ($contentitems_list as $ciid => $ci) {
        // filter decentral files by assigned content item
        $files = array_filter($files_list, function($item) use ($ciid) {
          return $item['CIID'] == $ciid;
        });
        $files_count = count($files);

        // if we have not yet reached the beginning offset,
        // we have to cut items away from the beginning of the array
        if ($current_offset < $mn_offset) {
          $files = array_slice($files, $mn_offset - $current_offset, $mn_results_per_page, true);
        }
        // if the amount of files in the array would exceed the given row count
        // we have to cut items away from the end of the array
        if ($current_row_count + count($files) > $mn_results_per_page) {
          $files = array_slice($files, 0, $mn_results_per_page - $current_row_count, true);
        }

        $this->add_contentitem($contentitems_items, $this->_getHierarchicalTitle($ciid, $titleSeparator), $files, $mn_result_page);

        $current_offset += $files_count;
        $current_row_count += count($files);

        // end the loop if we are finished outputting result rows
        if ($current_row_count >= $mn_results_per_page) break;
      }
    }
    else {
      if ($mn_filter_text) {
        $mn_filtermessage = $_LANG["mn_filtermessage_empty"];
      }
      else {
        $this->setMessage(Message::createFailure($_LANG['mn_message_no_decentralfile']));
      }
    }

    $maxLength = ConfigHelper::get('m_mod_filtertext_maxlength');
    $aftertext = ConfigHelper::get('m_mod_filtertext_aftertext');
    $shortFilterText = StringHelper::setText($mn_filter_text)
                       ->purge()
                       ->truncate($maxLength, $aftertext)
                       ->getText();

    // parse list template
    $this->tpl->load_tpl("content_decentralfilelist", "modules/ModuleMediaManagementNode_list.tpl");
    $this->tpl->parse_if('content_decentralfilelist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('mn'));
    $this->tpl->parse_if("content_decentralfilelist", "filter_set", $mn_filter_text);
    $this->tpl->parse_if("content_decentralfilelist", "filter_set", $mn_filter_text);
    $this->tpl->parse_if("content_decentralfilelist", "filtermessage", $mn_filtermessage, array("mn_filtermessage" => $mn_filtermessage));
    $this->tpl->parse_loop("content_decentralfilelist", $contentitems_items, "contentitems");
    $this->tpl->parse_if("content_decentralfilelist", "more_pages", $mn_page_navigation, array(
      "mn_page_navigation" => $mn_page_navigation,
    ));
    $mn_content = $this->tpl->parsereturn("content_decentralfilelist", array_merge($_LANG2["mn"], array(
      "mn_action" => "index.php?action=mod_mediamanagement&amp;action2=node&amp;$mn_url_filter&amp;$mn_url_page",
      "mn_site_selection" => $this->_parseModuleSiteSelection('mediamanagement', $_LANG["mn_site_label"], 'node'),
      "mn_filter_active_label" => $mn_filter_text ? sprintf($_LANG["mn_filter_active_label"], $_LANG["mn_filter_type_$mn_filter_type"], parseOutput($mn_filter_text), parseOutput($shortFilterText)) : $_LANG["mn_filter_inactive_label"],
      "mn_filter_type_select" => $mn_filter_type_select,
      "mn_filter_text" => $mn_filter_text,
      "mn_list_label" => $_LANG["mn_function_list_label"],
      "mn_list_label2" => $_LANG["mn_function_list_label2"],
    )));

    return array(
        'content' => $mn_content,
    );
  }
}
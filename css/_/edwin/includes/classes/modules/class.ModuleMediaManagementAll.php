<?php

/**
 * MediaManagementAll Module Class
 *
 * $LastChangedDate: 2018-12-14 10:26:08 +0100 (Fr, 14 Dez 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2010 Q2E GmbH
 */
class ModuleMediaManagementAll extends Module {

  /**
   * Type id of central files
   * @var int
   */
  const CENTRAL_FILE_TYPE = 0;

  /**
   * Type id of content item download area files
   * @var int
   */
  const CONTENT_ITEM_DL_AREA_FILE_TYPE = 1;

   /**
   * Type id of decentral files
   * @var int
   */
  const DECENTRAL_FILE_TYPE = 2;

  /**
   * contains the available filter criteria for central files including SQL WHERE clauses
   * the format is "name" => ( "DBColumn1 = '", "' OR DBColumn2 LIKE '%", "%'")
   * the filter expression is inserted between the array elements
   * @var array
   */
  private $listFiltersCentralFiles = array(
    "title" => array("CFTitle LIKE '%", "%'"),
    "filename" => array("CFFile LIKE '%", "%'"),
  );

  /**
   * contains the available filter criteria for decentral files including SQL WHERE clauses
   * the format is "name" => ( "DBColumn1 = '", "' OR DBColumn2 LIKE '%", "%'")
   * the filter expression is inserted between the array elements
   * @var array
   */
  private $listFiltersDecentralFiles = array(
    "title" => array("FTitle LIKE '%", "%'"),
    "filename" => array("FFile LIKE '%", "%'"),
  );

  /**
   * contains the available filter criteria for download area files including SQL WHERE clauses
   * the format is "name" => ( "DBColumn1 = '", "' OR DBColumn2 LIKE '%", "%'")
   * the filter expression is inserted between the array elements
   * @var array
   */
  private $listFiltersDownloadAreaFiles = array(
    "title" => array("DFTitle LIKE '%", "%'"),
    "filename" => array("DFFile LIKE '%", "%'"),
  );

  /**
   * contains the available sort criteria including SQL ORDER BY clauses
   * the format is "name" => ( "asc" => "DBColumn ASC", "desc" => "DBColumn DESC" )
   * the order of "asc" and "desc" inside the array specifies the preferred sort order
   * @var array
   */
  private $listOrders = array(
    "title" => array("asc" => "FTitle ASC", "desc" => "FTitle DESC"),
    "filename" => array("asc" => "FFile ASC", "desc" => "FFile DESC"),
    "date" => array("desc" => "COALESCE(FModified, FCreated) DESC",
                    "asc" => "COALESCE(FModified, FCreated) ASC"),
  );

  /**
   * @see ModuleMediaManagementAll::_issuu()
   * @var Issuu
   */
  private $_issuu;

  public function show_innercontent () {
    if (isset($_GET["convert"])) {
      $this->convert_content((int)$_GET["fid"], (int)$_GET["ftype"]);
    }
    if (isset($_GET["delete"])) {
      if (isset($_GET["areaid"]) && isset($_GET["pageid"])) {
        // delete ContentItemDLArea file
        $this->delete_content((int)$_GET["delete"], (int)$_GET["ftype"], (int)$_GET["areaid"], (int)$_GET["pageid"]);
      } else {
        $this->delete_content((int)$_GET["delete"], (int)$_GET["ftype"]);
      }
    }

    return $this->show_list();
  }

  /**
   * @return Issuu
   */
  protected function _issuu()
  {
    if ($this->_issuu === null) {
      $this->_issuu = new Issuu($this->db, $this->table_prefix, $this->site_id, 'ml');
    }

    return $this->_issuu;
  }

  /**
   * show a list containing all decentral files
   *
   * @return array contains backend content
   */
  private function show_list() {
    global $_LANG, $_LANG2;

    // active filter label
    $ml_filter_active_label = null;

    // initialize paging
    $ml_result_page = 1;
    $ml_results_per_page = (int)ConfigHelper::get('ml_results_per_page');
    if (isset($_GET["offset"]) && intval($_GET["offset"])) $ml_result_page = intval($_GET["offset"]);

    // initialize ordering
    $listOrderKeys = array_keys($this->listOrders);
    $ml_order = isset($_GET["order"], $this->listOrders[$_GET["order"]]) ? $_GET["order"] : $listOrderKeys[0];
    $ml_asc = isset($_GET["asc"]) ? intval($_GET["asc"]) : 1;
    $ml_url_order = "order=$ml_order&amp;asc=$ml_asc";

    // initialize filtering
    $request = new Input(Input::SOURCE_REQUEST);
    $listFilterKeys = array_keys($this->listFiltersCentralFiles);
    $ml_filter_type = coalesce($request->readString('filter_type'),
                               $this->session->read('ml_filter_type'),
                               $listFilterKeys[0]);
    if (!isset($this->listFiltersCentralFiles[$ml_filter_type])) {
      $ml_filter_type = $listFilterKeys[0];
    }

    // If filter_text was sent with the request it has to be used, even if it's empty.
    if ($request->exists('filter_text')) {
      $ml_filter_text = $request->readString('filter_text');
    } else {
      $ml_filter_text = coalesce($this->session->read('ml_filter_text'), '');
    }

    $this->session->save('ml_filter_type', $ml_filter_type);
    $this->session->save('ml_filter_text', $ml_filter_text);
    $ml_url_filter = "filter_type=$ml_filter_type&amp;filter_text=" . urlencode($ml_filter_text);
    $ml_filtermessage = "";

    // read total number of all files
    $ml_item_count = $this->get_files_count($ml_filter_type, $ml_filter_text, $this->site_id);
    // handle paging
    $ml_page_navigation = "";
    $ml_offset = 0;
    if ($ml_item_count > $ml_results_per_page) {
      // If requested result page is greater than the possible amount of pages, set it to the highest possible result page
      if ($ml_result_page > ($ml_item_count / $ml_results_per_page)) {
        $ml_result_page = ceil($ml_item_count / $ml_results_per_page);
      }
      $ml_page_navigation = create_page_navigation($ml_item_count, $ml_result_page, 5, $ml_results_per_page, '', '', "index.php?action=mod_mediamanagement&amp;action2=all&amp;site=$this->site_id&amp;$ml_url_order&amp;$ml_url_filter&amp;offset=");
      $ml_offset = (($ml_result_page - 1) * $ml_results_per_page);
    }
    $ml_url_page = "offset=$ml_result_page";

    // handle ordering
    // column title
    $sortPreference = array_keys($this->listOrders["title"]);
    $ml_list_title_sort = $ml_order == "title" ? ($ml_asc ? "asc" : "desc") : "none";
    $ml_list_title_sort_next = $ml_order == "title" ? ($ml_asc ? "desc" : "asc") : $sortPreference[0];
    $ml_list_title_link = "index.php?action=mod_mediamanagement&amp;action2=all&amp;site=$this->site_id&amp;$ml_url_page&amp;$ml_url_filter&amp;order=title&amp;asc=".($ml_order == "title" ? ($ml_asc ? 0 : 1) : $sortPreference[0] == "asc");
    // column filename
    $sortPreference = array_keys($this->listOrders["filename"]);
    $ml_list_filename_sort = $ml_order == "filename" ? ($ml_asc ? "asc" : "desc") : "none";
    $ml_list_filename_sort_next = $ml_order == "filename" ? ($ml_asc ? "desc" : "asc") : $sortPreference[0];
    $ml_list_filename_link = "index.php?action=mod_mediamanagement&amp;action2=all&amp;site=$this->site_id&amp;$ml_url_page&amp;$ml_url_filter&amp;order=filename&amp;asc=".($ml_order == "filename" ? ($ml_asc ? 0 : 1) : intval($sortPreference[0] == "asc"));
    // column date
    $sortPreference = array_keys($this->listOrders['date']);
    $ml_list_date_sort = $ml_order == 'date' ? ($ml_asc ? 'asc' : 'desc') : 'none';
    $ml_list_date_sort_next = $ml_order == 'date' ? ($ml_asc ? 'desc' : 'asc') : $sortPreference[0];
    $ml_list_date_link = "index.php?action=mod_mediamanagement&amp;action2=all&amp;site=$this->site_id&amp;$ml_url_page&amp;$ml_url_filter&amp;order=date&amp;asc=".($ml_order == 'date' ? ($ml_asc ? 0 : 1) : intval($sortPreference[0] == 'asc'));

    // handle filtering
    // create filter dropdown
    $tmp_filter_type_select = '<select name="filter_type" class="form-control">';
    foreach (array_keys($this->listFiltersCentralFiles) as $filter) {
      $tmp_filter_type_select .= '<option value="'.$filter.'"';
      if ($ml_filter_type == $filter) $tmp_filter_type_select .= ' selected="selected"';
      $tmp_filter_type_select .= '>'.$_LANG["ml_filter_type_$filter"].'</option>';
    }
    $ml_filter_type_select = $tmp_filter_type_select."</select>";

    // read files from the database
    $dbfiles = $this->get_files($ml_order, $ml_asc, $ml_filter_type, $ml_filter_text, $this->site_id, $ml_results_per_page, $ml_offset);

    $file_items = array();
    if ($dbfiles) {
      $row = 0;
      foreach ($dbfiles as $fid => $file) {
        $delete_params = '';
        $ml_content_link = '';
        $ml_convert_link = '';
        if ($file['FType'] == self::DECENTRAL_FILE_TYPE) {
          $ml_convert_link = 'index.php?action=mod_mediamanagement&amp;action2=all;&amp;convert&amp;fid='.$file["FID"].'&amp;ftype='.$file["FType"];
          $ml_content_link = 'index.php?action=files&amp;site='.$file['FK_SID'].'&amp;page='.$file['FK_CIID'];
        } else if ($file['FType'] == self::CONTENT_ITEM_DL_AREA_FILE_TYPE) {
          $delete_params = '&amp;areaid='.$file['FK_DAID'].'&amp;pageid='.$file['FK_CIID'];
          $ml_convert_link = 'index.php?action=mod_mediamanagement&amp;action2=all;&amp;convert&amp;fid='.$file["FID"].'&amp;ftype='.$file["FType"];
          $ml_content_link = 'index.php?action=content&amp;site='.$file['FK_SID'].'&amp;page='.$file['FK_CIID'].'&amp;area='.$file['FK_DAID'].'&amp;scrollToAnchor=a_areas';
        }
        // fill array for list template
        $file_items[$row] = array(
          'ml_fid' => $file['FID'],
          'ml_title' => parseOutput($file['FTitle']),
          'ml_file' => $this->_fileUrlForBackendUser($file['FFile']),
          'ml_filename' => parseOutput(mb_substr(mb_strrchr("../".$file['FFile'], "/"), 1)),
          'ml_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ml'), strtotime(coalesce($file['FModified'], $file['FCreated']))),
          'ml_size' => formatFileSize(filesize("../{$file['FFile']}")),
          'ml_delete_link' => 'index.php?action=mod_mediamanagement&amp;action2=all&amp;'.$ml_url_page.'&amp;'.$ml_url_order.'&amp;delete='.$file["FID"].'&amp;ftype='.$file["FType"].$delete_params,
          'ml_is_central_file' => ($file['FType'] == self::CENTRAL_FILE_TYPE) ? true : false,
          'row' => $row,  //used to identify if statements!
          'ml_content_link' => $ml_content_link,
          'ml_convert_link' => $ml_convert_link,
        );
        $file_item_locations[$row] = ($file['FType'] == self::CENTRAL_FILE_TYPE) ? $this->get_file_locations($file["FID"]) : array();
        $row ++;
      }
    }
    else {
      if ($ml_filter_text) {
        $ml_filtermessage = $_LANG["ml_filtermessage_empty"];
      }
      else {
        $this->setMessage(Message::createFailure($_LANG['ml_message_no_files']));
      }
    }

    // parse list template
    $this->tpl->load_tpl("content_filelist", "modules/ModuleMediaManagementAll_list.tpl");
    $this->tpl->parse_loop("content_filelist", $file_items, "files_items");
    $row = 0;
    // parse if statements of file items loop
    foreach ($file_items as $id => $file_item) {

      // parse file locations of central files
      $file_locations = array();
      foreach ($file_item_locations[$id] as $file_location) {
        $ml_file_location_link = '#';
        if ($file_location['FType'] == self::CONTENT_ITEM_DL_AREA_FILE_TYPE) {
          $ml_file_location_link = 'index.php?action=content&amp;site='.$file_location['FK_SID'].'&amp;page='.$file_location['FK_CIID'].'&amp;area='.$file_location['FK_DAID'].'&amp;scrollToAnchor=a_areas';
        } else if ($file_location['FType'] == self::DECENTRAL_FILE_TYPE) {
          $ml_file_location_link = 'index.php?action=files&amp;site='.$file_location['FK_SID'].'&amp;page='.$file_location['FK_CIID'];
        }
        $file_locations[] = array(
          'ml_ci_title' => $file_location['CTitle'],
          'ml_file_location_link' => $ml_file_location_link,
        );
      }

      $this->tpl->parse_if('content_filelist', "show_content_link_{$row}", !$file_item['ml_is_central_file']);
      $this->tpl->parse_if('content_filelist', "show_usage_{$row}", $file_item['ml_is_central_file'] && !empty($file_locations), array(

      ));
      $this->tpl->parse_if('content_filelist', "central_icon_{$row}", $file_item['ml_is_central_file']);
      $this->tpl->parse_if('content_filelist', "convert_icon_{$row}", !$file_item['ml_is_central_file']);

      $this->tpl->parse_loop('content_filelist', $file_locations, "file_locations_{$row}");
      $this->tpl->parse_if('content_filelist', "show_link_box_{$row}", !empty($file_locations));

      $row ++;
    }

    $maxLength = ConfigHelper::get('m_mod_filtertext_maxlength');
    $aftertext = ConfigHelper::get('m_mod_filtertext_aftertext');
    $shortFilterText = StringHelper::setText($ml_filter_text)
                       ->purge()
                       ->truncate($maxLength, $aftertext)
                       ->getText();
    $this->tpl->parse_if('content_filelist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ml'));
    $this->tpl->parse_if('content_filelist', 'filter_set', ($ml_filter_text));
    $this->tpl->parse_if('content_filelist', 'filter_set', ($ml_filter_text));
    $this->tpl->parse_if('content_filelist', 'filtermessage', $ml_filtermessage, array('ml_filtermessage' => $ml_filtermessage));
    $this->tpl->parse_if('content_filelist', 'more_pages', $ml_page_navigation, array(
      'ml_page_navigation' => $ml_page_navigation,
    ));
    $ml_content = $this->tpl->parsereturn('content_filelist', array_merge($_LANG2['ml'], array(
      'ml_action' => "index.php?action=mod_mediamanagement&amp;action2=all&amp;$ml_url_filter&amp;$ml_url_order&amp;$ml_url_page",
      'ml_site_selection' => $this->_parseModuleSiteSelection('mediamanagement', $_LANG["ml_site_label"], 'all'),
      'ml_filter_active_label' => $ml_filter_text ? sprintf($_LANG["ml_filter_active_label"], $_LANG["ml_filter_type_$ml_filter_type"], parseOutput($ml_filter_text), parseOutput($shortFilterText)) : $_LANG["ml_filter_inactive_label"],
      'ml_filter_type_select' => $ml_filter_type_select,
      'ml_filter_text' => $ml_filter_text,
      'ml_list_label' => $_LANG['ml_function_list_label'],
      'ml_list_label2' => $_LANG['ml_function_list_label2'],
      'ml_list_title_sort' => $ml_list_title_sort,
      'ml_list_title_sort_next' => $ml_list_title_sort_next,
      'ml_list_title_link' => $ml_list_title_link,
      'ml_list_filename_sort' => $ml_list_filename_sort,
      'ml_list_filename_sort_next' => $ml_list_filename_sort_next,
      'ml_list_filename_link' => $ml_list_filename_link,
      'ml_list_date_sort' => $ml_list_date_sort,
      'ml_list_date_sort_next' => $ml_list_date_sort_next,
      'ml_list_date_link' => $ml_list_date_link,
      'ml_sort_link_info' => $_LANG['ml_sort_link_info'],
      'ml_download_details' => $_LANG['ml_download_details'],
      'ml_centralfile_local_label' => $_LANG['ml_centralfile_local_label'],
      'ml_convert_label' => $_LANG['ml_convert_label'],
      'ml_ci_title_label' => $_LANG['ml_ci_title_label'],
      'ml_show_usage_label' => $_LANG['ml_show_usage_label'],
    )));

    return array(
        'content' => $ml_content,
    );
  }

  /**
   * determine the total amount of files from the database
   *
   * @param string $filterType the index of the filter inside the $this->listFilters array
   * @param string $filterText the filter text
   * @param int $filterSite site id of site to filter
   * @return integer specifies the total amount of files
   */
  private function get_files_count($filterType, $filterText, $filterSite) {

    $sqlFiltersCentralFiles = '';
    if ($filterText) {
      $sqlFiltersCentralFiles = ' WHERE '. implode($filterText, $this->listFiltersCentralFiles[$filterType]) . ' ';
    }
    if ($filterSite > 0) {
      if ($sqlFiltersCentralFiles) {
        $sqlFiltersCentralFiles .= ' AND FK_SID='.$filterSite.' ';
      } else {
        $sqlFiltersCentralFiles .= ' WHERE FK_SID='.$filterSite.' ';
      }
    }

    $sqlFiltersDecentralFiles = '';
    if ($filterText) {
      $sqlFiltersDecentralFiles = ' AND '. implode($filterText, $this->listFiltersDecentralFiles[$filterType]) . ' ';
    }
    if ($filterSite > 0) {
      $sqlFiltersDecentralFiles .= ' AND ci.FK_SID='.$filterSite.' ';
    }

    $sqlFiltersDownloadAreaFiles = '';
    if ($filterText) {
      $sqlFiltersDownloadAreaFiles = ' AND '. implode($filterText, $this->listFiltersDownloadAreaFiles[$filterType]) . ' ';
    }
    if ($filterSite > 0) {
      $sqlFiltersDownloadAreaFiles .= ' AND ci.FK_SID='.$filterSite.' ';
    }

    $sql = "SELECT SUM(files_count) AS files_count FROM (
              (
                SELECT COUNT(f.FID) AS files_count
                FROM {$this->table_prefix}file f
                JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID
                WHERE f.FFile IS NOT NULL
                $sqlFiltersDecentralFiles
              )
              UNION ALL
              (
                SELECT COUNT( CFID ) AS files_count
                FROM {$this->table_prefix}centralfile
                $sqlFiltersCentralFiles
              )
              UNION ALL
              (
                SELECT COUNT( cidlaf.DFID ) AS files_count
                FROM {$this->table_prefix}contentitem_dl_area_file cidlaf
                JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID
                JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID
                WHERE cidlaf.DFFile IS NOT NULL
                $sqlFiltersDownloadAreaFiles
              )
            ) AS total_count";

    // get count of selected files
    return $this->db->GetOne($sql);
  }

  /**
   * read all files from the database
   *
   * @param string $order the index of the sort order inside the $this->listOrders array
   * @param int $asc true if the sort order is ascending, false otherwise
   * @param string $filterType the index of the filter inside the $this->listFilters array
   * @param string $filterText the filter text
   * @param int $filterSite filter with this site id
   * @param int $rowCount the maximum number of rows to return
   * @param int $offset the row offset at which to start returning rows (first row has offset 0)
   * @return array contains all files (central files, "content item download" files and decentral files)
   *         and stores these columns: FTitle, FID, FFile, FCreated, FModified, FType, FPosition, FK_CIID, FK_SID, FK_DAID
   *         FType: 0 -> central file; 1 -> content item download file; 2 -> decentral file;
   */
  private function get_files($order, $asc, $filterType, $filterText, $filterSite, $rowCount, $offset) {

    $sqlFiltersCentralFiles = '';
    if ($filterText) {
      $sqlFiltersCentralFiles = ' WHERE '. implode($filterText, $this->listFiltersCentralFiles[$filterType]) . ' ';
    }
    if ($filterSite > 0) {
      if ($sqlFiltersCentralFiles) {
        $sqlFiltersCentralFiles .= ' AND FK_SID='.$filterSite.' ';
      } else {
        $sqlFiltersCentralFiles .= ' WHERE FK_SID='.$filterSite.' ';
      }
    }

    $sqlFiltersDecentralFiles = '';
    if ($filterText) {
      $sqlFiltersDecentralFiles = ' AND '. implode($filterText, $this->listFiltersDecentralFiles[$filterType]) . ' ';
    }
    if ($filterSite > 0) {
      $sqlFiltersDecentralFiles .= ' AND ci.FK_SID='.$filterSite.' ';
    }

    $sqlFiltersDownloadAreaFiles = '';
    if ($filterText) {
      $sqlFiltersDownloadAreaFiles = ' AND '. implode($filterText, $this->listFiltersDownloadAreaFiles[$filterType]) . ' ';
    }
    if ($filterSite > 0) {
      $sqlFiltersDownloadAreaFiles .= ' AND ci.FK_SID='.$filterSite.' ';
    }

    $sqlOrderBy = 'ORDER BY ' . $this->listOrders[$order][$asc ? 'asc' : 'desc'] . ' ';

    $sqlLimit = '';
    if ($rowCount >= 0) {
      $sqlLimit = 'LIMIT ';
      if ($offset > 0) $sqlLimit .= $offset . ', ';
      $sqlLimit .= $rowCount . ' ';
    }

    $sql = "(
              SELECT  f.FTitle AS FTitle, f.FFile AS FFile, f.FCreated AS FCreated, f.FModified AS FModified, f.FPosition AS FPosition, f.FK_CIID AS FK_CIID, ".self::DECENTRAL_FILE_TYPE." AS FType, f.FID AS FID, ci.FK_SID AS FK_SID, NULL AS FK_DAID
              FROM {$this->table_prefix}file f
              JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID
              WHERE f.FFile IS NOT NULL
              $sqlFiltersDecentralFiles
            )
            UNION ALL
            (
              SELECT CFTitle, CFFile, CFCreated, CFModified , NULL, NULL, ".self::CENTRAL_FILE_TYPE.", CFID, FK_SID, NULL
              FROM {$this->table_prefix}centralfile
              $sqlFiltersCentralFiles
            )
            UNION ALL
            (
              SELECT  cidlaf.DFTitle, cidlaf.DFFile, cidlaf.DFCreated, cidlaf.DFModified, cidlaf.DFPosition, ci.CIID, ".self::CONTENT_ITEM_DL_AREA_FILE_TYPE.", cidlaf.DFID, ci.FK_SID, cidlaf.FK_DAID
              FROM {$this->table_prefix}contentitem_dl_area_file cidlaf
              JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID
              JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID
              WHERE cidlaf.DFFile IS NOT NULL
              $sqlFiltersDownloadAreaFiles
            )
            $sqlOrderBy
            $sqlLimit
            ";

    // get query result of selected files
    return $this->db->GetAssoc($sql);
  }

  /**
   * Returns file information of files, that are associated with the given file id
   * @param int $fid
   *        File id of central file
   * @return array stores these columns: FK_CIID, FK_SID FK_DAID, CTitle,
   *         FType: 1 -> content item download file; 2 -> decentral file;
   */
  private function get_file_locations($fid) {
    $sql = "(
              SELECT  ci.CIID AS FK_CIID, ci.FK_SID AS FK_SID, cidlaf.FK_DAID AS FK_DAID, ci.CTitle AS CTitle, 1 AS FType
              FROM {$this->table_prefix}contentitem_dl_area_file cidlaf
              JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID
              JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID
              WHERE cidlaf.DFFile IS NULL
              AND FK_CFID = {$fid}
            )
            UNION ALL
            (
              SELECT f.FK_CIID, ci.FK_SID AS FK_SID, NULL, ci.CTitle, 2
              FROM {$this->table_prefix}file f
              JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID
              WHERE f.FFile IS NULL
              AND FK_CFID = {$fid}
            )";
    return $this->db->GetAssoc($sql);
  }

  /**
   * Converts a file to a central file
   *
   * @param int $fid
   *        File id of file to convert
   * @param int $ftype
   *        Type of file; 0 -> central file; 1 -> content item download file; 2 -> decentral file;
   */
  private function convert_content($fid, $ftype) {
    global $_LANG;

    if ($ftype == self::DECENTRAL_FILE_TYPE) {
      if ($this->_convertDecentral2Central($fid)) {
        $this->setMessage(Message::createSuccess($_LANG['ml_message_convertitem_success']));
      } else {
        //TODO print error message
      }
    } else if ($ftype == self::CONTENT_ITEM_DL_AREA_FILE_TYPE) {
        if ($this->_convertContentItemDLAreaFile2Central($fid)) {
          $this->setMessage(Message::createSuccess($_LANG['ml_message_convertitem_success']));
        } else {
          //TODO print error message
        }
    }
  }

  /**
   * Deletes a file
   *
   * @param int $fid
   *        File id of file to delete
   * @param int $ftype
   *        Type of file; 0 -> central file; 1 -> content item download file; 2 -> decentral file;
   * @param int $areaId
   *        download area id of file (FK_DAID)
   * @param int $pageId
   *        content item id
   */
  private function delete_content($fid, $ftype, $areaId=0, $pageId=0){
    global $_LANG;

    $fid = (int) $fid;
    if (!$fid) {
      return;
    }

    if ($ftype == self::DECENTRAL_FILE_TYPE && $this->_readDecentralFileById($fid)) {
      $this->_deleteDecentralFile($fid);
    }
    else if ($ftype == self::CENTRAL_FILE_TYPE && $this->_readCentralFileById($fid)) {
      $sql = " SELECT * "
           . " FROM {$this->table_prefix}centralfile cf "
           . " LEFT JOIN {$this->table_prefix}issuu_document id "
           . "        ON cf.FK_IDID_IssuuDocument = id.IDID "
           . " WHERE CFID = $fid ";
      $row = $this->db->GetRow($sql);

      if ($row['IDName']) {
        $this->_deleteCentralFileIssuuDocument($row['IDName']);
      }

      $this->_deleteCentralFile($fid);
    }
    else if ($ftype == self::CONTENT_ITEM_DL_AREA_FILE_TYPE && $this->_readContentItemDLAreaFileById($fid)) {
      $this->_deleteContentItemDLAreaFile($fid, $areaId, $pageId);
    }
    else {
      return;
    }

    $this->setMessage(Message::createSuccess($_LANG['ml_message_deleteitem_success']));
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
  private function _deleteCentralFileIssuuDocument($name)
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

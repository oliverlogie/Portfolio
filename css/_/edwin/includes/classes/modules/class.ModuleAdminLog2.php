<?php

/**
 * $LastChangedDate: $
 * $LastChangedBy:  $
 *
 * @package EDWIN Backend
 * @author Koppensteiner Raphael
 * @copyright (c) 2018 Q2E GmbH
 */
class ModuleAdminLog2 extends Module
{
  protected $_prefix = 'ad_l2';

  public function show_innercontent()
  {
    $action = isset($this->action[0]) ? $this->action[0] : null;

    if ($action == 'ad_l2_details') {
      return $this->_showContentDetails();
    }

    if (isset($_POST['process_reset'])) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }

    return $this->_showContentList();
  }

  protected function _initGrid()
  {
    $get = new Input(Input::SOURCE_GET);
    $gridSql = " SELECT DateTime, Level, Identifier, DataType, User, ID "
             . " FROM {$this->table_prefix}log_simple "
             . " WHERE 1 ";

    $queryFields[1] = array('type' => 'text', 'value' => 'DateTime', 'lazy' => true);
    $queryFields[2] = array('type' => 'selective', 'value' => 'Level');
    $queryFields[3] = array('type' => 'selective', 'value' => 'Identifier');
    $queryFields[4] = array('type' => 'text', 'value' => 'DataType', 'lazy' => true);
    $queryFields[5] = array('type' => 'text', 'value' => 'User', 'lazy' => true);

    $filterFields = $queryFields;
    $filterTypes = array(
      'DateTime'   => 'text',
      'Level'      => 'selective',
      'Identifier' => 'selective',
      'DataType'   => 'text',
      'User'       => 'text',
    );

    $ordersValuelist = array(
      1 => array('field' => 'DateTime',  'order' => 'ASC'),
      2 => array('field' => 'DateTime',  'order' => 'DESC'),
      3 => array('field' => 'Level', 'order' => 'ASC'),
      4 => array('field' => 'Level', 'order' => 'DESC'),
      5 => array('field' => 'Identifier', 'order' => 'ASC'),
      6 => array('field' => 'Identifier', 'order' => 'DESC'),
      7 => array('field' => 'DataType', 'order' => 'ASC'),
      8 => array('field' => 'DataType', 'order' => 'DESC'),
      9 => array('field' => 'User', 'order' => 'ASC'),
      10 => array('field' => 'User', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;
    $presetOrders = array(1 => 2);

    $page = ($get->exists('ad_l2_page')) ? $get->readInt('ad_l2_page') : ($this->session->read('ad_l2_page') ? $this->session->read('ad_l2_page') : 1);
    $this->session->save('ad_l2_page', $page);

    $sql = " SELECT DISTINCT(Level), Level "
         . " FROM {$this->table_prefix}log_simple "
         . " GROUP BY Level ";
    $level = (array)$this->db->GetAssoc($sql);
    foreach ($level as $id => $value) {
        $level[$id] = array('label' => $value);
    }
    $sql = " SELECT DISTINCT(Identifier), Identifier "
         . " FROM {$this->table_prefix}log_simple "
         . " GROUP BY Identifier ";
    $identifier = (array)$this->db->GetAssoc($sql);
    foreach ($identifier as $id => $value) {
        $identifier[$id] = array('label' => $value);
    }

    $selectiveData = array(
      'Level'      => $level,
      'Identifier' => $identifier,
    );

    $prefix = array('config'  => $this->_prefix,
                    'lang'    => $this->_prefix,
                    'session' => $this->_prefix,
                    'tpl'     => $this->_prefix);

    $grid = new DataGrid($this->db, $this->session, $prefix);
    $grid->setSelectiveData($selectiveData);
    $grid->load($gridSql, $queryFields, $filterFields, $filterTypes,
                $orders, $page, false, null,
                $presetOrders);

    return $grid;
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }

  private function _showContentList()
  {
    global $_LANG, $_LANG2;

    $data = $this->_grid()->get_result();
    if (is_array($data)) {
      $i = 1;
      foreach ($data as $key => &$value) {
        $row = $this->_grid()->get_grid_data($key);
        $value['ad_l2_user'] = $row['User'];
        $value['ad_l2_date'] = $row['DateTime'];
        $value['ad_l2_level'] = $row['Level'];
        $value['ad_l2_identifier'] = $row['Identifier'];
        $value['ad_l2_data_type'] = $row['DataType'];
        $id = $row['ID'];
        $value['ad_l2_details_link'] = $this->_parseUrl(null, array('action3' => 'log2;ad_l2_details', 'item' => $id));
        $data[$key]['ad_l2_row_bg']      = ( $i++ %2 ) ? 'even' : 'odd';
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
    $tplName = 'content';
    $this->tpl->load_tpl($tplName, 'modules/ModuleAdminLog2.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(),
        $this->_getMessageTemplateArray('ad_l2'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');
    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl(null, array('action3'=>'log2'))), array (
        'ad_l2_action'                => $this->_parseUrl(null, array('action3'=>'log2')),
        'ad_l2_count_all'             => $this->_grid()->get_quantity_total_rows(),
        'ad_l2_count_current'         => $currentRows,
        'ad_l2_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3'=>'log2')) . '&amp;ad_l2_page=','_bottom'),
        'ad_l2_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
        'ad_l2_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3'=>'log2')) . '&amp;ad_l2_page=','_top'),
        'ad_l2_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['ad_l2']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  private function _showContentDetails()
  {
    global $_LANG, $_LANG2;

    $get = new Input(Input::SOURCE_GET);
    $itemId = $get->readInt('item');

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}log_simple "
         . " WHERE ID = $itemId";
    $row = $this->db->GetRow($sql);

    $this->tpl->load_tpl('content', 'modules/ModuleAdminLog2_details.tpl');
    $this->tpl->parse_vars('content', array_merge(array(
      'ad_l2_id_label'         => $_LANG['ad_l2_ID_label'],
      'ad_l2_level_label'      => $_LANG['ad_l2_Level_label'],
      'ad_l2_dateTime_label'   => $_LANG['ad_l2_DateTime_label'],
      'ad_l2_identifier_label' => $_LANG['ad_l2_Identifier_label'],
      'ad_l2_user_label'       => $_LANG['ad_l2_User_label'],
      'ad_l2_data_label'       => $_LANG['ad_l2_Data_label'],
      'ad_l2_dataType_label'   => $_LANG['ad_l2_DataType_label'],
      'ad_l2_id'               => $row['ID'],
      'ad_l2_level'            => parseOutput($row['Level']),
      'ad_l2_dateTime'         => $row['DateTime'],
      'ad_l2_identifier'       => parseOutput($row['Identifier']),
      'ad_l2_user'             => parseOutput($row['User']),
      'ad_l2_data'             => parseOutput($row['Data']),
      'ad_l2_dataType'         => parseOutput($row['DataType']),
    ), $_LANG2['ad_l2']));

    return array(
      'content'      => $this->tpl->parsereturn('content'),
      'content_left' => $this->_getContentLeft(true),
    );
  }
}
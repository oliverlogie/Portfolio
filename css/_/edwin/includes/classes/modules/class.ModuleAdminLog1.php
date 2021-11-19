<?php

/**
 * $LastChangedDate: $
 * $LastChangedBy:  $
 *
 * @package EDWIN Backend
 * @author Koppensteiner Raphael
 * @copyright (c) 2018 Q2E GmbH
 */
class ModuleAdminLog1 extends Module
{
  protected $_prefix = 'ad_l1';

  public function show_innercontent()
  {
    return $this->_showContent();
  }

  protected function _initGrid()
  {
    $get = new Input(Input::SOURCE_GET);
    $gridSql = " SELECT LDateTime, LType, FK_CIID, CIIdentifier, FK_UID "
             . " FROM {$this->table_prefix}contentitem_log "
             . " WHERE 1 ";

    $queryFields[1] = array('type' => 'text', 'value' => 'LDateTime', 'lazy' => true);
    $queryFields[2] = array('type' => 'selective', 'value' => 'LType');
    $queryFields[3] = array('type' => 'text', 'value' => 'FK_CIID');
    $queryFields[4] = array('type' => 'text', 'value' => 'CIIdentifier', 'lazy' => true);
    $queryFields[5] = array('type' => 'selective', 'value' => 'FK_UID');

    $filterFields = $queryFields;
    $filterTypes = array(
      'LDateTime'    => 'text',
      'LType'        => 'selective',
      'FK_CIID'      => 'text',
      'CIIdentifier' => 'text',
      'FK_UID'       => 'selective',
    );

    $ordersValuelist = array(
      1 => array('field' => 'LDateTime',  'order' => 'ASC'),
      2 => array('field' => 'LDateTime',  'order' => 'DESC'),
      3 => array('field' => 'LType', 'order' => 'ASC'),
      4 => array('field' => 'LType', 'order' => 'DESC'),
      5 => array('field' => 'FK_CIID', 'order' => 'ASC'),
      6 => array('field' => 'FK_CIID', 'order' => 'DESC'),
      7 => array('field' => 'CIIdentifier', 'order' => 'ASC'),
      8 => array('field' => 'CIIdentifier', 'order' => 'DESC'),
      9 => array('field' => 'FK_UID', 'order' => 'ASC'),
      10 => array('field' => 'FK_UID', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;
    $presetOrders = array(1 => 2);

    $page = ($get->exists('ad_l1_page')) ? $get->readInt('ad_l1_page') : ($this->session->read('ad_l1_page') ? $this->session->read('ad_l1_page') : 1);
    $this->session->save('ad_l1_page', $page);

    $sql = " SELECT DISTINCT(LType), LType "
         . " FROM {$this->table_prefix}contentitem_log "
         . " GROUP BY LType ";
    $types = (array)$this->db->GetAssoc($sql);
    foreach ($types as $id => $value) {
        $types[$id] = array('label' => $value);
    }

    $sql = " SELECT UID, UFirstname, ULastname, UNick, UDeleted "
         . " FROM {$this->table_prefix}user ";
    $results = (array)$this->db->GetAssoc($sql);
    $users = array();
    foreach ($results as $key => $value) {
      if ($value['UFirstname'] && $value['ULastname']) {
        $label = sprintf("%s (%s %s)",
          $value['UNick'], $value['UFirstname'], $value['ULastname']);
      }
      else if ($value['UFirstname']) {
        $label = sprintf("%s (%s)", $value['UNick'], $value['UFirstname']);
      }
      else if ($value['ULastname']) {
        $label = sprintf("%s (%s)", $value['UNick'], $value['ULastname']);
      }
      else {
        $label = $value['UNick'];
      }

      if ($value['UDeleted']) {
        $label = sprintf("<del>%s</del>", $label);
      }

      $users[$value['UID']] = array('label' => $label);
    }

    $selectiveData = array(
      'LType'  => $types,
      'FK_UID' => $users,
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

  protected function _showContent()
  {
    global $_LANG, $_LANG2;

    if (isset($_POST['process_reset'])) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }

    $data = $this->_grid()->get_result();
    if (is_array($data)) {
      $i = 1;
      foreach ($data as $key => &$value) {
        $row = $this->_grid()->get_grid_data($key);
        $value['ad_l1_content_link'] = parseOutput($row['CIIdentifier']);
        $value['ad_l1_edit_link'] = '';
        if ($row['FK_CIID'] > 0 && $this->_navigation->getPageByID($row['FK_CIID'])) {
          $value['ad_l1_content_link'] = sprintf('<a href="%s">%s</a>',
            $this->_navigation->getPageByID($row['FK_CIID'])->getUrl(),
            parseOutput($row['CIIdentifier'])
          );

          if ($this->_navigation->getPageByID($row['FK_CIID'])->isRoot()) {
            $value['ad_l1_edit_link'] = sprintf('<a href="%s" class="btn ed_btn_list q2e_icon_edit"></a>',
              "index.php?action=mod_siteindex&site={$this->_navigation->getPageByID($row['FK_CIID'])->getSite()->getID()}"
            );
          }
          else {
            $value['ad_l1_edit_link'] = sprintf('<a href="%s" class="btn ed_btn_list q2e_icon_edit"></a>',
              "index.php?action=content&page={$row['FK_CIID']}&site={$this->_navigation->getPageByID($row['FK_CIID'])->getSite()->getID()}"
            );
          }
        }
        $data[$key]['ad_l1_row_bg'] = ( $i++ %2 ) ? 'even' : 'odd';
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
    $this->tpl->load_tpl($tplName, 'modules/ModuleAdminLog1.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(),
        $this->_getMessageTemplateArray('ad_l1'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');
    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl(null, array('action3'=>'log1'))), array (
        'ad_l1_action'                => $this->_parseUrl(null, array('action3'=>'log1')),
        'ad_l1_count_all'             => $this->_grid()->get_quantity_total_rows(),
        'ad_l1_count_current'         => $currentRows,
        'ad_l1_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3'=>'log1')) . '&amp;ad_l1_page=','_bottom'),
        'ad_l1_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
        'ad_l1_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3'=>'log1')) . '&amp;ad_l1_page=','_top'),
        'ad_l1_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['ad_l1']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(true),
    );
  }
}
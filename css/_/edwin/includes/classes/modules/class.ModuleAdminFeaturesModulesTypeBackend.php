<?php

/**
 * $LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2020 Q2E GmbH
 */
class ModuleAdminFeaturesModulesTypeBackend extends Module
{
  protected $_prefix = 'ad_ft_mb';

  protected $_shortname = 'modules_backend';

  public function show_innercontent()
  {
    // set the type manually as it can not be retrieved from this sub-sub-modules
    // $_shortname
    $this->_type = $this->_getModuleFactory()->getByShortname('admin');

    if (isset($this->action[0]) && $this->action[0]) {
      switch($this->action[0]) {
        case 'activate':
          $this->_processActivate();
          break;
        default:
          $this->_redirect($this->_parseUrl(null, array('action3' => $this->_shortname)));
      }
    }

    return $this->_showContent();
  }

  protected function _initGrid()
  {
    $get = new Input(Input::SOURCE_GET);
    $gridSql = " SELECT MID, MShortname, MClass, MActive, MPosition "
             . " FROM {$this->table_prefix}moduletype_backend "
             . " WHERE MRequired = 0 ";

    $queryFields[1] = array('type' => 'text', 'value' => 'MID', 'lazy' => true);
    $queryFields[2] = array('type' => 'text', 'value' => 'MShortname', 'lazy' => true);
    $queryFields[3] = array('type' => 'text', 'value' => 'MClass');
    $queryFields[4] = array('type' => 'text', 'value' => 'MPosition');

    $filterFields = $queryFields;
    $filterTypes = array(
      'MClass'     => 'text',
      'MID'        => 'text',
      'MShortname' => 'text',
      'MPosition'  => 'text',
    );

    $ordersValuelist = array(
      1 => array('field' => 'MShortname',  'order' => 'ASC'),
      2 => array('field' => 'MShortname',  'order' => 'DESC'),
      3 => array('field' => 'MClass', 'order' => 'ASC'),
      4 => array('field' => 'MClass', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;
    $presetOrders = array(1 => 1);

    $page = ($get->exists('ad_ft_mb_page')) ? $get->readInt('ad_ft_mb_page') : ($this->session->read('ad_ft_mb_page') ? $this->session->read('ad_ft_mb_page') : 1);
    $this->session->save('ad_ft_mb_page', $page);

    $selectiveData = array();

    $prefix = array(
      'config'  => $this->_prefix,
      'lang'    => $this->_prefix,
      'session' => $this->_prefix,
      'tpl'     => $this->_prefix
    );

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

    if (ed_http_input()->post()->exists('process_reset')) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }

    $data = $this->_grid()->get_result();
    if (is_array($data)) {
      foreach ($data as $key => &$value) {
        $row = $this->_grid()->get_grid_data($key);
        $activationLightLink = $this->_parseUrl(null, array('action3' => $this->_shortname . ';' . 'activate', 'page' => (int)$row['MID'], 'active' => ''));
        if ($row['MActive'] == 1) {
          $activationLight = ActivationLightInterface::GREEN;
          $activationLightLink .= ContentBase::ACTIVATION_DISABLED;
        }
        else {
          $activationLight = ActivationLightInterface::RED;
          $activationLightLink .= ContentBase::ACTIVATION_ENABLED;
        }

        $label = $_LANG['global_activation_light_'.$activationLight.'_label'];

        $value[$this->_prefix . '_activation_light']       = $activationLight;
        $value[$this->_prefix . '_activation_light_label'] = $label;
        $value[$this->_prefix . '_activation_light_link']  = $activationLightLink;
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

    $tplName = 'ad_ft_mb_content';
    $this->tpl->load_tpl($tplName, 'modules/ModuleAdminFeaturesModulesTypeBackend.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ad_ft_mb'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');
    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl(null, array('action3' => $this->_shortname))), array (
      'ad_ft_mb_action'                => $this->_parseUrl(null, array('action3' => $this->_shortname)),
      'ad_ft_mb_count_all'             => $this->_grid()->get_quantity_total_rows(),
      'ad_ft_mb_count_current'         => $currentRows,
      'ad_ft_mb_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3' => $this->_shortname)) . '&amp;ad_ft_mb_page=','_bottom'),
      'ad_ft_mb_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
      'ad_ft_mb_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3' => $this->_shortname)) . '&amp;ad_ft_mb_page=','_top'),
      'ad_ft_mb_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['ad_ft_mb']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  private function _processActivate()
  {
    global $_LANG;

    if (!(int)$this->getItemId() || !ed_http_input()->get()->exists('active')) {
      return;
    }

    $active = ed_http_input()->get()->readString('active') === 'enabled' ? 1 : 0;

    $sql = " UPDATE {$this->table_prefix}moduletype_backend "
         . "    SET MActive = ? "
         . "  WHERE MID = ? ";
    $this->db->q($sql, array($active, (int)$this->getItemId()));

    $this->_redirect(
      $this->_parseUrl(null, array('action3' => $this->_shortname)),
      Message::createSuccess($active ? $_LANG['ad_ft_mb_message_activate_success'] : $_LANG['ad_ft_mb_message_deactivate_success'])
    );
  }
}
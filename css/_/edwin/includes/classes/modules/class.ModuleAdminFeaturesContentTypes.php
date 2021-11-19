<?php

/**
 * $LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2020 Q2E GmbH
 */
class ModuleAdminFeaturesContentTypes extends Module
{
  protected $_prefix = 'ad_ft_ct';

  protected $_shortname = 'content_types';

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
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $gridSql = " SELECT CTID, CTClass, FK_CTID, CTActive, CTTemplate, "
             . "        CTPageType, CTPosition, ("
             . "          SELECT COUNT(*) "
             . "          FROM {$this->table_prefix}contentitem ci "
             . "          WHERE ci.FK_CTID = ct.CTID "
             . "        ) AS count "
             . " FROM {$this->table_prefix}contenttype ct "
             . " WHERE 1 ";

    $queryFields[1] = array('type' => 'text', 'value' => 'CTID', 'lazy' => true);
    $queryFields[2] = array('type' => 'text', 'value' => 'CTClass', 'lazy' => true);
    $queryFields[3] = array('type' => 'selective', 'value' => 'CTPageType');
    $queryFields[4] = array('type' => 'text', 'value' => 'CTPosition');

    $filterFields = $queryFields;
    $filterTypes = array(
      'CTClass'    => 'text',
      'CTID'       => 'text',
      'CTPageType' => 'selective',
      'CTPosition' => 'text',
    );

    $ordersValuelist = array(
      1 => array('field' => 'CTClass',  'order' => 'ASC'),
      2 => array('field' => 'CTClass',  'order' => 'DESC'),
      3 => array('field' => 'CTPosition', 'order' => 'ASC'),
      4 => array('field' => 'CTPosition', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;
    $presetOrders = array(1 => 1);

    $page = ($get->exists('ad_ft_ct_page')) ? $get->readInt('ad_ft_ct_page') : ($this->session->read('ad_ft_ct_page') ? $this->session->read('ad_ft_ct_page') : 1);
    $this->session->save('ad_ft_ct_page', $page);

    $types = array();
    foreach ($_LANG['ad_ft_ct_page_types'] as $id => $value) {
      $types[$id] = array('label' => $value);
    }

    $selectiveData = array(
      'CTPageType'  => $types,
    );

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
        $activationLightLink = $this->_parseUrl(null, array('action3' => $this->_shortname . ';' . 'activate', 'page' => (int)$row['CTID'], 'active' => ''));
        if ($row['CTActive'] == 1) {
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
        $value[$this->_prefix . '_count']                  = (int)$row['count'];
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

    $tplName = 'ad_ft_ct_content';
    $this->tpl->load_tpl($tplName, 'modules/ModuleAdminFeaturesContentTypes.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ad_ft_ct'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');
    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl(null, array('action3' => $this->_shortname))), array (
      'ad_ft_ct_action'                => $this->_parseUrl(null, array('action3' => $this->_shortname)),
      'ad_ft_ct_count_all'             => $this->_grid()->get_quantity_total_rows(),
      'ad_ft_ct_count_current'         => $currentRows,
      'ad_ft_ct_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3' => $this->_shortname)) . '&amp;ad_ft_ct_page=','_bottom'),
      'ad_ft_ct_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
      'ad_ft_ct_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl(null, array('action3' => $this->_shortname)) . '&amp;ad_ft_ct_page=','_top'),
      'ad_ft_ct_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['ad_ft_ct']));

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

    $sql = " UPDATE {$this->table_prefix}contenttype "
         . "    SET CTActive = ? "
         . "  WHERE CTID = ? ";
    $this->db->q($sql, array($active, (int)$this->getItemId()));

    $this->_redirect(
      $this->_parseUrl(null, array('action3' => $this->_shortname)),
      Message::createSuccess($active ? $_LANG['ad_ft_ct_message_activate_success'] : $_LANG['ad_ft_ct_message_deactivate_success'])
    );
  }
}
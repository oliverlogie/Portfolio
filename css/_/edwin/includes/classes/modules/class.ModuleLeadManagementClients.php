<?php

/**
 * Lead client management class
 *
 * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleLeadManagementClients extends AbstractModuleLeadManagement
{

  /**
   * Content handler
   */
  public function show_innercontent()
  {
    $get = new Input(Input::SOURCE_GET);

    if ($get->readInt('did')) {
      $this->_deleteLead($get->readInt('did'));
    }

    return $this->_getContent();
  }

  /**
   * Gets content
   *
   * @return array
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $get = new Input(Input::SOURCE_GET);
    $post = new Input(Input::SOURCE_POST);

    // if a lead has been edited recently, may show a message
    $this->_checkForMessages('la');

    // preset filters: array ( $queryFields KEY => field value )
    $presetFilters = array();
    $customFilters = array();

    if (!$this->_isUserLeadAdmin() || ($post->exists('la_show_leads') && $post->readInt('la_show_leads') != 1)) {
      $customFilters[] = 'c.FK_UID = '.$this->_user->getID();
    }

    // pre-configure the filters via http get attributes (infocenter uses this to display
    // campaign leads of selected campaign status). index = $queryFields array index
    if ($get->exists('cgid')) {
      $presetFilters[8] = $get->readInt('cgid'); //'CGID'
      $this->session->save('la_infocenter_prefilter_cgid', $get->readInt('cgid'));
    }
    if ($get->exists('cgsid')) {
      $presetFilters[9] = $get->readInt('cgsid'); //'FK_CGSID'
      $this->session->save('la_infocenter_prefilter_cgsid', $get->readInt('cgsid'));
    }

    $gridSql = 'SELECT CID, CCompany, CPosition, FK_FID, CFirstname, CLastname, ' // client fields
             . '       CBirthday, CCountry, CZIP, CCity, CAddress, CPhone, CMobilePhone, '
             . '       CEmail, CNewsletterConfirmedRecipient, CCreateDateTime, '
             . '       CChangeDateTime, c.FK_UID AS leadAgent, '
             . '       CTitlePre, CTitlePost, CDataPrivacyConsent, '
             . '       CGName, CGID, '   // campaign fields
             . '       CGLID, FK_CGSID, CGLDataOrigin, ' // campaign lead fields
             . '       CGSName, CGSID '  // campaign status fields
             . "FROM {$this->table_prefix}campaign_lead cl "
             . "INNER JOIN {$this->table_prefix}campaign cg "
             . '      ON CGID = cl.FK_CGID '
             . "LEFT JOIN {$this->table_prefix}client c "
             . '     ON CID = FK_CID '
             . "LEFT JOIN {$this->table_prefix}campaign_status cgs "
             . '     ON CGSID = FK_CGSID '
             . "WHERE CGLDeleted = 0 "
             . '  AND ( CGStatus = '.self::CAMPAIGN_STATUS_ACTIVE.' '
             . '        OR CGStatus = '.self::CAMPAIGN_STATUS_ARCHIVED.' '
             . '      ) ';

    // configure all filters and order values.
    // WARNING: if you change some settings, you may delete data grid session cookies in your
    //          browser to avoid problems with cached filters/orders.
    $filterSelective1 = array('CGLID', 'FK_FID', 'CTitlePre', 'CTitlePost',  'CPosition', 'CCompany');
    $filterSelective2 = array('CEmail', 'CBirthday', 'CPhone', 'CMobilePhone', 'CAddress', 'CZIP', 'CCity', 'CCountry', 'CCreateDateTime', 'CChangeDateTime',);
    $filterSelective3 = array('CGID', 'CGSID', 'CGLDataOrigin', 'leadAgent');

    $filterTypes = array(
      'CGLID' => 'text',
      'CID' => 'text',
      'CCompany' => 'text',
      'CPosition' => 'text',
      'FK_FID' => 'selective',
      'CTitlePre' => 'text',
      'CTitlePost' => 'text',
      'CFirstname' => 'text',
      'CLastname' => 'text',
      'CBirthday' => 'text',
      'CCountry' => 'selective',
      'CZIP' => 'text',
      'CCity' => 'text',
      'CAddress' => 'text',
      'CPhone' => 'text',
      'CMobilePhone' => 'text',
      'CEmail' => 'text',
      'CNewsletterConfirmedRecipient' => 'boolean',
      'CDataPrivacyConsent' => 'boolean',
      'CCreateDateTime' => 'text',
      'CChangeDateTime' => 'text',
      'CGName' => 'text',
      'CGID' => 'selective',
      'CGSID' => 'selective',
      'leadAgent' => 'selective',
      'FK_CGSID' => 'selective',
      'CGLDataOrigin' => 'text',
    );
    // set query fields.
    // WARNING 1: Do not forget to add fields with a valuelist to our $filterSelective array!
    // WARNING 2: Do not forget to update $presetFilters array keys, if set.
    // WARNING 3: Do not forget to update infocenter preset filter check, if array keys changed.
    $queryFields[1] = array('type' => 'text', 'value' => 'FK_FID', 'valuelist' => $filterSelective1);
    $queryFields[2] = array('type' => 'text', 'value' => 'CFirstname');
    $queryFields[3] = array('type' => 'text', 'value' => 'CLastname');
    $queryFields[4] = array('type' => 'text', 'value' => 'CEmail', 'valuelist' => $filterSelective2);
    $queryFields[5] = array('type' => 'text', 'value' => 'CCountry', 'valuelist' => $filterSelective2);
    $queryFields[6] = array('type' => 'boolean', 'value' => 'CNewsletterConfirmedRecipient');
    $queryFields[7] = array('type' => 'boolean', 'value' => 'CDataPrivacyConsent');
    $queryFields[8] = array('type' => 'selective', 'value' => 'CGID', 'valuelist' => $filterSelective3);
    $queryFields[9] = array('type' => 'selective', 'value' => 'CGSID', 'valuelist' => $filterSelective3);

    $filterFields = $queryFields;
    // add invisible filter settings, may used via http get attributes
    // (e.g. campaign status quicklinks of infocenter) or session
    $filterFields[10] = array('type' => 'selective', 'value' => 'FK_CGSID');
    $filterFields[11] = array('type' => 'selective', 'value' => 'leadAgent');

    // set order value list. these order values will be available in our order comboboxes
    $ordersValuelist = array(
      1 => array('field' => 'CLastname',       'order' => 'ASC'),
      2 => array('field' => 'CLastname',       'order' => 'DESC'),
      3 => array('field' => 'CZIP',            'order' => 'ASC'),
      4 => array('field' => 'CCity',           'order' => 'ASC'),
      5 => array('field' => 'CCreateDateTime', 'order' => 'ASC'),
      6 => array('field' => 'CCreateDateTime', 'order' => 'DESC'),
      7 => array('field' => 'CChangeDateTime', 'order' => 'ASC'),
      8 => array('field' => 'CChangeDateTime', 'order' => 'DESC'),
    );

    $orders[1]['valuelist'] = $ordersValuelist;
    $orders[2]['valuelist'] = $ordersValuelist;
    $orders[3]['valuelist'] = $ordersValuelist;

    // use LeadManagement (ld) lang variables
    $prefix = array('config' => 'la', 'lang' => 'ld', 'session' => 'la', 'tpl' => 'la');
    // create data grid object
    $slGrid = new DataGrid($this->db, $this->session, $prefix);

    // may reset old filter settings if new filter is set
    if ($presetFilters) {
      $slGrid->resetFilters();
    }

    // read countries
    $countries = array();
    foreach ($this->_configHelper->getCountries(array('la_countries','countries', ), false, $this->site_id) as $id => $value) {
      $countries[$id]['label'] = $value;
    }

    // read foas
    $foas = array();
    foreach ($_LANG['ld_foas'] as $id => $value) {
      $foas[$id]['label'] = $value;
    }

    // read user
    $users = $this->_readLeadAgentUsers();
    // read all active or archived campaigns
    $campaigns = $this->_readCampaigns();
    // read all status types
    $statusTypes = $this->_readStatusTypes();
    // set selective data
    $selectiveData = array('CCountry' => $countries, 'FK_FID' => $foas, 'CGID' => $campaigns, 'CGSID' => $statusTypes, 'leadAgent' => $users);
    $slGrid->setSelectiveData($selectiveData);

    // load (configure) data grid
    $page = ($get->exists('page')) ? $get->readInt('page') : 1;

    // Alias sql fields.
    $aliasFilterFields = array('leadAgent');

    $slGrid->load($gridSql, $queryFields, $filterFields, $filterTypes, $orders, $page, 0, $presetFilters, '', $customFilters, '', 0, 0, '', $aliasFilterFields);

    // reset filters and orders if reset btn was clicked
    if ($post->exists('process_reset'))
    {
      $slGrid->resetFilters();
      $slGrid->resetOrders();
      $slGrid->resetOrderControls();
      $this->session->reset('la_infocenter_prefilter_cgid');
      $this->session->reset('la_infocenter_prefilter_cgsid');
    }

    // if infocenter prefilter was changed, reset session vars
    if (($this->session->read('la_infocenter_prefilter_cgid') &&
        $slGrid->getFilterValue(7) != $this->session->read('la_infocenter_prefilter_cgid')) ||
       ($this->session->read('la_infocenter_prefilter_cgsid') &&
        $slGrid->getFilterValue(8) != $this->session->read('la_infocenter_prefilter_cgsid')))
    {
      $this->session->reset('la_infocenter_prefilter_cgid');
      $this->session->reset('la_infocenter_prefilter_cgsid');
    }

    // load filters and orders
    $laColFilters = $slGrid->load_col_filters();
    $laOrderFields = $slGrid->load_order_fields();
    $laOrderControlFields = $slGrid->load_order_controls($this->_parseUrl());

    if ($get->readInt('export_xls'))
    {
      // set export fields
      $exportFields = array(
        array('type' => 'text', 'value' => 'CGLID'),
        array('type' => 'text', 'value' => 'CCompany'),
        array('type' => 'text', 'value' => 'CPosition'),
        array('type' => 'text', 'value' => 'FK_FID'),
        array('type' => 'text', 'value' => 'CTitlePre'),
        array('type' => 'text', 'value' => 'CTitlePost'),
        array('type' => 'text', 'value' => 'CFirstname'),
        array('type' => 'text', 'value' => 'CLastname'),
        array('type' => 'selective', 'value' => 'CCountry'),
        array('type' => 'text', 'value' => 'CZIP'),
        array('type' => 'text', 'value' => 'CCity'),
        array('type' => 'text', 'value' => 'CAddress'),
        array('type' => 'text', 'value' => 'CPhone'),
        array('type' => 'text', 'value' => 'CMobilePhone'),
        array('type' => 'text', 'value' => 'CEmail'),
        array('type' => 'boolean', 'value' => 'CNewsletterConfirmedRecipient'),
        array('type' => 'boolean', 'value' => 'CDataPrivacyConsent'),
        array('type' => 'text', 'value' => 'CCreateDateTime'),
        array('type' => 'text', 'value' => 'CChangeDateTime'),
        array('type' => 'selective', 'value' => 'CGID'),
        array('type' => 'selective', 'value' => 'CGSID'),
        array('type' => 'text', 'value' => 'CGLDataOrigin'),
        array('type' => 'selective', 'value' => 'leadAgent'),
      );
      $fileName = sprintf($_LANG['la_export_file_name'], date("Ymd-Hi"));
      $slGrid->exportXls($exportFields, $fileName);
      exit;
    }

    $i = 1;
    // get data grid result
    $gridData = $slGrid->get_result();
    $laShowpageTop = '';
    $laShowpageBottom = '';

    // load template
    $this->tpl->load_tpl('content_la', 'modules/ModuleLeadManagementClients.tpl');

    if (is_array($gridData))
    {
      foreach ($gridData as $id => $value)
      {
        $leadId = $slGrid->get_grid_data($id, 'CGLID');
        $cgId = $slGrid->get_grid_data($id, 'CGID');
        $gridData[$id]['la_edit_link'] = 'index.php?action=mod_leadmgmt&amp;action2=client;edit&amp;site='.$this->site_id.'&amp;module=clients&amp;lead='.$leadId.'&amp;cgid='.$cgId;
        $gridData[$id]['la_row_bg'] = ($i++%2 ? 'row1' : 'row2');
        $gridData[$id]['la_lead_id'] = $leadId;
      }
      $laShowpageTop = $slGrid->load_page_navigation('index.php?action=mod_leadmgmt&amp;action2=clients&amp;page=','_top');
      $laShowpageBottom = $slGrid->load_page_navigation('index.php?action=mod_leadmgmt&amp;action2=clients&amp;page=','_bottom');
    }
    else {
      $this->setMessage($gridData);
    }
    $laCurrentSel = $slGrid->get_page_selection();
    $laCurrentRows = $slGrid->get_quantity_selected_rows();

    $this->tpl->parse_if('content_la', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('la'));
    $this->tpl->parse_if('content_la', 'infocenter_preselection', $this->session->read('la_infocenter_prefilter_cgid') && $this->session->read('la_infocenter_prefilter_cgsid'), array(
      'la_campaign' => $this->session->read('la_infocenter_prefilter_cgid') ? $campaigns[$this->session->read('la_infocenter_prefilter_cgid')]['label'] : '',
      'la_status' => $this->session->read('la_infocenter_prefilter_cgsid') ? $statusTypes[$this->session->read('la_infocenter_prefilter_cgsid')]['label'] : '',
    ));
    $this->tpl->parse_if('content_la', 'la_show_leads', $this->_isUserLeadAdmin(), array(
      'la_assigned_leads_checked' => ($post->readInt('la_show_leads') == 2) ? 'checked="checked"' : '',
      'la_all_leads_checked' => ($post->readInt('la_show_leads') == 1 || !$post->exists('la_show_leads')) ? 'checked="checked"' : '',
    ));
    $this->tpl->parse_if('content_la', 'order_controls_set', $slGrid->isOrderControlsSet());
    $this->tpl->parse_loop('content_la', $gridData, 'client_rows');
    if (is_array($gridData))
    {
      foreach ($gridData as $id => $value)
      {
        $leadId = $slGrid->get_grid_data($id, 'CGLID');
        $this->tpl->parse_if('content_la', 'la_delete_lead_'.$leadId, $this->_isUserLeadAdmin(), array(
          'la_delete_link' => 'index.php?action=mod_leadmgmt&amp;action2=clients&amp;page='.$page.'&amp;did='.$leadId,
        ));
      }
    }
    $content = $this->tpl->parsereturn('content_la', array_merge( $laColFilters , $laOrderFields, $laOrderControlFields, array (
      'la_action'                => 'index.php?action=mod_leadmgmt&amp;action2=clients',
      'la_showpage_top'          => $laShowpageTop,
      'la_showpage_top_label'    => sprintf($_LANG['la_showpage_top_label'],($laCurrentRows ? $laCurrentSel['begin'] : 0),($laCurrentRows ? $laCurrentSel['end'] : 0)),
      'la_showpage_bottom'       => $laShowpageBottom,
      'la_showpage_bottom_label' => sprintf($_LANG['la_showpage_bottom_label'],($laCurrentRows ? $laCurrentSel['begin'] : 0),($laCurrentRows ? $laCurrentSel['end'] : 0)),
      'la_count_current'         => $laCurrentRows,
      'la_count_all'             => $slGrid->get_quantity_total_rows(),
      'la_request_url'           => 'index.php?action=mod_response_leadmgmt&site='.$this->site_id.'&request=',
    ), $_LANG2['la']));

    return array(
      'content'             => $content,
      // use custom main template
      'content_output_mode' => 10,
      'content_output_tpl'  => 'leadmgmt',
    );
  }

  /**
   * Deletes a lead.
   *
   * @param int $leadId
   * @return boolean
   *         True on success, false on failure.
   */
  private function _deleteLead($leadId)
  {
    global $_LANG;

    if (!$this->_isUserLeadAdmin()) {
      return false;
    }

    // update lead - set to deleted
    $sql = " UPDATE {$this->table_prefix}campaign_lead "
         . ' SET CGLDeleted = 1 '
         . " WHERE CGLID = '{$this->db->escape($leadId)}' ";
    $result = $this->db->query($sql);

    if ($result)
    {
      $this->setMessage(Message::createSuccess($_LANG['la_message_lead_delete_success']));
      return true;
    }

    return false;
  }

  /**
   * Reads all status types from db
   * and generates data array.
   * If campaign filter was set, then only status entries of
   * selected campaign are returned.
   *
   * @return array
   *         Array with status id and name or empty array.
   */
  private function _readStatusTypes()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $where = '';
    // if there was set a campaign filter, get the campaign id and create sql-where statement
    if ($post->exists('la_query_filter_fields') && $post->exists('la_query_filter'))
    {
      $queryFilterFields = array_flip($post->readArrayIntToString('la_query_filter_fields'));
      $queryFilter = $post->readArrayIntToString('la_query_filter');
      $cgIdKey = (isset($queryFilterFields['CGID'])) ? $queryFilterFields['CGID'] : 0;
      $cgId = ($cgIdKey && isset($queryFilter[$cgIdKey])) ? $queryFilter[$cgIdKey] : 0;

      if ($cgId) {
        $where = ' WHERE cs.FK_CGID = 0 OR cs.FK_CGID = '.$cgId;
      }
    }

    $sql = ' SELECT CGSID, CGSName, CGName, CGID '
         . " FROM {$this->table_prefix}campaign_status cs "
         . " LEFT JOIN {$this->table_prefix}campaign "
         . '    ON CGID = cs.FK_CGID '
         . $where
         . ' ORDER BY cs.FK_CGID ASC, CGSPosition ASC ';
    $res = $this->db->query($sql);
    $statusTypes = array();
    $lastcgId = 0;
    while ($row = $this->db->fetch_row($res))
    {
      if ($lastcgId != $row['CGID'])
      {
        $lastcgId = $row['CGID'];
        $statusTypes[$row['CGSID']]['optgroup'] = parseOutput($row['CGName']);
        $statusTypes[$row['CGSID']]['optgroup_id'] = $lastcgId;
      }
      $statusTypes[$row['CGSID']]['label'] = parseOutput($row['CGSName']);
    }

    return $statusTypes;
  }


  /**
   * Overwrites AbstractModuleLeadManagement::_readLeadAgentUsers()
   * and modifies the returned user array to work with class DataGrid.
   *
   * @see AbstractModuleLeadManagement::_readLeadAgentUsers()
   */
  protected function _readLeadAgentUsers()
  {
    $users = array();
    foreach (parent::_readLeadAgentUsers() as $id => $value) {
      $users[$id]['label'] = $value;
    }

    return $users;
  }
}
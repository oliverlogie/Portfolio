<?php

/**
 * Lead management - Search class
 *
 * $LastChangedDate: 2019-02-01 11:00:21 +0100 (Fr, 01 Feb 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleLeadManagementSearch extends AbstractModuleLeadManagement
{

  /**
   * Content handler
   */
  public function show_innercontent()
  {
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

    $infoSent = $this->_sendInformMsg();

    // if a lead has been edited recently, may show a message
    $this->_checkForMessages('lh');

    // assign lead to user
    $resClientTaken = false;
    if ($this->action[0] == 'take' && $get->readInt('cid') && $get->readInt('lead') && $get->readInt('cgid'))
    {
      $resClientTaken = $this->_takeClient($get->readInt('cid'), $this->_user->getID(), $get->readInt('lead'), $get->readInt('cgid'));
      if ($resClientTaken) {
        $this->setMessage(Message::createSuccess($_LANG['lh_message_lead_taken_success']));
      }
    }

    // preset filters: array ( $queryFields KEY => field value )
    $presetFilters = '';

    $gridSql = 'SELECT CID, CCompany, CPosition, FK_FID, CFirstname, CLastname, ' // client fields
             . '       CBirthday, CCountry, CZIP, CCity, CAddress, CPhone, CMobilePhone, '
             . '       CEmail, CNewsletterConfirmedRecipient, CCreateDateTime, CChangeDateTime, c.FK_UID AS FK_UID, '
             . '       CTitlePre, CTitlePost, '
             . '       CGName, CGID, ' // campaign fields
             . '       CGLID '        // campaign lead fields
             . "FROM {$this->table_prefix}campaign_lead cl "
             . "INNER JOIN {$this->table_prefix}campaign cg "
             . '      ON CGID = cl.FK_CGID '
             . "LEFT JOIN {$this->table_prefix}client c "
             . '     ON CID = FK_CID '
             . "WHERE CGLDeleted = 0 "
             . '  AND ( CGStatus = '.self::CAMPAIGN_STATUS_ACTIVE.' '
             . '        OR CGStatus = '.self::CAMPAIGN_STATUS_ARCHIVED.' '
             . '      ) ';

    // configure all filters and order values.
    // WARNING: if you change some settings, you may delete data grid session cookies in your
    //          browser to avoid problems with cached filters/orders.
    //$filterSelective = array('FK_FID', 'CEmail', 'CGID', 'CCompany', 'CPhone', 'CMobilePhone', 'CAddress', 'CCity', 'CCountry', 'CCreateDateTime', 'CChangeDateTime');

    $filterTypes = array('CID' => 'text',
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
                          'CCreateDateTime' => 'text',
                          'CChangeDateTime' => 'text',
                          'CGName' => 'text',
                          'CGID' => 'selective',
                          'FK_CGSID' => 'selective',
                          'FK_UID' => 'selective',
                          );
    // set query fields. do not forget to add fields with a valuelist to our $filterSelective array!
    $queryFields[1] = array('type' => 'text', 'value' => 'CFirstname');
    $queryFields[2] = array('type' => 'text', 'value' => 'CLastname');
    $queryFields[3] = array('type' => 'text', 'value' => 'CZIP');
    $queryFields[4] = array('type' => 'text', 'value' => 'CCity');
    $queryFields[5] = array('type' => 'selective', 'value' => 'CCountry');
    $queryFields[6] = array('type' => 'text', 'value' => 'CEmail');

    $filterFields = $queryFields;
    // add invisible filter settings, may used via http get attributes
    // (e.g. campaign status quicklinks of infocenter) or session
//    $filterFields[7] = array('type' => 'selective', 'value' => 'FK_CGSID');
//    $filterFields[8] = array('type' => 'selective', 'value' => 'FK_UID');

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
    $prefix = array('config' => 'lh', 'lang' => 'ld', 'session' => 'lh', 'tpl' => 'lh');
    // create data grid object
    $slGrid = new DataGrid($this->db, $this->session, $prefix);

    // may reset old filter settings if new filter is set
    if ($presetFilters) {
      $slGrid->resetFilters();
    }

    // read countries
    $countries = array();
    foreach ($this->_configHelper->getCountries(array('lh_countries', 'countries', ), false, $this->site_id) as $id => $value) {
      $countries[$id]['label'] = $value;
    }

    // read foas
    $foas = array();
    foreach ($_LANG['ld_foas'] as $id => $value) {
      $foas[$id]['label'] = $value;
    }

    // read all active or archived campaigns
    $campaigns = $this->_readCampaigns();
    // set selective data
    $selectiveData = array('CCountry' => $countries, 'FK_FID' => $foas, 'CGID' => $campaigns);
    $slGrid->setSelectiveData($selectiveData);

    // load (configure) data grid
    $page = ($get->exists('page')) ? $get->readInt('page') : 1;
    $defaultEmpty = (!$post->exists('process_filter') && !$infoSent && !$resClientTaken) ? 1 : 0;
    $slGrid->load($gridSql, $queryFields, $filterFields, $filterTypes, $orders, $page, $defaultEmpty, $presetFilters);

    // reset filters and orders if reset btn was clicked
    if ($post->exists('process_reset')) {
      $slGrid->resetFilters();
      $slGrid->resetOrders();
      $slGrid->resetOrderControls();
    }

    // load filters and orders
    $laColFilters = $slGrid->load_col_filters();
    $laOrderFields = $slGrid->load_order_fields();
    $laOrderControlFields = $slGrid->load_order_controls($this->_parseUrl());

    $i = 1;
    // get data grid result
    $gridData = $slGrid->get_result();
    $laShowpageTop = '';
    $laShowpageBottom = '';

    // load template
    $this->tpl->load_tpl('content_lh', 'modules/ModuleLeadManagementSearch.tpl');

    if (is_array($gridData)) {
      foreach ($gridData as $id => $value) {
        $leadId = $slGrid->get_grid_data($id, 'CGLID');
        $gridData[$id]['lh_row_bg'] = ($i++%2 ? 'row1' : 'row2');
        $gridData[$id]['lh_lead_id'] = $leadId;
      }
      $laShowpageTop = $slGrid->load_page_navigation('index.php?action=mod_leadmgmt&amp;action2=search&amp;page=','_top');
      $laShowpageBottom = $slGrid->load_page_navigation('index.php?action=mod_leadmgmt&amp;action2=search&amp;page=','_bottom');
    }
    else {
      $this->setMessage($gridData);
    }
    $laCurrentSel = $slGrid->get_page_selection();
    $laCurrentRows = $slGrid->get_quantity_selected_rows();

    $this->tpl->parse_if('content_lh', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lh'));
    $this->tpl->parse_if('content_lh', 'lh_show_leads', $this->_isUserLeadAdmin(), array(
      'lh_assigned_leads_checked' => ($post->readInt('lh_show_leads') == 2) ? 'checked="checked"' : '',
      'lh_all_leads_checked' => ($post->readInt('lh_show_leads') == 1 || !$post->exists('lh_show_leads')) ? 'checked="checked"' : '',
    ));

    $this->tpl->parse_loop('content_lh', $gridData, 'client_rows');
    foreach ($gridData as $id => $value) {
      $leadId = $slGrid->get_grid_data($id, 'CGLID');
      $cgId = $slGrid->get_grid_data($id, 'CGID');
      $uId = $slGrid->get_grid_data($id, 'FK_UID');
      $cId = $slGrid->get_grid_data($id, 'CID');
      $agent = '';
      if ($uId && ($uId != $this->_user->getID()))
      {
        $sql = ' SELECT UFirstname, ULastname '
             . " FROM {$this->table_prefix}user "
             . " WHERE UID = {$uId} ";
        $row = $this->db->GetRow($sql);
        $agent = parseOutput($row['UFirstname']).' '.parseOutput($row['ULastname']);
      }
      $this->tpl->parse_if('content_lh', 'taken_other_'.$leadId, $agent, array(
        'lh_lead_agent' => $agent,
        'lh_cg_id' => $cgId,
      ));
      $this->tpl->parse_if('content_lh', 'taken_own_'.$leadId, $uId == $this->_user->getID(), array(
        'lh_edit_link' => 'index.php?action=mod_leadmgmt&amp;action2=client;edit&amp;site='.$this->site_id.'&amp;module=search&amp;lead='.$leadId.'&amp;cgid='.$cgId,
      ));
      $this->tpl->parse_if('content_lh', 'untaken_'.$leadId, !$uId, array(
        'lh_edit_link' => 'index.php?action=mod_leadmgmt&amp;action2=client;edit;take&amp;site='.$this->site_id.'&amp;cid='.$cId.'&amp;lead='.$leadId.'&amp;cgid='.$cgId,
        'lh_take_link' => 'index.php?action=mod_leadmgmt&amp;action2=search;take&amp;site='.$this->site_id.'&amp;cid='.$cId.'&amp;lead='.$leadId.'&amp;cgid='.$cgId,
      ));
    }

    $this->tpl->parse_if('content_lh', 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
    $this->tpl->parse_if('content_lh', 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
    $this->tpl->parse_if('content_lh', 'order_controls_set', $slGrid->isOrderControlsSet());

    $content = $this->tpl->parsereturn('content_lh', array_merge( $laColFilters , $laOrderFields, $laOrderControlFields, array (
      'lh_action'                => 'index.php?action=mod_leadmgmt&amp;action2=search',
      'lh_showpage_top'          => $laShowpageTop,
      'lh_showpage_top_label'    => sprintf($_LANG['lh_showpage_top_label'],($laCurrentRows ? $laCurrentSel['begin'] : 0),($laCurrentRows ? $laCurrentSel['end'] : 0)),
      'lh_showpage_bottom'       => $laShowpageBottom,
      'lh_showpage_bottom_label' => sprintf($_LANG['lh_showpage_bottom_label'],($laCurrentRows ? $laCurrentSel['begin'] : 0),($laCurrentRows ? $laCurrentSel['end'] : 0)),
      'lh_count_current'         => $laCurrentRows,
      'lh_count_all'             => $slGrid->get_quantity_total_rows(),
      'lh_request_url'           => 'index.php?action=mod_response_leadmgmt&site='.$this->site_id.'&request=',
      'main_cache_resource_version' => ConfigHelper::get('m_cache_resource_version'),
      'main_theme'                  => ConfigHelper::get('m_backend_theme'),
    ), $_LANG2["global"], $_LANG2['lh']));

    return array(
      'content'             => $content,
      'content_output_mode' => 2,
      'content_output_tpl'  => '',
    );
  }

  /**
   * Adds to selected lead an appointment with
   * custom message.
   *
   * @return boolean
   *         True on success, otherwise false.
   */
  private function _sendInformMsg()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    // inform lead agent
    if ($post->exists('process_inform'))
    {
      $cgId = $post->readInt('lh_inform_cgid');
      $leadId = $post->readInt('lh_inform_lid');
      $text = $post->readString('lh_inform_box_text');

      $sql = " INSERT INTO {$this->table_prefix}campaign_lead_appointment "
           . ' ( CGLACreateDateTime, CGLAChangeDateTime, CGLADateTime, CGLATitle, CGLAText, '
           . '   FK_UID, FK_CGLID, FK_CGID ) '
           . " VALUES ('".date('Y-m-d H:i')."', "
           . "         '".date('Y-m-d H:i')."', "
           . "         '".date('Y-m-d H:i')."', "
           . "         '".$this->db->escape($_LANG['lh_inform_title'])."', "
           . "         '".$this->db->escape($text)."', "
           . "         {$this->_user->getID()}, "
           . "         {$leadId}, "
           . "         {$cgId} "
           . ' ) ';
      return $this->db->query($sql);
    }

    return false;
  }
}
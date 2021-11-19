<?php

/**
 * Lead management - Appointments class
 *
 * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleLeadManagementAppointments extends AbstractModuleLeadManagement
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

    // if a lead has been edited recently, may show a message
    $this->_checkForMessages('lb');

    // preset filters: array ( $queryFields KEY => field value )
    $presetFilters = '';

    $customOrders[] = 'CGLADateTime ASC';
    $customFilters = array();

    if ($post->exists('process_filter_today')) {
      $customFilters[] = "CGLADateTime <= '".date('Y-m-d H:i:s')."'";
    }
    $gridSql = 'SELECT CID, CCompany, CPosition, FK_FID, CFirstname, CLastname, ' // client fields
             . '       CBirthday, CCountry, CZIP, CCity, CAddress, CPhone, CMobilePhone, '
             . '       CEmail, CNewsletterConfirmedRecipient, CCreateDateTime, '
             . '       CChangeDateTime, c.FK_UID, CTitlePre, CTitlePost, CDataPrivacyConsent, '
             . '       CGName, CGID, ' // campaign fields
             . '       CGLID, '        // campaign lead fields
             . '       CGLAID, CGLACreateDateTime, CGLADateTime, ' // campaign appointment fields
             . '       CGLATitle, CGLAText, cla.FK_UID AS appointment_creator '
             . "FROM {$this->table_prefix}campaign_lead cl "
             . "INNER JOIN {$this->table_prefix}campaign cg "
             . '      ON CGID = cl.FK_CGID '
             . "INNER JOIN {$this->table_prefix}campaign_lead_appointment cla "
             . '      ON CGLID = FK_CGLID '
             . "LEFT JOIN {$this->table_prefix}client c "
             . '     ON CID = FK_CID '
             . "WHERE "
             . "  CGLAStatus = '".self::APPOINTMENT_OPEN."' "
             . "  AND ( CGStatus = '".self::CAMPAIGN_STATUS_ACTIVE."' "
             . "        OR CGStatus = '".self::CAMPAIGN_STATUS_ARCHIVED."' "
             . '      ) '
             . "  AND c.FK_UID = '".$this->_user->getID()."' " // we want urgent and expired appointments,
                                                              // so we just check client's assigned user id and ignore appointment's user id.
             . '  AND CGLDeleted = 0 ';

    // configure all filters and order values.
    // WARNING: if you change some settings, you may delete data grid session cookies in your
    //          browser to avoid problems with cached filters/orders.
    $filterSelective = array('FK_FID', 'CEmail', 'CGID', 'CCompany', 'CPhone', 'CMobilePhone', 'CAddress', 'CCity', 'CCountry', 'CCreateDateTime', 'CChangeDateTime');

    $filterTypes = array(
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
      'FK_CGSID' => 'selective',
      'FK_UID' => 'selective',
      'CGLADateTime' => 'text',
      'CGLATitle' => 'text',
      'CGLAText' => 'text',
    );
    // set query fields. do not forget to add fields with a valuelist to our $filterSelective array!
    // all query fields must be defined in $filterTypes
    $queryFields[1] = array('type' => 'text', 'value' => 'CFirstname');
    $queryFields[2] = array('type' => 'text', 'value' => 'CLastname');
    $queryFields[3] = array('type' => 'text', 'value' => 'CGLADateTime');
    $queryFields[4] = array('type' => 'text', 'value' => 'CGLAText');
    $queryFields[5] = array('type' => 'selective', 'value' => 'CGID', 'valuelist' => $filterSelective);
    $queryFields[6] = array('type' => 'boolean', 'value' => 'CNewsletterConfirmedRecipient');
    $queryFields[7] = array('type' => 'boolean', 'value' => 'CDataPrivacyConsent');

    $filterFields = $queryFields;
    // add invisible filter settings, may used via http get attributes
    // (e.g. campaign status quicklinks of infocenter) or session
    $filterFields[8] = array('type' => 'selective', 'value' => 'FK_CGSID');
    $filterFields[9] = array('type' => 'selective', 'value' => 'FK_UID');

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
    $prefix = array('config' => 'lb', 'lang' => 'ld', 'session' => 'lb', 'tpl' => 'lb');
    // create data grid object
    $slGrid = new DataGrid($this->db, $this->session, $prefix);

    // may reset old filter settings if new filter is set
    if ($presetFilters) {
      $slGrid->resetFilters();
    }

    // read countries
    $countries = array();
    foreach ($this->_configHelper->getCountries(array('lb_countries', 'countries', ), false, $this->site_id) as $id => $value) {
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
    $slGrid->load($gridSql, $queryFields, $filterFields, $filterTypes, $orders, $page, 0, $presetFilters, '', $customFilters, $customOrders);

    // reset filters and orders if reset btn was clicked
    if ($post->exists('process_reset'))
    {
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
    $this->tpl->load_tpl('content_lb', 'modules/ModuleLeadManagementAppointments.tpl');

    if (is_array($gridData))
    {
      $urgentAppointments = array();
      foreach ($gridData as $id => $value)
      {
        $leadId = $slGrid->get_grid_data($id, 'CGLID');
        $cgId = $slGrid->get_grid_data($id, 'CGID');
        $claUId = $slGrid->get_grid_data($id, 'appointment_creator');
        $gridData[$id]['lb_edit_link'] = 'index.php?action=mod_leadmgmt&amp;action2=client;edit&amp;site='.$this->site_id.'&amp;module=appointments&amp;lead='.$leadId.'&amp;cgid='.$cgId;
        $gridData[$id]['lb_row_bg'] = ($i++%2 ? 'row1' : 'row2');
        $appDateTime = strtotime($slGrid->get_grid_data($id, 'CGLADateTime'));
        $gridData[$id]['lb_row_expired'] = ($appDateTime < time() && $claUId == $this->_user->getID()) ? 'important' : '';
        $gridData[$id]['lb_row_urgent'] = ($claUId != $this->_user->getID()) ? 'warning' : '';
        $gridData[$id]['lb_lead_id'] = $leadId;
        $this->tpl->parse_if('content_lb', 'lb_delete_lead_'.$leadId, $this->_isUserLeadAdmin(), array(
          'lb_delete_link' => '',
        ));
        if ($claUId != $this->_user->getID())
        {
          $urgentAppointments[] = $gridData[$id];
          unset($gridData[$id]);
        }
      }
      // sort grid data: urgent appointments should be on the top of our list
      $gridData = array_merge($urgentAppointments, $gridData);

      $laShowpageTop = $slGrid->load_page_navigation('index.php?action=mod_leadmgmt&amp;action2=appointments&amp;page=','_top');
      $laShowpageBottom = $slGrid->load_page_navigation('index.php?action=mod_leadmgmt&amp;action2=appointments&amp;page=','_bottom');
    }
    else {
      $this->setMessage($gridData);
    }
    $laCurrentSel = $slGrid->get_page_selection();
    $laCurrentRows = $slGrid->get_quantity_selected_rows();

    $this->tpl->parse_if('content_lb', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lb'));
    $this->tpl->parse_if('content_lb', 'order_controls_set', $slGrid->isOrderControlsSet());
    $this->tpl->parse_loop('content_lb', $gridData, 'client_rows');
    $content = $this->tpl->parsereturn('content_lb', array_merge( $laColFilters , $laOrderFields, $laOrderControlFields, array (
      'lb_action'                => 'index.php?action=mod_leadmgmt&amp;action2=appointments',
      'lb_showpage_top'          => $laShowpageTop,
      'lb_showpage_top_label'    => sprintf($_LANG['lb_showpage_top_label'],($laCurrentRows ? $laCurrentSel['begin'] : 0),($laCurrentRows ? $laCurrentSel['end'] : 0)),
      'lb_showpage_bottom'       => $laShowpageBottom,
      'lb_showpage_bottom_label' => sprintf($_LANG['lb_showpage_bottom_label'],($laCurrentRows ? $laCurrentSel['begin'] : 0),($laCurrentRows ? $laCurrentSel['end'] : 0)),
      'lb_count_current'         => $laCurrentRows,
      'lb_count_all'             => $slGrid->get_quantity_total_rows(),
      'lb_request_url'           => 'index.php?action=mod_response_leadmgmt&site='.$this->site_id.'&request=',
    ), $_LANG2['lb']));

    return array(
      'content'             => $content,
      'content_output_mode' => 10,
      'content_output_tpl'  => 'leadmgmt',
    );
  }
}
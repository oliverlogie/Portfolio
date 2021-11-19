<?php

/**
 * Lead management class
 *
 * $LastChangedDate: 2018-03-08 14:17:07 +0100 (Do, 08 Mrz 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleLeadManagement extends AbstractModuleLeadManagement
{
  public static $subClasses = array(
      'clients'      => 'ModuleLeadManagementClients',      // prefix: la
      'client'       => 'ModuleLeadManagementClient',       // prefix: ln
      'appointments' => 'ModuleLeadManagementAppointments', // prefix: lb
      'search'       => 'ModuleLeadManagementSearch',       // prefix: lh
  );

  /**
   * Number of active campaigns
   *
   * @var int
   */
  private $_activeCampaigns = 0;

  /**
   * Number of archived campaigns
   *
   * @var int
   */
  private $_archivedCampaigns = 0;

  /**
   * The cached campaign data.
   *
   * @var array
   */
  private $_cachedCampaignData = null;

  /**
   * Number of clients
   *
   * @var int
   */
  private $_clients = 0;

  /* (non-PHPdoc)
   * @see ContentBase::sendResponse()
   */
  public function sendResponse($request)
  {
    switch($request)
    {
      case 'getTime':
        return $this->_sendResponseAppointmentTime();
        break;
      case 'getStatusHistory':
        return $this->_sendResponseStatusHistory();
        break;
      case 'getAppointmentsOfDate':
        return $this->_sendResponseAppointmentsOfDate();
        break;
      case 'getReminderMessages':
        return $this->_sendResponseReminderMessages();
        break;
      default:
        parent::sendResponse($request);
        break;
    }
  }

  /**
   * Content handler
   */
  public function show_innercontent()
  {
    return $this->_listContent();
  }

  /**
   * Gets a list of all campaign types and their campaigns
   *
   * @return array
   *         Returns module list content and forces page renderer to use
   *         special main template.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    if (!$this->_readCampaignData()) {
      $this->setMessage(Message::createFailure($_LANG['ld_message_no_site_campaigns']));
    }

    $this->tpl->load_tpl('content_ld', 'modules/ModuleLeadManagement_list.tpl');
    $this->tpl->parse_if('content_ld', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ld'));
    $this->tpl->parse_if('content_ld', 'campaigns', $this->_readCampaignData());
    $this->tpl->parse_loop('content_ld', $this->_getCampaignTypes(), 'ld_campaign_types');
    $ld_content = $this->tpl->parsereturn('content_ld', array_merge(array(
      'ld_number_of_clients' => $this->_clients,
      'ld_campaign_stat'     => sprintf($_LANG['ld_campaign_stat'], ($this->_activeCampaigns + $this->_archivedCampaigns),
                                          $this->_activeCampaigns, $this->_archivedCampaigns),
      'ld_request_url'       => 'index.php?action=mod_response_leadmgmt&site='.$this->site_id.'&request=',
    ), $_LANG2['ld']));

    return array(
      'content'             => $ld_content,
      'content_output_mode' => 10,
      'content_output_tpl'  => 'leadmgmt',
    );
  }

  /**
   * Gets a list of all campaign types
   *
   * @return array
   */
  private function _getCampaignTypes()
  {
    global $_LANG;

    $cgTypes = array();
    $post = new Input(Input::SOURCE_POST);
    foreach ($this->_readCampaignData() as $cgTId => $typeGroup)
    {
      // show active campaigns on default
      $showStatus = array (self::CAMPAIGN_STATUS_ACTIVE);
      if ($post->readInt('cg_type_id') == $cgTId) {
        $showStatus = $post->readArrayIntToInt('ld_cg_status');
      }

      $this->tpl->load_tpl('ld_campaigns', 'modules/ModuleLeadManagement_list_campaign.tpl');
      $this->tpl->parse_loop('ld_campaigns', $this->_getCampaigns($cgTId, $showStatus), 'ld_campaigns');
      $cgTypes[] = array(
        'ld_campaigns'                => $this->tpl->parsereturn('ld_campaigns', array()),
        'ld_cg_type_title'            => $typeGroup['name'],
        'ld_cg_type_clients'          => $typeGroup['clients'],
        'ld_cg_type_id'               => $cgTId,
        'ld_show_campaign_type_label' => sprintf($_LANG['ld_show_campaign_type_label'], $typeGroup['name']),
        'ld_action'                   => 'index.php?action=mod_leadmgmt&amp;action2=main&amp;site='.$this->site_id,
        'ld_cg_type_hidden'           => ($post->readInt('cg_type_id') == $cgTId) ? '' : 'style="display:none;visibility:hidden;"',
        'ld_cg_status_1_checked'      => (in_array(self::CAMPAIGN_STATUS_ACTIVE, $showStatus)) ? 'checked="checked"' : '',
        'ld_cg_status_2_checked'      => (in_array(self::CAMPAIGN_STATUS_ARCHIVED, $showStatus)) ? 'checked="checked"' : '',
      );
    }
    return $cgTypes;
  }

  /**
   * Gets a list of campaigns
   *
   * @param int $cgTId
   *        The campaign type id.
   * @param array $showStatus
   *        Contains campaign status constants.
   * @return array
   */
  private function _getCampaigns($cgTId, $showStatus )
  {
    $campaigns = array();
    $data = $this->_readCampaignData();
    foreach ($data[$cgTId]['data'] as $cgId => $cgGroup)
    {
      // ignore this campaign, if status should not be shown
      if (is_array($showStatus) && !in_array($cgGroup['status'], $showStatus)) {
        continue;
      }
      $cgStatus = $this->_getCampaignStatus($cgTId, $cgId);
      $this->tpl->load_tpl('ld_campaigns_list_status', 'modules/ModuleLeadManagement_list_campaign_status.tpl');
      $this->tpl->parse_loop('ld_campaigns_list_status', $cgStatus, 'ld_campaign_lead_status');
      $this->tpl->parse_if('ld_campaigns_list_status', 'ld_campaign_lead_status_available', $cgStatus);
      $campaigns[] = array(
        'ld_cg_status'  => $this->tpl->parsereturn('ld_campaigns_list_status', array()),
        'ld_cg_clients' => $cgGroup['clients'],
        'ld_cg_title'   => $cgGroup['name'],
        'ld_cg_id'      => $cgId,
      );
    }
    return $campaigns;
  }

  /**
   * Gets campaign lead status allocations.
   *
   * @param int $cgTId
   *        The campaign type id.
   * @param int $cgId
   *        The campaign id.
   * @return array
   */
  private function _getCampaignStatus($cgTId, $cgId)
  {
    global $_LANG;

    $percentMaxWidth = (int)ConfigHelper::get('ld_infocenter_percent_max_width');
    $campaignStatus = array();
    $data = $this->_readCampaignData();
    foreach ($data[$cgTId]['data'][$cgId]['data'] as $cgSId  => $statusGroup)
    {
      $percent = $statusGroup['clients'] * 100 / $data[$cgTId]['data'][$cgId]['clients'];
      $rPercent = round($percent);
      $campaignStatus[] = array(
        'ld_cg_lead_status'          => sprintf($_LANG['ld_campaign_lead_status_label'], $statusGroup['name']),
        'ld_cg_status_clients'       => $statusGroup['clients'],
        'ld_cg_status_percent'       => ($percent < 1 && $percent > 0) ? '&lt;1' : $rPercent,
        'ld_cg_status_percent_width' => $rPercent / 100 * $percentMaxWidth,
        'ld_cg_status_percent_max_width' => $percentMaxWidth,
        'ld_cg_status_filter_link' => 'index.php?action=mod_leadmgmt&amp;action2=clients&amp;site='.$this->site_id.'&amp;cgid='.$cgId.'&amp;cgsid='.$cgSId,
      );
    }
    return $campaignStatus;
  }

  /**
   * Gets an array of active and archived campaigns of current site.
   *
   * @return array
   */
  private function _readCampaignData()
  {
    if ($this->_cachedCampaignData !== null)
    {
      return $this->_cachedCampaignData;
    }

    // we do not want to group NULL values, so we generate a random value if CGLID is null.
    $sql = 'SELECT CGTID, CGTName, CGID, CGName, IFNULL(CGLID, CONCAT(rand(), \'edwin\')) as unq_cglid, CGStatus, '
         . '       FK_CID, FK_FUID, '
         .  '      CGSID, CGSName '
         . "FROM {$this->table_prefix}campaign_type AS ct "
         . "INNER JOIN {$this->table_prefix}campaign AS c "
         . '  ON CGTID = FK_CGTID '
         . "LEFT JOIN {$this->table_prefix}campaign_lead cl "
         . '  ON cl.FK_CGID = CGID '
         . "LEFT JOIN {$this->table_prefix}campaign_status "
         . '  ON FK_CGSID = CGSID '
         . 'WHERE CGLDeleted = 0 '
         . '  AND ( '
         . '    CGStatus = '.self::CAMPAIGN_STATUS_ACTIVE.' '
         . '    OR CGStatus = '.self::CAMPAIGN_STATUS_ARCHIVED.' '
         . '  ) '
         . 'GROUP BY unq_cglid, CGTID, CGTName, CGID, CGName, CGStatus, FK_CID, FK_FUID, CGSID, CGSName '
         . 'ORDER BY CGTPosition ASC , CGPosition ASC , CGTID ASC, CGID ASC, CGSID ASC';

    $res = $this->db->query($sql);

    $data = array();
    $this->_activeCampaigns = 0;
    $this->_archivedCampaigns = 0;
    $lastCgTypeId = 0;
    $lastCgId = 0;
    $lastCgStatusId = 0;
    // remember: leads with status id null (FK_CGSID) causes problems, while creating the data array.
    while ($row = $this->db->fetch_row($res))
    {
      // campaign type
      if ($lastCgTypeId != $row['CGTID'])
      {
        $lastCgTypeId = $row['CGTID'];
        $lastCgStatusId = $lastCgId = 0;
        $data[$lastCgTypeId] = array('name' => parseOutput($row['CGTName']), 'clients' => 0, 'data' => array());
      }

      // campaign
      if ($lastCgId != $row['CGID'])
      {
        switch($row['CGStatus'])
        {
          case 1: $this->_activeCampaigns ++;
            break;
          case 2: $this->_archivedCampaigns ++;
            break;
        }
        $lastCgId = $row['CGID'];
        $lastCgStatusId = 0;
        $data[$lastCgTypeId]['data'] += array($lastCgId => array (
          'name' => parseOutput($row['CGName']),
          'clients' => 0,
          'status'  => $row['CGStatus'],
          'data' => array()
        ));
      }

      // campaign lead status
      if ($lastCgStatusId != $row['CGSID'])
      {
        $lastCgStatusId = $row['CGSID'];
        $data[$lastCgTypeId]['data'][$lastCgId]['data'] += array ( $lastCgStatusId => array (
          'name'    => parseOutput($row['CGSName']),
          'clients' => 0,
          'data'    => array(),
        ));
      }

      // if unq_cglid is not an integer, there are no leads/clients in this campaign
      if (is_numeric($row['unq_cglid']))
      {
        $data[$lastCgTypeId]['data'][$lastCgId]['data'][$lastCgStatusId]['data'] =
          array_merge ($data[$lastCgTypeId]['data'][$lastCgId]['data'][$lastCgStatusId]['data'],
            array($row['unq_cglid'] => array (
              'FK_CID'  => (int) $row['FK_CID'],
              'FK_FUID' => (int) $row['FK_FUID'],
            ))
          );

        $data[$lastCgTypeId]['clients'] ++;
        $data[$lastCgTypeId]['data'][$lastCgId]['clients'] ++;
        $data[$lastCgTypeId]['data'][$lastCgId]['data'][$lastCgStatusId]['clients'] ++;
        $this->_clients ++;
      }
    }

    return $this->_cachedCampaignData = $data;
  }

  /**
   * Checks user's input date and gets next free appointment time.
   *
   * @return string
   *         Appointment time or error message on failure.
   */
  private function _sendResponseAppointmentTime()
  {
    global $_LANG;

    header('Content-Type: application/json');

    $input = new Input(Input::SOURCE_REQUEST);
    $date = DateHandler::getValidDate($input->readString('date'), 'Y-m-d');
    if (!DateHandler::isValidDate($date))
    {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => $_LANG['ld_message_choose_date'],
      ));
    }
    else if (!DateHandler::isFutureDateTime($date . '23:59'))
    {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => $_LANG['ld_message_future_date'],
      ));
    }
    $appointmentTime = strtotime($date.' '.ConfigHelper::get('ld_appointment_day_start'));
    $appointmentTimeMax = strtotime($date.' '.ConfigHelper::get('ld_appointment_day_end'));
    $appointmentLunchtimeStart = strtotime($date.' '.ConfigHelper::get('ld_appointment_lunchtime_start'));
    $appointmentLunchtimeEnd = strtotime($date.' '.ConfigHelper::get('ld_appointment_lunchtime_end'));
    $appointmentDuration = intval(ConfigHelper::get('ld_appointment_duration') * 60);
    $sql = ' SELECT CGLAID, CGLADateTime '
         . " FROM {$this->table_prefix}campaign_lead_appointment "
         . " WHERE FK_UID = {$this->_user->getID()} "
         . "   AND CGLADateTime >= '".$date." 00:00:00' "
         . "   AND CGLADateTime <= '".$date." 23:59:59' "
         . "   AND CGLAStatus='".self::APPOINTMENT_OPEN."' "
         . ' ORDER BY CGLADateTime ASC ';
    $result = $this->db->query($sql);
    $takenTimes = array();
    while ($row = $this->db->fetch_row($result)) {
      $takenTimes[] = strtotime($row['CGLADateTime']);
    }
    $appointmentTimeFound = '';
    foreach ($takenTimes as $id => $value)
    {
      if (($appointmentTime + $appointmentDuration) <= $value) {
        $appointmentTimeFound = $appointmentTime;
      }
      else
      {
        $appointmentTime = $value + $appointmentDuration;
        if ($appointmentTime + $appointmentDuration >= $appointmentLunchtimeStart ||
            ($appointmentTime >= $appointmentLunchtimeStart && $appointmentTime <= $appointmentLunchtimeEnd))
          $appointmentTime = $appointmentLunchtimeEnd;
      }
    }
    if (!$appointmentTimeFound)
    {
      if ($appointmentTime + $appointmentDuration <= $appointmentTimeMax) {
        $appointmentTimeFound = $appointmentTime;
      }
      else
      {
        return Json::Encode(array(
            'message' => $_LANG['ld_notime_question_label'],
            'status'  => "1",
            'time'    => date("H:i", $appointmentTimeFound),
          ));
      }
    }
    if ($date == date("Y-m-d") && date("H:i") >= date("H:i", $appointmentTimeFound)) {
      $appointmentTimeFound = time() + 1800;
    }

    return Json::Encode(array(
        'status' => "0",
        'time'   => date("H:i", $appointmentTimeFound),
      ));
  }
}
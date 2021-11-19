<?php

/**
 * Lead management - client class
 *
 * $LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleLeadManagementClient extends AbstractModuleLeadManagement
{
  protected $_prefix = 'ln';

  /**
   * Stores additional lead data
   *
   * @var array
   */
  private $_cachedAdditionalData = null;

  /**
   * Stores open appointments
   *
   * @var array
   */
  private $_cachedAppointments = null;

  /**
   * Stores campaigns, campaign types and campaign data.
   *
   * since 3.1.0 only the selected campaign is cached within this variable.
   *
   * array (
   *  CGID => array (
   *           't_id', 't_name', 'name', 'data' => array (
   *                                                CGDID => array ( 'name',... ) )
   * @var array
   */
  private $_cachedCampaigns = null;

  /**
   * Lead data array. Contains client data
   * and campaign data.
   * This array will be generated in ::_readCampaignData.
   * It is used to store all available lead data and
   * show it, if lead is edited.
   *
   * @var array
   */
  private $_eData = null;

  /**
   * Contains error language titles.
   *
   * @var array
   */
  private $_errorTitles = null;

  /**
   * Id of selected campaign.
   *
   * @var int
   */
  private $_campaignId = 0;

  /**
   * The lead id.
   *
   * @var int
   */
  private $_leadId = 0;

  /**
   * Stores validated form data.
   *
   * @var array
   */
  private $_vData = array();

  /**
   * Is true if client area was saved.
   *
   * @var boolean
   */
  private $_clientDataAreaSaved = false;

  /**
   * Is true if campaign area was saved.
   *
   * @var boolean
   */
  private $_campaignDataAreaSaved = false;

  /**
   * The shortname of the previous module
   *
   * @var string
   */
  private $_previousModuleShortname = '';

  /**
   *
   * @see ModuleLeadManagementClient::_readCompetingCompanies()
   * @var array
   */
  private $_cachedCompetingCompanies;

  /**
   * @var AbstractModel|BackendUser
   */
  private $_currentAssignedLeadAgentUser;

  /**
   * Content handler
   */
  public function show_innercontent()
  {
    global $_LANG;

    if (isset($_FILES['form_element'])) { self::_fixFilesArray($_FILES['form_element']); }
    if (isset($_FILES['campaign_element'])) { self::_fixFilesArray($_FILES['campaign_element']); }

    $get = new Input(Input::SOURCE_GET);
    $post = new Input(Input::SOURCE_POST);

    // export lead data to xls file - get method not in use
    if ($get->readInt('export_xls')) {
      $this->_exportXls();
      exit;
    }
    else if ($get->readInt('deleteUpload')) {
      $this->_campaignId = $get->readInt('cgid');
      $this->_leadId = $get->readInt('lead');
      $this->_deleteUpload($get->readInt('deleteUpload'));
    }

    if ($post->readString('ln_previous_module')) {
       $this->_previousModuleShortname = $post->readString('ln_previous_module');
    }
    else if ($get->readString('module')) {
      $this->_previousModuleShortname = $get->readString('module');
    }
    // Fallback
    else {
      $this->_previousModuleShortname = 'clients';
    }

    $timestamp = date('Y-m-d H:i:s');

    if (isset($_POST['process_cancel'])) {
      $this->_redirectToTarget($this->_previousModuleShortname);
    }
    // edit client
    else if (isset($_POST['process']) && $this->action[0] == 'edit') {
      $this->_editAll();
    }
    // only update status/appointment area
    else if (isset($_POST['process_status']) && $this->action[0] == 'edit') {
      $this->_editStatus($timestamp);
    }
    // update status/appointment area and return to previous module
    else if (isset($_POST['process_status_return']) && $this->action[0] == 'edit') {
      $this->_editStatus($timestamp, true);
    }
    // only update status/appointment area
    else if (isset($_POST['process_client']) && $this->action[0] == 'edit') {
      $this->_editClient($timestamp);
      $this->_clientDataAreaSaved = true;
    }
    // update status/appointment area and return to previous module
    else if (isset($_POST['process_client_return']) && $this->action[0] == 'edit') {
      $this->_editClient($timestamp, true);
    }
    // only update status/appointment area
    else if (isset($_POST['process_campaign']) && $this->action[0] == 'edit') {
      $this->_editCampaign($timestamp);
      $this->_campaignDataAreaSaved = true;
    }
    // update status/appointment area and return to previous module
    else if (isset($_POST['process_campaign_return']) && $this->action[0] == 'edit') {
      $this->_editCampaign($timestamp, true);
    }
    // new client
    else if (isset($_POST['process'])) {
      $this->_createLead();
    }
    // export lead data to xls file
    else if (isset($_POST['process_export'])) {
      $this->_exportXls();
      exit;
    }

    return $this->_getContent();
  }

  protected function _getContentActionBoxButtonsTemplate()
  {
    return 'modules/ModuleLeadManagementClient_action_box.tpl';
  }

  /**
   * Gets competing companies from db and caches it.
   *
   * @param int $cgId
   *        The compaign id.
   *
   * @return array
   */
  protected function _readCompetingCompanies($cgId)
  {
    if (isset($this->_cachedCompetingCompanies[$cgId])) {
      return $this->_cachedCompetingCompanies[$cgId];
    }

    $sql = 'SELECT CGCCID, CGCCValue '
      . "FROM {$this->table_prefix}campaign_competing_company "
      . "WHERE FK_CGID = {$cgId} "
      . 'ORDER BY CGCCPosition ASC ';
    $this->_cachedCompetingCompanies[$cgId] = $this->db->GetAssoc($sql);

    return $this->_cachedCompetingCompanies[$cgId];
  }

  /**
   * Checks if user is allowed to view/edit this client.
   * If user is not allowed to view/edit this client, the user
   * will be redirected to previous page.
   *
   * @param int $leadId
   *        The campaign lead id.
   */
  private function _checkUser($leadId)
  {
    // redirect user if client is not assigned to this user and module "leadmgmt all" is not available
    if (!$this->_isUserLeadAdmin() && $this->_getAssignedLeadAgentUserOfLead($leadId)->id != $this->_user->getID()) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 1));
    }
  }

  /**
   * Creates a new client and stores all data in our database.
   *
   * @return void
   *
   * @throws Exception
   */
  private function _createLead()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $this->_campaignId = $post->readInt('ln_campaign');
    // get campaigns (structure data)
    $sData = $this->_readCampaignData();
    $this->_vData = $this->_getValidatedFormData($this->_campaignId, $sData);
    $now = date('Y-m-d H:i:s');

    // do not save lead/campaign data on form error
    if ($this->_errorTitles) {
      return;
    }

    // stores db query results
    $result = array();
    foreach ($this->_vData as $cgId => $cgData)
    {
      $clientValues = array();
      $cgLeadValues = array();
      $statusId = $post->readInt('ln_status');
      foreach ($cgData['data'] as $eId => $data) {
        if ($data['client']) {
          $clientValues[$data['position']] = $data['prepared_value'] === null ?
            'NULL' : sprintf("'%s'", $this->db->escape($data['prepared_value']));
        }
        else {
          $cgLeadValues[] = "({$eId}, '".$this->db->escape($data['prepared_value'])."'";
        }
      }

      ksort($clientValues);

      // save client data
      $clientId = 0;
      if ($clientValues)
      {
        $sql = " INSERT INTO {$this->table_prefix}client "
             . ' ( '.implode(',', $this->_clientFields).', CCreateDateTime, FK_UID ) '
             . " VALUES ( ".implode(',', $clientValues).", '{$now}', {$this->_user->getID()} ) ";
        $result[] = $this->db->query($sql);
        $clientId = $this->db->insert_id();
      }

      // save campaign lead and set lead id afterwards
      $sql = " INSERT INTO {$this->table_prefix}campaign_lead "
           . " ( FK_CID, FK_CGSID, FK_CGID, CGLDocumentsEMail, CGLDocumentsPost, CGLAppointment, CGLDataOrigin, FK_CGCCID ) "
           . " VALUES ( {$clientId}, {$statusId}, {$cgId}, {$post->readInt('ln_doc_email')}, {$post->readInt('ln_doc_post')}, "
           . "          {$post->readInt('ln_lead_appointment')}, '{$post->readString('ln_data_origin')}', {$post->readInt('ln_competing_companies')} ) ";
      $result[] = $this->db->query($sql);
      $this->_leadId = $this->db->insert_id();

      // update/add status
      $result = array_merge($result, $this->_updateStatus($now));

      // save campaign lead data
      $cgLeadValues = implode(', '.$this->_leadId.'), ', $cgLeadValues);
      $cgLeadValues .= ', '.$this->_leadId.')';
      if ($cgLeadValues)
      {
        $sql = " INSERT INTO {$this->table_prefix}campaign_lead_data "
             . " ( FK_CGDID, CGLDValue, FK_CGLID ) "
             . " VALUES {$cgLeadValues} ";
        $result[] = $this->db->query($sql);
      }
    }

    // may save appointment
    $result = array_merge($result, $this->_updateAppointments());

    if (!array_search(false, $result)) {
      $this->setMessage(Message::createSuccess($_LANG['global_message_create_success']));
      // data saved successfully, delete validated data
      $this->_vData = array();
    }
  }

  /**
   * Delete an uploaded file from client dataset and filesystem
   *
   * @param int $id
   *        the campaign's upload data field id
   *
   * @return void
   *
   * @throws Exception
   */
  private function _deleteUpload($id)
  {
    global $_LANG;

    // check if user allowed to edit this client
    $this->_checkUser($this->_leadId);

    $sData = $this->_readCampaignData();
    $field = $sData[$this->_campaignId]['data'][$id];

    if ($field['type'] == self::FORM_TYPE_UPLOAD) {

      $sql = " SELECT CGLDValue "
           . " FROM {$this->table_prefix}campaign_lead_data "
           . " WHERE FK_CGLID = $this->_leadId "
           . "   AND FK_CGDID = $id ";
      $upload = $this->db->GetOne($sql);

      $sql = " UPDATE {$this->table_prefix}campaign_lead_data "
           . " SET CGLDValue = '' "
           . " WHERE FK_CGLID = $this->_leadId "
           . "   AND FK_CGDID = $id ";
      $this->db->query($sql);

      unlinkIfExists('../' . $upload);

      $this->_eData[$id]['value'] = '';
      $this->setMessage(Message::createSuccess($_LANG['ln_message_edit_lead_success']));
    }
  }

  /**
   * Edits a client and updates all data in our database.
   *
   * @return void
   *
   * @throws Exception
   */
  private function _editAll()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $this->_campaignId = $post->readInt('ln_campaign_id');
    $this->_leadId = $post->readInt('ln_lead_id');
    $now = $timestamp = date('Y-m-d H:i:s');
    // stores db query results
    $result = array();
    // check if user allowed to edit this client
    $this->_checkUser($this->_leadId);

    // if lead id / campaign id is not available show error msg
    if (!$this->_leadId || !$this->_campaignId) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 1));
    }

    $sData = $this->_readCampaignData();
    $this->_vData = $this->_getValidatedFormData($this->_campaignId, $sData);

    // update/add status
    $result = array_merge($result, $this->_updateStatus($now));

    // update/create appointments
    $result = array_merge($result, $this->_updateAppointments($now));

    // do not update lead data on form error(s)
    if ($this->_errorTitles) {
      return;
    }

    // get client id and last status id
    $clientFields = array();
    foreach ($this->_vData[$this->_campaignId]['data'] as $key => $data)
    {
      // client data
      if ($data['client'] === true) {
        $clientFields[] = $this->_clientFields[$data['position']] . "="
                        . ($data['prepared_value'] === null ? 'NULL' : sprintf("'%s'", $this->db->escape($data['prepared_value'])));
      }
      // insert new data. this is mostly the case if a new campaign data field was added,
      // after the client was created.
      else if ($sData[$this->_campaignId]['data'][$key]['new_data'])
      {
        $sql = " INSERT INTO {$this->table_prefix}campaign_lead_data "
             . " ( FK_CGDID, CGLDValue, FK_CGLID ) "
             . " VALUES ('{$key}', '{$this->db->escape($data['prepared_value'])}','{$this->_leadId}' ) ";
        $result[] = $this->db->query($sql);
      }
      // update campaign data
      else
      {
        if ($sData[$this->_campaignId]['data'][$key]['type'] == self::FORM_TYPE_UPLOAD) {
          $position = $sData[$this->_campaignId]['data'][$key]['position'];

          // ignore upload fields if no new file has been uploaded
          if (   !isset($_FILES['campaign_element'][$position])
              || !$_FILES['campaign_element'][$position]['size']
              || $_FILES['campaign_element'][$position]['error']
          ) {
            continue;
          }

          $data['prepared_value'] = $this->_storeClientUpload($_FILES['campaign_element'][$position]);
        }

        $sql = " UPDATE {$this->table_prefix}campaign_lead_data "
             . " SET CGLDValue = '{$this->db->escape($data['prepared_value'])}' "
             . " WHERE FK_CGLID = {$this->_leadId} "
             . "   AND FK_CGDID = {$key} ";
        $result[] = $this->db->query($sql);
      }
    }

    $lastStatus = $this->_readLastStatusData();
    $this->_addStatusHistoryEntry(
      $this->_leadId,
      $this->_campaignId,
      $_LANG['ld_lead_edited'],
      $lastStatus['id'],
      $timestamp
    );

    // add entry to manipulation log
    $result[] = $this->_addManipulatedLogEntry($this->_leadId, $now);
    $sql = ' SELECT FK_CID, FK_CGSID '
         . " FROM {$this->table_prefix}campaign_lead "
         . " WHERE CGLID = {$this->_leadId} ";
    $leadRow = $this->db->GetRow($sql);

    $setUser = ($post->readInt('ln_user')) ? ' , FK_UID = '.$this->db->escape($post->readInt('ln_user')).' ' : '';
    $this->_onEditLeadAgentAssignment($post->readInt('ln_user'), $timestamp);

    // update client data
    $sql = " UPDATE {$this->table_prefix}client "
         . ' SET '.implode(',', $clientFields).', '
         . "     CChangeDateTime = '{$now}' "
         . "     {$setUser}  "
         . " WHERE CID = {$leadRow['FK_CID']} ";
    $result[] = $this->db->query($sql);

    // lead update
    $statusId = $post->readInt('ln_status');
    $sql = " UPDATE {$this->table_prefix}campaign_lead "
         . " SET FK_CGSID = {$statusId}, "
         . "     CGLDocumentsEMail = {$post->readInt('ln_doc_email')}, "
         . "     CGLDocumentsPost = {$post->readInt('ln_doc_post')}, "
         . "     CGLAppointment = {$post->readInt('ln_lead_appointment')}, "
         . "     CGLDataOrigin = '{$post->readString('ln_data_origin')}', "
         . "     FK_CGCCID = {$post->readInt('ln_competing_companies')} "
         . " WHERE CGLID = {$this->_leadId} ";
    $result[] = $this->db->query($sql);

    if (!array_search(false, $result)) {
      if ($this->_redirectAfterProcessingRequested('previous_submodule')) {
        // lead edited successfully
        $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 2));
      }
      else {
        $this->setMessage(Message::createSuccess(sprintf($_LANG['ld_message_lead_edit_success'])));
      }
    }
  }

  /**
   * Edits the campaign data part of a lead.
   *
   * @param string  $timestamp
   *        Date-Time value for database entries
   * @param boolean $return
   *        User will be redirected to previous module, if set true.
   *
   * @return boolean
   *         True on success, false otherwise.
   * @throws Exception
   */
  private function _editCampaign($timestamp, $return=false)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $this->_campaignId = $post->readInt('ln_campaign_id');
    $this->_leadId = $post->readInt('ln_lead_id');

    // stores db query results
    $result = array();
    // check if user allowed to edit this client
    $this->_checkUser($this->_leadId);

    // if lead id / campaign id is not available show error msg
    if (!$this->_leadId || !$this->_campaignId) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 1));
    }

    $sData = $this->_readCampaignData();
    $this->_vData = $this->_getValidatedFormData($this->_campaignId, $sData ,'campaign');

    // error message, if error titles available.
    // usually set during validating form data
    if ($this->_errorTitles)
    {
      $this->setMessage(Message::createFailure(sprintf($_LANG['ln_message_save_form_errors'], implode(', ', $this->_errorTitles))));
      return false;
    }

    foreach ($this->_vData[$this->_campaignId]['data'] as $key => $data)
    {
      // ignore client data
      if ($data['client'] === true) {
        continue;
      }

      // insert new data. this is mostly the case if a new campaign data field was added via DB,
      // after the client was created.
      if ($sData[$this->_campaignId]['data'][$key]['new_data'])
      {
        $sql = " INSERT INTO {$this->table_prefix}campaign_lead_data "
             . " ( FK_CGDID, CGLDValue, FK_CGLID ) "
             . " VALUES ('{$key}', '{$this->db->escape($data['prepared_value'])}','{$this->_leadId}' ) ";
        $result[] = $this->db->query($sql);
      }
      // update campaign data
      else
      {
        if ($sData[$this->_campaignId]['data'][$key]['type'] == self::FORM_TYPE_UPLOAD) {
          $position = $sData[$this->_campaignId]['data'][$key]['position'];

          // ignore upload fields if no new file has been uploaded
          if (   !isset($_FILES['campaign_element'][$position])
              || !$_FILES['campaign_element'][$position]['size']
              || $_FILES['campaign_element'][$position]['error']
          ) {
            continue;
          }

          $data['prepared_value'] = $this->_storeClientUpload($_FILES['campaign_element'][$position]);
        }

        $sql = " UPDATE {$this->table_prefix}campaign_lead_data "
             . " SET CGLDValue = '{$this->db->escape($data['prepared_value'])}' "
             . " WHERE FK_CGLID = {$this->_leadId} "
             . "   AND FK_CGDID = {$key} ";
        $result[] = $this->db->query($sql);
      }
    }

    // add entry to manipulation log
    $result['log'] = $this->_addManipulatedLogEntry($this->_leadId, $timestamp);

    $lastStatus = $this->_readLastStatusData();
    $this->_addStatusHistoryEntry(
      $this->_leadId,
      $this->_campaignId,
      $_LANG['ld_campaign_data_edited'],
      $lastStatus['id'],
      $timestamp
    );

    // lead edited successfully
    if ($return) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 2));
    }
    // lead edited successfully, but do not return
    else {
      $this->setMessage(Message::createSuccess($_LANG['ln_message_edit_campaign_success']));
      return true;
    }
  }

  /**
   * Edits the client data part of a lead.
   *
   * @param string  $timestamp
   *        Date-Time value for database entries
   * @param boolean $return
   *        User will be redirected to previous module, if set true.
   *
   * @return boolean
   *         True on success, false otherwise.
   * @throws Exception
   */
  private function _editClient($timestamp, $return=false)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $this->_campaignId = $post->readInt('ln_campaign_id');
    $this->_leadId = $post->readInt('ln_lead_id');

    // stores db query results
    $result = array();
    // check if user allowed to edit this client
    $this->_checkUser($this->_leadId);

    // if lead id / campaign id is not available show error msg
    if (!$this->_leadId || !$this->_campaignId) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 1));
    }

    $sData = $this->_readCampaignData();
    $this->_vData = $this->_getValidatedFormData($this->_campaignId, $sData, 'client');

    // error message, if error titles available.
    // usually set during validating form data
    if ($this->_errorTitles)
    {
      $this->setMessage(Message::createFailure(sprintf($_LANG['ln_message_save_form_errors'], implode(', ', $this->_errorTitles))));
      return false;
    }

    $clientFields = array();
    foreach ($this->_vData[$this->_campaignId]['data'] as $key => $data) {
      if ($data['client'] === true) {
        $clientFields[] = $this->_clientFields[$data['position']] . "="
                        . ($data['prepared_value'] === null ? 'NULL' : sprintf("'%s'", $this->db->escape($data['prepared_value'])));
      }
    }

    // get client id and last status id
    $sql = ' SELECT FK_CID '
         . " FROM {$this->table_prefix}campaign_lead "
         . " WHERE CGLID = {$this->_leadId} ";
    $cId = $this->db->GetOne($sql);

    $setUser = ($post->readInt('ln_user')) ? ' , FK_UID = '.$this->db->escape($post->readInt('ln_user')).' ' : '';
    $this->_onEditLeadAgentAssignment($post->readInt('ln_user'), $timestamp);

    // update client data
    $sql = " UPDATE {$this->table_prefix}client "
         . ' SET '.implode(',', $clientFields).', '
         . "     CChangeDateTime = '{$timestamp}' "
         . "     {$setUser}  "
         . " WHERE CID = {$cId} ";
    $result[] = $this->db->query($sql);

    // lead - additional data update
    $sql = " UPDATE {$this->table_prefix}campaign_lead "
         . " SET CGLDocumentsEMail = {$post->readInt('ln_doc_email')}, "
         . "     CGLDocumentsPost = {$post->readInt('ln_doc_post')}, "
         . "     CGLAppointment = {$post->readInt('ln_lead_appointment')}, "
         . "     CGLDataOrigin = '{$post->readString('ln_data_origin')}', "
         . "     FK_CGCCID = {$post->readInt('ln_competing_companies')} "
         . " WHERE CGLID = {$this->_leadId} ";
    $result[] = $this->db->query($sql);

    // add entry to manipulation log
    $result['log'] = $this->_addManipulatedLogEntry($this->_leadId, $timestamp);

    $lastStatus = $this->_readLastStatusData();
    $this->_addStatusHistoryEntry(
      $this->_leadId,
      $this->_campaignId,
      $_LANG['ld_client_data_edited'],
      $lastStatus['id'],
      $timestamp
    );

    // lead edited successfully
    if ($return) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 2));
    }
    // lead edited successfully, but do not return
    else {
      $this->setMessage(Message::createSuccess($_LANG['ln_message_edit_lead_success']));
      return true;
    }
  }

  /**
   * Edits the status/appointment part of a lead.
   *
   * @param string  $timestamp
   *        Date-Time value for database entries
   * @param boolean $return
   *        User will be redirected to previous module, if set true.
   *
   * @return boolean
   *         True on success, false otherwise.
   * @throws Exception
   */
  private function _editStatus($timestamp, $return=false)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $this->_campaignId = $post->readInt('ln_campaign_id');
    $this->_leadId = $post->readInt('ln_lead_id');
    // stores db query results
    $result = array();
    $statusId = $post->readInt('ln_status');
    // check if user allowed to edit this client
    $this->_checkUser($this->_leadId);

      // if lead id / campaign id is not available show error msg
    if (!$this->_leadId || !$this->_campaignId) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 1));
    }

    // update/add status
    $result = array_merge($result, $this->_updateStatus($timestamp));

    // update/create appointments
    $result = array_merge($result, $this->_updateAppointments($timestamp));

    if (array_search(false, $result)) {
      return false;
    }

    // error message, if error titles available.
    // usually set on appointment update
    if ($this->_errorTitles)
    {
      $this->setMessage(Message::createFailure(sprintf($_LANG['ln_message_save_form_errors'], implode(', ', $this->_errorTitles))));
      return false;
    }

    // no result means that there was no update/insert.
    // return if "save&return" btn was clicked
    if (empty($result) && $return)
    {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 3));
    }
    // just print error message, if there was no update/insert
    else if (empty($result))
    {
      $this->setMessage(Message::createFailure($_LANG['ln_message_edit_status_no_data']));
      return false;
    }

    // lead update
    $sql = " UPDATE {$this->table_prefix}campaign_lead "
         . " SET FK_CGSID = {$statusId} "
         . " WHERE CGLID = {$this->_leadId} ";
    $result['lead'] = $this->db->query($sql);

    // add entry to manipulation log
    $result['log'] = $this->_addManipulatedLogEntry($this->_leadId, $timestamp);

    // lead edited successfully
    if ($return) {
      $this->_redirectToTarget($this->_previousModuleShortname, array('leadedit' => 2));
    }
    // lead edited successfully, but do not return
    else {
      $this->setMessage(Message::createSuccess($_LANG['ln_message_edit_status_success']));
      return true;
    }
  }

  /**
   * Generates an XLS File Output of the current edited lead
   *
   * @return string
   *        Parsed XLS Output
   *
   * @throws Exception
   */
  private function _exportXls()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $this->_leadId = $get->readInt('lead');
    $this->_campaignId = $get->readInt('cgid');
    $fileName = sprintf($_LANG['ln_export_file_name'], $this->_leadId, date("Ymd-Hi"));
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: inline; filename="'.$fileName.'.xls"');
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');
    $sData = $this->_readCampaignData();

    if (!$this->_eData) {
      return;
    }
    $outputColTitles = html_entity_decode(strip_tags($_LANG['ln_internal_id']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";;
    $outputData = $this->_leadId."\t";
    // client & campaign data
    foreach ($sData[$this->_campaignId]['data'] as $id => $data)
    {
      $outputColTitles .= html_entity_decode(strip_tags($data['name']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
      switch($data['type'])
      {
        case self::FORM_TYPE_TEXT:
        case self::FORM_TYPE_TEXTAREA:
        case self::FORM_TYPE_CHECKBOX:
          $outputData .= $this->_eData[$id]['value'];
          break;
        case self::FORM_TYPE_COMBOBOX:
          $options = array();
          $selection = 0;
          // country selection
          if ($data['predefined'] == self::PREDEFINED_COUNTRY) {
            $options = $this->_configHelper->getCountries(array('ln_countries', 'countries', ), false, $this->site_id);
            $selection = $this->_eData[$id]['value'];
          }
          // custom selection list values
          else if ($data['value']) {
            $options = explode('$', $data['value']);
            $selection = $this->_eData[$id]['value']-1;
          }
          $outputData .= (isset($options[$selection])) ? $options[$selection] : '';
          break;
        case self::FORM_TYPE_CHECKBOXGROUP:
        case self::FORM_TYPE_RADIOBUTTON:
          $options = explode('$', $data['value']);
          $selected = ($this->_eData[$id]['value']) ? explode('$', $this->_eData[$id]['value']) : array();
          $pieces = array();
          foreach ($selected as $key) {
            $pieces[] = $options[$key-1];
          }
          $outputData .= implode(', ', $pieces);
          break;
        case self::FORM_TYPE_UPLOAD:
          $outputData .= $data['value'];
          break;
        default: throw new Exception('Form element not defined!');
      }
      $outputData .= "\t";
    }
    // lead create-, changedate
    $outputColTitles .= html_entity_decode(strip_tags($_LANG['ln_create_date_time']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
    $outputData .= $this->_eData['data']['create_date_time']."\t";
    $outputColTitles .= html_entity_decode(strip_tags($_LANG['ln_change_date_time']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
    $outputData .= $this->_eData['data']['change_date_time']."\t";
    // campaign name:
    $outputColTitles .= html_entity_decode(strip_tags($_LANG['ln_campaign_label']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
    $outputData .= $sData[$this->_campaignId]['name']."\t";
    // campaign status:
    $outputColTitles .= html_entity_decode(strip_tags($_LANG['ln_lead_status_label']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
    $outputData .= $this->_eData['data']['status_name']."\t";
    $outputColTitles .= html_entity_decode(strip_tags($_LANG['ln_lead_status_text_label']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
    $outputData .= $this->_eData['data']['status_text']."\t";
    // assigned user
    $outputColTitles .= html_entity_decode(strip_tags($_LANG['ln_assigned_user']), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
    $outputData .= $this->_eData['data']['u_firstname'].' '.$this->_eData['data']['u_lastname']."\t";
    // additional data:
    $additionalData = $this->_readAdditionalData(true);
    foreach ($additionalData as $label => $value)
    {
      $outputColTitles .= html_entity_decode(strip_tags($_LANG[$label]), ENT_COMPAT, ConfigHelper::get('charset'))."\t";
      $outputData .= $value."\t";
    }
    // remove line breaks, they would destroy the xls file
    $replaceChars = array("\r\n", "\r", "\n");
    foreach ($replaceChars as $replace) {
      $outputData = str_replace($replace, " ", $outputData);
    }
    echo $outputColTitles."\n".html_entity_decode($outputData, ENT_COMPAT, ConfigHelper::get('charset'));
  }

  /**
   * Generates default client data element.
   *
   * @param int $pos
   *        Position of element.
   *
   * @return array
   */
  private function _generateDefaultDataElement($pos)
  {
    global $_LANG;

    $predefined = 0;
    $validate = 0;
    $type = self::FORM_TYPE_TEXT;
    switch($pos) {
      case 3: // salutation
        $type = self::FORM_TYPE_COMBOBOX;
        break;
      case 7: // birthday
        $predefined = self::PREDEFINED_DATE;
        $validate = Validation::BIRTHDAY;
        break;
      case 8: // country
        $type = self::FORM_TYPE_COMBOBOX;
        $predefined = self::PREDEFINED_COUNTRY;
        break;
      case 13: // email
        $validate = Validation::EMAIL;
        break;
      case 14: // newsletter
        $type = self::FORM_TYPE_CHECKBOX;
        break;
      case 16: // data privacy consent
        $type = self::FORM_TYPE_CHECKBOX;
        break;
    }

    return array (
      'type'       => $type,
      // if you want to use another default name, create this element via db (campaign_data)
      'name'       => $_LANG[$this->_prefix.'_element_labels'][$pos],
      'value'      => ($pos == 3) ? implode('$', $_LANG[$this->_prefix.'_foas']) : '',
      'required'   => false,
      'dependency' => 0,
      'position'   => $pos,
      'validate'   => $validate,
      'predefined' => $predefined,
      'prechecked' => '',
      'min_length' => 0,
      'max_length' => 0,
      'min_value'  => 0,
      'max_value'  => 0,
      'client'     => true,
    );
  }

  /**
   * Generates data element array.
   * It is used if a lead should be edited.
   *
   * @param int     $pos
   *        Data element position.
   * @param string  $val
   *        Data element value.
   * @param boolean $client
   *        True if data row is client data.
   * @param boolean $newData [optional] [defaul:false]
   *        True if client already exists and campaign was changed afterwards.
   * @param string  $predefined [optional]
   *
   * @return array
   */
  private function _generateEditDataArray($pos, $val, $client, $newData=false, $predefined=null)
  {
    // convert birthday date format
    if (($pos == 7) && $client || $predefined == self::PREDEFINED_DATE) {
      if (strtotime($val) > 0) {
        $val = date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ln'), strtotime($val));
      }
      else {
        $val = '';
      }
    }

    return array (
      'position' => $pos,
      'value' => $val,
      'client' => $client,
      'name' => '',
      'err' => false,
      'err_msg' => array(),
      'err_pos' => 0,
      'new_data' => $newData, // true if client already exists and campaign data was changed afterwards.
    );
  }

  /**
   * Generates an array with form elements to parse into a template.
   *
   * @param array $sData
   *        Strucuture data.
   * @param array $vData
   *        Contains validated form data.
   *
   * @return array
   *         All client form elements.
   *
   * @throws Exception
   */
  private function _getClientFormElements($sData, $vData)
  {
    $formElements = array();
    $elementPrefix = 'client';

    // return empty array, if there is no structure data
    if (!$sData) {
      return array();
    }

    // use lead data if available
//    if ($this->_eData && !$vData) {
//      $vData = $this->_eData;
//    }
    foreach ($sData['data'] as $eId => $data)
    {
      // ignore campaign data
      if ($data['client'] === false) {
        continue;
      }

      $formElements[] = $this->_getFormElement($vData, $data, $eId, $elementPrefix);
    }

    return $formElements;
  }

  /**
   * Generates an array with form elements to parse into a template.
   *
   * @param array $sData
   *        Strucuture data.
   * @param array $vData
   *        Contains validated form data.
   *
   * @return array
   *         All campaign form elements.
   *
   * @throws Exception
   */
  private function _getCampaignFormElements($sData, $vData)
  {
    $formElements = array();
    $elementPrefix = 'campaign';

    // return empty array, if there is no structure data
    if (!$sData) {
      return array();
    }

    foreach ($sData['data'] as $eId => $data)
    {
      // ignore client data
      if ($data['client'] === true) {
        continue;
      }

      $formElements[] = $this->_getFormElement($vData, $data, $eId, $elementPrefix);
    }

    return $formElements;
  }

  /**
   * Generates an array of form field elements to use in templates.
   *
   * @param array  $vData
   *        Contains validated form data.
   * @param array  $data
   *        Predefined campaign data row.
   * @param        $eId
   * @param string $elementPrefix
   *        Template form field (name and id) prefix.
   *
   * @return array
   *
   * @throws Exception
   */
  private function _getFormElement($vData, $data, $eId, $elementPrefix)
  {
    // element/field id
    $ePos = $data['position'];
    // submitted form data value / available lead data of db
    $currentValue = (isset($vData[$eId]['value'])) ? $vData[$eId]['value'] : '';
    // set predefined campaign data value (e.g. checkbox/radiobutton label, select options, etc.)
    $vDataValue = $data['value'];

    // always take validated (posted) value first
    if (isset($vData[$eId]['value']) || (isset($vData[$eId]) && $vData[$eId]['value'] == null)) {
      $value = ($vData[$eId]['value']) ? $vData[$eId]['value'] : '';
    }
    // take db (saved) value
    else if (isset($this->_eData['data']) && isset($this->_eData[$eId]['value'])) {
      $value = $this->_eData[$eId]['value'];
    }
    // take prechecked values (for checkboxgroups and radiobutton)
    else if ($data['prechecked'] || in_array($data['type'], array(self::FORM_TYPE_CHECKBOXGROUP, self::FORM_TYPE_RADIOBUTTON))) {
      $value = $data['prechecked'];
    }
    // at least take predefined value
    else {
      $value = $data['value'];
    }

    // load default form elements
    switch($data['type'])
    {
      case self::FORM_TYPE_TEXT:
        $class = '';
        if ($data['predefined'] == self::PREDEFINED_DATE)
        {
          $class = 'ln_datepicker';
          if ($data['validate'] == Validation::BIRTHDAY) {
            $class = 'ln_datepicker_birthday';
          }
        }
        $formElement = $this->_getFormTextfield($ePos, $data['max_length'], $value, $elementPrefix, $class);
        break;
      case self::FORM_TYPE_TEXTAREA:
        $formElement = $this->_getFormTextarea($ePos, $data['max_length'], $value, $elementPrefix);
        break;
      case self::FORM_TYPE_COMBOBOX:
        $options = array();
        // country selection
        if ($data['predefined'] == self::PREDEFINED_COUNTRY) {
          $options = $this->_configHelper->getCountries(array('ln_countries', 'countries', ), false, $this->site_id);
        } else if ($vDataValue) {
          $options = explode('$', $vDataValue);
        }
        $formElement = $this->_getFormCombobox($ePos, $options, $value, $elementPrefix);
        break;
      case self::FORM_TYPE_CHECKBOX:
        $formElement = $this->_getFormCheckbox($ePos, $value, $elementPrefix, $data['position'] == 14 && $data['client']);
        break;
      case self::FORM_TYPE_CHECKBOXGROUP: $formElement = $this->_getFormCheckboxGroup($ePos, $vDataValue, $value, $elementPrefix);
        break;
      case self::FORM_TYPE_RADIOBUTTON: $formElement = $this->_getFormRadiobutton($ePos, $vDataValue, $value, $elementPrefix);
        break;
      case self::FORM_TYPE_UPLOAD:
        $value = $currentValue ?: $this->_eData[$eId]['value'];
        $url = 'index.php?action=mod_leadmgmt&amp;action2=client;'.$this->action[0].'&amp;site='.$this->site_id.'&amp;lead='.$this->_leadId.'&amp;cgid='.$this->_campaignId . '&deleteUpload=' . $eId;
        $formElement = $this->_getFormUpload($ePos, $value, $url, $elementPrefix);
        break;
      default: throw new Exception('Form element not defined!');
    }

    $required = ($data['required']) ? ' *' : '';

    return array (
      'ln_element_label' => $data['name'].$required,
      'ln_element'       => $formElement,
      'ln_element_id'    => $elementPrefix.'_element_'.$ePos,
      'ln_element_error_label' => ($vData && isset($vData[$eId]) && $vData[$eId]['err']) ? 'ln_error_label' : '',
      'ln_element_error_field' => ($vData && isset($vData[$eId]) && $vData[$eId]['err']) ? 'ln_error_field' : '',
      'ln_element_error' => ($vData && isset($vData[$eId]) && $vData[$eId]['err']) ? 'has-error' : '',
    );
  }

  /**
   * Gets error message array of validated form fields.
   *
   * @param array $vData
   *        The validated form data.
   * @param string $prefix
   *        The prefix to use in templates.
   * @return array
   *         Contains error messages.
   */
  private function _getMessageArray($vData, $prefix)
  {
    $err = array('client' => array(), 'campaign' => array());
    foreach ($vData as $eId => $data)
    {
      if ($data['err_msg'])
      {
        $arrayKey = ($data['client']) ? 'client' : 'campaign';
        if ($data['err_pos'] == 0)
        {
          foreach ($data['err_msg'] as $msg)
          {
            array_unshift($err[$arrayKey], array($prefix.'_message' => $msg));
          }
        }
        else
        {
          foreach ($data['err_msg'] as $msg)
          {
            $err[$arrayKey][] = array ($prefix.'_message' => $msg);
          }
        }
      }
    }
    return $err;
  }

  /**
   * Validates form data of given campaign form id.
   *
   * @param int    $cId
   * @param array  $sData
   * @param string $formDataOnly
   *        Set to validate only given form data (client/campaign)
   *
   * @return array
   *         Validated data. Contains validated campaign data fields:
   *         array ( campaign-id => array ('data' => array ( campaign-data-id =>
   *         'name' => '', 'position' => '', ... ) ) )
   */
  private function _getValidatedFormData($cId, $sData, $formDataOnly='')
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $vData = array($cId => array('data' => array()));
    $clientData = $post->readMultipleArrayIntToString('client_element');
    $campaignData = $post->readMultipleArrayIntToString('campaign_element');
    $incomplete = array ( 'client' => false, 'campaign' => false );
    $additionalValidationRequired = false;
    foreach ($sData[$cId]['data'] as $eId => $element)
    {
      $ePos = $element['position'];
      $eName = $element['name'];
      $formData = ($element['client']) ? $clientData : $campaignData;
      $formType = ($element['client']) ? 'client' : 'campaign';
      $errMsg = array();
      $errPos = $ePos;
      $error = false;

      // ignore campaign/client data
      if ($formDataOnly && $formDataOnly != $formType) {
        continue;
      }
      // read the source variable
      switch($element['type'])
      {
        case self::FORM_TYPE_COMBOBOX:
          $elementValue = (isset($formData[$ePos]) && $formData[$ePos] != 0) ? $formData[$ePos] : null;
          break;
        case self::FORM_TYPE_RADIOBUTTON:
        case self::FORM_TYPE_CHECKBOX:
          $elementValue = (isset($formData[$ePos])) ? $formData[$ePos] : null;
          break;
        case self::FORM_TYPE_CHECKBOXGROUP:
          $elementValue = (isset($formData[$ePos])) ? implode('$', $formData[$ePos]) : null;
          break;
        case self::FORM_TYPE_UPLOAD:
          $file = isset($_FILES['form_element'][$ePos]) ? $_FILES['form_element'][$ePos] : (isset($_FILES['campaign_element'][$ePos]) ? $_FILES['campaign_element'][$ePos] : null);
          if (isset($file['name']) && $file['size'] > 0) { // upload available
            $size = (int)$element['filesize'];
            $types = $element['filetypes'];
            if (!Validation::fileVerify($file, $size, $types)) {
              $errorSize = !Validation::filesize($file, $size);
              $errorType = !Validation::filetypeByExtension($file, $types);
              if ($errorSize) {
                $incomplete[$formType] = true;
                $errMsg[] = sprintf($_LANG['global_message_upload_file_size_error'],
                  formatFileSize($this->_getMaximumFilesize($size)));
              }
              if ($errorType) {
                $errMsg[] = $_LANG['global_message_upload_type_error'];
              }
              if (!$errorType && !$errorSize) {
                $errMsg[] = $_LANG['global_message_upload_general_error'];
              }
            }
            $elementValue = $file['name'];
          }
          else {
            $elementValue = '';
          }
          break;
        default: $elementValue = $formData[$ePos];
      }

      // check if element is required. checkbox elements are null, if not checked
      if ($element['required'] && ($elementValue === null || !Validation::required($elementValue)) && $element['type'] != self::FORM_TYPE_UPLOAD)
      {
        $error = true;

        if (!ConfigHelper::get('ln_validation_required_field_message_summary')) {
          $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_required_field'], $eName);
          $incomplete[$formType] = true;
        }

        if (!$incomplete[$formType] && ConfigHelper::get('ln_validation_required_field_message_summary'))
        {
          $errMsg[] = $_LANG[$this->_prefix.'_message_incomplete_input'];
          // error message should be placed at top of message stack
          $errPos = 0;
          $incomplete[$formType] = true;
        }
      }

      // number validation
      if (trim($elementValue) !== '' && $element['validate'] == Validation::NUMBER && !Validation::isNumber($elementValue)) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_input_number'], $eName);
      }
      // email validation
      else if (trim($elementValue) !== '' && $element['validate'] == Validation::EMAIL && !Validation::isEmail($elementValue)) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_mail'], $eName);
      }
      // date validation
      else if (trim($elementValue) !== '' && $element['validate'] == Validation::DATE && !DateHandler::isValidDate($elementValue)) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_date'], $eName);
      }
      // birthday validation
      else if (trim($elementValue) !== '' && $element['validate'] == Validation::BIRTHDAY)
      {
        if (!DateHandler::isValidDate($elementValue)) {
          $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_birthday'], $eName);
        }
        else if (DateHandler::isFutureDateTime($elementValue)) {
          $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_future_birthday'], $eName);
        }
      }
      // time validation
      else if (trim($elementValue) !== '' && $element['validate'] == Validation::TIME && !DateHandler::isValidTime($elementValue)) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_time'], $eName);
      }

      // min. length validation
      if (trim($elementValue) !== '' && $element['min_length'] > 0 && !Validation::minLength($elementValue, $element['min_length'])) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_min_length'], $eName, $element['min_length']);
      }

      // max. length validation
      if (trim($elementValue) !== '' && $element['max_length'] > 0 && !Validation::maxLength($elementValue, $element['max_length'])) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_max_length'], $eName, $element['max_length']);
      }

      // max. value validation
      if (trim($elementValue) !== '' && $element['max_value'] > 0 && !Validation::maxValue($elementValue, $element['max_value'])) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_max_value'], $eName, $element['max_value']);
      }

      // min. value validation
      if (trim($elementValue) !== '' && $element['min_value'] > 0 && !Validation::minValue($elementValue, $element['min_value'])) {
        $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_invalid_min_value'], $eName, $element['min_value']);
      }

      // check dependency - many fields depend on one field
      if (isset($element['dependency'])) {
        // We can not do the dependency validation here, because we need all validated fields.
        $additionalValidationRequired = true;
      }

      if ($error || $errMsg)
      {
        if ($element['client'] && !isset($this->_errorTitles[$_LANG['ln_client_data_title']])) {
          $this->_errorTitles[$_LANG['ln_client_data_title']] = $_LANG['ln_client_data_title'];
        }
        else if (!$element['client'] && !isset($this->_errorTitles[$_LANG['ln_campaign_data_title']])) {
          $this->_errorTitles[$_LANG['ln_campaign_data_title']] =  $_LANG['ln_campaign_data_title'];
        }
      }

      // Ensure to format all DATE field types in ISO format: Y-m-d
      // This is required, as formats as d.m.Y (among others) pass validation
      // but should be stored consistently in database
      if ($element['predefined'] == self::PREDEFINED_DATE || $element['position'] == 7 && $element['client']) {
        $preparedValue = $elementValue ? DateHandler::getValidDate($elementValue, 'Y-m-d') : null;
      }
      else {
        // ensure zero instead of null value for some field types to avoid
        // database errors when trying to insert NULL into a non-nullable field
        switch ($element['type']) {
          case self::FORM_TYPE_COMBOBOX:
          case self::FORM_TYPE_RADIOBUTTON:
          case self::FORM_TYPE_CHECKBOX:
            $preparedValue = $elementValue ?: 0;
            break;
          default:
            $preparedValue = $elementValue;
            break;
        }
      }

      $vData[$cId]['data'][$eId] = array(
        'position'       => $ePos,
        'name'           => $eName,
        'value'          => $elementValue,
        'client'         => $element['client'],
        'prepared_value' => $preparedValue,
        'err'            => ($error || $errMsg) ? true : false,
        'err_msg'        => $errMsg,
        'err_pos'        => $errPos,
      );
    }

    // May do additional validation, if required.
    if ($additionalValidationRequired) {
      foreach ($sData[$cId]['data'] as $eId => $element) {

        $formType = ($element['client']) ? 'client' : 'campaign';
        // ignore campaign/client data
        if ($formDataOnly && $formDataOnly != $formType) {
          continue;
        }

        $errMsg = $vData[$cId]['data'][$eId]['err_msg'];
        $eName = $element['name'];
        $eVal = $vData[$cId]['data'][$eId]['value'];
        $dPos = $element['dependency'];
        $dependedElement = $this->_getElementWithPosition($vData[$cId]['data'], $dPos);
        // we need to check if current element has got a value,
        // if referenced element is checked, selected or whatever.
        // to do this we check if element is required. checkbox elements are null, if not checked
        if ($dependedElement['value'] && ($eVal === null || !Validation::required($eVal))) {
          $errMsg[] = sprintf($_LANG[$this->_prefix.'_message_dependency_failure'], $eName, $dependedElement['name']);
        }

        if ($errMsg) {
          if ($element['client'] && !isset($this->_errorTitles[$_LANG['ln_client_data_title']])) {
            $this->_errorTitles[$_LANG['ln_client_data_title']] = $_LANG['ln_client_data_title'];
          }
          else if (!$element['client'] && !isset($this->_errorTitles[$_LANG['ln_campaign_data_title']])) {
            $this->_errorTitles[$_LANG['ln_campaign_data_title']] =  $_LANG['ln_campaign_data_title'];
          }
        }
        $vData[$cId]['data'][$eId]['err'] = ($errMsg) ? true : false;
        $vData[$cId]['data'][$eId]['err_msg'] = $errMsg;
      }
    }

    return $vData;
  }

  /**
   * Gets module's content
   *
   * @return array
   *         Returns module content and forces page renderer to use
   *         special main template.
   *
   * @throws Exception
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);
    $get = new Input(Input::SOURCE_GET);
    $this->_leadId = $get->readInt('lead');
    $this->_campaignId = ($post->readInt('ln_campaign')) ? $post->readInt('ln_campaign') : $get->readInt('cgid');
    $currentStatusId = (int) $this->db->GetOne(" SELECT FK_CGSID FROM {$this->table_prefix}campaign_lead WHERE CGLID = {$this->db->escape($this->_leadId)}");
    // assign lead to user
    if (isset($this->action[1]) && $this->action[1] == 'take' && $get->readInt('cid'))
    {
      $res = $this->_takeClient($get->readInt('cid'), $this->_user->getID(), $this->_leadId, $this->_campaignId, $currentStatusId);
      if ($res) {
        $this->setMessage(Message::createSuccess($_LANG['ln_message_lead_taken_success']));
      }
    }

    // check if user is allowed to view this client
    if ($this->_leadId) {
      $this->_checkUser($this->_leadId);
    }

    // edit client
    if ($this->_getFormActionType() == 'edit') {
      $action = 'index.php?action=mod_leadmgmt&amp;action2=client;edit&amp;site='.$this->site_id.'&amp;lead='.$this->_leadId.'&amp;cgid='.$this->_campaignId;
    }
    else {
      $action = 'index.php?action=mod_leadmgmt&amp;action2=client;new&amp;site='.$this->site_id;
    }

    if ($this->_errorTitles) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['ln_message_save_form_errors'], implode(', ', $this->_errorTitles))));
    }

    // generate campaign type option groups and campaign options
    $cgOptions = $this->_getCampaignOptions($this->_campaignId);
    if (!$cgOptions) {
      $this->setMessage(Message::createFailure($_LANG['ln_message_no_site_campaigns']));
    }
    $vData = ($this->_vData) ? $this->_vData[$this->_campaignId]['data'] : array();
    $msgArray = $this->_getMessageArray($vData, 'ln');
    // get campaigns (structure data)
    $sData = $this->_readCampaignData();
    $sData = ($sData) ? $sData[$this->_campaignId] : array();
    $cgElements = $this->_getCampaignFormElements($sData, $vData);
    $leadAgentUsers = $this->_readActiveLeadAgentUsers(isset($this->_eData['data']) ? $this->_eData['data']['u_id'] : 0);
    $leadAgentUserOptions = '';
    $selId = (isset($this->_eData['data']) && !$post->exists('ln_user')) ? $this->_eData['data']['u_id'] : $post->readInt('ln_user');
    foreach ($leadAgentUsers as $id => $fullName)
    {
      $selected = '';
      if ($id == $selId) {
        $selected = 'selected ="selected"';
      }
      $leadAgentUserOptions .= '<option value="'.$id.'" '.$selected.'>'.parseOutput($fullName).'</option>';
    }

    $this->tpl->load_tpl('content_ln', 'modules/ModuleLeadManagementClient.tpl');
    // hide campaign selection if client gets edited
    $this->tpl->parse_if('content_ln', 'select_campaign', !$this->_leadId);
    $editLeadData = array();
    if ($this->_leadId)
    {
      $additionalData = $this->_readAdditionalData(true);
      $clientData = ($this->_eData['data']['c_id']) ? $this->_readClientData($this->_eData['data']['c_id']) : array();
      $editLeadData = array_merge($clientData, array(
        'ln_campaign_name' => $this->_getCampaignName($this->_campaignId),
        'ln_client_id' => $this->_leadId,
        'ln_client_competing_company' => parseOutput($additionalData['ln_competing_company_label']),
        'ln_client_create_date_time' => ($this->_eData['data']['create_date_time']) ? date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ln'), strtotime($this->_eData['data']['create_date_time'])) : '',
        'ln_client_change_date_time' => ($this->_eData['data']['change_date_time']) ? date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ln'), strtotime($this->_eData['data']['change_date_time'])) : '',
      ));
    }
    $this->tpl->parse_if('content_ln', 'edit_lead', $this->_leadId, $editLeadData);
    $this->tpl->parse_if('content_ln', 'process_status', $this->_leadId);
    $this->tpl->parse_if('content_ln', 'process_client', $this->_leadId);
    $this->tpl->parse_if('content_ln', 'process_campaign', $this->_leadId);
    // hide data fields if no campaign exists for selected site
    $this->tpl->parse_if('content_ln', 'campaigns', $cgOptions);
    $this->tpl->parse_if('content_ln', 'lead_admin', $this->_isUserLeadAdmin());
    $this->tpl->parse_if('content_ln', 'status_history', $this->_leadId);
    $this->tpl->parse_if('content_ln', 'competing_companies', $this->_readCompetingCompanies($this->_campaignId));
    $this->tpl->parse_loop('content_ln', $this->_readAppointments(), 'appointments');
    $this->tpl->parse_if('content_ln', 'appointments', $this->_readAppointments());
    $this->tpl->parse_if('content_ln', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ln'));
    $this->tpl->parse_if('content_ln', 'client_data_global_message', $this->_getMessage() && $this->_clientDataAreaSaved, $this->_getMessageTemplateArray('ln'));
    $this->tpl->parse_if('content_ln', 'campaign_data_global_message', $this->_getMessage() && $this->_campaignDataAreaSaved, $this->_getMessageTemplateArray('ln'));
    $this->tpl->parse_loop('content_ln', $msgArray['client'], 'client_data_messages');
    $this->tpl->parse_if('content_ln', 'client_data_message', $msgArray['client'], array( 'form_messagetype' => 'failure' ));
    $this->tpl->parse_loop('content_ln', $msgArray['campaign'], 'campaign_data_messages');
    $this->tpl->parse_if('content_ln', 'campaign_data_message', $msgArray['campaign'], array( 'form_messagetype' => 'failure' ));
    $this->tpl->parse_loop('content_ln', $this->_getClientFormElements($sData, $vData),'ln_client_data_elements');
    // hide campaign data part if no campaign data is available
    $this->tpl->parse_if('content_ln', 'campaign_data', $cgElements);
    $this->tpl->parse_loop('content_ln', $cgElements,'ln_campaign_data_elements');
    if (is_array($this->_readAppointments()))
    {
      foreach ($this->_readAppointments() as $appointment)
      {
        $this->tpl->parse_if('content_ln', 'appointment_urgent_'.$appointment['ln_appointment_id'], $appointment['ln_appointment_urgent']);
        $this->tpl->parse_if('content_ln', 'appointment_normal_'.$appointment['ln_appointment_id'], empty($appointment['ln_appointment_urgent']));
      }
    }

    $ln_content = $this->tpl->parsereturn('content_ln', array_merge($this->_readAdditionalData(), array(
      'ln_action'              => $action,
      'ln_module_action_boxes' => $this->_getContentActionBoxes(),
      'ln_request_url'         => 'index.php?action=mod_response_leadmgmt&site='.$this->site_id.'&request=',
      'ln_lead_id'             => $this->_leadId,
      'ln_campaign_id'         => $this->_campaignId,
      'ln_previous_module'     => $this->_previousModuleShortname,
      'ln_status_options'      => ($this->_errorTitles) ? $this->_getStatusOptions($post->readInt('ln_status')) : $this->_getStatusOptions($currentStatusId),
      'ln_status_text'         => ($this->_errorTitles) ? $post->readString('ln_status_text') : '',
      'ln_campaign_options'    => $cgOptions,
      'ln_client_data_title'   => $_LANG['ln_client_data_title'],
      'ln_element_nl_confirmed_recipient_label' => $_LANG['ln_client_newsletter_confirmed_recipient_label'],
      'ln_element_nl_confirmed_recipient_checked' => isset($this->_eData['data']) && $this->_eData['data']['newsletter_confirmed_recipient'] ? 'checked="checked"' : '',
      'ln_campaign_data_title' => $_LANG['ln_campaign_data_title'],
      'ln_campaign_data_info'  => sprintf($_LANG['ln_campaign_data_info'], $this->_getCampaignName($this->_campaignId)),
      'ln_appointment_title'   => ($this->_errorTitles) ? $post->readString('ln_appointment_title') : '',
      'ln_appointment_date'    => ($this->_errorTitles) ? $post->readString('ln_appointment_date') : '',
      'ln_appointment_time'    => ($this->_errorTitles) ? $post->readString('ln_appointment_time') : '',
      'ln_appointment_text'    => ($this->_errorTitles) ? $post->readString('ln_appointment_text') : '',
      'ln_competing_company_label' => $_LANG['ln_competing_company_label'],
      'ln_doc_email_label'         => $_LANG['ln_doc_email_label'],
      'ln_doc_post_label'          => $_LANG['ln_doc_post_label'],
      'ln_lead_appointment'        => $_LANG['ln_lead_appointment'],
      'ln_data_origin_label'       => $_LANG['ln_data_origin_label'],
      'ln_user'                    => $leadAgentUserOptions,
      'ln_assigned_user_firstname' => (isset($this->_eData['data'])) ? $this->_eData['data']['u_firstname'] : '-',
      'ln_assigned_user_lastname'  => (isset($this->_eData['data'])) ? $this->_eData['data']['u_lastname'] : '',
      'ln_client_status'           => $this->_getFormActionType(),
      'ln_campaign_data_error'     => (isset($this->_errorTitles[$_LANG['ln_campaign_data_title']]) || $this->_campaignDataAreaSaved) ? 1 : 0,
      'ln_client_data_error'       => (isset($this->_errorTitles[$_LANG['ln_client_data_title']]) || $this->_clientDataAreaSaved) ? 1 : 0,
      'ln_scroll_to_anchor'        => ($this->_clientDataAreaSaved) ? 'client_data' : ($this->_campaignDataAreaSaved ? 'campaign_data' : ''),
    ), $_LANG2['ln']));

    return array (
      'content'             => $ln_content,
      'content_output_mode' => 10,
      'content_output_tpl'  => 'leadmgmt',
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Gets a list of available, active forms of current site.
   *
   * @param int $campaignId (optional)
   *        The campaign id. Option with this campaign id will be selected.
   *
   * @return string HTML optiongroups with campaign types and options
   *         that contain form names.
   *
   * @throws Exception
   */
  private function _getCampaignOptions($campaignId = 0)
  {
    $options = '';
    $lastTypeId = 0;

    // get all active campaigns
    $sql = " SELECT CGTID, CGTName, CGID, CGName "
         . " FROM {$this->table_prefix}campaign_type ct "
         . " INNER JOIN {$this->table_prefix}campaign c "
         . "       ON CGTID = FK_CGTID "
         . " WHERE CGStatus = " . self::CAMPAIGN_STATUS_ACTIVE . " "
         . " ORDER BY CGTPosition ASC, CGPosition ASC ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result))
    {
      $cgId = (int)$row['CGID'];
      $name = $row['CGName'];
      $ctId = (int)$row['CGTID'];
      $typeName = $row['CGTName'];

      // set selected campaign id to its default value
      if ($campaignId == 0 && $lastTypeId == 0) {
        $this->_campaignId = $cgId;
      }

      if ($lastTypeId != $ctId) {
        $lastTypeId = $ctId;
        $options .= ($options) ? '</optgroup>' : '';
        $options .= '<optgroup label="'.$typeName.'">';
      }
      $selected = '';
      if ($campaignId == $cgId) {
        $selected = 'selected="selected"';
      }
      $options .= '<option value="'.$cgId.'" '.$selected.'>'.$name.'</option>';
    }
    $options .= ($options) ? '</optgroup>' : '';

    return $options;
  }

  /**
  * Gets a list of competing companies of given campaign.
  *
  * @param int $cgId
  *        The campaign id.
  * @param int $ccId (optional)
  *        The competing company id. Option with this id will be selected.
  * @return string
  *         HTML options.
  */
  private function _getCompetingCompaniesOptions($cgId, $ccId = 0)
  {
    $options = '';

    foreach ($this->_readCompetingCompanies($cgId) as $key => $value)
    {
      $selected = '';
      if ($ccId == $key) {
        $selected = 'selected="selected"';
      }
      $options .= '<option value="'.$key.'" '.$selected.'>'.parseOutput($value).'</option>';
    }

    return $options;
  }

  /**
   * Gets campaign name of given campaign id or specified position.
   *
   * @param int $campaignId
   *        The campaign id. Get campaign name of given id.
   * @param int $defaultPosition (optional)
   *        Get campaign name of given campaign position.
   *        Default is first position.
   *
   * @return string
   */
  private function _getCampaignName($campaignId, $defaultPosition = 1)
  {
    $campaigns = $this->_readCampaignData();
    if (isset($campaigns[$campaignId])) {
      return $campaigns[$campaignId]['name'];
    }

    $i = 1;
    foreach ($campaigns as $data) {
      if ($i == $defaultPosition) {
        return $data['name'];
      }
    }

    return '';
  }

  /**
   * Gets all status entries of current selected campaign.
   *
   * @param int $statusId (optional)
   *        Id of preselected option.
   *
   * @return string
   *         HTML select options.
   * @throws Exception
   */
  private function _getStatusOptions($statusId = 0)
  {
    $options = '';

    // get all status entries of selected site
    $sql = ' SELECT CGSID, CGSName, CGName, CGID '
         . " FROM {$this->table_prefix}campaign_status cs "
         . " LEFT JOIN {$this->table_prefix}campaign "
         . '    ON CGID = cs.FK_CGID '
         . " WHERE cs.FK_CGID = {$this->_campaignId} "
         . '    OR cs.FK_CGID = 0 '
         . ' ORDER BY cs.FK_CGID ASC, CGSPosition ASC ';
    $res = $this->db->query($sql);
    $lastcgId = 0;
    while ($row = $this->db->fetch_row($res))
    {
      if ($lastcgId != $row['CGID'])
      {
        $options .= ($options && $lastcgId != 0) ? '</optgroup>' : '';
        $lastcgId = $row['CGID'];
        $options .= '<optgroup label="'.parseOutput($row['CGName']).'">';
      }
      $selected = '';
      if ($statusId == $row['CGSID']) {
        $selected = 'selected="selected"';
      }
      $options .= '<option value="'.$row['CGSID'].'" '.$selected.'>'.parseOutput($row['CGSName']).'</option>';
    }
    $options .= ($lastcgId) ? '</optgroup>' : '';

    return $options;
  }

  /**
   * Gets element data of element with given position.
   *
   * @param array $data
   *        The data array.
   * @param int $position
   *        The position of the element.
   * @return string|null
   *         Element or null if element's position could not be found.
   */
  private function _getElementWithPosition($data, $position)
  {
    foreach ($data as $eId => $dataElement)
    {
      if (isset($dataElement['position']) && $dataElement['position'] == $position) {
        return $dataElement;
      }
    }

    return null;
  }

  /**
   * Reads additional lead data of current lead from db
   * and caches it.
   *
   * @param boolean $dataArray
   *        If true, this method will return an simple array with database values,
   *        otherwise parsed data for template output will be returned. Default is false.
   * @return array|null
   *         Returns additional data or null if additional data could
   *         not be read from db.
   */
  private function _readAdditionalData($dataArray = false)
  {
    if ($this->_cachedAdditionalData !== null && !$dataArray) {
      return $this->_cachedAdditionalData;
    }

    $post = new Input(Input::SOURCE_POST);
    $process = ($post->exists('process')) ? true : false;
    $sql = ' SELECT CGLDocumentsEMail, CGLDocumentsPost, CGLAppointment, '
         . '        CGLDataOrigin, FK_CGCCID '
         . " FROM {$this->table_prefix}campaign_lead "
         . " WHERE CGLID = {$this->_leadId} ";
    $row = $this->db->GetRow($sql);

    if ($dataArray && $row)
    {
      $company = $this->_readCompetingCompanies($this->_campaignId);
      return array(
        'ln_competing_company_label' => ($row['FK_CGCCID']) ? $company[$row['FK_CGCCID']] : '',
        'ln_doc_email_label' => $row['CGLDocumentsEMail'],
        'ln_doc_post_label' => $row['CGLDocumentsPost'],
        'ln_lead_appointment' => $row['CGLAppointment'],
        'ln_data_origin_label' => $row['CGLDataOrigin'],
      );
    }

    if ($row)
    {
      $company = ($process && $post->exists('ln_competing_companies')) ? $post->readInt('ln_competing_companies') : $row['FK_CGCCID'];
      $addData = array(
        'ln_competing_companies'      => $this->_getCompetingCompaniesOptions($this->_campaignId, $company),
        'ln_doc_email_checked'        => ($process && $post->exists('ln_doc_email')) ? 'checked="checked"' : (!$process && $row['CGLDocumentsEMail'] ? 'checked="checked"' : ''),
        'ln_doc_post_checked'         => ($process && $post->exists('ln_doc_post')) ? 'checked="checked"' : (!$process && $row['CGLDocumentsPost'] ? 'checked="checked"' : ''),
        'ln_lead_appointment_checked' => ($process && $post->exists('ln_lead_appointment')) ? 'checked="checked"' : (!$process && $row['CGLAppointment'] ? 'checked="checked"' : ''),
        'ln_data_origin'              => ($process && $post->readString('ln_data_origin')) ? $post->readString('ln_data_origin') : $row['CGLDataOrigin'],
      );
    }
    else
    {
      $company = ($process && $post->exists('ln_competing_companies')) ? $post->readInt('ln_competing_companies') : 0;
      $addData = array(
        'ln_competing_companies'      => $this->_getCompetingCompaniesOptions($this->_campaignId, $company),
        'ln_doc_email_checked'        => ($process && $post->exists('ln_doc_email')) ? 'checked="checked"' :  '',
        'ln_doc_post_checked'         => ($process && $post->exists('ln_doc_post')) ? 'checked="checked"' :  '',
        'ln_lead_appointment_checked' => ($process && $post->exists('ln_lead_appointment')) ? 'checked="checked"' :  '',
        'ln_data_origin'              => ($process && $post->readString('ln_data_origin')) ? $post->readString('ln_data_origin') : '',
      );
    }

    $this->_cachedAdditionalData = $addData;

    return $addData;
  }

  /**
   * Reads open appointments of current lead from db and caches it.
   *
   * @return array|null
   *         Returns open appointments or null if appointments could
   *         not be read from db.
   * @throws Exception
   */
  private function _readAppointments()
  {
    if ($this->_cachedAppointments !== null || !$this->_leadId) {
      return $this->_cachedAppointments;
    }

    $post = new Input(Input::SOURCE_POST);
    // if form wasn't processed successfully, then we want to check all
    // previous marked checkboxes again, therefore read all marked checkboxes.
    $finished = $post->readArrayIntToInt('ln_appointment_finished');

    $sql = ' SELECT CGLAID, CGLACreateDateTime, CGLADateTime, '
         . '        CGLATitle, CGLAText, '
         . '        UFirstname, ULastname, UNick, UID '
         . " FROM {$this->table_prefix}campaign_lead_appointment "
         . " LEFT JOIN {$this->table_prefix}user "
         . '   ON FK_UID = UID '
         . " WHERE FK_CGLID = {$this->_leadId} "
         . "   AND FK_CGID = {$this->_campaignId} "
         . "   AND CGLAStatus = '".self::APPOINTMENT_OPEN."' "
         . ' ORDER BY CGLADateTime ASC ';
    $res = $this->db->query($sql);

    if ($res)
    {
      $shorttextMaxlength = ConfigHelper::get('ln_appointment_list_shorttext_maxlength', '', $this->site_id);
      $shorttextAftertext = ConfigHelper::get('shorttext_aftertext', 'ln_appointment_list', $this->site_id);
      $appointments = array();
      $i = 1;
      while ($row = $this->db->fetch_row($res))
      {
        $createDate = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ln'), strtotime($row['CGLACreateDateTime']));
        $date = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ln'), strtotime($row['CGLADateTime']));
        $aId = (int) $row['CGLAID'];
        $shortText = nl2br($row['CGLAText']);
        $shortTextBrPosition = (mb_strpos($shortText, '<br />')) ? mb_strpos($shortText, '<br />') : mb_strpos($shortText, '<br>');
        // cut text before first html break
        if ($shortTextBrPosition && $shortTextBrPosition <= $shorttextMaxlength) {
          $shortText = mb_substr($shortText, 0, $shortTextBrPosition).$shorttextAftertext;
        }
        // cut text after defined max length
        else {
          $shortText = StringHelper::setText($shortText)
                       ->purge('<br>')
                       ->truncate($shorttextMaxlength, $shorttextAftertext)
                       ->getText();
        }
        $appointments[] = array(
          'ln_appointment_id'             => $aId,
          'ln_appointment_createdatetime' => $createDate,
          'ln_appointment_datetime'       => $date,
          'ln_appointment_title'          => parseOutput($row['CGLATitle']),
          'ln_appointment_text'           => nl2br(parseOutput($row['CGLAText'])),
          'ln_appointment_shorttext'      => parseOutput($shortText, 1),
          'ln_appointment_user_firstname' => parseOutput($row['UFirstname']),
          'ln_appointment_user_lastname'  => parseOutput($row['ULastname']),
          'ln_appointment_user_nick'      => parseOutput($row['UNick']),
          'ln_appointment_checked'        => (isset($finished[$aId])) ? 'checked="checked"' : '',
          'ln_appointment_row_bg'         => ($i++ % 2) ? 'row1' : 'row2',
          'ln_appointment_urgent'         => ($row['UID'] != $this->_user->getID()) ? 'urgent' : '',
        );
      }
      $this->_cachedAppointments = $appointments;

      return $appointments;
    }

    return array();
  }

  /**
   * Gets all campaign types, campaigns and campaign data of our db and caches it.
   * Client fields (AbstractModuleLeadManagementForm::_clientFields) which are
   * not defined in campaign_data will be generated.
   * This method also generates an array ($_eData) that contains all editable lead data,
   * if lead gets edited. It is used to show available lead data.
   *
   * @return array
   *
   * @throws Exception
   */
  private function _readCampaignData()
  {
    if ($this->_cachedCampaigns !== null) {
      return $this->_cachedCampaigns;
    }

    // get only SELECTED campaign data
    $sql = 'SELECT CGTID, CGTName, CGID, CGName, '
         . '       CGDType, CGDName, CGDValue, CGDRequired, CGDDependency, '
         . '       CGDPosition, CGDValidate, CGDPredefined, CGDPrechecked, '
         . '       CGDMinLength, CGDMaxLength, CGDMinValue, CGDMaxValue, '
         . '       CGDFilesize, CGDFiletypes, CGDClientData, CGDID '
         . "FROM {$this->table_prefix}campaign_type ct "
         . "INNER JOIN {$this->table_prefix}campaign c "
         . '      ON CGTID = FK_CGTID '
         . "LEFT JOIN {$this->table_prefix}campaign_data cd "
         . '  ON COALESCE(NULLIF(c.FK_CGID, 0), c.CGID) = cd.FK_CGID '
         . "WHERE CGID = {$this->_campaignId} "
         . '  AND ( CGDDisabled = 0 '
         . '        OR CGDDisabled IS NULL ) ' // also get campaigns without campaign data
         . 'ORDER BY CGTPosition ASC, CGPosition ASC, CGDPosition ASC ';
    $res = $this->db->query($sql);
    $data = array();
    $this->_eData = array();
    $lastCgId = 0;
    while ($row = $this->db->fetch_row($res))
    {
      if ($lastCgId != $row['CGID'])
      {
        $lastCgId = (int) $row['CGID'];
        // each campaign should contain its name, corresponding type name and id
        // 'data' contains campaign's data elements
        $data[$lastCgId] = array(
          't_id'   => (int) $row['CGTID'],
          't_name' => parseOutput($row['CGTName']),
          'name'   => parseOutput($row['CGName']),
          'data'   => array(),
        );

        // default element keys are equivalent to their edwin client database table column position
        $defaultElements = $this->_clientFields;
        $editClientRow = '';
        $editLeadData = '';

        // EDIT LEAD DATA
        if ($this->_leadId && $this->_campaignId == $lastCgId)
        {
          // get client/lead data, lead's status and assigned user
          $sql = 'SELECT '.implode(',', $this->_clientFields).', '
               . '       CNewsletterOptInToken, CNewsletterOptInSuccessDateTime, CNewsletterConfirmedRecipient, '
               . '       CID, CCreateDateTime, CChangeDateTime, c.FK_UID, '
               . '       CGSID, CGSName, '
               . '       UFirstname, ULastname '
               . "FROM {$this->table_prefix}campaign_lead "
               . "INNER JOIN {$this->table_prefix}campaign_status "
               . '  ON CGSID = FK_CGSID '
               . "LEFT JOIN {$this->table_prefix}client c "
               . '  ON CID = FK_CID '
               . "LEFT JOIN {$this->table_prefix}user "
               . '  ON UID = c.FK_UID '
               . "WHERE CGLID = '{$this->_leadId}' "
               . '  AND CGLDeleted = 0 ';
          $editClientRow = $this->db->GetRow($sql);

          $sql = 'SELECT FK_CGDID, CGLDValue '
               . "FROM {$this->table_prefix}campaign_lead_data "
               . "WHERE FK_CGLID = '{$this->_leadId}' ";
          $editLeadData = $this->db->GetAssoc($sql);

          if (!$editClientRow && !$editLeadData) {
            $this->_redirectToTarget('clients', array('leadedit' => 1));
          }
          // user is allowed to edit this lead, so add general data to array.
          else
          {
            $lastStatusData = $this->_readLastStatusData();
            $this->_eData['data']['fu_id'] = 0;
            $this->_eData['data']['c_id'] = $editClientRow['CID'];
            $this->_eData['data']['create_date_time'] = $editClientRow['CCreateDateTime'];
            $this->_eData['data']['change_date_time'] = $editClientRow['CChangeDateTime'];
            $this->_eData['data']['u_id'] = $editClientRow['FK_UID'];
            $this->_eData['data']['u_firstname'] = $editClientRow['UFirstname'];
            $this->_eData['data']['u_lastname'] = $editClientRow['ULastname'];
            $this->_eData['data']['status_id'] = $editClientRow['CGSID'];
            $this->_eData['data']['status_name'] = $editClientRow['CGSName'];
            $this->_eData['data']['status_text'] = $lastStatusData['text'];
            $this->_eData['data']['newsletter_opt_in_token'] = $editClientRow['CNewsletterOptInToken'];
            $this->_eData['data']['newsletter_opt_in_success_datetime'] = $editClientRow['CNewsletterOptInSuccessDateTime'];
            $this->_eData['data']['newsletter_confirmed_recipient'] = $editClientRow['CNewsletterConfirmedRecipient'];
          }
        }
      }

      // add client default data
      // the id of default data elements always start with "d_", because
      // these elements do not exist in campaign_data and have not got a real id.
      foreach ($defaultElements as $key => $value)
      {
        unset($defaultElements[$key]);
        // element was already defined in campaign_data, therefore do not create default element
        if ((int) $row['CGDPosition'] == $key) {
          break;
        }
        else
        {
          $data[$lastCgId]['data'] = $data[$lastCgId]['data'] + array (
           'd_'.$key => $this->_generateDefaultDataElement($key));

          // client's data to edit
          if ($editClientRow)
          {
            $clField = $this->_clientFields[$key];
            $this->_eData['d_'.$key] = $this->_generateEditDataArray($key, parseOutput($editClientRow[$clField]), true, $row['CGDPredefined']);
          }
        }
      }

      // if position is null, this campaign has not got any campaign data fields
      // this means that our campaign just contains default client data elements
      if ($row['CGDPosition'] === null) {
        continue;
      }

      $client = ($row['CGDClientData']) ? true : false;
      $newData = false;

      // campaign client data
      if ($editClientRow && isset($this->_clientFields[$row['CGDPosition']]) && $client)
      {
        $clField = $this->_clientFields[$row['CGDPosition']];
        $val = parseOutput($editClientRow[$clField]);
        $this->_eData[$row['CGDID']] = $this->_generateEditDataArray((int) $row['CGDPosition'], $val, $client, false, $row['CGDPredefined']);
      }
      // campaign lead data
      else if ($editLeadData && isset($editLeadData[$row['CGDID']]))
      {
        $val = parseOutput($editLeadData[$row['CGDID']]); // campaign lead data value
        $this->_eData[$row['CGDID']] = $this->_generateEditDataArray((int) $row['CGDPosition'], $val, $client, false, $row['CGDPredefined']);
      }
      // this is mostly the case if client already exists and campaign data was changed afterwards
      else
      {
        $newData = true;
        $this->_eData[$row['CGDID']] = $this->_generateEditDataArray((int) $row['CGDPosition'], '', $client, true, $row['CGDPredefined']);
      }

      // store campaign structure data
      $data[$lastCgId]['data'] = $data[$lastCgId]['data'] + array (
        $row['CGDID'] => array (
          'type'       => (int) $row['CGDType'],
          'name'       => parseOutput($row['CGDName']),
          'value'      => parseOutput($row['CGDValue']),
          'required'   => ($row['CGDRequired']) ? true : false,
          'dependency' => (int) $row['CGDDependency'],
          'position'   => (int) $row['CGDPosition'],
          'validate'   => (int) $row['CGDValidate'],
          'predefined' => (int) $row['CGDPredefined'],
          'prechecked' => parseOutput($row['CGDPrechecked']),
          'min_length' => (int) $row['CGDMinLength'],
          'max_length' => (int) $row['CGDMaxLength'],
          'min_value'  => (int) $row['CGDMinValue'],
          'max_value'  => (int) $row['CGDMaxValue'],
          'filetypes'  => $row['CGDFiletypes'] ? explode('$', $row['CGDFiletypes']) : array('*'),
          'filesize'   => (int) $row['CGDFilesize'],
          'client'     => ($row['CGDClientData']) ? true : false,
          'new_data'   => $newData, // true if client already exists and campaign data was changed afterwards.
        )
      );
    }

    // sort the field configuration by client field position settings
    // $_clientFieldPosition to ensure the required client field order and
    // append the remaining fields after client fields without modifying their
    // position

    // 1. for standard configuration

    $results = array();
    $array = $data[$lastCgId]['data'];
    foreach ($this->_clientFieldPositions as $position) {
      foreach ($array as $key => $values) {
        if ($position == $values['position']) {
          $results[$key] = $values;
          break;
        }
      }
    }

    foreach ($array as $key => $values) {
      if (!isset($results[$key])) {
        $results[$key] = $values;
      }
    }

    $data[$lastCgId]['data'] = $results;
    $this->_cachedCampaigns = ($data) ? $data : null;

    // 2. for edit form field configuration

    $results = array();
    $array = $this->_eData;

    if ($array && isset($array['data'])) {
      $results['data'] = $array['data'];
      unset($array['data']);

      foreach ($this->_clientFieldPositions as $position) {
        foreach ($array as $key => $values) {
          if ($position == $values['position']) {
            $results[$key] = $values;
            break;
          }
        }
      }

      foreach ($array as $key => $values) {
        if (!isset($results[$key])) {
          $results[$key] = $values;
        }
      }

      $this->_eData = $results;
    }

    return $data;
  }

  /**
   * Gets the client data of given client id.
   *
   * @param int $cid
   *        The client id.
   * @return array
   *         The array containing the client data.
   *         The keys are string values, so it is ready to get parsed for templates.
   */
  private function _readClientData($cid)
  {
    global $_LANG;

    $sql = ' SELECT '.implode(', ', $this->_clientFields).' '
         . " FROM {$this->table_prefix}client "
         . " WHERE CID = '{$cid}' ";
    $row = $this->db->GetRow($sql);

    $country = '';
    if ($row['CCountry'] > 0)
    {
      $countries = $this->_configHelper->getCountries(array( 'ln_countries', 'countries',), true, $this->site_id);
      $country = $countries[$row['CCountry']];
    }
    $birthday = '';
    if (strtotime($row['CBirthday']) > 0) {
      $birthday = date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ln'), strtotime($row['CBirthday']));
    }

     return array(
      'ln_client_company' => parseOutput($row['CCompany']),
      'ln_client_position' => parseOutput($row['CPosition']),
      'ln_client_foa' => isset($_LANG['ln_foas'][$row['FK_FID']]) ? $_LANG['ln_foas'][$row['FK_FID']] : '',
      'ln_client_title_pre' => parseOutput($row['CTitlePre']),
      'ln_client_title_post' => parseOutput($row['CTitlePost']),
      'ln_client_firstname' => parseOutput($row['CFirstname']),
      'ln_client_lastname' => parseOutput($row['CLastname']),
      'ln_client_birthday' => $birthday,
      'ln_client_country' => parseOutput($country),
      'ln_client_zip' => parseOutput($row['CZIP']),
      'ln_client_city' => parseOutput($row['CCity']),
      'ln_client_address' => parseOutput($row['CAddress']),
      'ln_client_phone' => parseOutput($row['CPhone']),
      'ln_client_mobile_phone' => parseOutput($row['CMobilePhone']),
      'ln_client_email' => parseOutput($row['CEmail']),
      'ln_client_newsletter' => $_LANG['ln_checkbox_label'][$row['CNewsletter']],
      'ln_client_data_privacy_consent' => $_LANG['ln_checkbox_label'][$row['CDataPrivacyConsent']],
     );
  }

  /**
   * Gets last status id and text of current lead.
   *
   * @return array
   */
  private function _readLastStatusData()
  {
    // read last status id and text of current edited lead
    $sql = ' SELECT FK_CGSID, CGLSText '
         . " FROM {$this->table_prefix}campaign_lead_status "
         . " WHERE FK_CGLID = {$this->_leadId} "
         . "   AND FK_CGID = {$this->_campaignId} "
         . ' ORDER BY CGLSDateTime DESC '
         . ' LIMIT 0, 1 ';

    $row = $this->db->GetRow($sql);

    return array(
      'id'   => ($row) ? $row['FK_CGSID'] : '',
      'text' => ($row) ? $row['CGLSText'] : '',
    );
  }

  /**
   * Redirects the user to given target.
   * Special handling if target would be the search:
   * The window is closed and the user should be taken back to
   * the origin window (depends on browser).
   *
   * @param string $target
   *        The shortname of the target module
   * @param array $additionalArguments
   *        Additional HTTP GET parameters.
   */
  private function _redirectToTarget($target, $additionalArguments = array())
  {
    if ($target == 'search') {
    // Close window if user comes from search module
    // This awesome javascript works also in IE (tested with 7, 8 and 9)
    // and avoids the "Do you want to close this window" prompt.
      echo '<script type="text/javascript">window.open("", "_self", "");window.close();</script>';
    }
    else {
      $params = array_merge($additionalArguments, array(
        'action'  => 'mod_leadmgmt',
        'action2' => $target,
        'site'    => $this->site_id,
      ));
      $args = array();
      foreach ($params as $key => $val) {
        $args[] = "{$key}={$val}";
      }
      header('Location: index.php?'.implode($args, '&'));
    }

    // Exit, because we close the whole window or redirect the user.
    exit;
  }

  /**
   * Update and save urgent/client appointments
   *
   * @param string $timestamp
   *        Timestamp for change date-time of appointment.
   *
   * @return array
   *         DB query results.
   *
   * @throws Exception
   */
  private function _updateAppointments($timestamp='')
  {
    $post = new Input(Input::SOURCE_POST);
    // stores db query results
    $result = array();
    // validate appointments
    $vAData = $this->_validateAppointmentData();

    // update appointments
    $finished = $post->readArrayIntToInt('ln_appointment_finished');
    if ($finished)
    {
      $sql = " UPDATE {$this->table_prefix}campaign_lead_appointment "
           . " SET CGLAStatus = '".self::APPOINTMENT_FINISHED."', "
           . "     CGLAChangeDateTime = '{$timestamp}' "
           . " WHERE FK_CGLID = {$this->_leadId} "
           . "   AND FK_CGID = {$this->_campaignId} "
           . "   AND CGLAID IN (".implode(', ', array_keys($finished)).") ";
      $result['update_lead_appointment'] = $this->db->query($sql);
    }

    // save appointment
    if ($vAData)
    {
      $sql = " INSERT INTO {$this->table_prefix}campaign_lead_appointment "
           . " ( ".implode(', ', array_keys($vAData)).", FK_CGLID ) "
           . " VALUES (".implode(', ', $vAData).", '{$this->_leadId}') ";
      $result['insert_lead_appointment'] = $this->db->query($sql);
    }

    return $result;
  }

  /**
   * Update/save status
   *
   * @param string $timestamp
   *        Timestamp for status entries.
   *
   * @return array
   *         DB query results.
   *
   * @throws Exception
   */
  private function _updateStatus($timestamp='')
  {
    $post = new Input(Input::SOURCE_POST);
    // stores db query results
    $result = array();
    // get last status id
    $leadStatus = $this->_readLastStatusData();
    $statusId = $post->readInt('ln_status');

    if (($leadStatus['id'] != $statusId) || $post->readString('ln_status_text')) {
      // save campaign lead status
      $result['lead_status'] = $this->_addStatusHistoryEntry(
        $this->_leadId,
        $this->_campaignId,
        $post->readString('ln_status_text'),
        $statusId,
        $timestamp
      );
    }

    return $result;
  }

  /**
   * Validates appointment form fields and returns
   * validated data.
   *
   * @return array
   *         Validated data or empty array on validation error.
   */
  private function _validateAppointmentData()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (trim($post->readString('ln_appointment_text'))
       || trim($post->readString('ln_appointment_date'))
       || trim($post->readString('ln_appointment_time')))
    {
      // appointment title exists in database, but was never used in "ccmm".
      // so we ignore it!
//      $apTitle = $post->readString('ln_appointment_title');
      $apDate = $post->readString('ln_appointment_date');
      $apTime = $post->readString('ln_appointment_time');
      $apText = $post->readString('ln_appointment_text');

//      if (!$apTitle) {
//        $this->_errorTitles[] = $_LANG['ln_appointment_title'];
//      }
      if (!$apText) {
        $this->_errorTitles[] = $_LANG['ln_appointment_text'];
      }
      if (!$apDate || !DateHandler::isValidDate($apDate)) {
        $this->_errorTitles[] = $_LANG['ln_appointment_date'];
      }
      if (!$apTime || !DateHandler::isValidTime($apTime)) {
        $this->_errorTitles[] = $_LANG['ln_appointment_time'];
      }

      if (!$this->_errorTitles) {
        return array('CGLACreateDateTime' => "'".date('Y-m-d H:i:s')."'",
                     'CGLAChangeDateTime' => "'".date('Y-m-d H:i:s')."'",
                     'CGLADateTime'       => "'".DateHandler::combine($apDate, $apTime)."'",
//                     'CGLATitle'          => "'".$this->db->escape($apTitle)."'",
                     'CGLAText'           => "'".$this->db->escape($apText)."'",
                     'FK_UID'             => "'".$this->_user->getID()."'",
                     'FK_CGID'            => "'".$this->_campaignId."'",
                    );
      }
    }

    return array();
  }

  /**
   * @return string
   */
  private function _getFormActionType()
  {
    $type = '';
    if (isset($this->action[0]) && $this->action[0] == 'edit') {
      $type = 'edit';
    }
    else {
      $type = 'new';
    }

    return $type;
  }

  /**
   * Returns maximum file size from custom $size as well as 'post_max_size' and
   * 'upload_max_filesize'
   *
   * @param int $size
   *        allowed file size
   *
   * @return int
   */
  private function _getMaximumFilesize($size = 0)
  {
    return (int)$size > 0 ? (min((int)$size, getUploadLimit())) : getUploadLimit();
  }

  /**
   * Stores the client upload and writes metadata to database.
   *
   * @param array $file
   *        the uploaded file from the $_FILES array.
   *
   * @return string
   *         The name for the stored file, an empty string on failure
   *
   * @throws Exception
   */
  private function _storeClientUpload($file)
  {
    $fileName = $this->_getClientUploadFilename($file);
    $fileName = $this->_storeFile($file, 0, array('*'), '', $fileName, true);

    if ($fileName) {
      $now = date('Y-m-d H:i:s');
      $sql = " INSERT INTO {$this->table_prefix}client_uploads "
           . " (CUCreateDateTime, CUFile, FK_CID) "
           . " VALUES "
           . " ( '$now', '{$this->db->escape($fileName)}', 0) ";
      $this->db->query($sql);
    }

    return $fileName;
  }

  /**
   * Generate a unique file destination name of an uploaded file inside of the
   * 'files' directory. All client uploads are stored within the 'uploads' folder
   * ( which is inside the files folder ).
   *
   * @param array $uploadedFile
   *        The uploaded file from the $_FILES array.
   *
   * @return string
   *         The unique name for the uploaded file.
   */
  private function _getClientUploadFilename($uploadedFile)
  {
    $tmpName = $uploadedFile['name'];
    $tmpName = ResourceNameGenerator::file($tmpName);
    // get the extension - last part of filename splitted by all '.' within.
    $splitName = explode('.', $tmpName);
    $extension = $splitName[count($splitName) - 1];
    // remove the extension
    $name = mb_substr($tmpName, 0, mb_strlen($tmpName) - mb_strlen($extension) - 1);

    // Ensure the file does not exist. Change timestamp as long as the filename
    // is not unique.
    do
    {
      $timestamp = time();
      $destinationName = 'uploads/' . $name . '-' . $timestamp . '.' . $extension;
    }
    while (is_file('../files/' . $destinationName));

    return $destinationName;
  }

  /**
   * @return AbstractModel|BackendUser
   */
  private function _getCurrentAssignedLeadAgentUser()
  {
    if ($this->_currentAssignedLeadAgentUser !== null) {
      return $this->_currentAssignedLeadAgentUser;
    }

    return $this->_currentAssignedLeadAgentUser = $this->_getAssignedLeadAgentUserOfLead($this->_leadId);
  }

  /**
   * @param int $newUserId
   * @param string $timestamp
   */
  private function _onEditLeadAgentAssignment($newUserId, $timestamp = '')
  {
    global $_LANG;

    if (!$timestamp) {
      $timestamp = date('Y-m-d H:i:s');
    }

    if ($newUserId && $newUserId != $this->_getCurrentAssignedLeadAgentUser($this->_leadId)->id) {
      $lastStatus = $this->_readLastStatusData();
      $newUser = new BackendUser($this->db, $this->table_prefix);
      $newUser = $newUser->readItemById($newUserId);
      $this->_addStatusHistoryEntry(
        $this->_leadId,
        $this->_campaignId,
        sprintf($_LANG['ld_lead_taken'], $newUser->getName()),
        $lastStatus['id'],
        $timestamp
      );
    }
  }
}
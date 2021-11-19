<?php

/**
 * class.AbstractModuleLeadManagementForm.php
 *
 * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
abstract class AbstractModuleLeadManagement extends Module
{
  /**
   * Specifies the appointment open enum.
   *
   * @var string
   */
  const APPOINTMENT_OPEN = 'Open';

  /**
   * Specifies the appointment finished enum.
   *
   * @var string
   */
  const APPOINTMENT_FINISHED = 'Finished';

  /**
   * Specifies the campaign activ status.
   * An activ campaign is visible on the frontend and
   * can be attached to content items.
   *
   * @var int
   */
  const CAMPAIGN_STATUS_ACTIVE = 1;

  /**
   * Specifies the campaign archive status.
   * An archived campaign is visible on the backend, but
   * can not attached to content items.
   *
   * @var int
   */
  const CAMPAIGN_STATUS_ARCHIVED = 2;

  /**
   * Specifies the campaign deleted status.
   * A deleted campaign is completely hidden.
   *
   * @var int
   */
  const CAMPAIGN_STATUS_DELETED = 3;

  /**
   * Specifies the form type text constant
   *
   * @var int
   */
  const FORM_TYPE_TEXT = 1;

  /**
   * Specifies the form type textarea constant
   *
   * @var int
   */
  const FORM_TYPE_TEXTAREA = 2;

  /**
   * Specifies the form type combobox constant
   *
   * @var int
   */
  const FORM_TYPE_COMBOBOX = 3;

  /**
   * Specifies the form type checkbox constant
   *
   * @var int
   */
  const FORM_TYPE_CHECKBOX = 4;

  /**
   * Specifies the form type checkbox group constant
   *
   * @var int
   */
  const FORM_TYPE_CHECKBOXGROUP = 5;

  /**
   * Specifies the form type radiobutton constant
   *
   * @var int
   */
  const FORM_TYPE_RADIOBUTTON = 6;

  /**
   * Specifies the form type upload constant
   *
   * @var int
   */
  const FORM_TYPE_UPLOAD = 7;

  /**
   * Specifies the predefined country constant
   *
   * @var int
   */
  const PREDEFINED_COUNTRY = 1;

  /**
   * Specifies the predefined date constant
   *
   * @var int
   */
  const PREDEFINED_DATE = 2;

  /**
   * Client database fields.
   * Each array key is equivalent to form element position.
   *
   * @var array
   */
  protected $_clientFields = array(
    1  => 'CCompany',
    2  => 'CPosition',
    3  => 'FK_FID',
    4  => 'CTitlePre',
    5  => 'CFirstname',
    6  => 'CLastname',
    7  => 'CBirthday',
    8  => 'CCountry',
    9  => 'CZIP',
    10 => 'CCity',
    11 => 'CAddress',
    12 => 'CPhone',
    13 => 'CEmail',
    14 => 'CNewsletter',
    15 => 'CTitlePost',
    16 => 'CDataPrivacyConsent',
    17 => 'CMobilePhone',
  );

  /**
   * Defines client data field order
   *
   * @var array
   */
  protected $_clientFieldPositions = array(1, 2, 3, 4, 5, 6, 15, 7, 8, 9, 10, 11, 12, 17, 13, 14);

  /**
   * The prefix to use for language variables
   *
   * @var string
   */
  protected $_prefix = 'ld';

  /**
   * Stores number of expired appointments.
   *
   * @var int
   */
  private $_cachedExpiredAppointments = null;

  /**
   * Stores next appointments.
   *
   * @var int
   */
  private $_cachedNextAppointments = null;

    /**
     * {@inheritDocs}
     */
    protected function _getContentLeftLinks()
    {
      return array(
          array($this->_parseUrl('new'), $this->_langVar('moduleleft_newitem_label'), 'newitem btn btn-success', '')
      );
    }

  /**
   * Adds an entry to campaign lead manipulated log.
   *
   * @param int $leadId
   *        The lead id.
   * @param string $dateTime
   *        Date-Time of log entry (Y-m-d H:i).
   * @return boolean
   *         True on success or false on failure.
   */
  protected function _addManipulatedLogEntry($leadId, $dateTime)
  {
    $sql = "INSERT INTO {$this->table_prefix}campaign_lead_manipulated_log "
         . ' (CGLMLDateTime, FK_CGLID, FK_UID) '
         . " VALUES ( '{$dateTime}', {$leadId}, {$this->_user->getID()} ) ";
    return $this->db->query($sql);
  }

  /**
   * Checks if user is lead admin.
   *
   * @return boolean
   *         True if current user is lead admin
   */
  protected function _isUserLeadAdmin()
  {
    if ($this->_user->AvailableModule('leadmgmtall', $this->site_id)) {
      return true;
    }
    return false;
  }

  /**
   * Checks if there should be set an error/success message,
   * if a lead has been edited recently
   *
   * @param string $langPrefix
   *        Optional prefix for language file variables.
   */
  protected function _checkForMessages($langPrefix = '')
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    switch($get->readInt('leadedit'))
    {
      // edit not possible
      case 1:
        if (isset($_LANG[$langPrefix.'_message_lead_edit_not_possible'])) {
          $message = $_LANG[$langPrefix.'_message_lead_edit_not_possible'];
        }
        else {
          $message = $_LANG['ld_message_lead_edit_not_possible'];
        }
        $this->setMessage(Message::createFailure($message));
        break;
      // edit success
      case 2:
        if (isset($_LANG[$langPrefix.'_message_lead_edit_success'])) {
          $message = $_LANG[$langPrefix.'_message_lead_edit_success'];
        }
        else {
          $message = $_LANG['ld_message_lead_edit_success'];
        }
        $this->setMessage(Message::createSuccess($message));
        break;
      // edit no data - nothing updated
      case 3:
        if (isset($_LANG[$langPrefix.'_message_lead_edit_no_data'])) {
          $message = $_LANG[$langPrefix.'_message_lead_edit_no_data'];
        }
        else {
          $message = $_LANG['ld_message_lead_edit_no_data'];
        }
        $this->setMessage(Message::createFailure($message));
        break;
    }

    return;
  }

  /**
   * Generates HTML input text field.
   *
   * @param int $ePos
   *        Campaign form data (element) id.
   * @param int $maxLength
   *        Maximum length value of maxlength attribute.
   * @param string $value
   *        Input text field value.
   * @return string
   *         HTML input text field.
   */
  protected function _getFormTextfield($ePos, $maxLength, $value, $elementPrefix, $class = '')
  {
    $maxLength = ($maxLength > 0) ? 'maxlength="'.$maxLength.'"' : '';
    return '<input type="text" class="form-control input-sm '.$class.'" '.$maxLength.' id="'.$elementPrefix.'_element_'.$ePos.'" name="'.$elementPrefix.'_element['.$ePos.']" value="'.$value.'" />';
  }

  /**
   * Generates HTML text area.
   *
   * @param int $ePos
   *        Campaign form data (element) id.
   * @param int $maxLength
   *        Maximum length value of maxlength attribute.
   * @param string $value
   *        Text area value.
   * @return string
   *         HTML text area.
   */
  protected function _getFormTextarea($ePos, $maxLength, $value, $elementPrefix)
  {
    $maxLength = ($maxLength > 0) ? 'maxlength="'.$maxLength.'"' : '';
    return '<textarea rows="4" cols="40" '.$maxLength.' id="'.$elementPrefix.'_element_'.$ePos.'" name="'.$elementPrefix.'_element['.$ePos.']" class="form-control input-sm">'.$value.'</textarea>';
  }

  /**
   * Generates a HTML select combobox.
   *
   * @param int $ePos
   *        Campaign form data (element) id.
   * @param array $values
   *        Option labels of select element.
   * @param int $prechecked
   *        Id of preselected select option.
   * @return string
   *         HTML select field.
   */
  protected function _getFormCombobox($ePos, $values, $prechecked, $elementPrefix)
  {
    $select = '<select id="'.$elementPrefix.'_element_'.$ePos.'" name="'.$elementPrefix.'_element['.$ePos.']" class="form-control input-sm">';
    $select .= $this->_getFormComboboxOptions($ePos, $values, $prechecked, $elementPrefix);
    $select .= '</select>';
    return $select;
  }

  /**
   * Generates HTML select options.
   *
   * @param int $ePos
   *        Campaign form data (element) id.
   * @param array $values
   *        Option labels of select element.
   * @param int $prechecked
   *        Id of preselected select option.
   * @return string
   *         HTML select options.
   */
  protected function _getFormComboboxOptions($ePos, $values, $prechecked, $elementPrefix)
  {
    global $_LANG;

    $options = '<option value="0">'.$_LANG[$this->_prefix.'_combobox_please_choose'].'</option>';

    $useKeys = !isset($values[0]);
    $i = 1;
    foreach ($values as $key => $value) {
      $id = $useKeys ? $key : $i;
      $selected = ($prechecked == $id) ? 'selected="selected"' : '';
      $options .= '<option value="'.$id.'" '.$selected.'>'.$value.'</option>';
      $i ++;
    }

    return $options;
  }

  /**
   * Generates single HTML checkbox
   *
   * @param int  $ePos
   *        Campaign form data (element) id.
   * @param int  $prechecked
   *        Id of prechecked checkbox.
   * @param string $elementPrefix
   * @param bool $readonly [optional|default=false]
   *        set to true to generate real readonly chackboxes
   *
   * @return string HTML checkbox.
   */
  protected function _getFormCheckbox($ePos, $prechecked, $elementPrefix, $readonly = false)
  {
    $prechecked = ($prechecked == '1') ? ' checked="checked" ' : '';

    if ($readonly) {
      // Readonly field have to be set disabled in order to provide a good interface
      // for the user. That's why we append the value in a hidden field here.
      return sprintf(
        '<input type="checkbox" %s disabled readonly value="1" /><input type="hidden" id="%s" name="%s" value="%s"/>',
        $prechecked,
        $elementPrefix.'_element_'.$ePos,
        $elementPrefix.'_element['.$ePos.']',
        $prechecked ? 1 : 0
      );
    }
    else {
      return '<input type="checkbox" id="'.$elementPrefix.'_element_'.$ePos.'" name="'.$elementPrefix.'_element['.$ePos.']"' . $prechecked . ' value="1" />';
    }
  }

  /**
   * Generates HTML checkbox group.
   * Each checkbox has got an own label.
   *
   * @param int $ePos
   *        Campaign form data (element) id.
   * @param array $value
   *        Value of label.
   * @param array $prechecked
   *        Ids of prechecked checkboxes.
   * @return string
   *         HTML checkboxes with labels.
   */
  protected function _getFormCheckboxGroup($ePos, $value, $prechecked, $elementPrefix)
  {
    $prechecked = explode('$', $prechecked);
    $values = explode('$', $value);
    $group = '<div class="checkbox">';
    $i = 1;
    $count = count($values);
    foreach ($values as $value)
    {
      $checked = (in_array($i, $prechecked)) ? 'checked="checked"' : '';
      $group .= '<input type="checkbox" id="'.$elementPrefix.'_element_'.$ePos.'_'.$i.'" name="'.$elementPrefix.'_element['.$ePos.'][]" '.$checked.' value="'.$i.'" />'
             .  ' <label for="'.$elementPrefix.'_element_'.$ePos.'_'.$i.'">'.$value.'</label>'."\n";
      // not last item, so add br
      if ($i < $count) {
        $group .= '<br />';
      }
      $i ++;
    }
    $group .= '</div>';
    return $group;
  }

  /**
   * Generates HTML radiobuttons.
   * Each radiobutton has got an own label.
   *
   * @param int $ePos
   *        Campaign form data (element) id.
   * @param array $value
   *        Value of label.
   * @param array $prechecked
   *        Ids of prechecked radiobuttons.
   * @return string
   *         HTML radiobuttons with labels
   */
  protected function _getFormRadiobutton($ePos, $value, $prechecked, $elementPrefix)
  {
    $values = explode('$', $value);
    $group = '<div class="radio">';
    $i = 1;
    $count = count($values);
    foreach ($values as $value)
    {
      $checked = ($prechecked == $i) ? 'checked="checked"' : '';
      $group .= '<input type="radio" id="'.$elementPrefix.'_element_'.$ePos.'_'.$i.'" name="'.$elementPrefix.'_element['.$ePos.']" '.$checked.' value="'.$i.'" />'
             .  ' <label for="'.$elementPrefix.'_element_'.$ePos.'_'.$i.'">'.$value.'</label>'."\n";
      // not last item, so add br
      if ($i < $count) {
        $group .= '<br />';
      }
      $i ++;
    }
    $group .= '</div>';
    return $group;
  }

  /**
   * Generates a link to file and a button for deleting it. Currently there
   * isn't a file upload field created on EDWIN CMS backend.
   *
   * @param int $ePos
   *        Campaign form data (element) position.
   * @param string $value
   *        Value of label.
   * @param string $deleteUrl
   *        the url to call for deleting the uploaded file from filesystem
   *
   * @return string
   *         HTML upload field with label and existing upload.
   */
  protected function _getFormUpload($ePos, $value, $deleteUrl, $elementPrefix)
  {
    global $_LANG;

    if ($value) {
      $filename = (array)explode('/', $value);
      $filename = array_pop($filename);
      $str = sprintf($_LANG['lg_lead_upload_link'], $this->_navigation->getCurrentSite()->getUrl() . $value, $filename)
            . sprintf($_LANG['lg_lead_upload_delete_link'], $deleteUrl);
    }
    else {
      $str = $_LANG['lg_lead_no_upload_label'];
    }

    $str .= '<br/><input type="file" id="'.$elementPrefix.'_element_'.$ePos.'" name="'.$elementPrefix.'_element['.$ePos.']"/>';

    return $str;
  }

  /**
   * Parse the module top content (for modules with subclasses (submodules))
   * and return the parsed template.
   *
   * @return string
   *         The parsed module_top.tpl (or a module specific template if found)
   */
  protected function _getContentTop()
  {
    global $_LANG;

    // Load the active module types
    $sql = ' SELECT MClass, MShortname '
         . " FROM {$this->table_prefix}moduletype_backend "
         . ' WHERE MActive = 1 ';
    $modules = $this->db->GetAssoc($sql);

    $content = '';
    $subClasses = array();
    $subclassesAvailable = 0;
    if (isset($this->_subClasses) && $this->_subClasses) {
      $subclassesAvailable = 1;
    }

    // top only displayed when subclasses defined
    if ($subclassesAvailable)
    {
      // main class
      // here we check the first element of $this->originalAction because in $this->action
      // the first element "main" could already be cut away (i.e. if the action was "main;new")
      $subClasses[] = array (
        'item_state' => ("main" == $this->originalAction[0] || !$this->originalAction[0] ? 'active' : 'inactive'),
        'item_link'  => "index.php?action=mod_".$modules[get_class($this)]."&amp;action2=main&amp;site=".$this->site_id,
        'item_label' => $_LANG["modtop_".get_class($this)],
        'item_class' => get_class($this),
      );

      foreach ($this->_subClasses as $action => $value)
      {
        // Do not add the submodule in case it is not available for the
        // current user.
        if (!$this->_user->AvailableSubmodule($modules[get_class($this)], $action)) {
          continue;
        }

        $subClasses[] = array (
          'item_state' => ($this->action && $action == $this->action[0] ? 'active' : 'inactive'),
          'item_link'  => "index.php?action=mod_".$modules[get_class($this)]."&amp;action2=".$action."&amp;site=".$this->site_id,
          'item_label' => $_LANG["modtop_".$value],
          'item_class' => $value,
        );
      }

      // Check if there is a special template for the module top navigation.
      if (is_file('./templates/modules/'.get_class($this).'_module_top.tpl')) {
        $this->tpl->load_tpl('content_top', 'modules/'.get_class($this).'_module_top.tpl');
      }
      else {
        $this->tpl->load_tpl('content_top', 'module_top.tpl');
      }

      $this->tpl->parse_loop('content_top', $subClasses, 'sub_classes');
      $content = $this->tpl->parsereturn('content_top', array(
        'module_class' => 'module_top_'.$modules[get_class($this)],
      ));
    }

    return $content;
  }

  /**
   * Reads all active and archived campaigns from db
   * and generates data array.
   *
   * @return array
   *         Array with campaign id and name or empty array.
   */
  protected function _readCampaigns()
  {
    $sql = " SELECT CGID, CGName, CGTID, CGTName "
         . " FROM {$this->table_prefix}campaign "
         . " INNER JOIN {$this->table_prefix}campaign_type "
         . "    ON CGTID = FK_CGTID "
         . " WHERE CGStatus = ? "
         . "    OR CGStatus = ? "
         . " ORDER BY CGTPosition ASC, CGPosition ASC ";
    $campaigns = array();
    $results = $this->db->q($sql, array(self::CAMPAIGN_STATUS_ACTIVE, self::CAMPAIGN_STATUS_ARCHIVED))->fetchAll();
    $lastcgTId = 0;
    foreach ($results as $row) {
      if ($lastcgTId != $row['CGTID']) {
        $lastcgTId = $row['CGTID'];
        $campaigns[$row['CGID']]['optgroup']  = parseOutput($row['CGTName']);
        $campaigns[$row['CGID']]['optgroup_id']  = $row['CGTID'];
      }
      $campaigns[$row['CGID']]['label'] = parseOutput($row['CGName']);
    }

    return $campaigns;
  }

  /**
   * Reads all EDWIN users, that are allowed
   * to use the lead managment module.
   *
   * @return array
   *         Contains user's firstname, lastname and nickname.
   */
  protected function _readLeadAgentUsers()
  {
    $sql = " SELECT UID, UFirstname, ULastname, UNick, UDeleted "
         . " FROM {$this->table_prefix}user "
         . " WHERE UModuleRights LIKE '%leadmgmt%' "
         . " ORDER BY UNick ASC ";
    return $this->_getLeadAgentUserSelectionValues($this->db->GetAssoc($sql));
  }

  /**
   * Reads all EDWIN users, that are allowed
   * to use the lead managment module.
   *
   * @param int $thisUserIdAlsoIfDeleted
   *
   * @return array Contains user's firstname, lastname and nickname.
   * Contains user's firstname, lastname and nickname.
   */
  protected function _readActiveLeadAgentUsers($thisUserIdAlsoIfDeleted = 0)
  {
    $thisUserIdAlsoIfDeleted = (int)$thisUserIdAlsoIfDeleted;

    $sql = " SELECT UID, UFirstname, ULastname, UNick, UDeleted "
         . " FROM {$this->table_prefix}user "
         . " WHERE UModuleRights LIKE '%leadmgmt%' "
         . " AND ( UDeleted = 0 OR UID = $thisUserIdAlsoIfDeleted ) "
         . " ORDER BY UNick ASC ";
    return $this->_getLeadAgentUserSelectionValues($this->db->GetAssoc($sql));
  }

  /**
   * Reads future appointments from db.
   *
   * @return boolean|int
   *         Future appointments or false on failure.
   */
  protected function _readNextAppointment()
  {
    if ($this->_cachedNextAppointments !== null) {
      return $this->_cachedNextAppointments;
    }

    $futureDate = time() + ConfigHelper::get('ld_appointment_reminder_period') * 60;

    $sql = ' SELECT CFirstname, CLastname, CGLADateTime '
         . " FROM {$this->table_prefix}campaign_lead_appointment cla "
         . " INNER JOIN {$this->table_prefix}campaign_lead "
         . '   ON CGLID = FK_CGLID '
         . " LEFT JOIN {$this->table_prefix}client "
         . '   ON CID = FK_CID '
         . " WHERE CGLAStatus = '".self::APPOINTMENT_OPEN."' "
         . "   AND cla.FK_UID = {$this->_user->getID()} "
         . "   AND CGLADateTime > '".date('Y-m-d H:i:s')."' "
         . "   AND CGLADateTime <= '".date('Y-m-d H:i:s', $futureDate)."' "
         . "   AND CGLDeleted = 0 "
         . ' ORDER BY CGLADateTime ASC '
         . ' LIMIT 0, 1 ';

    $this->_cachedNextAppointments = $this->db->GetRow($sql);

    return $this->_cachedNextAppointments;
  }

  /**
   * Reads the number of expired appointments from db.
   *
   * @return boolean|int
   *         Number of expired appointments or false on failure.
   */
  protected function _readNumberOfExpiredAppointments()
  {
    if ($this->_cachedExpiredAppointments !== null) {
      return $this->_cachedExpiredAppointments;
    }

    $sql = ' SELECT COUNT(CGLAID) '
         . " FROM {$this->table_prefix}campaign_lead_appointment cgla "
         . " INNER JOIN {$this->table_prefix}campaign_lead "
         . '   ON FK_CGLID = CGLID '
         . " INNER JOIN {$this->table_prefix}client c "
         . '   ON FK_CID = CID '
         . " WHERE CGLAStatus = '".self::APPOINTMENT_OPEN."' "
         . "   AND c.FK_UID = {$this->_user->getID()} "  // do not show message-appointments of other users!
         . "   AND cgla.FK_UID = {$this->_user->getID()} "
         . "   AND CGLADateTime < '".date('Y-m-d H:i:s')."'"
         . "   AND CGLDeleted = 0 ";
    $this->_cachedExpiredAppointments = (int) $this->db->GetOne($sql);

    return $this->_cachedExpiredAppointments;
  }

  /**
   * Reads the number of urgent appointments from db.
   *
   * @return boolean|int
   *         Number of urgent appointments or false on failure.
   */
  protected function _readNumberOfUrgentAppointments()
  {
    $sql = ' SELECT COUNT(CGLAID) '
         . " FROM {$this->table_prefix}campaign_lead_appointment cgla "
         . " INNER JOIN {$this->table_prefix}campaign_lead "
         . '   ON FK_CGLID = CGLID '
         . " INNER JOIN {$this->table_prefix}client c "
         . '   ON FK_CID = CID '
         . " WHERE CGLAStatus = '".self::APPOINTMENT_OPEN."' "
         . "   AND c.FK_UID = {$this->_user->getID()} "
         . "   AND cgla.FK_UID != {$this->_user->getID()} "
         . '   AND CGLDeleted = 0 ';
    return (int) $this->db->GetOne($sql);
  }

  /**
   * Parses template of selected day's appointments and
   * returns it to the client.
   *
   * @return string
   *         Parsed EDWIN template.
   */
  protected function _sendResponseAppointmentsOfDate()
  {
    global $_LANG2;

    $input = new Input(Input::SOURCE_REQUEST);

    $date = $input->readString('date');

    if (DateHandler::isValidDate($date))
    {
      $sqlDay = DateHandler::getValidDate($date, 'Y-m-d');
      $shorttextMaxlength = ConfigHelper::get('ld_appointment_shorttext_maxlength', '', $this->site_id);
      $shorttextAftertext = ConfigHelper::get('shorttext_aftertext', 'ld_appointment', $this->site_id);
      $dayPart1 = ConfigHelper::get('ld_day_part1');
      $dayPart2 = ConfigHelper::get('ld_day_part2');
      $dayPart3 = ConfigHelper::get('ld_day_part3');

      $formatedDay = date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ln'), strtotime($date));

      $sql = ' SELECT CGLAID, CGLACreateDateTime, CGLADateTime, CGLAStatus, '
           . '        CGLATitle, CGLAText, '
           . '        CFirstname, CLastname '
           . " FROM {$this->table_prefix}campaign_lead_appointment cla "
           . " INNER JOIN {$this->table_prefix}campaign_lead "
           . '   ON FK_CGLID = CGLID '
           . " INNER JOIN {$this->table_prefix}client "
           . '   ON FK_CID = CID '
           . " WHERE cla.FK_UID = {$this->_user->getID()} "
           . "   AND CGLAStatus = '".self::APPOINTMENT_OPEN."' "
           . "   AND CGLADateTime >= '{$sqlDay} 00:00:00' "
           . "   AND CGLADateTime <= '{$sqlDay} 23:59:59' "
           . '   AND CGLDeleted = 0 '
           . ' ORDER BY CGLADateTime ASC ';

      $res = $this->db->query($sql);

      $appointmentRows = array();
      while ($row = $this->db->fetch_row($res))
      {
        $dateTime = strtotime($row["CGLADateTime"]);
        $createDate = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ln'), strtotime($row['CGLACreateDateTime']));
        $dayPart = 1;
        if ($dayPart1['start'] <= date('H:i', $dateTime) && date('H:i', $dateTime) <= $dayPart1['end']) {
          $dayPart = 1;
        }
        else if ($dayPart2['start'] <= date('H:i', $dateTime) && date('H:i', $dateTime) <= $dayPart2['end']) {
          $dayPart = 2;
        }
        else if ($dayPart3['start'] <= date('H:i', $dateTime) && date('H:i', $dateTime) <= $dayPart3['end']) {
          $dayPart = 3;
        }
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
        $appointmentRows[$dayPart][] = array (
          'ld_create_date' => $createDate,
          'ld_date'        => $formatedDay,
          'ld_time'        => date('H:i', $dateTime),
          'ld_title'       => parseOutput($row['CGLATitle']),
          'ld_text'        => nl2br(parseOutput($row['CGLAText'])),
          'ld_shorttext'   => parseOutput($shortText, 1),
          'ld_cfirstname'  => parseOutput($row['CFirstname']),
          'ld_clastname'   => parseOutput($row['CLastname'])
        );
      }
      $this->db->free_result($res);

      if ($appointmentRows)
      {
        $this->tpl->load_tpl('content_appointments', 'modules/ModuleLeadManagement_appointments.tpl');
        $this->tpl->parse_loop('content_appointments', isset($appointmentRows[1]) ? $appointmentRows[1] : array(), 'appointment_rows1');
        $this->tpl->parse_loop('content_appointments', isset($appointmentRows[2]) ? $appointmentRows[2] : array(), 'appointment_rows2');
        $this->tpl->parse_loop('content_appointments', isset($appointmentRows[3]) ? $appointmentRows[3] : array(), 'appointment_rows3');
        return $this->tpl->parsereturn('content_appointments', array_merge( array(
          'ld_date' => $formatedDay
        ), $_LANG2['ld']));
      }
    }
    else {
      return '';
    }
  }

  /**
   * Parses template of reminder and returns it to the client.
   *
   * @return string
   *         Parsed EDWIN template.
   */
  protected function _sendResponseReminderMessages()
  {
    $urgent = $this->_getUrgentAppointmentsLang();
    $expired = $this->_getExpiredAppointmentsLang();
    $next = $this->_getNextAppointmentsLang();

    $this->tpl->load_tpl('content_reminder', 'modules/ModuleLeadManagement_reminder.tpl');
    $this->tpl->parse_if('content_reminder', 'urgent_message', $urgent, array(
      'ld_urgent_message' => $urgent,
    ));
    $this->tpl->parse_if('content_reminder', 'expired_message', $expired, array(
      'ld_expired_message' => $expired,
    ));
    $this->tpl->parse_if('content_reminder', 'future_message', $next, array(
      'ld_future_message' => $next,
    ));
    return $this->tpl->parsereturn('content_reminder', array());
  }

  /**
   * Parses template of status history (of requested lead) and
   * returns it to the client.
   *
   * @return string
   *         Parsed EDWIN template.
   */
  protected function _sendResponseStatusHistory()
  {
    global $_LANG2;

    $input = new Input(Input::SOURCE_REQUEST);

    $leadId = $input->readInt('lid');
    $sHistory = $this->_readStatusHistory($leadId);

    $this->tpl->load_tpl('content_shistory', 'modules/ModuleLeadManagement_history.tpl');
    $this->tpl->parse_loop('content_shistory', $sHistory, 'status_history');
    foreach ($sHistory as $key => $row)
    {
      $this->tpl->parse_if('content_shistory', 'status_image_'.$key, $row['status_image_name']);
      $this->tpl->parse_if('content_shistory', 'status_name_'.$key, !$row['status_image_name']);
      $this->tpl->parse_if('content_shistory', 'status_system_user_'.$key, !$row['status_history_user_id']);
      $this->tpl->parse_if('content_shistory', 'status_normal_user_'.$key, $row['status_history_user_id']);
    }
    $this->tpl->parse_if('content_shistory', 'status_history', $sHistory);

    return $this->tpl->parsereturn('content_shistory', array($_LANG2['ld']));
  }

  /**
   * Assigns client/lead to given user.
   * New entries to manipulation log and lead history
   * are going to be added.
   *
   * @param int $clientId
   *        The client id.
   * @param int $userId
   *        The user id.
   * @param int $leadId
   *        The lead id.
   * @param int $cgId
   *        Lead's campaign id.
   * @param int $statusId
   *        Current lead status id.
   * @return boolean
   *         True on success, false on error.
   */
  protected function _takeClient($clientId, $userId, $leadId, $cgId, $statusId=0)
  {
    global $_LANG;

    $now = date('Y-m-d H:i:s');
    $where = ' AND FK_UID = 0 ';
    // users with status lead administrator are allowed to take/assign
    // already taken leads.
    if ($this->_isUserLeadAdmin()) {
      $where = '';
    }

    $sql = " SELECT FK_UID "
         . " FROM {$this->table_prefix}client "
         . " WHERE CID = {$clientId} "
         . "   AND FK_UID = {$userId} ";
    $userTaken = $this->db->GetOne($sql);

    // return, if user is already assigned to this client
    if ($userTaken) {
      return false;
    }

    // take client
    $sql = " UPDATE {$this->table_prefix}client "
         . " SET FK_UID = {$userId} "
         . " WHERE CID = {$clientId} "
         . " {$where} ";
    $res = $this->db->query($sql);

    // only add log entries if lead has been taken or
    // has been assigned to a NEW user
    if ($res)
    {
      // get lead's last status id, if not set
      if (!$statusId)
      {
        // read last status id
        $sql = 'SELECT FK_CGSID '
             . "FROM {$this->table_prefix}campaign_lead "
             . "WHERE CGLID = {$leadId} ";
        $statusId = $this->db->GetOne($sql);
      }
      // get user's full name
      if ($this->_user->getID() != $userId)
      {
        $sql = " SELECT CONCAT_WS('', UFirstname, ' ', ULastname,' (', UNick, ')') "
             . " FROM {$this->table_prefix}user "
             . " WHERE UID = {$userId} ";
        $fullUserName = $this->db->GetOne($sql);
      }
      else {
        $fullUserName = $this->_user->getFirstName().' '.$this->_user->getLastName().' ('.$this->_user->getNick().')';
      }

      // add entry to manipulation log
      $this->_addManipulatedLogEntry($leadId, $now);

      // history entry
      $this->_addStatusHistoryEntry($leadId, $cgId, sprintf($_LANG['ld_lead_taken'], $fullUserName), $statusId, $now);

      return true;
    }

    return false;
  }

  /**
   * @param int $leadId
   * @param int $cgId
   * @param string $text
   * @param int $statusId
   * @param string $now
   * @return int
   */
  protected function _addStatusHistoryEntry($leadId, $cgId, $text, $statusId, $now='')
  {
    $leadId = (int) $leadId;
    $cgId = (int) $cgId;
    $statusId = (int) $statusId;

    if (!$now) {
      $now = date('Y-m-d H:i:s');
    }

    $sql = " INSERT INTO {$this->table_prefix}campaign_lead_status "
      . " ( FK_CGLID, FK_CGID, CGLSDateTime, CGLSText, FK_CGSID, FK_UID ) "
      . " VALUES ( ?, ?, ?, ?, ?, ? ) ";
    $this->db->q($sql, array($leadId, $cgId, $now, $text, $statusId, $this->_user->getID()));
    return $this->db->insert_id();
  }

  /**
   * @param int $leadId
   * @return AbstractModel|BackendUser
   */
  protected function _getAssignedLeadAgentUserOfLead($leadId)
  {
    $leadId = (int) $leadId;

    $sql = " SELECT FK_UID FROM {$this->table_prefix}client  "
      . " INNER JOIN {$this->table_prefix}campaign_lead "
      . " ON FK_CID = CID "
      . " WHERE CGLID = ? ";
    $result = $this->db->q($sql, array($leadId))->fetch();

    $beUser = new BackendUser($this->db, $this->table_prefix);
    $beUser = $beUser->readItemById($result ? $result['FK_UID'] : 0);

    return $beUser;
  }

  /**
   * Gets formatted language string of expired appointments.
   *
   * @return String
   */
  private function _getExpiredAppointmentsLang()
  {
    global $_LANG;

    $url = 'index.php?action=mod_leadmgmt&amp;action2=appointments&amp;site='.$this->site_id;
    $count = $this->_readNumberOfExpiredAppointments();
    if ($count > 1) {
      return sprintf($_LANG['ld_message_expired_appointments'], $count, $url);
    }
    else if ($count == 1) {
      return sprintf($_LANG['ld_message_expired_appointment'], $url);
    }

    return '';
  }

  /**
   * Gets formatted language string of next appointment in configured period.
   *
   * @return String
   */
  private function _getNextAppointmentsLang()
  {
    global $_LANG;

    $next = $this->_readNextAppointment();
    if ($next)
    {
      $minutes = (int) ((strtotime($next['CGLADateTime']) - time()) / 60);
      $url = 'index.php?action=mod_leadmgmt&amp;action2=appointments&amp;site='.$this->site_id;
      $client = parseOutput($next['CFirstname']).' '.parseOutput($next['CLastname']);
      if ($minutes > 1) {
        return sprintf($_LANG['ld_message_next_appointment'], $minutes, date('H:i', strtotime($next['CGLADateTime'])), $client, $url);
      }
      else if ($minutes == 1) {
        return sprintf($_LANG['ld_message_next_appointment_one'], date('H:i', strtotime($next['CGLADateTime'])), $client, $url);
      }
    }
    return '';
  }

  /**
   * Gets formatted language string of urgent appointments.
   * Urgent appointments are usually notifications of other users (agents).
   *
   * @return String
   */
  private function _getUrgentAppointmentsLang()
  {
    global $_LANG;

    $url = 'index.php?action=mod_leadmgmt&amp;action2=appointments&amp;site='.$this->site_id;
    $count = $this->_readNumberOfUrgentAppointments();
    if ($count > 1) {
      return sprintf($_LANG['ld_message_urgent_appointments'], $count, $url);
    }
    else if ($count == 1) {
      return sprintf($_LANG['ld_message_urgent_appointment'], $url);
    }

    return '';
  }

  /**
   * Reads status history of current lead from db.
   *
   * @return array|null
   *         Returns status history or null if history could
   *         not be read from db.
   */
  private function _readStatusHistory($leadId)
  {
    global $_LANG;

    $shorttextMaxlength = ConfigHelper::get('ld_status_history_shorttext_maxlength', '', $this->site_id);
    $shorttextAftertext = ConfigHelper::get('shorttext_aftertext', 'ld_status_history', $this->site_id);

    $sql =  '  SELECT CGSID AS OrderId, CGLSText AS Text, FK_CGSID, CGLSDateTime AS OrderDateTime, CGLSDateTime AS DateTime, '
         . '          UID, UNick, UFirstname, ULastname, '
         . '          CGSName AS Status '
         . "   FROM {$this->table_prefix}campaign_lead_status "
         . "   LEFT JOIN {$this->table_prefix}user "
         . '     ON FK_UID = UID '
         . "   LEFT JOIN {$this->table_prefix}campaign_status "
         . '     ON FK_CGSID = CGSID '
         . "   WHERE FK_CGLID = {$leadId} "
         . ' UNION ALL '
         . '   SELECT CGLAID, CGLAText, NULL, COALESCE(CGLAChangeDateTime, CGLACreateDateTime), CGLADateTime, '
         . '          UID, UNick, UFirstname, ULastname, '
         . '          CGLAStatus '
         . "   FROM {$this->table_prefix}campaign_lead_appointment "
         . "   LEFT JOIN {$this->table_prefix}user "
         . '     ON FK_UID = UID '
         . "   WHERE FK_CGLID = {$leadId} "
         . ' ORDER BY OrderDateTime DESC, OrderId ASC ';

    $res = $this->db->query($sql);
    $status = array();
    if ($res)
    {
      $i = 1;
      while ($row = $this->db->fetch_row($res))
      {
        $shortText = nl2br($row['Text']);
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
        $createDate = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), $this->_prefix), strtotime($row['DateTime']));
        $status[] = array (
          'status_row_id'                 => ($i - 1),
          'status_history_id'             => (int) $row['FK_CGSID'],
          'status_history_text'           => parseOutput(nl2br($row['Text'])),
          'status_history_shorttext'      => parseOutput($shortText, 1),
          'status_history_datetime'       => $createDate,                  // create date-time of status history entry
          'status_history_name'           => parseOutput($row['Status']), // status name/title
          'status_history_user_id'        => $row['UID'],
          'status_history_user_nick'      => ($row['UID']) ? parseOutput($row['UNick']) : '',
          'status_history_user_firstname' => ($row['UID']) ? parseOutput($row['UFirstname']) : '',
          'status_history_user_lastname'  => ($row['UID']) ? parseOutput($row['ULastname']) : '',
          'status_history_system_label'   => $_LANG['ld_system_label'],
          'status_row_bg'                 => ($i++ % 2) ? 'row1' : 'row2',
          'status_type_class'             => (!$row['FK_CGSID']) ? 'status_appointment' : 'status_normal',
          'status_image_name'             => (!$row['FK_CGSID']) ? mb_strtolower(parseOutput($row['Status'])) : '',
          'status_history_image_title'    => (!$row['FK_CGSID']) ? $_LANG['ld_status_history_'.mb_strtolower($row['Status'])] : '',
        );
      }
    }

    return $status;
  }

  /**
   * Returns the list of users for the lead agent user selection with formatted
   * values for dropdowns
   *
   * @param array $rows
   *
   * @return array
   */
  private function _getLeadAgentUserSelectionValues($rows)
  {
    global $_LANG;

    $users = array();
    if ($rows && is_array($rows)) {
      foreach ($rows as $id => $row) {
        $user = sprintf('%s %s (%s)', $row['UFirstname'], $row['ULastname'], $row['UNick']);
        $user = trim($user);
        $user = sprintf('%s %s', $user, $row['UDeleted'] ? $_LANG['ln_assigned_user_deleted_label'] : '');
        $user = trim($user);

        $users[$id] = $user;
      }
    }

    return $users;
  }
}
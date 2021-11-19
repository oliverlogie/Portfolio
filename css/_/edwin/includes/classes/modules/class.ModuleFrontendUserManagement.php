<?php

/**
 * Module for management of frontend users
 *
 * $LastChangedDate: 2018-07-13 09:25:43 +0200 (Fr, 13 Jul 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ModuleFrontendUserManagement extends Module
{
  public static $subClasses = array('company' => 'ModuleFrontendUserManagementCompany');

  /**
   * @var string
   */
  protected $_prefix = 'fu';

  /**
   * The logger.
   *
   * @var GeneralFrontendUserLog
   */
  private $_logger;

  /**
   * Show inner content
   */
  public function show_innercontent()
  {
    global $_LANG;

    $this->_logger = new GeneralFrontendUserLog($this->db, $this->table_prefix);
    $this->_logger->setBackendUser($this->_user);

    $action = isset($this->action[0]) ? $this->action[0] : null;
    if (isset($_POST["process"]) && $action=="new") {
      $this->create_content();
    }
    if (isset($_POST['process']) && $action=='edit') {
      $this->edit_content();
    }
    if ($action=='delete') {
      $this->delete_content();
    }
    if ($action == 'ut_page') {
      $this->_userTreePage();
    }
    if (isset($_POST['process_reset'])) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }

    if (!$action) {
      return $this->_getContentList();
    }
    else {
      if ($action == 'export') {
        return $this->_getCsv();
      }
      return $this->get_content();
    }
  }

  protected function _initGrid()
  {
    $get = new Input(Input::SOURCE_GET);

    // 1. grid sql
    $gridSql = " SELECT FUID, FUCompany, FUPosition, FUTitle, FUFirstname, "
             . "        FUMiddlename, FULastname, FUNick, FUBirthday, "
             . "        FUCountry, FUZIP, FUCity, FUAddress, FUPhone, "
             . "        FUMobilePhone, FUFax, FUDepartment, FUEmail, FUNewsletter, "
             . "        FUCreateDateTime, FUChangeDateTime, FULastLogin, "
             . "        FUCountLogins, FK_FID, FK_FUCID_Company, "
             . "        root.CIID AS RootPage, MAX(ci.CIID) AS ContentPage "
             . " FROM {$this->table_prefix}frontend_user fu "
             . " LEFT JOIN {$this->table_prefix}frontend_user_rights r "
             . "        ON fu.FUID = r.FK_FUID "
             . " LEFT JOIN {$this->table_prefix}frontend_user_group g "
             . "        ON r.FK_FUGID = g.FUGID "
             . " LEFT JOIN {$this->table_prefix}contentitem root "
             . "        ON root.FK_FUID = FUID AND root.FK_CIID IS NULL "
             . " LEFT JOIN {$this->table_prefix}contentitem ci "
             . "        ON ci.FK_FUID = FUID AND ci.FK_CIID IS NOT NULL "
             . " WHERE FUDeleted = 0 ";

    // 2. fields = columns
    $queryFields[1] = array('type' => 'text', 'value' => 'FUNick', 'lazy' => true);
    $queryFields[2] = array('type' => 'text', 'value' => 'FUEmail', 'lazy' => true);
    $queryFields[3] = array('type' => 'selective', 'value' => 'FK_FUGID');

    // 3. filter fields = query fields as we do not need additional fields to be
    // filterable
    $filterFields = $queryFields;

    // 4. filter types
    $filterTypes = array(
      'FUNick'   => 'text',
      'FUEmail'  => 'text',
      'FK_FUGID' => 'selective',
    );

    // 5. order options
    $ordersValuelist = array(
      1 => array('field' => 'FUNick',  'order' => 'ASC'),
      2 => array('field' => 'FUNick',  'order' => 'DESC'),
      3 => array('field' => 'FUEmail', 'order' => 'ASC'),
      4 => array('field' => 'FUEmail', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;

    $presetOrders = array(1 => 1);

    // 5. page
    $page = ($get->exists('fu_page')) ? $get->readInt('fu_page') : ($this->session->read('fu_page') ? $this->session->read('fu_page') : 1);
    $this->session->save('fu_page', $page);

    // 6. prepare selective data for dropdown values
    $sql = " SELECT FUGID, FUGName "
        . " FROM {$this->table_prefix}frontend_user_group "
        . " ORDER BY FUGName ASC ";
    $groups = (array)$this->db->GetAssoc($sql);
    foreach ($groups as $id => $value) {
        $groups[$id] = array('label' => $value);
    }

    $selectiveData = array('FK_FUGID' => $groups);

    // 7. prefix
    $prefix = array('config'  => $this->_prefix,
                    'lang'    => $this->_prefix,
                    'session' => $this->_prefix,
                    'tpl'     => $this->_prefix);

    //---------------------------------------------------------------------- //
    $grid = new DataGrid($this->db, $this->session, $prefix);
    $grid->setSelectiveData($selectiveData);
    $grid->load($gridSql, $queryFields, $filterFields, $filterTypes,
                $orders, $page, false, null, $presetOrders, null, null,
                ConfigHelper::get($this->_prefix . '_results_per_page'),
                null, 'GROUP BY FUID, FUCompany, FUPosition, FUTitle, FUFirstname, FUMiddlename, FULastname, FUNick, FUBirthday, '
                          . "   FUCountry, FUZIP, FUCity, FUAddress, FUPhone, "
                          . "   FUMobilePhone, FUFax, FUDepartment, FUEmail, FUNewsletter, "
                          . "   FUCreateDateTime, FUChangeDateTime, FULastLogin, "
                          . "   FUCountLogins, FK_FID, FK_FUCID_Company, "
                          . "   root.CIID " );
    return $grid;
  }

  protected function _getContentLeftLinks()
  {
    $links = parent::_getContentLeftLinks();
    if (empty($this->action[0])) { // list view displayed
      $links[] = array($this->_parseUrl('export'), $this->_langVar('moduleleft_export_label'));
    }

    return $links;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Create Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function create_content(){
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $passwordLength = (int)ConfigHelper::get('m_login_password_length');
    $quality = (int)ConfigHelper::get('m_login_password_quality');
    $passwordTypes = ConfigHelper::get('m_login_password_types');

    $title = $post->readString('fu_title', Input::FILTER_PLAIN);
    $company = $post->readString('fu_company', Input::FILTER_PLAIN);
    $companyId = $post->readInt('fu_company_id', 0);
    $position = $post->readString('fu_position', Input::FILTER_PLAIN);
    $firstname = $post->readString('fu_firstname', Input::FILTER_PLAIN);
    $middlename = $post->readString('fu_middlename', Input::FILTER_PLAIN);
    $lastname = $post->readString('fu_lastname', Input::FILTER_PLAIN);
    $street = $post->readString('fu_street', Input::FILTER_PLAIN);
    $country = $post->readInt('fu_country', 1);
    $zip = $post->readString('fu_zip', Input::FILTER_PLAIN);
    $city = $post->readString('fu_city', Input::FILTER_PLAIN);
    $email = $post->readString('fu_email', Input::FILTER_PLAIN);
    $phone = $post->readString('fu_phone', Input::FILTER_PLAIN);
    $mobile = $post->readString('fu_mobile', Input::FILTER_PLAIN);
    $nick = $post->readString('fu_nick', Input::FILTER_PLAIN);
    $password = $post->readString('fu_password', Input::FILTER_NONE);
    $password2 = $post->readString('fu_password2', Input::FILTER_NONE);
    $birthday = $post->readDate('fu_birthday');
    $uid = $post->readString('fu_uid', Input::FILTER_PLAIN);
    $groups = $post->readArray('fu_group', array());
    $newsletter = $post->exists('fu_newsletter') ? 1 : 0;
    $foa = $post->readInt('fu_foa', 1);
    $fax = $post->readString('fu_fax', Input::FILTER_PLAIN);
    $department = $post->readString('fu_department', Input::FILTER_PLAIN);

    $pwHelper = new Password();
    $pwQuality = $pwHelper->setPassword($password)->getCalculatedQuality();

    $result = 0;
    if (!$nick || !$email) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_insufficient_input']));
    }
    else if (!$this->_validateNickIsUnique($nick)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_user_exists']));
    }
    else if (!$this->_validatEmailIsUnique($email)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_email_exists']));
    }
    else if (!Validation::isEmail($email)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_email']));
    }
    else if ($zip && !isNumber($zip)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_zip']));
    }
    else if ($phone && !Validation::isPhoneNumber($phone)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_phone']));
    }
    else if ($mobile && !Validation::isPhoneNumber($mobile)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_mobile']));
    }
    else if ($password && $password != $password2) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_password_mismatch']));
    }
    else if (mb_strlen($password) < $passwordLength) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['fu_message_invalid_too_short'], $passwordLength)));
    }
    else if ($pwQuality < $quality)
    {
      $characterTypes = '';
      for ($i = 0; $i < $quality; $i++)
      {
        $characterTypes .= $_LANG['fu_password_character_type'][$passwordTypes[$i]];
        if ($i + 2 < $quality) {
          $characterTypes .= $_LANG['fu_message_invalid_too_weak_spacer'];
        }
        else if ($i + 1 < $quality) {
          $characterTypes .= $_LANG['fu_message_invalid_too_weak_lastspacer'];
        }
      }
      $this->setMessage(Message::createFailure(sprintf($_LANG['fu_message_invalid_too_weak'], $characterTypes)));
    }
    else if ($fax && !Validation::isPhoneNumber($fax)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_fax']));
    }
    else
    {
      $now = date('Y-m-d H:i:s');
      $sqlArgs = array(
        'FUCompany'        => "'$company'",
        'FK_FUCID_Company' => "'$companyId'",
        'FUPosition'       => "'$position'",
        'FUTitle'          => "'$title'",
        'FUFirstname'      => "'$firstname'",
        'FUMiddlename'     => "'$middlename'",
        'FULastname'       => "'$lastname'",
        'FUNick'           => "'$nick'",
        'FUPW'             => "'".md5($password)."'",
        'FUBirthday'       => ($birthday ? sprintf("'%s'", $birthday) : 'NULL'),
        'FUCountry'        => "'$country'",
        'FUZIP'            => "'$zip'",
        'FUCity'           => "'$city'",
        'FUAddress'        => "'$street'",
        'FUPhone'          => "'$phone'",
        'FUMobilePhone'    => "'$mobile'",
        'FUEmail'          => "'$email'",
        'FUNewsletter'     => "'$newsletter'",
        'FK_FID'           => "'$foa'",
        'FUUID'            => "'$uid'",
        'FUFax'            => "'$fax'",
        'FUDepartment'     => "'$department'",
        'FUCreateDateTime' => "'$now'",
        'FUChangeDateTime' => "'$now'",
      );

      $sqlFields = implode(',', array_keys($sqlArgs));
      $sqlValues = implode(',', array_values($sqlArgs));

      $sql = " INSERT INTO {$this->table_prefix}frontend_user ($sqlFields) "
           . " VALUES ($sqlValues)";
      $result = $this->db->query($sql);

      if ($result) {
        // retrieve id of new frontend user and insert user rights into database
        $this->item_id = $this->db->insert_id();
        foreach ($groups as $groupID => $value)
        {
          $sql = " INSERT INTO {$this->table_prefix}frontend_user_rights "
               . " (FK_FUID, FK_FUGID) "
               . " VALUES "
               . " ({$this->item_id}, {$groupID}) ";
          $this->db->query($sql);
        }
      }
    }

    if ($result) {
      $this->_logger->log($this->_getModel());

      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['fu_message_newitem_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['fu_message_newitem_success']));
      }
    }
  }


  /**
   * Edit frontend user
   */
  protected function edit_content(){
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $title = $post->readString('fu_title', Input::FILTER_PLAIN);
    $company = $post->readString('fu_company', Input::FILTER_PLAIN);
    $companyId = $post->readInt('fu_company_id', 0);
    $position = $post->readString('fu_position', Input::FILTER_PLAIN);
    $firstname = $post->readString('fu_firstname', Input::FILTER_PLAIN);
    $middlename = $post->readString('fu_middlename', Input::FILTER_PLAIN);
    $lastname = $post->readString('fu_lastname', Input::FILTER_PLAIN);
    $street = $post->readString('fu_street', Input::FILTER_PLAIN);
    $country = $post->readInt('fu_country', 1);
    $zip = $post->readString('fu_zip', Input::FILTER_PLAIN);
    $city = $post->readString('fu_city', Input::FILTER_PLAIN);
    $email = $post->readString('fu_email', Input::FILTER_PLAIN);
    $phone = $post->readString('fu_phone', Input::FILTER_PLAIN);
    $mobile = $post->readString('fu_mobile', Input::FILTER_PLAIN);
    $nick = $post->readString('fu_nick', Input::FILTER_PLAIN);
    $password = $post->readString('fu_password', Input::FILTER_NONE);
    $password2 = $post->readString('fu_password2', Input::FILTER_NONE);
    $birthday = $post->readDate('fu_birthday');
    $uid = $post->readString('fu_uid', Input::FILTER_PLAIN);
    $groups = $post->readArray('fu_group', array());
    $newsletter = $post->exists('fu_newsletter') ? 1 : 0;
    $foa = $post->readInt('fu_foa', 1);
    $fax = $post->readString('fu_fax', Input::FILTER_PLAIN);
    $department = $post->readString('fu_department', Input::FILTER_PLAIN);

    if (!$nick || !$email) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_insufficient_input']));
    }
    else if (!$this->_validateNickIsUnique($nick, $this->item_id)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_user_exists']));
    }
    else if (!$this->_validatEmailIsUnique($email, $this->item_id)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_email_exists']));
    }
    else if ($zip && !isNumber($zip)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_zip']));
    }
    else if ($phone && !Validation::isPhoneNumber($phone)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_phone']));
    }
    else if ($mobile && !Validation::isPhoneNumber($mobile)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_mobile']));
    }
    else if ($password && $password != $password2) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_password_mismatch']));
    }
    else if ($fax && !Validation::isPhoneNumber($fax)) {
      $this->setMessage(Message::createFailure($_LANG['fu_message_invalid_fax']));
    }
    else {
      $passwordMD5 = md5($password);
      $now = date('Y-m-d H:i:s');
      $setPassword = $password ? " FUPW = '$passwordMD5', " : '';

      $sql = " UPDATE {$this->table_prefix}frontend_user "
           . " SET FUCompany = '{$this->db->escape($company)}', "
           . "     FK_FUCID_Company = $companyId, "
           . "     FUPosition = '{$this->db->escape($position)}', "
           . "     FK_FID = '0', "
           . "     FUTitle = '{$this->db->escape($title)}', "
           . "     FUFirstname = '{$this->db->escape($firstname)}', "
           . "     FUMiddlename = '{$this->db->escape($middlename)}', "
           . "     FULastname = '{$this->db->escape($lastname)}', "
           . "     FUNick = '{$this->db->escape($nick)}', "
           . " $setPassword "
           . "     FUBirthday = " . ($birthday ? sprintf("'%s'", $birthday) : 'NULL') . ", "
           . "     FUCountry = '$country', "
           . "     FUZIP = '$zip', "
           . "     FUCity = '{$this->db->escape($city)}', "
           . "     FUAddress = '{$this->db->escape($street)}', "
           . "     FUPhone = '{$this->db->escape($phone)}', "
           . "     FUMobilePhone = '{$this->db->escape($mobile)}', "
           . "     FUEmail = '{$this->db->escape($email)}', "
           . "     FUNewsletter = '{$this->db->escape($newsletter)}', "
           . "     FK_FID = '$foa', "
           . "     FUUID = '{$this->db->escape($uid)}', "
           . "     FUFax = '{$this->db->escape($fax)}', "
           . "     FUDepartment = '{$this->db->escape($department)}', "
           . "     FUChangeDateTime = '$now' "
           . " WHERE FUID = {$this->item_id} ";
      $result = $this->db->query($sql);

      if ($result)
      {
        $sql = " DELETE FROM {$this->table_prefix}frontend_user_rights "
             . " WHERE FK_FUID = {$this->item_id} ";
        $result = $this->db->query($sql);

        foreach ($groups as $groupId => $value)
        {
          $sql = " INSERT INTO {$this->table_prefix}frontend_user_rights "
               . " ( FK_FUID, FK_FUGID ) "
               . " VALUES "
               . " ( {$this->item_id}, $groupId ) ";
          $result = $this->db->query($sql);
        }
      }

      if ($result) {
        if ($this->_redirectAfterProcessingRequested('list')) {
          $this->_redirect($this->_getBackLinkUrl(),
              Message::createSuccess($_LANG['fu_message_edititem_success']));
        }
        else {
          $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
              Message::createSuccess($_LANG['fu_message_edititem_success']));
        }
      }

      $this->_logger->log($this->_getModel());
    }
  }

  /**
   * Show content
   */
  protected function get_content()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);
    $randomPassword = 0;
    $userdata = array();
    $groupdata = array();
    // stores groupes the user has permission to / empty if new user is created
    $groups = array();
    $password = '';
    $password2 = '';
    if ($this->item_id)
    {
      $sql = " SELECT FUID, FUCompany, FUPosition, FUTitle, FUFirstname, "
           . "        FUMiddlename, FULastname, "
           . "        FUNick, FUPW, FUBirthday, FUCountry, FUZIP, FUCity, FUAddress, "
           . "        FUPhone, FUMobilePhone, FUEmail, FUNewsletter, FUCreateDateTime, "
           . "        FUChangeDateTime, FK_FID, FUUID, FUFax, FUDepartment, "
           . "        FK_FUCID_Company "
           . " FROM {$this->table_prefix}frontend_user "
           . " WHERE FUID = {$this->item_id}";
      $row = $this->db->GetRow($sql);
      $birthday = ContentBase::strToTime($row['FUBirthday']) ? date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'fu'), ContentBase::strToTime($row['FUBirthday'])) : '';
      $userdata = array(
        'fu_id'        => $row['FUID'],
        'fu_company'   => $row['FUCompany'],
        'fu_company_id'=> (int)$row['FK_FUCID_Company'],
        'fu_position'  => $row['FUPosition'],
        'fu_title'     => $row['FUTitle'],
        'fu_firstname' => $row['FUFirstname'],
        'fu_middlename'=> $row['FUMiddlename'],
        'fu_lastname'  => $row['FULastname'],
        'fu_nick'      => $row['FUNick'],
        'fu_birthday'  => $birthday,
        'fu_country'   => $row['FUCountry'],
        'fu_zip'       => $row['FUZIP'],
        'fu_city'      => $row['FUCity'],
        'fu_street'    => $row['FUAddress'],
        'fu_phone'     => $row['FUPhone'],
        'fu_mobile'    => $row['FUMobilePhone'],
        'fu_email'     => $row['FUEmail'],
        'fu_newsletter'=> $row['FUNewsletter'] ? 'checked="checked"' : '',
        'fu_changed'   => $row['FUChangeDateTime'],
        'fu_foa'       => $row['FK_FID'],
        'fu_uid'       => $row['FUUID'],
        'fu_fax'       => $row['FUFax'],
        'fu_department' => $row['FUDepartment'],
      );
      $umFunction = "edit";

      // Select all group ids of groups the user is inside
      $sql = "SELECT ug.FUGID "
           . "FROM {$this->table_prefix}frontend_user_group ug "
           . "JOIN {$this->table_prefix}frontend_user_rights ur "
           . "ON ur.FK_FUGID = ug.FUGID "
           . "WHERE ur.FK_FUID = {$this->item_id}";
      $groups = $this->db->GetCol($sql);
    }
    else // new user
    {
      // get data from post source in case creating a new user failed before
      $userdata = array(
        'fu_id'        => '',
        'fu_company'   => $post->readString('fu_company', Input::FILTER_PLAIN),
        'fu_company_id'=> $post->readInt('fu_company_id', 0),
        'fu_position'  => $post->readString('fu_position', Input::FILTER_PLAIN),
        'fu_title'     => $post->readString('fu_title', Input::FILTER_PLAIN),
        'fu_firstname' => $post->readString('fu_firstname', Input::FILTER_PLAIN),
        'fu_middlename'=> $post->readString('fu_middlename', Input::FILTER_PLAIN),
        'fu_lastname'  => $post->readString('fu_lastname', Input::FILTER_PLAIN),
        'fu_nick'      => $post->readString('fu_nick', Input::FILTER_PLAIN),
        'fu_birthday'  => $post->readString('fu_birthday', Input::FILTER_PLAIN),
        'fu_country'   => $post->readInt('fu_country', 1),
        // read string here -> otherwise zero is displayed in form if not set
        'fu_zip'       => $post->readString('fu_zip', Input::FILTER_NONE),
        'fu_city'      => $post->readString('fu_city', Input::FILTER_PLAIN),
        'fu_street'    => $post->readString('fu_street', Input::FILTER_PLAIN),
        'fu_phone'     => $post->readString('fu_phone', Input::FILTER_PLAIN),
        'fu_mobile'    => $post->readString('fu_mobile', Input::FILTER_PLAIN),
        'fu_email'     => $post->readString('fu_email', Input::FILTER_PLAIN),
        'fu_uid'       => $post->readString('fu_uid', Input::FILTER_PLAIN),
        'fu_newsletter'=> $post->exists('fu_newsletter') ? 1 : 0,
        'fu_foa'       => $post->readInt('fu_foa', 1),
        'fu_fax'       => $post->readString('fu_fax', Input::FILTER_PLAIN),
        'fu_department' => $post->readString('fu_department', Input::FILTER_PLAIN),
      );
      $pwHelper = new Password();
      $pwHelper->setLength(ConfigHelper::get('m_login_password_length'))
               ->setQuality(ConfigHelper::get('m_login_password_quality'))
               ->setTypes(ConfigHelper::get('m_login_password_types'))
               ->create();
      $randomPassword = $pwHelper->getPassword();
      $password = $randomPassword;
      $password2 = $randomPassword;
      // fill groups array with input source if available
      $groups = $post->exists('fu_group');
      $groups = $groups ? array_keys($post->readArray('fu_group')) : array();

      $umFunction = "new";
    }

    // Select all available groups a user possibly is inside
    $sql = "SELECT FUGID, FUGName, FUGDescription, FK_SID "
         . "FROM {$this->table_prefix}frontend_user_group "
         . "JOIN {$this->table_prefix}frontend_user_group_sites "
         . "ON FUGID = FK_FUGID "
         . "ORDER BY FUGName ASC ";
    $result = $this->db->query($sql);
    $groupdata = array();
    while ($row = $this->db->fetch_row($result))
    {
      // if group has already been added to the array - just add site information
      if (isset($groupdata[$row['FUGID']])) {
        $groupdata[$row['FUGID']]['group_sites'] .= ", {$this->_allSites[$row['FK_SID']]}";
        continue;
      }

      $checked = '';
      if (in_array($row['FUGID'], $groups)) {
        $checked = 'checked="checked"';
      }

      $groupdata[$row['FUGID']] = array(
        'group_name'       => $row['FUGName'],
        'group_id'         => $row['FUGID'],
        'group_checked'    => $checked,
        'group_description'=> $row['FUGDescription'],
        'group_sites'      => $this->_allSites[$row['FK_SID']],
      );
    }

    $countries = $this->_configHelper->getCountries('countries', false, $this->site_id, 0);
    // create country selection
    $countryOptions = "";
    foreach ($countries as $cid => $value) {
      // if country is selected one
      if ($cid == $userdata['fu_country']) {
        $countryOptions .= "<option selected value=\"$cid\">$value</option>";
        continue;
      }
      $countryOptions .= "<option value=\"$cid\">$value</option>";
    }

    $foaOptions = "";
    foreach ($_LANG["fu_foas"] as $foaid => $foavalue) {
      if ($userdata['fu_foa'] == $foaid) $foaOptions .= "<option value=\"".$foaid."\" selected>".$foavalue."</option>";
      else $foaOptions .= "<option value=\"".$foaid."\">".$foavalue."</option>";
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_frontendusermgmt" /><input type="hidden" name="action2" value="main;'.$umFunction.'" /><input type="hidden" name="page" value="'.$this->item_id.'" />';

    $this->tpl->load_tpl('frontend_user', 'modules/ModuleFrontendUserManagement.tpl');
    $this->tpl->parse_if('frontend_user', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('fu'));
    $this->tpl->parse_loop('frontend_user', $groupdata, 'group_items');
    $this->tpl->parse_if('frontend_user', 'random_pw', $randomPassword, array(
      'fu_auto_password' => $randomPassword
    ));
    $content = $this->tpl->parsereturn('frontend_user', array_merge(
      array (
        'fu_password'            => $password,
        'fu_password2'           => $password2,
        'fu_country_options'     => $countryOptions,
        'fu_foa_options'         => $foaOptions,
        'fu_company_options'     => $this->_getCompanySelectOptions($userdata['fu_company_id']),
        'fu_function_label'      => $_LANG["fu_function_".$umFunction."_label"],
        'fu_action'              => "index.php",
        'fu_hidden_fields'       => $hiddenFields,
        'fu_function_label2'     => $_LANG["fu_function_".$umFunction."_label2"],
        'fu_module_action_boxes' => $this->_getContentActionBoxes(),
      ),
      $userdata,
      $_LANG2['fum']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Delete frontend user
   */
  private function delete_content()
  {
    global $_LANG, $_LANG2;

    if ($this->_getModel()->id) {
      $this->_getModel()->deleted = 1;
      $this->_getModel()->changeDatetime = date('Y-m-d H:i:s');
      $this->_getModel()->update();
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG[$this->_prefix . '_message_deleteitem_success']));
    }
  }

  /**
   * @return array
   */
  private function _getContentList()
  {
    global $_LANG, $_LANG2;

    $data = $this->_grid()->get_result();
    if (is_array($data)) {
      $i = 1;
      foreach ($data as $key => &$value) {
        $row = $this->_grid()->get_grid_data($key);
        $id = $row['FUID'];
        $value['fu_user_id'] = $id;
        $value['fu_user_nick'] = $row['FUNick'];
        $value['fu_user_email'] = $row['FUEmail'];
        $value['fu_delete_link'] = $this->_parseUrl('delete', array('page' => $id));
        $value['fu_content_link'] = $this->_parseUrl('edit', array('page' => $id));
        if ($row['RootPage']) {
          $value['fu_ut_link'] = 'index.php?action=content&amp;site=1' . '&amp;page=' . $row['RootPage'] . '&amp;fuid=' . $id;
        }
        else {
          $value['fu_ut_link'] = $this->_parseUrl('ut_page', array('page' => $id));
        }
        $value['fu_ut_link_cls'] = $row['ContentPage'] ? 'off' : 'none';
        $data[$key]['fu_row_bg']      = ( $i++ %2 ) ? 'even' : 'odd';
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

    $tplName = 'module_frontendusermanagement_list';
    $this->tpl->load_tpl($tplName, 'modules/ModuleFrontendUserManagement_list.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(),
        $this->_getMessageTemplateArray('fu'));
    $this->tpl->parse_if($tplName, 'filter_reset', $showResetButton);
    $this->tpl->parse_if($tplName, 'fu_user_pages', $this->_user->AvailableModule('frontendusertree', $this->site_id));
    $this->tpl->parse_if($tplName, 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop($tplName, $data, 'rows');

    $content = $this->tpl->parsereturn($tplName, array_merge( $this->_grid()->load_col_filters(), $this->_grid()->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl()), array (
        'fu_action'                => $this->_parseUrl(),
        'fu_count_all'             => $this->_grid()->get_quantity_total_rows(),
        'fu_count_current'         => $currentRows,
        'fu_showpage_bottom'       => $this->_grid()->load_page_navigation($this->_parseUrl() . '&amp;fu_page=','_bottom'),
        'fu_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
        'fu_showpage_top'          => $this->_grid()->load_page_navigation($this->_parseUrl() . '&amp;fu_page=','_top'),
        'fu_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['fum']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * get the content as CSV
   */
  private function _getCsv()
  {
    global $_LANG;

    //header('Content-Type: text/x-csv');
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: inline; filename=\"kundendaten_export".date("Y-m-d").".xls\"");
    //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');

    // get groups with all user ids of users inside
    $sql = " SELECT FUGID, FUGName "
          ." FROM {$this->table_prefix}frontend_user_rights "
          ." JOIN {$this->table_prefix}frontend_user_group "
          ." ON FK_FUGID = FUGID "
          ." GROUP BY FUGID, FUGName ";
    $groups = $this->db->GetAssoc($sql);

    $company = new FrontendUserCompany($this->db, $this->table_prefix);
    $companies = $company->readAllItems();

    // output title row - decode all html entities in order to generate proper output
    echo parseCSVOutput($_LANG["fu_company_label"])."\t".
         parseCSVOutput($_LANG["fu_company_select_label"])."\t".
         parseCSVOutput($_LANG["fu_foa_label"])."\t".
         parseCSVOutput($_LANG["fu_position_label"])."\t".
         parseCSVOutput($_LANG["fu_title_label"])."\t".
         parseCSVOutput($_LANG["fu_firstname_label"])."\t".
         parseCSVOutput($_LANG["fu_middlename_label"])."\t".
         parseCSVOutput($_LANG["fu_lastname_label"])."\t".
         parseCSVOutput($_LANG["fu_nick_label"])."\t".
         parseCSVOutput($_LANG["fu_birthday_label"])."\t".
         parseCSVOutput($_LANG["fu_country_label"])."\t".
         parseCSVOutput($_LANG["fu_zip_label"])."\t".
         parseCSVOutput($_LANG["fu_city_label"])."\t".
         parseCSVOutput($_LANG["fu_street_label"])."\t".
         parseCSVOutput($_LANG["fu_phone_label"])."\t".
         parseCSVOutput($_LANG["fu_mobile_label"])."\t".
         parseCSVOutput($_LANG["fu_fax_label"])."\t".
         parseCSVOutput($_LANG["fu_department_label"])."\t".
         parseCSVOutput($_LANG["fu_email_label"])."\t".
         parseCSVOutput($_LANG["fu_newsletter_label"])."\t".
         parseCSVOutput($_LANG["fu_lastchange_label"])."\t".
         parseCSVOutput($_LANG["fu_createdate_label"])."\t".
         parseCSVOutput($_LANG["fu_lastlogin_label"])."\t".
         parseCSVOutput($_LANG["fu_countlogins_label"])."\t";
    // output group names
    foreach ($groups as $key => $value) {
      echo parseCSVOutput($value)."\t";
    }
    echo "\n";

    $countries = $this->_configHelper->getCountries('countries', false, $this->site_id);
    $foas = $_LANG["fu_foas"];

    $this->_grid()->load_data(true);
    $rows = $this->_grid()->get_grid_data();

    foreach ($rows as $row) {
      $country = (isset($countries[$row['FUCountry']])) ? $countries[$row['FUCountry']] : '';
      $birthday = ContentBase::strToTime($row['FUBirthday']) ? date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'fu'), ContentBase::strToTime($row['FUBirthday'])) : '';
      $signupDate =  (ContentBase::strToTime($row['FUCreateDateTime'])) ? $row['FUCreateDateTime'] : '';
      $lastChangeDate = (ContentBase::strToTime($row['FUChangeDateTime'])) ? $row['FUChangeDateTime'] : '';
      $loginDate = ContentBase::strToTime($row['FULastLogin']) ? $row['FULastLogin'] : '';
      $company = $companies->exists($row['FK_FUCID_Company']) ?
                 $companies->get($row['FK_FUCID_Company'])->name : '';
      echo parseCSVOutput($row['FUCompany'])."\t".
           parseCSVOutput($company)."\t".
           parseCSVOutput(isset($foas[$row['FK_FID']]) ? $foas[$row['FK_FID']] : '')."\t".
           parseCSVOutput($row['FUPosition'])."\t".
           parseCSVOutput($row['FUTitle'])."\t".
           parseCSVOutput($row['FUFirstname'])."\t".
           parseCSVOutput($row['FUMiddlename'])."\t".
           parseCSVOutput($row['FULastname'])."\t".
           parseCSVOutput($row['FUNick'])."\t".
           parseCSVOutput($birthday)."\t".
           parseCSVOutput($country)."\t".
           parseCSVOutput($row['FUZIP'])."\t".
           parseCSVOutput($row['FUCity'])."\t".
           parseCSVOutput($row['FUAddress'])."\t".
           parseCSVOutput($row['FUPhone'])."\t".
           parseCSVOutput($row['FUMobilePhone'])."\t".
           parseCSVOutput($row['FUFax'])."\t".
           parseCSVOutput($row['FUDepartment'])."\t".
           parseCSVOutput($row['FUEmail'])."\t".
           parseCSVOutput($row['FUNewsletter'])."\t".
           parseCSVOutput($lastChangeDate)."\t".
           parseCSVOutput($signupDate)."\t".
           parseCSVOutput($loginDate)."\t".
           parseCSVOutput($row['FUCountLogins'])."\t";


      $sql = " SELECT FK_FUGID "
        ." FROM {$this->table_prefix}frontend_user_rights "
        ." WHERE FK_FUID = '{$row['FUID']}' ";
      $rights = $this->db->GetCol($sql);

      foreach ($groups as $key => $value)
      {
        // if user is inside group
        if (in_array($key, $rights)) {
          echo parseCSVOutput($_LANG["fu_yes_label"])."\t";
        }
        else {
          echo "\t";
        }
      }
      echo "\n";
    }
    exit;
  }

  /**
   * Create a new root page within the user navigation tree for the selected
   * user and redirect to user tree. There exists one root page within the user
   * tree for each user.
   */
  private function _userTreePage()
  {
    global $_LANG;

    // Invalid request.
    if ($this->action[0] != 'ut_page' || !$this->item_id) {
      return;
    }

    $sql = ' SELECT DISTINCT(CIID) '
         . " FROM {$this->table_prefix}contentitem "
         . ' WHERE FK_FUID = ' . $this->item_id
         . " AND CTree = 'user' "
         . ' AND FK_CIID IS NULL ';
    $ciid = $this->db->GetOne($sql);

    // There does not exist a root page for current frontend-user, so create a
    // new one.
    if (!$ciid)
    {
      // The user tree root page title.
      $title = $_LANG['global_tree_user_db_page_titel'];

      $sql = " INSERT INTO {$this->table_prefix}contentitem "
           . ' (CTitle, CPosition, CType, FK_CTID, FK_SID, FK_CIID, CTree, FK_FUID) '
           . ' VALUES '
           . " ('$title', 0, 0, NULL, 1, NULL, 'user', $this->item_id) ";
      $this->db->query($sql);
      $ciid = $this->db->insert_id();

      if ($ciid) {
        $sql = " INSERT INTO {$this->table_prefix}contentabstract "
             . " (FK_CIID) VALUES ($ciid)";
        $this->db->query($sql);
      }
    }

    header('Location: ' . 'index.php?action=content&site=1&page=' . $ciid . '&fuid=' . $this->item_id);
    exit();

  }

  private function _getCompanySelectOptions($usersCompany = 0)
  {
    $usersCompany = (int)$usersCompany;
    $company = new FrontendUserCompany($this->db, $this->table_prefix);
    $condition = " FUCDeleted = 0 ";
    if ($usersCompany) {
      $condition .= " OR FUCID = $usersCompany ";
    }
    $companies = $company->read(array('where' => $condition,
                                      'order' => 'FUCName ASC'));
    $options = '';
    foreach ($companies as $c) {
      $selected = '';
      if ($c->id == $usersCompany) {
        $selected = ' selected="selected" ';
      }
      $options .= '<option value="' . $c->id . '" ' . $selected . '>'
                . parseOutput($c->name) . '</option>';
    }
    return $options;
  }

  /**
   * @return FrontendUser
   */
  private function _getModel()
  {
    if ($this->_model === null) {
      $post = new Input(Input::SOURCE_POST);
      $model = new FrontendUser($this->db, $this->table_prefix, $this->_prefix);
      if ((int)$this->item_id) {
        $this->_model = $model->readItemById((int)$this->item_id);
      }
      else {
        $this->_model = $model;
      }

      if ($post->exists('process')) {
        // TODO: the frontend user model is not used for save/edit operations
      }
    }
    return $this->_model;
  }

  private function _validateNickIsUnique($nick, $ignoreId = 0)
  {
    $sql = " SELECT FUID "
         . " FROM {$this->table_prefix}frontend_user "
         . " WHERE FUNick = '{$this->db->escape($nick)}' "
         . " AND FUDeleted = 0 "
         . " AND FUID != '{$this->db->escape($ignoreId)}' "
         . " LIMIT 1";
    return $this->db->GetOne($sql) ? false : true;
  }

  private function _validatEmailIsUnique($email, $ignoreId = 0)
  {
    $sql = " SELECT FUID "
         . " FROM {$this->table_prefix}frontend_user "
         . " WHERE FUEmail = '{$this->db->escape($email)}' "
         . " AND FUDeleted = 0 "
         . " AND FUID != '{$this->db->escape($ignoreId)}' "
         . " LIMIT 1";
    return $this->db->GetOne($sql) ? false : true;
  }
}
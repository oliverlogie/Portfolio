<?php

/**
 * Module for management of frontend user companies
 *
 * TODO: provide an image upload for company image,
 *       as soon as this feature is required.
 *
 * $LastChangedDate: 2018-07-13 09:25:43 +0200 (Fr, 13 Jul 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class ModuleFrontendUserManagementCompany extends Module
{
  /**
   * @var string
   */
  protected $_prefix = 'fu_cp';

  /**
   * @see ModuleFrontendUserManagementCompany::_getAvailableAreas()
   * @var ModelList | null
   */
  private $_availableAreas;

  public function show_innercontent()
  {
    global $_LANG;

    $this->_create();
    $this->_update();
    $this->_delete();

    if (!$this->action[0]) {
      return $this->_getContentList();
    }
    else {
      return $this->_getContentForm();
    }
  }

  protected function _initGrid()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    // 1. grid sql
    $gridSql = " SELECT FUCID, FUCName, FUCStreet, FUCPostalCode, FUCCity, "
             . "        FK_CID_Country, FUCPhone, FUCFax, FUCEmail, FUCWeb, "
             . "        FUCNotes, FUCType, FUCImage, FUCVatNumber, "
             . "        FUCCreateDatetime, FUCChangeDatetime, FK_FUCAID_Area, "
             . "        FUCDeleted  "
             . "FROM {$this->table_prefix}frontend_user_company "
             . "WHERE FUCDeleted = 0 ";

    // 2. fields = columns
    $filterSelective = array('FK_CID_Country', 'FUCPhone', 'FUCFax', 'FUCWeb',
                             'FUCNotes', 'FUCType', 'FUCImage', 'FUCVatNumber',
                             'FUCCreateDatetime', 'FUCChangeDatetime',
                             'FK_FUCAID_Area');
    $queryFields[1] = array('type' => 'text', 'value' => 'FUCName', 'lazy' => true);
    $queryFields[2] = array('type' => 'text', 'value' => 'FUCPostalCode');
    $queryFields[3] = array('type' => 'text', 'value' => 'FUCCity', 'lazy' => true);
    $queryFields[4] = array('type' => 'text', 'value' => 'FUCEmail', 'lazy' => true);
    $queryFields[5] = array('type' => 'selective', 'value' => 'FK_CID_Country',
                            'valuelist' => $filterSelective);

    // 3. filter fields = query fields as we do not need additional fields to be
    // filterable
    $filterFields = $queryFields;

    // 4. filter types
    $filterTypes = array(
      'FUCID'             => 'text',
      'FUCName'           => 'text',
      'FUCStreet'         => 'text',
      'FUCPostalCode'     => 'text',
      'FUCCity'           => 'text',
      'FK_CID_Country'    => 'selective',
      'FUCPhone'          => 'text',
      'FUCFax'            => 'text',
      'FUCEmail'          => 'text',
      'FUCWeb'            => 'text',
      'FUCNotes'          => 'text',
      'FUCType'           => 'text',
      'FUCImage'          => 'text',
      'FUCVatNumber'      => 'text',
      'FK_FUCAID_Area'    => 'selective',
      'FUCDeleted'        => 'boolean',
      'FUCCreateDatetime' => 'date',
      'FUCChangeDatetime' => 'date',
    );

    // 5. order options
    $ordersValuelist = array(
      1 => array('field' => 'FUCName',         'order' => 'ASC'),
      2 => array('field' => 'FUCName',         'order' => 'DESC'),
      3 => array('field' => 'FUCCity',         'order' => 'ASC'),
      4 => array('field' => 'FUCCity',         'order' => 'ASC'),
      5 => array('field' => 'CCreateDateTime', 'order' => 'ASC'),
      6 => array('field' => 'CCreateDateTime', 'order' => 'DESC'),
      7 => array('field' => 'CChangeDateTime', 'order' => 'ASC'),
      8 => array('field' => 'CChangeDateTime', 'order' => 'DESC'),
    );
    $orders[1]['valuelist'] = $ordersValuelist;
    $orders[2]['valuelist'] = $ordersValuelist;
    $orders[3]['valuelist'] = $ordersValuelist;

    // 5. page
    $page = ($get->exists('fu_cp_page')) ? $get->readInt('fu_cp_page') : 1;

    // 6. prepare selective data for dropdown values
    $countries = array();
    foreach ($this->_configHelper->getCountries('countries', false) as $id => $value) {
      $countries[$id]['label'] = $value;
    }

    $areas = array();
    foreach ($this->_getAvailableAreas() as $area) {
      $areas[$area->id]['label'] = $area->name;
    }

    $selectiveData = array('FK_CID_Country' => $countries,
                           'FK_FUCAID_Area' => $areas);

    // 7. prefix
    $prefix = array('config'  => $this->_prefix,
                    'lang'    => $this->_prefix,
                    'session' => $this->_prefix,
                    'tpl'     => $this->_prefix);

    // ---------------------------------------------------------------------- //
    $grid = new DataGrid($this->db, $this->session, $prefix);
    $grid->setSelectiveData($selectiveData);
    $grid->load($gridSql, $queryFields, $filterFields, $filterTypes,
                $orders, $page, false, null, null, null, null,
                ConfigHelper::get($this->_prefix . '_results_per_page'));

    return $grid;
  }

  /**
   * Creates a new company if requested
   * @return void
   */
  private function _create()
  {
    global $_LANG, $_LANG2;

    if (!isset($_POST['process']) || $this->action[0] != 'new') {
      return false;
    }

    try {
      if (!$this->_getModel()->validate()) {
        $this->setMessage($this->_getModel()->getValidationMsg());
        return false;
      }
      $now = date('Y-m-d H:i:s');
      $this->_getModel()->createDatetime = $now;
      $this->_getModel()->changeDatetime = $now;
      $this->_getModel()->create();

      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['fu_cp_message_create_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->_getModel()->id)),
            Message::createSuccess($_LANG['fu_cp_message_create_success']));
      }
    }
    catch(Exception $e) {
      $this->setMessage(Message::createFailure($_LANG['fu_cp_message_save_failure']));
    }
  }

  /**
   * Updates a company if requested
   * @return void
   */
  private function _update()
  {
    global $_LANG, $_LANG2;

    if (!isset($_POST['process']) || $this->action[0] != 'edit' || !$this->item_id) {
      return false;
    }

    try {
      if (!$this->_getModel()->validate()) {
        $this->setMessage($this->_getModel()->getValidationMsg());
        return false;
      }
      $oldModel = $this->_getModel()->readItemById($this->item_id);
      $this->_getModel()->changeDatetime = date('Y-m-d H:i:s');
      $this->_getModel()->update();

      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['fu_cp_message_update_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['fu_cp_message_update_success']));
      }
    }
    catch(Exception $e) {
      $this->setMessage(Message::createFailure($_LANG['fu_cp_message_save_failure']));
    }
  }

  /**
   * Deltes a company if requested
   * @return void
   */
  private function _delete()
  {
    global $_LANG, $_LANG2;

    if ($this->action[0] != 'delete' || !$this->item_id) {
      return false;
    }

    if ($this->_getModel()->id) {
      $this->_getModel()->deleted = 1;
      $this->_getModel()->update();
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['fu_cp_message_deleteitem_success']));
    }
  }

  /**
   * @return array
   */
  private function _getContentForm()
  {
    global $_LANG, $_LANG2;

    $model = $this->_getModel();

    $areas = array(0 => $_LANG['fu_cp_area_none_label']);
    foreach ($this->_getAvailableAreas() as $area) {
      $areas[$area->id] = $area->name;
    }
    $area = $this->_getModel()->getFieldInstance('area')->setPredefined($areas);

    if ($this->item_id) {
      $function = 'edit';
    }
    else {
      $function = 'new';
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_frontendusermgmt" />'
                  . '<input type="hidden" name="action2" value="company;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';
    $fields = $this->_generateFormFieldLoopArray(
            array('id', 'createDatetime', 'changeDatetime', 'deleted', 'image'));
    $this->tpl->load_tpl('c_fu_cp', 'modules/ModuleFrontendUserManagementCompany.tpl');
    $this->tpl->parse_if('c_fu_cp', 'message', $this->_getMessage(), $this->_getMessageTemplateArray($this->_prefix));
    $this->tpl->parse_loop('c_fu_cp', $fields, 'fields');
    $this->_parseModuleFormFieldMsg('c_fu_cp');
    $this->_parseModuleFormFieldInfo('c_fu_cp');

    $content = $this->tpl->parsereturn('c_fu_cp', array_merge(array (
      'fu_cp_hidden_fields'    => $hiddenFields,
      'fu_cp_function_label'   => $_LANG['fu_cp_function_'.$function.'_label'],
      'fu_cp_function_label2'  => $_LANG['fu_cp_function_'.$function.'_label2'],
      'fu_cp_action'           => 'index.php',
      'module_action_boxes'    => $this->_getContentActionBoxes(),
    ), $_LANG2['fu_cp']));

    return array(
        'content'      => $content,
        'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * @return array
   */
  private function _getContentList()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);
    if ($post->exists('process_reset')) {
      $this->_grid()->resetFilters();
      $this->_grid()->resetOrders();
      $this->_grid()->resetOrderControls();
    }

    return array(
        'content'      => $this->_getContentListGrid($this->_grid()),
        'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * @param DataGrid $grid
   * @return string
   */
  private function _getContentListGrid(DataGrid $grid)
  {
    global $_LANG, $_LANG2;

    $data = $grid->get_result();
    if (is_array($data))
    {
      $i = 1;
      $urgentAppointments = array();
      foreach ($data as $key => $value)
      {
        $id = $grid->get_grid_data($key, 'FUCID');
        $data[$key]['fu_cp_edit_link'] = $this->_parseUrl('edit', array('page' => $id));
        $data[$key]['fu_cp_delete_link'] = $this->_parseUrl('delete', array('page' => $id));
        $data[$key]['fu_cp_row_bg'] = ($i++%2 ? 'even' : 'odd');
      }
    }
    else {
      $this->setMessage($data);
    }

    $currentSel = $grid->get_page_selection();
    $currentRows = $grid->get_quantity_selected_rows();
    $showResetButton = $this->_grid()->isFilterSet() ||
      $this->_grid()->isOrderSet() ||
      $this->_grid()->isOrderControlsSet();

    $this->tpl->load_tpl('content_fu_cp', 'modules/ModuleFrontendUserManagementCompany_list.tpl');
    $this->tpl->parse_if('content_fu_cp', 'message', $this->_getMessage(),
            $this->_getMessageTemplateArray('fu_cp'));
    $this->tpl->parse_if('content_fu_cp', 'filter_reset', $showResetButton);
    $this->tpl->parse_if('content_fu_cp', 'order_controls_set', $this->_grid()->isOrderControlsSet());
    $this->tpl->parse_loop('content_fu_cp', $data, 'rows');
    $content = $this->tpl->parsereturn('content_fu_cp', array_merge( $grid->load_col_filters(), $grid->load_order_fields(), $this->_grid()->load_order_controls($this->_parseUrl()), array (
      'fu_cp_action'                => $this->_parseUrl(),
      'fu_cp_count_all'             => $grid->get_quantity_total_rows(),
      'fu_cp_count_current'         => $currentRows,
      'fu_cp_showpage_bottom'       => $grid->load_page_navigation($this->_parseUrl() . '&amp;fu_cp_page=','_bottom'),
      'fu_cp_showpage_bottom_label' => sprintf($_LANG['m_grid_showpage_bottom_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
      'fu_cp_showpage_top'          => $grid->load_page_navigation($this->_parseUrl() . '&amp;fu_cp_page=','_top'),
      'fu_cp_showpage_top_label'    => sprintf($_LANG['m_grid_showpage_top_label'],($currentRows ? $currentSel['begin'] : 0),($currentRows ? $currentSel['end'] : 0)),
    ), $_LANG2['fu_cp']));

    return $content;
  }

  /**
   * @return FrontendUserCompany
   */
  private function _getModel()
  {
    if ($this->_model === null) {
      $post = new Input(Input::SOURCE_POST);
      $model = new FrontendUserCompany($this->db, $this->table_prefix, $this->_prefix);
      if ((int)$this->item_id) {
        $this->_model = $model->readItemById((int)$this->item_id);
      }
      else {
        $this->_model = $model;
      }

      if ($post->exists('process')) {
        $fields = $post->readMultipleArrayStringToString($this->_prefix.'_field');
        $this->_model->name       = $fields[$this->_prefix . '_name'];
        $this->_model->street     = $fields[$this->_prefix . '_street'];
        $this->_model->postalCode = $fields[$this->_prefix . '_postal_code'];
        $this->_model->city       = $fields[$this->_prefix . '_city'];
        $this->_model->country    = $fields[$this->_prefix . '_country'];
        $this->_model->phone      = $fields[$this->_prefix . '_phone'];
        $this->_model->fax        = $fields[$this->_prefix . '_fax'];
        $this->_model->email      = $fields[$this->_prefix . '_email'];
        $this->_model->web        = $fields[$this->_prefix . '_web'];
        $this->_model->notes      = $fields[$this->_prefix . '_notes'];
        $this->_model->type       = $fields[$this->_prefix . '_type'];
        $this->_model->vatNumber  = $fields[$this->_prefix . '_vat_number'];
        $this->_model->area       = $fields[$this->_prefix . '_area'];
      }
    }
    return $this->_model;
  }

  /**
   * Returns the available company areas
   * @return ModelList
   */
  private function _getAvailableAreas()
  {
    if ($this->_availableAreas === null) {
      $area = new FrontendUserCompanyArea($this->db, $this->table_prefix);
      $this->_availableAreas = $area->readAllAvailableItems();
    }
    return $this->_availableAreas;
  }
}


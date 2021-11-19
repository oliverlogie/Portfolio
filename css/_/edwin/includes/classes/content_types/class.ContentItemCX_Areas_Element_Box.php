<?php

/**
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Box extends ContentItemCX_Areas_Element
{
  private $_displayOpened = false;

  /**
   * {@inheritdoc}
   */
  public function deleteElementContent()
  {
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      $el->deleteElementContent();
    }

    $this->_deleteElementContentRow();
  }

  /**
   * {@inheritdoc}
   */
  public function getElementContent()
  {
    $this->_checkDatabase();

    return ed_template($this->_getElementTemplatePath(), $this->getElementTemplateData());
  }

  /**
   * {@inheritdoc}
   */
  public function getElementTemplateData()
  {
    $data = parent::getElementTemplateData();

    $data['children'] = $this->_getChildElementsTemplateVars();

    $overrides = array(
      'cx_scroll_to_anchor' => 'a_cx_area_element_boxes_' . $this->_getElementOption('data.elementable_id'),
    );

    $data['url_update'] = $this->_getElementProcessUrl('update', $overrides);
    $data['url_activate'] = $this->_getElementProcessUrl('activate', array_merge($overrides, array('active' => 1)));
    $data['url_deactivate'] = $this->_getElementProcessUrl('activate', array_merge($overrides, array('active' => 0)));
    $data['url_delete'] = $this->_getElementProcessUrl('delete', $overrides);

    $data['display_opened'] = $this->_displayOpened;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function updateElementContent()
  {
    // process children
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      $el->updateElementContent();
    }

    // save value of first title child element as value of box

    $title = '';
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      if ($el->getElementType() === 'title') {
        $title = $el->_getElementOption('data.content');
        break;
      }
    }

    $this->_options['data']['content'] = $title;

    $this->_updateElementContent($this->_options['data']['content']);

    $this->_displayOpened = true;
  }

  /**
   * {@inheritdoc}
   */
  protected function _parseOptions($options)
  {
    $options = parent::_parseOptions($options);

    $options['lang'] = array_merge(array(
      'title'                       => 'cx_areas_area_box_label',
      'btn_save'                    => 'cx_areas_area_box_btn_save_label',
      'list_move_label'             => 'cx_areas_area_box_list_move_label',
      'list_activation_green_label' => 'cx_areas_area_box_list_activation_green_label',
      'list_activation_red_label'   => 'cx_areas_area_box_list_activation_red_label',
      'list_delete_question'        => 'cx_areas_area_box_list_delete_question',
      'list_delete_label'           => 'cx_areas_area_box_list_delete_label',
      'list_showhide_label'         => 'cx_areas_area_box_list_showhide_label',
    ), $options['lang']);

    return $options;
  }

  /**
   * Action: update box content
   */
  protected function _processElementActionUpdate()
  {
    global $_LANG;

    $this->updateElementContent();

    $error = false;
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      if ($el->hasElementError()) {
        $error = true;
      }
    }

    if ($error) {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_process_cx_area_element_box_update_failure']));
    }
    else {
      $this->setMessage(Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_box_update_success']));
    }
  }

  /**
   * Action: delete box
   */
  protected function _processElementActionDelete()
  {
    global $_LANG;

    $helper = $this->_positionHelper();
    $helper->move($this->getElementId(), $helper->getHighestPosition());

    $this->deleteElementContent();

    $this->_parent->setMessage($this->getMessage() ?:
      Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_box_delete_success']));
  }

  /**
   * Action: de(activate) box
   */
  protected function _processElementActionActivate()
  {
    global $_LANG;

    $this->_options['data']['disabled'] = $this->_input()->readInt('active') ? 0 : 1;

    $sql = " UPDATE {$this->table_prefix}contentitem_cx_area_element "
         . " SET CXAEDisabled = :CXAEDisabled "
         . " WHERE CXAEID = :CXAEID ";
    $this->db->q($sql, array(
      'CXAEDisabled' => $this->_getElementOption('data.disabled'),
      'CXAEID' => $this->_getElementOption('data.id'),
    ));

    if ($this->_getElementOption('data.disabled')) {
      $this->setMessage(Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_box_deactivate_success']));
    }
    else {
      $this->setMessage(Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_box_activate_success']));
    }

    $this->_parent->setMessage($this->getMessage());
  }

  /**
   * @return \Core\Helpers\PositionHelperExtended
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  private function _positionHelper()
  {
    return new Core\Helpers\PositionHelperExtended(
      $this->db,
      sprintf('%scontentitem_cx_area_element', $this->db->prefix()),
      'CXAEID',
      'CXAEPosition',
      array(
        'CXAEElementableType' => $this->_getElementOption('data.elementable_type'),
        'CXAEElementableID' => $this->_getElementOption('data.elementable_id'),
      )
    );
  }
}
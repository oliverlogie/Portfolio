<?php

/**
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Boxes extends ContentItemCX_Areas_Element
{
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
      'cx_scroll_to_anchor' => 'a_cx_area_element_boxes_' . $this->getElementId(),
    );

    $data['url_box_add'] = $this->_getElementProcessUrl('addBox', $overrides);
    $data['url_box_move'] = $this->_getElementProcessUrl('moveBox', $overrides)
      . '&moveElementID=#moveID#&moveElementTo=#moveTo#';

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function updateElementContent()
  {
    // do not call update on child boxes, child boxes should be updated using
    // their own action
  }

  /**
   * {@inheritDoc}
   */
  protected function _checkDatabase()
  {
    // does not automatically insert children elements
    // children *box* elements have to be created by user action
  }

  /**
   * {@inheritdoc}
   */
  protected function _parseOptions($options)
  {
    $options = parent::_parseOptions($options);

    $options['settings'] = array_merge(array(
      'maximum' => 6
    ), $options['settings']);

    $options['settings']['maximum'] = (int)$options['settings']['maximum'];

    $options['lang'] = array_merge(array(
      'title'                         => 'cx_areas_area_boxes_label',
      'message_warning_no_boxes'      => 'cx_areas_area_boxes_message_warning_no_boxes',
      'message_warning_maximum_boxes' => 'cx_areas_area_boxes_message_warning_maximum_boxes',
      'btn_add_box_label'             => 'cx_areas_area_boxes_btn_add_box_label',
    ), $options['lang']);

    return $options;
  }

  /**
   * Action: adds a new box to list of boxes
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _processElementActionAddBox()
  {
    global $_LANG;

    if (!$this->_getElementOption('elements')) {
      return;
    }

    if (count($this->_getChildElements()) >= (int)$this->_getElementOption('settings.maximum')) {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_process_cx_area_element_boxes_add_box_failure']));
      return;
    }

    $position = $this->_positionHelper()->getHighestPosition() + 1;

    $identifier = array_keys($this->_getElementOption('elements'))[0];

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cx_area_element "
         . " (CXAEIdentifier, CXAEType, CXAEElementableType, CXAEElementableID, CXAEPosition, FK_CXAID, FK_CIID) VALUES ( "
         . " '{$this->db->escape($this->_getElementOption('data.identifier') . '.' . $identifier)}', "
         . " '{$this->db->escape($this->_getElementOption(sprintf('elements.%s.type', $identifier)))}', "
         . " 'contentitem_cx_area_element', "
         . " '{$this->db->escape($this->_getElementOption('data.id'))}', "
         . " '$position', "
         . " '{$this->db->escape($this->_getElementOption('data.area_id'))}', "
         . " '{$this->db->escape($this->page_id)}' "
         . " ) ";
    $this->db->q($sql);

    // Force refresh of properties
    $this->_childElementRows = null;
    $this->_childElements = null;

    $this->setMessage(Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_boxes_add_box_success']));

    $this->_updateElementContent($this->_options['data']['content']);
  }

  /**
   * Action: move a box from one position to another
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _processElementActionMoveBox()
  {
    global $_LANG;

    if (!$this->_getElementOption('elements')) {
      return;
    }

    $position = $this->_input()->readInt('moveElementTo');
    $id = $this->_input()->readInt('moveElementID');

    if (   !$id
        || !$position
    ) {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_process_cx_area_element_boxes_move_box_failure']));
      return;
    }

    if ($position > count($this->_getChildElements())) {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_process_cx_area_element_boxes_move_box_failure']));
      return;
    }

    $target = false;
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      if ($el->getElementId() == $id) {
        $target = $el;
      }
    }

    if (!$target) {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_process_cx_area_element_boxes_move_box_failure']));
      return;
    }

    $helper = $this->_positionHelper();

    if ($position > $helper->getHighestPosition()) {
      $this->setMessage(Message::createFailure('001' . $_LANG['cx_message_area_process_cx_area_element_boxes_move_box_failure']));
      return;
    }

    $helper->move($id, $position);

    // Force refresh of properties
    $this->_contentElementsFactory->clearCache();
    $this->_childElementRows = null;
    $this->_childElements = null;

    $this->setMessage(Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_boxes_move_box_success']));
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
        'CXAEElementableType' => 'contentitem_cx_area_element',
        'CXAEElementableID' => $this->getElementId(),
      )
    );
  }
}
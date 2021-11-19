<?php

/**
 * $LastChangedDate: 2019-03-08 13:57:40 +0100 (Fr, 08 Mär 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Text extends ContentItemCX_Areas_Element
{
  /**
   * {@inheritdoc}
   */
  public function deleteElementContent()
  {
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
  public function getElementTexts($subcontent = true)
  {
    return array($this->_getElementOption('data.content'));
  }

  /**
   * {@inheritdoc}
   */
  public function updateElementContent()
  {
    $filter = $this->_getElementOption('settings.plain') ?
      Input::FILTER_PLAIN : Input::FILTER_CONTENT_TEXT;

    $this->_options['data']['content'] = $this->_input()->readString(
      $this->_getElementInputName(), $filter);

    $this->_updateElementContent($this->_options['data']['content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function _parseOptions($options)
  {
    $options = parent::_parseOptions($options);

    $options['lang'] = array_merge(array(
      'title' => 'cx_areas_text_label',
    ), $options['lang']);

    $options['settings'] = array_merge(array(
      'plain' => false, // boolean: false,true
    ), $options['settings']);

    return $options;
  }
}
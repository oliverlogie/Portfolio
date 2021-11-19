<?php

/**
 * $LastChangedDate: 2019-03-08 13:57:40 +0100 (Fr, 08 Mär 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Alternatives extends ContentItemCX_Areas_Element
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

    // display the first child element by default
    if (!$data['content']) {
      $data['content'] = $data['children'][0]['name'];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function updateElementContent()
  {
    $this->_options['data']['content'] = $this->_input()->readString(
      $this->_getElementInputName());

    // we check if the value matches the name of a child element and otherwise
    // reset to an empty value, which will cause the first child element to be
    // displayed by default

    $allowedValues = array();
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      $allowedValues[] = $el->getElementTemplateData()['name'];
    }

    if (   $allowedValues
        && !in_array($this->_options['data']['content'], $allowedValues)
    ) {
      $this->_options['data']['content'] = '';
    }

    $this->_updateElementContent($this->_options['data']['content']);

    // process children
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      $el->updateElementContent();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function _parseOptions($options)
  {
    $options = parent::_parseOptions($options);

    $options['lang'] = array_merge(array(
      'title' => 'cx_areas_area_alternatives_label',
    ), $options['lang']);

    return $options;
  }
}
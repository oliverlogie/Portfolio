<?php

/**
 * $LastChangedDate: 2019-03-08 13:57:40 +0100 (Fr, 08 Mär 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Video extends ContentItemCX_Areas_Element
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
  public function updateElementContent()
  {
    $this->_options['data']['content'] = $this->_input()->readString(
      $this->_getElementInputName(), Input::FILTER_PLAIN);

    $this->_updateElementContent($this->_options['data']['content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function _parseOptions($options)
  {
    $options = parent::_parseOptions($options);

    $options['lang'] = array_merge(array(
      'title'       => 'cx_areas_video_label',
      'placeholder' => 'cx_areas_video_placeholder_label',
    ), $options['lang']);

    return $options;
  }
}
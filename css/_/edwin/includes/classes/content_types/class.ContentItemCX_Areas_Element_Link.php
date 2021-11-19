<?php

/**
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Link extends ContentItemCX_Areas_Element
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
  public function getElementTemplateData()
  {
    global $_LANG;

    $data = parent::getElementTemplateData();

    $content = $data['content'];

    $id = (int)$content['intlink'];

    $content['intlink'] = $this->getInternalLinkHelper($id)->toArray('cx');
    $data['content'] = $content;

    $data['url_autocomplete'] = "index.php?action=response&site=$this->site_id"
      . "&page=$this->page_id&request=ContentItemAutoComplete"
      . "&excludeContentItems=$this->page_id"
      . "&scope={$this->_getElementOption('settings.scope')}";

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function updateElementContent()
  {
    $content = $this->_getElementOption('data.content');

    $content['intlink'] = $this->_input()->readString($this->_getElementInputName() . '_intlink_id');
    $content['extlink'] = $this->_input()->readString($this->_getElementInputName() . '_extlink');

    $this->_options['data']['content'] = $content;

    $this->_updateElementContent($this->_options['data']['content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function _parseOptions($options)
  {
    $options = parent::_parseOptions($options);

    $options['data']['content'] = array_merge(array(
      'intlink' => '',
      'extlink' => '',
    ), json_decode($options['data']['content'], true) ?: array());

    $options['settings'] = array_merge(array(
      'scope' => 'local', // string: local,global
    ), $options['settings']);

    $options['lang'] = array_merge(array(
      'title'               => 'cx_areas_area_link_label',
      'intlink_title'       => 'cx_areas_area_link_intlink_label',
      'intlink_placeholder' => 'cx_areas_area_link_intlink_placeholder_label',
      'extlink_title'       => 'cx_areas_area_link_extlink_label',
      'extlink_placeholder' => 'cx_areas_area_link_extlink_placeholder_label',
    ), $options['lang']);

    return $options;
  }
}
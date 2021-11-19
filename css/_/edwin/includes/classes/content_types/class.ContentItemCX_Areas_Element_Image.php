<?php

/**
 * $LastChangedDate: 2019-03-08 13:57:40 +0100 (Fr, 08 Mär 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_Element_Image extends ContentItemCX_Areas_Element
{
  /**
   * {@inheritdoc}
   */
  public function deleteElementContent()
  {
    $content = $this->_getElementOption('data.content');

    if ($content['image']) {
      self::_deleteImageFiles($content['image']);
    }

    $this->_deleteElementContentRow();
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateElementContent($pageId, $areaId, $elementableId)
  {
    $content = $this->_getElementOption('data.content');

    $sql = " SELECT MAX(CXAEID) "
         . " FROM {$this->table_prefix}contentitem_cx_area_element ";
    $nextId = (int)$this->db->GetOne($sql) + 1;

    if ($content['image']) {
      $content['image'] = CopyHelper::createImage($content['image'], $pageId, $this->site_id, $areaId, $nextId);
    }

    $this->_options['data']['content'] = $content;

    parent::duplicateElementContent($pageId, $areaId, $elementableId);
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
  public function getElementImageTitles($subcontent = true)
  {
    $content = $this->_getElementOption('data.content');

    return array($content['title']);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementTemplateData()
  {
    $data = parent::getElementTemplateData();

    $content = $data['content'];

    $prefix = $this->_configPrefix;

    $content['image'] = array_merge(array(
      'value'     => $content['image'],
      'normal'    => $this->get_normal_image($this->_configPrefix, $content['image']),
      'large'     => $this->get_large_image($this->_configPrefix, $content['image']) != $this->get_normal_image('cx', $content['image']) ? $this->get_large_image('cx', $content['image']) : '',
      'thumbnail' => $this->get_thumb_image($content['image']),
      'zoom'      => $this->_getImageZoomLink($this->_configPrefix, $content['image']),
      'size_info' => $this->_getImageSizeInfo(array($prefix), 0),
    ), $this->_getUploadedImageDetails($content['image'], $this->_contentPrefix, $prefix, 0));

    $data['content'] = $content;

    $data['url_image_delete'] = $this->_getElementProcessUrl('deleteImage');

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function updateElementContent()
  {
    $content = $this->_getElementOption('data.content');

    if (isset($_FILES[$this->_getElementInputName() . '_image'])) {
      $existingImage = $content['image'];

      $uploadedImage = $this->_storeImage(
        $_FILES[$this->_getElementInputName() . '_image'],
        $existingImage,
        $this->getConfigPrefix(),
        0,
        array($this->site_id, $this->page_id, $this->_getElementOption('data.area_id'), $this->_getElementOption('data.id')),
        true,
        true
      );

      if ($uploadedImage) {
        $content['image'] = $uploadedImage;
      }
    }

    $content['title'] = $this->_input()->readString($this->_getElementInputName() . '_title');
    $content['alt'] = $this->_input()->readString($this->_getElementInputName() . '_alt');

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
      'image' => '',
      'title' => '',
      'alt'   => '',
    ), json_decode($options['data']['content'], true) ?: array());

    $options['lang'] = array_merge(array(
      'title'             => 'cx_areas_image_label',
      'title_placeholder' => 'cx_areas_image_title_placeholder_label',
      'title_title'       => 'cx_areas_image_title_label',
      'alt_placeholder'   => 'cx_areas_image_alt_placeholder_label',
      'alt_title'         => 'cx_areas_image_alt_label',
    ), $options['lang']);

    return $options;
  }

  /**
   * Action: deletes the element image
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _processElementActionDeleteImage()
  {
    global $_LANG;

    $content = $this->_getElementOption('data.content');

    if ($content['image']) {
      self::_deleteImageFiles($content['image']);
      $content['image'] = '';
      $this->setMessage(Message::createSuccess($_LANG['cx_message_area_process_cx_area_element_image_delete_image_success']));
    }

    $this->_options['data']['content'] = $content;

    $this->_updateElementContent($this->_options['data']['content']);
  }
}
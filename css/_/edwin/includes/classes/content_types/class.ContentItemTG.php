<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-08-19 08:00:15 +0200 (Mo, 19 Aug 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemTG extends AbstractContentItemGallery
{
  protected $_configPrefix = 'tg';
  protected $_contentPrefix = 'tg';
  protected $_columnPrefix = 'TG';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 3,
    'Image' => 1,
  );
  protected $_contentImageTitles = false;
  protected $_columnImagePrefix = 'TGI';
  protected $_templateSuffix = 'TG';

  /**
   * Delete content
   */
  public function delete_content()
  {
    // Query all gallery images from the database.
    $sql = ' SELECT TGIID, TGIImage '
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE FK_CIID = $this->page_id ";
    $images = $this->db->GetAssoc($sql);

    // Delete all gallery images from filesystem.
    self::_deleteImageFiles(array_values($images));

    // Delete all gallery image datasets.
    $sql = " DELETE FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);

    $sql = " DELETE FROM {$this->table_prefix}contentitem_tg_image_tags "
         . ' WHERE FK_TGIID IN (' . implode(',', array_keys($images)) . ') ';
    $this->db->query($sql);

    // Call the default delete content method.
    return parent::delete_content();
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    // Duplicate content item
    $parentId = parent::duplicateContent($pageId);

    // Duplicate images
    $sql = ' SELECT TGIID, TGITitle, TGIImage, TGIPosition, TGIImageTitle, TGIText '
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    $now = date('Y-m-d H:i:s');
    while ($row = $this->db->fetch_row($result)) {
      $image = CopyHelper::createImage($row['TGIImage'], $pageId, $this->site_id);
      $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image "
           . " (TGIImageTitle, TGIImage, TGIPosition, TGICreateDateTime, "
           . "  FK_CIID, TGITitle, TGIText) "
           . " VALUES ('{$this->db->escape($row['TGIImageTitle'])}', "
           . "         '{$this->db->escape($image)}', '{$row['TGIPosition']}', "
           . "         '{$now}', {$pageId}, '{$this->db->escape($row['TGITitle'])}', "
           . "         '{$this->db->escape($row['TGIText'])}')";
      $this->db->query($sql);
      $insertedId = $this->db->insert_id();
      $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image_tags (FK_TGIID, FK_TAID, TGITPosition) "
           . "  SELECT {$insertedId}, citgi.FK_TAID, citgi.TGITPosition "
           . "    FROM {$this->table_prefix}contentitem_tg_image_tags AS citgi "
           . "   WHERE citgi.FK_TGIID = {$row['TGIID']} ";
      $this->db->query($sql);
    }

    return $parentId;
  }

  public function edit_content()
  {
    $this->_readUploadedZip();
    $this->_uploadZip();
    $this->_uploadImage();
    $this->_processZip();
    $this->_moveGalleryImage();
    $this->_deleteGalleryImage();
    $this->_deleteGalleryImages();
    $this->_createTag();

    parent::edit_content();
  }

  public function get_content($params = array())
  {
    global $_LANG;

    $this->_readUploadedZip();
    $uploadZipData = $this->_getUploadZipData();

    $tagGlobalModel = new TagGlobal($this->db, $this->table_prefix);
    $tagGlobals = $tagGlobalModel->read(sprintf(" FK_SID = %s ", $this->site_id));
    $tagOptions = array();
    foreach ($tagGlobals as $tagGlobal) {
      /* @var $tagGlobal TagGlobal */
      $tags = $tagGlobal->getTags();

      $options = array();
      foreach ($tags as $tag) {
        /* @var $tag Tag */
        $options[$tag->id] = parseOutput($tag->title);
      }

      $tagOptions[] = array(
        'label' => parseOutput($tagGlobal->title),
        'options' => $options,
      );
    }

    // Retrieve all gallery images.
    $galleryImages = array();

    $sql = " SELECT i.TGIID, i.TGITitle, i.TGIText, i.TGIImage, i.TGIImageTitle, i.TGIPosition, GROUP_CONCAT(t.FK_TAID) AS selectedTags "
         . " FROM {$this->table_prefix}contentitem_tg_image i "
         . " LEFT JOIN {$this->table_prefix}contentitem_tg_image_tags t "
         . "        ON i.TGIID = t.FK_TGIID "
         . " WHERE i.FK_CIID = $this->page_id "
         . " GROUP BY i.TGIID, i.TGITitle, i.TGIText, i.TGIImage, i.TGIImageTitle, i.TGIPosition "
         . " ORDER BY i.TGIPosition ";

    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result))
    {
      $galleryImages[$row['TGIID']] = array(
        'tg_gallery_image_src'            => $this->get_thumb_image($row['TGIImage']),
        'tg_gallery_image_id'             => $row['TGIID'],
        'tg_gallery_image_position'       => $row['TGIPosition'],
        'tg_gallery_image_delete_link'    => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;deleteGalleryImageID={$row['TGIID']}&amp;scrollToAnchor=a_gallery_images&amp;areaLastEdited=tg_gallery_images_area",
        'tg_gallery_image_subtitle'       => parseOutput($row['TGIImageTitle'], 2),
        'tg_gallery_image_has_customdata' => (int)($row['TGITitle'] || $row['TGIText']),
        'tg_gallery_image_customdata_available_label' => ($row['TGITitle'] || $row['TGIText']) ?
            $_LANG['tg_gallery_image_customdata_available_label'] : $_LANG['tg_gallery_image_customdata_not_available_label'],
        'tg_gallery_image_customdata_url' => '?action=response&request=GetTaggedImageCustomDataForm&site=' . $this->site_id . '&page=' . $this->page_id . '&image=' . $row['TGIID'],
        'tg_gallery_image_tags_options'   => AbstractForm::multiSelectOptgroups($tagOptions, array_map('intval',explode(',', $row['selectedTags']) ?: array())),
      );
    }
    $this->db->free_result($result);

    // Retrieve all gallery image tags.
    $galleryImagesTags = array();
    $sql = ' SELECT FK_TGIID, FK_TAID, TGITPosition, TATitle'
         . " FROM {$this->table_prefix}contentitem_tg_image_tags "
         . " JOIN {$this->table_prefix}contentitem_tg_image "
         . '      ON FK_TGIID = TGIID '
         . " JOIN {$this->table_prefix}module_tag "
         . '      ON FK_TAID = TAID '
         . " WHERE FK_CIID = $this->page_id "
         . ' ORDER BY TGIPosition ASC, TGITPosition ASC';
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $galleryImagesTags[$row['FK_TGIID']][] = array(
        'tag_id' => $row['FK_TAID'],
        'tag_title' => parseOutput($row['TATitle']),
      );
    }
    $this->db->free_result($result);

    $imagePositionSelect = '<select name="tg_gallery_upload_image_position" class="tg_gallery_upload_image_position form-control">';
    for ($i = 1; $i <= $count + 1; $i++) {
      $imagePositionSelect .= "<option value=\"$i\">$i</option>";
    }
    $imagePositionSelect .= '</select>';

    $post = new Input(Input::SOURCE_POST);
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="content" />'
                  . '<input type="hidden" name="action2" value="" />'
                  . '<input type="hidden" id="scrollToAnchor" name="scrollToAnchor" value="" />'
                  . '<input type="hidden" id="areaLastEdited" name="areaLastEdited" value="" />'
                  . '<input type="hidden" id="tagBoxActivePane" name="tagBoxActivePane" value="'.$post->readInt('tagBoxActivePane', 0).'" />';;

    $scrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';
    $areaLastEdited = isset($_REQUEST['areaLastEdited']) ? $_REQUEST['areaLastEdited'] : '';
    $maximumReached = $count >= $this->getConfig('max_images');

    // determine the area to open after page reload
    $zipUploaded = $post->exists('process_tg_gallery_upload_zip') ? true : false;
    $zipProcessed = $post->exists('process_tg_gallery_uploaded_zip_process') ? true : false;
    $imageUploaded = $post->exists('process_tg_gallery_upload_image') ? true : false;
    // open images area if images are inside and no zip file or images have been uploaded / or a zip file has been processed
    if ((!$areaLastEdited && !empty($galleryImages) && !$imageUploaded && !$zipUploaded) || (!empty($galleryImages) && $zipProcessed)) {
      $areaLastEdited = 'tg_gallery_images_area';
    } else if (!$areaLastEdited) {
      $areaLastEdited = 'tg_gallery_uploads_area';
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, 'content_types/ContentItemTG.tpl');
    // If $scrollToAnchor is set we display the current message a second time for the gallery.
    $this->tpl->parse_if($tplName, 'gallery_message', $this->_getMessage() && $scrollToAnchor, $this->_getMessageTemplateArray('tg_gallery'));
    $this->tpl->parse_if($tplName, 'gallery_upload_zip_available', !$this->_zipFile);
    $this->tpl->parse_if($tplName, 'gallery_uploaded_zip', $this->_zipFile, array(
      'tg_gallery_uploaded_zip_name' => $this->_zipFileName,
    ));
    $this->tpl->parse_if($tplName, 'gallery_maximum_not_reached', !$maximumReached, array(
      'tg_gallery_upload_image_position_select' => $imagePositionSelect,
    ));
    $this->tpl->parse_if($tplName, 'gallery_maximum_reached', $maximumReached);

    $this->tpl->parse_if($tplName, 'gallery_upload_tags_available', !empty($tagOptions), array(
      'tg_gallery_upload_tags_options' => AbstractForm::multiSelectOptgroups($tagOptions, $uploadZipData['tags']),
    ));

    $this->tpl->parse_if($tplName, 'gallery_image_tags_available', !empty($tagOptions));
    $this->tpl->parse_loop($tplName, $galleryImages, 'gallery_images');
    $this->tpl->parse_if($tplName, 'gallery_images_uploaded', $galleryImages);
    $this->tpl->parse_vars($tplName, array(
      'tg_gallery_image_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
      'tg_hidden_fields' => $hiddenFields,
      'tg_gallery_image_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveGalleryImageID=#moveID#&moveGalleryImageTo=#moveTo#&scrollToAnchor=a_gallery_images&areaLastEdited=tg_gallery_images_area",
      'tg_scroll_to_anchor' => $scrollToAnchor,
      'tg_area_last_edited' => $areaLastEdited,
      'tg_upload_area_customdata_field_title' => parseOutput($uploadZipData['title'], 2),
      'tg_upload_area_customdata_field_subtitle' => parseOutput($uploadZipData['subtitle'], 2),
      'tg_upload_area_customdata_field_text' => parseOutput($uploadZipData['text'], 0),
      'tg_image_upload_active_tab' => !$zipUploaded ? 'active' : 'inactive',
      'tg_zip_upload_active_tab' => $zipUploaded ? 'active' : 'inactive',
      'tg_image_upload_area_active' => !$zipUploaded ? 'in active' : '',
      'tg_zip_upload_area_active' => $zipUploaded ? 'in active' : '',
      'tg_gallery_tag_box_active_pane' => $post->readInt('tagBoxActivePane', 0),
      'tg_max_zip_upload_tags' => $this->getConfig('max_zip_upload_tags'),
      'tg_max_image_tags' => $this->getConfig('max_image_tags'),
      'tg_tags_available' => empty($tagOptions) ? 0 : 1,
      'tg_max_file_size' => $this->getConfig('file_size'),
    ));

    return parent::get_content(array_merge($params, array(
      'settings' => array('tpl' => $tplName)
    )));

  }

  public function getImageTitles($subcontent = true)
  {
    $titles = parent::getImageTitles();

    if ($subcontent === true)
    {
      $sql = " SELECT TGITitle "
           . " FROM {$this->table_prefix}contentitem_tg_image "
           . " WHERE FK_CIID = $this->page_id "
           . " ORDER BY TGIPosition ASC ";
      $tmpTitles = $this->db->GetCol($sql);

      $titles = array_merge($titles, $tmpTitles);
    }

    return $titles;
  }

  /**
   * Preview content
   */
  public function preview()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $images = $this->_createPreviewImages(array(
      'TGImage' => 'tg_image', // Box image
    ));
    $imageSrc1 = $images['tg_image'];
    $imageSrcLarge1 = $this->_hasLargeImage($imageSrc1);
    $galleryItems = array();
    $sql = ' SELECT TGIID, TGITitle, TGIImage, TGIPosition '
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE FK_CIID = $this->page_id "
         . ' ORDER BY TGIPosition ';
    $result = $this->db->query($sql);
    $galleryImagesCount = $this->db->num_rows($result);
    $imagesPerPage = $this->getConfig('galleryimages_per_page');
    $displayThreshold = $this->getConfig('file_size_display_threshold');

    while ($row = $this->db->fetch_row($result)) {
      $position = (int)$row['TGIPosition'];

      if ($position > $imagesPerPage) {
        break;
      }
      $imageSubtitle = $row['TGITitle'];
      $large = $this->get_large_image($this->_contentPrefix, $row['TGIImage']);
      $imageSrc = $row['TGIImage'];
      $thumbSource = $this->get_thumb_image($row['TGIImage']);

      // $tmpImage contains large or normal image. (used for determining
      // size and type)
      $tmpImage = ($large) ? $large : '../'.$imageSrc;

      $tplSize = '';
      $size = filesize($tmpImage); // Image filesize
      if ($size >= parsePhpIniSize($displayThreshold)) {
        $tplSize = formatFileSize($size);
      }

      $res = getimagesize($tmpImage);
      $resolution = sprintf($_LANG['tg_image_resolution'], $res[0], $res[1]);
      $pathInfo = pathinfo($tmpImage); // Image file extension
      $extension = isset($pathInfo['extension']) && $pathInfo['extension'] ?
                   $pathInfo['extension'] : $_LANG['c_tg_file_extension_unknown_label'];

      $galleryItems[$row['TGIID']] = array(
        'c_tg_image_src' => $thumbSource,
        'c_tg_image_real_src' => '',
        'c_tg_image_link' => '#',
        'c_tg_sid' => $this->site_id - 1,
        'c_tg_image_id' => $position,
        'c_tg_active' => $position == 1 ? 'active' : '',
        'c_tg_image_subtitle' => $imageSubtitle,
        'c_tg_image_extension' => $extension,
        'c_tg_image_resolution' => $resolution,
        'c_tg_image_size' => $tplSize,
      );

      if ($position == 1) {
        $galleryImageSource = '../' . $row['TGIImage'];
        $galleryImageLarge = $this->_hasLargeImage($galleryImageSource);
      }
    }
    $this->db->free_result($result);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->set_tpl_dir('../templates');
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('tg')
    ));

    /* Do not show the zoom link */
    $this->tpl->parse_if($tplName, 'gallery_zoom', $galleryImageLarge, array(
      'c_tg_gallery_zoom_link' => '#',
    ));

    /* do not show links for switching images */
    $this->tpl->parse_if($tplName, 'gallery_first_link_available', false);
    $this->tpl->parse_if($tplName, 'gallery_first_link_not_available', false);
    $this->tpl->parse_if($tplName, 'gallery_previous_link_available', false);
    $this->tpl->parse_if($tplName, 'gallery_previous_link_not_available', false);
    $this->tpl->parse_if($tplName, 'gallery_next_link_available', false);
    $this->tpl->parse_if($tplName, 'gallery_next_link_not_available', false);
    $this->tpl->parse_if($tplName, 'gallery_last_link_available', false);
    $this->tpl->parse_if($tplName, 'gallery_last_link_not_available', false);
    $this->tpl->parse_if($tplName, 'zoom1', $imageSrcLarge1, array( 'c_tg_zoom1_link' => $imageSrcLarge1 ));
    $this->tpl->parse_if($tplName, 'image1', $imageSrc1, array( 'c_tg_image_src1' => $imageSrc1 ));
    $this->tpl->parse_if($tplName, 'more_pages', false);
    $this->tpl->parse_loop($tplName, $galleryItems, 'gallery_items');
    // Do not parse tag loops with content in preview
    foreach ($galleryItems as $key => $val) {
      $this->tpl->parse_loop($tplName, array(), 'gallery_item_' . $val['c_tg_image_id'] . '_tags');
    }

    $this->tpl->parse_vars($tplName, array(
      'c_tg_title' => parseOutput($post->readString('tg_title', Input::FILTER_CONTENT_TITLE), 2),
      'c_tg_title_plain' => '',
      'c_tg_text1' => parseOutput($post->readString('tg_text1', Input::FILTER_CONTENT_TEXT), 1),
      'c_tg_text2' => parseOutput($post->readString('tg_text2', Input::FILTER_CONTENT_TEXT), 1),
      'c_tg_text3' => parseOutput($post->readString('tg_text3', Input::FILTER_CONTENT_TEXT), 1),
      'c_tg_gallery_image_src' => $galleryImageSource,
      'c_surl' => '../',
    ));

    $content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');
    return $content;
  }

  /**
   * Return content of all content items.
   */
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,TGTitle,TGText1,TGText2,TGText3 FROM ".$this->table_prefix."contentitem_tg cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["TGTitle"];
      $class_content[$row["CIID"]]["c_title2"] = "";
      $class_content[$row["CIID"]]["c_title3"] = "";
      $class_content[$row["CIID"]]["c_text1"] = $row["TGText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["TGText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["TGText3"];
      $class_content[$row["CIID"]]["c_image_title1"] = "";
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }

  public function sendResponse($request)
  {
    switch($request)
    {
      case 'UpdateTaggedImage':
        return $this->_sendResponseUpdateTaggedImage();
        break;
      case 'GetTaggedImageCustomDataForm':
        return $this->_sendResponseGetTaggedImageCustomDataForm();
        break;
      case 'UpdateTaggedImageCustomData':
        return $this->_sendResponseUpdateTaggedImageCustomData();
        break;
      case 'DeleteTaggedImageCustomData':
        return $this->_sendResponseDeleteTaggedImageCustomData();
        break;
      default:
        return parent::sendResponse($request);
        break;
    }

  }

  protected function _deleteGalleryImageID($ID)
  {
    // Retrieve the image subtitle, before deleting the image.
    $sql = " SELECT TGITitle "
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE TGIID = $ID ";
    $title = $this->db->GetOne($sql);

    $deleted = parent::_deleteGalleryImageID($ID);

    if (!$deleted) {
      return false;
    }

    // The image has been successfully deleted, so remove its title from
    // the search index.
    $this->_spiderElement($this->page_id, 'image', $title);

    // Remove the image tags.
    $sql = " DELETE FROM {$this->table_prefix}contentitem_tg_image_tags "
         . ' WHERE FK_TGIID = ' . $ID;
    $result = $this->db->query($sql);

    return true;
  }

  protected function _processedValues()
  {
    return array_merge(parent::_processedValues(), array(
      'deleteGalleryImageID',
      'moveGalleryImageID',
      'process_tg_gallery_add_tag',
      'process_tg_gallery_images_delete',
      'process_tg_gallery_upload_image',
      'process_tg_gallery_upload_zip',
      'process_tg_gallery_uploaded_zip_process',
      'process_tg_upload_add_tag',
    ));
  }

  /**
   * Clear the upload title and tags post data variables.
   */
  private function _clearUploadPostData()
  {
    $_POST['tg_gallery_upload_title'] = null;
    $_POST['tg_gallery_upload_tags'] = null;
  }

  /**
   * Create a new tag.
   */
  private function _createTag()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    // Create a new tag from the upload area's tag list.
    if ($post->exists('process_tg_upload_add_tag'))
    {
      // There is only one button pressed, with the id of the tag group (global)
      // being the POST array data key.
      $globalID = $post->readArrayIntToString('process_tg_upload_add_tag');
      $globalID = array_pop(array_keys($globalID));
      // Retrieve the title.
      $title = $post->readArrayIntToString('tg_upload_add_tag_title');
      $title = $title[$globalID];
    }
    // Create a new tag from the gallery image area's tag list
    else if ($post->exists('process_tg_gallery_add_tag')) {
      $globalID = $post->readArrayIntToString('process_tg_gallery_add_tag');
      $globalID = array_pop(array_keys($globalID));
      // Retrieve the title.
      $title = $post->readArrayIntToString('tg_gallery_add_tag_title');
      $title = $title[$globalID];
    }
    else {
      return;
    }

    $result = ModuleTag::createTag($this->db, $this->table_prefix, $globalID, $title);

    if ($result === 1) {
      $this->setMessage(Message::createSuccess($_LANG['tg_message_tag_success']));
    }
    else if ($result === -1) {
      $this->setMessage(Message::createFailure($_LANG['tg_message_tag_duplicate_title']));
    }
    else if ($result === 0) {
      $this->setMessage(Message::createFailure($_LANG['tg_message_tag_error']));
    }

  }

  /**
   * Returns an array containing data entered in the Upload area.
   *
   * @return array
   *         The array containing:
   *         'title' The image title provided by the user.
   *         'tags' The image tags set for the image by the user.
   */
  private function _getUploadZipData()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_tg_gallery_upload_image') &&
        !$post->exists('process_tg_gallery_upload_zip') &&
        !$post->exists('process_tg_gallery_uploaded_zip_process'))
    {
      return array('subtitle' => '', 'title' => '', 'text' => '', 'tags' => array());
    }

    return array(
      'subtitle' => $post->readString('tg_gallery_upload_customdata_subtitle', Input::FILTER_CONTENT_TITLE),
      'title'    => $post->readString('tg_gallery_upload_customdata_title', Input::FILTER_CONTENT_TITLE),
      'text'     => $post->readString('tg_gallery_upload_customdata_text', Input::FILTER_CONTENT_TEXT),
      'tags'     => array_unique($post->readArrayIntToInt('tg_gallery_upload_tags')),
    );
  }

  /**
   * Extract and process a zip file.
   *
   * No processing, if there has not been a valid zip file uploaded, or the
   * process_tg_gallery_uploaded_zip_process POST parameter does not exist.
   */
  private function _processZip()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $prefix = $this->_contentPrefix;

    if (!$post->exists("process_tg_gallery_uploaded_zip_process") || !$this->_zipFile) {
      return;
    }

    $imagePositionStart = $post->readInt("tg_gallery_uploaded_zip_position");
    $uploadData = $this->_getUploadZipData();
    $title = $uploadData['title'];
    $subtitle = $uploadData['subtitle'];
    $text = $uploadData['text'];
    $tags = $uploadData['tags'];

    // lets open the zip
    $zip = new dUnzip2($this->_zipFile);
    $zip->debug = false;
    $filelist = $zip->getList();
    $message = $zip->getLastError();

    // Error opening zip file -> stop processing
    if ($message != "Loading list from 'End of Central Dir' index list...")
    {
      $this->setMessage(Message::createFailure($_LANG["tg_message_zipfile_error"] . "<!-- $message -->"));
      return 0;
    }

    asort($filelist);
    $i = 1;
    $cnt_valid_images = 0;
    $cnt_invalid_type_images = 0;
    $cnt_invalid_resolution_images = 0;
    $cnt_invalid_size_images = 0;
    $cnt_images = 0;
    $cnt_too_much_valid_images = 0;
    $saved_images = array();
    $imageSize = $this->getConfig('image_size');
    $imageTypes = $this->getConfig('image_types');
    $maxImages = $this->getConfig('max_images');

    $sql = " SELECT COUNT({$this->_columnImagePrefix}ID) "
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE FK_CIID = $this->page_id ";
    $existingImageCount = $this->db->GetOne($sql);

    foreach ($filelist as $filename => $fileinfo)
    {
      $delete_tmp_image = 1;
      $cnt_images++;

      // skip invalid MacOSX files
      if (    strpos($filename, '__MACOSX') !== false
          || strpos($filename, '.DS_Store') !== false
      ) {
        continue;
      }

      $tmp_image_extension = mb_substr($filename,mb_strlen($filename)-3);
      $tmp_name = "files/temp/ci_tmp".time()."_".$this->site_id.$this->page_id."_$i.".$tmp_image_extension;

      // unzip actual file
      $zip->unzip($filename, $tmp_name);

      // get current file info
      list($tmp_image_width, $tmp_image_height, $tmp_image_type) = getimagesize($tmp_name);
      $tmp_image_extension = pathinfo($tmp_name);
      $tmp_image_extension = mb_strtolower($tmp_image_extension['extension']);
      $tmp_image_size = filesize($tmp_name);

      // An exception is thrown if the current file isn't an image file.
      try
      {
        $tmpImage = CmsImageFactory::create($tmp_name);
        // If image size should be ignored, user is not forced to upload an image with configured sizes.
        // We have to scale the image later, if it doesn't fit with configured size
        if ($this->_configHelper->readImageConfiguration($prefix, 'ignore_image_size', 0)) {
          $imageSizeInvalid = false;
        }
        else if ($this->_configHelper->readImageConfiguration($prefix, 'autofit_image_upload', 0)) {
          $tmpImage = $this->_getResizedImageAutoFit($tmpImage, $prefix, 0);
          $imageSizeInvalid = $this->_storeImageGetSize($tmpImage, $prefix, 0, false) === ContentBase::IMAGESIZE_INVALID;
        }
        else {
          $imageSizeInvalid = $this->_storeImageGetSize($tmpImage, $prefix, 0, false) === ContentBase::IMAGESIZE_INVALID;
        }
      }
      catch(CmsImageException $e) {
        $imageSizeInvalid = false;
      }

      // Check file
      if (!in_array($tmp_image_extension,$imageTypes)) {
        $cnt_invalid_type_images++;
      }
      else if ($imageSizeInvalid) {
        $cnt_invalid_resolution_images++;
      }
      else if ($tmp_image_size > $imageSize) {
        $cnt_invalid_size_images++;
      }
      else
      {
        if (($existingImageCount + $cnt_valid_images) < $maxImages)
        {
          $rand = sprintf('%04d', rand(0, 9999));
          // save image and generate thumbnail; use custom file prefix
          if ($dest_name = $this->_storeImage($tmp_name, null, $this->getConfigPrefix(), $rand, null, null, true, $this->_configPrefix . '_gallery', false))
          {
            $saved_images[] = $dest_name;
            $cnt_valid_images++;
          }
          else {
            $cnt_invalid_resolution_images++;
          }
          // delete temporary image file
          unlinkIfExists($tmp_name);

          $delete_tmp_image = 0;
        }
        else {
          $cnt_too_much_valid_images++;
        }

        $i++;
      }
      // remove invalid file
      if ($delete_tmp_image) {
        unlinkIfExists($tmp_name);
      }
    }

    // If the images should be inserted at the start we have to make room
    // for the new images (increase the position of all existing images).
    if ($imagePositionStart)
    {
      $sql = " UPDATE {$this->table_prefix}contentitem_tg_image "
           . " SET {$this->_columnImagePrefix}Position = {$this->_columnImagePrefix}Position + $cnt_valid_images "
           . " WHERE FK_CIID = $this->page_id "
           . " ORDER BY {$this->_columnImagePrefix}Position DESC ";
      $this->db->query($sql);
      $position = 1;
    }
    else {
      $position = $existingImageCount + 1;
    }

    $imageIDs = null;
    // Insert images into the database.
    foreach ($saved_images as $image)
    {
      $sqlArgs = array(
        "{$this->_columnImagePrefix}Title"          => "'{$this->db->escape($title)}'",
        "{$this->_columnImagePrefix}ImageTitle"     => "'{$this->db->escape($subtitle)}'",
        "{$this->_columnImagePrefix}Text"           => "'{$this->db->escape($text)}'",
        "{$this->_columnImagePrefix}Image"          => "'$image'",
        "{$this->_columnImagePrefix}Position"       => "'$position'",
        "{$this->_columnImagePrefix}CreateDateTime" => "NOW()",
        "FK_CIID"                                   => "'$this->page_id'",
      );
      $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image "
           . " ( "  . implode(',', array_keys($sqlArgs)). " ) VALUES ( " . implode(',', $sqlArgs). " )";
      $this->db->query($sql);
      $imageIDs[] = $this->db->insert_id();
      $this->_spiderElement($this->page_id, 'image', '', $title);
      $position++;
    }

    // Insert all image tags provided (only if there have been tags specified).
    if ($imageIDs && $tags)
    {
      $sqlValues = array();

      // Add all tags specified to all images.
      foreach ($imageIDs as $imageID)
      {
        $i = 1;
        foreach ($tags as $tagID) {
          $sqlValues[] = '(' . $imageID . ',' . $tagID . ',' . ($i++) . ')';
        }
      }

      $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image_tags "
           . ' (FK_TGIID, FK_TAID, TGITPosition) '
           . ' VALUES '
           . implode(',', $sqlValues);
      $this->db->query($sql);
    }

    // delete zip file
    $zip->close();
    unlinkIfExists($this->_zipFile);
    $this->db->query("DELETE FROM ".$this->table_prefix."user_uploads WHERE FK_CIID=".$this->page_id);
    $this->_zipFile = '';
    $this->_zipFileName = '';
    $this->_clearUploadPostData();

    // process message
    $message = sprintf($_LANG["tg_message_process_zip_success"],$cnt_valid_images,$cnt_images);
    $messageType = Message::TYPE_SUCCESS;
    // error detected? - extend message
    if ($cnt_invalid_type_images || $cnt_invalid_resolution_images || $cnt_invalid_size_images)
    {
      $message .= sprintf($_LANG["tg_message_process_zip_error"], $cnt_invalid_resolution_images, $cnt_invalid_type_images, $cnt_invalid_size_images);
      $messageType = Message::TYPE_FAILURE;
    }
    if ($cnt_too_much_valid_images)
    {
      $message .= sprintf($_LANG["tg_message_process_zip_error_too_much_images"], $cnt_too_much_valid_images, $maxImages);
      $messageType = Message::TYPE_FAILURE;
    }

    $this->setMessage(new Message($messageType, $message));
  }

  /**
   * Change the TG Image data (title, tags).
   */
  private function _sendResponseUpdateTaggedImage()
  {
    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readInt('imageID');
    if (!$ID) {
      return false;
    }

    $subtitle = $post->readString('imageTitle', Input::FILTER_CONTENT_TITLE);
    $tags = $post->readArrayIntToInt('imageTags');

    // The image subtitle has to be added to the searchindex again, so retrieve
    // the old title and call the ContentItem::_spiderElement() method.

    $sql = ' SELECT TGIImageTitle '
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . ' WHERE TGIID = ' . $ID;
    $oldTitle = $this->db->GetOne($sql);
    $this->_spiderElement($this->page_id, 'image', $oldTitle, $subtitle);

    $this->_updateImageData($ID, $subtitle, $tags);

    return true;
  }

  /**
   * Update image data.
   *
   * @param int    $ID
   *        The image id.
   * @param string $subtitle
   *        The image subtitle.
   * @param array  $tags
   *        New image tag ids.
   */
  private function _updateImageData($ID, $subtitle, $tags)
  {
    // Update the image title
    $sql = " UPDATE {$this->table_prefix}contentitem_tg_image "
         . " SET TGIImageTitle = '{$this->db->escape($subtitle)}' "
         . " WHERE TGIID = $ID ";
    $this->db->query($sql);

    // Delete all image tags from database and insert new tags again.
    $sql = " DELETE FROM {$this->table_prefix}contentitem_tg_image_tags "
         . " WHERE FK_TGIID = $ID ";
    $this->db->query($sql);

    if ($tags)
    {
      $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image_tags "
           . ' (FK_TGIID, FK_TAID, TGITPosition) '
           . ' VALUES ';
      $sqlTags = array();
      $i = 1;
      foreach ($tags as $key => $val)
      {
        $sqlTags[] = ' (' . $ID .',' . (int)$val . ',' . $i . ') ';
        $i++;
      }
      $sql .= implode(',', $sqlTags);
      $this->db->query($sql);
    }
  }

  /**
   * Insert gallery image
   */
  private function _uploadImage()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_tg_gallery_upload_image') || !isset($_FILES['tg_gallery_upload_image'])) {
      return;
    }

    $image = $_FILES['tg_gallery_upload_image'];
    // There has not been provided a valid image.
    if (!$image['tmp_name']) {
      $this->setMessage(Message::createFailure($_LANG['tg_message_upload_missing_file_error']));
      return;
    }
    $uploadData = $this->_getUploadZipData();
    $title = $uploadData['title'];
    $subtitle = $uploadData['subtitle'];
    $text = $uploadData['text'];
    $tags = $uploadData['tags'];

    $position = $post->readInt('tg_gallery_upload_image_position');
    $imageSize = $this->getConfig('image_size');
    $imageTypes = $this->getConfig('image_types');

    // Check file
    if ($image['size'] > 0) // Image uploaded
    {
      $tmp_image_extension = pathinfo($image['name']);
      $tmp_image_extension = mb_strtolower($tmp_image_extension["extension"]);
      $tmp_image_size = filesize($image['tmp_name']);

      // Ensure there is a valid image type uploaded
      if (!in_array($tmp_image_extension,$imageTypes))
      {
        $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
        return;
      }

      if ($tmp_image_size > $imageSize) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['global_message_upload_file_size_error'], formatFileSize($imageSize))));
      }
      else
      {
        $rand = sprintf('%04d', rand(0, 9999));
        // save image and generate thumbnail; use custom file prefix
        if (! $dest_name = $this->_storeImage($image['tmp_name'], null, $this->getConfigPrefix(), $rand, null, null, true, $this->_configPrefix . '_gallery')) {
          return;
        }
        // Determine the position of the new image.
        $sql = ' SELECT COUNT(TGIID) '
             . " FROM {$this->table_prefix}contentitem_tg_image "
             . " WHERE FK_CIID = $this->page_id ";
        $existingImageCount = $this->db->GetOne($sql);
        if ($position < 1) {
          $position = 1;
        }
        else if ($position > $existingImageCount + 1) {
          $position = $existingImageCount + 1;
        }
        // Make room for the new image (increase the position of existing
        // images after the insertion point).
        if ($position <= $existingImageCount)
        {
          $sql = " UPDATE {$this->table_prefix}contentitem_tg_image "
               . ' SET TGIPosition = TGIPosition + 1 '
               . " WHERE FK_CIID = $this->page_id "
               . " AND TGIPosition >= $position "
               . ' ORDER BY TGIPosition DESC ';
          $this->db->query($sql);
        }
        // Insert the image into the database.
        $sqlArgs = array(
          "{$this->_columnImagePrefix}Title"          => "'{$this->db->escape($title)}'",
          "{$this->_columnImagePrefix}ImageTitle"     => "'{$this->db->escape($subtitle)}'",
          "{$this->_columnImagePrefix}Text"           => "'{$this->db->escape($text)}'",
          "{$this->_columnImagePrefix}Image"          => "'$dest_name'",
          "{$this->_columnImagePrefix}Position"       => "'$position'",
          "{$this->_columnImagePrefix}CreateDateTime" => "NOW()",
          "FK_CIID"                                   => "'$this->page_id'",
        );
        $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image "
          . " ( "  . implode(',', array_keys($sqlArgs)). " ) VALUES ( " . implode(',', $sqlArgs). " )";
        $this->db->query($sql);

        // Update the search index
        $this->_spiderElement($this->page_id, 'image', '', $title);

        // Insert all image tags into the database.
        if ($tags)
        {
          $imageID = $this->db->insert_id();
          $sql = " INSERT INTO {$this->table_prefix}contentitem_tg_image_tags "
               . ' (FK_TGIID, FK_TAID, TGITPosition) '
               . ' VALUES ';
          $sqlTags = array();
          $i = 1;
          foreach ($tags as $key => $val)
          {
            $sqlTags[] = ' (' . $imageID .',' . $val . ',' . $i . ') ';
            $i++;
          }

          $sql .= implode(',', $sqlTags);
          $this->db->query($sql);
        }

        $this->_clearUploadPostData();

        $this->setMessage(Message::createSuccess(sprintf($_LANG['tg_message_insert_image_success'], $position)));
      }
    }
  }

  /**
   * Returns the tagged image custom data form
   *
   * @return null|string
   */
  private function _sendResponseGetTaggedImageCustomDataForm()
  {
    global $_LANG2;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('image');

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}contentitem_tg_image "
         . " WHERE TGIID = '$id' "
         . " AND FK_CIID = '$this->page_id' ";
    $image = $this->db->GetRow($sql);

    if ($image) {
      $this->tpl->load_tpl('content', 'content_types/ContentItemTG_customdata.tpl');
      $this->tpl->parse_vars('content', array_merge(array(
        'tg_gallery_image_id'                    => $id,
        'tg_gallery_image_customdata_title'      => parseOutput($image['TGITitle'], 2),
        'tg_gallery_image_customdata_text'       => parseOutput($image['TGIText'], 0),
        'tg_gallery_image_customdata_delete_url' => '?action=response&request=DeleteTaggedImageCustomData&site=' . $this->site_id . '&page=' . $this->page_id . '&image=' . $id,
        'tg_gallery_image_customdata_update_url' => '?action=response&request=UpdateTaggedImageCustomData&site=' . $this->site_id . '&page=' . $this->page_id . '&image=' . $id,
      ), $_LANG2['tg']));

      return $this->tpl->parsereturn('content');
    }
    else {
      ed_http_code(\Core\Http\ResponseCode::NOT_FOUND);
    }
  }

  /**
   * Updates the image's custom data title and text
   *
   * @return null|string
   */
  private function _sendResponseUpdateTaggedImageCustomData()
  {
    $input = new Input(Input::SOURCE_REQUEST);
    $id = $input->readInt('image');

    $title = $input->readString('title', Input::FILTER_CONTENT_TITLE);
    $text = $input->readString('text', Input::FILTER_CONTENT_TEXT);

    $sql = " UPDATE {$this->table_prefix}contentitem_tg_image "
         . " SET TGITitle = '{$this->db->escape($title)}', "
         . "     TGIText = '{$this->db->escape($text)}' "
         . " WHERE TGIID = $id "
         . "   AND FK_CIID = '{$this->page_id}' ";
    $this->db->query($sql);

    return $this->_sendResponseGetTaggedImageCustomDataForm();
  }

  /**
   * Deletes the image's custom data title and text
   *
   * @return null|string
   */
  private function _sendResponseDeleteTaggedImageCustomData()
  {
    $input = new Input(Input::SOURCE_REQUEST);
    $id = $input->readInt('image');

    $sql = " UPDATE {$this->table_prefix}contentitem_tg_image "
      . " SET TGITitle = '', "
      . "     TGIText = '' "
      . " WHERE TGIID = $id "
      . "   AND FK_CIID = '{$this->page_id}' ";
    $this->db->query($sql);

    return $this->_sendResponseGetTaggedImageCustomDataForm();
  }
}

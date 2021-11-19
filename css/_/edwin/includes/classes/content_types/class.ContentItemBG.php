<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-07-05 07:36:38 +0200 (Fr, 05 Jul 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemBG extends AbstractContentItemGallery
{
  protected $_configPrefix = 'bg';
  protected $_contentPrefix = 'bg';
  protected $_columnPrefix = 'G';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 1,
  );
  protected $_contentImageTitles = false;
  protected $_columnImagePrefix = 'BI';
  protected $_templateSuffix = 'BG';

  /**
   * Returns an array containing custom data entered in the Upload area
   *
   * @return array
   */
  private function _getUploadZipCustomData() {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_bg_gallery_upload_zip') || !isset($_FILES['bg_gallery_upload_zip'])) {
          return array("title" => '', "subtitle" => '',"text" => '',);
    }

    $title = $post->readString('bg_gallery_upload_customdata_title', Input::FILTER_CONTENT_TITLE);
    $subtitle = $post->readString('bg_gallery_upload_customdata_subtitle', Input::FILTER_CONTENT_TITLE);
    $text = $post->readString('bg_gallery_upload_customdata_text', Input::FILTER_CONTENT_TEXT);

    return array(
      "title" => $title,
      "subtitle" => $subtitle,
      "text" => $text,
    );

  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Extract and process ZIP                                                               //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function _processZip()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_bg_gallery_uploaded_zip_process') || !$this->_zipFile) {
      return;
    }

    $prefix = $this->getConfigPrefix();
    $title = $post->readString('bg_gallery_upload_customdata_title', Input::FILTER_CONTENT_TITLE);
    $subtitle = $post->readString('bg_gallery_upload_customdata_subtitle', Input::FILTER_CONTENT_TITLE);
    $text = $post->readString('bg_gallery_upload_customdata_text', Input::FILTER_CONTENT_TEXT);

    $imagePositionStart = $post->readInt('bg_gallery_uploaded_zip_position');

    // lets open the zip
    $zip = new dUnzip2($this->_zipFile);
    $zip->debug = false;
    $filelist = $zip->getList();
    $message = $zip->getLastError();
    // if error opening zip file -> stop processing
    if ($message != "Loading list from 'End of Central Dir' index list..."){
      $this->setMessage(Message::createFailure($_LANG["bg_message_zipfile_error"] . "<!-- $message -->"));
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

    $sql = 'SELECT COUNT(BIID) '
         . "FROM {$this->table_prefix}contentitem_bg_image "
         . "WHERE FK_CIID = $this->page_id ";
    $existingImageCount = $this->db->GetOne($sql);

    foreach ($filelist as $filename => $fileinfo){
      $delete_tmp_image = 1;
      $cnt_images++;

      // skip invalid MacOSX files
      if (   strpos($filename, '__MACOSX') !== false
          || strpos($filename, '.DS_Store') !== false
      ) {
        continue;
      }

      $tmp_image_extension = mb_substr($filename,mb_strlen($filename)-3);
      $tmp_name = "files/temp/ci_tmp".time()."_".$this->site_id.$this->page_id."_$i.".$tmp_image_extension;

      // unzip actual file
      $zip->unzip($filename, $tmp_name);

      // get current file info
      $tmp_image_extension = pathinfo($tmp_name);
      $tmp_image_extension = mb_strtolower($tmp_image_extension["extension"]);
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

      $imageTypes = $this->getConfig('image_types');
      $imageSize = $this->getConfig('image_size');
      $maxImages = (int)$this->getConfig('max_images');
      // check file
      if (!in_array($tmp_image_extension,$imageTypes))
        $cnt_invalid_type_images++;
      else if ($imageSizeInvalid){
        $cnt_invalid_resolution_images++;
      }
      else if ($tmp_image_size > $imageSize)
          $cnt_invalid_size_images++;
      else{
        if (($existingImageCount + $cnt_valid_images) < $maxImages) {
          $rand = sprintf('%04d', rand(0, 9999));
          // save image and generate thumbnail; use custom file prefix
          if ($dest_name = $this->_storeImage($tmp_name, null, $prefix, $rand, null, null, true, $this->_configPrefix . '_gallery', false))
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
        else
          $cnt_too_much_valid_images++;

        $i++;
      }
      // remove invalid file
      if ($delete_tmp_image)
        unlinkIfExists($tmp_name);
    }

    // If the images should be inserted at the start we have to make room
    // for the new images (increase the position of all existing images).
    if ($imagePositionStart) {
      $sql = "UPDATE {$this->table_prefix}contentitem_bg_image "
           . "SET BIPosition = BIPosition + $cnt_valid_images "
           . "WHERE FK_CIID = $this->page_id "
           . 'ORDER BY BIPosition DESC ';
      $this->db->query($sql);
      $position = 1;
    } else {
      $position = $existingImageCount + 1;
    }
    // Insert images into the database.
    foreach ($saved_images as $image) {
      $sqlArgs = array(
        "{$this->_columnImagePrefix}Title"          => "'{$this->db->escape($title)}'",
        "{$this->_columnImagePrefix}ImageTitle"     => "'{$this->db->escape($subtitle)}'",
        "{$this->_columnImagePrefix}Text"           => "'{$this->db->escape($text)}'",
        "{$this->_columnImagePrefix}Image"          => "'$image'",
        "{$this->_columnImagePrefix}Position"       => "'$position'",
        "{$this->_columnImagePrefix}CreateDateTime" => "NOW()",
        "FK_CIID"                                   => "'$this->page_id'",
      );
      $sql = " INSERT INTO {$this->table_prefix}contentitem_bg_image "
           . " ( "  . implode(',', array_keys($sqlArgs)). " ) VALUES ( " . implode(',', $sqlArgs). " )";
      $this->db->query($sql);
      // Update the search index
      $this->_spiderElement($this->page_id, 'image', '', $subtitle);
      $position++;
    }

    // delete zip file
    $zip->close();
    unlinkIfExists($this->_zipFile);
    $result = $this->db->query("DELETE FROM ".$this->table_prefix."user_uploads WHERE FK_CIID=".$this->page_id);
    $this->_zipFile = '';
    $this->_zipFileName = '';

    // process message
    $message = sprintf($_LANG["bg_message_process_zip_success"],$cnt_valid_images,$cnt_images);
    $messageType = Message::TYPE_SUCCESS;
    // error detected? - extend message
    if ($cnt_invalid_type_images || $cnt_invalid_resolution_images || $cnt_invalid_size_images) {
      $message .= sprintf($_LANG["bg_message_process_zip_error"], $cnt_invalid_resolution_images, $cnt_invalid_type_images, $cnt_invalid_size_images);
      $messageType = Message::TYPE_FAILURE;
    }
    if ($cnt_too_much_valid_images) {
      $message .= sprintf($_LANG["bg_message_process_zip_error_too_much_images"], $cnt_too_much_valid_images, $maxImages);
      $messageType = Message::TYPE_FAILURE;
    }

    $this->setMessage(new Message($messageType, $message));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Insert Gallery Image                                                                  //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function _uploadImage()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_bg_gallery_upload_image') || !isset($_FILES['bg_gallery_upload_image'])) {
      return;
    }

    $title = $post->readString('bg_gallery_upload_customdata_title', Input::FILTER_CONTENT_TITLE);
    $subtitle = $post->readString('bg_gallery_upload_customdata_subtitle', Input::FILTER_CONTENT_TITLE);
    $text = $post->readString('bg_gallery_upload_customdata_text', Input::FILTER_CONTENT_TEXT);

    $image = $_FILES['bg_gallery_upload_image'];
    $imagePosition = $post->readInt('bg_gallery_upload_image_position');

    $prefix = array_unique(array($this->_configPrefix, $this->_contentPrefix));
    $imageTypes = $this->getConfig('image_types');
    $imageSize = $this->getConfig('image_size');
    // check file
    if ($image['size'] > 0){ // Image uploaded
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
      else{
        $rand = sprintf('%04d', rand(0, 9999));
        // save image and generate thumbnail; use custom file prefix
        if (! $dest_name = $this->_storeImage($image['tmp_name'], null, $prefix, $rand, null, null, true, $this->_configPrefix . '_gallery')) {
          return;
        }

        // Determine the position of the new image.
        $sql = 'SELECT COUNT(BIID) '
             . "FROM {$this->table_prefix}contentitem_bg_image "
             . "WHERE FK_CIID = $this->page_id ";
        $existingImageCount = $this->db->GetOne($sql);
        if ($imagePosition < 1) {
          $imagePosition = 1;
        } else if ($imagePosition > $existingImageCount + 1) {
          $imagePosition = $existingImageCount + 1;
        }
        // Make room for the new image (increase the position of existing
        // images after the insertion point).
        if ($imagePosition <= $existingImageCount) {
          $sql = "UPDATE {$this->table_prefix}contentitem_bg_image "
               . 'SET BIPosition = BIPosition + 1 '
               . "WHERE FK_CIID = $this->page_id "
               . "AND BIPosition >= $imagePosition "
               . 'ORDER BY BIPosition DESC ';
          $this->db->query($sql);
        }
        // Insert the image into the database.
        $sql = "INSERT INTO {$this->table_prefix}contentitem_bg_image "
             . '(BITitle, BIText, BIImage, BIPosition, BIImageTitle, BICreateDateTime, FK_CIID) '
             . "VALUES('{$this->db->escape($title)}', '{$this->db->escape($text)}', "
             . "       '$dest_name', $imagePosition, "
             . "       '{$this->db->escape($subtitle)}', NOW(), $this->page_id) ";
        $this->db->query($sql);

        // Update the search index
        $this->_spiderElement($this->page_id, 'image', '', $subtitle);

        $this->setMessage(Message::createSuccess(sprintf($_LANG['bg_message_insert_image_success'], $imagePosition)));
      }
    }
  }

  /**
   * Updates the custom data of a gallery image if the POST parameter process_bg_gallery_image_customdata_save is set.
   */
  private function _updateGalleryImageCustomData()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_bg_gallery_image_customdata_save');
    if (!$ID) {
      return;
    }

    $sql = ' SELECT BIImageTitle '
         . " FROM {$this->table_prefix}contentitem_bg_image "
         . " WHERE BIID = $ID ";
    $oldSubTitle = $this->db->GetOne($sql);

    $title = $post->readString("bg_gallery_image{$ID}_customdata_title", Input::FILTER_CONTENT_TITLE);
    $subtitle = $post->readString("bg_gallery_image{$ID}_customdata_subtitle", Input::FILTER_CONTENT_TITLE);
    $text = $post->readString("bg_gallery_image{$ID}_customdata_text", Input::FILTER_CONTENT_TEXT);

    $sql = "UPDATE {$this->table_prefix}contentitem_bg_image "
         . "SET BITitle = '{$this->db->escape($title)}', "
         . "    BIText = '{$this->db->escape($text)}', "
         . "    BIImageTitle = '{$this->db->escape($subtitle)}' "
         . "WHERE BIID = $ID ";
    $result = $this->db->query($sql);

    if ($result) {
      $this->_spiderElement($this->page_id, 'image', $oldSubTitle, $subtitle);
    }

    $this->setMessage(Message::createSuccess($_LANG['bg_message_gallery_image_customdata_update_success']));
  }

  /**
   * Deletes gallery image's customdata.
   *
   * @param int $ID
   *        The ID of the gallery image.
   */
  private function _deleteGalleryImageCustomDataID($ID)
  {
    global $_LANG;

    $sql = ' SELECT BIImageTitle '
         . " FROM {$this->table_prefix}contentitem_bg_image "
         . " WHERE BIID = $ID ";
    $subtitle = $this->db->GetOne($sql);

    $sql = "UPDATE {$this->table_prefix}contentitem_bg_image "
         . "SET BITitle = '', "
         . "    BIText = '', "
         . "    BIImageTitle = '' "
         . "WHERE BIID = $ID ";
    $result = $this->db->query($sql);

    if ($result) {
      $this->_spiderElement($this->page_id, 'image', $subtitle);
    }

    $this->setMessage(Message::createSuccess($_LANG['bg_message_gallery_image_customdata_delete_success']));
  }

  /**
   * Deletes the custom data of a gallery image if the GET parameter
   * deleteGalleryImageCustomdataID is set.
   */
  private function _deleteGalleryImageCustomData()
  {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $ID = $post->readKey('process_bg_gallery_image_customdata_delete');
      if (!$ID) {
        return;
      }

      $this->_deleteGalleryImageCustomDataID($ID);

      $this->setMessage(Message::createSuccess($_LANG['bg_message_gallery_image_customdata_delete_success']));
  }

  private function _getLastEditedImageID()
  {
    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_bg_gallery_image_customdata_edited');
    return $ID;

  }

  protected function _deleteGalleryImageID($ID)
  {
    // Retrieve the image subtitle, before deleting the image.
    $sql = " SELECT BIImageTitle "
         . " FROM {$this->table_prefix}contentitem_bg_image "
         . " WHERE BIID = $ID ";
    $subtitle = $this->db->GetOne($sql);

    $deleted = parent::_deleteGalleryImageID($ID);

    if (!$deleted) {
      return false;
    }

    // The image has been successfully deleted, so remove its title from
    // the search index.
    $this->_spiderElement($this->page_id, 'image', $subtitle);

    return true;
  }

  protected function _processedValues()
  {
    return array_merge(parent::_processedValues(), array(
      'deleteGalleryImageID',
      'moveGalleryImageID',
      'process_bg_gallery_image_customdata_delete',
      'process_bg_gallery_image_customdata_save',
      'process_bg_gallery_images_delete',
      'process_bg_gallery_upload_image',
      'process_bg_gallery_upload_zip',
      'process_bg_gallery_uploaded_zip_process',
    ));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // Query all gallery images from the database.
    $sql = 'SELECT BIImage '
         . "FROM {$this->table_prefix}contentitem_bg_image "
         . "WHERE FK_CIID = $this->page_id ";
    $images = $this->db->getCol($sql);

    // Delete all gallery images.
    self::_deleteImageFiles($images);

    // Delete all gallery image datasets.
    $sql = "DELETE FROM {$this->table_prefix}contentitem_bg_image "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);

    // Call the default delete content method.
    return parent::delete_content();
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    // Duplicate content item
    $parentId = parent::duplicateContent($pageId, $newParentId, $parentField, $id, $idField);

    // Duplicate images
    $sql = ' SELECT BITitle, BIText, BIImage, BIImageTitle, BIPosition '
         . " FROM {$this->table_prefix}contentitem_bg_image "
         . " WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    $now = date('Y-m-d H:i:s');
    while ($row = $this->db->fetch_row($result)) {
      $image = CopyHelper::createImage($row['BIImage'], $pageId, $this->site_id);
      $sql = " INSERT INTO {$this->table_prefix}contentitem_bg_image "
           . " (BITitle, BIText, BIImage, BIImageTitle, BIPosition, BICreateDateTime, FK_CIID) "
           . " VALUES ('{$this->db->escape($row['BITitle'])}', '{$this->db->escape($row['BIText'])}', "
           . "         '{$this->db->escape($image)}', '{$this->db->escape($row['BIImageTitle'])}', "
           . "         '{$row['BIPosition']}', '{$now}', {$pageId})";
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
    $this->_updateGalleryImageCustomData();
    $this->_deleteGalleryImageCustomData();

    if ($this->isProcessed()) {
      parent::edit_content();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $this->_readUploadedZip();
    $uploadZipCustomData = $this->_getUploadZipCustomData();
    $galleryImages = array();
    $sql = 'SELECT BIID, BITitle, BIText, BIImage, BIImageTitle,BIPosition '
         . "FROM {$this->table_prefix}contentitem_bg_image "
         . "WHERE FK_CIID = $this->page_id "
         . 'ORDER BY BIPosition ';
    $result = $this->db->query($sql);
    $count = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $galleryImages[] = array(
        'bg_gallery_image_src' => $this->get_thumb_image($row['BIImage']),
        'bg_gallery_image_id' => $row['BIID'],
        'bg_gallery_image_position' => $row['BIPosition'],
        'bg_gallery_image_delete_link' => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;deleteGalleryImageID={$row['BIID']}&amp;scrollToAnchor=a_gallery_images&amp;areaLastEdited=bg_gallery_images_area",
        'bg_gallery_image_customdata' => (int)($row['BITitle'] || $row['BIText'] || $row['BIImageTitle']),
        'bg_gallery_image_customdata_subtitle' => $row['BIImageTitle'],
        'bg_gallery_image_customdata_title' => parseOutput($row['BITitle'], 2),
        'bg_gallery_image_customdata_text' => parseOutput($row['BIText'], 1),
        'bg_gallery_image_customdata_available_label' => (int)($row['BITitle'] || $row['BIText']) ?
          $_LANG['bg_gallery_image_customdata_available_label'] : $_LANG['bg_gallery_image_customdata_not_available_label'],
      );
    }
    $this->db->free_result($result);

    $imagePositionSelect = '<select name="bg_gallery_upload_image_position" class="form-control">';
    for ($i = 1; $i <= $count + 1; $i++) {
      $imagePositionSelect .= "<option value=\"$i\">$i</option>";
    }
    $imagePositionSelect .= '</select>';

    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="content" />'
                  . '<input type="hidden" name="action2" value="" />'
                  . '<input type="hidden" id="scrollToAnchor" name="scrollToAnchor" value="" />'
                  . '<input type="hidden" id="areaLastEdited" name="areaLastEdited" value="" />';

    $scrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';
    $areaLastEdited = isset($_REQUEST['areaLastEdited']) ? $_REQUEST['areaLastEdited'] : '';
    $maximumReached = $count >= $this->getConfig('max_images');

    // determine the area to open after page reload
    $post = new Input(Input::SOURCE_POST);
    $zipUploaded = $post->exists('process_bg_gallery_upload_zip') ? true : false;
    $zipProcessed = $post->exists('process_bg_gallery_uploaded_zip_process') ? true : false;
    $imageUploaded = $post->exists('process_bg_gallery_upload_image') ? true : false;
    // open images area if images are inside and no zip file or images have been uploaded / or a zip file has been processed
    if ((!$areaLastEdited && !empty($galleryImages) && !$imageUploaded && !$zipUploaded) || (!empty($galleryImages) && $zipProcessed)) {
      $areaLastEdited = 'bg_gallery_images_area';
    } else if (!$areaLastEdited) {
      $areaLastEdited = 'bg_gallery_uploads_area';
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    // If $scrollToAnchor is set we display the current message a second time for the gallery.
    $this->tpl->parse_if($tplName, 'gallery_message', $this->_getMessage() && $scrollToAnchor, $this->_getMessageTemplateArray('bg_gallery'));
    $this->tpl->parse_if($tplName, 'gallery_upload_zip_available', !$this->_zipFile);
    $this->tpl->parse_if($tplName, 'gallery_uploaded_zip', $this->_zipFile, array( 'bg_gallery_uploaded_zip_name' => $this->_zipFileName ));
    $this->tpl->parse_if($tplName, 'gallery_maximum_not_reached', !$maximumReached, array( 'bg_gallery_upload_image_position_select' => $imagePositionSelect ));
    $this->tpl->parse_if($tplName, 'gallery_maximum_reached', $maximumReached);
    $this->tpl->parse_if($tplName, 'gallery_image_message', $this->_getMessage() && $this->_getLastEditedImageID(), $this->_getMessageTemplateArray('bg_gallery'));
    $this->tpl->parse_if($tplName, 'gallery_images_uploaded', $galleryImages);
    $this->tpl->parse_loop($tplName, $galleryImages, 'gallery_images');
    $content = $this->tpl->parsereturn($tplName, array(
      'bg_hidden_fields' => $hiddenFields,
      'bg_gallery_image_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveGalleryImageID=#moveID#&moveGalleryImageTo=#moveTo#&scrollToAnchor=a_gallery_images&areaLastEdited=bg_gallery_images_area",
      'bg_scroll_to_anchor' => $scrollToAnchor,
      'bg_gallery_image_last_edited' => $this->_getLastEditedImageID(),
      'bg_gallery_image_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
      'bg_area_last_edited' => $areaLastEdited,
      'bg_upload_area_customdata_field_title' => $uploadZipCustomData['title'],
      'bg_upload_area_customdata_field_subtitle' => $uploadZipCustomData['subtitle'],
      'bg_upload_area_customdata_field_text' => $uploadZipCustomData['text'],
      'bg_image_upload_active_tab' => !$zipUploaded ? 'active' : 'inactive',
      'bg_zip_upload_active_tab' => $zipUploaded ? 'active' : 'inactive',
      'bg_image_upload_area_active' => !$zipUploaded ? 'active' : 'inactive',
      'bg_zip_upload_area_active' => $zipUploaded ? 'active' : 'inactive',
      'bg_max_file_size' => $this->getConfig('file_size'),
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
      $sql = " SELECT BIImageTitle "
           . " FROM {$this->table_prefix}contentitem_bg_image "
           . " WHERE FK_CIID = $this->page_id "
           . " ORDER BY BIPosition ASC ";
      $tmpTitles = $this->db->GetCol($sql);

      $titles = array_merge($titles, $tmpTitles);
    }

    return $titles;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview()
  {
    $post = new Input(Input::SOURCE_POST);

    $galleryItems = array();
    $galleryImageLarge = '';
    $galleryImageSource = '';
    $title = '';
    $text1 = '';
    $images = $this->_createPreviewImages(array(
      'GImage' => 'bg_image', // Box image
    ));
    $imageSrc1 = $images['bg_image'];
    $imageSrcLarge1 = $this->_hasLargeImage($imageSrc1);
    $sql = 'SELECT BITitle, BIText, BIImage, BIImageTitle, BIPosition '
         . "FROM {$this->table_prefix}contentitem_bg_image "
         . "WHERE FK_CIID = $this->page_id "
         . 'ORDER BY BIPosition ';
    $result = $this->db->query($sql);
    $galleryImagesCount = $this->db->num_rows($result);
    while ($row = $this->db->fetch_row($result)) {
      $position = (int)$row['BIPosition'];

      if ($position > (int)$this->getConfig('galleryimages_per_page')) {
        break;
      }
      $imageSubtitle = $row['BIImageTitle'];
      $imageText = $row['BIText'];

      $thumbSource = $this->get_thumb_image($row['BIImage']);
      $galleryItems[] = array(
        'c_bg_image_src' => $thumbSource,
        'c_bg_image_real_src' => $row['BIImage'],
        'c_bg_image_link' => '#',
        'c_bg_sid' => $this->site_id - 1,
        'c_bg_image_id' => $position,
        'c_bg_active' => $position == 1 ? 'active' : '',
        'c_bg_image_subtitle' => $imageSubtitle,
        'c_bg_image_title' => parseOutput($row['BITitle']),
        'c_bg_image_text' => parseOutput($imageText, 1),
      );

      if ($position == 1) {
        $galleryImageSource = '../' . $row['BIImage'];
        $galleryImageLarge = $this->_hasLargeImage($galleryImageSource);

        // Title and text of the gallery image overwrite the default title and text.
        if ($row['BITitle']) {
          $title = $row['BITitle'];
        }
        if ($row['BIText']) {
          $text1 = $row['BIText'];
        }
      }
    }
    $this->db->free_result($result);

    $this->tpl->set_tpl_dir('../templates');
    $this->tpl->load_tpl('content_site_bg', $this->_getTemplatePath());
    $this->tpl->parse_if('content_site_bg', 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('bg')
    ));

    $this->tpl->parse_if('content_site_bg', 'gallery_zoom', $galleryImageLarge, array(
      'c_bg_gallery_zoom_link' => '#',
    ));

    /* do not show links for switching images */
    $this->tpl->parse_if('content_site_bg', 'gallery_first_link_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_first_link_not_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_previous_link_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_previous_link_not_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_next_link_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_next_link_not_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_last_link_available', false);
    $this->tpl->parse_if('content_site_bg', 'gallery_last_link_not_available', false);
    $this->tpl->parse_if('content_site_bg', 'zoom1', $imageSrcLarge1, array( 'c_bg_zoom1_link' => $imageSrcLarge1 ));
    $this->tpl->parse_if('content_site_bg', 'image1', $imageSrc1, array( 'c_bg_image_src1' => $imageSrc1 ));
    $this->tpl->parse_if('content_site_bg', 'more_pages', false);
    $this->tpl->parse_loop('content_site_bg', $galleryItems, 'gallery_items');

    $postTitle1 = $post->readString('bg_title1', Input::FILTER_CONTENT_TITLE);
    $postText1 = $post->readString('bg_text1', Input::FILTER_CONTENT_TEXT);
    $this->tpl->parse_vars('content_site_bg', array_merge(array(
      'c_bg_title1' => parseOutput($postTitle1, 2),
      'c_bg_title2' => parseOutput($post->readString('bg_title2', Input::FILTER_CONTENT_TITLE), 2),
      'c_bg_title3' => parseOutput($post->readString('bg_title3', Input::FILTER_CONTENT_TITLE), 2),
      'c_bg_text1' => parseOutput($postText1, 1),
      'c_bg_text2' => parseOutput($post->readString('bg_text2', Input::FILTER_CONTENT_TEXT), 1),
      'c_bg_text3' => parseOutput($post->readString('bg_text3', Input::FILTER_CONTENT_TEXT), 1),
      'c_bg_title_variation' => ($title) ? parseOutput($title, 2) : parseOutput($postTitle1, 2),
      'c_bg_title_variation_plain' => ($title) ? strip_tags($title) : parseOutput($postTitle1, 2),
      'c_bg_text1_variation' => ($text1) ? parseOutput($text1, 1) : parseOutput($postText1, 1),
      'c_bg_text1_variation_plain' => ($text1) ? strip_tags($text1) : parseOutput($postText1, 1),
      'c_bg_gallery_image_src' => $galleryImageSource,
      'c_surl' => '../',
    )));
    $content = $this->tpl->parsereturn('content_site_bg', $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');
    return $content;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,GTitle1,GTitle2,GTitle3,GText1,GText2,GText3 FROM ".$this->table_prefix."contentitem_bg cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["GTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["GTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["GTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["GText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["GText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["GText3"];
      $class_content[$row["CIID"]]["c_image_title1"] = "";
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }
}

<?php

/**
 * Abstract Content Class
 *
 * $LastChangedDate: 2014-03-12 11:10:07 +0100 (Mi, 12 Mrz 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
abstract class AbstractContentItemGallery extends ContentItem
{
  /**
   * Stores the gallery image table's column prefix.
   *
   * @var string
   */
  protected $_columnImagePrefix = '';
  /**
   * Stores the file name of an uploaded zip file.
   *
   * @var string
   */
  protected $_zipFile = '';

  /**
   * Stores the name (title) of an uploaded zip file.
   *
   * @var string
   */
  protected $_zipFileName = '';

  /**
   * Deletes a gallery image if the GET parameter deleteGalleryImageID is set.
   */
  protected function _deleteGalleryImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteGalleryImageID');

    if (!$ID) {
      return;
    }

    $this->_deleteGalleryImageID($ID);

    $this->setMessage(Message::createSuccess($_LANG["{$this->_contentPrefix}_message_gallery_image_delete_success"]));
  }

  /**
   * Deletes a single gallery image with the specified ID.
   *
   * @param int $ID
   *        The ID of the gallery image.
   *
   * @return bool
   *         true on success, false otherwise
   */
  protected function _deleteGalleryImageID($ID)
  {
    $colPrefix = $this->_columnImagePrefix;
    $prefix = $this->_contentPrefix;

    // Determine the position of the deleted gallery image.
    $sql = " SELECT {$colPrefix}Position, {$colPrefix}Image "
         . " FROM {$this->table_prefix}contentitem_{$prefix}_image "
         . " WHERE {$colPrefix}ID = $ID ";
    $row = $this->db->GetRow($sql);

    if (!$row) {
      return false;
    }

    $deletedPosition = $row["{$colPrefix}Position"];
    $image = $row["{$colPrefix}Image"];

    // Delete the gallery image.
    $sql = " DELETE FROM {$this->table_prefix}contentitem_{$prefix}_image "
         . " WHERE {$colPrefix}ID = $ID ";
    $result = $this->db->query($sql);

    // Move the following gallery images one position up.
    $sql = " UPDATE {$this->table_prefix}contentitem_{$prefix}_image "
         . " SET {$colPrefix}Position = {$colPrefix}Position - 1 "
         . " WHERE FK_CIID = $this->page_id "
         . " AND {$colPrefix}Position > $deletedPosition "
         . " ORDER BY {$colPrefix}Position ASC ";
    $result = $this->db->query($sql);

    // Delete the image files.
    self::_deleteImageFiles($image);

    return true;
  }

  /**
   * Deletes one ore more gallery images if the POST parameters
   * process_{prefix}_gallery_images_delete and {prefix}_gallery_image are set.
   */
  protected function _deleteGalleryImages()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $prefix = $this->_contentPrefix;

    if (!$post->exists("process_{$prefix}_gallery_images_delete") || !$post->exists("{$prefix}_gallery_image")) {
      return;
    }

    $IDs = $post->readArrayIntToInt("{$prefix}_gallery_image");

    foreach ($IDs as $ID) {
      $this->_deleteGalleryImageID($ID);
    }

    if (count($IDs) == 1) {
      $message = $_LANG["{$prefix}_message_gallery_images_delete_success_one"];
    }
    else {
      $message = sprintf($_LANG["{$prefix}_message_gallery_images_delete_success_more"], count($IDs));
    }

    $this->setMessage(Message::createSuccess($message));
  }


  /**
   * Moves a gallery image if the GET parameters moveGalleryImageID and
   * moveGalleryImageTo are set.
   */
  protected function _moveGalleryImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveGalleryImageID');
    $moveTo = $get->readInt('moveGalleryImageTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_{$this->_contentPrefix}_image",
                                         "{$this->_columnImagePrefix}ID", "{$this->_columnImagePrefix}Position",
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG["{$this->_contentPrefix}_message_gallery_image_move_success"]));
    }
  }

  /**
   * Reads an existing uploaded zip file into $this->_zipFile (and
   * $this->_zipFileName).
   */
  protected function _readUploadedZip()
  {
    $sql = ' SELECT UUFile, UUName '
         . " FROM {$this->table_prefix}user_uploads "
         . ' WHERE UUType = 1 '
         . " AND FK_UID = {$this->_user->getID()} "
         . " AND FK_CIID = $this->page_id ";
    $row = $this->db->GetRow($sql);
    if ($row) {
      $this->_zipFile = $row['UUFile'];
      $this->_zipFileName = $row['UUName'];
    }
  }

  /**
   * Store a gallery image.
   *
   * @deprecated Not used anymore. Use core function ContentBase::_storeImage
   *
   * @param string $tmpName
   *        The temporary image filename of the uploaded image.
   * @param string $destName
   *        The new image filename
   * @param string $tmpImageExtension
   *        The image extension
   * @param bool $setMessage
   *        Set an message in case of invalid image size.
   */
  protected function _storeGalleryImage($tmpName, $destName, $tmpImageExtension, $setMessage = true)
  {
    $originalImage = CmsImageFactory::create($tmpName);
    $large = $this->_storeImageGetSize($originalImage, $this->getConfigPrefix(), 0, $setMessage) == ContentBase::IMAGESIZE_LARGE;
    // Retrieve the size of the normal image.
    $configWidth = $this->getConfig('image_width');
    $configHeight = $this->getConfig('image_height');
    $normalSize = $this->_readMutableSize($originalImage, $configWidth, $configHeight);

    $image = '';
    // large image acitvated & large image uploaded -> create normal image
    if ($large)
    {
      rename($tmpName,"../".$destName."-l.".$tmpImageExtension);
      chmod("../".$destName."-l.".$tmpImageExtension, 0644);

      $srcW = $normalSize[2] ? $normalSize[2] : $originalImage->getWidth();
      $srcH = $normalSize[3] ? $normalSize[3] : $originalImage->getHeight();

      $output_normalimage = imagecreatetruecolor($normalSize[0],$normalSize[1]);
      switch($tmpImageExtension){
        case "jpg": $image = imagecreatefromjpeg("../".$destName."-l.".$tmpImageExtension);
                    imagecopyresampled($output_normalimage,$image,0,0,0,0,$normalSize[0],$normalSize[1],$srcW,$srcH);
                    imagejpeg($output_normalimage,"../".$destName.".".$tmpImageExtension);
                    break;
        case "png": $image = imagecreatefrompng("../".$destName."-l.".$tmpImageExtension);
                    imagealphablending($output_normalimage, false); // turn off the alpha blending to keep the alpha channel
                    imagecopyresampled($output_normalimage,$image,0,0,0,0,$normalSize[0],$normalSize[1],$srcW,$srcH);
                    imagesavealpha($output_normalimage, true); // save full alpha channel information (as opposed to single-color transparency)
                    imagepng($output_normalimage,"../".$destName.".".$tmpImageExtension);
                    break;
      }
    }
    else {
      rename($tmpName,"../".$destName.".".$tmpImageExtension);
      chmod("../".$destName.".".$tmpImageExtension, 0644);
    }

    // If there has been a large image uploaded, use the large image size and
    // source for thumbnail creation. Manually set the size parameters
    // (0) width, (1) height, (2) selection width, (3) selection height
    if ($large) {
      $sourceSize[0] = $originalImage->getWidth();
      $sourceSize[1] = $originalImage->getHeight();
      $sourceSize[2] = 0;
      $sourceSize[3] = 0;
    }
    else {
      $sourceSize = $normalSize;
    }

    // Determine the thumbnail width and height.
    $thumbnailWidth = $this->getConfig('th_image_width');
    $thumbnailHeight = $this->getConfig('th_image_height');
    $thumbnailSelWidth = $this->getConfig('th_selection_width');
    $thumbnailSelHeight = $this->getConfig('th_selection_height');

    if (!$thumbnailSelWidth || !$thumbnailSelHeight)
    {
      $thumbnailSize = $this->_readMutableSize($originalImage, $thumbnailWidth, $thumbnailHeight);
    }
    // Manually set the size parameters (0) width, (1) height, (2) selection width,
    // (3) selection height
    else {
      $thumbnailSize[0] = $thumbnailWidth;
      $thumbnailSize[1] = $thumbnailHeight;
      $thumbnailSize[2] = ($thumbnailSelWidth > $sourceSize[0]) ? $sourceSize[0] : $thumbnailSelWidth;
      $thumbnailSize[3] = ($thumbnailSelHeight > $sourceSize[1]) ? $sourceSize[1] : $thumbnailSelHeight;
    }

    // Special handling for fixed thumbnail width and height. Set the selection
    // width and height parameters to normal image width and height, as the
    // source image is scaled without cropping any areas.
    // Thumbnail width is fixed, calculate its height.
    $thumbnailFixedWidth = $this->getConfig('th_image_width_fixed');
    $thumbnailFixedHeight = $this->getConfig('th_image_height_fixed');
    if ($thumbnailFixedWidth)
    {
      $thumbnailSize[0] = $thumbnailFixedWidth;
      $ratio = $sourceSize[0] / $thumbnailSize[0];
      $thumbnailSize[1] = (int)($sourceSize[1] / $ratio);
    }
    // Thumbnail height is fixed, calculate its width.
    else if ($thumbnailFixedHeight)
    {
      $thumbnailSize[1] = $thumbnailFixedHeight;
      $ratio = $sourceSize[1] / $thumbnailSize[1];
      $thumbnailSize[0] = (int)($sourceSize[0] / $ratio);
    }

    // Ensure the thumbnail selection size is set.
    $thumbnailSize[2] = $thumbnailSize[2] ? $thumbnailSize[2] : $sourceSize[0]; // selection width = image width
    $thumbnailSize[3] = $thumbnailSize[3] ? $thumbnailSize[3] : $sourceSize[1]; // selection height = image height

    $startx = intval(($sourceSize[0] - $thumbnailSize[2]) / 2);
    $starty = intval(($sourceSize[1] - $thumbnailSize[3]) / 2);

    // create thumbnail
    $upload_image = $image;

    $image = imagecreatetruecolor($thumbnailSize[2],$thumbnailSize[3]);

    imagealphablending($image, false);
    switch($tmpImageExtension)
    {
      case "jpg": imagecopy($image,(is_file("../".$destName."-l.".$tmpImageExtension) ? $upload_image : imagecreatefromjpeg("../".$destName.".".$tmpImageExtension)),0,0,$startx,$starty,$thumbnailSize[2],$thumbnailSize[3]);
                  break;
      case "png": imagecopy($image,(is_file("../".$destName."-l.".$tmpImageExtension) ? $upload_image : imagecreatefrompng("../".$destName.".".$tmpImageExtension)),0,0,$startx,$starty,$thumbnailSize[2],$thumbnailSize[3]);
                  break;
    }

    $output_thumbnail = imagecreatetruecolor($thumbnailSize[0],$thumbnailSize[1]);
    imagealphablending($output_thumbnail, false);
    imagecopyresampled($output_thumbnail,$image,0,0,0,0,$thumbnailSize[0],$thumbnailSize[1],$thumbnailSize[2],$thumbnailSize[3]);
    switch($tmpImageExtension)
    {
      case "jpg": imagejpeg($output_thumbnail,"../".$destName."-th.".$tmpImageExtension);
                  break;
      case "png": imagesavealpha($output_thumbnail, true); // save full alpha channel information (as opposed to single-color transparency)
                  imagepng($output_thumbnail,"../".$destName."-th.".$tmpImageExtension);
                  break;
    }
  }

  /**
   * Upload a zip file.
   */
  protected function _uploadZip()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists("process_{$this->_contentPrefix}_gallery_upload_zip") ||
        !isset($_FILES["{$this->_contentPrefix}_gallery_upload_zip"]))
    {
      return;
    }

    // If there is already an uploaded zip file we ignore the new upload.
    if ($this->_zipFile) {
      return;
    }

    $timestamp = time();
    $zipFileName = "temp/tmp_{$timestamp}_{$this->_user->getID()}";
    $zipFile = $this->_storeFile($_FILES[$this->_contentPrefix . '_gallery_upload_zip'],
                                 $this->getConfig('file_size'),
                                 array('zip'), '', "$zipFileName.zip", false);

    if (!$zipFile) {
      return;
    }

    $sql = " INSERT INTO {$this->table_prefix}user_uploads "
         . ' (FK_UID, FK_CIID, UUFile, UUName, UUTime, UUType) '
         . ' VALUES '
         . " ({$this->_user->getID()}, $this->page_id, '$zipFile', '$zipFileName', $timestamp, 1) ";
    $result = $this->db->query($sql);

    $this->_zipFile = $zipFile;
    $this->_zipFileName = $zipFileName;

    $this->setMessage(Message::createSuccess($_LANG["{$this->_contentPrefix}_message_fileupload_success"]));
  }

}
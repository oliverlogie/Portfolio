<?php

/**
 * The base class for ContentItem and Module, containing common methods.
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
abstract class ContentBase
{
  /**
   * The image has a non-standard size.

   *
   * @var int
   */
  const IMAGESIZE_INVALID = 0;
  /**
   * The image has a standard normal size.
   *
   * @var int
   */
  const IMAGESIZE_NORMAL = 1;
  /**
   * The image has a standard large size.
   *
   * @var int
   */
  const IMAGESIZE_LARGE = 2;

  /**
   * Describes the 'enabled' state of a content item / siteindex / or subelement
   *
   * @var string
   */
  const ACTIVATION_ENABLED = 'enabled';

  /**
   * Describes the 'disabled' state of a content item / siteindex / or subelement
   *
   * @var string
   */
  const ACTIVATION_DISABLED = 'disabled';

  /**
   * An instance of the config helper class
   *
   * @var ConfigHelper
   */
  protected $_configHelper;

  /**
   * An instance of the database class.
   *
   * @var db
   */
  protected $db;

  /**
   * Navigation object
   *
   * @var Navigation
   */
  protected $_navigation;

  /**
   * The table prefix for accessing the database.
   *
   * @var string
   */
  protected $table_prefix;
  /**
   * An instance of the template class.
   *
   * @var Template
   */
  protected $tpl;
  /**
   * An instance of the user class.
   *
   * @var User
   */
  protected $_user;
  /**
   * An instance of the Session class.
   *
   * @var Session
   */
  protected $session;
  /**
   * Contains the status message of the page.
   *
   * @var Message
   */
  private $message;

  /**
   * The image to display, if there is not a content image available
   *
   * @var string
   */
  protected $_noContentImage;

  /**
   * Initializes the ContentBase members.
   *
   * @param db $db
   *        An instance of the database class.
   * @param string $table_prefix
   *        The table prefix for accessing the database.
   * @param Template $tpl
   *        An instance of the template class.
   * @param User $user
   *        An instance of the user class.
   * @param Session $session
   *        An instance of the Session class.
   */
  protected function __construct(db $db, $table_prefix, Template $tpl,
                                 User $user, Session $session,
                                 Navigation $navigation)
  {
    $this->db = $db;
    $this->table_prefix = $table_prefix;
    $this->tpl = $tpl;
    $this->_user = $user;
    $this->_navigation = $navigation;
    if ($session) {
      $this->session = $session;
    } else {
      $this->session = new Session(ConfigHelper::get('m_session_name_backend'));
    }
    $this->_configHelper = new ConfigHelper($this->db, $this->table_prefix, $this->_navigation);
    $this->_noContentImage = 'img/no_image.png';

    // If a message object was stored within our session (usually before page redirects)
    // setup the actual message with it.
    if ($this->session->read('session_message') instanceof Message) {
      $this->setMessage($this->session->read('session_message'));
    }
    $this->session->reset('session_message');
  }

  /**
   * Stores an image.
   *
   * @param array|string $sourceImage
   *        Either the uploaded image from the $_FILES array or a string with
   *        the path to an existing image file.
   * @param string|null $existingImageName
   *        The file name of an existing image (used to guarantee a new name).
   *        Can be null (or anything equal to false) if there is no existing file.
   * @param string|array $prefix
   *        The prefix of the content (used to get the correct configration
   *        variables and also for the file name). Can also be an array of
   *        prefixes for the priority of the configuration variables (but the
   *        first prefix is always used for the file name).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables and also for the file name).
   *        If it is 0 then it is ignored (use when only one image on a page).
   * @param array|string|null $components
   *        Non-Standard file name component(s) (for easy identification in
   *        the file system) or null if the standard components (usually
   *        site ID and page ID) should be used.
   * @param bool|null $createBoxImage
   *        Controls the creation of a box image. If the default value null is
   *        passed it is determined automatically (true if $imageNumber is 1).
   * @param bool $createThumbnail
   *        Controls the creation of a thumbnail image.
   * @param string $destinationPrefix
   *        Destination prefix can be used for the file name. If no destination prefix
   *        is available, $prefix will be used.
   * @param boolean $setMessage
   *        On default messages are set, if set to false this function will not set messages.
   * @param boolean $createBlogImage (optional, default true)
   *        If set to false, the blog image will not be generated.
   *
   * @return string
   *        The file name of the saved image file or null if nothing was saved
   *        because of an error (details in $this->message).
   */
  protected function _storeImage($sourceImage, $existingImageName,
                                 $prefix, $imageNumber = 0, $components = null,
                                 $createBoxImage = null,
                                 $createThumbnailImage = false, $destinationPrefix='', $setMessage=true,
                                 $createBlogImage = true)
  {
    global $_LANG;

    // Initialize the image variables with null.
    $normalImage = null;
    $largeImage = null;
    $boxImage = null;
    $boxImage2 = null;
    $thumbnailImage = null;
    $blogImage = null;

    if (!$sourceImage) {
      return null;
    }

    // Get the path to the source image and return null if we don't have one.
    $sourceImagePath = $this->_storeImageGetSourcePath($sourceImage);
    if (!$sourceImagePath) {
      return null;
    }

    // Load the source image.
    try {
      $originalImage = CmsImageFactory::create($sourceImagePath);
    }
    catch(Exception $e) {
      if ($setMessage)
        $this->setMessage(Message::createFailure($e->getMessage()));
      return null;
    }

    $ignoreImageSize = $this->_configHelper->readImageConfiguration($prefix, 'ignore_image_size', $imageNumber);

    // We have some special handling for images with invalid dimensions
    $image = null;
    if ($this->_storeImageGetSize($originalImage, $prefix, $imageNumber, false) == self::IMAGESIZE_INVALID) {
      if ($ignoreImageSize) {
        $image = $this->_getResizedImageFromIgnoredImageSize($originalImage, $prefix, $imageNumber);
      }
      else if ($this->_configHelper->readImageConfiguration($prefix, 'autofit_image_upload', $imageNumber)) {
        $image = $this->_getResizedImageAutoFit($originalImage, $prefix, $imageNumber);
      }
    }

    $image = $image ?: $originalImage;

    // Look if the image has a valid size.
    $originalImageSize = $this->_storeImageGetSize($image, $prefix, $imageNumber, ($ignoreImageSize ? false : $setMessage));
    switch ($originalImageSize) {
      case self::IMAGESIZE_LARGE:
        $largeImage = $image;
        break;
      case self::IMAGESIZE_NORMAL:
        $normalImage = $image;
        break;
      case self::IMAGESIZE_INVALID:
      default:
        if (!$ignoreImageSize)
          return null;
        else {
          $largeImage = $image;
        }
    }

    // Look if the image has a valid type and determine the extension.
    $originalImageType = $originalImage->getType();
    switch ($originalImageType) {
      case IMAGETYPE_GIF:
        $newImageExtension = '.gif';
        break;
      case IMAGETYPE_JPEG:
        $newImageExtension = '.jpg';
        break;
      case IMAGETYPE_PNG:
        $newImageExtension = '.png';
        break;
      default:
        if ($setMessage)
          $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
        return null;
    }

    // Determine the new filename for the image.
    // If there is more than one prefix the first one is used for the file name.
    if (is_array($prefix)) {
      $newImageNamePrefix = (string)$prefix[0];
    }
    else {
      $newImageNamePrefix = (string)$prefix;
    }
    // Overwrite $newImageNamePrefix if $destinationPrefix is available
    if ($destinationPrefix) {
      $newImageNamePrefix = $destinationPrefix;
    }
    // If there are no file name components the standard components are used.
    if (!$components) {
      $components = $this->_storeImageGetDefaultComponents();
    }
    do {
      $newImageName = "img/$newImageNamePrefix";
      foreach ((array)$components as $component) {
        $newImageName .= "-$component";
      }
      if ($imageNumber) {
        $newImageName .= "-$imageNumber";
      }
      $timestamp = time() % 1000;
      $newImageName .= "_$timestamp";
    } while (is_file('../'.$newImageName.$newImageExtension));

    // All these resizing and watermarking operations could fail if the
    // configuration was invalid, so we put a try/catch block around it.
    try {
      // If a large image was uploaded we create a normal image from it.
      if ($largeImage) {
        $normalImage = $this->_storeImageCreateNormalFromLargeImage($largeImage, $prefix, $imageNumber);
      }

      // Create a box image from the normal or the large image.
      // A box image is created either if $createBoxImage is true or if
      // $createBoxImage is null and $imageNumber is 1.
      if ($createBoxImage || ($createBoxImage === null && $imageNumber == 1)) {
        $boxImage = $this->_storeImageCreateBoxImage($normalImage, $largeImage, $prefix, $imageNumber);
        $boxImage2 = $this->_storeImageCreateBoxImage2($normalImage, $largeImage, $prefix, $imageNumber);
      }

      // Create a thumbnail image from the normal or the large image.
      if ($createThumbnailImage) {
        $thumbnailImage = $this->storeImageCreateThumbnailImage($normalImage, $largeImage, $prefix, $imageNumber);
      }

      // Create the blog image anyway
      if ($createBlogImage) {
        $blogImage = $this->_storeImageCreateBlogImage($normalImage, $largeImage, $prefix, $imageNumber);
      }

      // Apply watermark to the normal and the large image.
      $this->_storeImageApplyWatermark($normalImage, $largeImage, $prefix, $imageNumber);
    }
    catch(Exception $e) {
      if ($setMessage)
        $this->setMessage(Message::createFailure($e->getMessage()));
      return null;
    }

    // Save all images
    if ($originalImageType == IMAGETYPE_GIF) {
      $normalImage->writeGif("../$newImageName$newImageExtension", 0644);
      if ($largeImage) {
        $largeImage->writeGif("../$newImageName-l$newImageExtension", 0644);
      }
      if ($boxImage) {
        $boxImage->writeGif("../$newImageName-b$newImageExtension", 0644);
      }
      if ($boxImage2) {
        $boxImage2->writeGif("../$newImageName-b2$newImageExtension", 0644);
      }
      if ($thumbnailImage) {
        $thumbnailImage->writeGif("../$newImageName-th$newImageExtension", 0644);
      }
      if ($blogImage) {
        $blogImage->writeGif("../$newImageName-be$newImageExtension", 0644);
      }
    }
    else if ($originalImageType == IMAGETYPE_JPEG) {
      $normalImage->writeJpeg("../$newImageName$newImageExtension", 0644);
      if ($largeImage) {
        $largeImage->writeJpeg("../$newImageName-l$newImageExtension", 0644);
      }
      if ($boxImage) {
        $boxImage->writeJpeg("../$newImageName-b$newImageExtension", 0644);
      }
      if ($boxImage2) {
        $boxImage2->writeJpeg("../$newImageName-b2$newImageExtension", 0644);
      }
      if ($thumbnailImage) {
        $thumbnailImage->writeJpeg("../$newImageName-th$newImageExtension", 0644);
      }
      if ($blogImage) {
        $blogImage->writeJpeg("../$newImageName-be$newImageExtension", 0644);
      }
    } else {
      $normalImage->writePng("../$newImageName$newImageExtension", 0644);
      if ($largeImage) {
        $largeImage->writePng("../$newImageName-l$newImageExtension", 0644);
      }
      if ($boxImage) {
        $boxImage->writePng("../$newImageName-b$newImageExtension", 0644);
      }
      if ($boxImage2) {
        $boxImage2->writePng("../$newImageName-b2$newImageExtension", 0644);
      }
      if ($thumbnailImage) {
        $thumbnailImage->writePng("../$newImageName-th$newImageExtension", 0644);
      }
      if ($blogImage) {
        $blogImage->writePng("../$newImageName-be$newImageExtension", 0644);
      }
    }

    // Delete existing (old) images.
    if ($existingImageName) {
      self::_deleteImageFiles($existingImageName);
    }

    return "$newImageName$newImageExtension";
  }

  /**
   * Resize an image to its fixed width or fixed height.
   *
   * There is at least a fixed width or a fixed height required. If both parameters
   * are not set, the image will not be resized. If both parameters are set only
   * the image width is used.
   *
   * @param CmsImage $origImage
   *        The original image, which should be resized.
   * @param int $width [optional]
   *        The fixed width, the image should be resized to.
   * @param int $height
   *        The fixed height, the image should be resized to.
   *
   * @return CmsImage | null
   *         The resized image. If there is no width or height set null is returned.
   */
  private function _storeImageFixedSize(CmsImage $origImage, $width = null, $height = null)
  {
    $image = clone $origImage;

    // If both sides - box width and box heigth - are not set, there exist no
    // fixed values.
    if ($width === null && $height === null) {
      return null;
    }

    // Width is fixed
    if ($width)
    {
      $ratio = $image->getWidth() / $width;
      $height = $image->getHeight() / $ratio;
    }
    // Height is fixed
    else if ($height)
    {
      $ratio = $image->getHeight() / $height;
      $width = $image->getWidth() / $ratio;
    }

    $image->resize((int)$width, (int)$height);

    return $image;

  }

  /**
   * Stores an image and resizes it to specified image size.
   *
   * @param array|string $sourceImage
   *        Either the uploaded image from the $_FILES array or a string with
   *        the path to an existing image file.
   * @param string|null $existingImageName
   *        The file name of an existing image (used to guarantee a new name).
   *        Can be null (or anything equal to false) if there is no existing file.
   * @param int|float $width
   *        The final width to which the image should be resized.
   * @param int|float $height
   *        The final height to which the image should be resized.
   * @param int|float $selectionWidth
   *        The width of the cropped area.
   * @param int|float $selectionHeight
   *        The height of the cropped area.
   * @param string $prefix
   *        The prefix used for image filename.
   * @param int $imageNumber
   *        The number used for image filename.
   * @param array|string|null $components
   *        Non-Standard file name component(s) (for easy identification in
   *        the file system) or null if the standard components (usually
   *        site ID and page ID) should be used.
   * @return string
   *        The file name of the saved image file or null if nothing was saved
   *        because of an error (details in $this->message).
   */
  protected function _storeImageWithSize($sourceImage, $existingImageName = null,
                                         $width, $height, $selectionWidth, $selectionHeight,
                                         $prefix, $imageNumber = 0, $components = null)
  {
    global $_LANG;

    // Get the path to the source image and return null if we don't have one.
    $sourceImagePath = $this->_storeImageGetSourcePath($sourceImage);
    if (!$sourceImagePath) {
      return null;
    }

    // Load the source image.
    try {
      $image = CmsImageFactory::create($sourceImagePath);
    }
    catch(Exception $e) {
      $this->setMessage(Message::createFailure($e->getMessage()));
      return null;
    }

    // Look if the image has a valid type and determine the extension.
    $imageType = $image->getType();
    $newImageExtension = '';
    switch ($imageType) {
      case IMAGETYPE_GIF:
        $newImageExtension = '.gif';
        break;
      case IMAGETYPE_JPEG:
        $newImageExtension = '.jpg';
        break;
      case IMAGETYPE_PNG:
        $newImageExtension = '.png';
        break;
      default:
        $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
        return null;
    }

    // Determine the new filename for the image.
    do {
      $newImageName = "img/$prefix";
      foreach ((array)$components as $component) {
        $newImageName .= "-$component";
      }
      if ($imageNumber) {
        $newImageName .= "-$imageNumber";
      }
      $timestamp = time() % 1000;
      $newImageName .= "_$timestamp";
    } while (is_file('../'.$newImageName.$newImageExtension));

    // special handling for mutable image sizes
    if (is_array($width) || is_array($height))
    {
      $size = self::_readMutableSize($image, $width, $height);
      $width = $size[0];
      $height = $size[1];
      $selectionWidth = $size[2];
      $selectionHeight = $size[3];
    }

    $image->resize($width, $height, $selectionWidth, $selectionHeight);

    switch($imageType)
    {
      case IMAGETYPE_GIF:
        $image->writeGif("../$newImageName$newImageExtension", 0644);
        break;
      case IMAGETYPE_JPEG:
        $image->writeJpeg("../$newImageName$newImageExtension", 0644);
        break;
      case IMAGETYPE_PNG:
      default:
        $image->writePng("../$newImageName$newImageExtension", 0644);
    }

    // Delete existing (old) images.
    if ($existingImageName) {
      self::_deleteImageFiles($existingImageName);
    }

    return "$newImageName$newImageExtension";
  }

  /**
   * Gets the full path to the source image file after validation.
   *
   * This method only validates if the source image is a valid upload or a
   * valid existing image file. It does not validate image format or size.
   *
   * @param array|string $sourceImage
   *        Either the uploaded image from the $_FILES array or a string with
   *        the path to an existing image file.
   * @return string
   *        The full path to the source image.
   */
  protected function _storeImageGetSourcePath($sourceImage)
  {
    // If $sourceImage is an array it is an uploaded file. We verify the
    // uploaded file and return the path to it.
    if (is_array($sourceImage)) {
      return $this->_verifyUpload($sourceImage);
    }

    $fileSize = false;
    if (filesize($sourceImage) <= 0) {
      // sometimes function filesize returns 0 (very strange),
      // so try to get filesize with the position of a file pointer
      $fp = fopen($sourceImage, "rb");
      fseek($fp, 0, SEEK_END);
      $size = ftell($fp);
      fclose($fp);
      if ($size > 0) {
        $fileSize = true;
      }
    } else {
      $fileSize = true;
    }
    // If $sourceImage is a string it is the path to an existing file. We
    // verify the existing file and return the path to it.
    if (is_string($sourceImage) && is_file($sourceImage) && $fileSize) {
      return $sourceImage;
    }

    // Every other case is an error and we return null.
    return null;
  }

  /**
   * Resizes an image, if the image size was ignored.
   *
   * @param CmsImage $originalImage
   *        Uploaded image, which does not fit with configured size.
   * @param string $prefix
   *        A string with the prefix of the content type to read the configuration.
   * @param int $imageNumber
   *        The number of the image (usually from 1 to 3), ignored if 0.
   * @param boolean $normalImageOnly
   *        If set to true, only the normal image will be calculated.
   * @return CmsImage | null
   *         The resized image or null
   *
   */
  protected function _getResizedImageFromIgnoredImageSize($originalImage, $prefix, $imageNumber = 0, $normalImageOnly = false)
  {
    $resizedImage = null;
    // Determine the size of the source image.
    $orgImageWidth = $originalImage->getWidth();
    $orgImageHeight = $originalImage->getHeight();
    $orgImageSize = array($orgImageWidth, $orgImageHeight);
    // Determine the configured normal size.
    $normalWidth = $this->_configHelper->readImageConfiguration($prefix, 'image_width', $imageNumber);
    $normalHeight = $this->_configHelper->readImageConfiguration($prefix, 'image_height', $imageNumber);
    $normalSize = array($normalWidth, $normalHeight);
    // Determine the configured large size.
    $largeWidth = $this->_configHelper->readImageConfiguration($prefix, array('large_image_width', 'image_width'), $imageNumber);
    $largeHeight = $this->_configHelper->readImageConfiguration($prefix, array('large_image_height', 'image_height'), $imageNumber);
    $largeSize = array($largeWidth, $largeHeight);
    // Determine fixed (not mutable) configuration sizes
    $fixedNormalWidth = !is_array($normalWidth) ? $normalWidth : 0;
    $fixedNormalHeight = !is_array($normalHeight) ? $normalHeight : 0;
    $fixedLargeWidth = !is_array($largeWidth) ? $largeWidth : 0;
    $fixedLargeHeight = !is_array($largeHeight) ? $largeHeight : 0;
    $largeWidthArray = self::_getMutableSizeArray($largeWidth);
    $largeHeightArray = self::_getMutableSizeArray($largeHeight);
    // Determine if there is configured a fixed large image height or width
    $widthLargeFixed = (int)$this->_configHelper->readImageConfiguration($prefix, 'large_image_width_fixed', $imageNumber);
    $heightLargeFixed = (int)$this->_configHelper->readImageConfiguration($prefix, 'large_image_height_fixed', $imageNumber);

    $normalMinWidth = is_array($normalWidth) ? $normalWidth[0] : $normalWidth;
    $normalMinHeight = is_array($normalHeight) ? $normalHeight[0] : $normalHeight;
    $largeMinWidth = is_array($largeWidth) ? $largeWidth[0] : $largeWidth;
    $largeMinHeight = is_array($largeHeight) ? $largeHeight[0] : $largeHeight;
    $largeMaxWidth = is_array($largeWidth) ? $largeWidth[1] : $largeWidth;
    $largeMaxHeight = is_array($largeHeight) ? $largeHeight[1] : $largeHeight;
  // we do not allow image sizes, that are lower than our configuration normal image sizes
    if (!$normalImageOnly && ($orgImageWidth < $normalMinWidth || $orgImageHeight < $normalMinHeight)) {
      return null;
    }
    // we do not allow image sizes, that are lower than our configuration large image sizes
    if (!$normalImageOnly && (($orgImageWidth >= $largeMaxWidth && $orgImageHeight < $largeMinHeight) ||
       ($orgImageWidth < $largeMinWidth && $orgImageHeight >= $largeMaxHeight)))
    {
      return null;
    }

    // REMARK $normalSize != $largeSize:
    // The size arrays each contain two elements, one width and one height value.
    // Two arrays in PHP are equal if they have the same amount of elements
    // and if each element in the first array is equal to the corresponding
    // element in the second array.
    // So we can compare the sizes like we do in the following if statements.

    // handle large image resizing, if large image size does not correspond to the normal image size
    if (!$normalImageOnly && $normalSize != $largeSize && ($largeWidthArray[1] <= $orgImageWidth || $largeHeightArray[1] <= $orgImageHeight)
       && (is_array($largeWidth) || is_array($largeHeight)))
    {
      // handle large image with fixed width
      if ($fixedLargeWidth && is_array($largeHeight)) {
        $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, null, null, $largeHeight, $fixedLargeWidth);
      }
      // handle large image with fixed height
      else if ($fixedLargeHeight) {
        $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, $largeWidth, $fixedLargeHeight);
      }
      // mutable height && width
      else
      {
        if ($orgImageWidth >= $orgImageHeight) {
          $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, null, null, $largeHeight, $largeWidth[1]);
        }
        else {
          $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, $largeWidth, $largeHeight[1]);
        }
      }

      // One image side is set to a fixed size.
      if ($widthLargeFixed || $heightLargeFixed) {
        $resizedImage = self::_storeImageFixedSize($resizedImage, $widthLargeFixed, $heightLargeFixed);
      }
    }
    // handle normal image with fixed width
    else if ($fixedNormalWidth && is_array($normalHeight)) {
      $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, null, null, $normalHeight, $fixedNormalWidth);
    }
    // handle normal image with fixed height
    else if ($fixedNormalHeight && is_array($normalWidth)) {
      $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, $normalWidth, $fixedNormalHeight);
    }
    // mutable height && width
    else if (is_array($normalWidth) && is_array($normalHeight))
    {
      if ($orgImageWidth >= $orgImageHeight) {
        $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, null, null, $normalHeight, $normalWidth[1]);
      }
      else {
        $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, $normalWidth, $normalHeight[1]);
      }
    }
    // We got fixed configured sizes
    else
    {
      // Resize to fit with configured large image size, if image is bigger than the configured large image size
      // and large image size does not correspond to the normal image size
      if (!$normalImageOnly && $largeWidth <= $orgImageWidth && $largeHeight <= $orgImageHeight && $normalSize != $largeSize)
      {
        if ($largeWidth >= $largeHeight) {
          $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, null, null, array($largeHeight, $largeHeight), $largeWidth);
        }
        else {
          $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, array($largeWidth, $largeWidth), $largeHeight);
        }
      }
      // Resize to fit with configured normal image size, if image is smaller than the configured normal image size
      // Other case: If width is bigger/smaller and height is smaller/bigger than the configured sizes,
      // just resize the image to fit with the normal image size configuration
      else {

        if ($normalWidth >= $normalHeight) {
          $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, null, null, array($normalHeight, $normalHeight), $normalWidth);
        }
        else {
          $resizedImage = $this->_getResizedImageWithOneFixedSide($originalImage, array($normalWidth, $normalWidth), $normalHeight);
        }
      }
    }

    return $resizedImage;
  }

  /**
   * Resizes an image without changing its aspect ratio, to fit the large or
   * normal image boundaries
   *
   * @param CmsImage $originalImage
   *        Uploaded image, that should be resized to fit the boundaries
   * @param string $prefix
   *        A string with the prefix of the content type to read the configuration.
   * @param int $imageNumber
   *        The number of the image (usually from 1 to 3), ignored if 0.
   *
   * @return CmsImage
   *         The resized image.
   *
   */
  protected function _getResizedImageAutoFit($originalImage, $prefix, $imageNumber = 0)
  {
    // do not modify the original image, so we work on a copy
    $image = clone $originalImage;

    // set large and normal image boundaries
    $boundaries = array();
    $boundaries[] = array(
      $this->_configHelper->readImageConfiguration($prefix, array('large_image_width', 'image_width'), $imageNumber),
      $this->_configHelper->readImageConfiguration($prefix, array('large_image_height', 'image_height'), $imageNumber),
    );
    $boundaries[] = array(
      $this->_configHelper->readImageConfiguration($prefix, array('image_width'), $imageNumber),
      $this->_configHelper->readImageConfiguration($prefix, array('image_height'), $imageNumber),
    );

    // for all widths and heights with fixed values, create the configuration value,
    // in order to compare it to image width and height below
    if (!is_array($boundaries[0][0])) { $boundaries[0][0] = array($boundaries[0][0], $boundaries[0][0]); }
    if (!is_array($boundaries[0][1])) { $boundaries[0][1] = array($boundaries[0][1], $boundaries[0][1]); }
    if (!is_array($boundaries[1][0])) { $boundaries[1][0] = array($boundaries[1][0], $boundaries[1][0]); }
    if (!is_array($boundaries[1][1])) { $boundaries[1][1] = array($boundaries[1][1], $boundaries[1][1]); }

    // fix image configuration values if provided in wrong format i.e.
    // '...height' => array(200) should bei transformed to '...height' => array(200,200)
    if (!isset($boundaries[0][0][1])) { $boundaries[0][0][1] = $boundaries[0][0][0]; }
    if (!isset($boundaries[0][1][1])) { $boundaries[0][1][1] = $boundaries[0][1][0]; }
    if (!isset($boundaries[1][0][1])) { $boundaries[1][0][1] = $boundaries[1][0][0]; }
    if (!isset($boundaries[1][1][1])) { $boundaries[1][1][1] = $boundaries[1][1][0]; }

    if ($image->getWidth() >= $boundaries[0][0][0] && $image->getHeight() >= $boundaries[0][1][0]) { // large image
      $image->fit($boundaries[0][0], $boundaries[0][1]);
    }
    else if ($image->getWidth() >= $boundaries[1][0][0] && $image->getHeight() >= $boundaries[1][1][0]) { // normal image
      $image->fit($boundaries[1][0], $boundaries[1][1]);
    }
    else {
      // image too small
    }

    return $image;
  }

  /**
   * Scales an image up or down to the given fixed size and may resizes it afterwards
   * if it does not fit with the configured mutable size.
   *
   * @param CmsImage $originalImage
   *        The original image, which should be resized.
   * @param array $mutableWidth
   *        The mutable width, which is may used to resize the image.
   * @param int $fixedHeight
   *        The fixed height, the image should be resized to.
   * @param array $mutableHeight
   *        The mutable height, which is may used to resize the image.
   * @param int $fixedWidth
   *        The fixed width, the image should be resized to.
   * @return CmsImage
   *         The resized image.
   */
  protected function _getResizedImageWithOneFixedSide($originalImage, $mutableWidth = null, $fixedHeight = null, $mutableHeight = null, $fixedWidth = null)
  {
    $tmpResizedImage = $this->_storeImageFixedSize($originalImage, $fixedWidth, $fixedHeight );
    // if fixed width is defined and height is not small enough, resize the image down
    if ($fixedWidth && $tmpResizedImage->getHeight() > $mutableHeight[1]) {
      $acpect_ratio = $mutableHeight[1]/$originalImage->getHeight();
      $tmpResizedImage->resize(round($originalImage->getWidth()*$acpect_ratio), $mutableHeight[1], 0, 0, CmsImage::POSITION_CENTER, CmsImage::POSITION_MIDDLE);
    }
    // if fixed height is defined and width is not small enough, resize the image down
    else if ($fixedHeight && $tmpResizedImage->getWidth() > $mutableWidth[1]) {
      $acpect_ratio = $mutableWidth[1]/$originalImage->getWidth();
      $tmpResizedImage->resize($mutableWidth[1], round($originalImage->getHeight()*$acpect_ratio), 0, 0, CmsImage::POSITION_CENTER, CmsImage::POSITION_MIDDLE);
    }

    return $tmpResizedImage;
  }

  /**
   * Gets the size of an image and returns one of the IMAGESIZE_* constants.
   *
   * @param CmsImage $image
   *        The image whose size should be determined.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   * @param bool $setMessage [optional] [default:true]
   *        set a message for invalid image
   *
   * @return int
   *        One of the IMAGESIZE_* constants that specifies the size of the image.
   */
  protected function _storeImageGetSize(CmsImage $image, $prefix, $imageNumber, $setMessage = true)
  {
    global $_LANG;

    // Determine the configured normal size.
    $normalWidth = $this->_configHelper->readImageConfiguration($prefix, 'image_width', $imageNumber);
    $normalHeight = $this->_configHelper->readImageConfiguration($prefix, 'image_height', $imageNumber);
    $normalSize = array($normalWidth, $normalHeight);

    // Determine the configured large size.
    $largeWidth = $this->_configHelper->readImageConfiguration($prefix, array('large_image_width', 'image_width'), $imageNumber);
    $largeHeight = $this->_configHelper->readImageConfiguration($prefix, array('large_image_height', 'image_height'), $imageNumber);
    $largeSize = array($largeWidth, $largeHeight);

    // Determine the size of the source image.
    $imageSize = array($image->getWidth(), $image->getHeight());

    /**
     * Special handling for mutable normal / large image size.
     * If $normalWidth, $normalHeight, $largeWith or $largeHeight is an array,
     * it specifies the minimum and maximum width for the given image.
     */
    if (is_array($normalWidth) || is_array($normalHeight) ||
        is_array($largeWidth)  || is_array($largeHeight))
    {
      // create arrays from size values (in case there are no arrays defined)
      // do not call this method before evaluating the image configuration as
      // is_array would return true always
      $normalWidth = self::_getMutableSizeArray($normalWidth);
      $normalHeight = self::_getMutableSizeArray($normalHeight);
      $largeWidth = self::_getMutableSizeArray($largeWidth);
      $largeHeight = self::_getMutableSizeArray($largeHeight);

      if ($imageSize[0] >= $normalWidth[0] &&
          $imageSize[0] <= $normalWidth[1] &&
          $imageSize[1] >= $normalHeight[0] &&
          $imageSize[1] <= $normalHeight[1] )
      {
        return self::IMAGESIZE_NORMAL;
      }

      if ($imageSize[0] >= $largeWidth[0] &&
          $imageSize[0] <= $largeWidth[1] &&
          $imageSize[1] >= $largeHeight[0] &&
          $imageSize[1] <= $largeHeight[1] )
      {
        return self::IMAGESIZE_LARGE;
      }

      if ($setMessage)
        $this->setMessage($this->_getImageUploadErrorMessage($normalWidth, $normalHeight, $largeWidth, $largeHeight, $prefix, $imageNumber));

      return self::IMAGESIZE_INVALID;
    }

    // REMARK:
    // The size arrays each contain two elements, one width and one height value.
    // Two arrays in PHP are equal if they have the same amount of elements
    // and if each element in the first array is equal to the corresponding
    // element in the second array.
    // So we can compare the sizes like we do in the following if statements.

    // The source image corresponds to the normal size.
    if ($imageSize == $normalSize) {
      return self::IMAGESIZE_NORMAL;
    }

    // The source image corresponds to the large size.
    if ($imageSize == $largeSize) {
      return self::IMAGESIZE_LARGE;
    }

    if ($setMessage)
      $this->setMessage($this->_getImageUploadErrorMessage($normalWidth, $normalHeight, $largeWidth, $largeHeight, $prefix, $imageNumber));

    return self::IMAGESIZE_INVALID;
  }

  /**
   * Creates the normal image from a large image.
   *
   * @param CmsImage $largeImage
   *        The large image that should be cropped/resized to the normal image.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   * @return CmsImage
   *        The created normal image.
   */
  protected function _storeImageCreateNormalFromLargeImage(CmsImage $largeImage, $prefix, $imageNumber)
  {
    $ignoreImageSize = $this->_configHelper->readImageConfiguration($prefix, 'ignore_image_size', $imageNumber);
    // If image size should be ignored, user is not forced to upload an image with configured sizes.
    // We have to scale the image, if it doesn't fit with configured size
    if ($ignoreImageSize) {
      return $this->_getResizedImageFromIgnoredImageSize($largeImage, $prefix, $imageNumber, true);
    }

    // Determine if there is configured a fixed image height or width.
    $widthFixed = (int)$this->_configHelper->readImageConfiguration($prefix, 'image_width_fixed', $imageNumber);
    $heightFixed = (int)$this->_configHelper->readImageConfiguration($prefix, 'image_height_fixed', $imageNumber);

    // One image side is set to a fixed size.
    if ($widthFixed || $heightFixed) {
      return self::_storeImageFixedSize($largeImage, $widthFixed, $heightFixed);
    }

    // Determine the configured normal size.
    $normalWidth = $this->_configHelper->readImageConfiguration($prefix, 'image_width', $imageNumber);
    $normalHeight = $this->_configHelper->readImageConfiguration($prefix, 'image_height', $imageNumber);

    // Determine the selection on the large image that should be resized.
    $normalSelectionWidth = (int)$this->_configHelper->readImageConfiguration($prefix, 'selection_width', $imageNumber);
    $normalSelectionHeight = (int)$this->_configHelper->readImageConfiguration($prefix, 'selection_height', $imageNumber);

    /*
     * if there is a mutable width or height defined, calculate normal image size
     * from the given large images size
     */
    if (is_array($normalWidth) || is_array($normalHeight) || (!$normalSelectionWidth && !$normalSelectionHeight)) {
      $normalSize = self::_readMutableSize($largeImage, $normalWidth, $normalHeight);
      $normalWidth = $normalSize[0];
      $normalHeight = $normalSize[1];
      $normalSelectionWidth = $normalSize[2];
      $normalSelectionHeight = $normalSize[3];
    }

    // Clone the large image and resize the selection from the clone.
    // The selection is taken from the center of the large image.
    $normalImage = clone $largeImage;
    $normalImage->resize($normalWidth, $normalHeight, $normalSelectionWidth, $normalSelectionHeight);

    return $normalImage;
  }

  /**
   * Creates the blog level image from either the large or the normal image.
   *
   * If there is a large image then the blog level image is created from it.
   * Otherwise the blog level image is created from the normal image.
   *
   * @param CmsImage $normalImage
   *        The normal image that should be cropped/resized to the blog level image.
   * @param CmsImage $largeImage
   *        The large image that should be cropped/resized to the blog level image.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   * @return CmsImage
   *         The created box image.
   */
  private function _storeImageCreateBlogImage(CmsImage $normalImage, CmsImage $largeImage = null, $prefix, $imageNumber)
  {
    // Determine the configured box image size.
    $imageWidth = ConfigHelper::get('lo_be_image_width');
    $imageHeight = ConfigHelper::get('lo_be_image_height');

    // Determine the selection size on the source image.
    $imageSelectionWidth = 0;
    if (ConfigHelper::get('be_selection_width')) {
      $imageSelectionWidth = (int)ConfigHelper::get('be_selection_width');
    }
    $imageSelectionHeight = 0;
    if (ConfigHelper::get('be_selection_height')) {
      $imageSelectionHeight = (int)ConfigHelper::get('be_selection_height');
    }

    // special handling for mutable image sizes
    if (is_array($imageWidth) || is_array($imageHeight) || (!$imageSelectionWidth && !$imageSelectionHeight))
    {
      $tmpImage = $largeImage ? $largeImage : $normalImage;
      $imageSize = self::_readMutableSize($tmpImage, $imageWidth, $imageHeight);
      $imageWidth = $imageSize[0];
      $imageHeight = $imageSize[1];
      $imageSelectionWidth = $imageSize[2];
      $imageSelectionHeight = $imageSize[3];
    }

    // Determine the selection position (default is center/middle, if the
    // configuration variable 'beimage_source' is equal to 1 then the
    // selection is top/middle).
    $imageSelectionPosition = (int)$this->_configHelper->readImageConfiguration($prefix, 'beimage_source', $imageNumber);
    switch($imageSelectionPosition) {
      case 1:
        $imageSelectionX = CmsImage::POSITION_CENTER;
        $imageSelectionY = CmsImage::POSITION_TOP;
        break;
      case 3:
        $imageSelectionX = CmsImage::POSITION_CENTER;
        $imageSelectionY = CmsImage::POSITION_BOTTOM;
        break;
      case 4:
        $imageSelectionX = CmsImage::POSITION_LEFT;
        $imageSelectionY = CmsImage::POSITION_TOP;
        break;
      case 2:
      default:
        $imageSelectionX = CmsImage::POSITION_CENTER;
        $imageSelectionY = CmsImage::POSITION_MIDDLE;
    }

    // Clone the source image and resize the selection from the clone.
    if ($largeImage) {
      $image = clone $largeImage;
    } else {
      $image = clone $normalImage;
    }
    $image->resize($imageWidth, $imageHeight, $imageSelectionWidth,
                   $imageSelectionHeight, $imageSelectionX, $imageSelectionY);

    return $image;
  }

  /**
   * Creates the box image from either the large or the normal image.
   *
   * If there is a large image then the box image is created from it.
   * Otherwise the box image is created from the normal image.
   *
   * @param CmsImage $normalImage
   *        The normal image that should be cropped/resized to the box image.
   * @param CmsImage $largeImage
   *        The large image that should be cropped/resized to the box image.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   * @return CmsImage
   *        The created box image.
   */
  protected function _storeImageCreateBoxImage(CmsImage $normalImage, CmsImage $largeImage = null, $prefix, $imageNumber)
  {
    // Determine if there is configured a fixed boximage height or width.
    $boxWidth = ConfigHelper::get('lo_image_width_fixed');
    $boxHeight = ConfigHelper::get('lo_image_height_fixed');

    // One boximage side is set to a fixed size.
    if ($boxWidth || $boxHeight)
    {
      $image = $largeImage ? clone $largeImage : clone $normalImage;
      return self::_storeImageFixedSize($image, $boxWidth, $boxHeight);
    }

    // Determine the configured box image size.
    $boxWidth = ConfigHelper::get('lo_image_width');
    $boxHeight = ConfigHelper::get('lo_image_height');

    // Determine the selection size on the source image.
    $boxSelectionWidth = 0;
    if (ConfigHelper::get('lo_selection_width')) {
      $boxSelectionWidth = (int)ConfigHelper::get('lo_selection_width');
    }
    $boxSelectionHeight = 0;
    if (ConfigHelper::get('lo_selection_height')) {
      $boxSelectionHeight = (int)ConfigHelper::get('lo_selection_height');
    }

    // special handling for mutable image sizes
    if (is_array($boxWidth) || is_array($boxHeight))
    {
      $tmpImage = $largeImage ? $largeImage : $normalImage;
      $boxSize = self::_readMutableSize($tmpImage, $boxWidth, $boxHeight);
      $boxWidth = $boxSize[0];
      $boxHeight = $boxSize[1];
      $boxSelectionWidth = $boxSize[2];
      $boxSelectionHeight = $boxSize[3];
    }

    // Determine the selection position (default is center/middle, if the
    // configuration variable 'boximage_source' is equal to 1 then the
    // selection is top/middle).
    $boxSelectionPosition = (int)$this->_configHelper->readImageConfiguration($prefix, 'boximage_source', $imageNumber);
    $boxSelectionX = CmsImage::POSITION_CENTER;
    $boxSelectionY = CmsImage::POSITION_MIDDLE;
    if ($boxSelectionPosition == 1) {
      $boxSelectionY = CmsImage::POSITION_TOP;
    }

    // Clone the source image and resize the selection from the clone.
    if ($largeImage) {
      $boxImage = clone $largeImage;
    } else {
      $boxImage = clone $normalImage;
    }
    $boxImage->resize($boxWidth, $boxHeight,
                      $boxSelectionWidth, $boxSelectionHeight,
                      $boxSelectionX, $boxSelectionY);

    return $boxImage;
  }

  /**
   * Creates the second box image from either the large or the normal image.
   *
   * If there is a large image then the box image is created from it.
   * Otherwise the box image is created from the normal image.
   *
   * @param CmsImage $normalImage
   *        The normal image that should be cropped/resized to the box image.
   * @param CmsImage $largeImage
   *        The large image that should be cropped/resized to the box image.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   * @return CmsImage
   *        The created box image.
   */
  protected function _storeImageCreateBoxImage2(CmsImage $normalImage, CmsImage $largeImage = null, $prefix, $imageNumber)
  {
    // Determine if there is configured a fixed boximage height or width.
    $boxWidth = ConfigHelper::get('lo_image_width_fixed2');
    $boxHeight = ConfigHelper::get('lo_image_height_fixed2');

    // One boximage side is set to a fixed size.
    if ($boxWidth || $boxHeight)
    {
      $image = $largeImage ? clone $largeImage : clone $normalImage;
      return self::_storeImageFixedSize($image, $boxWidth, $boxHeight);
    }

    // Determine the configured box image size.
    $boxWidth = ConfigHelper::get('lo_image_width2');
    $boxHeight = ConfigHelper::get('lo_image_height2');

    // Determine the selection size on the source image.
    $boxSelectionWidth = 0;
    if (ConfigHelper::get('lo_selection_width2')) {
      $boxSelectionWidth = (int)ConfigHelper::get('lo_selection_width2');
    }
    $boxSelectionHeight = 0;
    if (ConfigHelper::get('lo_selection_height2')) {
      $boxSelectionHeight = (int)ConfigHelper::get('lo_selection_height2');
    }

    // special handling for mutable image sizes
    if (is_array($boxWidth) || is_array($boxHeight))
    {
      $tmpImage = $largeImage ? $largeImage : $normalImage;
      $boxSize = self::_readMutableSize($tmpImage, $boxWidth, $boxHeight);
      $boxWidth = $boxSize[0];
      $boxHeight = $boxSize[1];
      $boxSelectionWidth = $boxSize[2];
      $boxSelectionHeight = $boxSize[3];
    }

    // Determine the selection position (default is center/middle, if the
    // configuration variable 'boximage_source' is equal to 1 then the
    // selection is top/middle).
    $boxSelectionPosition = (int)$this->_configHelper->readImageConfiguration($prefix, 'boximage2_source', $imageNumber);
    $boxSelectionX = CmsImage::POSITION_CENTER;
    $boxSelectionY = CmsImage::POSITION_MIDDLE;
    if ($boxSelectionPosition == 1) {
      $boxSelectionY = CmsImage::POSITION_TOP;
    }

    // Clone the source image and resize the selection from the clone.
    if ($largeImage) {
      $boxImage = clone $largeImage;
    } else {
      $boxImage = clone $normalImage;
    }
    $boxImage->resize($boxWidth, $boxHeight,
                      $boxSelectionWidth, $boxSelectionHeight,
                      $boxSelectionX, $boxSelectionY);

    return $boxImage;
  }

  /**
   * Creates the thumbnail image from either the large or the normal image.
   *
   * If there is a large image then the thumbnail image is created from it.
   * Otherwise the box image is created from the normal image.
   *
   * @param CmsImage $normalImage
   *        The normal image that should be cropped/resized to the thumbnail image.
   * @param CmsImage $largeImage
   *        The large image that should be cropped/resized to the thumbnail image.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   * @return CmsImage
   *        The created thumbnail image.
   */
  public function storeImageCreateThumbnailImage(CmsImage $normalImage, CmsImage $largeImage = null, $prefix, $imageNumber)
  {
    // Determine if there is configured a fixed thumbnail image height or width.
    $thumbnailWidthFixed = (int)$this->_configHelper->readImageConfiguration($prefix, 'th_image_width_fixed', $imageNumber);
    $thumbnailHeightFixed = (int)$this->_configHelper->readImageConfiguration($prefix, 'th_image_height_fixed', $imageNumber);

    // One thumbnail image side is set to a fixed size.
    if ($thumbnailWidthFixed || $thumbnailHeightFixed)
    {
      $image = $largeImage ? clone $largeImage : clone $normalImage;
      return self::_storeImageFixedSize($image, $thumbnailWidthFixed, $thumbnailHeightFixed);
    }

    // Determine the configured thumbnail image size.
    $thumbnailWidth = (int)$this->_configHelper->readImageConfiguration($prefix, 'th_image_width', $imageNumber);
    $thumbnailHeight = (int)$this->_configHelper->readImageConfiguration($prefix, 'th_image_height', $imageNumber);

    // Determine the source image, the selection size on it and clone it.
    if ($largeImage) {
      $thumbnailSelectionWidth =
        (int)$this->_configHelper->readImageConfiguration($prefix,
                                           array('th_large_selection_width', 'th_selection_width'),
                                           $imageNumber);
      $thumbnailSelectionHeight =
        (int)$this->_configHelper->readImageConfiguration($prefix,
                                           array('th_large_selection_height', 'th_selection_height'),
                                           $imageNumber);
      $thumbnailImage = clone $largeImage;
    } else {
      $thumbnailSelectionWidth = (int)$this->_configHelper->readImageConfiguration($prefix, 'th_selection_width', $imageNumber);
      $thumbnailSelectionHeight = (int)$this->_configHelper->readImageConfiguration($prefix, 'th_selection_height', $imageNumber);
      $thumbnailImage = clone $normalImage;
    }

    // Resize the selection from the clone.
    $thumbnailImage->resize($thumbnailWidth, $thumbnailHeight,
                            $thumbnailSelectionWidth, $thumbnailSelectionHeight);

    return $thumbnailImage;
  }

  /**
   * Applys a watermark to the normal and large image.
   *
   * @param CmsImage $normalImage
   *        The normal image.
   * @param CmsImage $largeImage
   *        The large image.
   * @param string $prefix
   *        The prefix of the content (used to get the correct configration variables).
   * @param int $imageNumber
   *        The number (index) of the image, starting with 1 (used to get the
   *        correct configuration variables).
   */
  protected function _storeImageApplyWatermark(CmsImage $normalImage, CmsImage $largeImage = null, $prefix, $imageNumber)
  {
    $watermarkPosition = $this->_configHelper->readImageConfiguration($prefix, 'watermark', $imageNumber);
    if (!$watermarkPosition) {
      return;
    }

    switch($watermarkPosition){
      case 1:
        $watermarkX = CmsImage::POSITION_LEFT;
        $watermarkY = CmsImage::POSITION_TOP;
        break;
      case 2:
        $watermarkX = CmsImage::POSITION_RIGHT;
        $watermarkY = CmsImage::POSITION_TOP;
        break;
      case 3:
        $watermarkX = CmsImage::POSITION_CENTER;
        $watermarkY = CmsImage::POSITION_MIDDLE;
        break;
      case 4:
        $watermarkX = CmsImage::POSITION_LEFT;
        $watermarkY = CmsImage::POSITION_BOTTOM;
        break;
      case 5:
      default:
        $watermarkX = CmsImage::POSITION_RIGHT;
        $watermarkY = CmsImage::POSITION_BOTTOM;
        break;
    }

    // Apply watermark to the normal image.
    $normalImage->applyWatermark('img/watermark.png', $watermarkX, $watermarkY);

    // Apply watermark to the large image.
    if ($largeImage) {
      $largeImage->applyWatermark('img/watermark.png', $watermarkX, $watermarkY);
    }
  }

  /**
   * Returns the standard file name component(s).
   *
   * This method has to be overridden in each of the child classes.
   *
   * @return array
   *        The standard file name components.
   */
  abstract protected function _storeImageGetDefaultComponents();

  /**
   * Stores an uploaded file in the file system.
   *
   * @param array $uploadedFile
   *        The uploaded file (e.g. $_FILE['test']).
   * @param integer $allowedSize
   *        The allowed file size.
   * @param array $allowedTypes
   *        An array containing the allowed file extensions.
   *        Use the star character (*) to allow all file extensions.
   * @param string $existingName
   *        Optional parameter specifying the name of a file that is allowed
   *        to be overwritten (if an existing file should be replaced).
   * @param string $destinationName
   *        Optional parameter specifying the destination file name (inside of the 'files' folder).
   *        If it is omitted then the original filename of the uploaded file is used.
   * @param boolean $frontend
   *        If true (default value) then the file is stored at the frontend,
   *        else it is stored at the backend.
   * @return string|null
   *        The path to the stored file (relative to the FE root, including the 'files' folder)
   *        or null, if there was no file uploaded.
   */
  protected function _storeFile($uploadedFile, $allowedSize, $allowedTypes,
                                $existingName = '', $destinationName = '',
                                $frontend = true)
  {
    global $_LANG;

    if (!$this->_verifyUpload($uploadedFile, $allowedSize)) {
      return null;
    }
    if ($allowedSize && $uploadedFile['size'] > $allowedSize) {
      $maximumSize = formatFileSize($allowedSize);
      $errorMessage = sprintf($_LANG['global_message_upload_file_size_error'], $maximumSize);
      $this->setMessage(Message::createFailure($errorMessage));
      return null;
    }

    if ($destinationName) {
      $destinationName = "files/$destinationName";
    } else {
      $destinationName = 'files/' . ResourceNameGenerator::file($uploadedFile['name']);
    }

    $destinationPrefix = '';
    if ($frontend) {
      $destinationPrefix = '../';
    }

    $pathinfo = pathinfo($uploadedFile['name']);
    if (!in_array('*', $allowedTypes) && !in_array($pathinfo['extension'], $allowedTypes)) {
      $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
      return null;
    }
    if ($destinationName != $existingName && file_exists($destinationPrefix . $destinationName)) {
      $this->setMessage(Message::createFailure($_LANG['global_message_upload_file_exists_error']));
      return null;
    }

    // update the file name in our words filelink table if old filename is available
    if ($existingName) {
      $sql = "UPDATE {$this->table_prefix}contentitem_words_filelink "
           . "SET WFFile = '{$destinationName}' "
           . "WHERE WFFile = '{$existingName}' ";
      $this->db->query($sql);
    }

    move_uploaded_file($uploadedFile['tmp_name'], $destinationPrefix . $destinationName);
    chmod($destinationPrefix . $destinationName, 0644);
    return $destinationName;
  }

  /**
   * Fixes the odd indexing of multiple file uploads from the format
   * $_FILES['field']['key']['index'] to the more standard and appropriate
   * $_FILES['field']['index']['key']
   *
   * @param array $files
   *        The content from the $_FILES array.
   *
   * @link http://www.php.net/manual/en/features.file-upload.multiple.php#96983
   */
  protected static function _fixFilesArray(&$files)
  {
    $names = array( 'name' => 1, 'type' => 1, 'tmp_name' => 1, 'error' => 1, 'size' => 1);

    foreach ($files as $key => $part)
    {
      // only deal with valid keys and multiple files
      $key = (string) $key;
      if (isset($names[$key]) && is_array($part))
      {
        foreach ($part as $position => $value) {
            $files[$position][$key] = $value;
        }
        // remove old key reference
        unset($files[$key]);
      }
    }
  }

  /**
   * Checks if a file was successfully uploaded and if it is larger than 0 bytes.
   *
   * @param array $uploadedFile
   *        The uploaded file data from the $_FILES array.
   * @param integer $allowedSize [optional] [default : null]
   *        The allowed file size. If null the file size is only checked against
   *        the upload limit retrieved by getUploadLimit().
   * @return string
   *        The path of the uploaded file or null, if nothing was uploaded or
   *        the upload was invalid.
   */
  protected function _verifyUpload($uploadedFile,$allowedSize = null)
  {
    global $_LANG;

    // If an upload error occured we set the appropriate error message.
    if ($uploadedFile['error']) {
      switch ($uploadedFile['error']) {
        case UPLOAD_ERR_NO_FILE:
          break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          // Do not use min() function if $allowedSize is null as it might cause
          // undefined results.
          $maximumSize = $allowedSize !== null ? (min($allowedSize, getUploadLimit())) : getUploadLimit();
          $maximumSize = formatFileSize($maximumSize);
          $errorMessage = sprintf($_LANG['global_message_upload_file_size_error'], $maximumSize);
          $this->setMessage(Message::createFailure($errorMessage));
          break;
        default:
          $this->setMessage(Message::createFailure($_LANG['global_message_upload_general_error']));
          break;
      }

      return null;
    }

    // If the file from the given array is not an uploaded file (can only
    // happen if somebody tampered with the data inside the $_FILES array)
    // we don't accept it.
    if (!is_uploaded_file($uploadedFile['tmp_name'])) {
      return null;
    }

    // We also don't accept empty files.
    if ($uploadedFile['size'] == 0) {
      return null;
    }

    return $uploadedFile['tmp_name'];
  }

  /**
   * Sends data to the client when the action is "response", handles standard requests for all ContentItems.
   *
   * IMPORTANT: If these standard cases do not get handled then the
   *            subclass is overriding this method and not calling
   *            parent::sendResponse($request).
   *
   * @param string $request
   *        The content of the "request" variable inside GET or POST data.
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'ContentItemAutoComplete':
        $this->_sendResponseContentItemAutoComplete();
        break;
      case 'DownloadAutoComplete':
        $this->_sendResponseDownloadAutoComplete();
        break;
      default:
        break;
    }
  }

  /**
   * Sends a search result for content items to the client for the autocomplete feature.
   */
  private function _sendResponseContentItemAutoComplete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $searchString = $get->readString('q');
    if (!$searchString) {
      echo Json::Encode(array());
      return;
    }
    // Searchstring will be escaped in autocomplete js, so decode it now.
    // Escaping the string allows to search also sites with special characters (umlaute)
    $searchString = urldecode($searchString);

    $currentSite = $this->_navigation->getCurrentSite();

    // Determine search scope: local (default) or global
    $sqlScope = "WHERE FK_SID = {$currentSite->getID()} ";
    if (isset($_GET['scope']) && $_GET['scope'] == ScopeHelper::SCOPE_GLOBAL) {
      $globalScopeSiteIDs = ScopeHelper::getGlobalScopeContentItems($currentSite, $this->db, $this->table_prefix);
      $sqlScope = 'WHERE FK_SID IN (' . implode(', ', $globalScopeSiteIDs) . ') ';
    }

    // Blacklist ContentItems
    $sqlExcludeContentItems = '';
    if (isset($_GET['excludeContentItems'])) {
      $excludeContentItems = explode(',', $_GET['excludeContentItems']);
      $excludeContentItems = array_map('intval', $excludeContentItems);
      $sqlExcludeContentItems = 'AND CIID NOT IN (' . implode(', ', $excludeContentItems) . ') ';
    }

    // Whitelist ContentTypes
    $sqlIncludeContentTypes = '';
    if (isset($_GET['includeContentTypes'])) {
      $includeContentTypes = explode(',', $_GET['includeContentTypes']);
      $includeContentTypes = array_map('intval', $includeContentTypes);
      $sqlIncludeContentTypes = 'AND CType IN (' . implode(', ', $includeContentTypes) . ') ';
    }

    // Blacklist ContentTypes
    $sqlExcludeContentTypes = '';
    if (isset($_GET['excludeContentTypes'])) {
      $excludeContentTypes = explode(',', $_GET['excludeContentTypes']);
      $excludeContentTypes = array_map('intval', $excludeContentTypes);
      $sqlExcludeContentTypes = 'AND CType NOT IN (' . implode(', ', $excludeContentTypes) . ') ';
    }

    // Whitelist ContentTypeIDs
    $sqlIncludeContentTypeIDs = '';
    if (isset($_GET['includeContentTypeIDs'])) {
      $includeContentTypeIDs = explode(',', $_GET['includeContentTypeIDs']);
      $includeContentTypeIDs = array_map('intval', $includeContentTypeIDs);
      $sqlIncludeContentTypeIDs = 'AND ci.FK_CTID IN (' . implode(', ', $includeContentTypeIDs) . ') ';
    }

    // Blacklist sites
    $sqlExcludeSiteIDs = '';
    if (isset($_GET['excludeSiteIDs'])) {
      $excludeSiteIDs = explode(',', $_GET['excludeSiteIDs']);
      $excludeSiteIDs = array_map('intval', $excludeSiteIDs);
      $sqlExcludeSiteIDs = 'AND FK_SID NOT IN (' . implode(', ', $excludeSiteIDs) . ') ';
    }

    // Blacklist sites
    $sqlIncludeSiteIDs = '';
    if (isset($_GET['includedSiteIDs'])) {
      // reset scope in case we include want to include custom sites
      // we use a "WHERE" in the condition as $sqlScope is empty
      $sqlScope = '';
      $includedSiteIDs = explode(',', $_GET['includedSiteIDs']);
      $includedSiteIDs = array_map('intval', $includedSiteIDs);
      $sqlIncludeSiteIDs = 'WHERE FK_SID IN (' . implode(', ', $includedSiteIDs) . ') ';
    }

    $sql = 'SELECT CIID, CIIdentifier, CTitle, CPosition, CTClass, FK_SID, CTree, ci.FK_CTID, FUDeleted '
         . "FROM {$this->table_prefix}contentitem ci "
         . "LEFT JOIN {$this->table_prefix}contenttype ct ON ci.FK_CTID = CTID "
         . "LEFT JOIN {$this->table_prefix}frontend_user ON ci.FK_FUID = FUID "
         . $sqlScope
         . $sqlIncludeSiteIDs
         . $sqlExcludeContentItems
         . $sqlIncludeContentTypes
         . $sqlExcludeContentTypes
         . $sqlIncludeContentTypeIDs
         . $sqlExcludeSiteIDs
         . 'AND ( '
         . "  CIIdentifier LIKE '%$searchString%' OR "
         . "  CTitle LIKE '%$searchString%' "
         . ') ';
    $result = $this->db->query($sql);

    $searchResult = array();
    while ($row = $this->db->fetch_row($result)) {

      // Ignore user pages of deleted users
      if ($row['FUDeleted']) {
        continue;
      }

      // Content items from the current site are local, from other sites global.
      $siteScope = ScopeHelper::SCOPE_LOCAL;
      if ($currentSite->getID() != (int)$row['FK_SID']) {
        $siteScope = ScopeHelper::SCOPE_GLOBAL;
      }

      // Allow site index in search result, but fake some properties.
      if ($row['CTree'] === 'main' && $row['CPosition'] == 0) {
        $row['FK_CTID'] = 0;
        $row['CTClass'] = 'ModuleSiteindexCompendium';
        $row['CIIdentifier'] = $_LANG['global_sites_backend_root_siteindex_title'];
      }
      // Ignore all other root pages.
      else if ($row['CPosition'] <= 0) {
        continue;
      }

      $searchResult[] = array(
        'id'          => (int)$row['CIID'],
        'identifier'  => $row['CIIdentifier'],
        'title'       => $row['CTitle'],
        'contenttype' => $row['CTClass'],
        'siteID'      => (int)$row['FK_SID'],
        'siteToken'   => ScopeHelper::getSiteToken((int)$row['FK_SID']),
        'siteScope'   => $siteScope,
        'tree'        => $row['CTree'],
        'type'        => ContentItem::getTypeShortname($row['FK_CTID']),
        // We need this url to get the content item icons from different locations (default autocomplete, tinymce dialog autocomplete, etc.)
        'backend_url' => root_url() . 'edwin/',
      );
    }
    $this->db->free_result($result);

    $autoCompleteResultSorter = new AutoCompleteResultSorter($currentSite->getID());
    usort($searchResult, array($autoCompleteResultSorter, 'sortCallback'));

    header('Content-Type: application/json');

    echo Json::Encode($searchResult);
  }

  /**
   * Sends a search result for downloads to the client for the autocomplete feature.
   *
   * The search result contains central files.
   * Decentral files and files in ContentItemDL items are only shown if a page id
   * is set. Then decentral and ContentItemDL files of current page are additionally shown.
   * The page id is set in tiny mce (dialog.js).
   */
  private function _sendResponseDownloadAutoComplete()
  {
    $get = new Input(Input::SOURCE_GET);

    $searchString = urldecode($get->readString('q'));
    if (!$searchString) {
      echo Json::Encode(array());
      return;
    }

    $currentSite = $this->_navigation->getCurrentSite();

    // Determine search scope: local (default) or global
    $sqlScope = "WHERE FK_SID = {$currentSite->getID()} ";
    if (isset($_GET['scope']) && $_GET['scope'] == ScopeHelper::SCOPE_GLOBAL) {
      $globalScopeSiteIDs = ScopeHelper::getGlobalScopeDownloads($currentSite, $this->db, $this->table_prefix);
      $sqlScope = 'WHERE FK_SID IN (' . implode(', ', $globalScopeSiteIDs) . ') ';
    }

    $downloadTypes = array();
    if (isset($_GET['downloadTypes'])) {
      $downloadTypes = explode(',', $get->readString('downloadTypes'));
    }

    // Determine the request origin
    $originModule = false;
    if ($get->readString('origin') == BackendRequest::EDWIN_COMPONENT_MODULE) {
      $originModule = true;
    }

    if (!$get->readInt('page'))
    {
      $downloadTypes[] = 'centralfile';
    }
    else
    {
      $sql = 'SELECT FID AS ID, FTitle AS Title, FFile AS File, '
           . "       CIID, CIIdentifier, FK_SID, 'file' AS Type, 0 AS ShowAlways "
           . "FROM {$this->table_prefix}file f "
           . "JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID "
           . $sqlScope
           . 'AND FFile IS NOT NULL '
           . 'AND ( '
           . "  FTitle LIKE '%$searchString%' OR "
           . "  FFile LIKE 'files/%$searchString%' "
           . ') '
           . 'AND f.FK_CIID = '.$get->readInt('page').' ';
      $sqlAllTypes['file'] = $sql;

      $sql = 'SELECT DFID AS ID, DFTitle AS Title, DFFile AS File, '
           . "       CIID, CIIdentifier, FK_SID, 'dlfile' AS Type, 0 AS ShowAlways "
           . "FROM {$this->table_prefix}contentitem_dl_area_file cidlaf "
           . "JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID "
           . "JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID "
           . $sqlScope
           . 'AND DFFile IS NOT NULL '
           . 'AND ( '
           . "  DFTitle LIKE '%$searchString%' OR "
           . "  DFFile LIKE 'files/%$searchString%' "
           . ') '
           . 'AND cidla.FK_CIID = '.$get->readInt('page').' ';
      $sqlAllTypes['dlfile'] = $sql;
    }

    $sql = 'SELECT CFID AS ID, CFTitle AS Title, CFFile AS File, '
         . "       0 AS CIID, '' AS CIIdentifier, FK_SID, 'centralfile' AS Type, CFShowAlways AS ShowAlways "
         . "FROM {$this->table_prefix}centralfile "
         . $sqlScope
         . 'AND ( '
         . "  CFTitle LIKE '%$searchString%' OR "
         . "  CFFile LIKE 'files/%$searchString%' "
         . ') ';
    $sqlAllTypes['centralfile'] = $sql;

    $sqls = array();
    if ($downloadTypes) {
      // we filter the sql statements by the specified download types
      foreach ($downloadTypes as $downloadType) {
        if (isset($sqlAllTypes[trim($downloadType)])) {
          $sqls[] = $sqlAllTypes[trim($downloadType)];
        }
      }
    }

    if (!$sqls) {
      // if there were no download types explicitly specified or if those
      // were invalid we search all available download types
      $sqls = $sqlAllTypes;
    }

    $sql = implode('UNION ALL ', $sqls);
    $result = $this->db->query($sql);

    $searchResult = array();
    while ($row = $this->db->fetch_row($result)) {
      if (ConfigHelper::get('m_modules_show_only_available_files')
         && $originModule && !$row['ShowAlways'] && $row['Type'] == 'centralfile') {
        // Get file count
        $sql = 'SELECT COUNT(FID) '
             . "FROM {$this->table_prefix}file f "
             . "JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID "
             . "WHERE FK_CFID = ".$row['ID']." ";
        $fileCount = $this->db->GetOne($sql);
        // Get words file link count
        $sql = 'SELECT COUNT(CFID) '
             . "FROM {$this->table_prefix}centralfile cf "
             . "JOIN {$this->table_prefix}contentitem_words_filelink ciwfl ON ciwfl.WFFile = cf.CFFile "
             . "JOIN {$this->table_prefix}contentitem ci ON ciwfl.FK_CIID = ci.CIID "
             . "WHERE CFID = ".$row['ID']." ";
        $fileWordsCount = $this->db->GetOne($sql);
        // Get download area file count
        $sql = 'SELECT COUNT(DFID) '
             . "FROM {$this->table_prefix}contentitem_dl_area_file cidlaf "
             . "JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID "
             . "JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID "
             . "WHERE FK_CFID = ".$row['ID']." ";
        $dlAreaFileCount = $this->db->GetOne($sql);

        if (!$fileCount && !$fileWordsCount && !$dlAreaFileCount) {
          continue;
        }
      }

      // Downloads from the current site are local, from other sites global.
      $siteScope = ScopeHelper::SCOPE_LOCAL;
      if ($currentSite->getID() != (int)$row['FK_SID']) {
        $siteScope = ScopeHelper::SCOPE_GLOBAL;
      }

      $searchResult[] = array(
        'type' => $row['Type'],
        'id' => (int)$row['ID'],
        'title' => $row['Title'],
        'file' => mb_substr(mb_strrchr($row['File'], '/'), 1),
        'ciid' => (int)$row['CIID'],
        'identifier' => $row['CIIdentifier'],
        'siteID' => (int)$row['FK_SID'],
        'siteToken' => ScopeHelper::getSiteToken((int)$row['FK_SID']),
        'siteScope' => $siteScope,
      );
    }
    $this->db->free_result($result);
    $autoCompleteResultSorter = new AutoCompleteResultSorter($currentSite->getID());
    usort($searchResult, array($autoCompleteResultSorter, 'sortCallback'));

    header('Content-Type: application/json');

    echo Json::Encode($searchResult);
  }

  /**
   * Deletes an image file (including box-images, thumbnails, etc.).
   *
   * @param string|array $imageFiles
   *        the "base name" of the image file (i.e. "img/ci_image_1-1_999.jpg") or an array thereof
   * @param string|array $imageFiles,...
   *        unlimited number of additional image files (or arrays thereof)
   */
  protected static function _deleteImageFiles($imageFiles)
  {
    foreach (func_get_args() as $arg) {
      $fileArray = is_array($arg) ? $arg : array($arg);

      foreach ($fileArray as $file) {
        $filePath = (string)$file;
        if ($filePath) {
          $pathInfo = pathinfo($filePath);
          $dir = $pathInfo["dirname"];
          $ext = $pathInfo["extension"];
          if (isset($pathInfo["filename"])) {
            // >= PHP 5.2.0
            $name = $pathInfo["filename"];
          }
          else {
            // PHP < 5.2.0 (determine file name without extension from basename)
            $name = $pathInfo["basename"];
            if (mb_strstr($pathInfo["basename"], ".")) {
              $name = mb_substr($pathInfo["basename"], 0, mb_strrpos($pathInfo["basename"], "."));
            }
          }

          unlinkIfExists("../$dir/$name.$ext");
          unlinkIfExists("../$dir/$name-b.$ext");
          unlinkIfExists("../$dir/$name-b2.$ext");
          unlinkIfExists("../$dir/$name-be.$ext");
          unlinkIfExists("../$dir/$name-l.$ext");
          unlinkIfExists("../$dir/$name-th.$ext");
        }
      }
    }
  }

  /**
   * Gets a string that tells the user the correct image size(s).
   *
   * @param string|array $prefixes
   *        A string with the prefix of the content type (i.e. 'ti') or an array
   *        of prefixes (i.e. ['es_ext', 'es'], the first found value is returned).
   * @param int $imageNumber
   *        The number of the image (usually from 1 to 3), ignored if 0.
   * @return string
   *        A string that tells the user the correct image size(s).
   */
  protected function _getImageSizeInfo($prefixes, $imageNumber) {
    global $_LANG;

    $imageWidth = $this->_configHelper->readImageConfiguration($prefixes, 'image_width', $imageNumber);
    $imageHeight = $this->_configHelper->readImageConfiguration($prefixes, 'image_height', $imageNumber);
    $largeImageWidth =
      $this->_configHelper->readImageConfiguration($prefixes,
                                         array('large_image_width', 'image_width'),
                                         $imageNumber);
    $largeImageHeight =
      $this->_configHelper->readImageConfiguration($prefixes,
                                         array('large_image_height', 'image_height'),
                                         $imageNumber);

    $autoResize = $this->_configHelper->readImageConfiguration($prefixes, 'ignore_image_size', $imageNumber) ||
                  $this->_configHelper->readImageConfiguration($prefixes, 'autofit_image_upload', $imageNumber);

    // special handling for mutable image sizes
    if (is_array($imageWidth)      || is_array($imageHeight) ||
        is_array($largeImageWidth) || is_array($largeImageHeight))
    {
      // create arrays from size values (in case there are no arrays defined)
      // do not call this method before evaluating the image configuration as
      // is_array would return true always
      $imageWidth = self::_getMutableSizeArray($imageWidth);
      $imageHeight = self::_getMutableSizeArray($imageHeight);
      $largeImageWidth = self::_getMutableSizeArray($largeImageWidth);
      $largeImageHeight = self::_getMutableSizeArray($largeImageHeight);

      // format output for mutable image sizes
      $widthLabel = $imageWidth[0] != $imageWidth[1] ?
                    sprintf($_LANG['global_upload_image_mutable_resolution'], $imageWidth[0], $imageWidth[1]) :
                    $imageWidth[0];
      $heightLabel = $imageHeight[0] != $imageHeight[1] ?
                     sprintf($_LANG['global_upload_image_mutable_resolution'], $imageHeight[0], $imageHeight[1]) :
                     $imageHeight[0];

      if ($imageWidth  == $largeImageWidth  && $imageHeight == $largeImageHeight )
      {
        $langLabel = ($autoResize) ? $_LANG['global_image_auto_resize_label'] : $_LANG['global_required_resolution_label'];
        return sprintf($langLabel, $widthLabel, $heightLabel);
      }
      else
      {
        // format output for mutable image sizes
        $largeWidthLabel = $largeImageWidth[0] != $largeImageWidth[1] ?
                           sprintf($_LANG['global_upload_image_mutable_resolution'], $largeImageWidth[0], $largeImageWidth[1]) :
                           $largeImageWidth[0];
        $largeHeightLabel = $largeImageHeight[0] != $largeImageHeight[1] ?
                            sprintf($_LANG['global_upload_image_mutable_resolution'], $largeImageHeight[0], $largeImageHeight[1]) :
                            $largeImageHeight[0];
        $langLabel = ($autoResize) ? $_LANG['global_image_auto_resize_2sizes_label'] : $_LANG['global_required_resolution_2sizes_label'];
        return sprintf($langLabel, $largeWidthLabel, $largeHeightLabel, $widthLabel, $heightLabel);
      }
    }

    if ($imageWidth == $largeImageWidth && $imageHeight == $largeImageHeight)
    {
      $langLabel = ($autoResize) ? $_LANG['global_image_auto_resize_label'] : $_LANG['global_required_resolution_label'];
      $required_resolution_label = sprintf($langLabel, $imageWidth, $imageHeight);
    }
    else
    {
      $langLabel = ($autoResize) ? $_LANG['global_image_auto_resize_2sizes_label'] : $_LANG['global_required_resolution_2sizes_label'];
      $required_resolution_label = sprintf($langLabel, $largeImageWidth, $largeImageHeight,
                                           $imageWidth, $imageHeight);
    }

    return $required_resolution_label;
  }

  /**
   * Sets the message to the given message, if it's not already set.
   *
   * @param Message $message
   *        The message that should be set.
   * @throws InvalidArgumentException
   *        The given message object is null.
   */
  public function setMessage(Message $message) {
    if (!$message) {
      throw new InvalidArgumentException('The given message must not be null.');
    }

    if (!$this->message) {
      $this->message = $message;
    }
  }

  /**
   * Gets the status message of the page.
   *
   * @return Message
   *        The status message of the page or null if there is no message.
   */
  protected function _getMessage() {
    return $this->message;
  }

  /**
   * Gets an array that can be used with the Template class to parse the IF for the message.
   *
   * @param string $prefix
   *        The prefix for the template variables (usually the two-character-
   *        prefix for the ContentItem/Module).
   * @return array
   *        An array that contains the message text and the type of the message
   *        or an empty array if there is no message.
   */
  protected function _getMessageTemplateArray($prefix) {
    $templateArray = array(0);

    if ($this->message) {
      $templateArray = $this->message->getTemplateArray($prefix);
    }

    return $templateArray;
  }

  /**
   * Update search index (for downloads).
   *
   * @param string $title
   *        The title of the download.
   * @param int $ciid
   *        The ID of the ContentItem.
   * @param bool $add
   *        true if the download title should be added to the search index,
   *        false if it should be removed.
   */
  protected function _spiderDownload($title, $ciid, $add)
  {
    $fl_words = array();

    // Split the title into single words.
    $temp = self::_parseForSpider($title);
    foreach ($temp as $word) {
      if (isset($fl_words[$word])) {
        $fl_words[$word]++;
      } else {
        $fl_words[$word] = 1;
      }
    }

    foreach ($fl_words as $word => $count) {
      if ($add) { // add word to search index
        $sql = 'SELECT FK_CIID '
             . "FROM {$this->table_prefix}contentitem_words "
             . "WHERE FK_CIID = $ciid "
             . "AND WWord = '$word' ";
        $row = $this->db->GetRow($sql);
        if ($row) {
          $sql = "UPDATE {$this->table_prefix}contentitem_words "
               . "SET WDownloadCount = WDownloadCount + $count "
               . "WHERE FK_CIID = $ciid "
               . "AND WWord = '$word' ";
          $result = $this->db->query($sql);
        } else {
          $sql = "INSERT INTO {$this->table_prefix}contentitem_words "
               . '            (FK_CIID, WWord, WDownloadCount) '
               . "VALUES ($ciid, '$word', $count) ";
          $result = $this->db->query($sql);
        }
      } else { // remove word from search index
        $sql = "UPDATE {$this->table_prefix}contentitem_words "
             . "SET WDownloadCount = WDownloadCount - $count "
             . "WHERE FK_CIID = $ciid "
             . "AND WWord = '$word' ";
        $result = $this->db->query($sql);
      }
    }

    if (!$add) {
      $sql = "DELETE FROM {$this->table_prefix}contentitem_words "
           . 'WHERE WContentTitleCount + WTitleCount + WTextCount + WDownloadCount = 0 '
           . "AND FK_CIID = $ciid ";
      $result = $this->db->query($sql);
    }
  }

    /**
     * Update the search index for a specific content item, for a specific
     * element type.
     *
     * @param int $ID
     *        The content item id.
     * @param string $type
     *        One of the following strings identifying the count that should be
     *        increased for the given content item and word.
     *        'content_title', 'download', 'image', 'text', 'title'
     * @param string $oldWord
     *        The old word.
     * @param string $newWord
     *        The new word.
     * @param bool $noChangeDate [optional] [default : false]
     *        If set to true the change date of the content item is not going to
     *        be updated.
     *
     * @return bool
     *         True on success, false otherwise. If $oldWord and $newWord are
     *         identical, false is returned.
     */
    protected function _spiderElement($ID, $type, $oldWord = '', $newWord = '', $noChangeDate = false)
    {
      // There has not been a content item id specified.
      if (!$ID || !$type) {
        return false;
      }

      switch($type)
      {
        case 'content_title':
          $sqlType = 'WContentTitleCount';
          break;
        case 'download':
          $sqlType = 'WDownloadCount';
          break;
        case 'image':
          $sqlType = 'WImageCount';
          break;
        case 'text':
          $sqlType = 'WTextCount';
          break;
        case 'title':
          $sqlType = 'WTitleCount';
          break;
        default: /* unknown type */
          return false;
      }

      $oldWords = self::_parseForSpider($oldWord);
      $newWords = self::_parseForSpider($newWord);

      // Word has not changed.
      if ($oldWords == $newWords) {
        return false;
      }

      // Decrease the number of occurances of the old word for the specified
      // content element type within the search index.
      if ($oldWords)
      {
        foreach ($oldWords as $oldWord)
        {
          $sql = ' SELECT (WContentTitleCount + WDownloadCount + WImageCount + WTextCount + WTitleCount) AS Count'
               . " FROM {$this->table_prefix}contentitem_words "
               . " WHERE FK_CIID = $ID "
               . "   AND WWord = '$oldWord' "
               . ' LIMIT 1 ';
          $count = (int)$this->db->GetOne($sql);

          // The old word exists multiple times, so decrease the word count.
          if ($count > 1)
          {
            $sql = " UPDATE {$this->table_prefix}contentitem_words "
                 . " SET $sqlType = $sqlType - 1 "
                 . " WHERE FK_CIID = $ID "
                 . "   AND WWord = '$oldWord' "
                 . ' LIMIT 1 ';
            $this->db->query($sql);
          }
          // The old word only existed once in content item, so remove it completely.
          else
          {
            $sql = " DELETE FROM {$this->table_prefix}contentitem_words "
                 . " WHERE FK_CIID = $ID "
                 . "   AND WWord = '$oldWord' "
                 . ' LIMIT 1 ';
            $this->db->query($sql);
          }
        }
      }

      if ($newWords)
      {
        foreach ($newWords as $newWord)
        {
          // Check if the new word already exists within the search index
          $sql = ' SELECT FK_CIID '
               . " FROM {$this->table_prefix}contentitem_words "
               . " WHERE FK_CIID = $ID "
               . "   AND WWord = '$newWord' "
               . ' LIMIT 1 ';
          $exists = $this->db->GetOne($sql);

          // The word exists within the search index, so increase the specified count.
          if ($exists)
          {
            $sql = " UPDATE {$this->table_prefix}contentitem_words "
                 . " SET $sqlType = $sqlType + 1 "
                 . " WHERE FK_CIID = $ID "
                 . "   AND WWord = '$newWord' "
                 . ' LIMIT 1 ';
            $this->db->query($sql);
          }
          else
          {
            $sql = " INSERT INTO {$this->table_prefix}contentitem_words "
                 . " ( WWord, FK_CIID, $sqlType )"
                 . ' VALUES '
                 . " ( '{$this->db->escape($newWord)}', $ID, 1 ) ";
            $this->db->query($sql);
          }
        }
      }

      // Set the change date for contentitem.
      if (!$noChangeDate)
      {
        $now = date('Y-m-d H:i:s');
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . " SET CChangeDateTime = '$now' "
             . " WHERE CIID = $ID ";
        $this->db->query($sql);
      }

      return true;
    }

  /**
   * Extracts all words that should be spidered from a given string.
   *
   * @param string $content
   *        The string from which the words should be taken out.
   * @return array
   *        Contains all words from the string that should be spidered.
   */
  protected static function _parseForSpider($content)
  {
    global $_CONFIG;

    $minLength = $_CONFIG['se_min_wordlength'];

    // convert newlines / strip html tags
    $content = preg_replace('/\\r\\n|\\n|\\r|\<br\>|\<br\ \\\\>/u', ' ', $content);
    $content = strip_tags($content);
    $content = str_replace('&nbsp;', ' ', $content);

    // strip non-word chars (allowed: all possible language characters, digits and underscores)
    $content = preg_replace('/[^\pL\d_]/u', ' ', $content);

    // convert to lowercase
    $content = mb_strtolower($content);

    // unicode trim
    $content = trim($content);

    // split the string to words
    $content = explode(' ', $content);

    // remove words, which are too short
    foreach ($content as $key => $word)
    {
      if (mb_strlen($word) < $minLength) {
        unset($content[$key]);
      }
    }

    return $content;
  }

  /**
   * Gets the path to the thumbnail image or an empty string if there is none.
   *
   * @param string $image
   *        The path to the base image (e.g. 'img/ti-1-2-3_456.jpg').
   * @return string
   *        The path to the thumbnail image or an empty string if there is none.
   */
  protected static function _getThumbnailImage($image)
  {
    $thumbnailImage = mb_substr($image, 0, -4) . '-th' . mb_substr($image, -4);
    if (is_file($thumbnailImage)) {
      return $thumbnailImage;
    }
    return '';
  }

  /**
   * Wrapper function for the php strtotime function, which has various return
   * values with different php versions.
   *
   * @param string $time
   * @param timestamp $now [optional]
   *
   * @return unix timestamp | false
   *
   * @see strtotime
   */
  public static function strToTime($time = '', $now = null)
  {
    // check for default date / datetime values and return false,
    // strtotime possibly creates different return values
    if ('0000-00-00' == $time || '0000-00-00 00:00:00' == $time) {
        return false;
    }

    $timestamp = strtotime($time, $now);

    if ($timestamp !== false && $timestamp !== -1) {
      return $timestamp;
    }

    return false;
  }

  /**
   * Calculates new image size fitting the new width(s) and new height(s)
   * from given image's size. This method should be called whenever the new images
   * widths and / or heights are mutable (minimum and maximum value exist) and
   * the new image should be created from a larger version of the image.
   * i.e. $_CONFIG[ti_image_width] = array(310, 320)
   *
   * NOTE:
   * - If the new image's size exceeds its allowed widths $newWidths or heights
   *   $newHeights, a selection width and height is calculated for cropping the
   *   new area from the existing image.
   * - If both values (width and height) are mutable, the height is set to a
   *   fixed value in order to create an image nevertheless.
   *   i.e. $_CONFIG["lo_image_width"] = array(60, 65)
   *        $_CONFIG["lo_image_height"] = array(80, 85)
   *        lo_image_height is assumed to be (80, 80)
   *
   * @param CmsImage $image
   * @param array | int $newWidths
   * @param array | int $newHeights
   *
   * @return array $size
   *         contains four values
   *         - image width, the calculated width
   *         - image height, the calculated height
   *         - image selection width, the calculated selection width
   *         - image selection height, the calculated selection height
   */
  protected static function _readMutableSize(CmsImage $image, $newWidths, $newHeights)
  {
    $size = array(0,0,0,0);

    $newWidths = self::_getMutableSizeArray($newWidths);
    $newHeights = self::_getMutableSizeArray($newHeights);

    // store image width and heigth
    $width = $image->getWidth();
    $height = $image->getHeight();

    // Check if the given image's size fits the new image's width and height
    $widthOk = ($width >= $newWidths[0]) && ($width <= $newWidths[1]);
    $heightOk = ($height >= $newHeights[0]) && ($height <= $newHeights[1]);

    if ($widthOk && $heightOk) { // neither width nor height has to be changed
       return array((int)$width, (int)$height, 0, 0);
    }

    $ratio = 0;
    $newWidth = 0;
    $newHeight = 0;
    $newWidthSelection = 0;
    $newHeightSelection = 0;

    /*
     * Determine the aspect ratio for landscape format and calculate new width
     * and height for new image.
     */
    if ($width >= $height)
    {
      $ratio = $width / $height;

      // height is fixed
      if ($newHeights[0] == $newHeights[1])
      {
        $newHeight = $newHeights[0];
        $newWidth = $newHeights[0] * $ratio;
      }
      // width is fixed
      else if ($newWidths[0]  == $newWidths[1])
      {
        $newWidth = $newWidths[0];
        $newHeight = $newWidths[0] / $ratio;
      }
      // neither width nor height is fixed, so use the image's height and
      // set its minimum and maximum to the same value (fixed)
      else
      {
        $newHeights[1] = $newHeights[0];
        $newHeight = $newHeights[0];
        $newWidth = $newHeights[0] * $ratio;
      }
    }
    // determine the aspect ratio for portrait format
    else {
      $ratio = $height / $width;

      if ($newWidths[0]  == $newWidths[1])
      {
        $newWidth = $newWidths[0];
        $newHeight = $newWidths[0] * $ratio;
      }
      else if ($newHeights[0] == $newHeights[1])
      {
        $newHeight = $newHeights[0];
        $newWidth = $newHeights[0] / $ratio;
      }
      // neither width nor height is fixed, so use the image's height and
      // set its minimum and maximum to the same value (fixed)
      else
      {
        $newWidths[0] = $newWidths[1];
        $newWidth = $newWidths[1];
        $newHeight = $newWidth * $ratio;
      }
    }

    // if the new width exceeds its limitations calculate selection width and height
    if ($newWidth > $newWidths[1])
    {
      $newWidth = $newWidths[1];
      $newWidthSelection = $height * $newWidth / $newHeight;
      $newHeightSelection = $height;
    }
    // if the new height exceeds its limitations calculate selection width and height
    else if ($newHeight > $newHeights[1])
    {
      $newHeight = $newHeights[1];
      $newHeightSelection = $width * $newHeight / $newWidth;
      $newWidthSelection = $width;
    }

    return array((int)$newWidth, (int)$newHeight, (int)$newWidthSelection, (int)$newHeightSelection);
  }

  /**
   * Returns the size value as an array. For a single value an array containing
   * the minimum and maximum size is returned.
   * If $size already is an array with a minimum and maximum value (array index
   * 0 & 1) defined, $size is returned.
   *
   * NOTE: This function should be called when dealing with $_CONFIG variables
   * for image size, in case of necessary handling for mutable image sizes.
   *
   * @param $size
   *
   * @return array
   *         - An array containing two values if parameter is a single integer value
   */
  protected static function _getMutableSizeArray($size)
  {
    if (is_array($size) && isset($size[1])) {
      return $size;
    }

    $size = (array)$size;
    if (!isset($size[1])) {
      $size[1] = $size[0];
    }

    return $size;
  }


  /**
   * Explode image titles for content item.
   *
   * @param string $contenttype - the contenttype prefix
   * @param string $titles - string containing image titles separated by '$%$'
   *
   * @return array - contains image title, plain image title and the image title label
   */
  protected function explode_content_image_titles($contentPrefix, $titles)
  {
    global $_LANG;

    $output = array();
    for ($i=1; $i <= 15; $i++) {
      $output["{$contentPrefix}_image{$i}_title"] = '';
      $output["{$contentPrefix}_image{$i}_title_plain"] = '';
      $output["{$contentPrefix}_image{$i}_title_label"] = !empty($_LANG["{$contentPrefix}_image{$i}_title_label"]) ? $_LANG["{$contentPrefix}_image{$i}_title_label"] : (!empty($_LANG["{$contentPrefix}_image_title_label"]) ? $_LANG["{$contentPrefix}_image_title_label"] : $_LANG["global_image_title_label"]);
    }

    if ($titles) {
      $titles = $this->_splitImageTitleString($titles);
      foreach ($titles as $id => $value) {
        $id++;
        $output["{$contentPrefix}_image{$id}_title"] = parseOutput($value, 2);
        $output["{$contentPrefix}_image{$id}_title_plain"] = parseOutput($value, 3);
      }
    }

    return $output;
  }

  /**
   * @param string $imageTitles
   *        the '$%$' separated image title string
   * @return array
   *         the array of image titles
   */
  protected function _splitImageTitleString($imageTitles)
  {
    return explode('$%$', $imageTitles);
  }

  /**
   * Gets the "hierarchical title" of a content item (Products -> Hardware -> CPUs -> Intel Xeon X3220).
   *
   * @param int $ciid
   *        The ID of the content item.
   * @param string $separator
   *        The string separating the titles. If empty string, false or null,
   *        the $_CONFIG['m_hierarchical_title_separator'] is used.
   * @return string
   *        The "hierarchical title" of a content item.
   */
  protected function _getHierarchicalTitle($ciid, $separator)
  {
    if (!$separator) {
      $separator = ConfigHelper::get('m_hierarchical_title_separator');
    }

    $title = array();

    $page = $this->_navigation->getPageByID($ciid);
    while (!$page->isRoot()) {
      array_unshift($title, $page->getTitle());
      $page = $page->getParent();
    }

    return implode($separator, $title);
  }

  /**
   * Converts a decentral file to a central file.
   *
   * @param int $fid
   *        file id of the decentral file.
   * @return boolean
   *         false if decentral file does not exist and true if the file was successfully converted, otherwise false.
   */
  protected function _convertDecentral2Central($fid) {
    // determine information from the decentral file $fid
    $decentral_file = $this->db->GetRow(<<<SQL
SELECT FTitle, FFile, FCreated, FModified, f.FSize, f.FK_CIID, FK_SID
FROM {$this->table_prefix}file f JOIN {$this->table_prefix}contentitem ci ON f.FK_CIID = ci.CIID
WHERE FID = $fid
SQL
    );

    if (!$decentral_file) return false;

    // create new central file
    $modified = $decentral_file['FModified'] ? "'{$decentral_file['FModified']}'" : 'NULL';
    $fileSize = $decentral_file['FSize'];
    $result = $this->db->query(<<<SQL
INSERT INTO {$this->table_prefix}centralfile(CFTitle, CFFile, CFCreated, CFModified, CFSize, FK_SID)
VALUES('{$this->db->escape($decentral_file["FTitle"])}', '{$decentral_file["FFile"]}', '{$decentral_file["FCreated"]}', $modified, $fileSize, {$decentral_file["FK_SID"]})
SQL
    );
    $central_file_id = $this->db->insert_id();

    // change file entry
    $sql = "UPDATE {$this->table_prefix}file "
         . "SET FTitle = '', "
         . "    FFile = NULL, "
         . "    FModified = NULL, "
         . "    FSize = 0, "
         . "    FK_CFID = $central_file_id "
         . "WHERE FID = $fid ";

    if ($this->db->query($sql)) {
      return true;
    }
    return false;
  }

  /**
   * Converts a contenteItemDLArea file to a central file.
   *
   * @param int $fid
   *        file id of the contenteItemDLArea file.
   * @return boolean
   *         false if contenteItemDLArea file does not exist and true if the file was successfully converted, otherwise false.
   */
  protected function _convertContentItemDLAreaFile2Central($fid) {
    // Determine file information.
    $sql = ' SELECT DFTitle, DFFile, DFCreated, DFModified, DFSize, ci.FK_SID AS FK_SID'
         . " FROM {$this->table_prefix}contentitem_dl_area_file cidlaf"
         . " JOIN {$this->table_prefix}contentitem_dl_area cidla ON cidlaf.FK_DAID = cidla.DAID "
         . " JOIN {$this->table_prefix}contentitem ci ON cidla.FK_CIID = ci.CIID "
         . " WHERE DFID = $fid ";
    $dlFile = $this->db->GetRow($sql);

    // Invalid file id, or no filename
    if (!$dlFile || !$dlFile['DFFile']) {
      return false;
    }

    // Create  a new central file.
    $modified = $dlFile['DFModified'] ? "'{$dlFile['DFModified']}'" : 'NULL';
    $fileSize = $dlFile['DFSize'];

    $sql = " INSERT INTO {$this->table_prefix}centralfile "
         . ' (CFTitle, CFFile, CFCreated, CFModified, CFSize, FK_SID) '
         . ' VALUES ( '
         . "   '{$this->db->escape($dlFile['DFTitle'])}', '{$dlFile['DFFile']}', "
         . "   '{$dlFile["DFCreated"]}', $modified, $fileSize, '{$dlFile["FK_SID"]}'"
         . ' )';
    $result = $this->db->query($sql);
    // Retrieve the id of the created central download.
    $cfID = $this->db->insert_id();

    // change file entry
    $sql = " UPDATE {$this->table_prefix}contentitem_dl_area_file "
         . " SET DFFile = NULL, "
         . "     DFModified = NULL, "
         . "     DFSize = 0, "
         . "     FK_CFID = $cfID "
         . " WHERE DFID = $fid ";

    if ($this->db->query($sql)) {
      return true;
    }
    return false;
  }

  /**
   * Deletes a decentral file
   * @param int $fid
   *        file id of the decentral file.
   */
  protected function _deleteDecentralFile($fid) {
    // determine the title and the path to the decentral file
    $row = $this->db->GetRow(<<<SQL
SELECT FFile, FTitle, FK_CIID, FPosition
FROM {$this->table_prefix}file
WHERE FID = $fid
SQL
    );
    $mn_file = $row["FFile"];
    $mn_title = $row["FTitle"];
    $mn_page_id = $row["FK_CIID"];
    $mn_position = $row["FPosition"];

    // delete decentral file $fid
    $result = $this->db->query(<<<SQL
DELETE FROM {$this->table_prefix}file
WHERE FID = $fid
SQL
    );

    // move following files one position up
    $sql = "UPDATE {$this->table_prefix}file "
         . 'SET FPosition = FPosition - 1 '
         . "WHERE FK_CIID = $mn_page_id "
         . "AND FPosition > $mn_position "
         . 'ORDER BY FPosition ASC ';
    $result = $this->db->query($sql);

    // delete from words file-links
    $sql = "DELETE FROM {$this->table_prefix}contentitem_words_filelink "
         . "WHERE WFFile = '$mn_file' ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    // remove the file title from the search index
    $this->_spiderDownload($mn_title, $mn_page_id, false);

    // delete decentral file from disk
    unlinkIfExists('../'.$mn_file);
  }

  /**
   * Deletes a central file
   * @param int $fid
   *        file id of the central file.
   */
  protected function _deleteCentralFile($fid) {

    // remove the title of the central file from the search index of all
    // related content items with no custom title
    $sql = 'SELECT CFTitle '
         . "FROM {$this->table_prefix}centralfile "
         . "WHERE CFID = $fid ";
    $title = $this->db->GetOne($sql);
    $sql = 'SELECT FK_CIID '
         . "FROM {$this->table_prefix}file "
         . "WHERE FK_CFID = $fid "
         . "AND COALESCE(FTitle, '') = '' "
         . 'UNION ALL '
         . 'SELECT FK_CIID '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "JOIN {$this->table_prefix}contentitem_dl_area_file ON DAID = FK_DAID "
         . "WHERE FK_CFID = $fid "
         . "AND COALESCE(DFTitle, '') = '' ";
    $related_contentitems = $this->db->GetCol($sql);
    foreach ($related_contentitems as $ciid) {
      $this->_spiderDownload($title, $ciid, false);
    }

    // determine the path to the central file
    $sql = 'SELECT CFFile '
         . "FROM {$this->table_prefix}centralfile "
         . "WHERE CFID = $fid ";
    $file = $this->db->GetOne($sql);

    // Get related content items of files table, we need the id and file position
    // to reorder other files
    $sql = " SELECT FK_CIID, FPosition "
         . " FROM {$this->table_prefix}file "
         . " WHERE FK_CFID = $fid ";
    $relatedFileContentItems = $this->db->GetAssoc($sql);
    // delete file links pointing to this central download
    $sql = "DELETE FROM {$this->table_prefix}file "
         . "WHERE FK_CFID = $fid ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);
    foreach ($relatedFileContentItems as $ciid => $position) {
      // move following files one position up
      $sql = "UPDATE {$this->table_prefix}file "
           . 'SET FPosition = FPosition - 1 '
           . "WHERE FK_CIID = $ciid "
           . "AND FPosition > $position "
           . 'ORDER BY FPosition ASC ';
      $result = $this->db->query($sql);
    }

    // Get related content items of download area files table, we need the id and file position
    // to reorder other files
    $sql = " SELECT FK_DAID, DFPosition "
         . " FROM {$this->table_prefix}contentitem_dl_area_file "
         . " WHERE FK_CFID = $fid ";
    $relatedDLAreas = $this->db->GetAssoc($sql);
    $sql = "DELETE FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE FK_CFID = $fid ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);
    foreach ($relatedDLAreas as $areaId => $position) {
      // move following files one position up
      $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
           . 'SET DFPosition = DFPosition - 1 '
           . "WHERE FK_DAID = $areaId "
           . "AND DFPosition > $position "
           . 'ORDER BY DFPosition ASC ';
      $result = $this->db->query($sql);
    }

    // Get related content items of module downloadticker files table, we need the id and file position
    // to reorder other files. It is possible to attach the same file several times, so
    // get all possbile download ticker ids.
    $sql = " SELECT DTID "
         . " FROM {$this->table_prefix}module_downloadticker "
         . " WHERE FK_CFID = $fid ";
    $result = $this->db->GetCol($sql);
    if ($result) {
      foreach ($result as $dtid) {
        ModuleDownloadTicker::deleteFilelink($dtid, $this->db, $this->table_prefix, $this->site_id);
      }
    }

    // delete central file $fid
    $sql = "DELETE FROM {$this->table_prefix}centralfile "
         . "WHERE CFID = $fid ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    // delete from words file-links
    $sql = "DELETE FROM {$this->table_prefix}contentitem_words_filelink "
         . "WHERE WFFile = '$file' ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    // delete central file from disk
    unlinkIfExists("../$file");
  }

  /**
   * Deletes a ContentItemDLArea file
   * @param int $fid
   *        file id of the ContentItemDLArea file.
   * @param int $areaId
   *        download area id of file (FK_DAID)
   * @param int $pageId
   *        content item id
   */
  protected function _deleteContentItemDLAreaFile($fid, $areaId, $pageId) {
    // determine title, filename and position of deleted file
    $sql = 'SELECT DFTitle, DFFile, DFPosition, CFTitle '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE DFID = $fid ";
    $file = $this->db->GetRow($sql);

    // delete file
    $sql = "DELETE FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE DFID = $fid ";
    $result = $this->db->query($sql);

    // move following files one position up
    $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
         . 'SET DFPosition = DFPosition - 1 '
         . "WHERE FK_DAID = $areaId "
         . "AND DFPosition > {$file['DFPosition']} "
         . 'ORDER BY DFPosition ASC ';
    $result = $this->db->query($sql);

    // delete from words file-links
    $sql = "DELETE FROM {$this->table_prefix}contentitem_words_filelink "
         . "WHERE WFFile = '".$file['DFFile']."' ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    if ($file['DFFile']) {
      unlinkIfExists('../' . $file['DFFile']);
    }

    // remove the file title from the search index of the content item
    $this->_spiderDownload(coalesce($file['DFTitle'], $file['CFTitle']), $pageId, false);
  }

  /**
   * @param int $id
   * @return array
   */
  protected function _readCentralFileById($id)
  {
    $sql = " SELECT * "
      . " FROM {$this->table_prefix}centralfile "
      . " WHERE CFID = ? ";
    return $this->db->q($sql, array((int) $id))->fetch() ?: array();
  }

  /**
   * @param int $id
   * @return array
   */
  protected function _readDecentralFileById($id)
  {
    $sql = " SELECT * "
      . " FROM {$this->table_prefix}file "
      . " WHERE FID = ? ";
    return $this->db->q($sql, array((int) $id))->fetch() ?: array();
  }

  /**
   * @param int $id
   * @return array
   */
  protected function _readContentItemDLAreaFileById($id)
  {
    $sql = " SELECT * "
      . " FROM {$this->table_prefix}contentitem_dl_area_file "
      . " WHERE DFID = ? ";
    return $this->db->q($sql, array((int) $id))->fetch() ?: array();
  }

  /**
   * Retrieve the language site label for the specified site. The language site
   * label contains the site's backend label from $_LANG['global_sites_backend_label']
   * or the title database if not set within the $_LANG. Additionally it contains
   * the language site special label $_LANG['global_sites_backend_language_site_general_label']
   * from its language parent (or itself).
   *
   * @param NavigationSite $navigationSite
   *        The site to retrieve the label for.
   *
   * @return string
   *         The backend language label.
   */
  public static function getLanguageSiteLabel(NavigationSite $navigationSite)
  {
    global $_LANG;

    // Retrieve the backend label for $navigationSite.
    $tmpID = $navigationSite->getID();
    $label = isset($_LANG['global_sites_backend_label'][$tmpID]) ?
             $_LANG['global_sites_backend_label'][$tmpID]: $navigationSite->getTitle();

    // Retrieve the language parent site of $navigationSite.
    $tmpID = $navigationSite->getLanguageParent() ?
             $navigationSite->getLanguageParent()->getID() : $tmpID;

    // Combine language parent site label with site's backend label.
    if (isset($_LANG['global_sites_backend_language_site_general_label'][$tmpID]))
    {
      $label = $_LANG['global_sites_backend_language_site_general_label'][$tmpID]
             . ' - ' . $label;
    }

    return $label;
  }

  /**
   * Returns the path to the file with the specified type and ID.
   *
   * @param string $type
   *        The type of the file (file, dlfile or centralfile).
   * @param int &$ID
   *        The ID of the file. Is changed to 0 if the file link is a dead link.
   * @param bool $real [optional] [default : true]
   *        If true, generate the real file path. If false return the file path
   *        from database.
   *
   * @return string
   *         The path to the file.
   */
  protected static function _getFilePath($type, &$ID, $real = true)
  {
    global $db;
    $tablePrefix = ConfigHelper::get('table_prefix');

    switch ($type) {
      case 'file':
        $sql = 'SELECT FFile '
             . "FROM {$tablePrefix}file "
             . "WHERE FID = $ID ";
        $filePath = $db->GetOne($sql);
        break;
      case 'dlfile':
        $sql = 'SELECT DFFile '
             . "FROM {$tablePrefix}contentitem_dl_area_file "
             . "WHERE DFID = $ID ";
        $filePath = $db->GetOne($sql);
        break;
      case 'centralfile':
        $sql = 'SELECT CFFile '
             . "FROM {$tablePrefix}centralfile "
             . "WHERE CFID = $ID ";
        $filePath = $db->GetOne($sql);
        break;
      default:
        trigger_error("Unknown file type '$type'.", E_USER_WARNING);
        $filePath = null;
    }

    if ($filePath && $real) {
      $filePath = "../" . $filePath;
    }
    if (!$filePath) {
      $ID = 0;
    }

    return $filePath;
  }

  /**
   * Get path to the large image
   *
   * @param string $prefix
   *        The content/ moduletype prefix
   * @param $image
   *        The normal image name
   *
   * @return string
   *         image path ($this->_noContentImage if there is not an image
   *         available)
   */
  protected function get_large_image($prefix,$image) {
    global $_LANG;

    if (!$image)
      return (isset($_LANG[$prefix."_no_contentimage"]) ? $_LANG[$prefix."_no_contentimage"] : $this->_noContentImage);

    $image_large = mb_substr($image,0,mb_strlen($image)-4)."-l".mb_substr($image,mb_strlen($image)-4);
    if (is_file("../".$image_large)) return "../".$image_large;
    else return "../".$image;
  }

  /**
   * Get path to the normal image
   *
   * @param string $prefix
   *        The content/ moduletype prefix
   * @param $image
   *        The normal image name
   *
   * @return string
   *         image path ($this->_noContentImage if there is not an image
   *         available)
   */
  protected function get_normal_image($prefix, $image)
  {
    global $_LANG;

    if (!$image)
      return (isset($_LANG[$prefix."_no_contentimage"]) ? $_LANG[$prefix."_no_contentimage"] : $this->_noContentImage);
    else
      return "../".$image;
  }

  /**
   * Get the large image zoom link
   *
   * @param string $prefix
   *        The content/ moduletype prefix
   * @param $image
   *        The normal image name
   *
   * @return string
   *         The zoom link for given image
   *         - $_LANG[$prefix.'_large_image_available_label'] or
   *         - $_LANG['global_large_image_available_label']
   *         or an empty string if the (large or normal) image is not available
   */
  protected function _getImageZoomLink($prefix, $image)
  {
    global $_LANG;

    if (!$image)
      return '';

    $large = $this->get_large_image($prefix, $image);

    if ($large == '../'.$image)
      return '';

    if (isset($_LANG[$prefix.'_large_image_available_label']))
      return sprintf($_LANG[$prefix.'_large_image_available_label'], $large );

    return sprintf($_LANG['global_large_image_available_label'], $large);
  }

  /**
   * Get the error message for image uploads with invalid image dimensions.
   * Each parameter has to be of type integer or an array for mutable image
   * sizes.
   *
   * @param mixed $normalWidth
   *        the normal image's width configuration values
   * @param mixed $normalHeight
   *        the normal image's height configuration values
   * @param mixed $largeWidth
   *        the large image's width configuration values
   * @param mixed $largeHeight
   *        the large image's height configuration values
   * @param string $prefix
   *        the contentitem prefix, used to get image configuration
   * @param int $imageNumber [optional] [default : 0]
   */
  protected function _getImageUploadErrorMessage($normalWidth, $normalHeight, $largeWidth, $largeHeight, $prefix = '', $imageNumber = 0)
  {
    global $_LANG;

    $langSuffix = '';
    if ($this->_configHelper->readImageConfiguration($prefix, 'ignore_image_size', $imageNumber)) {
        $langSuffix = '_min';
    }
    else if ($this->_configHelper->readImageConfiguration($prefix, 'autofit_image_upload', $imageNumber)) {
        $langSuffix = '_autofit';
    }

    // special handling for mutable image size
    if (is_array($normalWidth) || is_array($normalHeight) || is_array($largeWidth)  || is_array($largeHeight))
    {
      $normalWidth = self::_getMutableSizeArray($normalWidth);
      $normalHeight = self::_getMutableSizeArray($normalHeight);
      $largeWidth = self::_getMutableSizeArray($largeWidth);
      $largeHeight = self::_getMutableSizeArray($largeHeight);

      if ($normalWidth[0] != $normalWidth[1])
        $widthLabel = sprintf($_LANG['global_upload_image_mutable_resolution'], $normalWidth[0], $normalWidth[1]);
      else
        $widthLabel = $normalWidth[0];

      if ($normalHeight[0] != $normalHeight[1])
        $heightLabel = sprintf($_LANG['global_upload_image_mutable_resolution'], $normalHeight[0], $normalHeight[1]);
      else
        $heightLabel = $normalHeight[0];

      if ($normalWidth == $largeWidth && $normalHeight == $largeHeight)
        $message = sprintf($_LANG['global_message_upload_resolution_error'.$langSuffix], $widthLabel, $heightLabel);
      else
      {
        if ($largeWidth[0] != $largeWidth[1])
          $largeWidthLabel = sprintf($_LANG['global_upload_image_mutable_resolution'], $largeWidth[0], $largeWidth[1]);
        else
          $largeWidthLabel = $largeWidth[0];

        if ($largeHeight[0] != $largeHeight[1])
          $largeHeightLabel = sprintf($_LANG['global_upload_image_mutable_resolution'], $largeHeight[0], $largeHeight[1]);
        else
          $largeHeightLabel = $largeHeight[0];

        $message = sprintf($_LANG['global_message_upload_resolution_2sizes_error'.$langSuffix], $largeWidthLabel, $largeHeightLabel, $widthLabel, $heightLabel);
      }
    }
    // normal image size with fixed values
    else
    {
      if ($normalWidth == $largeWidth && $normalHeight == $largeHeight)
        $message = sprintf($_LANG['global_message_upload_resolution_error'.$langSuffix], $normalWidth, $normalHeight);
      else
        $message = sprintf($_LANG['global_message_upload_resolution_2sizes_error'.$langSuffix], $largeWidth, $largeHeight, $normalWidth, $normalHeight);
    }

    return Message::createFailure($message);
  }

  /**
   * Returns the disk space helper object
   *
   * @return CmsDiskSpace
   */
  public function getDiskSpaceHelper()
  {
    $limit = (int)ConfigHelper::get('m_disk_space_usage_limit');
    $sites = array();

    // only user sites and language children / parents ( FrEDWIN )
    if (ConfigHelper::get('m_disk_space_usage_local')) {
      $userSites = $this->_user->getPermittedSites();
      $sites = array();
      foreach ($userSites as $id => $val) {
        // 1. page itself
        $sites[] = $id;

        $parent = $this->_navigation->getSiteByID($id)->getLanguageParent();
        if ($parent) {
          // 2. language parent
          $sites[] = $parent->getId();

          // 3. siblings
          foreach ($parent->getLanguageChildren() as $page) {
            $sites[] = $page->getId();
          }
        }

        $children = $this->_navigation->getSiteByID($id)->getLanguageChildren();
        // 4. children
        if ($children) {
          foreach ($children as $page) {
            $sites[] = $page->getId();
          }
        }
      }

      $sites = array_unique($sites);
    }

    $helper = new CmsDiskSpace($this->db, $this->table_prefix, $limit, $sites);
    return $helper;
  }

  /**
   * Gets the uploaded image path and its attributes or
   * a default image.
   *
   * @param string $image
   *        The image path.
   * @param string $tplPrefix
   *        The prefix of the template variables.
   * @param string $imagePrefix
   *        The prefix to read the image template size.
   * @param int $imageNumber
   *        The number of the image.
   * @return array
   */
  protected function _getUploadedImageDetails($image, $tplPrefix, $imagePrefix, $imageNumber = 0)
  {
    if (!$image || $image == '../') {
      $image = $this->_noContentImage;
      $height = '';
      $width = '';
    }
    else {
      $image = '../'.$image;
      $height = $this->_configHelper->getImageTemplateSize($imagePrefix, 'height', $imageNumber);
      $width = $this->_configHelper->getImageTemplateSize($imagePrefix, 'width', $imageNumber);
    }
    if (!$imageNumber) {
      $imageNumber = '';
    }
    return array(
      $tplPrefix.'_image_src'.$imageNumber => $image,
      $tplPrefix.'_image_tpl_height'.$imageNumber => $height,
      $tplPrefix.'_image_tpl_width'.$imageNumber => $width,
    );
  }

  /**
   * Outputs the requested file as an attachement. Does nothing for invalid file.
   *
   * @param string $file
   *        the path to the file
   */
  public static function outputFile($file)
  {
    // display siteindex if file doesn't exist
    if (is_file($file)) {
      $size = filesize($file);
      header("Pragma: public"); // required
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); // required ( ie )
      header("Cache-Control: private",false); // required for certain browsers
      // header('Content-Type: application/force-download');
      header('Content-Type: application/octet-stream');
      header("Content-Disposition: attachment; filename=\"".basename($file)."\";" );
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: ".$size);

      set_time_limit(0);
      $fh = @fopen($file,'rb');
      while (!feof($fh)) {
        print(@fread($fh, 1024*8));
        ob_flush();
        flush();
      }

      exit();
    }
  }

  /**
   * @return Message
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * @param int $internalLinkPageId
   * @param array $options (optional)
   * @see InternalLinkHelper::setId()
   * @return InternalLinkHelper
   */
  public function getInternalLinkHelper($internalLinkPageId, array $options = array())
  {
    return Container::build('InternalLinkHelper')->setId($internalLinkPageId, $options);
  }

  /**
   * Returns the file URL for backend users
   *
   * @param string $filePath
   * @param int    $siteId
   *
   * @return string
   */
  protected function _fileUrlForBackendUserOnSite($filePath, $siteId = 1)
  {
    $site = $this->_navigation->getSiteByID($siteId ?: 1);

    return $site->getUrl() . str_replace('files/', 'files/'.md5($this->session->getId()).'/', trim($filePath));
  }

  /**
   * @param string $url
   * @param \Message $message
   */
  protected function _redirect($url, \Message $message = null)
  {
    $this->session->save('session_message', $message);
    header('Location: ' . $url);
    exit;
  }
}

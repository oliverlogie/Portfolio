<?php

use Core\Services\ExtendedData\Interfaces\InterfaceExtendable;

/**
 * Content Class
 *
 * $LastChangedDate: 2020-02-28 09:34:18 +0100 (Fr., 28 Feb 2020) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
  abstract class ContentItem extends ContentBase implements InterfaceExtendable
  {
    const ACTION_CONTENT = 'content';
    const ACTION_FILES = 'files';
    const ACTION_INTERNALLINKS = 'intlinks';
    const ACTION_EXTERNALLINKS = 'extlinks';
    const ACTION_COMMENTS = 'comments';
    const ACTION_STRUCTURELINKS = 'strlinks';

    /**
     * Structure links cache
     *
     * Whenever structure links are retrieved for content items they are cached
     * within this array (key=page id; value=array of structure links)
     *
     * @var array
     */
    protected static $_structureLinksCache = array();

    protected $site_id = 0;
    protected $page_id = "";
    protected $page_path = "";
    protected $action = "";

    /**
     * The general content type prefix.
     *
     * This prefix usually consists of two lower case characters. It is used
     * inside database table names, HTML input elements, configuration variables
     * and language variables.
     *
     * @var string
     */
    protected $_contentPrefix = '';
    /**
     * The prefix for the database columns.
     *
     * This prefix usually consists of one to three upper case characters.
     *
     * @var string
     */
    protected $_columnPrefix = '';
    /**
     * An array containing "ElementType => count" pairs.
     *
     * @var array
     */
    protected $_contentElements = array();
    /**
     * true if thumbnails should be generated for the images of the content type; false otherwise.
     *
     * @var bool
     */
    protected $_contentThumbnails = true;
    /**
     * The number of the image that should be used to generate the box image.
     *
     * @var int
     */
    protected $_contentBoxImage = 1;
    /**
     * true if the content type contains image titles additionally to the images; false otherwise.
     *
     * This property is ignored if the content type does not contain images.
     *
     * @var bool
     */
    protected $_contentImageTitles = true;

    /**
     * Data fields available foreach contentitem and used within SQL queries
     * when retrieving contentitem data i.e. ContentItem::_getData()
     *
     * @var array
     */
    protected $_dataFields = array('ci.CShowFromDate', 'ci.CShowUntilDate', 'ci.CTree',
                                   'ci.CDisabled', 'ci.FK_CTID', 'ci.CShare', 'ci.CBlog',
                                   'ci.FK_FUID', 'ci.CTaggable', 'ci.CPositionLocked',
                                   'ci.CContentLocked', 'ci.CDisabled', 'ci.CDisabledLocked',
                                   'ci.CSEOTitle', 'ci.CSEODescription', 'ci.CSEOKeywords',
    );

    /**
     * true if the structure links module is activated in DB
     *
     * @var bool true | false
     */
    protected $_modStructureLinksActive = false;

    /**
     * true if the current page can be linked to other pages. (Always false
     * if structure links module is inactive)
     *
     * This variable does not evaluate to true for linked items which are only
     * link targets.
     *
     * @var bool true | false
     */
    protected $_structureLinksAvailable = false;

    /**
     * Contains ids of all contentitems this contentitem is linked to.
     *
     * @var array
     */
    protected $_structureLinks = array();

    /**
     * The core content item id this content item refers to
     *
     * @var int
     */
    protected $_structureLinkReferenceId = 0;

    /**
     * May stores frontend user data of current
     * user tree
     *
     * @var array
     */
    private $_frontendUserData = null;

    /**
     * The contentitem data from ContentItem::_getData()
     *
     * @var array | null
     */
    protected $_data = null;

    /**
     * The contentitem's prefix for config variables
     *
     * @var string
     */
    protected $_configPrefix = '';

    /**
     * The string identifying the contentitem's template, e.g. ContentItemTI has
     * suffix TI for ContentItemTI1.tpl
     *
     * @var string
     */
    protected $_templateSuffix = '';

    /**
     * The NavigationPage object of contentitem
     *
     * @var NavigationPage
     */
    protected $_page;

    /**
     * The ContentItem's subelements
     *
     * @var ContentItemSubelementList
     */
    protected $_subelements = null;

    /**
     * @var CFunctionShare
     */
    protected $_functionShare;

    /**
     * @var CFunctionBlog
     */
    protected $_functionBlog;

    /**
     * True if activation status has been changed.
     *
     * @var boolean
     */
    private $_activationChanged = false;

    /**
     * Creates a ContentItem object for a specified page ID.
     *
     * @param int $siteID
     *        The ID of the site.
     * @param int $pageID
     *        The ID of the page.
     * @param Template $template
     *        The template object.
     * @param db $db
     *        The database object.
     * @param string $tablePrefix
     *        The database table prefix.
     * @param string $action
     *        The action (from the URL).
     * @param User $user
     *        The logged in user.
     * @param Session $session
     *        The current session.
     * @return ContentItem|string
     *        The created ContentItem object or a string giving a reason why
     *        the content item could not be created (can be passed to
     *        BackendRequest::redirect_page()).
     */
    public static function create($siteID, $pageID, Template $template,
                                  db $db, $tablePrefix, $action,
                                  User $user, Session $session, Navigation $navigation)
    {
      $page = $navigation->getPageByID($pageID);

      // If the page ID was not found we redirect to an error page.
      if (!$page) {
        return 'invalid_path';
      }

      // For a root page the class is "ContentItemLogical".
      if ($page->getType() == ContentType::TYPE_ROOT) {
        $contentItemClass = 'ContentItemLogical';
      }
      else {
        // For all other pages we take the class name from the database and
        // confirm the existence of the class file and class.
        $contentItemClass = $page->getContentTypeClass();
        if (!class_exists($contentItemClass, true)) {
          return 'contentclass_notfound';
        }
      }

      // Create and return an instance of the class.
      return new $contentItemClass($siteID, $pageID,
                                   $template, $db, $tablePrefix,
                                   $action, $page->getDirectPath(),
                                   $user, $session, $navigation);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Constructor                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix, $action = '', $page_path = '', User $user = null, Session $session = null, Navigation $navigation)
    {
      global $_LANG, $_LANG2, $_MODULES;

      parent::__construct($db, $table_prefix, $tpl, $user, $session, $navigation);

      $this->site_id = (int)$site_id;
      $this->page_id = (int)$page_id;
      $this->action = $action;
      $this->page_path = $page_path;
      $this->_page = $this->page_id ? $this->_navigation->getPageById($this->page_id) : null;

      $this->_functionShare = new CFunctionShare(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionBlog = new CFunctionBlog(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_readStructureLinksModuleActive();

      if ($this->page_id) {
        $custom = $this->_page->getCustomTemplate();
        if ($custom) {
          $this->_configPrefix = $this->_configPrefix . $custom;
          $this->_templateSuffix = $this->_templateSuffix . $custom;
          // include custom template's langfile
          $path = './language/' . $this->_user->getLanguage() . '-custom/content_types/lang.ContentItem' .
                  $this->_templateSuffix . '.php';
          if (is_file($path)) { require_once($path); }
        }
      }
      $this->_readSubElements();
    }

    /**
     * Creates a new content item.
     * This method should be called by a content type
     * instance of the new content item.
     *
     * @param string              $title
     *        The title of the new item.
     * @param int                 $typeId
     *        The content type id.
     * @param int                 $position
     *        The position of the item.
     * @param int                 $parentId
     *        The page id of new content item's parent.
     * @param array               $newItemGroups
     *        Group settings for login tree.
     * @param CampaignContentItem $cciModel
     *        The model with a campaign form, that
     *        should be attached to the new content item.
     *
     * @return int The new contentitem id.
     *
     * @throws Exception
     */
    public function build($title, $typeId, $position, $parentId, $newItemGroups=array(), CampaignContentItem $cciModel=null)
    {
      if (!$title || !$typeId || !$parentId || !$position) {
        throw new Exception('Can not create contentitem, need more details!');
      }

      $parent = $this->_navigation->getPageByID($parentId);
      $tree = $parent->getTree();
      // Retrieve the frontend user the current page belongs to.
      $fuid = $parent->getFrontendUserID();
      $factory = new ContentTypeFactory($this->db, $this->table_prefix);
      $ct = $factory->getById($typeId);
      $itemPath = Container::make('Core\Url\ContentItemPathGenerator')->generateChildPath($parent->getDirectPath(), $title, $this->site_id);

      $positionHelper = new PositionHelper($this->db, $this->table_prefix . 'contentitem',
          'CIID', 'CPosition', 'FK_CIID', $parentId, 'CPositionLocked');
      // we will insert the item at last position and move to requested position afterwards
      $newLastPosition = $positionHelper->getHighestPosition() + 1;

      $share = 0;
      $default = ConfigHelper::get('m_share_default');
      $default = isset($default[$this->site_id]) ? $default[$this->site_id] : $default[0];
      // if share function is available for the new content item (type
      // and path) and the default value for items is true, share the new item
      if ($this->_sharingAvailable($itemPath, $ct->getId()) && $default) {
        $share = 1;
      }

      $blog = 0;
      $default = ConfigHelper::get('m_blog_default');
      $default = isset($default[$this->site_id]) ? $default[$this->site_id] : $default[0];
      if (is_array($default)) $default = isset($default[$tree]) ? $default[$tree] : false;
      // if blog function is available for the new content item (type
      // and path) and the default value for items is true
      if ($this->_blogAvailable($itemPath, $ct->getId()) && $default) {
        $blog = 1;
      }

      $sql = " INSERT INTO {$this->table_prefix}contentitem "
           . " (CIIdentifier, CTitle, CPosition, CType, FK_CTID, FK_SID, "
           . " FK_CIID, CDisabled, CShare, CBlog, CTree, FK_FUID) "
           . " VALUES ( "
           . " '{$itemPath}','{$this->db->escape($title)}', {$newLastPosition}, "
           . " {$ct->getPageType()}, {$ct->getId()}, {$this->site_id}, "
           .   ($parentId ? $parentId : "NULL").", 1, {$share}, "
           . " {$blog}, '{$tree}', '{$fuid}') ";
      $result = $this->db->query($sql);
      $itemId = $this->db->insert_id(); // get id of the new contentitem inserted
      $this->set_create_datetime($itemId, $itemPath, $this->_user->getID());
      $positionHelper->update()->move($itemId, $position); // try to move to originally requested position

      // Create a 'mc_contentabstract' database entry for the new content item.
      // Shorttexts and images (box, navigation, bloglevel) are stored within
      // this table.
      $sql = " INSERT INTO {$this->table_prefix}contentabstract (FK_CIID) VALUES ($itemId) ";
      $this->db->query($sql);

      // Save new campaign (form)-contentitem relation
      /* @var $cciModel CampaignContentItem */
      if ($cciModel && $cciModel->id) {
        $sql = " INSERT INTO {$this->table_prefix}campaign_contentitem "
             . ' (FK_CIID, FK_CGID, CGCCampaignRecipient) '
             . ' VALUES ( '
             . " {$itemId}, {$cciModel->id}, '{$cciModel->recipient}' ) ";
        $this->db->query($sql);
      }

      // Special handling for login tree, store group settings for top level items
      if ($newItemGroups) {
        $newItemGroups = array_keys($newItemGroups);
        // Delete existing group selection for page (item)
        $sql = " DELETE FROM {$this->table_prefix}frontend_user_group_pages "
             . " WHERE FK_CIID = $itemId ";
        $this->db->query($sql);
        // Add a new table entry foreach selected group
        foreach ($newItemGroups as $groupId) {
          $sql = " INSERT INTO {$this->table_prefix}frontend_user_group_pages "
               . " ( FK_FUGID, FK_CIID ) "
               . " VALUES "
               . " ( $groupId, $itemId ) ";
          $this->db->query($sql);
        }
      }

      return $itemId;
    }

    /**
     * Duplicates the whole content item
     * with all subcontent and settings.
     *
     * @param int $parentId
     *        The id of the new parent page.
     * @return int
     *         The new contentitem id.
     */
    public function duplicate($parentId)
    {
      if (!$parentId) {
        return 0;
      }

      $cType = $this->_page->getRealContentTypeId();
      $cTypeClass = $this->_page->getRealContentTypeClass();
      $newParent = $this->_navigation->getPageByID($parentId);
      $newTitle = CopyHelper::createTitle($this->_page);
      $positionHelper = new PositionHelper($this->db, $this->table_prefix.'contentitem', 'CIID', 'CPosition',
                                           'FK_CIID', $parentId, 'CPositionLocked');
      $position = $positionHelper->getHighestPosition() + 1;
      /* @var $ci ContentItem */
      $ci = new $cTypeClass($this->site_id, $newParent->getID(), $this->tpl, $this->db,
                            $this->table_prefix, $this->action, $newParent->getPath(), $this->_user,
                            $this->session, $this->_navigation);
      // Insert new content item
      $itemId = $ci->build($newTitle, $cType, $position, $parentId);
      $this->_duplicateSettings($itemId);
      $this->_duplicateAdditionalContent($itemId);
      $this->_duplicateAbstractContent($itemId);
      $this->duplicateContent($itemId);
      $this->_subelements->duplicateContent($itemId);

      $newCi = ContentItem::create($this->site_id, $itemId, $this->tpl, $this->db,
                                   $this->table_prefix, $this->action, $this->_user,
                                   $this->session, $this->_navigation);
      // Update contentitem words tables although new content item has been disabled. Reason:
      // other EDWIN functions may want to display linked files count or disabled internal links etc.
      $newCi->spiderContent(0, true);
      if ($newCi->hasContent()) {
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . ' SET CHasContent = 1 '
             . " WHERE CIID = {$itemId}";
        $result = $this->db->query($sql);
      }

      Container::make('ContentItemLogService')->logCopied(array(
          'FK_CIID'      => $this->page_id,
          'CIIdentifier' => $this->page_path,
          'FK_UID'       => $this->_user->getID(),
      ));

      Navigation::clearCache($this->db, $this->table_prefix);

      return $itemId;
    }

    /**
     * Duplicates content.
     * It just duplicates a whole database row, so it is not possible to
     * copy a whole ContentItemCB. This function must be called for each
     * ContentItemCB Box, Biglink and so on.
     * Special handling for:
     * - Primary keys (identified via content item's column prefix + 'ID')
     * - Images
     * - FK_CIID
     *
     * @param int $pageId
     *        The page id of the new element.
     * @param int $newParentId (optional)
     *        The id of the new parent.
     * @param string $parentField (optional)
     *        The parent field name.
     * @param int $id (optional)
     *        The id of the data row to duplicate.
     *        If no id is set, the current page id will be taken.
     * @param string $idField (optional)
     *        The column id-name of the data row to duplicate.
     *        If no id field is set, FK_CIID will be taken.
     * @return int
     *         The id of the new element or 0 on error.
     */
    public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
    {
      if (!$idField) {
        $idField = 'FK_CIID';
      }
      if (!$id) {
        $id = $this->page_id;
      }
      if (!$newParentId) {
        $newParentId = $pageId;
      }
      if (!$parentField) {
        $parentField = 'FK_CIID';
      }

      $sql = " SELECT * "
           . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " WHERE {$idField} = {$id} ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);
      if (!$row) {
        return 0;
      }

      // Determine the column names for all images.
      $countImg = (isset($this->_contentElements['Image'])) ? $this->_contentElements['Image'] : 0;
      $columnImageNames = array();
      for ($i = 1; $i <= $countImg; $i++) {
        $columnName = $this->_getContentElementColumnName('Image', $countImg, $i);
        $columnImageNames[] = $columnName;
      }
      $sqlArgs = array();
      $images = array();
      foreach ($row as $field => $value) {
        // Do not use same primary key again (all tables with primary key use auto-increment)
        if ($field == $this->_columnPrefix.'ID') {
          continue;
        }
        // Store image fields with content. We copy them later, because image path names use inserted id for their name.
        else if (in_array($field, $columnImageNames) && $value) {
          $images[$field] = $value;
          continue;
        }
        else if ($field == $parentField) {
          $value = $newParentId;
        }
        $sqlArgs[$field] = "'{$this->db->escape($value)}'";
      }

      $sqlFields = implode(', ', array_keys($sqlArgs));
      $sqlValues = implode(', ', array_values($sqlArgs));

      $sql = " INSERT INTO {$this->table_prefix}contentitem_{$this->_contentPrefix} ($sqlFields) "
           . " VALUES ($sqlValues)";
      $result = $this->db->query($sql);
      $id = $this->db->insert_id();

      // Update image paths
      $sqlArgs = array();
      foreach ($images as $field => $name) {
        $name = CopyHelper::createImage($name, $pageId, $this->site_id, $newParentId, $id);
        $sqlArgs[] = "$field = '" . $this->db->escape($name) ."'";
      }

      if ($sqlArgs) {
        $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
             . "    SET ".implode(', ', $sqlArgs)." "
             . " WHERE {$this->_columnPrefix}ID = {$id} ";
        $this->db->query($sql);
      }

      return $id;
    }

    /**
     * Duplicates abstract content to given page.
     *
     * @param int $pageId
     *        The id of the page to copy the contentitem's
     *        abstract content to.
     * @return void
     */
    private function _duplicateAbstractContent($pageId)
    {
      $sql = " SELECT CShortText, CShortTextManual, CImage, CLockImage, "
           . "        CImage2, CLockImage2, CShortTextBlog, CImageBlog, "
           . "        CAdditionalImage, CAdditionalText "
           . " FROM {$this->table_prefix}contentabstract "
           . " WHERE FK_CIID = {$this->page_id} ";
      $row = $this->db->GetRow($sql);

      $shortText = ($row['CShortText']) ? "'".$this->db->escape($row['CShortText'])."'" : "NULL";
      $shortTextManual = ($row['CShortTextManual']) ? "'".$this->db->escape($row['CShortTextManual'])."'" : "NULL";
      $shortTextBlog = ($row['CShortTextBlog']) ? "'".$this->db->escape($row['CShortTextBlog'])."'" : "NULL";
      $image = ($row['CImage']) ? "'".$this->db->escape(CopyHelper::createImage($row['CImage'], $pageId, $this->site_id))."'" : "NULL";
      $image2 = ($row['CImage2']) ? "'".$this->db->escape(CopyHelper::createImage($row['CImage2'], $pageId, $this->site_id))."'" : "NULL";
      $imageBlog = ($row['CImageBlog']) ? "'".$this->db->escape(CopyHelper::createImage($row['CImageBlog'], $pageId, $this->site_id))."'" : "''";
      $additionalImage = ($row['CAdditionalImage']) ? "'".$this->db->escape(CopyHelper::createImage($row['CAdditionalImage'], $pageId, $this->site_id))."'" : "''";
      $additionalText = ($row['CAdditionalText']) ? "'".$this->db->escape($row['CAdditionalText'])."'" : "''";
      $sqlArgs = array(
        "CShortText = {$shortText}",
        "CShortTextManual = {$shortTextManual}",
        "CImage = {$image}",
        "CLockImage = {$row['CLockImage']}",
        "CImage2 = {$image2}",
        "CLockImage2 = {$row['CLockImage2']}",
        "CShortTextBlog = {$shortTextBlog}",
        "CImageBlog = {$imageBlog}",
        "CAdditionalImage = {$additionalImage}",
        "CAdditionalText = {$additionalText}",
      );

      $sql = " UPDATE {$this->table_prefix}contentabstract "
           . "    SET ".implode(', ', $sqlArgs)." "
           . " WHERE FK_CIID = $pageId ";
      $this->db->query($sql);
    }

    /**
     * Duplicates the additional content to given page.
     * - Internal links
     * - External links
     * - Downloads (only central files)
     * - Attached campaigns
     * (- Structure links: can not be copied, because structure links are unique.
     *    It would be possible to set )
     *
     * @param int $pageId
     *        The id of the page to copy the contentitems's
     *        additional content to.
     * @return void
     */
    private function _duplicateAdditionalContent($pageId)
    {
      // Attached campaigns
      $sql = " INSERT INTO {$this->table_prefix}campaign_contentitem (FK_CIID, FK_CGID, CGCCampaignRecipient) "
           . "  SELECT {$pageId}, cci2.FK_CGID, cci2.CGCCampaignRecipient "
           . "    FROM {$this->table_prefix}campaign_contentitem AS cci2 "
           . "   WHERE cci2.FK_CIID = {$this->page_id} ";
      $this->db->query($sql);

      // Internal links
      $sql = " INSERT INTO {$this->table_prefix}internallink (ILTitle, ILPosition, FK_CIID_Link, FK_CIID) "
           . "  SELECT il2.ILTitle, il2.ILPosition, il2.FK_CIID_Link, {$pageId} "
           . "    FROM {$this->table_prefix}internallink AS il2 "
           . "   WHERE il2.FK_CIID = {$this->page_id} ";
      $this->db->query($sql);

      // External links
      $sql = " INSERT INTO {$this->table_prefix}externallink (ELTitle, ELUrl, ELPosition, FK_CIID) "
           . "  SELECT el2.ELTitle, el2.ELUrl, el2.ELPosition, {$pageId} "
           . "    FROM {$this->table_prefix}externallink AS el2 "
           . "   WHERE el2.FK_CIID = {$this->page_id} ";
      $this->db->query($sql);

      // Downloads
      $sql = " SELECT FTitle, FFile, FPosition, FSize, FK_CFID "
           . " FROM {$this->table_prefix}file "
           . " WHERE FK_CIID = {$this->page_id} ";
      $res = $this->db->query($sql);
      $sqlValues = array();
      while ($row = $this->db->fetch_row($res)) {
        $fileCreated = date('Y-m-d H:i:s');
        $centralFileId = ($row['FK_CFID']) ? $row['FK_CFID'] : "NULL";
        $filePath = "NULL";
        // Ignore decentral files
        if ($row['FFile']) {
          continue;
        }
        $sqlValues[] = "('{$this->db->escape($row['FTitle'])}', {$filePath}, '{$fileCreated}', '{$row['FPosition']}', '{$row['FSize']}', $centralFileId, '{$pageId}')";
      }
      $sqlValues = implode(',', $sqlValues);
      if ($sqlValues) {
        $sql = " INSERT INTO {$this->table_prefix}file (FTitle, FFile, FCreated, FPosition, FSize, FK_CFID, FK_CIID) "
             . " VALUES {$sqlValues} ";
        $this->db->query($sql);
      }
    }

    /**
     * Duplicates the settings to given page.
     * Settings are:
     * - Show from/until date
     * - Share function
     * - Comments/Blog function
     * - Frontend user area (FK_FUID)
     * - Taggable function
     * - Frontend user group access
     *
     * These settings are not copied:
     * - Disabled
     * - DisabledLocked
     * - ContentLocked
     * - PositionLocked
     *
     * @param int $pageId
     *        The id of the page to copy the contentitem's
     *        settings to.
     * @return void
     */
    private function _duplicateSettings($pageId)
    {
      $cols = array('CShowFromDate', 'CShowUntilDate', 'CShare', 'CBlog', 'FK_FUID', 'CTaggable', 'CSEOTitle', 'CSEODescription', 'CSEOKeywords');
      $data = $this->_getData();
      $sqlCols = array();
      foreach ($cols as $col) {
        if (mb_stristr($col, 'Date') && $data[$col] == ''){
          $value = 'NULL';
        }
        else {
          $value = "'{$this->db->escape($data[$col])}'";
        }
        $sqlCols[] = "$col = $value";
      }

      // ContentItem settings
      $sql = " UPDATE {$this->table_prefix}contentitem "
           . "    SET ".implode(', ', $sqlCols)." "
           . " WHERE CIID = $pageId ";
      $this->db->query($sql);

      // Frontend user group access
      $sql = " INSERT INTO {$this->table_prefix}frontend_user_group_pages (FK_FUGID, FK_CIID) "
           . "  SELECT {$this->page_id}, fugp2.FK_CIID "
           . "    FROM {$this->table_prefix}frontend_user_group_pages AS fugp2 "
           . "   WHERE fugp2.FK_CIID = {$this->page_id} ";
      $this->db->query($sql);
    }

    /**
     * Ensures that all necessary database entries exist.
     */
    protected function _checkDatabase()
    {
      $sql = 'SELECT FK_CIID '
           . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . "WHERE FK_CIID = $this->page_id ";
      $exists = $this->db->GetOne($sql);

      $this->_createExtendedData($this->page_id);

      if ($exists) {
        return;
      }

      // insert new content item
      $sql = "INSERT INTO {$this->table_prefix}contentitem_{$this->_contentPrefix} (FK_CIID) "
           . "VALUES ($this->page_id)";
      $result = $this->db->query($sql);
    }

    /**
     * Saves all data for a content item.
     *
     * The default implementation reads all input/uploaded default content
     * elements from the user, saves them into the database, spiders all titles
     * and texts and sets the short text and image for the IB levels.
     */
    public function edit_content()
    {
      global $_LANG;

      if ($this->_hasContent()) {
        // set contentitem CHasContent field to true
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . ' SET CHasContent = 1 '
             . " WHERE CIID = {$this->page_id}";
        $result = $this->db->query($sql);
      }

      if (isset($_GET['dimg'])) {
        $this->delete_content_image($this->_contentPrefix,
            $this->_columnPrefix.'Image', intval($_GET['dimg']));
      }

      $this->_changePageActivation();

      // read old date before updating content
      $boxImageColumn = $this->_getBoxImageColumnName();
      $existingBoxImage = $this->_getBoxImage($boxImageColumn);

      // Read all content elements.
      $input['Title'] = $this->_readContentElementsTitles();
      $input['Text'] = $this->_readContentElementsTexts();
      $input['Image'] = $this->_readContentElementsImages();
      $input['Date'] = $this->_readContentElementsDate();
      $input['Share'] = $this->_readContentElementsShare();
      $input['Blog'] = $this->_readContentElementsBlog();

      $sql = $this->_buildContentElementsUpdateStatement($input);
      $result = $this->db->query($sql);

      $this->_updateExtendedData($this->page_id);

      if ($result) {
        $message = $_LANG['global_message_success'];
        if (isset($_LANG["{$this->_contentPrefix}_message_success"])) {
          $message = $_LANG["{$this->_contentPrefix}_message_success"];
        }
        $this->setMessage(Message::createSuccess($message));
      }

      // Set short text and image for IB levels.
      $boxImageChanged = false;
      if (   isset($input['Image'][$boxImageColumn])
          && $input['Image'][$boxImageColumn] != $existingBoxImage
      ) {
        $boxImageChanged = true;
      }
      $this->setShortTextAndImages($boxImageChanged, $boxImageColumn);

      // update images of linked content items
      if ($this->_structureLinksAvailable && $this->_structureLinks)
      {
        $currentPage = $this->_navigation->getCurrentPage();

        foreach ($this->_structureLinks as $pageID)
        {
          $page = $this->_navigation->getPageByID($pageID);
          $site = $page->getSite();

          $ci = ContentItem::create($site->getID(), $pageID, $this->tpl, $this->db,
                                    $this->table_prefix, $this->action, $this->_user,
                                    $this->session, $this->_navigation);
          $ci->updateStructureLinkContentImages();
        }
      }
    }

    ////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                     //
    ////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      return array();
    }

    /**
     * Returns the name of the HTML input element for a specific content element.
     *
     * @param string $type
     *        The type of the content element (lower-case, e.g. 'title').
     * @param int $count
     *        The total amount of content elements of the specified type.
     * @param int $i
     *        The number of the current content element.
     * @param int $ID
     *        The ID of the edited dataset.
     * @return string
     *        The name of the HTML input element.
     */
    private function _getContentElementInputName($type, $count, $i, $ID)
    {
      $inputName = $this->_contentPrefix;
      if ($ID) {
        $inputName .= $ID;
      }
      $inputName .= "_$type";
      if ($count > 1) {
        $inputName .= $i;
      }

      return $inputName;
    }

    /**
     * Returns the name of the database column for a specific content element.
     *
     * @param string $type
     *        The type of the content element (camel-case like in the database,
     *        e.g. 'Title' or 'ImageTitles').
     * @param int $count
     *        The total amount of content elements of the specified type.
     * @param int $i
     *        The number of the current content element.
     * @param int $ID
     *        The ID of the edited dataset.
     * @return string
     *        The name of the database column.
     */
    protected function _getContentElementColumnName($type, $count, $i)
    {
      $columnName = "{$this->_columnPrefix}{$type}";
      if ($count > 1) {
        $columnName .= $i;
      }

      return $columnName;
    }

    /**
     * Returns the name of the template variable for content element.
     *
     * @param string $type
     *        The type of the content element (camel-case like in the database,
     *        e.g. 'Title' or 'Image').
     * @param int $count
     *        The total amount of content elements of the specified type.
     * @param int $i
     *        The number of the current content element.
     *
     * @return string
     *         The template variable's name.
     */
    private function _getContentElementTemplateName($type, $count, $i)
    {
      $name = $this->_contentPrefix.'_'.mb_strtolower($type);

      if ($count > 1) {
        $name .= $i;
      }

      return $name;
    }

    protected function _changePageActivation()
    {
      $get = new Input(Input::SOURCE_GET);

      $changeActivationID = $get->readInt('changePageActivationID');
      $changeActivationTo = $get->readString('changePageActivationTo', Input::FILTER_NONE);

      if (!$changeActivationID || !$changeActivationTo) {
        return;
      }

      $pageActivator = new NavigationPageActivator(
        $this->_navigation,
        $this->_navigation->getPageByID($changeActivationID),
        $this->db,
        $this->table_prefix,
        $this->session,
        $this->tpl,
        $this->_user,
        $this->_structureLinksAvailable
      );
      $activationChanged = $pageActivator->change($changeActivationTo);

      if ($pageActivator->getMessage()) {
        $this->setMessage($pageActivator->getMessage());
      }

      // if activation changed successfully, we remove cached sitemap files
      if ($activationChanged) {
        BackendRequest::removeCachedFiles($this->site_id);
      }

      $this->_redirect("index.php?action=content&site=$this->site_id&page=$this->page_id", $this->getMessage());
    }

    /**
     * Returns the content element's label used within the template.
     *
     * @param string $type
     *        The type of the content element (camel-case like in the database,
     *        e.g. 'Title' or 'Image').
     * @param int $count
     *        The total amount of content elements of the specified type.
     * @param int $i
     *        The number of the current content element.
     *
     * @return string
     *         The content element's label.
     */
    protected function _getContentElementTemplateLabel($type, $count, $i)
    {
      global $_LANG;

      $type = mb_strtolower($type);

      $label = $_LANG["global_{$type}_label"];
      // use contenttype specific label / element position
      if (isset($_LANG["{$this->_contentPrefix}_{$type}{$i}_label"]) && $_LANG["{$this->_contentPrefix}_{$type}{$i}_label"])
        $label = $_LANG["{$this->_contentPrefix}_{$type}{$i}_label"];

      // use contenttype specific label
      else if (isset($_LANG["{$this->_contentPrefix}_{$type}_label"]) && $_LANG["{$this->_contentPrefix}_{$type}_label"])
        $label = $_LANG["{$this->_contentPrefix}_{$type}_label"];

      return $label;
    }

    /**
     * Reads all titles that were input by the user and returns them.
     *
     * The titles are automatically filtered, but not escaped for the database.
     * @param int $ID
     *        The ID of the edited dataset, this is appended to the prefix of
     *        all HTML input elements (e.g. dl_area{$ID}_title).
     * @return array
     *        Contains all titles that were entered by the user. The array index
     *        is the name of the database column, the array value is the title.
     */
    protected function _readContentElementsTitles($ID = null)
    {
      if (empty($this->_contentElements['Title'])) {
        return array();
      }
      $count = $this->_contentElements['Title'];

      $post = new Input(Input::SOURCE_POST);

      for ($i = 1; $i <= $count; $i++) {
        $inputName = $this->_getContentElementInputName('title', $count, $i, $ID);
        $columnName = $this->_getContentElementColumnName('Title', $count, $i);

        $title = $post->readString($inputName, Input::FILTER_CONTENT_TITLE);

        $input[$columnName] = $title;
      }

      return $input;
    }

    /**
     * Reads all dates that were input by the user and returns them.
     *
     * There exist two dates specifying the start date and end date
     *
     * @return array
     *        Contains dates that were entered by the user. The array index
     *        is the name of the database column, the array value is the date.
     */
    protected function _readContentElementsDate()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      if (isset($_POST["process_save"])) {

        $postDateFrom = $post->readString("date_from", Input::FILTER_PLAIN);
        $postTimeFrom = $post->readString("time_from", Input::FILTER_PLAIN);
        $postDateUntil = $post->readString("date_until", Input::FILTER_PLAIN);
        $postTimeUntil = $post->readString("time_until", Input::FILTER_PLAIN);

        // Create date strings and time strings and combine afterwards
        $dateFrom = DateHandler::getValidDate($postDateFrom, 'Y-m-d');
        $timeFrom = DateHandler::getValidDate($postTimeFrom, 'H:i:s');
        $dateUntil = DateHandler::getValidDate($postDateUntil, 'Y-m-d');
        $timeUntil = DateHandler::getValidDate($postTimeUntil, 'H:i:s');

        $datetimeFrom = DateHandler::combine($dateFrom, $timeFrom);
        $datetimeUntil = DateHandler::combine($dateUntil, $timeUntil);

        $page = $this->_navigation->getPageByID($this->page_id);
        $parentDatetimeFrom = DateHandler::getValidDateTime($page->getStartDateParent());
        $parentDatetimeUntil = DateHandler::getValidDateTime($page->getEndDateParent());

        // and return an empty array
        if (   !DateHandler::isValidDate($postDateFrom) && $postDateFrom != ''
            || !DateHandler::isValidDate($postTimeFrom) && $postTimeFrom != ''
            || !DateHandler::isValidDate($postDateUntil) && $postDateUntil != ''
            || !DateHandler::isValidDate($postTimeUntil) && $postTimeUntil != ''
        ) {
          $this->setMessage(Message::createFailure($_LANG['global_message_invalid_date']));
          return array();
        }
        if (   strtotime($datetimeFrom) > strtotime($datetimeUntil)
            && DateHandler::isValidDate($datetimeFrom) &&DateHandler::isValidDate($datetimeUntil)
        ) {
          $this->setMessage(Message::createFailure($_LANG['global_message_wrong_date']));
          return array();
        }
        // wrong start date - conflict with parent date
        if (   (DateHandler::isValidDate($datetimeFrom) && strtotime($parentDatetimeFrom) && (strtotime($parentDatetimeFrom) > strtotime($datetimeFrom)))
            || (DateHandler::isValidDate($datetimeFrom) && strtotime($parentDatetimeUntil) && (strtotime($parentDatetimeUntil) < strtotime($datetimeFrom)))
        ) {
          $this->setMessage(Message::createFailure($_LANG['global_message_conflict_parent_date']));
          return array();
        }
        // wrong end date - conflict with parent date
        if (   (DateHandler::isValidDate($datetimeUntil) && strtotime($parentDatetimeUntil) && (strtotime($parentDatetimeUntil) < strtotime($datetimeUntil)))
            || (DateHandler::isValidDate($datetimeUntil) && strtotime($parentDatetimeFrom) && (strtotime($parentDatetimeFrom) > strtotime($datetimeFrom)))
        ) {
          $this->setMessage(Message::createFailure($_LANG['global_message_conflict_parent_date']));
          return array();
        }
        else {
          $dates['CShowFromDate'] = $datetimeFrom;
          $dates['CShowUntilDate'] = $datetimeUntil;
          return $dates;
        }
      }
      else {
        return array();
      }
    }

    /**
     * Reads all texts that were input by the user and returns them.
     *
     * The texts are automatically filtered, but not escaped for the database.
     *
     * @param int $ID
     *        The ID of the edited dataset, this is appended to the prefix of
     *        all HTML input elements (e.g. dl_area{$ID}_text).
     * @return array
     *        Contains all texts that were entered by the user. The array index
     *        is the name of the database column, the array value is the text.
     */
    protected function _readContentElementsTexts($ID = null)
    {
      if (empty($this->_contentElements['Text'])) {
        return array();
      }
      $count = $this->_contentElements['Text'];

      $post = new Input(Input::SOURCE_POST);

      for ($i = 1; $i <= $count; $i++) {
        $inputName = $this->_getContentElementInputName('text', $count, $i, $ID);
        $columnName = $this->_getContentElementColumnName('Text', $count, $i);

        $title = $post->readString($inputName, Input::FILTER_CONTENT_TEXT);

        $input[$columnName] = $title;
      }

      return $input;
    }

    /**
     * Reads all existing images and stores all images uploaded by the user and returns their names.
     *
     * @param array $components
     *        The image file name components (for easy identification in the
     *        file system).
     * @param int $ID
     *        The ID of the edited dataset, this is appended to the prefix of
     *        all HTML input elements (e.g. dl_area{$ID}_image).
     * @param int $sqlID
     *        The ID used to read the existing images from the database. Usually
     *        the same as $ID. Use different value from $ID, if the updated
     *        contentitem actually is not the same as this contentitem (database
     *        id from this content item, template id from other contentitem).
     * @param string $IDColumn
     *        The name of the database column that contains the ID of the edited
     *        dataset, this is used to read the existing images from the database.
     * @return array
     *        Contains the names of all images, combining existing images from
     *        the database and images uploaded by the user.
     *        The array index is the name of the database column, the array
     *        value is the name of the image.
     */
    protected function _readContentElementsImages($components = null, $ID = null, $sqlID = null, $sqlIDColumn = null, $linkedImagesOnly = false)
    {
      if (empty($this->_contentElements['Image'])) {
        return array();
      }
      $count = $this->_contentElements['Image'];

      $input = array();

      $post = new Input(Input::SOURCE_POST);

      // Determine the column names for all images.
      $columnNames = array();
      for ($i = 1; $i <= $count; $i++) {
        $columnName = $this->_getContentElementColumnName('Image', $count, $i);
        $columnNames[] = $columnName;
      }

      // By default, if the parameters $sqlID or $IDColumn are not set, the dataset
      // is identified by the page ID inside the column FK_CIID.
      if (!$sqlID || !$sqlIDColumn) {
        $sqlID = $this->page_id;
        $sqlIDColumn = 'FK_CIID';
      }

      // Query existing images from the database.
      $sqlColumnNames = implode(', ', $columnNames);
      $sql = "SELECT $sqlColumnNames "
           . "FROM {$this->table_prefix}contentitem_$this->_contentPrefix "
           . "WHERE $sqlIDColumn = $sqlID ";
      $existingImages = $this->db->GetRow($sql);

      // Read/upload images.
      for ($i = 1; $i <= $count; $i++) {
        $inputName = $this->_getContentElementInputName('image', $count, $i, $ID);
        $columnName = $this->_getContentElementColumnName('Image', $count, $i);

        // The method is called for a linked content item (structure link), so check
        // if the user wants to update images of this (linked) content item.
        if ($linkedImagesOnly)
        {
          $linkedImage = $this->_getContentElementInputName('image_structure_link', $count, $i, $ID);
          if (!$post->exists($linkedImage)) {
            continue;
          }
        }

        if (!isset($_FILES[$inputName])) {
          continue;
        }
        // special handling in case the database value is NULL
        if ($existingImages[$columnName] === null) {
          $existingImage = ''; // buildContentElementsUpdateStatement needs string values
        }
        else {
          $existingImage = $existingImages[$columnName];
        }
        $input[$columnName] = $existingImage;
        if ($uploadedImage = $this->_storeImage($_FILES[$inputName], $existingImage,
                                                $this->getConfigPrefix(), $i, $components,
                                                $i == $this->_contentBoxImage,
                                                $this->_contentThumbnails)) {
          $input[$columnName] = $uploadedImage;
        }
      }

      // Read image titles.
      if ($this->_contentImageTitles && !$linkedImagesOnly) {
        $inputName = $this->_getContentElementInputName('image_title', 1, 1, $ID);
        $columnName = $this->_getContentElementColumnName('ImageTitles', 1, 1);

        $imageTitles = $post->readImageTitles($inputName);
        $input[$columnName] = $imageTitles;
      }

      return $input;
    }

    /**
     * Reads all internal links that were input by the user and returns them.
     *
     * @param int $ID
     *        The ID of the edited dataset, this is appended to the prefix of
     *        all HTML input elements (e.g. dl_area{$ID}_link).
     * @return array
     *        Contains all internal links that were entered by the user. The
     *        array index is the name of the database column, the array value is
     *        the ID of the linked content item.
     */
    protected function _readContentElementsLinks($ID = null)
    {
      if (empty($this->_contentElements['Link'])) {
        return array();
      }
      $count = $this->_contentElements['Link'];

      $post = new Input(Input::SOURCE_POST);

      for ($i = 1; $i <= $count; $i++) {
        $inputName = $this->_getContentElementInputName('link', $count, $i, $ID);
        $columnName = $this->_getContentElementColumnName('Link', $count, $i);

        list($link, $linkID) = $post->readContentItemLink($inputName);

        $input[$columnName] = $linkID;
      }

      return $input;
    }

    /**
     * Reads sharing state selected by the user and returns it.
     *
     * @return array - contains sharing state
     *               - 1 : sharing buttons displayed on the FE
     *               - 0 : no sharing buttons displayed on the FE
     */
    protected function _readContentElementsShare()
    {
      $post = new Input(Input::SOURCE_POST);

      if ($post->exists("share_item")) {
        return array("CShare" => 1);
      }
      else {
        $display = ConfigHelper::get('m_share_display');
        $display = isset($display[$this->site_id]) ? $display[$this->site_id] : $display[0];
        $default = ConfigHelper::get('m_share_default');
        $default = isset($default[$this->site_id]) ? $default[$this->site_id] : $default[0];
        // if checkbox isn't displayed, but sharing activated for this contentitem
        // and the default value set to true -> set CShare to 1
        if ($this->_sharingAvailable() && !$display && $default) {
          return array("CShare" => 1);
        }
        return array("CShare" => 0);
      }
    }

    /**
     * Reads blog (comments) state selected by the user and returns it.
     *
     * @return array - contains blog state
     *               - 1 : comments allowed on the FE
     *               - 0 : comments aren't allowed on the FE
     */
    protected function _readContentElementsBlog()
    {
      $post = new Input(Input::SOURCE_POST);

      if ($post->exists("blog_item")) {
        return array("CBlog" => 1);
      }
      else {
        $tree = $this->_navigation->getPageByID($this->page_id)->getTree();
        $default = ConfigHelper::get('m_blog_default');
        $default = isset($default[$this->site_id]) ? $default[$this->site_id] : $default[0];
        if (is_array($default)) $default = isset($default[$tree]) ? $default[$tree] : false;
        /**
         * If checkbox isn't displayed (as the function is not activated for user),
         * but the blog functionality activated for this contentitem
         * and the default value set to true -> set CShare to 1
         */
        if ($this->_blogAvailable() && $default &&
            !$this->_user->AvailableModule('blog', $this->site_id)) {
          return array("CBlog" => 1);
        }
        return array("CBlog" => 0);
      }
    }

    /**
     * Deletes uploaded images.
     *
     * It works by looking at an input array ($images) containing existing and
     * uploaded images as returned by _readContentElementsImages() and comparing
     * that to the database entry that only contains the existing images prior
     * to the upload. Every differing image is deleted.
     *
     * @param array $images
     *        An array containing existing and uploaded images.
     * @param int $ID
     *        The ID of the edited dataset, this is used to read the existing
     *        images from the database.
     * @param string $IDColumn
     *        The name of the database column that contains the ID of the edited
     *        dataset, this is used to read the existing images from the database.
     */
    protected function _deleteUploadedImages($images, $ID = null, $IDColumn = null)
    {
      if (empty($this->_contentElements['Image'])) {
        return;
      }
      $count = $this->_contentElements['Image'];

      $input = array();

      // Determine the column names for all images.
      $columnNames = array();
      for ($i = 1; $i <= $count; $i++) {
        $columnName = $this->_getContentElementColumnName('Image', $count, $i);
        $columnNames[] = $columnName;
      }

      // By default, if the parameters $ID or $IDColumn are not set, the dataset
      // is identified by the page ID inside the column FK_CIID.
      $sqlID = $ID;
      $sqlIDColumn = $IDColumn;
      if (!$sqlID || !$sqlIDColumn) {
        $sqlID = $this->page_id;
        $sqlIDColumn = 'FK_CIID';
      }

      // Query existing images from the database.
      $sqlColumnNames = implode(', ', $columnNames);
      $sql = "SELECT $sqlColumnNames "
           . "FROM {$this->table_prefix}contentitem_$this->_contentPrefix "
           . "WHERE $sqlIDColumn = $sqlID ";
      $existingImages = $this->db->GetRow($sql);

      // Delete images that are different from the existing images.
      foreach ($images as $imageColumn => $imageName) {
        if (   isset($existingImages[$imageColumn])
            && $existingImages[$imageColumn] != $imageName
        ) {
          unlinkIfExists($imageName);
        }
      }
    }

    /**
     * Builds the SQL statement to update the content elements.
     *
     * @param array $input
     *        Contains an array for each element type, which themselves contain
     *        "ColumnName => Value" pairs for the content elements.
     * @param int $ID
     *        The ID of the edited dataset.
     * @param string $IDColumn
     *        The name of the database column that contains the ID of the edited
     *        dataset.
     * @return string
     *        The final SQL UPDATE statement or an empty string if there is
     *        nothing to update.
     */
    protected function _buildContentElementsUpdateStatement($input, $ID = null, $IDColumn = null)
    {
      // Collect all column name/value pairs.
      foreach ($input as $inputType) {
        foreach ($inputType as $columnName => $value) {
          // fix empty dates
          if (mb_stristr($columnName, 'Date') && $value == ''){
            $value = 'NULL';
          }
          // Escape string values and enclose them in quotes.
          else if (is_string($value)) {
            $value = "'{$this->db->escape($value)}'";
          }
          $sqlValues[] = "$columnName = $value";
        }
      }

      // If there are no values we return an empty string.
      if (!isset($sqlValues) || !$sqlValues) {
        return '';
      }

      // By default, if the parameters $ID or $IDColumn are not set, the dataset
      // is identified by the page ID inside the column FK_CIID.
      $sqlID = $ID;
      $sqlIDColumn = $IDColumn;
      if (!$sqlID || !$sqlIDColumn) {
        $sqlID = $this->page_id;
        $sqlIDColumn = 'FK_CIID';
      }

      // Build and return the final UPDATE statement.
      $sqlValues = implode(', ', $sqlValues);
      $sql = "UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix}, {$this->table_prefix}contentitem "
           . "SET $sqlValues "
           . "WHERE {$this->table_prefix}contentitem_{$this->_contentPrefix}.$sqlIDColumn = $sqlID "
           . "AND CIID = $sqlID ";
      return $sql;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ////////////////////////////////////////////////////////////////////////////////////////////
    public function delete_content()
    {
      global $_LANG;

      // Determine the column names for all images.
      $count = 0;
      if (isset($this->_contentElements['Image'])) {
        $count = (int)$this->_contentElements['Image'];
      }
      $columnNames = array();
      for ($i = 1; $i <= $count; $i++) {
        $columnName = $this->_getContentElementColumnName('Image', $count, $i);
        $columnNames[] = $columnName;
      }

      // Query existing images from the database.
      $images = '';
      $sqlColumnNames = implode(', ', $columnNames);
      if ($sqlColumnNames)
      {
        $sql = "SELECT $sqlColumnNames "
             . "FROM {$this->table_prefix}contentitem_$this->_contentPrefix "
             . "WHERE FK_CIID = $this->page_id ";
        $images = $this->db->GetRow($sql);
      }

      // Delete all images.
      self::_deleteImageFiles($images);

      // Delete the special content item dataset.
      $sql = "DELETE FROM {$this->table_prefix}contentitem_$this->_contentPrefix "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);

      // Delete the standard content item dataset.
      $this->delete_content_item();

      // Return the success message.
      if ($result) {
        if (isset($_LANG["{$this->_contentPrefix}_message_deleteitem_success"])) {
          return $_LANG["{$this->_contentPrefix}_message_deleteitem_success"];
        }
        return $_LANG['global_message_deleteitem_success'];
      }
      return null;
    }

    /**
     * Show content
     *
     * @param array $params
     *        parameters required for modifying contenttype specific output
     *        - content_top
     *        - content_left
     *        - content_action_boxes
     *        - row
     *        - settings
     *          * 'no_preview'
     *          * 'tpl'
     *
     * @return array
     *         The content array
     */
    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $settings = isset($params['settings']) ? $params['settings'] : array();
      $row = (isset($params['row']) && !empty($params['row'])) ? $params['row'] : false;

      // no data provided, so load from database
      if (!$row) {
        $row = $this->_getData();
      }

      // generate template output variables for content elements
      $tplVars = array();
      foreach ($this->_contentElements as $type => $count)
      {
        if ($type == 'Image')
          $tplVars = array_merge($tplVars, $this->_loadContentElementImageOutput($row));
        else
          $tplVars = array_merge($tplVars, $this->_loadContentElementOutput($type, $row));
      }

      $disabled = ($row["CDisabled"] || !$row ? 1 : 0);
      $previewParam = (isset($settings['no_preview']) && $settings['no_preview']) ? array('no_preview') : array();

      $content_top = isset($params['content_top']) ? $params['content_top'] : $this->_getContentTop();
      $content_left = isset($params['content_left']) ? $params['content_left'] : $this->get_contentleft($disabled, $previewParam);
      $contentActionBoxes = isset($params['content_action_boxes']) ? $params['content_action_boxes'] : ($this->_getContentActionBoxes($row, empty($previewParam) ? true : false));

      $action = "index.php";
      $hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" /><input type="hidden" name="page" value="'.$this->page_id.'" /><input type="hidden" name="action" value="content" /><input type="hidden" name="action2" value="" />';

      // get loaded template name
      $tplName = isset($settings['tpl']) ? $settings['tpl'] : '';
      // template has not been loaded yet
      if (!$tplName)
      {
        $tplName = $this->_getStandardTemplateName();
        $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      }
      $this->_parseTemplateCommonParts();
      $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray($this->_contentPrefix));

      if (isset($this->_contentElements['Image']))
      {
        // foreach image: parse the template <IF>
        for ($i = 1; $i <= $this->_contentElements['Image']; $i++)
        {
          $colname = $this->_getContentElementColumnName('Image', $this->_contentElements['Image'], $i);
          $tmpImage = $row[$colname];
          $this->tpl->parse_if($tplName, "delete_image$i", $tmpImage, $this->get_delete_image($this->_contentPrefix,$i));
        }
      }

      $content = $this->tpl->parsereturn($tplName, array_merge(array (
          $this->_contentPrefix.'_action'          => $action,
          $this->_contentPrefix.'_hidden_fields'   => $hidden_fields,
          $this->_contentPrefix.'_function_label'  => isset($_LANG[$this->_contentPrefix.'_function_label']) ? $_LANG[$this->_contentPrefix.'_function_label'] : $_LANG['global_function_label'],
          $this->_contentPrefix.'_position'        => "",
          $this->_contentPrefix.'_actions_label'   => $_LANG["m_actions_label"],
          $this->_contentPrefix.'_image_alt_label' => $_LANG["m_image_alt_label"],
          'content_action_boxes'                   => $contentActionBoxes
      ), $tplVars, $this->_getContentExtensionData($this->page_id), $_LANG2[$this->_contentPrefix]));

      return array(
        "content"             => $content,
        "content_left"        => $content_left,
        "content_top"         => $content_top,
        "content_contenttype" => get_class($this)
      );
    }

    /**
     * Returns the specified image of the contentitem.
     *
     * @param int $number
     *        The image number
     *
     * @return string
     *         The image
     */
    public function getImage($number = 1)
    {
      if (empty($this->_contentElements['Image'])) {
        return '';
      }

      // Retrieve the column name for the specified image field of the current
      // content item.
      $count = $this->_contentElements['Image'];
      if ($count > 1)
      {
        // There is a valid number specified.
        if ($count >= $number) {
          $column = "{$this->_columnPrefix}Image$number";
        }
        // Return the first image.
        else {
          $column = "{$this->_columnPrefix}Image1";
        }

      }
      else {
        $column = "{$this->_columnPrefix}Image";
      }

      $sql = " SELECT $column "
           . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " WHERE FK_CIID = $this->page_id ";

      return $this->db->GetOne($sql);
    }

    /**
     * Returns the standard file name component(s).
     *
     * In the class ContentItem the standard components are site ID and page ID.
     *
     * @return array
     *        The standard file name components.
     */
    protected function _storeImageGetDefaultComponents()
    {
      return array($this->site_id, $this->page_id);
    }

    /**
     * Generates the top navigation for a content item.
     *
     * The top navigation contains these links:
     * content: the content of the page itself
     * files: files that are directly uploaded or linked to the page
     * intlinks: internal links from this page to other pages on the same site
     * extlinks: external links from this page to other sites
     * strlinks: structure links from this page to other sites
     * comments: comments for this page
     *
     * @param string $activeAction
     *        The action which should be marked as active, must be one of the
     *        ACTION_* constants inside this class.
     * @return string
     *        The complete content for the top navigation.
     */
    protected function _getContentTop($activeAction = self::ACTION_CONTENT)
    {
      global $_LANG;

      // Collect the allowed actions inside an array.
      $actions = array(
        self::ACTION_CONTENT,
        self::ACTION_FILES,
        self::ACTION_INTERNALLINKS,
        self::ACTION_EXTERNALLINKS,
        self::ACTION_COMMENTS,
        self::ACTION_STRUCTURELINKS,
      );

      // Check if the action passed in $activeAction is valid.
      if (!in_array($activeAction, $actions)) {
        throw new Exception("The ContentItem action '$activeAction' is invalid.");
      }

      $currentPage = $this->_navigation->getCurrentPage();

      // Never display top content for logical levels: LO & LP & Archive
      if ((!$currentPage->isLeaf() && !$currentPage->isOverview()) || $currentPage->isArchive())
      {
        return '';
      }

      $topData = array();

      // Fill the array $topData with inactive marked template variables for all
      // actions except for the action passed in $activeAction.
      unset($actions[array_search($activeAction, $actions)]);
      foreach ($actions as $action) {
        $topData["m_{$action}_link"] = "index.php?action=$action&amp;site=$this->site_id&amp;page=$this->page_id";
        $topData["m_{$action}_link_class"] = 'toplink_inactive';
        $topData["m_{$action}_div_class"] = "inactive";
        $topData["m_{$action}_label"] = $_LANG["m_contenttop_{$action}_label"];
      }

      // Fill the array $topData with active marked template variables for the
      // action passed in $activeAction.
      $topData["m_{$activeAction}_link"] = '#';
      $topData["m_{$activeAction}_link_class"] = 'toplink_active';
      $topData["m_{$activeAction}_div_class"] = "active";
      $topData["m_{$activeAction}_label"] = $_LANG["m_contenttop_{$activeAction}_label"];

      $comInfo = '';
      // If the blog function is displayed in the top content display further
      // information in the blog tab. Get the amount of comments on this page.
      // Additionally get the amount of new comments on this page.
      if ($this->_blogAvailable())
      {
        // Get amount of comments (new and puclished).
        $sql = ' SELECT COUNT(CID) '
             . " FROM {$this->table_prefix}comments "
             . " WHERE FK_CIID = {$this->page_id} "
             . ' AND CCanceled = 0 '
             . ' AND CDeleted = 0 ';
        $comCount = $this->db->GetOne($sql);

        // Get amount of new comments.
        $sql = ' SELECT COUNT(CID) '
             . " FROM {$this->table_prefix}comments "
             . " WHERE FK_CIID = {$this->page_id} "
             . ' AND CPublished = 0 '
             . ' AND CCanceled = 0 '
             . ' AND CDeleted = 0 ';
        $comNew = $this->db->GetOne($sql);

        // If there are new (unpublished) comments available on this page, display
        // both, the amount of new comments as well as amount of all comments.
        if ($comNew) {
          $comInfo = sprintf($_LANG['m_contenttop_comments_count_new'], $comNew, $comCount);
        }
        else {
          $comInfo = sprintf($_LANG['m_contenttop_comments_count'], $comCount);
        }
      }

      $logical = ($currentPage->isBlog() || $currentPage->isOverview() || $currentPage->isVariation()) ?
                 'logical_' : '';

      // the structure links section is only available for contentitems
      // which are within the defined link source site (usually a portal site).
      // and user with the structure links module / function permitted.
      $strlinksAvailable = $this->_modStructureLinksActive && $this->_structureLinksAvailable &&
                           $this->_user->AvailableModule('structurelinks', $this->site_id);

      // Parse the template, template for logical levels for customizing tabs.
      $this->tpl->load_tpl('content_site_top', "content_site_{$logical}top.tpl");
      $this->tpl->parse_if('content_site_top', 'content_site_top_comments',
                           $this->_blogAvailable(), array(
        'm_comments_count' => $comInfo,
      ));
      $this->tpl->parse_if('content_site_top', 'content_site_top_strlinks', $strlinksAvailable);
      return $this->tpl->parsereturn('content_site_top', $topData);
    }

    /**
     * Create the content for the left navigation box
     *
     * @param bool $disabled
     *        true if the content item is disabled, false otherwise (controls
     *        the display of the "show frontend" link)
     * @param array $param
     *        "new_item_available", "no_preview" or empty (controls the display
     *        of the "new item" and "preview" links)
     *
     * @return string
     *         contains the complete content for the left navigation box
     */
    protected function get_contentleft($disabled = true, $param = array()) {
      global $_LANG, $_MODULES;

      $m_contentleft_newitem_link = "";
      $m_contentleft_preview_link = "";
      $m_contentleft_showfrontend_link = "";
      if (in_array("new_item_available", $param)) {
        $m_contentleft_newitem_link = $_LANG["m_contentleft_newitem_link"];
      }
      if (!in_array("no_preview", $param)) {
        $m_contentleft_preview_link = $_LANG["m_contentleft_preview_link"];
      }
      if (!$disabled) {
        $m_contentleft_showfrontend_link = sprintf($_LANG["m_contentleft_showfrontend_link"],
             $this->_navigation->getCurrentSite()->getUrl().$this->page_path);
      }

      $userTreeData = $this->_getUserTreeData();
      if (!$userTreeData) {
        $userTreeData = array();
      }
      $searchModule = new ModuleSearch(array(), $this->site_id, $this->tpl, $this->db, $this->table_prefix,
                                       '', '', $this->_user, $this->session, $this->_navigation);
      $this->tpl->load_tpl('content_site_left', 'content_site_left_sub.tpl');
      $this->tpl->parse_if('content_site_left', 'menu_user_info', $userTreeData, array_merge($userTreeData, array(
        'si_contentleft_user_link' => sprintf($_LANG['m_contentleft_showfrontend_user_link'], (isset($userTreeData['lo_ut_area_fu_id'])) ? $userTreeData['lo_ut_area_fu_id'] : 0),
      )));
      return $this->tpl->parsereturn('content_site_left', array (
        'm_contentleft_newitem_link' => $m_contentleft_newitem_link,
        'm_contentleft_preview_link' => $m_contentleft_preview_link,
        'm_contentleft_showfrontend_link' => $m_contentleft_showfrontend_link,
        'm_contentleft_sl_box' => $this->_getStructureLinksBox(),
        'm_contentleft_search' => $searchModule->getSearchBox(),
      ));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content Item (IntLinks,Extlinks,Downloads) & Check Above Logical Items         //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function delete_content_item($level = false)
    {
      $sql = " SELECT ci.FK_CIID, CPosition, CIIdentifier, ca.CImage, "
           . "        ca.CImage2, ca.CAdditionalImage "
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentabstract ca "
           . '      ON CIID = ca.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      $row = $this->db->GetRow($sql);
      if (!$row) {
        return false;
      }

      $parentID = $row['FK_CIID'];
      $tmp_fk_ciid = $row["FK_CIID"];
      $tmp_image = $row["CImage"];
      $tmpImage2 = $row["CImage2"];
      $additionalImage = $row['CAdditionalImage'];
      $tmp_position = $row["CPosition"];

      // Before deleting content item, ensure to move it to last position using
      // the position helper, which manages locked positions
      // If the item itself or the item at last position is locked to it's position,
      // moving the item will fail, so we have to move it manually as a fallback.
      $positionHelper = new PositionHelper($this->db, $this->table_prefix . 'contentitem',
          'CIID', 'CPosition', 'FK_CIID', $parentID, 'CPositionLocked');
      if ($positionHelper->getHighestPosition() != $tmp_position) {
        if (!$positionHelper->move($this->page_id, $positionHelper->getHighestPosition())) {
          $sql = " UPDATE {$this->table_prefix}contentitem "
               . " SET CPosition = " . ($positionHelper->getHighestPosition() + 1). " "
               . " WHERE CIID = $this->page_id ";
          $this->db->query($sql);

          $sql = " UPDATE {$this->table_prefix}contentitem "
               . " SET CPosition = CPosition - 1 "
               . " WHERE CPosition > $tmp_position "
               . " AND FK_CIID = $parentID "
               . " ORDER BY CPosition ASC ";
          $this->db->query($sql);
        }
      }

      if (!$level){ // delete common items only for content, not for levels
        // remove downloads for current content item
        $sql = 'SELECT FFile '
             . "FROM {$this->table_prefix}file "
             . "WHERE FK_CIID = $this->page_id "
             . 'AND FFile IS NOT NULL ';
        $result = $this->db->query($sql);
        while ($row = $this->db->fetch_row($result)){
          unlinkIfExists('../' . $row['FFile']);
        }
        $this->db->free_result($result);
        $sql = "DELETE FROM {$this->table_prefix}file "
             . "WHERE FK_CIID = $this->page_id ";
        $this->db->query($sql);

        // remove internal links for current item
        $this->db->query("DELETE FROM ".$this->table_prefix."internallink WHERE FK_CIID=".$this->page_id);

        // remove external links for current item
        $this->db->query("DELETE FROM ".$this->table_prefix."externallink WHERE FK_CIID=".$this->page_id);

        unlinkIfExists("../$tmp_image");
        unlinkIfExists("../$tmpImage2");
      }
      else{
        // delete imagebox image for level, if set
        unlinkIfExists("../$tmp_image");
        unlinkIfExists("../$tmpImage2");
        // delete optional data for imagebox-levels
        $sql = "DELETE FROM {$this->table_prefix}contentitem_ib "
             . "WHERE FK_CIID = $this->page_id ";
        $this->db->query($sql);
      }
      unlinkIfExists("../$additionalImage");

      // Walk the tree up and disable above levels if necessary.
      do {
        // If no enabled item is left under the parent we disable the parent.
        $sql = 'SELECT COUNT(CIID) '
             . "FROM {$this->table_prefix}contentitem "
             . "WHERE FK_CIID = $tmp_fk_ciid "
             . 'AND CDisabled = 0 '
             . "AND CIID != $this->page_id ";
        $itemCountEnabled = $this->db->GetOne($sql);
        if ($itemCountEnabled == 0) {
          // disable item (if it isn't a root page)
          $sql = " UPDATE {$this->table_prefix}contentitem "
               . " SET CDisabled = 1 "
               . " WHERE CIID = $tmp_fk_ciid "
               . " AND CType != ".ContentType::TYPE_ROOT." ";
          $this->db->query($sql);

          // Walk up the tree.
          $sql = 'SELECT FK_CIID '
               . "FROM {$this->table_prefix}contentitem "
               . "WHERE CIID = $tmp_fk_ciid "
               . "AND CType != ".ContentType::TYPE_ROOT." ";
          $tmp_fk_ciid = $this->db->GetOne($sql);
        }
      } while (!$itemCountEnabled && $tmp_fk_ciid);

      // delete search index for content item
      $this->db->query("DELETE FROM ".$this->table_prefix."contentitem_words WHERE FK_CIID=".$this->page_id);
      // delete text content file links
      $this->db->query("DELETE FROM ".$this->table_prefix."contentitem_words_filelink WHERE FK_CIID=".$this->page_id);
      // delete text content internal links
      $this->db->query("DELETE FROM ".$this->table_prefix."contentitem_words_internallink WHERE FK_CIID=".$this->page_id);

      // delete comments
      $this->db->query('DELETE FROM '.$this->table_prefix.'comments WHERE FK_CIID = '.$this->page_id);
      // delete structure links
      $this->db->query('DELETE FROM '.$this->table_prefix.'structurelink WHERE FK_CIID = '.$this->page_id.' OR FK_CIID_Link = '.$this->page_id);
      // remove content abstract
      $this->db->query("DELETE FROM ".$this->table_prefix."contentabstract WHERE FK_CIID=".$this->page_id);
      // remove content item
      $this->db->query("DELETE FROM ".$this->table_prefix."contentitem WHERE CIID=".$this->page_id);
      // delete attached campaigns/forms
      $this->db->query("DELETE FROM ".$this->table_prefix."campaign_contentitem WHERE FK_CIID=".$this->page_id);
      // delete attached employee boxes
      $this->db->query("DELETE FROM ".$this->table_prefix."module_employee_assignment WHERE FK_CIID=".$this->page_id);
      // delete attached global areas
      $this->db->query("DELETE FROM ".$this->table_prefix."module_globalareamgmt_assignment WHERE FK_CIID=".$this->page_id);
      // delete attached medialibrary boxes
      $this->db->query("DELETE FROM ".$this->table_prefix."module_medialibrary_assignment WHERE FK_CIID=".$this->page_id);
      // delete attached sideboxes
      $this->db->query("DELETE FROM ".$this->table_prefix."module_sidebox_assignment WHERE FK_CIID=".$this->page_id);

      $this->_deleteExtendedData($this->page_id);

      Container::make('ContentItemLogService')->logDeleted(array(
          'FK_CIID'      => $this->page_id,
          'CIIdentifier' => $this->page_path,
          'FK_UID'       => $this->_user->getID(),
      ));
    }

    /**
     * Set shorten text and images for imageboxes and levels with image navigation
     * ( IB, IP, PB, LP ) as well as blog levels ( BE )
     *
     * @param int $changeImage [optional] [default : 0]
     *        set to 1 if images should be changed
     * @param string $fieldnameText [optional]
     *        the column name of the text field
     * @param string $fieldnameImage [optional]
     *        the column name of the Image field
     *
     */
    public function setShortTextAndImages($changeImage = 0, $fieldnameImage = null, $fieldnameText = null)
    {
      $this->_setBlogTextAndImage();

      // retrieve field names, if not set
      if ($fieldnameText === null) $fieldnameText = $this->_getBoxTextColumnName();
      if ($fieldnameImage === null) $fieldnameImage = $this->_getBoxImageColumnName();

      $table = "contentitem_".$this->_contentPrefix;
      $sqlImage = ($fieldnameImage ? $fieldnameImage : 'NULL') . ' AS Image';
      $sql = " SELECT ci.FK_CIID, ci.CType, $sqlImage, $fieldnameText AS Text, ci.CPosition "
        . " FROM {$this->table_prefix}$table st "
        . " JOIN {$this->table_prefix}contentitem ci "
        . '      ON ci.CIID = st.FK_CIID '
        . " JOIN {$this->table_prefix}contentabstract ca "
        . '      ON ci.CIID = ca.FK_CIID '
        . " WHERE st.FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);
      $this->db->free_result($result);
      $tmpShortText = StringHelper::setText($row['Text'])
        ->purge(ConfigHelper::get('be_allowed_html_level3'))
        ->truncate(ConfigHelper::get('ci_shorttext_maxlength'),
          ConfigHelper::get('ci_shorttext_aftertext'),
          ConfigHelper::get('shorttext_cut_exact', 'ci'))
        ->getText();
      $image = $row["Image"];
      $tmpBoxImage = '';
      $tmpBoxImage2 = '';

      if ($image) {
        $tmpBoxImage = mb_substr($image,0,mb_strlen($image)-mb_strlen(mb_strrchr($image,".")))
          ."-b".mb_strrchr($image,".");
        $tmpBoxImage2 = mb_substr($image,0,mb_strlen($image)-mb_strlen(mb_strrchr($image,".")))
          ."-b2".mb_strrchr($image,".");
      }

      // update current page data
      $currentPage = $this->_navigation->getPageByID($this->page_id);
      if ($changeImage) {
        if (mb_substr($currentPage->getImage(), -6, 2) != '-b' && !$currentPage->isImageLocked()) {
          unlinkIfExists("../{$currentPage->getImage()}");
        }

        if (mb_substr($currentPage->getImage2(), -6, 2) != '-b2' && !$currentPage->isImage2Locked()) {
          unlinkIfExists("../{$currentPage->getImage2()}");
        }
      }

      $sql = " UPDATE {$this->table_prefix}contentabstract "
        . " SET CShortText = '".$this->db->escape($tmpShortText)."' "
        . (($changeImage && !$currentPage->isImageLocked()) ? ", CImage = '$tmpBoxImage' " : "")
        . (($changeImage && !$currentPage->isImage2Locked()) ? ", CImage2 = '$tmpBoxImage2' " : "")
        . " WHERE FK_CIID = {$currentPage->getID()} ";
      $this->db->query($sql);

      // update parent page, that uses shorttext(s) and image from current page
      // this is for pages under overviews (IB, IP, PB) and the LP imagenav
      // level.
      $page = $currentPage;

      while (   $page
             && $page->getParent()
             && !$page->getParent()->isOverview()
             && $page->getParent()->getContentTypeId() != 78
             && $page->getParent()->getPreferredChild() == $currentPage
      ) {
        $page = $page->getParent();
      }

      // if page doesn't exist or it is the root page there doesn't exist a level
      // to set data for, so there's nothing left to do
      if (!$page || !$page->getParent() || $page->getID() == $currentPage->getID()) {
        return;
      }

      // update parent page data
      if ($changeImage) {
        if (mb_substr($page->getImage(), -6, 2) != '-b' && !$page->isImageLocked()) {
          unlinkIfExists("../{$page->getImage()}");
        }

        if (mb_substr($page->getImage2(), -6, 2) != '-b2' && !$page->isImage2Locked()) {
          unlinkIfExists("../{$page->getImage2()}");
        }
      }

      $sql = " UPDATE {$this->table_prefix}contentabstract "
        . " SET CShortText = '".$this->db->escape($tmpShortText)."' "
        . (($changeImage && !$page->isImageLocked()) ? ", CImage = '$tmpBoxImage' " : "")
        . (($changeImage && !$page->isImage2Locked()) ? ", CImage2 = '$tmpBoxImage2' " : "")
        . " WHERE FK_CIID = {$page->getID()} ";
      $this->db->query($sql);
    }

    /**
     * Create word index for search only if content changed
     *
     * @param boolean $no_changedate
     *        default: false
     *        if true the entry for the change date isn't updated
     * @param boolean $force
     *        If true, content will be spidered also if content-change could not
     *        be determined.
     */
    public function spiderContent($no_changedate = 0, $force = false)
    {
      // return if no changes happened
      if (!$this->hasContentChanged() || $force) {
        return;
      }

      // set change datetime for content item
      if (!$no_changedate) {
        $this->set_change_datetime();
      }

      $titles = $this->getTitles();
      $texts = $this->getTexts(true);
      $imageTitles = $this->getImageTitles();

      $content_words = array();

      // spider content title
      $temp = $this->db->GetOne("SELECT CTitle FROM {$this->table_prefix}contentitem WHERE CIID = $this->page_id AND FK_SID = $this->site_id");
      $temp = self::_parseForSpider($temp);
      foreach ($temp as $word){
        if (isset($content_words[$word]["content_title"])) $content_words[$word]["content_title"]++;
        else $content_words[$word]["content_title"] = 1;
      }

      // spider titles
      foreach ($titles as $content){
        $temp = self::_parseForSpider($content);
        foreach ($temp as $word){
          if (isset($content_words[$word]["title"])) $content_words[$word]["title"]++;
          else $content_words[$word]["title"] = 1;
        }
      }

      // spider texts
      foreach ($texts as $content){
        $temp = self::_parseForSpider($content);
        foreach ($temp as $word){
          if (isset($content_words[$word]["text"])) $content_words[$word]["text"]++;
          else $content_words[$word]["text"] = 1;
        }
      }

      // spider files
      $downloads = array();
      $sql = 'SELECT FTitle AS Title1, CFTitle AS Title2 '
           . "FROM {$this->table_prefix}file "
           . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
           . "WHERE FK_CIID = $this->page_id "
           . 'AND ( '
           . '  FFile IS NOT NULL OR '
           . '  CFFile IS NOT NULL '
           . ') '
           . 'UNION ALL '
           . 'SELECT DFTitle AS Title1, CFTitle AS Title2 '
           . "FROM {$this->table_prefix}contentitem_dl_area_file "
           . "JOIN {$this->table_prefix}contentitem_dl_area ON FK_DAID = DAID "
           . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
           . "WHERE FK_CIID = $this->page_id "
           . 'AND ( '
           . '  DFFile IS NOT NULL OR '
           . '  CFFile IS NOT NULL '
           . ') ';
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)){
        $temp = self::_parseForSpider(coalesce($row['Title1'], $row['Title2']));
        foreach ($temp as $word){
          if (isset($content_words[$word]["download"])) $content_words[$word]["download"]++;
          else $content_words[$word]["download"] = 1;
        }
      }
      $this->db->free_result($result);

      // spider image titles
      foreach ($imageTitles as $content)
      {
        $temp = self::_parseForSpider($content);
        foreach ($temp as $word)
        {
          if (isset($content_words[$word]['image'])) {
            $content_words[$word]['image']++;
          }
          else {
            $content_words[$word]['image'] = 1;
          }
        }
      }

      // delete old words from content item
      $sql = " DELETE FROM {$this->table_prefix}contentitem_words "
           . ' WHERE FK_CIID = ' . $this->page_id;
      $result = $this->db->query($sql);

      // insert words into search index
      $values = array();
      foreach ($content_words as $word => $count)
      {
        $count_content_title = isset($count["content_title"]) ? $count["content_title"] : 0;
        $count_title = isset($count["title"]) ? $count["title"] : 0;
        $count_text = isset($count["text"]) ? $count["text"] : 0;
        $count_download = isset($count["download"]) ? $count["download"] : 0;
        $countImage = isset($count['image']) ? $count['image'] : 0;
        $values[] = "($this->page_id, '$word', $count_content_title, $count_title, $count_text, $count_download, $countImage )";
      }
      if ($values) {
        $sql = " INSERT INTO {$this->table_prefix}contentitem_words "
             . ' (FK_CIID, WWord, WContentTitleCount, WTitleCount, WTextCount, '
             . '  WDownloadCount, WImageCount) '
             . ' VALUES '.implode(',', $values);
        $this->db->query($sql);
      }

      // delete old internal links from link index
      $sql = " DELETE FROM {$this->table_prefix}contentitem_words_internallink "
           . " WHERE FK_CIID = $this->page_id ";
      $this->db->query($sql);

      // insert internal links into internal link index
      $internal = array_unique($this->_getTextInternalLinks(implode(' ', $texts)));
      if (!empty($internal)) // internal links available
      {
        $sql = " INSERT INTO {$this->table_prefix}contentitem_words_internallink "
             . " (FK_CIID, FK_CIID_Link) VALUES ";
        foreach ($internal as $id) {
          $sqlPart[] = "($this->page_id, $id)";
        }
        $sql .= implode(',', $sqlPart);
        $this->db->query($sql);
      }

      // delete file links from file index
      $sql = " DELETE FROM {$this->table_prefix}contentitem_words_filelink "
           . " WHERE FK_CIID = $this->page_id ";
      $this->db->query($sql);

      // insert file links into file index
      $fileIds = $this->_getTextFileLinks(implode(' ', $texts));
      $paths = array();
      foreach ($fileIds as $type => $val) // retrieve all file paths
      {
        foreach ($val as $id) {
          $paths[] = ContentBase::_getFilePath($type, $id, false); // file path from db
        }
      }

      // remove empty array values as array_count_values() produces a warning
      // for emtpy values
      foreach ($paths as $key => $val){
        if (!$val) unset($paths[$key]);
      }

      $paths = array_count_values($paths);
      if (!empty($paths))
      {
        $sqlPart = array();
        $sql = " INSERT INTO {$this->table_prefix}contentitem_words_filelink "
             . " (FK_CIID, WFFile, WFTextCount) VALUES ";
        foreach ($paths as $path => $count) {
          $sqlPart[] = "( $this->page_id, '{$this->db->escape($path)}', $count )";
        }
        $sql .= implode(',', $sqlPart);
        $this->db->query($sql);
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Set create datetime                                                                   //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function set_create_datetime($page_id, $page_path, $user_id)
    {
      $now = date('Y-m-d H:i:s');
      $sql = " UPDATE {$this->table_prefix}contentitem "
           . " SET CCreateDateTime = '$now', "
           . "     CChangeDateTime = '$now' "
           . " WHERE CIID = '$page_id' ";
      $result = $this->db->query($sql);

      Container::make('ContentItemLogService')->logCreated(array(
          'FK_CIID'      => $page_id,
          'CIIdentifier' => $page_path,
          'FK_UID'       => $user_id,
      ));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Set change datetime                                                                   //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function set_change_datetime()
    {
      $now = date('Y-m-d H:i:s');
      $sql = " UPDATE {$this->table_prefix}contentitem "
           . " SET CChangeDateTime = '$now' "
           . " WHERE CIID = '{$this->page_id}' ";
      $result = $this->db->query($sql);

      Container::make('ContentItemLogService')->logUpdated(array(
          'FK_CIID'      => $this->page_id,
          'CIIdentifier' => $this->page_path,
          'FK_UID'       => $this->_user->getID(),
      ));
    }

    /**
     * Converts the title for a subsidiary content item to a full URL path.
     *
     * This method is called if a new content item is created or if the title
     * of an existing content item is changed.
     * The purpose is the generation of an SEO URL matching the title,
     * including optimization for german characters 'ä', 'ö', 'ü' and 'ß'.
     *
     * @deprecated Use Core\Url\ContentItemPathGenerator instead.
     * @param Db $db
     *        The database object
     * @param string $tablePrefix
     *        The table prefix
     * @param string $parentPath
     *        The path (CIIdentifier) of parent page of the content item to
     *        generate the child path for
     * @param string $title
     *        The new title for the (new or existing) content item.
     * @param int $siteId
     *        The site id
     * @param int $pageId
     *        The ID of the existing content item (if a title is changed)
     *        or 0 if the title is for a newly created content item.
     *
     * @return string
     *         The full URL path of the content item matching the new title.
     */
    public static function generateChildPath(Db $db, $tablePrefix, $parentPath, $title, $siteId, $pageId = 0)
    {
      return Container::make('Core\Url\ContentItemPathGenerator')->generateChildPath(
        $parentPath,
        $title,
        $siteId,
        $pageId
      );
    }

    /**
     * Returns an array with preview images.
     *
     * @param array $images
     *        An array with one entry per image where the index is the name of
     *        the database column and the value is the name of the HTML upload
     *        input element.
     *
     * @return array
     *        An array with the same number of entries as in $images, the index
     *        is the value of $images (the name of the HTML upload input
     *        element) and the value is the path to the preview image (either
     *        the path to an existing image as stored in the database, prefixed
     *        with "../", or the path to the temporarily stored uploaded image).
     */
    protected function _createPreviewImages($images)
    {
      global $_LANG;

      $image_src = array();

      // Delete old temporary images.
      if ($handle = opendir("img/preview")) {
        while (false !== ($file = readdir($handle))) {
          $extension = pathinfo($file);
          $extension = $extension['extension'];
          if ($extension == 'gif' || $extension == 'jpg' || $extension == 'png') {
            unlinkIfExists("img/preview/$file");
          }
        }
      }

      // Read existing images from the database.
      $sqlColumns = implode(', ', array_keys($images));
      $sql = " SELECT $sqlColumns "
           . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " WHERE FK_CIID = $this->page_id ";
      $row = $this->db->GetRow($sql);

      $count = count($images);
      $i = 0;
      foreach ($images as $fieldname_db => $fieldname) {
        $i++;
        $imageNumber = $images ? $i : 0;
        // By default we assume there is no image, so we initialize the
        // return value with null. If there is an image upload or a path in the
        // database it gets set to the correct value later.
        $image_src[$fieldname] = null;

        // There is an existing image inside the database.
        if (!empty($row[$fieldname_db])) {
          $image_src[$fieldname] = '../' . $row[$fieldname_db];
        }

        // Initialize the image variables with null.
        $normalImage = null;
        $largeImage = null;

        // Get the path to the source image.
        // We need to check if file is available in PHP $_FILES array, because in CI IM
        // it is not possible to add images after the object has been set to sold.
        $sourceImagePath = (isset($_FILES[$fieldname])) ? $this->_storeImageGetSourcePath($_FILES[$fieldname]) : null;
        if (!$sourceImagePath) {
          continue;
        }

        // Load the source image.
        try {
          $originalImage = CmsImageFactory::create($sourceImagePath);
        }
        catch(Exception $e) {
          $this->setMessage(Message::createFailure($e->getMessage()));
          return null;
        }

        // Look if the image has a valid size.
        $originalImageSize = $this->_storeImageGetSize($originalImage, $this->getConfigPrefix(), $imageNumber);
        $ignoreImageSize = $this->_configHelper->readImageConfiguration($this->getConfigPrefix(), 'ignore_image_size', $imageNumber);
        $resizedImage = null;
        // If image size should be ignored, user is not forced to upload an image with configured sizes.
        // We have to scale the image, if it doesn't fit with configured size
        if ($ignoreImageSize && $originalImageSize == self::IMAGESIZE_INVALID) {
          $resizedImage = $this->_getResizedImageFromIgnoredImageSize($originalImage, $this->getConfigPrefix());
        }
        $image = ($resizedImage) ? $resizedImage : $originalImage;
        $badSize = false;
        switch ($originalImageSize) {
          case self::IMAGESIZE_LARGE:
            $largeImage = $image;
            break;
          case self::IMAGESIZE_NORMAL:
            $normalImage = $image;
            break;
          case self::IMAGESIZE_INVALID:
          default:
            if (!$resizedImage)
              $badSize = true;
            else {
              $largeImage = $image;
            }
        }

        if ($badSize) {
          continue;
        }

        // Look if the image has a valid type and determine the extension.
        $originalImageType = $image->getType();
        $newImageExtension = '';
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
            $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
            return null;
        }

        // Determine the temporary filename for the image.
        $newImageName = "img/preview/tmp_{$this->_configPrefix}_$this->page_id-$i";
        $timestamp = time() % 1000;
        $newImageName .= "_$timestamp";

        // All these resizing and watermarking operations could fail if the
        // configuration was invalid, so we put a try/catch block around it.
        try {
          // If a large image was uploaded we create a normal image from it.
          if ($largeImage) {
            $normalImage = $this->_storeImageCreateNormalFromLargeImage($largeImage, $this->getConfigPrefix(), $imageNumber);
          }

          // Apply watermark to the normal and the large image.
          $this->_storeImageApplyWatermark($normalImage, $largeImage, $this->getConfigPrefix(), $imageNumber);
        }
        catch (Exception $e) {
          continue;
        }

        // Save all images.
        if ($originalImageType == IMAGETYPE_GIF) {
          $normalImage->writeGif("$newImageName$newImageExtension", 0644);
          if ($largeImage) {
            $largeImage->writeGif("$newImageName-l$newImageExtension", 0644);
          }
        }
        else if ($originalImageType == IMAGETYPE_JPEG) {
          $normalImage->writeJpeg("$newImageName$newImageExtension", 0644);
          if ($largeImage) {
            $largeImage->writeJpeg("$newImageName-l$newImageExtension", 0644);
          }
        } else {
          $normalImage->writePng("$newImageName$newImageExtension", 0644);
          if ($largeImage) {
            $largeImage->writePng("$newImageName-l$newImageExtension", 0644);
          }
        }

        $image_src[$fieldname] = "$newImageName$newImageExtension";
      }

      return $image_src;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Image from Content Item                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function delete_content_image($contenttype, $col, $number)
    {
      $this->_deleteContentImage($number, $contenttype, $col);

      header("Location: index.php?action=content&site=$this->site_id&page=$this->page_id");
      exit();
    }

    /**
     * Deletes a content image with given number.
     * In previous releases ContentItem::delete_content_image
     * Sometimes we do no want a redirect after an image has been deleted,
     * therefore this function was coded
     *
     * @param int $number
     *        The number of the image to delete.
     * @param string $contentPrefix [optional]
     *        If no special content prefix is set, ContentItem::_contentPrefix
     *        will be used.
     * @param string $columnPrefix [optional]
     *        If no special column prefix is set, ContentItem::_columnPrefix
     *        will be used.
     */
    protected function _deleteContentImage($number, $contentPrefix = '', $columnPrefix = '')
    {
      // Contentitems with one single image do not have the image number
      // within the image column name (i.e. CCImage)
      if (((int)$number == 1) && (isset($this->_contentElements['Image']) && ($this->_contentElements['Image'] == 1))) {
        $number = '';
      }
      $contentPrefix = ($contentPrefix) ? $contentPrefix : $this->_contentPrefix;
      $columnPrefix = ($columnPrefix) ? $columnPrefix : $this->_columnPrefix;
      $sql = " SELECT $columnPrefix$number "
           . " FROM {$this->table_prefix}contentitem_$contentPrefix "
           . " WHERE FK_CIID = $this->page_id ";
      $image = $this->db->GetOne($sql);

      if ($image)
      {
        $sql = " UPDATE {$this->table_prefix}contentitem_$contentPrefix "
             . " SET $columnPrefix$number = '' "
             . " WHERE FK_CIID = $this->page_id ";
        $this->db->query($sql);

        // the first image should be deleted, so we check the images for the
        // current page as well as all parents and delete the image source
        if ($number < 2) {

          $page = $this->_navigation->getPageByID($this->page_id);

          $boxImage = mb_substr($image, 0, -4) . "-b" . mb_substr($image, -4);
          $boxImage2 = mb_substr($image, 0, -4) . "-b2" . mb_substr($image, -4);

          do {
            // We do not check for locked images, because locked images are
            // renamed, so they can not match the $image name with -b or -b2
            // A check for equality is sufficient

            if ($page->getImage() === $boxImage) {
              $sql = " UPDATE {$this->table_prefix}contentabstract "
                   . " SET CImage = '' "
                   . " WHERE FK_CIID = {$page->getID()} ";
              $this->db->query($sql);
            }

            if ($page->getImage2() === $boxImage) {
              $sql = " UPDATE {$this->table_prefix}contentabstract "
                   . " SET CImage2 = '' "
                   . " WHERE FK_CIID = {$page->getID()} ";
              $this->db->query($sql);
            }
          }
          while ($page = $page->getParent());

          unlinkIfExists('../' . $boxImage);
          unlinkIfExists('../' . $boxImage2);
        }

        unlinkIfExists('../'.$image);
        unlinkIfExists('../'.mb_substr($image, 0, -4) . "-be" . mb_substr($image, -4));
        unlinkIfExists('../'.mb_substr($image, 0, -4) . "-l" . mb_substr($image, -4));
        unlinkIfExists('../'.mb_substr($image, 0, -4) . "-th" . mb_substr($image, -4));
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Path to Thumbnail Image if available                                              //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function get_thumb_image($image)
    {
      $image_thumb = mb_substr($image, 0, -4) . "-th" . mb_substr($image, -4);
      if (is_file("../$image_thumb")) {
        return "../$image_thumb";
      } else {
        return "../$image";
      }
    }

    /**
     * Determines if for a given image an according large image is available.
     *
     * @param string $filePath
     *        The physical path to an image (e.g. "../img/ci_image_1-1_99.jpg").
     * @return boolean
     *        true if a large image is available, false otherwise.
     */
    protected function _hasLargeImage($filePath)
    {
      if (!$filePath) {
        return false;
      }

      $pathInfo = pathinfo($filePath);
      $dir = $pathInfo['dirname'];
      $ext = $pathInfo['extension'];
      if (isset($pathInfo['filename'])) {
        // >= PHP 5.2.0
        $name = $pathInfo['filename'];
      }
      else {
        // PHP < 5.2.0 (determine file name without extension from basename)
        $name = $pathInfo['basename'];
        $dotPosition = mb_strrpos($pathInfo['basename'], '.');
        if ($dotPosition !== false) {
          $name = mb_substr($pathInfo['basename'], 0, $dotPosition);
        }
      }

      return is_file("$dir/$name-l.$ext");
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Image Delete Link                                                                 //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function get_delete_image($contenttype,$image_number,$data='') {
      global $_LANG;

      $delete_link = 'index.php?action=content&amp;site='.$this->site_id.'&amp;page='.$this->page_id.'&amp;dimg='.$image_number.$data;
      $delete_data = array( $contenttype.'_delete_image_label' => (isset($_LANG[$contenttype.'_delete_image_label']) ? $_LANG[$contenttype.'_delete_image_label'] : $_LANG['global_delete_image_label']),
                            $contenttype.'_delete_image_question_label' => (isset($_LANG[$contenttype.'_delete_image_question_label']) ? $_LANG[$contenttype.'_delete_image_question_label'] : $_LANG['global_delete_image_question_label']),
                            $contenttype.'_delete_image'.$image_number.'_link' => $delete_link );

      return $delete_data;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Get Data for Print Function                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function get_print_part ()
    {
      global $_LANG;

      $content = '';
      $available = ConfigHelper::get('print_version_available');
      $template = 'ContentItem' . $this->_templateSuffix;

      if (in_array($template, $available)) {
        $this->tpl->set_tpl_dir('../templates');
        $this->tpl->load_tpl('main_print', 'main_print_part.tpl');
        $content = $this->tpl->parsereturn('main_print', array(
          'm_print_label' => $_LANG['m_print_label'],
          'm_print_link' => '#'
        ));
        $this->tpl->set_tpl_dir('./templates');
      }

      return $content;
    }

    /**
     * Returns all title elements within this content item
     * @return array
     *          an array containing all titles stored for this content item
     */
    protected function getTitles()
    {
      if (empty($this->_contentElements['Title'])) {
        return array();
      }

      $titles = array();
      $texts = array();
      $sql = 'SELECT * '
       . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
       . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);

      $count = $this->_contentElements['Title'];
      if ($count > 1) {
        for ($i = 1; $i <= $count; $i++) {
          $entry = $row["{$this->_columnPrefix}Title{$i}"];
          $titles[] = stripslashes($entry);
        }
      }
      else $titles[] = stripslashes($row["{$this->_columnPrefix}Title"]);
      $this->db->free_result($result);

      return $titles;
    }

    /**
     * Returns all image titles and images for this content item.
     *
     * Overwrite this method for content items with subcontent.
     *
     * @param bool $subcontent [optional] [default : true]
     *        If subcontent is false, image titles and images from subcontent
     *        will not be retrieved.
     *
     * @return array
     *         An array containing all image titles and images stored for this
     *         content item.
     */
    public function getImageTitles($subcontent = true)
    {
      if (!$this->_contentImageTitles) {
        return array();
      }

      $titles = array();
      $sql = " SELECT {$this->_columnPrefix}ImageTitles "
           . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " WHERE FK_CIID = $this->page_id ";
      $cols = $this->db->GetCol($sql);

      // For subcontent there might exist multiple result rows for one content
      // item, so we iterate over all values retrieved from database.
      // i.e. ContentItemCB_Boxes - there exist multiple boxes referring to one
      //      ContentItemCB
      foreach ($cols as $val) {
        $titles = array_merge($titles, explode('$%$', $val));
      }

      return $titles;
    }

    /**
     * Returns all text elements within this content item (and from its
     * subcontents)
     * @param bool $subcontent
     *        if subcontent is false, text from subcontent will not be returned,
     *        default: true
     * @return array
     *         an array containing all texts stored for this content item (and subcontent)
     */
    public function getTexts($subcontent = true)
    {
      if (empty($this->_contentElements['Text'])) {
        return array();
      }

      $texts = array();
      $sql = 'SELECT * '
           . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);

      $count = $this->_contentElements['Text'];
      if ($count > 1) {
        for ($i = 1; $i <= $count; $i++) {
          $texts[] = $row["{$this->_columnPrefix}Text{$i}"];
        }
      }
      else $texts[] = $row["{$this->_columnPrefix}Text"];
      $this->db->free_result($result);

      return $texts;
    }

   /**
    * Determines if content has changed and thus spidering is necessary.
    *
    * @return bool
    *        True if content was changed, false otherwise.
    */
    protected static function hasContentChanged()
    {
      // The main content has changed.
      if (   isset($_POST['process'])
          || isset($_POST['process_save'])
      ) {
        return true;
      }
      // Nothing has changed.
      return false;
    }

    /**
     * Parse the action  boxes of the template and return the content as a string
     *
     * @param array $row - contains database column values of the contentitem
     * NOTE: The following columns are accessed, $row should contain
     * - CDisabled
     * - CTree
     * - CShowFromDate
     * - CShowUntilDate
     * - FK_CTID
     * - CShare
     * - CBlog
     * @param bool $preview - not used any more, as the preview comes from left content
     *
     * @return string - parsed action boxes template
     */
    protected function _getContentActionBoxes($row, $preview = true)
    {
      global $_LANG;

      $tree = $row['CTree'];
      $contentTypeId = (int)$row['FK_CTID'];
      $action = 'index.php';
      $buttonSaveLabel = $_LANG['global_button_save_label'];
      if (isset($_LANG["{$this->_contentPrefix}_button_save_label"])) {
        $buttonSaveLabel = $_LANG["{$this->_contentPrefix}_button_save_label"];
      }

      $buttonsSave = '<input type="submit" class="btn btn-success" name="process_save" value="' . $buttonSaveLabel . '" onclick="document.forms[0].target=\'_top\';document.forms[0].action2.value=\'\';" />';

      $dateFrom = DateHandler::getValidDateTime($row['CShowFromDate'], 'd.m.Y');
      $dateUntil = DateHandler::getValidDateTime($row['CShowUntilDate'], 'd.m.Y');
      $timeFrom = DateHandler::getValidDateTime($row['CShowFromDate'], 'H:i');
      $timeUntil = DateHandler::getValidDateTime($row['CShowUntilDate'], 'H:i');

      // Initialize variables for the information box.
      $infoCreateDateTime = '';
      $infoChangeDateTime = '';
      $infoCreatedBy = '';
      $infoChangedBy = '';
      if (ConfigHelper::get('m_infobox'))
      {
        $service = Container::make('ContentItemLogService');
        // Retrieve the user, who created the content item as well as the create
        // datetime.
        $log[1] = $service->getLastCreated($this->page_id);
        // user, datetime (content creation)
        if (isset($log[1]) && $log[1])
        {
          $infoCreateDateTime = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ci_metainfo'), ContentBase::strToTime($log[1]['LDateTime']));
          $infoCreatedBy = sprintf($_LANG['global_info_from_label'], $log[1]['UNick']);

          // Select changedatetime and the user, who changed the content at last.
          $log[2] = $service->getLastUpdated($this->page_id);
        }
        // if the content has been changed
        if (isset($log[2]) && $log[2])
        {
          $timestamp = ContentBase::strToTime($log[2]['LDateTime']);
          if ($timestamp) {
            $infoChangeDateTime = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ci_metainfo'), $timestamp);
          }
          $infoChangedBy = sprintf($_LANG['global_info_from_label'], $log[2]['UNick']);
        }
      }

      $dateAvailable =  ConfigHelper::get('ci_timing_type') == 'activated' &&
                        in_array($contentTypeId, ConfigHelper::get('ci_timing_allowed_ctypes'));
      $this->tpl->load_tpl('content_action_boxes', 'content_action_boxes.tpl');
      // get assigned campaign forms
      $cgSql = 'SELECT CGCCampaignRecipient, CGName '
           . "FROM {$this->table_prefix}campaign_contentitem cc "
           . "INNER JOIN {$this->table_prefix}campaign ca "
           . '  ON cc.FK_CGID = CGID '
           . "WHERE FK_CIID = {$this->page_id}";
      $cgRes = $this->db->query($cgSql);
      $cgFormData = array();
      while ($cgRow = $this->db->fetch_row($cgRes))
      {
        $cgFormData[] = array(
          'form_name' => parseOutput($cgRow['CGName']),
          'form_mail_recipients' => parseOutput($cgRow['CGCCampaignRecipient']),
        );
      }
      $this->tpl->parse_loop('content_action_boxes', $cgFormData, 'added_forms');
      $this->tpl->parse_if('content_action_boxes', 'forms_available', $cgFormData && $this->_user->AvailableModule('form', $this->site_id), array());
      $this->tpl->parse_if('content_action_boxes', 'date_available', $dateAvailable, array(
        'date_from' => $dateFrom,
        'time_from' => $timeFrom,
        'date_until' => $dateUntil,
        'time_until' => $timeUntil,
      ));

      $startDateAvailable = $tree == Navigation::TREE_MAIN &&
                            ConfigHelper::get('ci_timing_type') == 'startdateonly' &&
                            in_array($contentTypeId, ConfigHelper::get('ci_timing_allowed_ctypes'));
      $this->tpl->parse_if('content_action_boxes', 'start_date_available', $startDateAvailable, array(
        'date_from' => $dateFrom,
        'time_from' => $timeFrom,
      ));

      $display = ConfigHelper::get('m_share_display');
      $display = isset($display[$this->site_id]) ? $display[$this->site_id] : $display[0];
      $this->tpl->parse_if('content_action_boxes', 'share_user_available',
             $this->_sharingAvailable() && $this->_user->AvailableModule('share', $this->site_id) && $display,
             array(
              'share_checked' => $row['CShare'] ? 'checked' : '',
              'share_item_label' => $_LANG['global_share_item_label'],
              'global_share_item_tooltip' => $_LANG['global_share_item_tooltip'],
             ));
      $this->tpl->parse_if('content_action_boxes', 'share_user_not_available',
             $this->_sharingAvailable() && !$this->_user->AvailableModule('share', $this->site_id) && $display,
             array(
              'share_checked' => $row['CShare'] ? 'checked' : '',
              'share_item_label' => $_LANG['global_share_item_label'],
              'global_share_item_status' => $_LANG["lo_share_status{$row['CShare']}"],
             ));
      // add the blog checkbox to actionbox if the module is available for user
      $this->tpl->parse_if('content_action_boxes', 'blog_user_available',
             $this->_blogAvailable() && $this->_user->AvailableModule('blog', $this->site_id),
             array(
              'blog_checked' => $row['CBlog'] ? 'checked' : '',
              'blog_item_label' => $_LANG['global_blog_item_label'],
              'global_blog_item_tooltip' => $_LANG['global_blog_item_tooltip'],
             ));
      // add status information (blog comments allowed for contentitem ?) if the
      // user isn't allowed to change blog availability (blog module isn't
      // available for user)
      $this->tpl->parse_if('content_action_boxes', 'blog_user_not_available',
             $this->_blogAvailable() && !$this->_user->AvailableModule('blog', $this->site_id),
             array(
              'blog_checked' => $row['CBlog'] ? 'checked' : '',
              'blog_item_label' => $_LANG['global_blog_item_label'],
              'global_blog_item_status' => $_LANG["lo_blog_status{$row['CBlog']}"],
             ));
      // Display content item information if it is activated in the config and if
      // there is an entry for item 'created' in the database.
      $this->tpl->parse_if('content_action_boxes', 'info_content',
                           ConfigHelper::get('m_infobox') && isset($log[1]) && $log[1], array(
        'info_createdatetime' => $infoCreateDateTime,
        'info_created_by' => $infoCreatedBy,
      ));
      // Display the user, who changed the content item at last (with datetime)
      // if the content item has been changed.
      $this->tpl->parse_if('content_action_boxes', 'info_content_changed',
                           $infoChangeDateTime && $infoChangedBy, array(
        'info_changedatetime' => $infoChangeDateTime,
        'info_changed_by' => $infoChangedBy,
      ));
      $this->tpl->parse_if('content_action_boxes', 'info_content_not_changed',
                           !$infoChangeDateTime || !$infoChangedBy);
      $activationLight = $this->_page->getActivationLight();
      $activationLightLabel = $_LANG["lo_activation_light_{$activationLight}_label"];
      // page is visible with parent timing, but no timing for the page itself
      if ($activationLight == ActivationClockInterface::GREEN && !$this->_page->getStartDate() && !$this->_page->getEndDate())
        $activationLightLabel = $_LANG["lo_activation_light_clock_green_from_parent_label"];
      $timingEnabled = $activationLight == ActivationClockInterface::GREEN || $activationLight == ActivationClockInterface::ORANGE || $activationLight == ActivationClockInterface::RED;
      $activationLightLink = "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;changePageActivationID=$this->page_id&amp;changePageActivationTo=";
      if ($this->_page->getActivationState() == NavigationPage::ACTIVATION_DISABLED_SELF) {
        $activationLightLink .= self::ACTIVATION_ENABLED;
      }
      else {
        $activationLightLink .= self::ACTIVATION_DISABLED;
      }

      $this->tpl->parse_if('content_action_boxes', "timing_enabled", $timingEnabled);
      $this->tpl->parse_if('content_action_boxes', "timing_disabled", !$timingEnabled);
      $actionBoxesContent = $this->tpl->parsereturn('content_action_boxes', array(
        'action' => $action,
        'buttons_save' => $buttonsSave,
        'actions_label' => $_LANG['m_actions_label'],
        'activation_light' => $activationLight,
        'activation_light_label' => $activationLightLabel,
        'activation_light_link' => $activationLightLink,
      ));
      return $actionBoxesContent;
    }

    /**
     * Shows if the contentitem / page has content
     * only suitable for contentitems directly holding content
     * @return bool true if the contentitem / page has content stored in the database,
     *              false if no content has been entered yet or content has been deleted
     */
    public function hasContent() {
      $sql = "SELECT {$this->_columnPrefix}ID "
           . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . "WHERE FK_CIID = {$this->page_id}";
      $result = $this->db->GetOne($sql);
      // no entry in table found - no content for this item available
      if (!$result) {
        return false;
      }
      return true;
    }

    /**
     * returns the metainfo part for the content template
     *
     * This method should be used inside the content items preview method, where
     * the frontend template of the content item is parsed - contentitems within
     * an archive may have certain metainfo displayed on the FE
     *
     * @param string $prefix
     *        the prefix of the of the current content
     * @return string the parsed metainfo part template
     */
    protected function _getMetainfoPart ($prefix = "") {
      global $_LANG;

      $sql = 'SELECT CIID, CShowFromDate, CShowUntilDate, CCreateDateTime, CChangeDateTime, CType '
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE CIID = {$this->page_id} ";
      $row = $this->db->GetRow($sql);

      $metainfo = array(
        'show_from_date' => $row['CShowFromDate'],
        'show_until_date' => $row['CShowUntilDate'],
        'create_date' => $row['CCreateDateTime'],
        'change_date' => $row['CChangeDateTime'],
        'page_type' => $row['CType'],
      );

      $template = "main_metainfo_part.tpl";
      if (is_file("./templates/main_metainfo_part_".$prefix.".tpl"))
        $template = "main_metainfo_part_".$prefix.".tpl";
      $this->tpl->load_tpl('main_metainfo', $template);
      $this->tpl->parse_if('main_metainfo', 'changed_available', ($metainfo["change_date"] && $metainfo["change_date"] != "1970-01-01" && $metainfo["change_date"] != "0000-00-00"), array( 'm_changed_label' => sprintf($_LANG["global_preview_metainfo_changed_label"],date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["change_date"]))),
                                                                                                                                                                                                              'm_change_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["change_date"])) ) );
      return $this->tpl->parsereturn('main_metainfo', array( 'm_metainfo_label' => $_LANG["global_preview_metainfo_label"],
                                                             'm_show_from_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["show_from_date"])),
                                                             'm_show_until_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["show_until_date"])),
                                                             'm_create_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["create_date"])),
                                                             'm_change_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["change_date"])),
                                                             'm_created_label' => sprintf($_LANG["global_preview_metainfo_created_label"],date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["create_date"]))),
                                                             'm_changed_label' => sprintf($_LANG["global_preview_metainfo_created_label"],date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'ci_metainfo'), strtotime($metainfo["change_date"]))),
                                                            ));
    }

    /**
     * Determines if the content item is inside an archive.
     *
     * @return bool
     *        true if the current content item is inside an archive, false otherwise.
     */
    protected function _isInsideArchive()
    {
      $page = $this->_navigation->getPageByID($this->page_id);
      $parent = $page->getParent();
      // determine if any parent is of type archive
      while ($parent) {
        if ($parent->isArchive()) {
          return true;
          break;
        }
        $parent = $parent->getParent();
      }
      return false;
    }

    /**
     * Returns all broken links found inside the contentitems text fields
     *
     * @param string $text - optional - if set the given string is searched for
     *        broken links instead of the contentitems text fields
     *
     * @return array contains the following properties:
     *         - title,
     *         - type (internal, file, dlfile, centralfile),
     *         - link (link to the page / contentitem),
     * of the broken links found within the contentitems text fields, an empty
     * array if no broken links were found
     */
    public function getBrokenTextLinks($text = null)
    {
      // if parameter isn't set, get text of contentitem itself
      if (!$text)
      {
        $text = $this->getTexts(false);
        $text = implode('', $text);
      }

      if (!$text)
      {
        return array();
      }

      $IDs = array('file' => array(),
                   'dlfile' => array(),
                   'centralfile' => array(),
                   'internal' => array());
      $pattern = '#<a( title="([^"]*)")? href="(edwin-(file|link)://(file|centralfile|dlfile|internal)/(\d+))#ui';
      while (preg_match($pattern, $text, $matches))
      {
        $IDs[$matches[5]][(int)$matches[6]]['id'] = (int)$matches[6];
        $IDs[$matches[5]][(int)$matches[6]]['title'] = $matches[2];
        // Remove the found link from $text and check again
        $text = mb_substr_replace($text, '', mb_strpos($text, $matches[0]), mb_strlen($matches[0]));
      }
      // check if one of the links is broken and store title, type and link to
      // page foreach broken link
      $broken = array();
      if ($IDs)
      {
        if ($IDs['file'])
        {
          $onlyFileIds = implode(', ', array_keys($IDs['file']));
          $sql = "SELECT FID "
                ."FROM {$this->table_prefix}file "
                ."WHERE FID IN ({$onlyFileIds})";
          $idCol = $this->db->GetCol($sql);
          $brokenIds = array_diff(array_keys($IDs['file']), $idCol);
          foreach ($brokenIds as $id)
          {
            $broken[] = array(
              'title' => $IDs['file'][$id]['title'],
              'type'  => 'file',
              'link'  => "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$this->page_id,
            );
          }
        }
        if ($IDs['dlfile'])
        {
          $onlyDlFileIds = implode(', ', array_keys($IDs['dlfile']));
          $sql = "SELECT DFID "
                ."FROM {$this->table_prefix}contentitem_dl_area_file "
                ."WHERE DFID IN ({$onlyDlFileIds})";
          $idCol = $this->db->GetCol($sql);
          $brokenIds = array_diff(array_keys($IDs['dlfile']), $idCol);
          foreach ($brokenIds as $id)
          {
            $broken[] = array(
              'title' => $IDs['dlfile'][$id]['title'],
              'type'  => 'dlfile',
              'link'  => "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$this->page_id,
            );
          }
        }
        if ($IDs['centralfile'])
        {
          $onlyCentralFileIds = implode(', ', array_keys($IDs['centralfile']));
          $sql = "SELECT CFID "
                ."FROM {$this->table_prefix}centralfile "
                ."WHERE CFID IN ({$onlyCentralFileIds})";
          $idCol = $this->db->GetCol($sql);
          $brokenIds = array_diff(array_keys($IDs['centralfile']), $idCol);
          foreach ($brokenIds as $id)
          {
            $broken[] = array(
              'title' => $IDs['centralfile'][$id]['title'],
              'type'  => 'centralfile',
              'link'  => "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$this->page_id,
            );
          }
        }
        if ($IDs['internal'])
        {
          $onlyIntlinkIds = implode(', ', array_keys($IDs['internal']));
          $sql = "SELECT CIID "
                ."FROM {$this->table_prefix}contentitem "
                ."WHERE CIID IN ({$onlyIntlinkIds})";
          $idCol = $this->db->GetCol($sql);
          $tempIDs = array_diff(array_keys($IDs['internal']), $idCol);
          foreach ($tempIDs as $id)
          {
            $broken[] = array(
              'title' => $IDs['internal'][$id]['title'],
              'type'  => 'internal',
              'link'  => "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$this->page_id,
            );
          }
        }
        return $broken;
      }
      // if no internal link exist within the specified text return an empty array
      return array();
    }

    /**
     * Checks the contentitems text fields for broken links (internal and file links)
     *
     * @return bool true if a broken link has been found,
     *              false otherwise
     */
    public function hasBrokenTextLink()
    {
      if ($this->_hasBrokenFileLink() || $this->_hasBrokenInternalLink())
      {
        return true;
      }
      return false;
    }

    /**
     * Checks the contentitems text fields for broken file links
     *
     * @return bool true if a broken file link has been found,
     *              false otherwise
     */
    protected function _hasBrokenFileLink()
    {
      $text = $this->getTexts();
      $text = implode('', $text);

      if (!$text)
      {
        return false;
      }

      $fileIDs = $this->_getTextFileLinks($text);

      //check if one of the links is broken
      if ($fileIDs)
      {
        if ($fileIDs['file'])
        {
          $onlyFileIds = implode(', ', $fileIDs['file']);
          $sql = "SELECT COUNT(FID) "
                ."FROM {$this->table_prefix}file "
                ."WHERE FID IN ({$onlyFileIds})";
          if ($this->db->GetOne($sql) != count($fileIDs['file']))
          {
            return true;
          }
        }
        else if ($fileIDs['dlfile'])
        {
          $onlyDlFileIds = implode(', ', $fileIDs['dlfile']);
          $sql = "SELECT COUNT(DFID) "
                ."FROM {$this->table_prefix}contentitem_dl_area_file "
                ."WHERE DFID IN ({$onlyDlFileIds})";
          if ($this->db->GetOne($sql) != count($fileIDs['dlfile']))
          {
            return true;
          }
        }
        else if ($fileIDs['centralfile'])
        {
          $onlyCentralFileIds = implode(', ', $fileIDs['centralfile']);
          $sql = "SELECT COUNT(CFID) "
                ."FROM {$this->table_prefix}centralfile "
                ."WHERE CFID IN ({$onlyCentralFileIds})";
          if ($this->db->GetOne($sql) != count($fileIDs['centralfile']))
          {
            return true;
          }
        }
      }
      // if no file link is broken
      return false;
    }

    /**
     * Checks the contentitems text fields for broken internal links
     *
     * @return bool true if a broken internal link has been found,
     *              false otherwise
     */
    protected function _hasBrokenInternalLink()
    {
      $text = $this->getTexts();
      $text = implode('', $text);

      if (!$text)
      {
        return false;
      }

      $pageIDs = array();
      $pattern = '#<a.*?href="(edwin-link://internal/(\d+))"#ui';
      preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
      foreach ($matches as $match) {
        $pageIDs[] = (int)$match[2];
      }
      //check if one of the links is broken
      $pageIDsImploded = implode(', ', $pageIDs);
      if ($pageIDsImploded)
      {
        $sql = "SELECT COUNT(CIID) "
              ."FROM {$this->table_prefix}contentitem "
              ."WHERE CIID IN ({$pageIDsImploded})";
        if ($this->db->GetOne($sql) != count($pageIDs))
        {
          return true;
        }
      }
      // if no internal link is broken
      return false;
    }

    /**
     * Returns a shortname for the given contenttype
     *
     * @param int $typeID - the contenttype id (FK_CTID from DB)
     * @return string
     * (0) '' - empty string for root pages without a specified contenttype
     * (3) 'teaser'
     * (75) 'logical'
     * (76) 'archive'
     * (77) 'teaserplus'
     * (78) 'logicalplus'
     * (79) 'variation'
     * (80) 'blog'
     * (default) 'content'
     */
    public static function getTypeShortname($typeID)
    {
      switch($typeID)
      {
        case 3:  return 'teaser';
        case 75: return 'logical';
        case 76: return 'archive';
        case 77: return 'teaserplus';
        case 78: return 'logicalplus';
        case 79: return 'variation';
        case 80: return 'blog';
        case 81: return 'productteaser';
        case 0:  return '';
        default: return 'content';
      }
    }

    /**
     * Check if the blog function is available for contentitem of specified
     * type and path on the current site.
     *
     * @param string $pagePath [optional] the page path, if not set the current
     *        page's path is used.
     * @param int $pageType [optional] the content type id (FK_CTID from DB), if
     *        not set, the page type is detected for the given page (page path)
     *
     * @return bool true | false
     */
    protected function _blogAvailable($pagePath = null, $pageType = null)
    {
      $page = $this->_navigation->getPageByID($this->page_id);
      if ($pagePath === null) {
        $pagePath = $page->getDirectPath();
      }
      if ($pageType === null) {
        $pageType = $page->getContentTypeId();
      }

      return $this->_functionBlog->isAvailableOnSiteForPagePathAndContentType(
          $this->site_id, $pagePath, $pageType);
    }

    /**
     * Check if the share function is available for contentitem of specified
     * type and path on the current site.
     *
     * @param string $pagePath [optional] the page path, if not set the current
     *        page's path is used.
     * @param int $pageType [optional] the content type id (FK_CTID from DB), if
     *        not set, the page type is detected for the given page (page path)
     *
     * @return bool true | false
     */
    protected function _sharingAvailable($pagePath = null, $pageType = null)
    {
      global $_MODULES;

      if ($pagePath === null) {
        $pagePath = $this->page_path;
      }
      if ($pageType === null) {
        $pageType = $this->_navigation->getPageByID($this->page_id)->getContentTypeId();
      }

      return $this->_functionShare->isAvailableOnSiteForPagePathAndContentType(
          $this->site_id, $pagePath, $pageType);
    }

  /**
     * Returns all frontend language vars for the current contentitem (for preview)
     *
     * @return array frontend language vars
     */
    protected function _getFrontentLang()
    {
      $lang = ConfigHelper::get('site_languages');
      $lang = $lang[$this->site_id];
      $langFile = ConfigHelper::get('m_use_compressed_lang_file') ? 'compressed' : 'core';
      if (is_file("../language/{$lang}-default/lang.{$langFile}.php"))
        require_once("../language/{$lang}-default/lang.{$langFile}.php");
      if (is_file("../language/{$lang}/lang.{$langFile}.php"))
        require_once("../language/{$lang}/lang.{$langFile}.php");
      if (is_file("../language/{$lang}-default/content_types/lang.ContentItem".get_class($this).".php"))
        require_once("../language/{$lang}-default/content_types/lang.ContentItem".get_class($this).".php");
      if (is_file("../language/{$lang}/content_types/lang.ContentItem".get_class($this).".php"))
        require_once("../language/{$lang}/content_types/lang.ContentItem".get_class($this).".php");
      if (is_file("../language/{$lang}-custom/content_types/lang.ContentItem".$this->_templateSuffix.".php"))
        require_once("../language/{$lang}-custom/content_types/lang.ContentItem".$this->_templateSuffix.".php");

      if ($lang && !isset($_LANG) || !isset($_LANG2)) {
        trigger_error("Language files already included. Use _getFrontentLang() only once!", E_USER_NOTICE);
      }

      return array_merge( $_LANG, $_LANG2["global"], $_LANG2[$this->_contentPrefix] );

    }

    /**
     * Store data for the blog level (if parent of current item is a blog level).
     */
    private function _setBlogTextAndImage()
    {
      $page = $this->_navigation->getPageByID($this->page_id);
      $parent = $page->getParent();

      // Do not continue processing the content items data for a blog level, if
      // it hasn't got a parent or if its parent is not a blog level.
      if (!$parent || !$parent->isBlog()) {
        return;
      }

      $data = $this->_getData();
      // fetch parent ContentItemBE and retrieve its prefix
      $ciParent = ContentItem::create($parent->getSite()->getID(), $parent->getID(),
          $this->tpl, $this->db, $this->table_prefix, $this->action, $this->_user,
          $this->session, $this->_navigation);
      // As the prefix might contain an array of multiple values, we have to
      // prepare our array for the config variables used:
      // => array('be', 'ci_be')
      $prefix = $ciParent->getConfigPrefix();
      $tmp = $prefix;
      foreach ($tmp as $key => $val) {
        $prefix[] = 'ci_' . $val;
      }

      $contentType = $page->getContentTypeId();
      $textField = 0;
      $imageField = 0;

      // Determine the text field to use as source for the blog level text.
      // Enure that there is not an invalid source text field number configured.
      $configText = ConfigHelper::get('text_source', $prefix);
      if (isset($configText[$contentType]) && ($configText[$contentType] <= $this->_contentElements['Text'])) {
        $textField = $configText[$contentType];
      }

      // Determine the image field to use as source for the blog level text.
      // Enure that there is not an invalid source image field number configured.
      $configImage = ConfigHelper::get('image_source', $prefix);
      if (isset($configImage[$contentType]) && ($configImage[$contentType] <= $this->_contentElements['Image'])) {
        $imageField = $configImage[$contentType];
      }

      $text = '';
      $imageSrc = '';
      // Retrieve the content item data to store for blog level.

      if (isset($this->_contentElements['Text']))
      {
        if ($this->_contentElements['Text'] == 1) {
          $text = $data["{$this->_columnPrefix}Text"];
        }
        // If there has not been a text field configured for this content item,
        // use the first text field with valid content.
        else if ($textField == 0)
        {
          for ($i = 1; $i <= $this->_contentElements['Text']; $i++)
          {
            if ($data["{$this->_columnPrefix}Text$i"]) {
              $text = $data["{$this->_columnPrefix}Text$i"];
              break;
            }
          }
        }
        else {
          $text = $data["{$this->_columnPrefix}Text$textField"];
        }
      }

      if (isset($this->_contentElements['Image']))
      {
        if ($this->_contentElements['Image'] == 1) {
          $imageSrc = $data["{$this->_columnPrefix}Image"];
        }
        // If there has not been an image field configured for this content item,
        // use the first image available.
        else if ($imageField == 0)
        {
          for ($i = 1; $i <= $this->_contentElements['Image']; $i++)
          {
            if ($data["{$this->_columnPrefix}Image$i"]) {
              $imageSrc = $data["{$this->_columnPrefix}Image$i"];
              break;
            }
          }
        }
        else {
          $imageSrc = $data["{$this->_columnPrefix}Image$imageField"];
        }
      }

      // Change the image source to the blog level image.
      if ($imageSrc) {
        $imageSrc = mb_substr($imageSrc,0,mb_strlen($imageSrc)-mb_strlen(mb_strrchr($imageSrc,'.')))
                  . '-be' . mb_strrchr($imageSrc,'.');
      }

      $allowedHtml = ConfigHelper::get('allowed_html', $prefix);
      $allowTags = $allowedHtml ? $allowedHtml : ConfigHelper::get('be_allowed_html_level3');
      $shorttextMaxlength = (int)ConfigHelper::get('shorttext_maxlength', $prefix);
      $shorttextAftertext = ConfigHelper::get('shorttext_aftertext', $prefix);
      $shortTextCutExact = ConfigHelper::get('shorttext_cut_exact', $prefix);
      $text = StringHelper::setText($text)
              ->purge($allowTags)
              ->truncate($shorttextMaxlength, $shorttextAftertext, $shortTextCutExact)
              ->getText();

      $sql = " UPDATE {$this->table_prefix}contentabstract "
           . " SET CImageBlog = '$imageSrc', "
           . "     CShortTextBlog = '{$this->db->escape($text)}'"
           . ' WHERE FK_CIID = ' . $this->page_id;
      $result = $this->db->query($sql);

    }

    /**
     * Get the structure link box content.
     *
     * @return string
     *         the parsed structure link box content
     */
    private function _getStructureLinksBox()
    {
      global $_LANG;

      if (!$this->_modStructureLinksActive) {
        return '';
      }

      // No structure link box for logical levels...
      if ($this instanceof ContentItemLogical)
      {
        // ..except from IB, IP and VA
        if (!$this->_navigation->getCurrentPage()->isOverview() &&
            !$this->_navigation->getCurrentPage()->isVariation())
        {
          return '';
        }
      }

      $separator = ConfigHelper::get('m_structure_links_hierarchical_title_separator');
      $targets = array();
      $source = array();
      if (!empty($this->_structureLinks))
      {
        foreach ($this->_structureLinks as $id)
        {
          $page = $this->_navigation->getPageByID($id);
          $site = $page->getSite(); // TODO: ensure that page object is not null
          $siteLabel = sprintf($_LANG['m_sl_link_title_label'],
                               parseOutput($this->_getHierarchicalTitle($id, $separator)),
                               self::getLanguageSiteLabel($site));

          // output page title as link
          if ($this->_user->ContentPermitted($site->getID(), $id, '')) {
            $pageLink = sprintf($_LANG['m_sl_link'],
                                "index.php?action=content&amp;site=".$site->getID()."&amp;page=".$id,
                                $siteLabel, parseOutput($page->getTitle()));
          }
          // title only
          else {
            $pageLink = sprintf($_LANG['m_sl_link_label'], $siteLabel, parseOutput($page->getTitle()));
          }

          $targets[] = array(
            'm_sl_page_id' => $id,
            'm_sl_page_link' => $pageLink,
          );
        }
      }
      else if (!$this->_structureLinksAvailable)
      {
        // read the core content item id this content item refers to
        $id = $this->_readStructureLinkContentItemId();
        $pageLink = '';
        if ($id)
        {
          $page = $this->_navigation->getPageByID($id);
          $site = $page->getSite();
          $siteLabel = sprintf($_LANG['m_sl_link_title_label'],
                               parseOutput($this->_getHierarchicalTitle($id, $separator)),
                               self::getLanguageSiteLabel($site));

          // output page title as link
          if ($this->_user->ContentPermitted($site->getID(), $id, '')) {
            $pageLink = sprintf($_LANG['m_sl_link'],
                                "index.php?action=content&amp;site=".$site->getID()."&amp;page=".$id,
                                $siteLabel, parseOutput($page->getTitle()));
          }
          // title only
          else {
            $pageLink = sprintf($_LANG['m_sl_link_label'], $this->_getHierarchicalTitle($id, $separator));
          }

          $source = array(
            'm_sl_page_id'    => $id,
            'm_sl_page_link'  => $pageLink,
          );
        }
      }

      // the structure links section is only available for contentitems
      // which are within the defined link source site (usually a portal site).
      // and user with the structure links module / function permitted.
      $strlinksAvailable = $this->_modStructureLinksActive && $this->_structureLinksAvailable &&
                           $this->_user->AvailableModule('structurelinks', $this->site_id);
      $strlinksLink = '';
      if ($strlinksAvailable) {
        $strlinksLink = "index.php?action=strlinks&amp;site=".$this->site_id."&amp;page=".$this->page_id;
        $strlinksLink = sprintf($_LANG['m_sl_strlinks_link'], $strlinksLink);
      }

      $this->tpl->load_tpl('content_site_left_sl', 'content_site_left_sl_box.tpl');
      $this->tpl->parse_if('content_site_left_sl', 'm_sl_no_target', $this->_structureLinksAvailable && empty($targets));
      $this->tpl->parse_if('content_site_left_sl', 'm_sl_target', $this->_structureLinksAvailable && !empty($targets));
      $this->tpl->parse_if('content_site_left_sl', 'm_sl_no_source', !$this->_structureLinksAvailable && empty($source));
      $this->tpl->parse_if('content_site_left_sl', 'm_sl_source', !$this->_structureLinksAvailable && !empty($source), $source);
      $this->tpl->parse_loop('content_site_left_sl', $targets, 'm_sl_items');
      return $this->tpl->parsereturn('content_site_left_sl', array (
        'm_sl_strlinks_link' => $strlinksLink,
      ));

    }

    /**
     * Retrieve structure link content item id.
     *
     * @return int
     *         The content item id the content item gets its content from.
     */
    protected function _readStructureLinkContentItemId()
    {
      if ($this->_structureLinkReferenceId != 0) {
        return $this->_structureLinkReferenceId;
      }
      // the core content item this content item refers to
      $sql = ' SELECT FK_CIID '
            ." FROM {$this->table_prefix}structurelink "
            ." WHERE FK_CIID_Link = $this->page_id ";
      $this->_structureLinkReferenceId = $this->db->GetOne($sql);

      return $this->_structureLinkReferenceId;
    }

    /**
     * Retrieve structure link settings for this contentitem. If there is not set
     * a structure link site id within $_CONFIG["m_structure_links"], variable values
     * are not measured.
     *
     * Set the ContentItem::_modStructureLinksActive variable to true if the
     * structure module is active.
     *
     * Set the ContentItem::_structureLinksAvailable variable to true if the
     * current site's contentitems can be linked with content items from other
     * sites ($_CONFIG["m_structure_links"]).
     *
     * Store ids of all contentitems, this contentitem is linked to within
     * the ContentItem::_structureLinks array.
     */
    private function _readStructureLinksModuleActive()
    {
      global $_MODULES;

      if (!ConfigHelper::get('m_structure_links')) {
        return;
      }

      if (in_array('structurelinks', $_MODULES)) {
        $this->_modStructureLinksActive = true;
      }
      else {
        $this->_modStructureLinksActive = false;
      }

      // current site does not contain core content
      if (in_array($this->site_id, ConfigHelper::get('m_structure_links'))) {
        $this->_structureLinksAvailable = true;
      }

      // this content item does not contain core content
      if (!$this->_structureLinksAvailable) {
        return;
      }

      // check if values have been loaded before and are available from cache
      if (isset(self::$_structureLinksCache[$this->page_id]))
      {
        $this->_structureLinks = self::$_structureLinksCache[$this->page_id];
        return;
      }

      // retrieve linked contentitems, where this contentitem is core content of
      $sql = ' SELECT FK_CIID_Link '
            ." FROM {$this->table_prefix}structurelink "
            ." WHERE FK_CIID = $this->page_id ";
      $this->_structureLinks = $this->db->GetCol($sql);

      self::$_structureLinksCache[$this->page_id] = $this->_structureLinks;
    }

    /**
     * Update linked images of this contentitem. This method should be called
     * from the ContentItem::edit_content() method for all linked content items.
     *
     * NOTE: Call this method only, if the contentitem is linked by a structure
     *       link from another contentitem and the "structurelinks" module /
     *       function is available for the current user.
     */
    public function updateStructureLinkContentImages()
    {
      // read old date before updating content
      $boxImageColumn = $this->_getBoxImageColumnName();
      $existingBoxImage = $this->_getBoxImage($boxImageColumn);

      // Retrieve and store new images (only linked images)
      $input['Image'] = $this->_readContentElementsImages(null, null, null, null, true);
      $sql = $this->_buildContentElementsUpdateStatement($input);
      $result = $this->db->query($sql);

      // Set short text and image for IB / IP levels.
      $boxImageChanged = false;
      if (   isset($input['Image'][$boxImageColumn])
          && $input['Image'][$boxImageColumn] != $existingBoxImage
      ) {
        $boxImageChanged = true;
      }
      $this->setShortTextAndImages($boxImageChanged, $boxImageColumn);
    }

    /**
     * Update linked images of contentitem subcontent. This method should be
     * called from the update methods for all linked contentitems.
     *
     * NOTE: Call this method only, if the contentitem is linked by a structure
     *       link from another contentitem and the "structurelinks" module /
     *       function is available for the current user.
     *
     * @param int $subID
     *        The ID of the subitem, which is linked to a subitem of this
     *        contentitem.
     * @param array $fields
     *        Required fields, checked for valid content before replacing image.
     *        The image is not going to be replaced, if all fields contain
     *        invalid content.
     */
    public function updateStructureLinkSubContentImages($subID, $fields = array())
    {
      $idCol = $this->_columnPrefix . 'ID';
      $positionCol = $this->_columnPrefix . 'Position';
      // Retrieve subcontent at position equal to subcontent $sourceID
      $sql = " SELECT $idCol " . ($fields ? (', ' . implode(',', $fields)) : '')
           . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " WHERE $positionCol IN ( "
           . "         SELECT $positionCol "
           . "         FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . "         WHERE $idCol = $subID "
           . '       ) '
           . "   AND FK_CIID = $this->page_id ";
      $row = $this->db->GetRow($sql);

      $count = 0;
      // Check for invalid content of target item.
      foreach ($fields as $field) {
        if (!$row[$field]) {
          $count++;
        }
      }

      // none of the required fields contains valid content
      if ($count === count($fields)) {
        return false;
      }

      // subitem id
      $ID = $row[$idCol];
      $components = array($this->site_id, $this->page_id, $ID);
      // Retrieve and store new images (only linked images)
      $input['Image'] = $this->_readContentElementsImages($components, $subID, $ID, $idCol, true);

      if (empty($input['Image'])) {
        return false;
      }

      $imageCol = $this->_getContentElementColumnName('Image', 1, 1);

      // Update the database.
      $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " SET $imageCol = '" . $input['Image'][$imageCol] . "' "
           . " WHERE FK_CIID = {$this->page_id} "
           . " AND $idCol = $ID ";
      $result = $this->db->query($sql);

      return true;
    }

    /**
     * Determine the box image column name.
     *
     * @return string
     *         The box image column name. Empty string, if the ContentItem
     *         has not got images or there is not an image to create the box
     *         image from specified.
     */
    private function _getBoxImageColumnName()
    {
      $boxImageColumn = '';
      if (!empty($this->_contentElements['Image']) && $this->_contentBoxImage)
      {
        $imageCount = $this->_contentElements['Image'];
        $boxImageColumn = "{$this->_columnPrefix}Image";
        if ($imageCount > 1) {
          $boxImageColumn .= $this->_contentBoxImage;
        }
      }

      return $boxImageColumn;
    }

    /**
     * Get an existing box image from DB.
     *
     * @param $boxImageColumn [optional]
     *        The box image column.
     *
     * @return string
     *         The boximage.
     */
    private function _getBoxImage($boxImageColumn = '')
    {
      if (!$boxImageColumn) {
        $boxImageColumn = $this->_getBoxImageColumnName();
      }

      // Determine existing box image.
      $image = '';
      if ($boxImageColumn)
      {
        $sql = "SELECT $boxImageColumn "
             . "FROM {$this->table_prefix}contentitem_$this->_contentPrefix "
             . "WHERE FK_CIID = $this->page_id ";
        $image = $this->db->GetOne($sql);
      }

      return $image;
    }

    /**
     * Retrieve the text field column name for short text within IB / IP levels.
     * Use the first text field containing valid content.
     *
     * @return string
     *         The column name of the text field.
     */
    private function _getBoxTextColumnName()
    {
      $boxTextColumn = '';
      $colNames = array();
      $count = isset($this->_contentElements['Text']) ?
               $this->_contentElements['Text'] : 0;

      for ($i = 1; $i <= $count; $i++) {
        $colNames[] = $this->_getContentElementColumnName('Text', $count, $i);
      }

      $sql = ' SELECT ' . implode(',', $colNames) . ' '
           . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " WHERE FK_CIID = $this->page_id ";
      $row = $this->db->GetRow($sql);

      foreach ($colNames as $boxTextColumn) {
        if (isset($row[$boxTextColumn]) && $row[$boxTextColumn]) {
          break;
        }
      }

      return $boxTextColumn;
    }

    /**
     * Parse common template parts used within each contentitem or equal foreach
     * contenttype. Ensure the template has been loaded by the Template object
     * before calling this method.
     *
     * @param string $tplName [optional]
     *        The template name of the template containing the contentitem's
     *        content.
     * @param int $ID [optional]
     *        The id for retrieving data from ContentItem::_getContentElementInputName()
     *        Use for subcontent such as CB_Boxes, CB_Box_BigLinks, DL_Areas, ...
     */
    protected function _parseTemplateCommonParts($tplName = null, $ID = null)
    {
      if ($tplName === null) {
        $tplName = 'content_site_' . $this->_contentPrefix;
      }

      $post = new Input(Input::SOURCE_POST);

      // Parse the checkboxes for updating images
      $count = isset($this->_contentElements['Image']) ? $this->_contentElements['Image'] : 0;
      for ($i = 1; $i <= $count; $i++)
      {
        // The IF name is the same as the 'image_structure_link' checkbox's
        // $_POST variable name e.g. 'ti_image_structure_link1'
        $name = $this->_getContentElementInputName('image_structure_link', $count, $i, $ID);
        // The checkbox is only available, if the 'structurelinks' module is
        // active, the user has permission to it, and the current site is the
        // one defined inside the $_CONFIG['m_structure_links'] configuration
        // variable to be the one providing content for its subportals.
        // The checkbox should not be displayed for contentitems without any
        // structure links existing.
        $displayCheckbox = $this->_structureLinksAvailable && !empty($this->_structureLinks) &&
                           $this->_user->AvailableModule('structurelinks', $this->site_id);
        $this->tpl->parse_if($tplName, $name, $displayCheckbox);
      }
    }

    /**
     * Retrieve all file links within the given text
     *
     * @return array
     *         indexed by filetype ('file', 'centralfile', 'dlfile') - file ids
     */
    private function _getTextFileLinks($text)
    {
      $fileIDs = array('file' => array(),
                       'dlfile' => array(),
                       'centralfile' => array());
      if (!$text) {
        return $fileIDs;
      }

      // Retrieve all file links (linktype and file id)
      $pattern = '#href="(edwin-file:\/\/(file|centralfile|dlfile)\/(\pN+))#ui';
      $count = 1;
      while (preg_match($pattern, $text, $matches))
      {
        // [type] = file id
        $fileIDs[$matches[2]][] = (int)$matches[3];
        // Remove the found link from $text and check again
        $text = mb_substr_replace($text, '', mb_strpos($text, $matches[1]), mb_strlen($matches[1]));
      }

      return $fileIDs;
    }

    /**
     * Retrieve all internal links within the given text
     *
     * @return array
     *         Contains all internal link target ids
     */
    private function _getTextInternalLinks($text)
    {
      $pageIDs = array();
      if (!$text) {
        return $pageIDs;
      }

      $pattern = '#<a.*?href="(edwin-link://internal/(\d+))"#ui';
      while (preg_match($pattern, $text, $matches))
      {
        $pageIDs[] = (int)$matches[2];
        // Remove the found link from $text and check again
        $text = mb_substr_replace($text, '', mb_strpos($text, $matches[1]), mb_strlen($matches[1]));
      }

      return $pageIDs;
    }

    /**
     * Load content elements for output (title, text)
     *
     * @param string $type
     *        The type of the content element (camel-case like in the database,
     *        e.g. 'Title' or 'Image').
     * @param array $row
     *        The contentitem data from database
     *
     * @return array
     *         Field values and labels for specified content element
     */
    protected function _loadContentElementOutput($type, $row)
    {
      if (empty($this->_contentElements[$type])) {
        return array();
      }

      $count = $this->_contentElements[$type];
      for ($i = 1; $i <= $count; $i++)
      {
        // retrieve element value und template variable name
        $tplName = $this->_getContentElementTemplateName(mb_strtolower($type), $count, $i);
        $colName = $this->_getContentElementColumnName($type, $count, $i);
        $output[$tplName] = parseOutput($row[$colName], $type == 'Title' ? 2 : 0);

        // retrieve element label and template variable name
        $tplName = $this->_contentPrefix.'_'.mb_strtolower($type).($count > 1 ? $i : '').'_label';
        $output[$tplName] = $this->_getContentElementTemplateLabel($type, $count, $i);
      }

      return $output;
    }

    /**
     * Load content images for output
     *
     * @param array $row
     *        The contentitem data from database
     *
     * @return array
     *         Field values and labels for images
     */
    protected function _loadContentElementImageOutput($row)
    {
      global $_LANG;

      if (empty($this->_contentElements['Image'])) {
        return array();
      }

      // Notice: ContentItemBG (in example) has got an image but no image title field.
      $imageTitles = array();
      if (isset($row[$this->_columnPrefix.'ImageTitles'])) {
        $imageTitles = $row[$this->_columnPrefix.'ImageTitles'];
        $imageTitles = $this->explode_content_image_titles($this->_contentPrefix, $imageTitles);
      }
      else {
        $imageTitles = $this->explode_content_image_titles($this->_contentPrefix, array());
      }

      $count = $this->_contentElements['Image'];
      for ($i = 1; $i <= $count; $i++)
      {
        $tplName = $this->_getContentElementTemplateName('image_src', $count, $i);
        $colName = $this->_getContentElementColumnName('Image', $count, $i);
        $tmpImage = $row[$colName];

        $tplNumber = $count > 1 ? $i : '';
        $tplName = $this->_contentPrefix.'_image'.$tplNumber.'_label';
        $output[$tplName] = $this->_getContentElementTemplateLabel('Image', $count, $i);

        $tplName = $this->_contentPrefix.'_required_resolution_label'.($count > 1 ? $i : '');
        $output[$tplName] = $this->_getImageSizeInfo($this->getConfigPrefix(), $i);

        $tplName = $this->_contentPrefix.'_large_image_available'.$tplNumber;
        $output[$tplName] = $this->_getImageZoomLink($this->_contentPrefix, $tmpImage);

        $imageDetails = $this->_getUploadedImageDetails($tmpImage, $this->_contentPrefix, $this->getConfigPrefix(), $tplNumber);
        $output = array_merge($output, $imageDetails);
      }

      return array_merge($output, $imageTitles);
    }

    /**
     * Return content item data from database (use within
     * ContentItem::get_content())
     *
     * @return mixed
     *         array containing the data or false, if an error occured
     */
    protected function _getData()
    {
      if ($this->_data !== null)
        return $this->_data;

      // Create database entries.
      $this->_checkDataBase();

      foreach ($this->_contentElements as $type => $count) {
        for ($i = 1; $i <= $count; $i++) {
          $sqlArgs[] = $this->_getContentElementColumnName($type, $count, $i);
        }
      }

      // Retrieve common contentitem data (ContentItem::_dataFields)
      // as well as contenttype specific data ($sqlArgs)
      $sql = ' SELECT ' . implode(', ', $this->_dataFields)
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }

    /**
     * Return the default template's name for usage in Template object
     *
     * @return string
     */
    protected function _getStandardTemplateName()
    {
      return 'content_site_' . $this->_contentPrefix;
    }

    /**
     * Return the default template's path
     *
     * @throws Exception
     *         if the contentitem's template is not available
     *
     * @param string $string
     *        the string to append to the standard template name i.e.
     *        content_types/ContentItemQP_Statement_$string.tpl
     *
     * @return string
     */
    protected function _getTemplatePath($string = '')
    {
      $tplPath = 'content_types/ContentItem' . $this->_templateSuffix;
      if ($string) $tplPath .= '_' . $string;
      $tplPath .= '.tpl';

      if (!is_file($this->tpl->get_root() . '/' . $tplPath)) {
        throw new Exception(__CLASS__ . ": Missing content_type template '$tplPath'");
      }

      return $tplPath;
    }

    /**
     * Gets the user data of current user tree, containing data only
     * available within logical levels from inside the user tree.
     *
     * @return string|array
     *         Empty string if there is no user data available or
     *         an array:<br>
     *         lo_ut_area_fu_id        => FUID,<br>
     *         lo_ut_area_fu_email     => FUEmail,<br>
     *         lo_ut_area_fu_firstname => FUFirstname,<br>
     *         lo_ut_area_fu_lastname  => FULastname,<br>
     *         lo_ut_area_fu_nick      => FUNick,
     */
    protected function _getUserTreeData() {

      if (!empty($this->_frontendUserData)) {
        return $this->_frontendUserData;
      }

      $currentPage = $this->_navigation->getPageByID($this->page_id);

      // Current tree isn't 'user' tree.
      if ($currentPage->getTree() != Navigation::TREE_USER) {
        return '';
      }

      $sql = ' SELECT FUID, FUNick, FUFirstname, FULastname, FUEmail '
           . " FROM {$this->table_prefix}frontend_user "
           . " JOIN {$this->table_prefix}contentitem "
           . '      ON FK_FUID = FUID '
           . ' WHERE CIID = ' . $currentPage->getID();

      // The frontend-user does not exist.
      $row = $this->db->GetRow($sql);
      if (!$row) {
        return '';
      }

      $this->_frontendUserData = array(
        'lo_ut_area_fu_id'        => $row['FUID'],
        'lo_ut_area_fu_email'     => $row['FUEmail'],
        'lo_ut_area_fu_firstname' => $row['FUFirstname'],
        'lo_ut_area_fu_lastname'  => $row['FULastname'],
        'lo_ut_area_fu_nick'      => $row['FUNick'],
      );

      return $this->_frontendUserData;
    }

    /**
     * Check if the contentitem has valid content, so the CHasContent field
     * can be set within the ContentItem::edit_content() method.
     *
     * @return bool
     */
    protected function _hasContent()
    {
      return true;
    }

    /**
     * Get subelement activation light link data. Call from ContentItem
     * subelement classes such as ContentItemCB_Boxes or ContentItemQS_Statements.
     *
     * For nested elements ( i.e. CA - areas with boxes ) use idParam / toParam
     * in order to privide unique url parameters. Non unique parameters might
     * result in unexpected results ( i.e. areas and boxes with same id will be
     * changed )
     *
     * @param array $row
     *          The array containing element data from database
     * @param array $settings
     *          - parentDisabledField : (string) the database field name of parent
     *          disabled field. If the parent element is disabled, but current
     *          element is active, the activation light has status
     *          ActivationLighInterface::YELLOW.
     *          - urlParams : (string) additional url parameters added to
     *          generated link ( i.e. scrollToAnchor can be set this way )
     *          - idParam : alternative id parameter ( replaces changeActivationID )
     *          - toParam : alternative to parameter ( replaces changeActivationTo )
     *
     * @throws Exception
     * @return array
     *         An array containing template variables for parsing subelemt
     */
    protected function _getActivationData($row, $settings = array())
    {
      global $_LANG;

      if (!isset($row[$this->_columnPrefix.'Disabled'])) {
        throw new Exception("Can not get activation link data. 'Disabled' field not available!");
      }

      $id = $row[$this->_columnPrefix.'ID'];
      $idParam = isset($settings['idParam']) ? $settings['idParam'] : 'changeActivationID';
      $toParam = isset($settings['toParam']) ? $settings['toParam'] : 'changeActivationTo';

      $activationLightLink = "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;$idParam=$id&amp;$toParam=";
      if ($row[$this->_columnPrefix.'Disabled'] == 1) {
        $activationLight = ActivationLightInterface::RED;
        $activationLightLink .= ContentBase::ACTIVATION_ENABLED;
      }
      else
      {
        if (isset($settings['parentDisabledField']) && $row[$settings['parentDisabledField']] == 1)
          $activationLight = ActivationLightInterface::YELLOW;
        else
          $activationLight = ActivationLightInterface::GREEN;
        $activationLightLink .= ContentBase::ACTIVATION_DISABLED;
      }

      if (isset($settings['urlParams']) && mb_strlen($settings['urlParams'])) {
        $activationLightLink .= '&amp;' . $settings['urlParams'];
      }

      $label = $_LANG['global_activation_light_'.$activationLight.'_label'];

      return array(
        $this->_contentPrefix.'_activation_light'       => $activationLight,
        $this->_contentPrefix.'_activation_light_label' => $label,
        $this->_contentPrefix.'_activation_light_link'  => $activationLightLink,
      );
    }

    /**
     * Update subelement activation status, if the fowllowing $_GET parameters are set
     *   - changeActivationID
     *   - changeActivationTo
     * Call from ContentItem subelement classes such as ContentItemCB_Boxes or
     * ContentItemQS_Statements.
     *
     * If both $settings parameters parentField and parentId are set, field and
     * value of page ( FK_CIID = $this->page_id ) is replaced by given settings.
     *
     * @param array $settings
     *        - parentField : the database field name of parent foreign key
     *          field
     *        - parentId : the id of the parent element
     *        - idParam : alternative id parameter ( replaces changeActivationID )
     *        - toParam : alternative to parameter ( replaces changeActivationTo )
     *
     * @return void
     */
    protected function _changeActivation($settings = array())
    {
      global $_LANG;

      $get = new Input(Input::SOURCE_GET);

      $idParam = isset($settings['idParam']) ? $settings['idParam'] : 'changeActivationID';
      $toParam = isset($settings['toParam']) ? $settings['toParam'] : 'changeActivationTo';

      $id = $get->readInt($idParam);
      $type = $get->readString($toParam, Input::FILTER_NONE);

      if (!$id || !$type) {
        return;
      }

      switch ( $type ) {
        case ContentBase::ACTIVATION_ENABLED;
          $to = 0;
          break;
        case ContentBase::ACTIVATION_DISABLED;
          $to = 1;
          break;
        default: return; // invalid activation status
      }

      $parentSql = " AND FK_CIID = $this->page_id ";
      if (isset($settings['parentField']) && isset($settings['parentId']))
        $parentSql = " AND {$settings['parentField']} = {$settings['parentId']} ";

      $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " SET {$this->_columnPrefix}Disabled = $to "
           . " WHERE {$this->_columnPrefix}ID = $id "
           . $parentSql;
      $this->db->query($sql);

      $this->_activationChanged = true;
      $msg = $_LANG[$this->_contentPrefix.'_message_activation_'.$type];
      $this->setMessage(Message::createSuccess($msg));
    }

    /**
     * Returns the config prefixes
     * - ContentItem::_configPrefix
     * - ContentItem::_contentPrefix  as a fallback for custom templates
     *   i.e. "ti1_" & "ti_"
     *
     * @return array
     */
    public function getConfigPrefix()
    {
      $array = array($this->_configPrefix, $this->_contentPrefix);
      return array_unique($array);
    }

    /**
     * Returns the configuration value for specified variable using the
     * ContentItem's prefix ( config prefix and content prefix ). Note, that the
     * configuration variable's name $name should not contain the prefix.
     *
     * i.e. ContentItemTI::_getConfig('my_example_var') returns
     *      ti1_my_example_var or
     *      ti_my_example_var
     *      for ContentItemTI template 1 ( TI1 )
     *
     * @param string $name
     *        the prefix variable's name without prefix ( the prefix is
     *        automatically provided by this method )
     * @param int $index [optional]
     *        the index to use for array config variables such as site based
     *        config values, 0 is used as fallback if available
     *
     * @return mixed | null
     *         The configuration value, null if not found
     */
    public function getConfig($name, $index = null)
    {
      $prefixes = array($this->_configPrefix, $this->_contentPrefix);
      return ConfigHelper::get($name, $prefixes, $index);
    }

    /**
     * Returns true if activation status was changed.
     *
     * @return boolean
     */
    public function hasActivationChanged()
    {
      return $this->_activationChanged;
    }

    /**
     * Initializes the empty ContentItem::_subelements list and reads
     * subelements.
     *
     * This function is called by ContentItem::_construct and should be
     * overwritten by any class extending the ContentItem class and posessing
     * subelements such as 'boxes' or 'areas'. Ensure to call
     * parent::_readSubElements() when overwriting this method, or initialize
     * the empty ContentItem::_subelements manually.
     *
     * @return void
     */
    protected function _readSubElements()
    {
      $this->_subelements = new ContentItemSubelementList();
    }

    /**
     * Returns true if the contentitem is edited / processed
     *
     * @return boolean
     */
    public function isProcessed()
    {
      $post = new Input(Input::SOURCE_POST);
      if ($this->getProcessedValue()) {
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * Returns the request value ( $_POST, $_GET ) identifying, which type of
     * processing is requested.
     *
     * @return string
     *         the editing / processing type, empty string if the ContentItem
     *         is not processed
     *         I.e. 'process', 'process_date' or the value for processing
     *               one of the ContentItems's subelements
     *               i.e. 'process_qs_statement'
     */
    public function getProcessedValue()
    {
      $value = $this->_subelements->getProcessedValue();

      // currently no subelements are processed, so we check if the contentitem
      // itself is processed
      if (!$value) {
        $request = new Input(Input::SOURCE_REQUEST);
        foreach ($this->_processedValues() as $val) {
          if ($request->exists($val)) {
            $value = $val;
          }
        }
      }

      return $value;
    }

    /**
     * Returns the request values, that indicate, that the ContentItem has to be
     * processed by calling the ContentItem::edit_content method.
     *
     * Overwrite this method for any ContentItem with special process
     * parameters. Do not forget to merge with these default values, if they are
     * available for extending class as well.
     *
     * @return array
     */
    protected function _processedValues()
    {
      return array('dimg',
                   'changePageActivationID',
                   'process',
                   'process_date',
                   'process_save',);
    }

    /**
     * Returns the file URL for backend users
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function _fileUrlForBackendUser($filePath)
    {
      return $this->_fileUrlForBackendUserOnSite($filePath, $this->site_id);
    }

    /**
     * Creates the extended data for the given id(s) for items of the current
     * content item type. ( i.e. use in ContentItemQP_Statements )
     *
     * Multiple ids can be provided using an array for $extendableId.
     *
     * @param int|array $extendableId
     *
     * @throws \ReflectionException
     */
    protected function _createExtendedData($extendableId)
    {
      if (ConfigHelper::get('m_extended_data')) {
        $this->_extendedDataService()->createExtendedData($this, $extendableId);
      }
    }

    protected function _updateExtendedData($extendableId)
    {
      if (ConfigHelper::get('m_extended_data')) {
        $this->_extendedDataService()->updateExtendedData($this, $extendableId);
      }
    }

    /**
     * Deletes the extended data for the given id(s) for the current content
     * item. Multiple ids can be provided using an array for $extendableId.
     *
     * @param int|array $extendableId
     *
     * @throws \ReflectionException
     */
    protected function _deleteExtendedData($extendableId)
    {
      if (ConfigHelper::get('m_extended_data')) {
        $this->_extendedDataService()->deleteExtendedData($this, $extendableId);
      }
    }

    /**
     * Deletes the extended data for the given content item and its' id
     *
     * @param \ContentItem $contentItem
     * @param int          $id
     *
     * @throws \ReflectionException
     */
    protected function _deleteExtendedDataByContentItem(\ContentItem $contentItem, $id)
    {
      if (ConfigHelper::get('m_extended_data')) {
        $this->_extendedDataService()->deleteExtendedData($contentItem, (int)$id);
      }
    }

    /**
     * @param int $extendableId
     *
     * @return array
     *
     * @throws \Core\Db\Exceptions\QueryException
     */
    protected function _getContentExtensionData($extendableId)
    {
      return ConfigHelper::get('m_extended_data') ?
        $this->_extendedDataService()->getContentExtensionData($this, $extendableId) : array();
    }

    /**
     * @return Core\Services\ExtendedData\ExtendedDataService
     */
    private function _extendedDataService()
    {
      return Container::make('Core\Services\ExtendedData\ExtendedDataService');
    }
  }
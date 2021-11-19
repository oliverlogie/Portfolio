<?php

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
  class ContentItemLogical extends ContentItem
  {
    /**
     * Describes the greyed out application lock.
     *
     * The application lock is greyed out if the content item is enabled because
     * only the 'disabled' state can be locked.
     *
     * @var string
     */
    const ACTIVATION_LOCK_GREYED = 'greyed';
    /**
     * Describes the unlocked application lock.
     *
     * The application lock is unlocked if the content item is disabled but not
     * locked.
     *
     * @var string
     */
    const ACTIVATION_LOCK_UNLOCKED = 'unlocked';
    /**
     * Describes the locked application lock.
     *
     * The application lock is locked if the content item is disabled and locked.
     *
     * @var string
     */
    const ACTIVATION_LOCK_LOCKED = 'locked';

    protected $_contentImageTitles = false;

    /**
     * Stores ID of the edited item
     *
     * @var int ID
     */
    private $_lastEditedItemID = null;

    /**
     * Cached campaigns and campaign types.
     *
     * @var array
     */
    private $_cachedCampaignResult = null;

    private $_post;
    private $_get;
    private $_functionAdditionalText;
    private $_functionAdditionalTextLevel;
    private $_functionAdditionalImage;
    private $_functionAdditionalImageLevel;
    private $_functionForm;
    private $_functionTaglevel;
    private $_functionTags;
    private $_functionMobileSwitch;
    private $_functionSeoManagement = array();

    public function __construct($site_id, $page_id, Template $tpl, db $db,
        $table_prefix, $action = '', $page_path = '', User $user = null,
        Session $session = null, Navigation $navigation
    ) {
      global $_MODULES;

      parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
          $page_path, $user, $session, $navigation);

      $this->_post = new Input(Input::SOURCE_POST);
      $this->_get = new Input(Input::SOURCE_GET);
      $this->_functionAdditionalText = new CFunctionAdditionalText(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionAdditionalTextLevel = new CFunctionAdditionalTextLevel(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionAdditionalImage = new CFunctionAdditionalImage(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionAdditionalImageLevel = new CFunctionAdditionalImageLevel(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionTaglevel = new CFunctionTaglevel(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionTags = new CFunctionTags(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionMobileSwitch = new CFunctionMobileSwitch(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      $this->_functionForm = new CFunctionForm(
          $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
    }

    /**
     * Edit content
     */
    public function edit_content()
    {
      global $_LANG, $_MODULES;

      $post = new Input(Input::SOURCE_POST);
      $currentPage = $this->_navigation->getCurrentPage();
      $currentSite = $this->_navigation->getCurrentSite();

      if ($this->_changePageActivation()) {
        return;
      }

      if (isset($_POST["process"])) {
        foreach ($_POST["process"] as $id => $value) {
          $lo_ciid = (int)$id;
        }
        $this->_processSelectedGroups();
      }
      else if (isset($_POST["process_date"])) {
        $lo_ciid = $post->readKey('process_date');
      }

      if (isset($_POST["lo{$lo_ciid}_lockimage"])) $lo_lockimage = 1;
      else $lo_lockimage = 0;
      if (isset($_POST["lo{$lo_ciid}_lockimage2"])) $loLockImage2 = 1;
      else $loLockImage2 = 0;
      if (isset($_POST["lo_reset_path"])) $lo_reset_path = 1;
      else $lo_reset_path = 0;

      $itemPage = $this->_navigation->getPageByID($lo_ciid);
      if ($itemPage) {
        $this->_updatePageAdditionalTextFromRequest($itemPage);
        $this->_updatePageAdditionalTextLevelFromRequest($itemPage);
        $this->_updatePageAdditionalImageFromRequest($itemPage);
        $this->_updatePageAdditionalImageLevelFromRequest($itemPage);;
        $this->_updatePageBlogFromRequest($itemPage);
        $this->_updatePageShareFromRequest($itemPage);
        $this->_updatePageTaggableFromRequest($itemPage);
      }

      $this->_lastEditedItemID = $lo_ciid;

      $result = false;
      // save data
      // store optional / additional data for IB, IP or VA -> 99999
      if ($lo_ciid == 99999) {
        $this->editOptionalData();
      }
      else
      { // subitem data
        if (isset($_POST["process_date"]))
        {
          $newDates = $this->_readLogicalItemsDate($lo_ciid);
          if (!empty($newDates))
          {
            // The only case in which we may not allow time-control is if that item
            // is the last enabled child under its (enabled) parent.
            // (But we may allow this in the future, because it is also possible to
            // delete the last enabled child in which case the parent is disabled.)
            if ((!empty($newDates['CShowFromDate']) || !empty($newDates['CShowUntilDate']))
                 && $this->_isLastActiveItem($this->_lastEditedItemID, $this->_navigation->getPageByID($this->_lastEditedItemID)->getParent()->getID()))
            {
              $this->setMessage(Message::createFailure($_LANG['lo_message_time_control_on_last_active_not_possible']));
              return false;
            }
            else
            {
              $sql = "UPDATE {$this->table_prefix}contentitem "
                   . "SET CShowFromDate = '{$newDates['CShowFromDate']}', "
                   . "CShowUntilDate = '{$newDates['CShowUntilDate']}' "
                   . "WHERE CIID = $lo_ciid ";
              $result = $this->db->query($sql);
            }
          }
        }
        else
        {
          $tagsFunction = new CFunctionTags($this->db, $this->table_prefix,
              $this->session, $this->_navigation, $_MODULES);
          $tagsAvailable = $tagsFunction->isActive() &&
              $tagsFunction->isAvailableOnPage($this->_navigation->getPageById($lo_ciid)) &&
              $tagsFunction->isAvailableForUser($this->_user, $currentSite);
          // save selected tags
          if ($tagsAvailable) {

            $sql = " DELETE FROM {$this->table_prefix}contentitem_tag "
                 . " WHERE FK_CIID = $lo_ciid ";
            $this->db->query($sql);

            $tags = $post->readArrayIntToInt('lo_tag');
            if ($tags) {
              $sqlTags = array();
              foreach ($tags as $val) {
                $sqlTags[] = "( $lo_ciid, $val )";
              }
              $sql = " INSERT INTO {$this->table_prefix}contentitem_tag "
                   . " (FK_CIID, FK_TAID) VALUES " . implode(',', $sqlTags);
              $this->db->query($sql);
            }
          }

          $sql = " SELECT CImage, CLockImage, CImage2, CLockImage2 "
               . " FROM {$this->table_prefix}contentabstract "
               . " WHERE FK_CIID = $lo_ciid ";
          $row = $this->db->GetRow($sql);
          $components = array($this->site_id, $lo_ciid, 'box');
          $lo_image = '';
          $loImage2 = '';
          // Existing images of child pages should not be deleted, they are maybe used again
          // if a custom image would be deleted.
          $existingImgName = '';
          if (strcmp('lo', mb_substr($row['CImage'], 4, 2)) == 0) {
            $existingImgName = $row['CImage'];
          }
          $existingImg2Name = '';
          if (strcmp('lo', mb_substr($row['CImage2'], 4, 2)) == 0) {
            $existingImg2Name = $row['CImage2'];
          }
          if (isset($_FILES["lo{$lo_ciid}_image"]) && $_FILES["lo{$lo_ciid}_image"]['tmp_name']) {
            // create a smaller version of image for second box image if there isn't
            // an extra file uploaded and it isn't locked
            if (isset($_FILES["lo{$lo_ciid}_image2"]) && !$_FILES["lo{$lo_ciid}_image2"]['tmp_name'] && !$loLockImage2) {
              $lo_image = $this->_storeImage($_FILES["lo{$lo_ciid}_image"], $existingImgName, 'lo', 0, $components, false);
              // create second image if first has been stored successfully
              if ($lo_image) {
                $loImage2 = $this->_storeImageWithSize($_FILES["lo{$lo_ciid}_image"],
                    $existingImg2Name, ConfigHelper::get('lo_image_width2'),
                    ConfigHelper::get('lo_image_height2'), ConfigHelper::get('lo_selection_width2'),
                    ConfigHelper::get('lo_selection_height2'), 'lo', 2, $components);
              }
            }
            else {
              $lo_image = $this->_storeImage($_FILES["lo{$lo_ciid}_image"], $existingImgName, 'lo', 0, $components, false);
            }
          }
          // If there was no image uploaded but the existing image was not locked
          // before and should now be locked, then we move the existing box image
          // to a new file (it doesn't matter if the existing box image is from
          // the content or a manual box image, the move is always performed).
          if (!$lo_image && !$row['CLockImage'] && $lo_lockimage) {
            if (isset($row['CImage']) && $row['CImage']) {
              $loImageSrc = $row['CImage'];
            }
            else
            {
              // If the page itself has no image we take it from its preferred child page.
              $page = $this->_navigation->getPageByID($lo_ciid);
              $preferredChildPage = $page->getPreferredChild();
              if ($preferredChildPage) {
                $loImageSrc = $preferredChildPage->getImage();
              }
            }

            $lo_image = $this->_storeImage('../' . $loImageSrc, $loImageSrc, 'lo', 0, $components, false);
          }

          // Same handling for second picture
          if (!$loImage2 && isset($_FILES["lo{$lo_ciid}_image2"]) && $_FILES["lo{$lo_ciid}_image2"]['tmp_name']) {
            $loImage2 = $this->_storeImage($_FILES["lo{$lo_ciid}_image2"], $existingImg2Name, 'lo', 2, $components, false);
          }
          if (!$loImage2 && !$row['CLockImage2'] && $loLockImage2) {
            if (isset($row['CImage2']) && $row['CImage2']) {
              $loImageSrc2 = $row['CImage2'];
            }
            else
            {
              // If the page itself has no image we take it from its preferred child page.
              $page = $this->_navigation->getPageByID($lo_ciid);
              $preferredChildPage = $page->getPreferredChild();
              if ($preferredChildPage) {
                $loImageSrc2 = $preferredChildPage->getImage2();
              }
            }
            $loImage2 = $this->_storeImage('../' . $loImageSrc2, $loImageSrc2, 'lo', 2, $components, false);
          }

          $shortTextManual = $post->readString('lo_manual_shorttext', Input::FILTER_SHORT_TEXT);
          // Truncate the manual short text
          $shortTextMaxlength = ConfigHelper::get('ci_shorttext_maxlength');
          $shortTextAftertext = ConfigHelper::get('shorttext_aftertext', 'ci');
          $shortTextCutExact = ConfigHelper::get('shorttext_cut_exact', 'ci');
          $shortTextManual = StringHelper::setText($shortTextManual)
                             ->purge(ConfigHelper::get('be_allowed_html_level3'))
                             ->truncate($shortTextMaxlength, $shortTextAftertext, $shortTextCutExact)
                             ->getText();
          $sqlShortTextManual = 'NULL';
          if ($shortTextManual) {
            $sqlShortTextManual = "'{$this->db->escape($shortTextManual)}'";
          }

          $title = $post->readString('lo_title', Input::FILTER_PLAIN);
          $sql = " UPDATE {$this->table_prefix}contentitem ci "
               . "        JOIN {$this->table_prefix}contentabstract ca "
               . '        ON ci.CIID = ca.FK_CIID '
               . " SET ci.CTitle = '{$this->db->escape($title)}', "
               . ($lo_image ? " ca.CImage = '$lo_image', " : '')
               . ($loImage2 ? " ca.CImage2 = '$loImage2', " : '')
               . "     ca.CShortTextManual = $sqlShortTextManual, "
               . "     ca.CLockImage = '$lo_lockimage', "
               . "     ca.CLockImage2 = '$loLockImage2' "
               . " WHERE CIID = $lo_ciid ";
          $result = $this->db->query($sql);

          $this->_getFunctionSeoManagement($lo_ciid)->setVarsFromPost($post)->update();

          // spider content => spider changed navigation title
          $ci = ContentItem::create($this->site_id, $lo_ciid, $this->tpl,
                  $this->db, $this->table_prefix, '', $this->_user, $this->session,
                  $this->_navigation);
          $ci->spiderContent();

          // may attach a campaign form or change form email addresses
          if ($post->readInt('lo_newitem_form'))
          {
            // first get all campaign - contentitem connections.
            // we need them to check that no duplicate campaign forms will be attached.
            $sql = ' SELECT CGCID, FK_CIID, CGCCampaignRecipient '
                 . " FROM {$this->table_prefix}campaign_contentitem "
                 . " WHERE FK_CGID = '{$this->db->escape($post->readInt('lo_newitem_form'))}' ";
            $res = $this->db->query($sql);
            $itemForms = array();
            while ($cgCiRow = $this->db->fetch_row($res))
            {
              $itemForms[$cgCiRow['FK_CIID']] = array (
                'CGCCampaignRecipient' => $cgCiRow['CGCCampaignRecipient'],
                'CGCID' => $cgCiRow['CGCID'],
              );
            }

            $campaignRecipients = $this->_getFormMailRecipients($post->readString('lo_newitem_form_recipients'));
            if ($post->readInt('has_children'))
            {
              $children = $this->_navigation->getPageByID($lo_ciid)->getAllChildren();
              $values = array();
              foreach ($children as $child)
              {
                // do not display if forms can not be attached or the page is
                // a level
                if (!$this->_functionForm->isAvailableOnPage($child) || $child->isLevel()) {
                  continue;
                }

                // do not add already attached forms of child items
                if (!isset($itemForms[$child->getID()])) {
                  $values[] = "( '".$child->getID()."', '".$post->readInt('lo_newitem_form')."', '".$campaignRecipients."' ) ";
                }
                // just update email recipients of already attached forms of child items
                else if ($campaignRecipients)
                {
                  $formRecipients = ($itemForms[$child->getID()]['CGCCampaignRecipient']) ?  $itemForms[$child->getID()]['CGCCampaignRecipient'].','.$campaignRecipients : $campaignRecipients;
                  $sql = " UPDATE {$this->table_prefix}campaign_contentitem "
                       . " SET CGCCampaignRecipient = '{$formRecipients}' "
                       . " WHERE CGCID = {$itemForms[$child->getID()]['CGCID']} ";
                  $this->db->query($sql);
                }
              }
              // also assign LO's id to campaign form. we will never use this to print a form on the frontend,
              // but we will use it to delete forms of LO's children.
              if (!isset($itemForms[$lo_ciid])) {
                $values[] = "( '".$lo_ciid."', '".$post->readInt('lo_newitem_form')."', '".$campaignRecipients."' ) ";
              }
            }
            else if (!isset($itemForms[$lo_ciid])) {
              $values = " ( {$lo_ciid}, {$post->readInt('lo_newitem_form')}, '{$campaignRecipients}' ) ";
            }

            if (isset($values) && $values)
            {
              $sql = " INSERT INTO {$this->table_prefix}campaign_contentitem "
                   . ' (FK_CIID, FK_CGID, CGCCampaignRecipient) '
                   . ' VALUES '
                   . (is_array($values) ? implode(',', $values) : $values);
              $this->db->query($sql);
            }
            else {
              $this->setMessage(Message::createFailure($_LANG['lo_message_duplicate_form_attachement_failure']));
            }
          }
          unset($formRecipients);
          if ($post->exists('lo_attached_form_recipients'))
          {
            $formRecipients = $post->readArrayIntToString('lo_attached_form_recipients');
            $ciids = array();
            if ($post->readInt('has_children'))
            {
              $children = $this->_navigation->getPageByID($lo_ciid)->getAllChildren();
              foreach ($children as $child)
              {
                if ($child->getType() == ContentType::TYPE_NORMAL && $this->_functionForm->isAvailableOnPage($child)) {
                  $ciids[] = $child->getID();
                }
              }
            }
            $ciids[] = $lo_ciid;
            foreach ($formRecipients as $cgid => $recipients)
            {
              $recipients = $this->_getFormMailRecipients($recipients);
              $sql = " UPDATE {$this->table_prefix}campaign_contentitem "
                   . " SET CGCCampaignRecipient = '{$recipients}' "
                   . " WHERE FK_CGID = {$cgid} "
                   . "   AND FK_CIID IN (".implode(', ', $ciids).") ";
              $this->db->query($sql);
            }
          }
        }
      }

      $result2 = $this->db->query("SELECT CDisabled,CIIdentifier,CPosition,CType,FK_CTID,FK_SID,FK_CIID from ".$this->table_prefix."contentitem WHERE CIID=".$lo_ciid);
      $row2 = $this->db->fetch_row($result2);
      $this->db->free_result($result2);

      // change path name
      if ($lo_reset_path){
        $lo_oldpath = $row2["CIIdentifier"];
        $lo_newpath = Container::make('Core\Url\ContentItemPathGenerator')->generateChildPath($this->page_path, $post->readString("lo_title", Input::FILTER_PLAIN), $this->site_id, $lo_ciid);
        if ($lo_newpath != $lo_oldpath) {
          // change path
          $result = $this->db->query("UPDATE ".$this->table_prefix."contentitem SET CIIdentifier='".$lo_newpath."' WHERE CIID=".$lo_ciid);
          // change subpaths
          $result = $this->db->query("UPDATE ".$this->table_prefix."contentitem SET CIIdentifier=CONCAT('".$lo_newpath."',SUBSTRING(CIIdentifier FROM ".(mb_strlen($lo_oldpath)+1).")) WHERE CIIdentifier LIKE '".$lo_oldpath."/%' AND FK_SID=".$row2["FK_SID"]);
        }
        // else: title not changed -> don't change path
      }

      if ($result) {
        $this->setMessage(Message::createSuccess($_LANG['lo_message_success']));
      }
    }

    /**
     * Edit optional data of the logical level type IB, IP or VA.
     */
    public function editOptionalData($imageOnly = false)
    {
      global $_LANG, $_LANG2;

      $post = new Input(Input::SOURCE_POST);

      $sql = " SELECT csub.FK_CIID, {$this->_columnPrefix}Image AS Image, ci.FK_CTID "
            ." FROM {$this->table_prefix}contentitem ci "
            ." LEFT JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} csub "
            .'      ON ci.CIID = csub.FK_CIID '
            ." WHERE ci.CIID = {$this->page_id} ";
      $row = $this->db->GetRow($sql);

      $title = $post->readString('lo_iblevel_title', Input::FILTER_CONTENT_TITLE);
      $text = $post->readString('lo_iblevel_text', Input::FILTER_CONTENT_TEXT);
      $image = null;

      // store image if it has been uploaded
      if (isset($_FILES['lo_iblevel_image']) && $_FILES['lo_iblevel_image']['tmp_name']) {
        $image = $this->_storeImage($_FILES['lo_iblevel_image'], $row['Image'], $this->getConfigPrefix(), 0, null, null, true);
      }

      // the FK_CIID is null if there hasn't been content saved for this level
      if (!$row['FK_CIID'])
      {
        $sql = " INSERT INTO {$this->table_prefix}contentitem_{$this->_contentPrefix} "
             . " (FK_CIID) VALUES ($this->page_id) ";
        $result = $this->db->query($sql);
      }

      // insert an empty string if there is no image available
      $image = ($image) ? ", {$this->_columnPrefix}Image = '$image' " : '';
      // update images only ?
      if ($imageOnly && $image) {
        $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
             . ' SET ' . mb_substr($image, 1, mb_strlen($image))
             . " WHERE FK_CIID = $this->page_id ";
        $result = $this->db->query($sql);
      }
      else
      {
        $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
             . " SET {$this->_columnPrefix}Title='{$this->db->escape($title)}', "
             . "     {$this->_columnPrefix}Text='{$this->db->escape($text)}' "
             . $image
             . " WHERE FK_CIID = $this->page_id ";
        $result = $this->db->query($sql);

        $this->setShortTextAndImages(true);

        // get the selected attribute(s) for variation level VA and store them
        // in the database
        if ($row['FK_CTID'] == 79)
        {
          // remove attributes of current content item from the database
          $sql = ' DELETE '
                ." FROM {$this->table_prefix}contentitem_{$this->_contentPrefix}_attributes "
                ." WHERE FK_CIID = {$this->page_id} ";
          $this->db->query($sql);

          $sql = ' SELECT AID '
                ." FROM {$this->table_prefix}module_attribute_global "
                .' WHERE FK_CTID = 79 '
                ." AND FK_SID = {$this->site_id} ";
          $attrGroups = $this->db->GetCol($sql);

          /**
           * Read all possibly selected attributes and store it in the database.
           * If there isn't an attribute selected by the user, retrieve and store
           * the selected value from the next group of attributes
           */
          foreach ($attrGroups as $attrGroupID)
          {
            $attrID = $post->readInt("lo_iblevel_attr{$attrGroupID}");
            // no attribute selected from this group
            if (!$attrID) {
              continue;
            }
            $sql = "INSERT INTO {$this->table_prefix}contentitem_{$this->_contentPrefix}_attributes "
                  .'(FK_CIID, FK_AVID) '
                  .' VALUES '
                  ."($this->page_id, $attrID)";
            $this->db->query($sql);
          }
        }
      }

      $this->setMessage(Message::createSuccess($_LANG["lo_message_{$this->_contentPrefix}level_data_success"]));

      // update linked content
      if ($this->_structureLinksAvailable && $this->_structureLinks &&
          $post->exists('lo_iblevel_image_structure_link1'))
      {
        foreach ($this->_structureLinks as $pageID)
        {
          $page = $this->_navigation->getPageByID($pageID);
          $site = $page->getSite();
          $ci = ContentItem::create($site->getID(), $pageID, $this->tpl, $this->db,
                                    $this->table_prefix, $this->action, $this->_user,
                                    $this->session, $this->_navigation);
          $ci->editOptionalData(true); // image only
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function delete_content(){
      global $_LANG;

      // delete manual set boximage
      $sql = ' SELECT CImage'
           . " FROM {$this->table_prefix}contentabstract "
           . " WHERE FK_CIID = $this->page_id ";
      $lo_boximage = $this->db->GetOne($sql);
      unlinkIfExists("../$lo_boximage");

      // delete logical level
      $this->delete_content_item(true);

      // delete subcontent
      $sql = 'SELECT CIID, CIIdentifier, CType, FK_CTID '
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)) {
        $deletePage = ContentItem::create($this->site_id, $row['CIID'],
                                          $this->tpl, $this->db, $this->table_prefix,
                                          $this->action, $this->_user, $this->session,
                                          $this->_navigation);
        if ($deletePage instanceof ContentItem) {
          $deletePage->delete_content();
        }
      }
      $this->db->free_result($result);

      return $_LANG["lo_message_deleteitem_success"];
    }

    public function sendResponse($request)
    {
      $get = new Input(Input::SOURCE_GET);

      switch($request) {
        case 'SubitemContent' :
          return $this->_getContentSubitem($get->readInt('subitem'));
        default:
          return parent::sendResponse($request);
      }
    }

    /**
     * @see ContentItem::_processedValues()
     *
     * Removes the 'dimg' parameter from default values, as dimg is still
     * handled in ContentItemLogical::get_content()
     *
     * @return array
     */
    protected function _processedValues()
    {
      $defaults = parent::_processedValues();
      $indexOf = array_search('dimg', $defaults, true);
      array_splice($defaults, $indexOf, 1);

      return $defaults;
    }

    /**
     * Changes the activation lock of a content item if the GET parameters changeActivationLockID and changeActivationLockTo are set.
     */
    private function _changeActivationLock()
    {
      global $_LANG;

      $get = new Input(Input::SOURCE_GET);

      $changeActivationLockID = $get->readInt('changeActivationLockID');
      $changeActivationLockTo = $get->readString('changeActivationLockTo', Input::FILTER_NONE);

      if (!$changeActivationLockID || !$changeActivationLockTo) {
        return;
      }

      $sql = 'SELECT CDisabled, CDisabledLocked '
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE CIID = $changeActivationLockID ";
      $row = $this->db->GetRow($sql);

      $existingActivationLock = $row['CDisabledLocked'] ? self::ACTIVATION_LOCK_LOCKED
                                                        : self::ACTIVATION_LOCK_UNLOCKED;

      // If the content item is not disabled we do not change the activation.
      if (!$row['CDisabled'] || $changeActivationLockTo == $existingActivationLock) {
        return;
      }

      switch ($changeActivationLockTo) {
        case self::ACTIVATION_LOCK_LOCKED:
          $sql = "UPDATE {$this->table_prefix}contentitem "
               . 'SET CDisabledLocked = 1 '
               . "WHERE CIID = $changeActivationLockID ";
          $result = $this->db->query($sql);

          $this->setMessage(Message::createSuccess($_LANG['lo_message_activation_lock_success']));
          break;
        case self::ACTIVATION_LOCK_UNLOCKED:
          $sql = "UPDATE {$this->table_prefix}contentitem "
               . 'SET CDisabledLocked = 0 '
               . "WHERE CIID = $changeActivationLockID ";
          $result = $this->db->query($sql);

          $this->setMessage(Message::createSuccess($_LANG['lo_message_activation_unlock_success']));
          break;
        default:
          return;
      }
    }

    /**
     * May deletes a form attachement.
     *
     * @return boolean
     *         True on success, otherwise false.
     */
    private function _deleteFormAttachement()
    {
      global $_LANG, $_MODULES;

      $get = new Input(Input::SOURCE_GET);
      if ($this->_functionForm->isAvailableForUser($this->_user, $this->_navigation->getSiteByID($this->site_id))
         && $get->readInt('attached_form_did') && $get->readInt('ciid'))
      {
        $ciids = array();
        // also get ids of chidren contentitems if available
        if ($this->_navigation->getPageByID($get->readInt('ciid'))->hasChildren())
        {
          $children = $this->_navigation->getPageByID($get->readInt('ciid'))->getAllChildren();
          foreach ($children as $child) {
            if ($child->isRealLeaf() && $this->_functionForm->isAvailableOnPage($child)) {
              $ciids[] = $child->getID();
            }
          }
        }
        $ciids[] = $get->readInt('ciid');
        $sql = "DELETE FROM {$this->table_prefix}campaign_contentitem "
             . "WHERE FK_CGID = '{$get->readInt('attached_form_did')}' "
             . "  AND FK_CIID IN (".implode(', ', $ciids).") ";
        $res = $this->db->query($sql);

        $this->setMessage(Message::createSuccess($_LANG['lo_message_remove_form_attachement_success']));

        return true;
      }

      return false;
    }

    /**
     * Deletes an image if necessary.
     * @return boolean true if image has been deleted, otherwise false
     */
    private function _deleteImage()
    {
      global $_LANG;

      if ($this->_get->readInt('dimg') && $this->_get->exists('ofPage'))
      {
        $imgNumber = ($this->_get->readInt('dimg') == 1) ? '' : $this->_get->readInt('dimg');
        $pageId = $this->_get->readInt('ofPage');
        if ((empty($imgNumber) || $imgNumber == 2) && $pageId)
        {
          $sql = ' SELECT CImage'.$imgNumber
               . " FROM {$this->table_prefix}contentabstract"
               . ' WHERE FK_CIID = '.$pageId;
          $curImg = $this->db->GetOne($sql);
          // Delete only custom image files
          if (strcmp('lo', mb_substr($curImg, 4, 2)) == 0) {
            ContentBase::_deleteImageFiles($curImg);
          }

          // Initialize the content item
          $contentItem = ContentItem::create($this->site_id, $pageId, $this->tpl,
                                             $this->db, $this->table_prefix,
                                             '', $this->_user, $this->session, $this->_navigation);
          // Try to get the original image of the content item.
          $ciImg = $contentItem->getImage();

          // We have to modify the name of the content item (child) image
          // to get a small version of the image
          if ($ciImg) {
            switch($this->_get->readInt('dimg')) {
              case 1: $ciImg = mb_substr($ciImg,0,mb_strlen($ciImg)-mb_strlen(mb_strrchr($ciImg,".")))
                               ."-b".mb_strrchr($ciImg,".");
                break;
              case 2: $ciImg = mb_substr($ciImg,0,mb_strlen($ciImg)-mb_strlen(mb_strrchr($ciImg,".")))
                               ."-b2".mb_strrchr($ciImg,".");
                break;
              // should not happen, nevertheless handle this case
              default: $ciImg = '';
            }
          }
          // Remember it is possible that in old EDWIN versions (< 2.3.5), no
          // small version (IMAGENAME-b.ext|-b2.ext) of the original image exists anymore.
          if (!is_file('../'.$ciImg)) {
            $ciImg = 'NULL';
          } else {
            $ciImg = "'".$ciImg."'";
          }

          // Update the image name and unlock it
          $sql = " UPDATE {$this->table_prefix}contentabstract ca "
               . " SET ca.CImage{$imgNumber} = {$ciImg}, "
               . "     ca.CLockImage{$imgNumber} = 0"
               . ' WHERE FK_CIID  = '.$pageId;
          $result = $this->db->query($sql);

          $this->setMessage(Message::createSuccess($_LANG['lo_message_image_delete_success']));

          return true;
        }
        else
        {
          return false;
        }
      }
      // Delete a teaser image of VA, IB, IP or BE
      else if ($this->_get->exists('dimg') && $this->_get->exists('teaserImg')) {
        $this->_deleteContentImage('', $this->_contentPrefix, $this->_columnPrefix."Image");
        // Set last edited item id to open the box again.
        $this->_lastEditedItemID = 99999;
        $this->setMessage(Message::createSuccess($_LANG['lo_message_image_delete_success']));
        return true;
      }
      return false;
    }

    /**
     * Moves a content item if the GET parameters moveContentItemID and moveContentItemTo are set.
     */
    private function _moveContentItem()
    {
      global $_LANG;

      if (!isset($_GET['moveContentItemID'], $_GET['moveContentItemTo'])) {
        return;
      }

      $moveID = (int)$_GET['moveContentItemID'];
      $moveTo = (int)$_GET['moveContentItemTo'];

      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem",
                                           'CIID', 'CPosition',
                                           'FK_CIID', $this->page_id,
                                           'CPositionLocked');
      $moved = $positionHelper->move($moveID, $moveTo);

      if ($moved) {
        $this->setMessage(Message::createSuccess($_LANG['lo_message_contentitem_move_success']));
        $page = $this->_navigation->getPageByID($moveID);
        if ($page) {
          Container::make('ContentItemLogService')->logMoved(array(
              'FK_CIID'      => $page->getID(),
              'CIIdentifier' => $page->getDirectPath(),
              'FK_UID'       => $this->_user->getID(),
          ));
        }
      }
    }

    /**
     * {@inheritdoc}
     */
    public function get_content($params = array())
    {
      global $_LANG, $_MODULES;

      $post = new Input(Input::SOURCE_POST);
      $request = new Input(Input::SOURCE_REQUEST);
      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem",
                                           'CIID', 'CPosition',
                                           'FK_CIID', $this->page_id,
                                           'CPositionLocked');

      if ($this->page_path) $current_level = mb_substr_count($this->page_path,"/")+1;
      else $current_level = 0;

      $this->_moveContentItem();
      $this->_changeActivation();
      $this->_changeActivationLock();
      $this->_deleteImage();
      $this->_deleteFormAttachement();
      $this->_removeAdditionalImageIfRequested();
      $this->_changeMobileSwitchState();

      // after possible actions clear the navigation cache to ensure up-to-date
      // content when parsing view.
      $this->_navigation->clearCache($this->db, $this->table_prefix);
      $currentPage = $this->_navigation->getCurrentPage();
      $currentSite = $this->_navigation->getCurrentSite();

      $lo_newitem_position = 1;
      $lo_newitem_title = "";
      $lo_newitem_type = 0;
      $lo_message_from_newitem = 0;
      $campaignRecipients = '';
      $mCopy = new ModuleCopy(array(), $this->site_id, $this->tpl, $this->db, $this->table_prefix, $this->action,
                              $this->page_id, $this->_user, $this->session, $this->_navigation);
      $loModCopyActive = $mCopy->isActive();
      $mCopyAvailableForUser = $mCopy->isAvailableForUser($this->_user, $currentSite);
      $loModMobileSwitchAvailableForUser = $this->_functionMobileSwitch->isAvailableForUser($this->_user, $currentSite);

      // process form data / new item
      if (isset($_POST["process_new"]) || isset($_POST["process_new_contentedit"]))
      {
        $lo_newitem_title = $post->readString("lo_newitem_title", Input::FILTER_PLAIN);
        $lo_newitem_type = $post->readInt("lo_newitem_type");

        // read position or if none was set (no items inside level) -> insert new
        // item at position 1
        $lo_newitem_position = $post->readInt('lo_newitem_position', 1);

        // check input
        if (!mb_strlen(trim($lo_newitem_title)) || !$lo_newitem_type) {
          $this->setMessage(Message::createFailure($_LANG['lo_message_insufficient_input']));
          $lo_message_from_newitem = 1;
        }

        // for items inside the login navigation, where user rights can be
        // defined, display an error message if no groups have been selected.
        if (   $this->_loginFunctionsAvailable($currentPage, $current_level)
            && !$post->exists('lo_newitem_group')
        ) {
          $this->setMessage(Message::createFailure($_LANG['lo_message_no_group_selected']));
          $lo_message_from_newitem = 1;
        }

        // if module campaign mgmt is available, may create relation to existing campaign and
        // insert form mail recipients
        $campaignId = 0;
        if (    isset($_POST['lo_newitem_form'])
            && $this->_functionForm->isAvailableForUser($this->_user, $this->_navigation->getSiteByID($this->site_id))
            && !$this->_functionForm->isExcludedForContentTypeIdOnSite($lo_newitem_type, $this->_navigation->getSiteByID($this->site_id))
        ) {
          $campaignId = (int)$_POST['lo_newitem_form'];
          $campaignRecipients = $this->_getFormMailRecipients($_POST['lo_newitem_form_recipients']);
        }

        // Create new content item
        if (!$this->_getMessage()) {
          $factory = new ContentTypeFactory($this->db, $this->table_prefix);
          $ct = $factory->getById($lo_newitem_type);

          // we check if the new item is allowed here.
          // by doing so we avoid creating a new element from hitting F5 in the
          // browser and submitting the new item form again, although no items
          // are allowed anymore
          if ($this->_configHelper->newItemAt($currentPage, $ct)) {
            $newItemGroups = $post->readArray('lo_newitem_group');
            $cTypeClass = $ct->getClass();
            $cciModel = new CampaignContentItem($this->db, $this->table_prefix);
            $cciModel->id = $campaignId;
            $cciModel->recipient = $campaignRecipients;
            /* @var $ci ContentItem */
            $ci = new $cTypeClass($this->site_id, $this->page_id, $this->tpl, $this->db,
              $this->table_prefix, $this->action, $this->page_path, $this->_user, $this->session, $this->_navigation);
            // Insert new content item
            $itemID = $ci->build($lo_newitem_title, $lo_newitem_type, $lo_newitem_position, $currentPage->getID(), $newItemGroups, $cciModel);
            if ($itemID) {
              if (isset($_POST['process_new'])) {
                $this->setMessage(Message::createSuccess($_LANG['lo_message_success']));

                // All pages and children and other data should be loaded
                // to make sure everything is displayed correctly with the newly
                // created item
                Navigation::clearCache($this->db, $this->table_prefix);
                $currentPage = $this->_navigation->getCurrentPage();
                $currentSite = $this->_navigation->getCurrentSite();
              }
              else {
                header("Location: index.php?action=content&site=".$this->site_id."&page=".$itemID);
                exit;
              }
              $lo_newitem_title = "";
              $lo_newitem_type = 0;
              $lo_newitem_position = '';
            }
            else $lo_message_from_newitem = 1;
          }
        }
      }

      // get modules available for user
      $modules = array_flip($_MODULES);
      foreach ($modules as $key => $val)
        if (!$this->_user->AvailableModule($key, $this->site_id))
          unset ( $modules[$key] );
      $loModTreemgmt = isset($modules['treemgmtall']) || isset($modules['treemgmtleafonly']);
      $sharePermission = isset($modules['share']);
      $blogPermission = isset($modules['blog']);

      // load permitted paths for users
      $lo_user_permitted_paths = array();
      $result = $this->db->query("SELECT UPaths,UNick from ".$this->table_prefix."user,".$this->table_prefix."user_rights WHERE FK_UID=UID AND UPaths IS NOT NULL AND FK_SID=".$this->site_id);
      while ($row = $this->db->fetch_row($result)){
        foreach (explode(",",$row["UPaths"]) as $tmp_path)
          $lo_user_permitted_paths[$tmp_path][] = $row["UNick"];
      }
      $this->db->free_result($result);

      // initialize the required resolution labels for the first and second box
      // image (empty) as it is only set for IB, IP, LP
      $loReqResLabel = '';
      $reqResLabel = '';
      /**
       * Contenttypes IB & IP & PB:
       * if there is a minimum and maximum defined for box image
       * width, image height or both, create a different required resolution label
       */
      if ($currentPage->getContentTypeId() == 3 || $currentPage->getContentTypeId() == 77 || $currentPage->getContentTypeId() == 81)
      {
        $configWidth = ConfigHelper::get('lo_image_width');
        $configHeight = ConfigHelper::get('lo_image_height');
        if (is_array($configWidth) || is_array($configHeight))
        {
          // if there is no maximum value set, set the maximum value to the same
          // values as minimum given (i.e. $_CONFIG["lo_image_width"] = 300
          // is changed to $_CONFIG["lo_image_width"] = array(300, 300))
          $configWidth = ContentBase::_getMutableSizeArray($configWidth);
          $configHeight = ContentBase::_getMutableSizeArray($configHeight);

          // format output for mutable image sizes
          $widthLabel = $configWidth[0] != $configWidth[1] ?
                        sprintf($_LANG['global_upload_image_mutable_resolution'], $configWidth[0], $configWidth[1]) :
                        $configWidth[0];
          $heightLabel = $configHeight[0] != $configHeight[1] ?
                         sprintf($_LANG['global_upload_image_mutable_resolution'], $configHeight[0], $configHeight[1]) :
                         $configHeight[0];

          $loReqResLabel = sprintf($_LANG["global_required_resolution_label"],
                         $widthLabel, $heightLabel);
        }
        else {
          $loReqResLabel = sprintf($_LANG["global_required_resolution_label"],
                                   $configWidth, $configHeight);
        }
      }

      /**
       * Contenttypes IP & LP:
       * if there is a minimum and maximum defined for the second box image
       * width, image height or both, create a different required resolution label
       */
      if ($currentPage->getContentTypeId() == 77 || $currentPage->getContentTypeId() == 78 )
      {
        $configWidth = ConfigHelper::get('lo_image_width2');
        $configHeight = ConfigHelper::get('lo_image_height2');
        if (is_array($configWidth) || is_array($configHeight))
        {
          // if there is no maximum value set, set the maximum value to the same
          // values as minimum given (i.e. $_CONFIG["lo_image_width2"] = 300
          // is changed to $_CONFIG["lo_image_width2"] = array(300, 300))
          $configWidth = ContentBase::_getMutableSizeArray($configWidth);
          $configHeight = ContentBase::_getMutableSizeArray($configHeight);

          // format output for mutable image sizes
          $widthLabel = $configWidth[0] != $configWidth[1] ?
                        sprintf($_LANG['global_upload_image_mutable_resolution'], $configWidth[0], $configWidth[1]) :
                        $configWidth[0];
          $heightLabel = $configHeight[0] != $configHeight[1] ?
                         sprintf($_LANG['global_upload_image_mutable_resolution'], $configHeight[0], $configHeight[1]) :
                         $configHeight[0];

          $reqResLabel = sprintf($_LANG["global_required_resolution_label"],
                         $widthLabel, $heightLabel);
        }
        else {
          $reqResLabel = sprintf($_LANG["global_required_resolution_label"],
                                 $configWidth, $configHeight);
        }
      }

      $lo_items_count = count($currentPage->getAllChildren());
      // initialize paging /////////////////////////////////////////////////////
      $resultsPage = $request->readInt('offset', 1);
      $resultsPerPage = (int)ConfigHelper::get('lo_results_per_page');
      $pageNavigation = '';

      // check for invalid page offset
      if (($resultsPage * $resultsPerPage - $resultsPerPage) >= $lo_items_count)
        $resultsPage = 1;

      $offset = 0;
      if ($lo_items_count > $resultsPerPage) {
        $pagelink = "index.php?action=content&site=$this->site_id&page=$this->page_id&amp;offset=";
        $pageNavigation = create_page_navigation($lo_items_count, $resultsPage, 5, $resultsPerPage, $_LANG['lo_results_showpage_current'], $_LANG['lo_results_showpage_other'], $pagelink);
        $offset = (($resultsPage - 1) * $resultsPerPage);
      }

      $sqlLimit = '';
      if ($resultsPerPage >= 0) {
        $sqlLimit = 'LIMIT ';
        if ($offset > 0) $sqlLimit .= $offset . ', ';
        $sqlLimit .= $resultsPerPage . ' ';
      }
      //////////////////////////////////////////////////////////////////////////

      /**
       * Special handling for top level in login tree:
       * - initialize the group filter if set
       * - select only content items matching the current filter
       */
      if ($this->_loginFunctionsAvailable($currentPage, $current_level))
      {
        //Initialize filter settings for login tree (top level)
        // $filter stores group id
        $filter = coalesce($request->readInt('filter_type'),
                           $this->session->read('lo_filter_type'));
        $this->session->save('lo_filter_type', $filter);
        if ($request->exists('filter_type') && !$request->readInt('filter_type')) {
          $this->session->save('lo_filter_type', '');
          $filter = '';
        }
        $sqlFilter = '';
        if ($filter) {
          $sqlFilter = " AND fugp.FK_FUGID = $filter ";
        }

        $sql = " SELECT FK_FUGID "
             . " FROM {$this->table_prefix}frontend_user_group_sites "
             . " WHERE FK_SID = {$this->site_id}";
        $possibleGroupIds = $this->db->GetCol($sql);
        $possibleGroupIds = implode(', ', $possibleGroupIds);

        // only select items matching the current filter (or none if not activated)
        $sql = ' SELECT DISTINCT(CIID), CShortTextManual, COUNT(com.CID) AS allComments, '
             . '        CPosition, ( '
             . '        SELECT CIID '
             . "        FROM {$this->table_prefix}contentitem c_sub "
             . "        WHERE c_sub.CIIdentifier LIKE CONCAT(ci.CIIdentifier, '%')  "
             . '        AND ci.CIID != c_sub.CIID '
             . '        AND ( c_sub.CShowFromDate IS NOT NULL '
             . '              OR c_sub.CShowUntilDate IS NOT NULL ) '
             . '        LIMIT 1) AS TimedChild '
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentabstract ca "
           . '      ON ci.CIID = ca.FK_CIID '
           . " JOIN {$this->table_prefix}contenttype ct "
           . '      ON ct.CTID = ci.FK_CTID '
           . " LEFT JOIN {$this->table_prefix}frontend_user_group_pages fugp "
           . '      ON ci.CIID = fugp.FK_CIID '
           . " LEFT JOIN {$this->table_prefix}comments com "
           . '      ON ci.CIID = com.FK_CIID '
           . '      AND com.CCanceled = 0 '
           . '      AND com.CDeleted = 0 '
           . " WHERE ci.FK_CIID = $this->page_id "
           . $sqlFilter
           . " GROUP BY CIID, CShortTextManual, CIIdentifier "
           . ' ORDER BY CPosition ASC '
           . $sqlLimit;
        $resultItems = $this->db->GetAssoc($sql);
      }
      else
      {
        $sql = ' SELECT CIID, CShortTextManual, COUNT(com.CID) AS allComments, ( '
             . '        SELECT CIID '
             . "        FROM {$this->table_prefix}contentitem c_sub "
             . "        WHERE c_sub.CIIdentifier LIKE CONCAT(ci.CIIdentifier, '%')  "
             . '        AND ci.CIID != c_sub.CIID '
             . '        AND ( c_sub.CShowFromDate IS NOT NULL '
             . '              OR c_sub.CShowUntilDate IS NOT NULL ) '
             . '        LIMIT 1) AS TimedChild '
             . " FROM {$this->table_prefix}contentitem ci "
             . " JOIN {$this->table_prefix}contentabstract ca "
             . '      ON ci.CIID = ca.FK_CIID '
             . " JOIN {$this->table_prefix}contenttype ct "
             . '      ON ct.CTID = ci.FK_CTID '
             . " LEFT JOIN {$this->table_prefix}comments com "
             . '      ON ci.CIID = com.FK_CIID '
             . '      AND com.CCanceled = 0 '
             . '      AND com.CDeleted = 0 '
             . " WHERE ci.FK_CIID = $this->page_id "
             . " GROUP BY CIID, CShortTextManual, CIIdentifier "
             . ' ORDER BY CPosition ASC '
             . $sqlLimit;
        $resultItems = $this->db->GetAssoc($sql);
      }

      if ($this->_functionBlog->isActive() && $resultItems)
      {
        $sql = ' SELECT FK_CIID, COUNT(CID) '
             . ' FROM '.$this->table_prefix.'comments '
             . ' WHERE FK_CIID IN ('.implode(',', array_keys($resultItems)).') '
             . '   AND CPublished = 1 '
             . '   AND CCanceled = 0 '
             . '   AND CDeleted = 0 '
             . ' GROUP BY FK_CIID ';
        $publishedComments = $this->db->GetAssoc($sql);
      }

      // get attached form ids of parent item
      $sql = ' SELECT FK_CGID, CGCID '
           . " FROM {$this->table_prefix}campaign_contentitem "
           . " WHERE FK_CIID = {$this->page_id} ";
      $foParentFormIds = $this->db->GetAssoc($sql);

      $lo_items = array();
      foreach ($resultItems as $id => $row)
      {
        $id = (int)$id;
        $page = $this->_navigation->getPageByID($id);

        $position = $page->getPosition();
        $disabledLocked = $page->isDisabledLocked();
        $image = $page->getImage();
        $imageLocked = $page->isImageLocked();
        $image2 = $page->getImage2();
        $image2Locked = $page->isImage2Locked();
        $path = $page->getDirectPath();
        $contentType = $page->getContentTypeId();
        $className = $page->getContentTypeClass();
        $lockedContent = $page->isContentLocked();
        $blog = $page->getBlog() ? 1 : 0;
        $share = $page->getShare() ? 1 : 0;
        $title = $page->getTitle();
        $customTemplate = $page->getCustomTemplate();
        $langClassName = $customTemplate ? $className . $customTemplate : $className;
        $loModCopyAvailable = $mCopy->isAvailableForUser($this->_user, $currentSite) && $mCopy->isAvailableOnPage($page);
        $activationLight = $page->getActivationLight();
        $timingState = $page->getTimingState();

        $activationLightLink = "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;offset=$resultsPage&amp;changePageActivationID=$id&amp;changePageActivationTo=";
        if ($page->getActivationState() == NavigationPage::ACTIVATION_DISABLED_SELF)
          $activationLightLink .= self::ACTIVATION_ENABLED;
        else
          $activationLightLink .= self::ACTIVATION_DISABLED;
        //special treatment if timing is set for this page
        if ($timingState != NavigationPage::TIMING_DISABLED)
          $activationLightLink = "#lonav_time_$id";

        $activationLightLabel = $_LANG["lo_activation_light_{$activationLight}_label"];
        // page is visible with parent timing, but no timing for the page itself
        if ($activationLight == ActivationClockInterface::GREEN && !$page->getStartDate() && !$page->getEndDate())
          $activationLightLabel = $_LANG["lo_activation_light_clock_green_from_parent_label"];

        // Determine the activation lock.
        $activationLock = self::ACTIVATION_LOCK_GREYED;
        $activationLockLink = '';
        if ($activationLight == ActivationLightInterface::RED) {
          $activationLock = $disabledLocked ? self::ACTIVATION_LOCK_LOCKED : self::ACTIVATION_LOCK_UNLOCKED;
          $activationLockLink = "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;offset=$resultsPage&amp;changeActivationLockID={$id}&amp;changeActivationLockTo=";
          $activationLockLink .= $disabledLocked ? self::ACTIVATION_LOCK_UNLOCKED : self::ACTIVATION_LOCK_LOCKED;
        }
        $activationLockLabel = $_LANG["lo_activation_lock_{$activationLock}_label"];

        $lo_image = "";
        $loImageIcon = "";
        $lo_image_src = "";
        $lo_shorttext = "";
        $lo_users_list = "";
        // if current page is an image box level (ContentItemIB, ContentItemIP, ContentItemPB)
        if ($currentPage->getContentTypeId() == 3 || $currentPage->getContentTypeId() == 77 || $currentPage->getContentTypeId() == 81)
        {
          $lo_image_src = 'img/no_image.png';
          $lo_box_image_class = 'padding_l_95';
          if ($image) {
            $lo_image_src = '../' . $image;
            $lo_box_image_class = 'padding_l_35';
          }
          else
          {
            // If the page itself has no image we take it from its preferred child page.
            $preferredChildPage = $page->getPreferredChild();
            if ($preferredChildPage && $preferredChildPage->getImage()) {
              $lo_image_src = '../' . $preferredChildPage->getImage();
            }
          }
          $loImageIcon = '<img src="'.$lo_image_src.'" alt="" />';

          $this->tpl->load_tpl('content_site_lo_image', 'content_site_logical_image.tpl');
          $deleteImage = false;
          if ($image) {
            $ciPrefix = mb_substr($image, 4, 2);
            // only custom images can be deleted
            if (strcmp($ciPrefix, 'lo') == 0)
              $deleteImage = true;
          }
          $this->tpl->parse_if('content_site_lo_image', 'delete_image', $deleteImage, $this->get_delete_image('lo', 1, '&amp;ofPage='.$id));
          $lo_image = $this->tpl->parsereturn('content_site_lo_image', array (
            'lo_image_src' => $lo_image_src,
            'lo_image_label' => $_LANG["lo_image_label"]
          ));

          $lo_manual_shorttext = $row["CShortTextManual"];
          $this->tpl->load_tpl('content_site_lo_shorttext', 'content_site_logical_shorttext.tpl');
          $lo_shorttext = $this->tpl->parsereturn('content_site_lo_shorttext', array (
            'lo_manual_shorttext' => $lo_manual_shorttext,
            'lo_manual_shorttext_label' => $_LANG["lo_manual_shorttext_label"],
            'lo_page_id' => $id
          ));
        }

        $loImage2 = '';
        // For logical levels with an image navigation (ContentItemIP, ContentItemLP)
        if ($currentPage->getContentTypeId() == 77 || $currentPage->getContentTypeId() == 78 )
        {
          $loImage2Src = 'img/no_image.png';
          $lo_box_image_class = 'padding_l_95';
          if ($image2) {
            $loImage2Src = '../' . $image2;
            $lo_box_image_class = 'padding_l_35';
          }
          else
          {
            // If the page itself has no image we take it from its preferred child page.
            $preferredChildPage = $page->getPreferredChild();
            if ($preferredChildPage && $preferredChildPage->getImage()) {
              $loImage2Src = '../' . $preferredChildPage->getImage2();
            }
          }
          // set image icon from second item image if first has not been available
          // & for ContentItemLP where only CImage2 could have been set
          $loImageIcon = !$loImageIcon ? '<img src="'.$loImage2Src.'" alt="" />' : $loImageIcon;
          $deleteImage = false;
          if ($image2) {
            $ciPrefix = mb_substr($image2, 4, 2);
            // only custom images can be deleted
            if (strcmp($ciPrefix, 'lo') == 0)
              $deleteImage = true;
          }
          $this->tpl->load_tpl('content_site_lo_image2', 'content_site_logical_image2.tpl');
          $this->tpl->parse_if('content_site_lo_image2', 'delete_image', $deleteImage, $this->get_delete_image('lo', 2, '&amp;ofPage='.$id));
          $loImage2 = $this->tpl->parsereturn('content_site_lo_image2', array (
            'lo_image2_required_resolution_label' => $reqResLabel,
            'lo_image2_src' => $loImage2Src,
            'lo_lockimage2' => $image2Locked ? ' checked="checked" ' : '',
          ));
        }

        if (isset($lo_user_permitted_paths[$path]))
          $lo_users_list = sprintf($_LANG["lo_users_list"],implode(", ",$lo_user_permitted_paths[$path]));

        $lo_add = "";
        if ($className == "ContentItemES")
        {
          $result2 = $this->db->query("SELECT EExt from {$this->table_prefix}contentitem_es WHERE FK_CIID=$id");
          $row2 = $this->db->fetch_row($result2);
          if (isset($_LANG["es_ext_modules"][intval($row2["EExt"])]))
            $lo_add = " (".$_LANG["es_ext_modules"][intval($row2["EExt"])].")";
          $this->db->free_result($result2);
        }

        // Handle the timing component for this page
        $dateFrom = DateHandler::getValidDateTime($page->getStartDate(), 'd.m.Y');
        $dateUntil = DateHandler::getValidDateTime($page->getEndDate(), 'd.m.Y');
        $timeFrom = DateHandler::getValidDateTime($page->getStartDate(), 'H:i');
        $timeUntil = DateHandler::getValidDateTime($page->getEndDate(), 'H:i');
        $parentDatetimeFrom = DateHandler::getValidDateTime($page->getStartDateParent(), 'd.m.Y H:i');
        $parentDatetimeUntil = DateHandler::getValidDateTime($page->getEndDateParent(), 'd.m.Y H:i');

        // get message for timing area, if available
        $timingMsg = '';
        // if start dates exist and the parent page start date conflicts with items start date
        if ($parentDatetimeFrom && $page->getStartDate() && !(strtotime($parentDatetimeFrom) <= strtotime($page->getStartDate())))
          $timingMsg = $_LANG['lo_conflict_parent_datetime_warning'];
        // if end dates exist and the parent page end date conflicts with items end date
        else if ($parentDatetimeUntil && $page->getEndDate() && !(strtotime($parentDatetimeUntil) >= strtotime($page->getEndDate())))
          $timingMsg = $_LANG['lo_conflict_parent_datetime_warning'];
        // timing enabled above
        else if ($timingState == NavigationPage::TIMING_ENABLED_ABOVE || $timingState == NavigationPage::TIMING_ENABLED_SELF_AND_ABOVE)
          $timingMsg = sprintf($_LANG['lo_parent_datetime_warning'], $parentDatetimeFrom, $parentDatetimeUntil);
        else if ((bool)$row['TimedChild'])
          $timingMsg = $_LANG['lo_children_datetime_warning'];

        /**
         * if the item is a top level item inside login tree - display group selection
         */
        $groupItems = array();
        if ($this->_loginFunctionsAvailable($page))
        {
          $sql = " SELECT ug.FUGID, ug.FUGName, ug.FUGDescription "
               . " FROM {$this->table_prefix}frontend_user_group ug "
               . " JOIN {$this->table_prefix}frontend_user_group_sites ugs "
               . "     ON ugs.FK_FUGID = ug.FUGID "
               . " WHERE FK_SID = {$this->site_id} ";
          $resultGroup = $this->db->query($sql);
          $sql = " SELECT FK_FUGID "
               . " FROM {$this->table_prefix}frontend_user_group_pages "
               . " WHERE FK_CIID = $id ";
          $selectedGroups = $this->db->GetCol($sql);
          while ($group = $this->db->fetch_row($resultGroup))
          {
            $checked = '';
            if (in_array($group['FUGID'], $selectedGroups)) {
              $checked = ' checked="checked" ';
            }

            $groupItems[] = array(
              'lo_item_group_id'     => $group['FUGID'],
              'lo_item_group_name'   => $group['FUGName'],
              'lo_item_group_desc'   => $group['FUGDescription'],
              'lo_item_group_checked'=> $checked,
            );
          }
        }

        $functionView = new ContentItemLogical_CFunctionView($this->db, $this->table_prefix, $this->tpl, $this->_user);

        /**
         * get the content for special functions if the necessary modules are active
         * an available (there might be availability restrictions due to $_CONFIG settings)
         */
        $loSpecialFunctions = false;
        $loBlog = '';
        $loShare = '';
        if ($this->_functionBlog->isAvailableOnPage($page)) {
          $loSpecialFunctions = true;
          $this->tpl->load_tpl('content_site_lo_blog', 'content_site_logical_blog.tpl');
          $functionView->parse('content_site_lo_blog', $this->_functionBlog, $page);
          $loBlog = $this->tpl->parsereturn('content_site_lo_blog', array (
            'lo_blog_checked' => $blog ? ' checked="checked" ' : '',
            'lo_blog_status'  => $_LANG["lo_blog_status$blog"],
          ));
        }

        $displayShareConfig  = ConfigHelper::get('m_share_display');
        $displayShareConfig = isset($displayShareConfig[$this->site_id]) ?
            $displayShareConfig[$this->site_id] : $displayShareConfig[0];
        if  ($this->_functionShare->isActive() && $displayShareConfig) {
          $loSpecialFunctions = true;
          $this->tpl->load_tpl('content_site_lo_share', 'content_site_logical_share.tpl');
          $functionView->parse('content_site_lo_share', $this->_functionShare, $page);
          $loShare = $this->tpl->parsereturn('content_site_lo_share', array (
            'lo_share_checked' => $share ? ' checked="checked" ' : '',
            'lo_share_status'  => $_LANG["lo_share_status$share"],
          ));
        }

        $itemPublishedComments = isset($publishedComments[$id]) ? (int)$publishedComments[$id] : 0;
        $loTreemgmtLink = '';
        if (!$page->isPositionLocked()) {
          if (isset($modules['treemgmtall']))
            $loTreemgmtLink = 'index.php?action=mod_response_treemgmtall&amp;request=TreeView&amp;site='.$this->site_id.'&amp;page='.$id;
          else if (isset($modules['treemgmtleafonly']) && $page->getType() != 90)
            $loTreemgmtLink = 'index.php?action=mod_response_treemgmtleafonly&amp;request=TreeView&amp;site='.$this->site_id.'&amp;page='.$id;
        }

        $contentLink = "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$id;
        if ($loImageIcon) {
          $loImageIconCont = ($lo_image_src == 'img/no_image.png') ? $loImageIcon : '<span data-toggle="lo-tooltip-image" title="' . str_replace('"', "'", $loImageIcon) . '">' . $loImageIcon . '</span>';
        }
        else {
          $loImageIconCont = '';
        }
        if ($lockedContent)
          $itemTitleLink = sprintf($_LANG['lo_item_title_label'], $loImageIconCont, parseOutput($title));
        else
          $itemTitleLink = sprintf($_LANG['lo_item_title_link'], $contentLink, $loImageIconCont, parseOutput($title));

        $buttonsDate = '<input type="submit" class="button_date" name="process_date['.$id.']" value="'
            .$_LANG["global_button_save_label"]
            .'" onclick="document.forms[0].top=\'_top\';document.forms[0].action2.value=\'\' class=\'\';" />';

        $configTimingType = ConfigHelper::get('ci_timing_type');
        $configTimingAllowedTypes = ConfigHelper::get('ci_timing_allowed_ctypes');
        $dateAvailable = $configTimingType != 'deactivated' && in_array($contentType, $configTimingAllowedTypes);
        $timingActivated = $dateAvailable && $configTimingType == 'activated';
        $timingDeactivatedForItem = ($configTimingType != 'deactivated') && !$dateAvailable;
        $timingStartDateOnly = $dateAvailable && $configTimingType == 'startdateonly';
        $timingEnabled = $activationLight == ActivationClockInterface::GREEN || $activationLight == ActivationClockInterface::ORANGE || $activationLight == ActivationClockInterface::RED;
        $activationLockEnabled = $activationLight == ActivationLightInterface::RED;
        $lockedPosition = $positionHelper->isLocked($position);
        $moveUpPosition = $positionHelper->getMoveUpPosition($position);
        $moveDownPosition = $positionHelper->getMoveDownPosition($position);
        $unpublishedComments = $loBlog ? ((int)$row['allComments'] - $itemPublishedComments) : 0;
        $numberComments = (int)$row['allComments'];

        $this->tpl->load_tpl('lo_item', 'content_site_logical_item.tpl');
        $this->tpl->parse_if('lo_item', "lo_timing_type_activated", $timingActivated);
        $this->tpl->parse_if('lo_item', "lo_timebox", $timingActivated);
        $this->tpl->parse_if('lo_item', "lo_timing_type_activated_for_others", $timingDeactivatedForItem);
        $this->tpl->parse_if('lo_item', "lo_timing_type_activated_start_date_only", $timingStartDateOnly);
        $this->tpl->parse_if('lo_item', "lo_timebox_start_dates_only", $timingStartDateOnly);
        $this->tpl->parse_if('lo_item', "lo_timing_enabled", $timingEnabled);
        $this->tpl->parse_if('lo_item', "lo_timing_disabled", !$timingEnabled);
        $this->tpl->parse_if('lo_item', "lo_timing_message", $timingMsg, array('lo_timing_message' => $timingMsg));
        $this->tpl->parse_if('lo_item', "lo_activation_lock_enabled", $activationLockEnabled);
        $this->tpl->parse_if('lo_item', "lo_activation_lock_disabled", !$activationLockEnabled);
        $this->tpl->parse_if('lo_item', "lo_position_locked", $lockedPosition);
        $this->tpl->parse_if('lo_item', "lo_position_unlocked", !$lockedPosition);
        $this->tpl->parse_if('lo_item', "lo_content_locked", $lockedContent);
        $this->tpl->parse_if('lo_item', "lo_content_unlocked", !$lockedContent);
        $this->tpl->parse_if('lo_item', "lo_box_group_selection", !empty($groupItems));
        if (!empty($groupItems)) $this->tpl->parse_loop('lo_item', $groupItems, "logical_item_groups");
        $this->tpl->parse_if('lo_item', "lo_box_landingpage_url", $currentPage->getTree() == Navigation::TREE_PAGES);
        $this->tpl->parse_if('lo_item', "lo_box_specialfunctions", $loSpecialFunctions);
        $this->tpl->parse_if('lo_item', "lo_new_comments", $loBlog && $unpublishedComments && $numberComments);
        $this->tpl->parse_if('lo_item', "lo_comments", $loBlog && !$unpublishedComments && $numberComments);
        $this->tpl->parse_if('lo_item', 'lo_delete_item_available', $this->_user->deleteContentPermitted());
        $this->tpl->parse_if('lo_item', 'lo_mod_treemgmt_active', $loModTreemgmt);
        if ($loModTreemgmt) {
          $this->tpl->parse_if('lo_item', "lo_mod_treemgmt_available", $loTreemgmtLink);
          $this->tpl->parse_if('lo_item', "lo_mod_treemgmt_unavailable", !$loTreemgmtLink);
        }
        $this->tpl->parse_if('lo_item', 'lo_mod_copy_active', $loModCopyActive && $mCopyAvailableForUser);
        $this->tpl->parse_if('lo_item', "lo_mod_copy_available", $loModCopyAvailable && $mCopyAvailableForUser);
        $this->tpl->parse_if('lo_item', "lo_mod_copy_unavailable", !$loModCopyAvailable && $mCopyAvailableForUser);
        $loModMobileSwitchAvailable = $loModMobileSwitchAvailableForUser &&
                                      $this->_functionMobileSwitch->isAvailableOnPage($page);
        $this->tpl->parse_if('lo_item', 'lo_mobile_switch_active_and_available', $loModMobileSwitchAvailable);

        $isFormFunctionAvailable = $this->_functionForm->isAvailableOnPage($page) &&
                                   $this->_functionForm->isAvailableForUser($this->_user, $currentSite);

        // get assigned campaign forms
        $sql = 'SELECT CGCID, FK_CIID, cc.FK_CGID, CGCCampaignRecipient, CGName '
             . "FROM {$this->table_prefix}campaign_contentitem cc "
             . "INNER JOIN {$this->table_prefix}campaign ca "
             . 'ON cc.FK_CGID = CGID '
             . "WHERE FK_CIID = {$id}";
        $aForms = $this->db->query($sql);
        $attachedForms = array();
        $children = $page->getAllChildren();
        while ($row = $this->db->fetch_row($aForms))
        {
          $page = $this->_navigation->getPageByID($row['FK_CIID']);
          $configEditParentForm = ConfigHelper::get('fo_edit_parent_form');
          $formLocked = (!$configEditParentForm && $page->isRealLeaf() &&(isset($foParentFormIds[$row['FK_CGID']]))) ? true : false;
          $attachedForms[] = array(
            'lo_attached_form_locked'      => ($formLocked) ? 'lo_form_locked' : '',
            'lo_attached_form_on_parent'   => (isset($foParentFormIds[$row['FK_CGID']])) ? true : false,
            'lo_attached_form_recipients_readonly' => ($formLocked) ? ' readonly="readonly" ' : '',
            'lo_attached_form_row_id'      => (int) $row['CGCID'],
            'lo_attached_form_id'          => (int) $row['FK_CGID'],
            'lo_attached_form_title'       => parseOutput($row['CGName']),
            'lo_attached_form_options'     => $this->_getCampaignForms(),
            'lo_attached_form_recipients'  => parseOutput($row['CGCCampaignRecipient']),
            'lo_attached_form_delete_link' => 'index.php?action=content&amp;site='.$this->site_id.'&amp;page='.$this->page_id.'&amp;offset='.$resultsPage.'&amp;attached_form_did='.$row['FK_CGID'].'&amp;ciid='.$row['FK_CIID'],
          );
        }
        $this->tpl->parse_if('lo_item', 'lo_print_edit_recipients_warning', count($children));
        $this->tpl->parse_if('lo_item', 'lo_attached_form_available', $attachedForms);

        $this->tpl->parse_if('lo_item', 'newitem_edit_form', $isFormFunctionAvailable, array(
          'lo_newitem_form_options'    => $this->_getCampaignForms(),
          'lo_newitem_form_recipients' => $campaignRecipients,
          'lo_has_children'            => (count($children)) ? 1 : 0,
          'lo_newitem_add_form_label'  => (count($children)) ? $_LANG['lo_newitem_add_form_to_children_label'] : $_LANG['lo_newitem_add_form_label'],
        ));

        $this->tpl->parse_loop('lo_item', $attachedForms, 'lo_added_forms');
        foreach ($attachedForms as $value)
        {
          $this->tpl->parse_if('lo_item', 'lo_form_recipients_'.$value['lo_attached_form_row_id'], !count($children));
          $this->tpl->parse_if('lo_item', 'lo_form_locked_'.$value['lo_attached_form_row_id'], $value['lo_attached_form_locked']);
          $this->tpl->parse_if('lo_item', 'lo_form_not_attached_on_parent_'.$value['lo_attached_form_row_id'], !$value['lo_attached_form_on_parent']);
          $this->tpl->parse_if('lo_item', 'lo_form_attached_on_parent_'.$value['lo_attached_form_row_id'], $value['lo_attached_form_on_parent'] && !$value['lo_attached_form_locked']);
        }

        $mobileSwitchLight = $this->_getMobileSwitchLight($page);
        $mobileSwitchLink = $this->_functionMobileSwitch
                                 ->getLinkOfLogicalLevel($this->site_id, $this->page_id, $id, $resultsPage, $mobileSwitchLight);
        $this->tpl->parse_if('lo_item', 'lo_seo_management', $this->_getFunctionSeoManagement($id)->isAvailableForUserOnPage($this->_user, $page));
        $tmp = $this->tpl->parsereturn('lo_item', array_merge($this->_getFunctionSeoManagement($id)->getTemplateVars(), array (
          'lo_title' => parseOutput($title),
          'lo_type' => $_LANG["global_{$langClassName}_intlabel"]." - ".$_LANG["global_{$langClassName}_label"].$lo_add,
          'lo_image' => $lo_image,
          'lo_image2' => $loImage2,
          'lo_mobile_switch_status' => $mobileSwitchLight,
          'lo_mobile_switch_status_label' => $_LANG['lo_mobile_switch_status_' . $mobileSwitchLight . '_label'],
          'lo_mobile_switch_link' => $mobileSwitchLink,
          'lo_activation_light' => $activationLight,
          'lo_activation_light_link' => $activationLightLink,
          'lo_activation_light_label' => $activationLightLabel,
          'lo_activation_lock' => $activationLock,
          'lo_activation_lock_link' => $activationLockLink,
          'lo_activation_lock_label' => $activationLockLabel,
          'lo_shorttext' => $lo_shorttext,
          'lo_lockimage' => ($imageLocked ? "checked='checked' " : ""),
          'lo_content_link' => $contentLink,
          'lo_item_title_link' => $itemTitleLink,
          'lo_boxtitle_label' => sprintf($_LANG["lo_boxtitle_label"],$position),
          'lo_timeboxtitle_label' => sprintf($_LANG["lo_timeboxtitle_label"],$position),
          'lo_item_position' => $position,
          'lo_item_move_up_link' => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;offset=$resultsPage&amp;moveContentItemID=$id&amp;moveContentItemTo=$moveUpPosition",
          'lo_item_move_down_link' => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;offset=$resultsPage&amp;moveContentItemID=$id&amp;moveContentItemTo=$moveDownPosition",
          'lo_delete_link' => "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$this->page_id."&amp;offset=".$resultsPage."&amp;did=$id",
          'lo_position_status' => $lockedPosition ? 'locked' : 'unlocked',
          'lo_users_list' => $lo_users_list,
          'lo_required_resolution_label' => $loReqResLabel,
          'lo_image_alt_label' => $_LANG["m_image_alt_label"],
          'date_from' => $dateFrom,
          'time_from' => $timeFrom,
          'date_until' => $dateUntil,
          'time_until' => $timeUntil,
          'buttons_date' => $buttonsDate,
          'lo_save_label' => $_LANG['global_button_save_label'],
          'lo_timing_activated_display_class' => ($configTimingType == 'deactivated') ? '' : '_time',
          'lo_item_ctype' => ContentItem::getTypeShortname($contentType),
          'lo_item_ctype_label' => $_LANG["global_{$langClassName}_intlabel"],
          'lo_item_landingpage_url' => $page->getUrl(),
          'lo_blog' => $loBlog,
          'lo_share' => $loShare,
          'lo_id' => $id,
          'lo_position' => $position,
          'lo_comments' => $numberComments,
          'lo_unpublished_comments' => $unpublishedComments,
          'lo_comments_padding' => (isset($lo_box_image_class)) ? $lo_box_image_class : '',
          'lo_mod_treemgmt_link' => $loTreemgmtLink,
          'lo_mod_copy_link' => 'index.php?action=mod_response_copy&amp;request=duplicate&amp;page='.$id,
          'lo_comments_label' => $_LANG['lo_comments'],
          'lo_comments_title' => $_LANG['lo_comments_unpublished'].': '.$unpublishedComments.', '.$_LANG['lo_comments_total_count'].': '.$numberComments,
        )));

        $lo_items[] = array( 'lo_item' => $tmp );
      }

      if (!$lo_items || ($this->_getMessage() && $lo_message_from_newitem))
        $lo_newitem_div = "visibility:visible;display:block;";
      else
        $lo_newitem_div = "visibility:hidden;display:none;";

      // Determine if the maximum amount of elements in this level is reached.
      // The maximum is not relevant for archives and blog levels,
      // as these content types may contain an unlimited amount of elements.
      $maximumReached = false;
      if (!$currentPage->isArchive() && !$currentPage->isBlog() && !$currentPage->isProductBox())
      {
        $configMaximum = ConfigHelper::get('lo_max_items');
        $maximum = (int)$configMaximum[0][$currentPage->getTree()][$current_level + 1];

        if (isset($configMaximum[$currentSite->getID()]))
        {
          if (isset($configMaximum[$currentSite->getID()][$currentPage->getTree()][$current_level + 1]))
            $maximum = (int)$configMaximum[$currentSite->getID()][$currentPage->getTree()][$current_level + 1];
          else
            $maximum = 0;
        }

        if ($lo_items_count >= $maximum) {
          $maximumReached = true;
        }
      }

      $lo_content_top = "";
      $lo_content_left = "";
      if ($currentPage->isRoot())
      {
        // Root pages for all navigation trees of current site.
        $leftRootPages = $currentSite->getRootPages();
        $pageList = array();
        if ($this->_user->AvailableSiteIndex($currentSite->getID()))
        {
          $link = "index.php?action=mod_siteindex&amp;site={$currentSite->getID()}";
          $link = sprintf($_LANG['si_boxes_link'], $link, $_LANG["global_edit_home_label"]);
          $pageList[] = array(
            'si_menue_type' => "home",
            'si_menue_link' => $link,
          );
        }
        foreach ($leftRootPages as $tree => $leftRootPage)
        {
          // Do not show pages from the user tree (array) or hidden pages.
          if (is_array($leftRootPage) || $leftRootPage->getTree() == Navigation::TREE_HIDDEN) {
            continue;
          }

          if ($this->_user->AvailablePath($leftRootPage->getPath(), $currentSite->getID(), $leftRootPage->getTree())) {
            $link = "index.php?action=content&amp;site={$currentSite->getID()}&amp;page={$leftRootPage->getID()}";
          }
          // root page isn't available for user, so do not show it in menue
          else {
            continue;
          }

          // if the item is the current page (active) do not display a link, but a label only
          if ($leftRootPage->getID() == $currentPage->getID()) {
            $link = $_LANG["global_edit_{$leftRootPage->getTree()}_label"];
          }
          else {
            $link = sprintf($_LANG['si_boxes_link'], $link, $_LANG["global_edit_{$leftRootPage->getTree()}_label"]);
          }
          $pageList[] = array(
            'si_menue_type' => $leftRootPage->getTree(),
            'si_menue_link' => $link,
          );
        }

        $userTreeData = $this->_getUserTreeData();
        if (!$userTreeData) {
          $userTreeData = array();
        }
        $searchModule = new ModuleSearch(array(), $this->site_id, $this->tpl, $this->db, $this->table_prefix,
                                         '', '', $this->_user, $this->session, $this->_navigation);
        $this->tpl->load_tpl('site_index_left', 'modules/ModuleSiteindex_left.tpl');
        $this->tpl->parse_if('site_index_left', 'menu_user_info', $userTreeData, array_merge($userTreeData, array(
          'si_contentleft_user_link' => sprintf($_LANG['m_contentleft_showfrontend_user_link'], (isset($userTreeData['lo_ut_area_fu_id'])) ? $userTreeData['lo_ut_area_fu_id'] : 0),
        )));
        $this->tpl->parse_loop('site_index_left', $pageList, 'menue_links');
        $lo_content_left = $this->tpl->parsereturn('site_index_left', array(
          'si_mainfunctions_label' => $_LANG['m_mainfunctions_label'],
          'si_specialpages_label' => $_LANG['m_specialpages_label'],
          'si_contentleft_newitem_link' => ($maximumReached || !$this->_user->createContentPermitted()) ?
                                           '' : $_LANG['m_contentleft_newitem_link'],
          'si_contentleft_showfrontend_link' => sprintf($_LANG['m_contentleft_showfrontend_link'], $currentPage->getUrl()),
          'si_contentleft_search' => $searchModule->getSearchBox(),
        ));
      }
      else {
        $args = array('no_preview');
        $args[] = ($maximumReached || !$this->_user->createContentPermitted()) ?
                  null : 'new_item_available';
        // always display frontend link for logical levels.
        $lo_content_left = $this->get_contentleft(false, $args);
        $lo_content_top = $this->_getContentTop();
      }

      // get parsed newitem area, empty if not available
      $newItemArea = $this->_parseNewItemArea($maximumReached, $lo_newitem_type, $lo_newitem_title, $lo_newitem_div, $lo_newitem_position, $campaignRecipients);
      // get login filter, empty if not available
      $loLoginTreeFilter = $this->_parseLoginTreeFilter();

      $lastEditedPage = $this->_getPageFromLastEditedItem();
      $scrollToAnchor = $lastEditedPage ?
                        $this->_getLastEditedScrollToAnchor($lastEditedPage) : '';
      $lo_action = "index.php";
      $lo_action .= ($resultsPage > 1) ? "?offset=".$resultsPage : '';
      $lo_hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                         .'<input type="hidden" name="page" value="'.$this->page_id.'" />'
                         .'<input type="hidden" name="action" value="content" />';

      $this->tpl->load_tpl('content_site_lo', 'content_site_logical.tpl');
      $this->tpl->parse_if('content_site_lo', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lo'));
      $this->tpl->parse_if('content_site_lo', 'entries_maximum_reached', $maximumReached, array('lo_message_maximum_reached' => $_LANG['lo_message_maximum_reached']));
      $this->tpl->parse_if('content_site_lo', 'more_pages', $pageNavigation, array('lo_page_navigation' => $pageNavigation));
      $this->tpl->parse_loop('content_site_lo', $lo_items, 'logical_items');
      $lo_content = $this->tpl->parsereturn('content_site_lo', array (
        'lo_title_label' => $_LANG["lo_title_label"],
        'lo_login_group_filter' => $loLoginTreeFilter,
        'lo_newitem_area' => $newItemArea,
        'lo_ut_area' => $this->_parseUserTreeArea(),
        'lo_action' => $lo_action,
        'lo_hidden_fields' => $lo_hidden_fields, // attention: allways parse hidden fields after newitem area
        'lo_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&offset=$resultsPage&moveContentItemID=#moveID#&moveContentItemTo=#moveTo#",
        'lo_deleteitem_question_label' => $_LANG["lo_deleteitem_question_label"],
        'lo_delete_attached_form_question_label' => $_LANG["lo_delete_attached_form_question_label"],
        'lo_item_last_edited_id' => $lastEditedPage ? $lastEditedPage->getID() : 0,
        'lo_item_box_last_edited' => $this->_getLastEditedBox(),
        'lo_scroll_to_anchor' => $scrollToAnchor,
        'lo_leveldata_area' => $this->_parseLevelAdditionalData(),
      ));

      return array( 'content' => $lo_content, "content_left" => $lo_content_left, "content_top" => $lo_content_top, 'content_contenttype' => "ContentItemLogical" );
    }

  /**
     * Reads all dates that were input by the user and returns them for a
     * content item in the logical site.
     *
     * There exist two dates specifying the start date and end date a special
     * content item shoud be visible in the FE
     *
     * @param int $id the id of the content item, user input dates shoud be read from
     *
     * @return array
     *        Contains dates that were entered by the user. The array index
     *        is the name of the database column, the array value is the date.
     */
    protected function _readLogicalItemsDate($id)
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);


      if (isset($_POST["process_date"])) {

        $formNumber = $post->readKey("process_date");

        $postDateFrom = $post->readString("date{$formNumber}_from", Input::FILTER_PLAIN);
        $postTimeFrom = $post->readString("time{$formNumber}_from", Input::FILTER_PLAIN);
        $postDateUntil = $post->readString("date{$formNumber}_until", Input::FILTER_PLAIN);
        $postTimeUntil = $post->readString("time{$formNumber}_until", Input::FILTER_PLAIN);

        // Create date strings and time strings and combine afterwards
        $dateFrom = DateHandler::getValidDate($postDateFrom, 'Y-m-d');
        $timeFrom = DateHandler::getValidDate($postTimeFrom, 'H:i:s');
        $dateUntil = DateHandler::getValidDate($postDateUntil, 'Y-m-d');
        $timeUntil = DateHandler::getValidDate($postTimeUntil, 'H:i:s');

        $datetimeFrom = DateHandler::combine($dateFrom, $timeFrom);
        $datetimeUntil = DateHandler::combine($dateUntil, $timeUntil);

        $page = $this->_navigation->getPageByID($id);
        //$parentDatetimeFrom = $page->getStartDateParent();
        $parentDatetimeFrom = DateHandler::getValidDateTime($page->getStartDateParent());
        $parentDatetimeUntil = DateHandler::getValidDateTime($page->getEndDateParent());

        // and return an empty array
        if (!DateHandler::isValidDate($postDateFrom) && $postDateFrom != ''
            || !DateHandler::isValidDate($postTimeFrom) && $postTimeFrom != ''
            || !DateHandler::isValidDate($postDateUntil) && $postDateUntil != ''
            || !DateHandler::isValidDate($postTimeUntil) && $postTimeUntil != '')
        {
          $this->setMessage(Message::createFailure($_LANG['global_message_invalid_date']));
          return array();
        }
        if (DateHandler::isValidDate($datetimeFrom) && !DateHandler::isFutureDateTime($datetimeFrom)
            || DateHandler::isValidDate($datetimeUntil) && !DateHandler::isFutureDateTime($datetimeUntil))
        {
          $this->setMessage(Message::createFailure($_LANG['global_message_past_date']));
          return array();
        }
        if (strtotime($datetimeFrom) > strtotime($datetimeUntil)
            && DateHandler::isValidDate($datetimeFrom) &&DateHandler::isValidDate($datetimeUntil))
        {
          $this->setMessage(Message::createFailure($_LANG['global_message_wrong_date']));
          return array();
        }
        // wrong start date - conflict with parent date
        if ((DateHandler::isValidDate($datetimeFrom) && strtotime($parentDatetimeFrom) && (strtotime($parentDatetimeFrom) > strtotime($datetimeFrom)))
            || (DateHandler::isValidDate($datetimeFrom) && strtotime($parentDatetimeUntil) && (strtotime($parentDatetimeUntil) < strtotime($datetimeFrom))))
        {
          $this->setMessage(Message::createFailure($_LANG['global_message_conflict_parent_date']));
          return array();
        }
        // wrong end date - conflict with parent date
        if ((DateHandler::isValidDate($datetimeUntil) && strtotime($parentDatetimeUntil) && (strtotime($parentDatetimeUntil) < strtotime($datetimeUntil)))
            || (DateHandler::isValidDate($datetimeUntil) && strtotime($parentDatetimeFrom) && (strtotime($parentDatetimeFrom) > strtotime($datetimeUntil))))
        {
          $this->setMessage(Message::createFailure($_LANG['global_message_conflict_parent_date']));
          return array();
        }
        else {
          $dates['CShowFromDate'] = $datetimeFrom;
          $dates['CShowUntilDate'] = $datetimeUntil;
          return $dates;
        }
      }
      else
        return array();
    }

    private function _processSelectedGroups()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      if (!$post->exists('process')) {
        return;
      }
      $itemID = $post->readKey('process');

      // no group selected
      if (!$post->exists("lo_item{$itemID}_group")) {
        return;
      }
      $selectedGroupes = $post->readArray("lo_item{$itemID}_group");
      $selectedGroupes = array_keys($selectedGroupes);

      // delete existing group selection for page (item)
      $sql = " DELETE FROM {$this->table_prefix}frontend_user_group_pages "
           . " WHERE FK_CIID = $itemID ";
      $this->db->query($sql);

      // add a new table entry foreach selected group
      foreach ($selectedGroupes as $groupID)
      {
        $sql = " INSERT INTO {$this->table_prefix}frontend_user_group_pages "
             . " ( FK_FUGID, FK_CIID ) "
             . " VALUES "
             . " ( $groupID, $itemID ) ";
        $this->db->query($sql);
      }

      $this->setMessage(Message::createSuccess($_LANG['lo_message_success']));
    }

    /**
     * Return the parsed filter for frontend-user-groups, initializes filter settings
     * NOTE: a group filter is only available on the top level inside the login
     * navigation tree, filter settings have to be initialized before calling this method
     *
     * @return string - the parsed frontend-user-group filter
     */
    private function _parseLoginTreeFilter()
    {
      global $_LANG;

      $currentPage = $this->_navigation->getCurrentPage();

      // if current tree isn't 'login' or we aren't on specified levels of login
      // tree
      if (!$this->_loginFunctionsAvailable($currentPage, $currentPage->getLevel() + 1)) {
        return '';
      }

      $filter = $this->session->read('lo_filter_type');
      $filterName = '';

      $sql = " SELECT ug.FUGID, ug.FUGName "
           . " FROM {$this->table_prefix}frontend_user_group ug "
           . " JOIN {$this->table_prefix}frontend_user_group_sites ugs "
           . "     ON ugs.FK_FUGID = ug.FUGID "
           . " WHERE FK_SID = {$this->site_id} ";

      // in case no groups are available on this site (no database entries)
      $exists = $this->db->GetOne($sql);
      if (!$exists) {
        return '';
      }

      $resultGroups = $this->db->query($sql);

      // create group filter options
      $tempFilterSelect = '';
      while ($group = $this->db->fetch_row($resultGroups))
      {
        $tempFilterSelect .= '<option value="'.$group['FUGID'].'"';
        if ($filter == $group['FUGID']) {
          $tempFilterSelect .= ' selected="selected"';
          $filterName = $group['FUGName'];
        }
        $tempFilterSelect .= '>'.$group['FUGName'].'</option>';
      }


      $this->tpl->load_tpl('content_site_lo_filter', 'content_site_logical_login_filter.tpl');
      $this->tpl->parse_if('content_site_lo_filter', 'login_item_filter_set', $filterName);
      return $this->tpl->parsereturn('content_site_lo_filter', array(
        "lo_login_action"             => "index.php?action=content&site={$this->site_id}&page={$this->page_id}",
        "lo_login_filter_active_label"=> $filterName ? sprintf($_LANG['lo_login_filter_active_label'], parseOutput($filterName)) : $_LANG['lo_login_filter_inactive_label'],
        "lo_login_filter_select"      => $tempFilterSelect,
      ));
    }

    /**
     * Return parsed newitem area if maximum amount of elements on current level
     * isn't reached.
     *
     * @param bool $maximumReached
     *        True if maximum amount of elements is reached
     * @param int $newItemType
     *        Contenttype of new item in case creating one failed before
     * @param string $newItemTitle
     *        Title of new item in case creating one failed before
     * @param string $visibility
     *        Visibility (css style) of the parsed area
     * @param $newItemPosition
     * @param string $campaignRecipients
     *        Email addresses (comma seperated) of form recipients.
     * @return null|string
     */
    private function _parseNewItemArea($maximumReached, $newItemType, $newItemTitle, $visibility, $newItemPosition, $campaignRecipients='')
    {
      global $_LANG;

      if ($maximumReached || !$this->_user->createContentPermitted()) {
        return '';
      }

      $currentPage = $this->_navigation->getCurrentPage();
      $currentSite = $this->_navigation->getCurrentSite();
      $currentLevel = $currentPage->getLevel() + 1;

      $activeTypes = ConfigHelper::get('ci_contenttypes', '', $this->site_id);
      // content type selectors: combo box or radio button
      $loContentTypeLogical = array();
      $loContentTypeNormal = array();
      $factory = new ContentTypeFactory($this->db, $this->table_prefix);
      $cts = $factory->getAll();
      foreach ($cts as $ct) {

        $isAvailable = $ct->isActive();
        if (is_array($activeTypes) && !in_array($ct->getId(), $activeTypes)) {
          $isAvailable = false;
        }

        if (!$isAvailable) { continue; }
        $allowed = $this->_configHelper->newItemAt($currentPage, $ct);

        if ($allowed) {
          $ctClass = $ct->getTemplate() ? $ct->getClass() . $ct->getTemplate() :
                     $ct->getClass(); // TI => TI2
          $checked = $newItemType == $ct->getId() ? ' checked ' : '';

          $item = array(
            'lo_contenttype_id'       => $ct->getId(),
            'lo_contenttype'          => $ctClass,
            'lo_contenttype_intlabel' => $_LANG['global_'. $ctClass .'_intlabel'],
            'lo_contenttype_label'    => ($_LANG['global_'.$ctClass.'_label'] ? $_LANG['global_'.$ctClass.'_label'] : ''),
            'lo_contenttype_text'     => $_LANG['global_'.$ctClass.'_text'],
            'lo_contenttype_checked'  => $checked,
            'lo_contenttype_desc'     => $_LANG['global_'.$ctClass.'_desc'],
          );

          if ($ct->isLogical()) { $loContentTypeLogical[$ct->getPosition()] = $item; }
          else { $loContentTypeNormal[$ct->getPosition()] = $item; }
        }
      }

      /**
       * For top level inside the 'login' tree - get all available frontend-user-groups
       */
      $loNewitemGroups = array();
      if ($this->_loginFunctionsAvailable($currentPage, $currentLevel))
      {
        $sql = " SELECT ug.FUGID, ug.FUGName, ug.FUGDescription "
             . " FROM {$this->table_prefix}frontend_user_group ug "
             . " JOIN {$this->table_prefix}frontend_user_group_sites ugs "
             . "     ON ugs.FK_FUGID = ug.FUGID "
             . " WHERE FK_SID = {$this->site_id} ";
        $resultGroups = $this->db->query($sql);

        // create group items for parsing the template
        while ($group = $this->db->fetch_row($resultGroups))
        {
          $loNewitemGroups[] = array(
            'lo_newitem_group_id'     => $group['FUGID'],
            'lo_newitem_group_name'   => $group['FUGName'],
            'lo_newitem_group_desc'   => $group['FUGDescription'],
          );
        }
      }

      // stores each position available for new item -> if more than one item
      // exists in current level
      $loNewItemPosLast = 0;
      $loNewItemPos = array();
      $positionSelected = false;
      // if items exist inside current level
      if ($currentPage->hasChildren()) {
        // determine the amount of items inside current level
        $children = $currentPage->getAllChildren();
        $count = count( $children );
        for ($i = 2; $i <= $count; $i++)
        {
          // get the child page at specified position
          foreach ($children as $child) {
            if ($child->getPosition() == $i) {
              break;
            }
          }
          $selected = '';
          if ($newItemPosition == $i) {
            $selected = ' selected="selected" ';
            $positionSelected = true;
          }
          $loNewItemPos[] = array(
            'lo_newitem_title'    => $child->getTitle(), // use page title
            'lo_newitem_position' => $i,
            'lo_newitem_selected' => $selected,
          );
        }
        // position after the last item - if set also indicates that there are
        // items inside current level
        $loNewItemPosLast = $i;
      }

      $optGroups = array();
      $options = array();
      $optionData = array();
      foreach ($loContentTypeLogical as $type) {
        $options[$type['lo_contenttype_id']] = $type['lo_contenttype_intlabel'] . ' - ' . $type['lo_contenttype_label'];
        $optionData[$type['lo_contenttype_id']] = array_merge($type, array(
          'lo_contenttype_type' => 'logical',
        ));
      }
      if ($options) {
        $optGroups[] = array(
          'label' => $_LANG['lo_conntenttype_select_logical_label'],
          'options' => $options,
        );
      }
      $options = array();
      foreach ($loContentTypeNormal as $type) {
        $options[$type['lo_contenttype_id']] = $type['lo_contenttype_intlabel'] . ' - ' . $type['lo_contenttype_label'];
        $optionData[$type['lo_contenttype_id']] = array_merge($type, array(
          'lo_contenttype_type' => 'leaf',
        ));
      }
      if ($options) {
        $optGroups[] = array(
          'label' => $_LANG['lo_conntenttype_select_normal_label'],
          'options' => $options,
        );
      }

      $this->tpl->load_tpl('content_site_lo', 'content_site_logical_newitem.tpl');
      $this->tpl->parse_loop('content_site_lo', $loContentTypeLogical, 'contenttypes_logical');
      $this->tpl->parse_loop('content_site_lo', $loContentTypeNormal, 'contenttypes_normal');
      $this->tpl->parse_if('content_site_lo', 'newitem_group_selection', $loNewitemGroups);
      $this->tpl->parse_loop('content_site_lo', $loNewitemGroups, 'newitem_groups');
      $this->tpl->parse_if('content_site_lo', 'newitem_position_selection', $loNewItemPosLast);
      $this->tpl->parse_loop('content_site_lo', $loNewItemPos, 'newitem_position');
      $this->tpl->parse_if('content_site_lo', 'newitem_add_form', $this->_functionForm->isAvailableForUser($this->_user, $currentSite), array(
        'lo_newitem_form_options'     => $this->_getCampaignForms(),
        'lo_newitem_form_recipients'  => $campaignRecipients,
        'lo_newitem_add_form_label'   => $_LANG['lo_newitem_add_form_label'],
      ));
      return $this->tpl->parsereturn('content_site_lo', array(
        'lo_newitem_div' => $visibility,
        'lo_newitem_label' => $_LANG['lo_newitem_label'],
        'lo_newitem_title_label' => $_LANG['lo_title_label'],
        'lo_newitem_title' => $newItemTitle,
        'lo_newitem_type_label' => $_LANG['lo_newitem_type_label'],
        'lo_newitem_submit_label' => $_LANG['lo_newitem_submit_label'],
        'lo_newitem_submit_contentedit_label' => $_LANG['lo_newitem_submit_contentedit_label'],
        'lo_conntenttype_select_logical_label' => $_LANG["lo_conntenttype_select_logical_label"],
        'lo_conntenttype_select_normal_label' => $_LANG["lo_conntenttype_select_normal_label"],
        'lo_newitem_position_start_selected' => $currentPage->isArchive() && !$positionSelected ? 'selected="selected"' : '',
        'lo_newitem_position_last_selected' => !$currentPage->isArchive() && !$positionSelected ? 'selected="selected"' : '',
        'lo_newitem_position_last' => $loNewItemPosLast,
        'lo_newitem_excluded_types_js' => '[' . implode(',', ConfigHelper::get('m_form_contenttype_excluded', '', $this->site_id)) . ']',
        'lo_newitem_type_option_groups' => AbstractForm::selectOptgroups($optGroups, $newItemType),
        'lo_newitem_type_option_data' => json_encode($optionData),
      ));
    }

    /**
     * Return the parsed level special data section for the contenttypes IB, IP, VA
     * or BE if level data is acctivated in the config file.
     *
     * @return string - the parsed leveldata section
     */
    private function _parseLevelAdditionalData()
    {
      global $_LANG;

      if (!ConfigHelper::get('level_data_active', array($this->_configPrefix, $this->_contentPrefix))) {
        return '';
      }

      $currentPage = $this->_navigation->getCurrentPage();
      $contentType = $currentPage->getContentTypeId();

      if ($contentType != 3 && $contentType != 77 && $contentType != 79 && $contentType != 80 && $contentType != 81) {
        return '';
      }

      $levelAdditionalData = '';

      $sql = " SELECT {$this->_columnPrefix}Title, {$this->_columnPrefix}Text, "
            ." {$this->_columnPrefix}Image "
            ." FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
            ." WHERE FK_CIID = {$this->page_id} ";
      $teaserResult = $this->db->query($sql);
      $teaserData = $this->db->fetch_row($teaserResult);
      /*
       * if the last edited item was the current level itself / the current levels
       * additional data display the area again, otherwise hide it
       */
      $loTeaserLevelDiv = $this->_lastEditedItemID == 99999 ?
                          1 : 0;

      $loTeaserLevelHiddenFields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                                 . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                                 . '<input type="hidden" name="action" value="content" />';

      $loAttrGroupes = array();
      /**
       * The ContentItemVA has a attribute stored additionally, get all available
       * atrtributes for the variation level and set the currently selected
       * attribute.
       */
      if ($contentType == 79)
      {
        // Determine available attributes for this content item
        $availableAttrGroups = ConfigHelper::get('level_data_attributes_on_path',
            array($this->_configPrefix, $this->_contentPrefix));
        $availableAttrGroups = isset($availableAttrGroups[$this->site_id]) ?
                               $availableAttrGroups[$this->site_id] : array();
        // store all paths (= array keys)
        $availableAttrGroupPaths = array_keys($availableAttrGroups);

        if ($availableAttrGroupPaths)
        {
          // if there are paths defined, but the content items path isn't available
          // there are not any attributes available for the content item, this variable
          // evaluates to true if attributes are available for the content items path
          $attrsAvailable = false;

          // if there are available attribute groups defined for the page path store the attribute id
          if (in_array($this->page_path, $availableAttrGroupPaths)) {
            $sqlAvailableAttrGroupIDs = (array)$availableAttrGroups[$this->page_path];
          }
          // there isn't an available attribute group stored for page path itself
          // so check for any parent page paths
          else
          {
            foreach ($availableAttrGroupPaths as $p)
            {
              if (mb_substr($this->page_path, 0, mb_strlen($p)) == $p)
              {
                $sqlAvailableAttrGroupIDs = (array)$availableAttrGroups[$p];
                break;
              }
            }
          }
          // attribute available for the contentitem
          if (isset($sqlAvailableAttrGroupIDs) && $sqlAvailableAttrGroupIDs) {
            $attrsAvailable = true;
            $sqlAvailableAttrGroupIDs = ' AND AID IN ('.implode(', ', $sqlAvailableAttrGroupIDs).') ';
          }
        }
        // there are no paths defined - no attributegroup restrictions
        else
        {
          $sqlAvailableAttrGroupIDs = '';
          $attrsAvailable = true;
        }

        if ($attrsAvailable)
        {
          $loAttributes = array();

          // get selected attributes from database, get id of group the attribute
          // belongs to in order to select the correct radio button in the form
          $sql = ' SELECT ca.FK_AVID, a.AID '
                ." FROM {$this->table_prefix}contentitem_{$this->_contentPrefix}_attributes ca "
                ." JOIN {$this->table_prefix}module_attribute av "
                .' ON ca.FK_AVID = av.AVID '
                ." JOIN {$this->table_prefix}module_attribute_global a "
                .' ON a.AID = av.FK_AID '
                ." WHERE ca.FK_CIID = {$this->page_id} ";
          $selectedAttrsResult = $this->db->query($sql);
          while ($attr = $this->db->fetch_row($selectedAttrsResult)) {
            // set the selected element for each attribute group, if there isn't
            // an attribute selected the value isn't set
            $selectedAttrs[$attr['AID']] = $attr['FK_AVID'];
          }

          // get attribute information from database
          $sql = ' SELECT AVID, AVTitle, AVText, AVImage, AID '
                ." FROM {$this->table_prefix}module_attribute "
                ." LEFT JOIN {$this->table_prefix}module_attribute_global "
                .' ON FK_AID = AID '
                .' WHERE FK_AID IN ( '
                .'       SELECT AID '
                ."       FROM {$this->table_prefix}module_attribute_global "
                .'       WHERE FK_CTID = 79 '
                ."       AND FK_SID = {$this->site_id} "
                .        $sqlAvailableAttrGroupIDs
                .' ) '
                .' ORDER BY APosition ASC, AVPosition ASC ';
          $attrsResult = $this->db->query($sql);
          while ($attribute = $this->db->fetch_row($attrsResult))
          {
            $checked = isset($selectedAttrs[$attribute['AID']]) &&
                       $selectedAttrs[$attribute['AID']] == $attribute['AVID'] ?
                       'checked' : '';
            $loAttributes[$attribute['AID']][] = array(
              'lo_iblevel_attr_id'        => $attribute['AVID'],
              'lo_iblevel_attr_title'     => $attribute['AVTitle'],
              'lo_iblevel_attr_text'      => $attribute['AVText'],
              'lo_iblevel_attr_image_src' => $this->get_thumb_image($attribute['AVImage']),
              'lo_iblevel_attr_checked'   => $checked,
            );
          }
          $this->db->free_result($attrsResult);

          // get attribute group information from database
          $sql = ' SELECT AID, ATitle, AText '
                ." FROM {$this->table_prefix}module_attribute_global "
                .' WHERE FK_CTID = 79 '
                ." AND FK_SID = {$this->site_id} "
                .  $sqlAvailableAttrGroupIDs;
          $groupResult = $this->db->query($sql);
          while ($group = $this->db->fetch_row($groupResult))
          {
            $this->tpl->load_tpl('content_site_logical_level_data_attrs', 'content_site_logical_level_data_attrs.tpl');
            $this->tpl->parse_loop('content_site_logical_level_data_attrs', $loAttributes[$group['AID']], 'lo_iblevel_attr_values');
            $attrData = $this->tpl->parsereturn('content_site_logical_level_data_attrs', array (
              'lo_iblevel_noattribute_title' => $_LANG['lo_valevel_noattribute_title'],
              'lo_iblevel_noattribute_text' => $_LANG['lo_valevel_noattribute_text'],
              'lo_iblevel_noattribute_checked' => !isset($selectedAttrs[$group['AID']]) ? 'checked' : '',
            ));

            $loAttrGroupes[] = array(
              'lo_iblevel_attrgroup_attrs' => $attrData,
              'lo_iblevel_attrgroup_title' => $group['ATitle'],
              'lo_iblevel_attrgroup_text' => $group['AText'],
              'lo_iblevel_attrgroup_id' => $group['AID'],
              'lo_iblevel_attribute_label' => $_LANG['lo_valevel_attribute_label'],
            );
          }
        }
      }
      $teaserImgSrc = $this->get_large_image($this->_contentPrefix, $teaserData["{$this->_columnPrefix}Image"]);
      $this->tpl->load_tpl('content_site_logical_level_data', 'content_site_logical_level_data.tpl');
      $this->tpl->parse_if('content_site_logical_level_data', 'lo_iblevel_attrs', $loAttrGroupes);
      $this->tpl->parse_loop('content_site_logical_level_data', $loAttrGroupes, 'lo_iblevel_attr_groupes');
      // retrieve structure links checkbox if available
      // do not use $this->_parseTemplateCommonParts()
      $displayCheckbox = $this->_structureLinksAvailable && !empty($this->_structureLinks) &&
                         $this->_user->AvailableModule('structurelinks', $this->site_id);
      $this->tpl->parse_if('content_site_logical_level_data', 'lo_iblevel_image_structure_link1', $displayCheckbox);
      $this->tpl->parse_if('content_site_logical_level_data', 'lo_delete_image1', $teaserData["{$this->_columnPrefix}Image"]);
      $levelAdditionalData = $this->tpl->parsereturn('content_site_logical_level_data', array_merge(
        $this->get_delete_image('global', 1, '&teaserImg=1'),
        $this->_getUploadedImageDetails($teaserData["{$this->_columnPrefix}Image"], "lo_iblevel", $this->getConfigPrefix()),
        array (
        'lo_iblevel_div' => $loTeaserLevelDiv,
        'lo_iblevel_box_label' => $_LANG["lo_{$this->_contentPrefix}level_box_label"],
        'lo_iblevel_box_showhide_label' => $_LANG["lo_{$this->_contentPrefix}level_box_showhide_label"],
        'lo_iblevel_title' => parseOutput($teaserData["{$this->_columnPrefix}Title"]),
        'lo_iblevel_title_label' => $_LANG["lo_{$this->_contentPrefix}level_title_label"],
        'lo_iblevel_text' => parseOutput($teaserData["{$this->_columnPrefix}Text"]),
        'lo_iblevel_text_label' => $_LANG["lo_{$this->_contentPrefix}level_text_label"],
        'lo_iblevel_required_resolution_label' => $this->_getImageSizeInfo($this->getConfigPrefix(), 0),
        'lo_iblevel_image_alt_label' => $_LANG["lo_{$this->_contentPrefix}level_image_label"],
        'lo_iblevel_image_label' => $_LANG["lo_{$this->_contentPrefix}level_image_label"],
        'lo_iblevel_large_image_available1' => $this->_getImageZoomLink($this->_contentPrefix, $teaserData["{$this->_columnPrefix}Image"]),
        'lo_iblevel_data_submit_label' => $_LANG["lo_{$this->_contentPrefix}level_data_submit_label"],
        'lo_iblevel_hidden_fields' => $loTeaserLevelHiddenFields,
        'lo_iblevel_prefix' => $this->_configPrefix,
      )));

      return $levelAdditionalData;
    }

    /**
     * Return the parsed user tree area, if user tree data
     * is available.
     *
     * @return string
     *         The parsed user tree area.
     */
    private function _parseUserTreeArea()
    {
      global $_LANG;

      $utData = $this->_getUserTreeData();
      if ($utData) {
        $this->tpl->load_tpl('content_site_lo_ut_area', 'content_site_logical_ut_area.tpl');
        return $this->tpl->parsereturn('content_site_lo_ut_area', $utData);
      }

      return '';
    }

    /**
     * Determine if user rights / group settings have to be defined for given
     * page and level and group filter is available.
     *
     * @param NavigationPage $page
     *        The page to retrieve the information for
     * @param int $level [optional]
     *        The page's level, if not set, determined from page object. Set for
     *        special requirements (e.g root page level is -1)
     *
     * @return bool
     */
    private function _loginFunctionsAvailable($page, $level = null)
    {
      // no user rights (group) settings for non-login pages
      if ($page->getTree() !== Navigation::TREE_LOGIN) {
        return false;
      }

      if ($level === null) $level = $page->getRealLevel();

      // rights have to be defined on current level
      if ($level <= ConfigHelper::get('m_user_rights_levels')) {
        return true;
      }

      return false;
    }

    /**
     * Gets a list of available, active forms of current site.
     *
     * @param int $campaignId (optional)
     *        The campaign id. Form with this campaign id will be selected.
     * @return string HTML optiongroups with campaign types and options
     *         that contain form names.
     */
    private function _getCampaignForms($campaignId=0)
    {
      $forms = '';
      // campaign mgmt available? get active forms of current site
      if ($this->_user->AvailableModule('form', $this->site_id))
      {
        if ($this->_cachedCampaignResult === null)
        {
          // get all forms
          $sql = 'SELECT CGTID, CGTName, CGID, CGName '
               . "FROM {$this->table_prefix}campaign_type AS ct "
               . "INNER JOIN {$this->table_prefix}campaign AS c "
               . 'ON CGTID = FK_CGTID '
               . "WHERE ct.FK_SID = {$this->site_id} "
               . "  AND c.FK_SID = {$this->site_id} "
               . '  AND CGStatus = 1 '
               . 'ORDER BY CGTPosition ASC, CGPosition ASC';
          $res = $this->db->query($sql);
          $this->_cachedCampaignResult = array();
          while ($row = $this->db->fetch_row($res)) {
            $this->_cachedCampaignResult[] = $row;
          }
        }
        $lastTypeId = 0;
        foreach ($this->_cachedCampaignResult as $row)
        {
          if ($lastTypeId != $row['CGTID']) {
            $lastTypeId = $row['CGTID'];
            $forms .= ($forms) ? '</optgroup>' : '';
            $forms .= '<optgroup label="'.parseOutput($row['CGTName']).'">';
          }
          $selected = '';
          if ($campaignId == $row['CGID']) {
            $selected = 'selected="selected"';
          }
          $forms .= '<option value="'.$row['CGID'].'" '.$selected.'>'.parseOutput($row['CGName']).'</option>';
        }
        $forms .= ($forms) ? '</optgroup>' : '';
      }
      return $forms;
    }

    /**
     * Gets form e-mail addresses and validates them.
     *
     * @param string
     *        Form e-mail recipients, seperated by comma
     * @return string
     *         Form mail addresses, seperated by comma
     */
    private function _getFormMailRecipients($formRecipients)
    {
      global $_LANG;

      $campaignRecipients = '';

      if ($formRecipients)
      {
        $campaignRecipients = array();
        $formRecipients = explode(',', $formRecipients);
        foreach ($formRecipients as $recipient)
        {
          $recipient = trim($recipient);
          if (!Validation::isEmail($recipient))
          {
            $this->setMessage(Message::createFailure($_LANG['lo_message_wrong_recipient_email']));
            break;
          }
          else {
            $campaignRecipients[] = $recipient;
          }
        }
        if (is_array($campaignRecipients)) {
          $campaignRecipients = implode(',', $campaignRecipients);
        }
      }

      return $campaignRecipients;
    }

    /**
     * Checks if item is the last active child
     * under its (enabled) parent.
     *
     * REMEMBER: NavigationPage::isLastActiveItem()
     *
     * @param int $id
     *        The contentitem id.
     * @param int $parentId
     *        The parent id of the contentitem
     * @return boolean
     *         True, if this item is the last active one.
     */
    private function _isLastActiveItem($id, $parentId)
    {
      $sql = 'SELECT CDisabled '
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE CIID = $parentId ";
      $parentDisabled = $this->db->GetOne($sql);
      if (!$parentDisabled)
      {
        $sql = 'SELECT COUNT(CIID) '
             . "FROM {$this->table_prefix}contentitem "
             . "WHERE FK_CIID = $parentId "
             . "AND CIID != $id "
             . 'AND CDisabled = 0 ';
        $otherActiveItems = $this->db->GetOne($sql);
        if (!$otherActiveItems) {
          return true;
        }
      }
      return false;
    }

    /**
     * Returns the content for a subitem within the logical level
     *
     * TODO: Move all item specific content to this method and load content from
     *       ajax, whenever opening an item's box.
     *       This method does not return all content currently.
     *       It returns:
     *         * the tagging part ( 'taglevel' module )
     *
     * @param int $subitemId
     *        the contentitem's id to load box content for
     *
     * @return string
     *         the subitem's content for logical level
     */
    private function _getContentSubitem($subitemId)
    {
      global $_LANG, $_LANG2, $_MODULES;

      $currentPage = $this->_navigation->getCurrentPage();
      $currentSite = $this->_navigation->getCurrentSite();
      $page = $this->_navigation->getPageByID($subitemId);
      $this->tpl->load_tpl('content_subitem', 'content_site_logical_subitem.tpl');

      $functionView = new ContentItemLogical_CFunctionView($this->db, $this->table_prefix, $this->tpl, $this->_user);

      $functionView->parse('content_subitem', $this->_functionTaglevel, $page);
      if ($this->_functionTaglevel->isActive()) {
        $labelIsTaggable = $page->isTaggable() ?
                           $_LANG['lo_taglevel_is_taggable_true_label'] :
                           $_LANG['lo_taglevel_is_taggable_false_label'];
        $this->tpl->parse_vars('content_subitem', array(
          'lo_taglevel_checked'           => $page->isTaggable() ? 'checked="checked"' : '',
          'lo_taglevel_is_taggable_label' => $labelIsTaggable,
        ));
      }

      $functionView->parse('content_subitem', $this->_functionTags, $page);
      if ($this->_functionTags->isActive() && $currentPage->isTaggable()) {

        /*********************************************************************
         * retrieve tags from database
         *
         * @see ContentItemTG::_getTags() contains duplicate code
         *
         * TODO: implement in / move to extra class
         ********************************************************************/

        $selected = array();
        $sql = " SELECT TAID, TATitle "
             . " FROM {$this->table_prefix}contentitem_tag "
             . " JOIN {$this->table_prefix}module_tag "
             . "       ON FK_TAID = TAID "
             . " WHERE FK_CIID = {$page->getID()} "
             . " ORDER BY TATitle ASC ";
        $result = $this->db->query($sql);

        while ($row = $this->db->fetch_row($result)) {
          $selected[$row['TAID']] = array(
            'lo_selected_tag_id'    => $row['TAID'],
            'lo_selected_tag_title' => $row['TATitle']
          );
        }

        $tagGlobalModel = new TagGlobal($this->db, $this->table_prefix);
        $tagGlobals = $tagGlobalModel->readAllWithTagsAvailableBySiteId($this->site_id);
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

        $this->tpl->parse_if('content_subitem', 'lo_tags_items_available', $tagGlobals);
        $this->tpl->parse_if('content_subitem', 'lo_tags_items_unavailable', !$tagGlobals);
        $this->tpl->parse_loop('content_subitem', $selected, 'lo_tags_items_selected');
        $this->tpl->parse_vars('content_subitem', array(
          'lo_tag_options' => AbstractForm::multiSelectOptgroups($tagOptions, array_keys($selected)),
        ));
      }

      $functionView->parse('content_subitem', $this->_functionAdditionalTextLevel, $page);
      if ($this->_functionAdditionalTextLevel->isActive()) {
        $labelTextLevel = $page->isAdditionalTextLevel() ?
                          $_LANG['lo_additionaltextlevel_checked_true_label'] :
                          $_LANG['lo_additionaltextlevel_checked_false_label'];
        $checked = $page->isAdditionalTextLevel() ? 'checked="checked"' : '';
        $this->tpl->parse_vars('content_subitem', array(
          'lo_additionaltextlevel_checked'       => $checked,
          'lo_additionaltextlevel_checked_label' => $labelTextLevel,
        ));
      }

      $functionView->parse('content_subitem', $this->_functionAdditionalImageLevel, $page);
      if ($this->_functionAdditionalImageLevel->isActive()) {
        $labelTextLevel = $page->isAdditionalImageLevel() ?
                          $_LANG['lo_additionalimagelevel_checked_true_label'] :
                          $_LANG['lo_additionalimagelevel_checked_false_label'];
        $checked = $page->isAdditionalImageLevel() ? 'checked="checked"' : '';
        $this->tpl->parse_vars('content_subitem', array(
          'lo_additionalimagelevel_checked'       => $checked,
          'lo_additionalimagelevel_checked_label' => $labelTextLevel,
        ));
      }

      $functionView->parse('content_subitem', $this->_functionAdditionalText, $page);
      if ($this->_functionAdditionalText->isActive()) {
        $text = $page->getAdditionalText();
        $this->tpl->parse_if('content_subitem', 'lo_additionaltext_text_available', $text);
        $this->tpl->parse_if('content_subitem', 'lo_additionaltext_text_unavailable', !$text);
        $this->tpl->parse_vars('content_subitem', array(
          'lo_additionaltext' => parseOutput($text),
          'lo_additionaltext_plain' => strip_tags($text),
        ));
      }

      $functionView->parse('content_subitem', $this->_functionAdditionalImage, $page);
      if ($this->_functionAdditionalImage->isActive()) {
        $image = $page->getAdditionalImage() ? $page->getAdditionalImage() : '';
        if ($page->getAdditionalImage()) {
          $image = '../' . $page->getAdditionalImage();
        }
        $deleteLink = 'index.php?action=content&amp;site=' . $this->site_id
                    . '&amp;page=' . $this->page_id . '&amp;delete_additionalimage=1'
                    . '&amp;ofPage='.$page->getID();

        $this->tpl->parse_if('content_subitem', 'lo_additionalimage_image_unavailable', !$image);
        $this->tpl->parse_if('content_subitem', 'lo_additionalimage_image_available', $image, array(
          'lo_additionalimage' => $image
        ));
        $this->tpl->parse_if('content_subitem', 'delete_image', $image, array(
          'lo_additionalimage_delete_label' => $_LANG['global_delete_image_label'],
          'lo_additionalimage_delete_question_label' => $_LANG['global_delete_image_question_label'],
          'lo_additionalimage_delete_link' => $deleteLink,
        ));
        $this->tpl->parse_vars('content_subitem', array(
          'lo_additionalimage_required_resolution_label' => $this->_getImageSizeInfo('lo_additional', 0),
        ));
      }

      $content = $this->tpl->parsereturn('content_subitem', array_merge(array(
        'lo_id'                => $page->getID(),
      ), $_LANG2['global']));

      return $content;
    }

    private function _updatePageAdditionalTextLevelFromRequest(NavigationPage $page)
    {
      if ($this->_functionAdditionalTextLevel->isAvailableForUserOnPage(
              $this->_user, $page)
      ) {
        $id = $page->getID();
        $value = 0;
        if ($this->_post->exists("lo{$id}_additionaltextlevel")) {
          $value = 1;
        }
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . " SET CAdditionalTextLevel = '$value' "
             . " WHERE CIID = '$id' ";
        $this->db->query($sql);
      }
    }

    private function _updatePageAdditionalTextFromRequest(NavigationPage $page)
    {
      if ($this->_functionAdditionalText->isAvailableForUserOnPage(
              $this->_user, $page)
      ) {
        $id = $page->getID();
        $value = '';
        if ($this->_post->exists("lo{$id}_additionaltext")) {
          $value = $this->_post->readString("lo{$id}_additionaltext",
              Input::FILTER_CONTENT_TEXT);
        }
        $sql = " UPDATE {$this->table_prefix}contentabstract "
             . " SET CAdditionalText = '{$this->db->escape($value)}' "
             . " WHERE FK_CIID = '$id' ";
        $this->db->query($sql);
      }
    }

    private function _updatePageAdditionalImageFromRequest(NavigationPage $page)
    {
      if ($this->_functionAdditionalImage->isAvailableForUserOnPage(
              $this->_user, $page)
      ) {
        $id = $page->getID();
        $upload = new CmsUpload("lo{$id}_additionalimage");
        if ($upload->notEmpty()) {
          $size = $this->_storeImageGetSize(CmsImageFactory::create($upload->getTemporaryName()), 'lo_additional', 0, true);
          if ($size != ContentBase::IMAGESIZE_INVALID) {
            $sql = " SELECT CAdditionalImage "
                 . " FROM {$this->table_prefix}contentabstract "
                 . " WHERE FK_CIID = '$id' ";

            $existing = $this->db->GetOne($sql);
            $components = array($this->site_id, $id, 'additional');
            $image = $this->_storeImageWithSize($upload->getArray(), $existing,
                ConfigHelper::get('lo_additional_image_width'),
                ConfigHelper::get('lo_additional_image_height'),
                ConfigHelper::get('lo_additional_selection_width'),
                ConfigHelper::get('lo_additional_selection_height'),
                'lo_additionalimage', 0, $components);

            $sql = " UPDATE {$this->table_prefix}contentabstract "
                 . " SET CAdditionalImage = '{$this->db->escape($image)}' "
                 . " WHERE FK_CIID = '$id' ";
            $this->db->query($sql);
          }
        }
      }
    }

    private function _removeAdditionalImageIfRequested()
    {
      global $_LANG;

      $pageId = $this->_get->readInt('ofPage');
      try {
        $page = $this->_navigation->getPageByID($pageId);
        $available = $pageId && $this->_functionAdditionalImage
            ->isAvailableForUserOnPage($this->_user, $page);
        if ($available && $this->_get->exists('delete_additionalimage')) {
          $id = $page->getID();
          $sql = " SELECT CAdditionalImage "
               . " FROM {$this->table_prefix}contentabstract "
               . " WHERE FK_CIID = '$id' ";
          $existing = $this->db->GetOne($sql);

          if ($existing) {
            unlinkIfExists('../' . $existing);
          }

          $sql = " UPDATE {$this->table_prefix}contentabstract "
               . " SET CAdditionalImage = '' "
               . " WHERE FK_CIID = '$id' ";
          $this->db->query($sql);

          $this->setMessage(Message::createSuccess($_LANG['lo_message_image_delete_success']));
          $this->_lastEditedItemID = $pageId;
        }
      }
      catch(Exception $e) {}
    }

    private function _updatePageAdditionalImageLevelFromRequest(NavigationPage $page)
    {
      if ($this->_functionAdditionalImageLevel->isAvailableForUserOnPage(
              $this->_user, $page)
      ) {
        $id = $page->getID();
        $value = 0;
        if ($this->_post->exists("lo{$id}_additionalimagelevel")) {
          $value = 1;
        }
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . " SET CAdditionalImageLevel = '$value' "
             . " WHERE CIID = '$id' ";
        $this->db->query($sql);
      }
    }

    private function _updatePageBlogFromRequest(NavigationPage $page)
    {
      if ($this->_functionBlog->isAvailableForUserOnPage(
              $this->_user, $page)
      ) {
        $id = $page->getID();
        $value = 0;
        if ($this->_post->exists("lo{$id}_blog")) {
          $value = 1;
        }
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . " SET CBlog = '$value' "
             . " WHERE CIID = '$id' ";
        $this->db->query($sql);
      }
    }

    private function _updatePageShareFromRequest(NavigationPage $page)
    {
      $siteId = $page->getSite()->getID();
      $displayShareConfig  = ConfigHelper::get('m_share_display');
      $displayShareConfig = isset($displayShareConfig[$siteId]) ?
          $displayShareConfig[$siteId] : $displayShareConfig[0];
      if ($this->_functionShare->isAvailableForUserOnPage(
              $this->_user, $page) && $displayShareConfig
      ) {
        $id = $page->getID();
        $value = 0;
        if ($this->_post->exists("lo{$id}_share")) {
          $value = 1;
        }
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . " SET CShare = '$value' "
             . " WHERE CIID = '$id' ";
        $this->db->query($sql);
      }
    }

    private function _updatePageTaggableFromRequest(NavigationPage $page)
    {
      if ($this->_functionTaglevel->isAvailableForUserOnPage(
              $this->_user, $page)
      ) {
        $id = $page->getID();
        $value = 0;
        if ($this->_post->exists("lo{$id}_taggable")) {
          $value = 1;
        }
        $sql = " UPDATE {$this->table_prefix}contentitem "
             . " SET CTaggable = '$value' "
             . " WHERE CIID = '$id' ";
        $this->db->query($sql);
      }
    }

    private function _getPageFromLastEditedItem()
    {
      $id = (int)$this->_lastEditedItemID;
      if ($this->_get->readInt('page_copy')) {
        $pageDuplicate = $this->_navigation->getPageByID($this->_get->readInt('page_copy'));
        if ($pageDuplicate) {
          $this->_lastEditeditemID = $pageDuplicate->getID();
        }
      }

      try {
        $page = $this->_navigation->getPageByID($this->_lastEditedItemID);
      }
      catch(Exception $e) {
        $page = null;
      }
      return $page;
    }

    private function _getLastEditedBox()
    {
      $box = 'box';
      if ($this->_post->readKey('process_date')) {
        $box = 'timebox';
      }

      return $box;
    }

    private function _getLastEditedScrollToAnchor(NavigationPage $lastEditedPage)
    {
      $request = new Input(Input::SOURCE_REQUEST);
      $anchor = $request->readString("lo{$lastEditedPage->getID()}_scroll_to_anchor");

      // Open box of page duplicate
      if (   $this->_get->readInt('page_copy')
          && $this->_get->readInt('page_copy') == $lastEditedPage->getID()
      ) {
        $anchor = 'anchor_lobox'.$lastEditedPage->getPosition();
      }

      return $anchor;
    }

    /**
     * @param NavigationPage $page
     *        The page / contentitem to retrieve the light for.
     *
     * @return string
     *         The mobile switch light string i.e. yellow, green, red, ...
     */
    private function _getMobileSwitchLight(NavigationPage $page)
    {
      $activationState = $page->getMobileSwitchState();
      switch ($activationState) {
        case NavigationPage::MOBILE_SWITCH_ON:
          $mobileSwitchLight = CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_ON;
          break;
        case NavigationPage::MOBILE_SWITCH_OFF:
          $mobileSwitchLight = CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_OFF;
          break;
        case NavigationPage::MOBILE_SWITCH_ABOVE_OFF:
          $mobileSwitchLight = CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_ABOVE_OFF;
          break;
        case NavigationPage::MOBILE_SWITCH_DISABLED:
          $mobileSwitchLight = CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_DISABLED;
          break;
        default:
          throw new Exception("Unknown mobile switch state '$activationState'.");
      }

      if ($mobileSwitchLight != CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_ON) {
        return $mobileSwitchLight;
      }

      $activationLight = $page->getActivationLight();
      if (   $activationLight != ActivationLightInterface::GREEN
          && $activationLight != ActivationClockInterface::GREEN
      ) {
        return CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_DISABLED;
      }

      return $mobileSwitchLight;
    }

    /**
     * Changes the mobile switch state of a content item if the GET parameters
     * changeMobileSwitchID and changeMobileSwitchTo are set.
     */
    private function _changeMobileSwitchState()
    {
      global $_LANG;

      $get = new Input(Input::SOURCE_GET);

      $changeActivationID = $get->readInt('changeMobileSwitchID');
      if (!$changeActivationID || !$get->exists('changeMobileSwitchTo')) {
        return;
      }
      $changeActivationTo = $get->readInt('changeMobileSwitchTo');

      $page = $this->_navigation->getPageByID($changeActivationID);
      $existingState = $page->getMobileSwitchState();
      // Nothing to change here...
      if ($existingState == $changeActivationTo) {
        return;
      }
      // It is only possible to set the mobile switch state to on or off.
      if ($changeActivationTo != NavigationPage::MOBILE_SWITCH_OFF
         && $changeActivationTo != NavigationPage::MOBILE_SWITCH_ON) {
        return;
      }
      // We can not change the state if the page is not activated.
      if ($page->getActivationState() != NavigationPage::ACTIVATION_ENABLED) {
        return;
      }

      $activationChanged = false;
      switch ($changeActivationTo) {
        case NavigationPage::MOBILE_SWITCH_ON:
            $page->setMobileSwitchOn();
            $this->setMessage(Message::createSuccess($_LANG['lo_message_mobile_status_on_success']));
            $activationChanged = true;
          break;
        case NavigationPage::MOBILE_SWITCH_OFF:
            $page->setMobileSwitchOff();
            $this->setMessage(Message::createSuccess($_LANG['lo_message_mobile_status_off_success']));
            $activationChanged = true;
          break;
        default:
          return;
      }

      // if activation changed successfully, we clear navigation cache and
      // remove cached sitemap files
      if ($activationChanged) {
        $this->_navigation->clearCache($this->db, $this->table_prefix);
        BackendRequest::removeCachedFiles($this->site_id);
      }
    }

    /**
     * Gets the seo management instance with given page id.
     * @param int $pageId
     * @return CFunctionSeoManagement
     */
    private function _getFunctionSeoManagement($pageId)
    {
      global $_MODULES;
      if (isset($this->_functionSeoManagement[$pageId]) && $this->_functionSeoManagement[$pageId] !== null) {
        return $this->_functionSeoManagement[$pageId];
      }
      $this->_functionSeoManagement[$pageId] = new CFunctionSeoManagement(
        $this->db, $this->table_prefix, $this->session, $this->_navigation, $_MODULES);
      // Set the specific page id.
      $this->_getFunctionSeoManagement($pageId)->setPageId($pageId);
      return $this->_functionSeoManagement[$pageId];
    }
  }
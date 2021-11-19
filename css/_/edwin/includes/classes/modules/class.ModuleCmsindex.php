<?php

  /**
   * CMS Index Module Class
   *
   * $LastChangedDate: 2018-04-26 11:00:47 +0200 (Do, 26 Apr 2018) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ModuleCmsindex extends Module
  {
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){
      global $_LANG, $_LANG2, $_MODULES;

      // delete broken internal links
      $this->_deleteBrokenInternalLinks();

      $ciHiddenFields = '<input type="hidden" id="scrollToAnchor" name="scrollToAnchor" value="" />';
      $ciHiddenFields .= '<input type="hidden" id="ciSectionLastEdited" name="ciSectionLastEdited" value="" />';

      $ciScrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';
      $ciSectionLastEdited = isset($_REQUEST['ciSectionLastEdited']) ? $_REQUEST['ciSectionLastEdited'] : '';

      $ciBrokenIntlinks = '';
      $ciDisabledPages = '';
      switch($ciSectionLastEdited)
      {
        case 'bl_int':
          $ciBrokenIntlinks = $this->_parseBrokenInternalLinks();
          break;
        default:
          $ciDisabledPages = $this->_parseDisabledPages();
          $ciSectionLastEdited = 'bl_deact';
      }
      $searchModule = new ModuleSearch($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix,
                                       '', '', $this->_user, $this->session, $this->_navigation);
      $this->tpl->load_tpl('cms_index', 'modules/ModuleCmsindex.tpl');
      $this->tpl->parse_if('cms_index', 'ci_sites_available', !$this->_user->noSites());
      $this->tpl->parse_if('cms_index', 'ci_module_search_last_search_term', $searchModule->readLastSearchTerm());
      $this->tpl->parse_if('cms_index', 'ci_module_search_available', $searchModule->isAvailableForUser($this->_user, $this->_navigation->getSiteByID($this->site_id)));
      $this->tpl->parse_if('cms_index', 'ci_timing_tab_available', (ConfigHelper::get('ci_timing_type') != 'deactivated'));
      $this->tpl->parse_if('cms_index', 'message', $this->_getMessage() && $ciSectionLastEdited != 'bl_deact', $this->_getMessageTemplateArray('ci'));
      $si_content = $this->tpl->parsereturn('cms_index', array_merge(
        array (
          'ci_change_pw_link'       => "index.php?action=change_pw",
          'ci_disabled_pages'       => $ciDisabledPages,
          'ci_broken_intlinks'      => $ciBrokenIntlinks,
          'ci_permitted_paths'      => $this->_getPermittedPaths(),
          'ci_site_quicklinks'      => $this->_getQuicklinks(),
          'ci_requirements'         => $_LANG["ci_requirements"],
          'ci_sroll_to_anchor'      => $ciScrollToAnchor,
          'ci_section_last_edited'  => $ciSectionLastEdited,
          'ci_hidden_fields'        => $ciHiddenFields,
          'ci_disk_space'           => $this->_getDiskSpaceUsageMessage(),
          'ci_version'              => sprintf($_LANG["global_display_core_version"], BackendRequest::EDWIN_CORE_VERSION) ),
        $_LANG2['ci']
      ));

      return array(
          'content' => $si_content,
      );
    }

    /**
     * Creates the Quicklink navigation.
     *
     * @return string
     *        The completely parsed quicklink navigation.
     */
    private function _getQuicklinks()
    {
      global $_LANG, $_LANG2;

      $siteIDs = array();
      // remove unavailable site ids from array
      foreach ($this->_allSites as $siteId => $site) {
        if ($this->_user->AvailableSite($siteId)) {
          $siteIDs[] = $siteId;
        }
      }

      $quicklinksArray = array();
      foreach ($siteIDs as $siteID)
      {
        $siteindexLink = '';
        if ($this->_user->AvailableSiteIndex($siteID)) {
          $siteindexLink = "index.php?action=mod_siteindex&amp;site=$siteID";
        }

        $linkItems = array();
        $site = $this->_navigation->getSiteByID($siteID);

        $rootPages = $site->getRootPages();
        foreach ($rootPages as $root)
        {
          // Do not display a quicklink for the user-tree ( = Navigation::getRootPages()
          // returns an array) and pages the user has not got permission to.
          if (is_array($root) || !$this->_user->AvailablePage($root)) {
            continue;
          }

          $linkItems[] = array(
            'ci_quicklinks_link'  => "index.php?action=content&amp;site=$siteID&amp;page={$root->getID()}",
            'ci_quicklinks_label' => $_LANG["global_edit_{$root->getTree()}_label"],
            'ci_quicklinks_tree'  => $root->getTree(),
          );
        }

        // no quicklink items - do not display empty container
        if (empty($linkItems) && empty($siteindexLink)) {
          continue;
        }

        $this->tpl->load_tpl('cms_index_quicklinks', 'modules/ModuleCmsindex_site_quicklinks_part.tpl');
        $this->tpl->parse_loop('cms_index_quicklinks', $linkItems, 'link_items');
        $this->tpl->parse_if('cms_index_quicklinks', 'site_siteindex', $siteindexLink, array());
        $quicklinksArray[$siteID] = $this->tpl->parsereturn('cms_index_quicklinks', array(
          'ci_quicklinks_siteindex_link'         => $siteindexLink,
          'ci_quicklinks_site_label'             => $this->_allSites[$siteID],
          'ci_quicklinks_language'               => $site->getLanguage(),
          'ci_quicklinks_language_label'         => (isset($_LANG["global_language_{$site->getLanguage()}_label"])) ? $_LANG["global_language_{$site->getLanguage()}_label"] : '',
          // do not display the language bar containing the flag and site title if
          // there is only one site available for the current user
          'ci_quicklinks_language_display_style' => count($siteIDs) > 1 ? '' : ' style="display:none;" ',
        ));
      }

      // no site items containing quicklinks - do not quicklinks section at all
      if (empty($quicklinksArray)) {
        return '';
      }

      $orderedSites = array();
      // retrieve all sites that aren't language children of other sites
      // non portal sites are listed first (FK_SID_Portal = NULL)
      $sql = " SELECT * FROM {$this->table_prefix}site "
           . " WHERE FK_SID_Language IS NULL "
           . " ORDER BY FK_SID_Portal ASC, SPositionPortal ASC ";
      $result = $this->db->query($sql);

      /**
       * if site is a portal site add it to portal parents array index in order to
       * get a list grouped by portals with subportals array[portalid][portalid/subportalid]
       */
      while ($row = $this->db->fetch_row($result))
      {
        if ($row['FK_SID_Portal']) {
          $orderedSites[$row['FK_SID_Portal']][] = $row;
        }
        else {
          $orderedSites[$row['SID']][] = $row;
        }
      }

      // take ordered sites array and generate the Quicklinks for all portals / subportals
      // including languages
      $linkBox = array();
      foreach ($orderedSites as $parentIndex)
      {
        foreach ($parentIndex as $row)
        {
          $siteID = $row['SID'];
          $site = $this->_navigation->getSiteByID($siteID);
          $this->_getOrderedQuicklinks($linkBox, $quicklinksArray, $site);
        }
      }
      $parseIds = array();
      $ids = array_keys($linkBox);
      foreach ($ids as $key => $id) {
        $site = $this->_navigation->getSiteByID($id);
        $languageParent = $site->getLanguageParent();

        $languageTitle = $this->_allSites[$id];
        // use special title of language sites if available, but only if there is
        // more than one site available for this user, otherwise use the available
        // sites title
        if (isset($_LANG["global_sites_backend_language_site_general_label"][$id]))
        {
          if (count($siteIDs) > 1) {
            $languageTitle = $_LANG["global_sites_backend_language_site_general_label"][$id];
          }
          else {
            $languageTitle = ContentBase::getLanguageSiteLabel($site);
          }
        }

        $parseIds[] = array(
          'ci_site_id'   => $id,
          'ci_site_type' => $site->getPortalParent() ? 'subportal' : 'portal',
          'ci_site_language_general_label' => $languageTitle,
        );
      }

      $this->tpl->load_tpl('cms_index_quicklinks', 'modules/ModuleCmsindex_site_quicklinks.tpl');
      // parse the portal loop
      $this->tpl->parse_loop('cms_index_quicklinks', $parseIds, 'site_items');
      // for each portal there may exist one or more languages
      foreach ($linkBox as $id => $values) {
        // parse languages
        $this->tpl->parse_loop('cms_index_quicklinks', $values, "site_items_{$id}");
      }
      return $this->tpl->parsereturn('cms_index_quicklinks', array());

    }

    /**
     * Recursivly creates an array containing parsed quicklinks content foreach
     * site with language sites content added foreach site
     *
     * @param array $linBox - the array to add the parsed content to
     * @param array $quicklinksArray - contains unordered parsed content for all sites available
     * @param NavigationSite $site - NavigationSite object of parent site
     */
    private function _getOrderedQuicklinks(&$linkBox, $quicklinksArray, NavigationSite $site)
    {
      $siteID = $site->getID();
      if (key_exists($siteID, $quicklinksArray)) {
        // for language sites add data to array index of parent language
        $langParent = $site->getLanguageParent();
        if ($langParent) {
          $linkBox[$langParent->getID()][$site->getID()] = array('ci_site_quicklink_box' => $quicklinksArray[$siteID]);
        }
        else {
          $linkBox[$site->getID()][$site->getID()] = array('ci_site_quicklink_box' => $quicklinksArray[$siteID]);
        }
      }

      $languageChildren = $site->getLanguageChildren();
      foreach ($languageChildren as $langChild) {
        $this->_getOrderedQuicklinks($linkBox, $quicklinksArray, $langChild);
      }
    }

    /**
     * Get the permitted paths section.
     *
     * @return string
     *         The parsed permitted paths section.
     */
    private function _getPermittedPaths()
    {
      $tmpSites = $this->_user->getPermittedPaths();

      if (!$tmpSites) {
        return '';
      }

      $ciPermittedPaths = "";
      // read permitted paths
      foreach ($tmpSites as $tmpSiteID => $tmpSiteTrees)
      {
        foreach ($tmpSiteTrees as $tmpSiteTree => $tmpSitePaths)
        {
          // Do not display the user navigation tree.
          if ($tmpSiteTree == Navigation::TREE_USER) {
            continue;
          }

          $permittedPaths = array();
          foreach ($tmpSitePaths as $tmpPath)
          {
            $sql = "SELECT CTitle, CIID "
                  ."FROM {$this->table_prefix}contentitem "
                  ."WHERE FK_SID = {$tmpSiteID} "
                  ."  AND CIIdentifier='{$tmpPath}'";
            $result = $this->db->query($sql);
            $row = $this->db->fetch_row($result);
            $permittedPaths[] = array(
              'ci_ppath_label' => $row["CTitle"],
              'ci_ppath_link'  => "index.php?action=content&amp;site=".$tmpSiteID."&amp;page=".$row["CIID"],
            );
            $this->db->free_result($result);
          }

          $tmpSite = $this->_navigation->getSiteByID($tmpSiteID);

          $this->tpl->load_tpl('cms_index_pp', 'modules/ModuleCmsindex_permitted_paths.tpl');
          $this->tpl->parse_loop('cms_index_pp', $permittedPaths, 'permitted_items');
          $ciPermittedPaths .= $this->tpl->parsereturn('cms_index_pp', array (
            'ci_site_label' => $this->getLanguageSiteLabel($tmpSite),
          ));
        }
      }

      return $ciPermittedPaths;
    }

    public function sendResponse($request)
    {
      parent::sendResponse($request);

      switch ($request) {
        case 'BrokenLinksInternal':
          echo $this->_parseBrokenInternalLinks();
          break;
        case 'BrokenLinksInsideText':
          echo $this->_parseBrokenTextLinks();
          break;
        case 'BrokenLinksInsideTextInformation':
          echo $this->_parseBrokenTextLinksInfo();
          break;
        case 'DisabledByTimingPages':
          echo $this->_parseDisabledByTimingPages();
          break;
        case 'DisabledPages':
          echo $this->_parseDisabledPages();
        default:
          break;
      }
    }

    /**
     * Parses the text links section of the cms index
     * @return string - the parsed text links section
     */
    private function _parseBrokenTextLinks()
    {
      global $_LANG, $_LANG2;

      /**
       * Retrieve all pages with broken internal links
       * or download links within their content (texts)
       */
      $sql =  " SELECT CIID, ci.FK_CTID, CTClass "
            . " FROM {$this->table_prefix}contentitem ci "
            . " JOIN {$this->table_prefix}contenttype ct "
            . "   ON ci.FK_CTID = ct.CTID ";
      $result = $this->db->query($sql);
      $ids = array();
      $broken = array();
      while ($row = $this->db->fetch_row($result))
      {
        // foreach contentitem check its texts for broken links inside
        $contentItemClass = $row['CTClass'];

        if (!file_exists("includes/classes/content_types/class.$contentItemClass.php"))
          continue;

        include_once "includes/classes/content_types/class.$contentItemClass.php";
        if (!class_exists($contentItemClass))
          exit();

        // Create and return an instance of the class.
        $ci = ContentItem::create($this->site_id, $row['CIID'],
                            $this->tpl, $this->db, $this->table_prefix,
                            $this->action, $this->_user, $this->session,
                            $this->_navigation);
        // check texts of contentitem, if broken links occur add to $cisWithBroken
        if ($ci->hasBrokenTextLink())
        {
          $ids[] = $row['CIID'];
        }
      }
      $this->db->free_result($result);

      // now add the pages to the set of pages allready retrieved before
      // (broken links within internal link area)
      if ($ids) {
        $ids = implode(', ', $ids);
        $sql = "SELECT CIID, CTitle, CIIdentifier, FK_SID, CPosition, CTree "
              ."FROM {$this->table_prefix}contentitem "
              ."WHERE CIID IN ({$ids}) "
              ."ORDER BY FK_SID ASC, CIIdentifier ASC ";
        $result = $this->db->query($sql);

        while ($row = $this->db->fetch_row($result)) {
          // only create output if user has access to site / pages
          if ($this->_user->AvailableSite($row["FK_SID"])
             && $this->_user->AvailablePath($row["CIIdentifier"], $row["FK_SID"], $row["CTree"]))
          {
            // get Title of parents and item itself
            $page = $this->_navigation->getPageByID($row['CIID']);
            $titles = array();
            $titles[] = $page->getTitle();
            while ($page = $page->getParent())
            {
              $titles[] = $page->getTitle();
            }
            // remove last title (Portal-Home) and change order
            array_pop($titles);
            $titles = array_reverse($titles);
            $brokenLink = array (
              'ci_broken_link_link'    => "index.php?action=content&amp;site=".$row["FK_SID"]."&amp;page=".$row["CIID"],
              'ci_broken_link_page'    => parseOutput(implode(' | ', $titles)),
              'ci_broken_link_page_id' => $row["CIID"],
            );
            $broken[$row['FK_SID']][] = $brokenLink;
          }
        }
        $this->db->free_result($result);
      }

      $ciBrokenLinks = '';
      foreach ($broken as $site => $brokenItems)
      {
        $this->tpl->load_tpl('cms_index_bl', 'modules/ModuleCmsindex_broken_links.tpl');
        $this->tpl->parse_loop('cms_index_bl', $brokenItems, 'broken_links');
        $ciBrokenLinks .= $this->tpl->parsereturn('cms_index_bl', array_merge(array (
          'ci_site_label' => parseOutput(ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($site)))
        ), $_LANG2['ci']));
      }

      if (!$ciBrokenLinks) {
        $this->setMessage(Message::createFailure($_LANG['ci_message_no_broken_textlinks_found']));
        return $this->_parseNoLinksFound();
      }

      return $ciBrokenLinks;
    }

    /**
     * Parses the text links info section of the cms index
     * @return string - the parsed text links info section
     */
    private function _parseBrokenTextLinksInfo()
    {
      global $_LANG, $_LANG2;

      $get = new Input(Input::SOURCE_GET);
      $ID = $get->readInt('page');
      if (!$ID) {
        echo "";
      }

      $sql =  " SELECT CIID, c.FK_CTID, FK_SID, CTClass "
            . " FROM {$this->table_prefix}contentitem c "
            . " JOIN {$this->table_prefix}contenttype ct ON c.FK_CTID = CTID "
            . " WHERE CIID = $ID";
      $row = $this->db->GetRow($sql);

      $contentItemClass = $row['CTClass'];
      if (!file_exists("includes/classes/content_types/class.$contentItemClass.php"))
        return;

      include_once "includes/classes/content_types/class.$contentItemClass.php";
      if (!class_exists($contentItemClass))
        exit();

      // Create and return an instance of the class.
      $ci = ContentItem::create($this->site_id, $row['CIID'],
                          $this->tpl, $this->db, $this->table_prefix,
                          $this->action, $this->_user, $this->session,
                          $this->_navigation);
      $broken = array();
      // retrieve broken links from content items texts
      $bls = $ci->getBrokenTextLinks();
      foreach ($bls as $bl)
      {
        $broken[] = array(
          'ci_bl_info_type'       => $bl['type'] == 'internal' ? 'internal' : 'file',
          'ci_bl_info_type_label' => $bl['type'] == 'internal' ?
            $_LANG['ci_broken_type_internal_label'] : $_LANG['ci_broken_type_file_label'],
          'ci_bl_info_title'      => parseOutput($bl['title'],2),
          'ci_bl_info_link'       => $bl['link'],
        );
      }

      $ciBrokenLinks = '';
      $this->tpl->load_tpl('cms_index_bl_info', 'modules/ModuleCmsindex_broken_links_info.tpl');
      $this->tpl->parse_loop('cms_index_bl_info', $broken, 'broken_links');

      $ciBrokenLinks = $this->tpl->parsereturn('cms_index_bl_info', $_LANG2['ci']);

      return $ciBrokenLinks;
    }

    /**
     * Removes broken internal links if
     * - process_broken_intlinks_delete and
     * - broken_intlink
     * POST variables are set
     */
    private function _deleteBrokenInternalLinks()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      if (!$post->exists('process_broken_intlinks_delete') || !$post->exists('broken_intlink')) {
        return;
      }

      $IDs = $post->readArrayIntToInt('broken_intlink');
      if ($IDs) {
        $sqlIDs = implode(', ', $IDs);
        $sql = "DELETE FROM {$this->table_prefix}internallink "
              ."WHERE ILID IN ($sqlIDs)";

        $result = $this->db->query($sql);

        if (count($IDs) == 1 && $result) {
          $message = $_LANG['ci_message_intlinks_delete_success_one'];
        } else if ($result) {
          $message = sprintf($_LANG['ci_message_intlinks_delete_success_more'], count($IDs));
        }

        $this->db->free_result($result);

        $this->setMessage(Message::createSuccess($message));
      }
    }

    /**
     * Parses internal links section of the cms index
     * @return string - the parsed internal links section
     */
    private function _parseBrokenInternalLinks()
    {
      global $_LANG, $_LANG2;

      // stores broken links
      $broken = array();

      /**
       * Retrieve all pages with broken internal links in their internal links section
       */
      $sql = "SELECT DISTINCT(page.CIID), page.FK_SID, page.CIIdentifier, page.CTree "
            ."FROM {$this->table_prefix}internallink il "
            ."LEFT JOIN {$this->table_prefix}contentitem target ON target.CIID = il.FK_CIID_Link "
            ."JOIN {$this->table_prefix}contentitem page ON page.CIID = il.FK_CIID "
            ."WHERE ILID NOT IN "
            ."  ( "
            ."     SELECT il2.ILID "
            ."     FROM {$this->table_prefix}internallink il2 "
            ."     JOIN {$this->table_prefix}contentitem ON CIID = il2.FK_CIID_Link "
            ."  ) "
            ."ORDER BY page.CIIdentifier ASC ";
      $result = $this->db->query($sql);

      while ($row = $this->db->fetch_row($result))
      {
        // only create output if user has access to site / pages
        if ($this->_user->AvailableSite($row["FK_SID"])
           && $this->_user->AvailablePath($row["CIIdentifier"], $row["FK_SID"], $row["CTree"]))
        {
          // get broken internal links for current page
          $sql = "SELECT ILID, ILTitle "
                ."FROM {$this->table_prefix}internallink "
                ."WHERE FK_CIID = {$row['CIID']} "
                ."AND FK_CIID_Link NOT IN ( "
                ."    SELECT CIID FROM {$this->table_prefix}contentitem "
                .")";
          $links = $this->db->query($sql);
          $brokenLinks = array();
          while ($link = $this->db->fetch_row($links))
          {
            $brokenLinks[] = array(
              'ci_broken_link_title' => parseOutput($link['ILTitle']),
              'ci_broken_link_id' => parseOutput($link['ILID']),
            );
          }

          $this->tpl->load_tpl('cms_index_bli', 'modules/ModuleCmsindex_broken_intlinks_links.tpl');
          $this->tpl->parse_loop('cms_index_bli', $brokenLinks, 'links');
          // get Title of parents an item itself
          $page = $this->_navigation->getPageByID($row['CIID']);
          $titles = array();
          $titles[] = parseOutput($page->getTitle());
          while ($page = $page->getParent())
          {
            $titles[] = parseOutput($page->getTitle());
          }
          // remove last title (Portal-Home) and change order
          array_pop($titles);
          $titles = array_reverse($titles);
          $brokenLinkPage = array (
            'ci_broken_link_page_link'  => "index.php?action=intlinks&amp;site=".$row["FK_SID"]."&amp;page=".$row["CIID"],
            'ci_broken_link_page_title' => implode(' | ', $titles),
            'ci_broken_intlinks'        => $this->tpl->parsereturn('cms_index_bli', array()),
          );
          $broken[$row['FK_SID']][] = $brokenLinkPage;
        }
      }
      $this->db->free_result($result);



      $ciBrokenLinks = '';
      $count = 0;
      foreach ($broken as $site => $brokenItems)
      {
        $count ++;
        $this->tpl->load_tpl('cms_index_bli', 'modules/ModuleCmsindex_broken_intlinks.tpl');
        $this->tpl->parse_loop('cms_index_bli', $brokenItems, 'broken_links');
        $this->tpl->parse_if('cms_index_bli', 'first_site', $count == 1, array());
        $ciBrokenLinks .= $this->tpl->parsereturn('cms_index_bli', array_merge(array (
          'ci_site_label' => parseOutput(ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($site)))
        ), $_LANG2['ci']));
      }

      if (!$ciBrokenLinks && !$this->_getMessage()) {
        $this->setMessage(Message::createFailure($_LANG['ci_message_no_broken_intlinks_found']));
        return $this->_parseNoLinksFound();
      }

      return $ciBrokenLinks;
    }

    /*
     * Parses section containig pages disabled by timing
     * @return string - the parsed disabled pages area
     */
    private function _parseDisabledByTimingPages()
    {
      global $_LANG, $_LANG2;

      // stores pages deactivated because of their date / timing
      $disabled = array();

      $now = date('Y-m-d H:i:s');

      // retrieve all pages with enabled direct parents
      $sql = "SELECT CIID, FK_SID, CIIdentifier, FK_CIID, CShowFromDate, CShowUntilDate, CType, CTree "
            ."FROM {$this->table_prefix}contentitem "
            ."WHERE CDisabled = 0 "
            ."AND ((CShowFromDate IS NOT NULL AND CShowFromDate > '$now') "
            ."OR (CShowUntilDate IS NOT NULL AND CShowUntilDate < '$now')) "
            ."AND FK_CIID NOT IN ( "
            ."    SELECT CIID "
            ."    FROM {$this->table_prefix}contentitem "
            ."    WHERE CDisabled = 1 "
            ."    OR (CShowFromDate IS NOT NULL AND CShowFromDate > '$now') "
            ."    OR (CShowUntilDate IS NOT NULL AND CShowUntilDate < '$now') "
            .") "
            ."ORDER BY CIIdentifier ASC ";
      $result = $this->db->query($sql);

      while ($row = $this->db->fetch_row($result))
      {
        if ($this->_user->AvailableSite($row["FK_SID"])
           && $this->_user->AvailablePath($row["CIIdentifier"], $row["FK_SID"], $row["CTree"]))
        {
          // direct parent of page is enabled and in time, but if page has any
          //disabled ancestors do not show it, and continue with next page
          $page = $this->_navigation->getPageByID($row['CIID']);
          // if timing is possibly enabled above
          if ($page->getTimingState() != NavigationPage::TIMING_ENABLED)
          {
            $timedAbove = false;
            while ($page = $page->getParent())
            {
              if (trim($page->getStartDate()) != '' && strtotime($page->getStartDate()) > strtotime($now)) {
                $timedAbove = true;
                break;
              }
              else if (trim($page->getEndDate()) && strtotime($page->getEndDate()) < strtotime($now)) {
                $timedAbove = true;
                break;
              }
            }
            if ($timedAbove) {
              continue;
            }
          }


          $page = $this->_navigation->getPageByID($row['CIID']);
          $titles = array();
          do {
            $titles[] = $page->getTitle();
          }
          while ($page = $page->getParent());
          // remove last title (Portal-Home) and change order
          array_pop($titles);
          $titles = array_reverse($titles);

          // get current page again
          $currentPage = $this->_navigation->getPageByID($row['CIID']);
          $timingState = '';
          $format = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'ci');
          $time = '';
          if (strtotime($currentPage->getStartDate()) > strtotime($now)) {
            $timingState = 'future';
            $time = date($format, strtotime($currentPage->getStartDate()));
          } else if (strtotime($currentPage->getEndDate()) < strtotime($now)) {
            $timingState = 'expired';
            $time = date($format, strtotime($currentPage->getEndDate()));
          }
          $type = ContentItem::getTypeShortname($currentPage->getContentTypeId());

          $linkPageID = $row['CType'] == 90 ? $row["FK_CIID"] : $row["CIID"];

          $disabled[$row["FK_SID"]][] = array(
            'ci_ditem_label'              => parseOutput(implode(' | ', $titles)),
            'ci_ditem_link'               => "index.php?action=content&amp;site=".$row["FK_SID"]."&amp;page=".$linkPageID,
            'ci_ditem_timing_state'       => $timingState,
            'ci_ditem_timing_state_label' => sprintf($_LANG["ci_disabled_pages_timing_{$timingState}_label"], $time),
            'ci_ditem_ctype'              => $type,
            'ci_ditem_ctype_label'        => $_LANG["m_nv_ctype_label_{$type}"],
          );
        }
      }
      $this->db->free_result($result);

      $ciDisabledPages = '';
      foreach ($disabled as $site => $pages)
      {
        $this->tpl->load_tpl('cms_index_dp', 'modules/ModuleCmsindex_disabled_pages_timing.tpl');
        $this->tpl->parse_loop('cms_index_dp', $pages, 'disabled_items');
        $ciDisabledPages .= $this->tpl->parsereturn('cms_index_dp', array_merge(array (
          'ci_site_label' => parseOutput(ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($site)))
        ), $_LANG2['ci']));
      }

      if (!$ciDisabledPages) {
        $this->setMessage(Message::createFailure($_LANG['ci_message_no_timing_pages_found']));
        return $this->_parseNoLinksFound();
      }

      return $ciDisabledPages;
    }

    /*
     * Parses the disabled pages and returns the parsed content as a string
     * @return string - the parsed disabled pages area
     */
    private function _parseDisabledPages()
    {
      global $_LANG, $_LANG2;

      $disabledItems = array();
      // selects all disabled contentitems with enabled direct parents
      $sql = "SELECT FK_SID, CIID, CTitle, CIIdentifier, CType, FK_CIID, CTree "
            ."FROM {$this->table_prefix}contentitem "
            ."WHERE CDisabled=1 "
            ."AND FK_CIID NOT IN ( "
            ."    SELECT CIID "
            ."    FROM {$this->table_prefix}contentitem "
            ."    WHERE CDisabled=1 "
            .") "
            ."ORDER BY FK_SID ASC,CIIdentifier ASC";
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result))
      {
        if ($this->_user->AvailableSite($row["FK_SID"])
           && $this->_user->AvailablePath($row["CIIdentifier"], $row["FK_SID"], $row["CTree"]))
        {
          // direct parent of page is enabled, but if page has any disabled ancestors
          // do not show it, and continue with next page
          $page = $this->_navigation->getPageByID($row['CIID']);
          $disabledAbove = false;
          while ($page = $page->getParent())
          {
            if ($page->isDisabled()) {
              $disabledAbove = true;
              break;
            }
          }
          if ($disabledAbove) {
            continue;
          }

          $page = $this->_navigation->getPageByID($row['CIID']);
          $titles = array();
          do {
            $titles[] = $page->getTitle();
          }
          while ($page = $page->getParent());
          // remove last title (Portal-Home) and change order
          array_pop($titles);
          $titles = array_reverse($titles);

          $linkPageID = $row['CType'] == 90 ? $row["FK_CIID"] : $row["CIID"];
          $currentPage = $this->_navigation->getPageByID($row['CIID']);
          $type = ContentItem::getTypeShortname($currentPage->getContentTypeId());
          $disabledItems[$row["FK_SID"]][] = array(
            'ci_ditem_label'       => parseOutput(implode(' | ', $titles)),
            'ci_ditem_link'        => "index.php?action=content&amp;site=".$row["FK_SID"]."&amp;page=".$linkPageID,
            'ci_ditem_ctype'       => $type,
            'ci_ditem_ctype_label' => $_LANG["m_nv_ctype_label_{$type}"]
          );
        }
      }
      $this->db->free_result($result);

      $ciDisabledPages = '';
      foreach ($disabledItems as $site => $pages)
      {
        $this->tpl->load_tpl('cms_index_dp', 'modules/ModuleCmsindex_disabled_pages.tpl');
        $this->tpl->parse_loop('cms_index_dp', $pages, 'disabled_items');
        $ciDisabledPages .= $this->tpl->parsereturn('cms_index_dp', array_merge(array (
          'ci_site_label' => parseOutput(ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($site)))
        ), $_LANG2['ci']));
      }

      if (!$ciDisabledPages) {
        $this->setMessage(Message::createFailure($_LANG['ci_message_no_disabled_pages_found']));
        return $this->_parseNoLinksFound();
      }

      return $ciDisabledPages;
    }

    /**
     * Should be called if no entries where found for one of the 4 sections
     * containing disabled pages, timing disabled pages, broken internal links
     * or broken links inside texts
     *
     * NOTE: set the module message before calling this method
     */
    private function _parseNoLinksFound()
    {
      $this->tpl->load_tpl('ci_broken_links_not_items', 'modules/ModuleCmsindex_no_broken_pages_or_links.tpl');
      return $this->tpl->parsereturn('ci_broken_links_not_items', $this->_getMessageTemplateArray('ci'));
    }

    /**
     * Returns the message of currently used disk space if feature is activated
     * with $_CONFIG['ci_disk_space_usage'].
     *
     * @return string
     *         the message, empty string if feature is not activated
     */
    private function _getDiskSpaceUsageMessage()
    {
      global $_LANG;

      $msg = '';
      if (!ConfigHelper::get('ci_disk_space_usage')) { return $msg; }

      $disk = $this->getDiskSpaceHelper();
      $used = formatFileSize($disk->getUsedSpace());
      $limit = formatFileSize($disk->getLimit());
      $free = formatFileSize($disk->getFreeSpace());

      if ($disk->getLimit()) {
        if ($disk->isExceeded()) {
          $msg = sprintf($_LANG['ci_disk_space_usage_exceeded'], $used, $limit, $free);
        }
        else {
          $msg = sprintf($_LANG['ci_disk_space_usage'], $used, $limit, $free);
        }
      }
      else {
        $msg = sprintf($_LANG['ci_disk_space_usage_no_limit'], $used);
      }

      return $msg;
    }
  }


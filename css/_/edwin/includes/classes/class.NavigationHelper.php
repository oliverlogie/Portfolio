<?php

/**
 * Class providing navigation handling specific to frontend or backend.
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class NavigationHelper
{
  /**
   * Gets the fallback site ID.
   *
   * At the backend this returns null because on the backend there is not always
   * some site active (i.e. Cmsindex).
   *
   * @return integer|null
   */
  public static function getFallbackSiteID()
  {
    return null;
  }

  /**
   * Gets the current site and page by looking at the client request.
   *
   * At the backend we look at $_REQUEST['site'] and $_REQUEST['page']
   * to determine which site and page path the client requested.
   * If there is a parameter $_REQUEST['action'] that begins with 'mod_' then
   * the page is ignored (because in modules the parameter 'page' doesn't
   * specify pages but module items).
   *
   * @param int $currentSiteID
   *        Is set to the ID of the requested site.
   * @param int|string|null &$currentPage
   *        Is set to the path of the requested page.
   */
  public static function getCurrentSiteAndPageFromClientRequest(&$currentSiteID, &$currentPage)
  {
    $currentSiteID = 0;
    $currentPage = null;

    if (isset($_REQUEST['site'])) {
      $currentSiteID = (int)$_REQUEST['site'];
    }

    if (isset($_REQUEST['action']) && mb_substr($_REQUEST['action'], 0, 4) == 'mod_') {
      return;
    }

    if (isset($_REQUEST['page'])) {
      $currentPage = (int)$_REQUEST['page'];
    }
  }
}


<?php

/**
 * Object handling availability of share functionality of contentitems.
 *
 * $LastChangedDate: 2014-03-14 10:48:28 +0100 (Fr, 14 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CFunctionShare extends AbstractCFunction
{
  public function getShortname()
  {
    return 'share';
  }

  public function isActive()
  {
    if (in_array('share', $this->_modules)) {
      return true;
    }
    else {
      return false;
    }
  }

  public function isAvailableOnPage(NavigationPage $page)
  {
    $available = false;
    $siteId = $page->getSite()->getID();

    if (   $this->isActive()
        && $this->_isAvailableForContentTypeOnSite($page->getContentTypeId(), $siteId)
        && $this->_isAvailableForPagePathOnSite($page->getDirectPath(), $siteId)
    ) {
      $available = true;
    }

    return $available;
  }

  public function isAvailableForUser(User $user, NavigationSite $site)
  {
    $available = false;
    $permitted = $user->AvailableModule('share', $site->getID());

    if ($this->isActive() && $permitted) {
      $available = true;
    }

    return $available;
  }

  /**
   * Checks if the function is available on given page path of site and
   * contenttype. Note, that this method ignores user permissions.
   *
   * @param int $siteId
   * @param string $path
   * @param int $contentType
   *
   * @return bool
   */
  public function isAvailableOnSiteForPagePathAndContentType($siteId, $path, $contentType)
  {
    $available = false;

    if (   $this->isActive()
        && $this->_isAvailableForContentTypeOnSite($contentType, $siteId)
        && $this->_isAvailableForPagePathOnSite($path, $siteId)
    ) {
      $available = true;
    }

    return $available;
  }

  /**
   * @return bool
   */
  private function _isAvailableForContentTypeOnSite($contentType, $siteId)
  {
    $excluded = ConfigHelper::get('m_share_contenttype_excluded');
    $excluded = isset($excluded[$siteId]) ? $excluded[$siteId] : $excluded[0];

    if (in_array($contentType, $excluded)) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * @return bool
   */
  private function _isAvailableForPagePathOnSite($path, $siteId)
  {
    $paths = ConfigHelper::get('m_share_from_page');

    if (isset($paths[$siteId])) {
      return ConfigHelper::isPageOnPath($path, $paths[$siteId]);
    }
    else {
      return true;
    }
  }
}

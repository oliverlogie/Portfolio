<?php

/**
 * Objects of CFunctionMobileSwitch class handle the function to switch
 * on the mobile mode of content pages.
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mrz 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2013 Q2E GmbH
 */
class CFunctionMobileSwitch extends AbstractCFunction
{
  /**
   * @var string
   */
  const MOBILE_SWITCH_LIGHT_ON = 'on';

  /**
   * @var string
   */
  const MOBILE_SWITCH_LIGHT_OFF = 'off';

  /**
   * @var string
   */
  const MOBILE_SWITCH_LIGHT_ABOVE_OFF = 'above_off';

  /**
   * @var string
   */
  const MOBILE_SWITCH_LIGHT_DISABLED = 'disabled';

  public function getShortname()
  {
    return 'mobileswitch';
  }

  public function isActive()
  {
    if (in_array($this->getShortname(), $this->_modules)) {
      return true;
    }
    else {
      return false;
    }
  }

  public function isAvailableOnPage(NavigationPage $page)
  {
    return true;
  }

  public function isAvailableForUser(User $user, NavigationSite $site)
  {
    $available = false;

    $active = $this->isActive();
    $permitted = $user->AvailableModule($this->getShortname(), $site->getID());

    if ($active && $permitted) {
      $available = true;
    }

    return $available;
  }

  /**
   * Gets the mobile switch link.
   *
   * @param int $siteId
   *        The current site id.
   * @param int $currentPageId
   *        The current active logical level.
   * @param int $pageId
   *        The target page id / the page to get the mobile switch link of.
   * @param int $resultPage
   *        The current result page of the active logical level.
   * @param string $mobileSwitchLight
   *               On off the constants of FunctionMobileSwitch::MOBILE_SWITCH_LIGHT_*
   * @return string
   */
  public function getLinkOfLogicalLevel($siteId, $currentPageId, $pageId, $resultPage, $mobileSwitchLight)
  {
    $mobileSwitchLink = '';

    if ($mobileSwitchLight == CFunctionMobileSwitch::MOBILE_SWITCH_LIGHT_DISABLED) {
      $mobileSwitchLink = '#';
    }
    else {
      $mobileSwitchLink = "index.php?action=content&amp;site=$siteId&amp;page=$currentPageId&amp;offset=$resultPage&amp;changeMobileSwitchID=$pageId&amp;changeMobileSwitchTo=";
      if ($mobileSwitchLight == self::MOBILE_SWITCH_LIGHT_ON
         || $mobileSwitchLight == self::MOBILE_SWITCH_LIGHT_ABOVE_OFF) {
        $mobileSwitchLink .= NavigationPage::MOBILE_SWITCH_OFF;
      }
      else {
        $mobileSwitchLink .= NavigationPage::MOBILE_SWITCH_ON;
      }
    }

    return $mobileSwitchLink;
  }
}

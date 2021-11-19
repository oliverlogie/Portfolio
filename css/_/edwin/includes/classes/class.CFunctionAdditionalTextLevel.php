<?php

/**
 * Objects of CFunctionAdditionalTextLevel class handle the 'additionaltextlevel'
 * core function / module features.
 *
 * The 'additionaltextlevel' feature allows defining certain level items, which
 * allows the user to provide an additional text for contentitems inside this
 * level.
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CFunctionAdditionalTextLevel extends AbstractCFunction
{
  public function getShortname()
  {
    return 'additionaltextlevel';
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
    $active = $this->isActive();
    $isAvailableOnLevel = false;
    $available = false;

    $config = ConfigHelper::get('m_additionaltextlevel_level', '',
        $page->getSite()->getID());
    $isAvailableOnLevel = in_array($page->getRealLevel(), $config);

    if ($active && $isAvailableOnLevel && $page->isLevel()) {
      $available = true;
    }

    return $available;
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
}

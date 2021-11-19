<?php

/**
 * Objects of CFunctionTaglevel class handle the 'taglevel' core function /
 * module features.
 *
 * The 'taglevel' feature allows defining certain level items as taggable, which
 * allows the user to tag all contentitems inside this level. For taggable
 * levels a filter navigation is created in the frontend.
 *
 * $LastChangedDate: 2014-03-14 10:48:28 +0100 (Fr, 14 Mrz 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CFunctionTaglevel extends AbstractCFunction
{
  public function getShortname()
  {
    return 'taglevel';
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
    $available = false;

    $config = ConfigHelper::get('m_taglevel_contenttypes');
    $isTaggable = in_array($page->getContentTypeId(), $config);
    if ($isTaggable) {
      foreach ($page->getAllChildren() as $p) {
        if ($p->isLevel()) {
          $isTaggable = false;
          break;
        }
      }
    }

    if ($active && $isTaggable) {
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

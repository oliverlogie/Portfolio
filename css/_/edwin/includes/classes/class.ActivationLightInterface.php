<?php

/**
 * Activation light interface constants collection
 *
 * $LastChangedDate: 2014-12-10 15:29:30 +0100 (Mi, 10 Dez 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2014 Q2E GmbH
 */
interface ActivationLightInterface
{
  /**
   * Describes the red activation light of an element, indicating that the
   * element itself is disabled.
   *
   * @var string
   */
  const RED = 'red';

  /**
   * Describes the yellow activation light of an element, indicating that the
   * item itself is enabled but a parent element is disabled.
   *
   * @var string
   */
  const YELLOW = 'yellow';

  /**
   * Describes the green activation light of an element, indicating that the
   * element itself is enabled and all parent elements above are also enabled.
   *
   * @var string
   */
  const GREEN = 'green';
} 
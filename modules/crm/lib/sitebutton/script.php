<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Crm\UI\Webpack;

/**
 * Class Script
 * @package Bitrix\Crm\SiteButton
 */
class Script
{
	/**
	 * Remove cache.
	 *
	 * @param Button $button Button instance.
	 * @return void
	 */
	public static function removeCache(Button $button)
	{
		Webpack\Button::instance($button->getId())->delete();
	}

	/**
	 * Generate cache.
	 *
	 * @param Button $button Button instance.
	 * @return bool
	 */
	public static function saveCache(Button $button)
	{
		return Webpack\Button::rebuild($button->getId());
	}

	/**
	 * Get embedded script.
	 *
	 * @param Button $button Button instance.
	 * @return string
	 */
	public static function getScript(Button $button)
	{
		if (!$button->getId())
		{
			return '';
		}

		return Webpack\Button::instance($button->getId())->getEmbeddedScript();
	}
}

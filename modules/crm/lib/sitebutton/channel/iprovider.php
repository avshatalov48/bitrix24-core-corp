<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton\Channel;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Interface Provider.
 *
 * @package Bitrix\Crm\SiteButton\Channel
 */
interface iProvider
{
	/**
	 * Return true if it can be used.
	 *
	 * @return bool
	 */
	public static function canUse();

	/**
	 * Get presets.
	 *
	 * @return array
	 */
	public static function getPresets();

	/**
	 * Get list.
	 *
	 * @return array
	 */
	public static function getList();

	/**
	 * Get widgets.
	 *
	 * @param string $id Channel ID
	 * @param bool $removeCopyright Remove copyright
	 * @param string|null $lang Language ID
	 * @return array
	 */
	public static function getWidgets($id, $removeCopyright = true, $lang = null);

	/**
	 * Get resources.
	 *
	 * @return array
	 */
	public static function getResources();

	/**
	 * Get edit path.
	 *
	 * @return array
	 */
	public static function getPathEdit();

	/**
	 * Get add path.
	 * @return string
	 */
	public static function getPathAdd();

	/**
	 * Get list path.
	 *
	 * @return string
	 */
	public static function getPathList();

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public static function getName();

	/**
	 * Get type.
	 *
	 * @return string
	 */
	public static function getType();
}

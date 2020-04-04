<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main\Config\Option;

/**
 * Class Settings
 *
 * @package Bitrix\Crm\Tracking
 */
class Settings
{
	/**
	 * Get attribution window.
	 *
	 * @return int
	 */
	public static function getAttrWindow()
	{
		return (int) Option::get('crm', '~crm_tracking_attr_window', 28);
	}

	/**
	 * Set attribution window.
	 *
	 * @param int $value Value.
	 * @return void
	 */
	public static function setAttrWindow($value)
	{
		$value = (int) $value;
		$value = $value > 0 ? $value : 1;
		$value = $value <= 180 ? $value : 180;
		Option::set('crm', '~crm_tracking_attr_window', $value);
	}

	/**
	 * Is attribution window offline.
	 *
	 * @return bool
	 */
	public static function isAttrWindowOffline()
	{
		return Option::get('crm', '~crm_tracking_attr_window_offline', 'Y') === 'Y';
	}

	/**
	 * Set attribution window.
	 *
	 * @param bool $mode Mode.
	 * @return void
	 */
	public static function setAttrWindowOffline($mode)
	{
		$mode = (bool) $mode;
		$value = $mode ? 'Y' : 'N';
		Option::set('crm', '~crm_tracking_attr_window_offline', $value);
	}

	/**
	 * Is attribution window offline.
	 *
	 * @return bool
	 */
	public static function isSocialRefDomainUsed()
	{
		return Option::get('crm', '~crm_tracking_soc_ref_domain', 'Y') === 'Y';
	}

	/**
	 * Set attribution window.
	 *
	 * @param bool $mode Mode.
	 * @return void
	 */
	public static function setSocialRefDomain($mode)
	{
		$mode = (bool) $mode;
		$value = $mode ? 'Y' : 'N';
		Option::set('crm', '~crm_tracking_soc_ref_domain', $value);
	}
}
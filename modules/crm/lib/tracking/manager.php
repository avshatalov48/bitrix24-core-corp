<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main\Config\Option;
use Bitrix\Crm\Communication;
use Bitrix\Main\Loader;

/**
 * Class Manager
 *
 * @package Bitrix\Crm\Tracking
 */
class Manager
{
	/**
	 * Return true if tracking is accessible.
	 *
	 * @return bool
	 */
	public static function isAccessible()
	{
		return true;
	}

	/**
	 * Return true if ad tracking is accessible.
	 *
	 * @return bool
	 */
	public static function isAdAccessible()
	{
		if (!Loader::includeModule('seo'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Return true if ad tracking is accessible.
	 *
	 * @return bool
	 */
	public static function isAdUpdateAccessible()
	{
		if (!self::isAdAccessible())
		{
			return false;
		}

		return false;
	}

	/**
	 * Return true if tracking configured.
	 *
	 * @return bool
	 */
	public static function isConfigured()
	{
		$optionName = '~tracking_configured';
		if (Option::get('crm', $optionName, 'N') === 'Y')
		{
			return true;
		}

		if (empty(Provider::getReadySources()))
		{
			return false;
		}

		Option::set('crm', $optionName, 'Y');
		return true;
	}

	/**
	 * Return true if calltracking configured.
	 *
	 * @return bool
	 */
	public static function isCallTrackingConfigured()
	{
		return Provider::hasSourcesWithFilledPool(Communication\Type::PHONE);
	}

	/**
	 * Return true if calltracking configured.
	 *
	 * @return bool
	 */
	public static function getCallTrackingConfigUrl()
	{
		return '/crm/tracking/channel/call/';
	}
}
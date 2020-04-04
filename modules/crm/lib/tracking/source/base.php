<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Source;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Bitrix\Crm\Tracking\Source
 */
class Base
{
	const Ga = 'google';
	const Fb = 'fb';
	const Vk = 'vk';
	const Ya = 'yandex';
	const Ig = 'instagram';

	/**
	 * Get name.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function getNameByCode($code)
	{
		$code = $code === 'organic' ? 'another' : $code;
		return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_NAME_' . strtoupper($code)) ?: $code;
	}

	/**
	 * Get short name.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function getShortNameByCode($code)
	{
		$code = $code === 'organic' ? 'another' : $code;
		return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_SHORT_NAME_' . strtoupper($code)) ?: self::getNameByCode($code);
	}

	/**
	 * Get description.
	 *
	 * @param string|null $code Code.
	 * @param string|null $name Name.
	 * @return string
	 */
	public static function getDescriptionByCode($code = null, $name = null)
	{
		if ($code === 'organic')
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_DESC_ANOTHER');
		}

		$name = $name ?: static::getNameByCode($code);
		if ($code)
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_ADS_DESC', ['%name%' => $name]);
		}

		return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_TRAFFIC_DESC', ['%name%' => $name]);
	}
}
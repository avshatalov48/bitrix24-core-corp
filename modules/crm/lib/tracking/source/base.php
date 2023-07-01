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
	const Vkads = 'vkads';
	const Ya = 'yandex';
	const Ig = 'instagram';
	const Organic = 'organic';
	const Other = 'other';
	const Sender = 'sender-mail';
	const OneC = '1c';

	/** @var string $code Code. */
	protected $code;

	/**
	 * Get name.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function getNameByCode($code)
	{
		$code = $code === self::Organic ? self::Other : $code;
		if ($code === self::Ga)
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_NAME_'.mb_strtoupper($code) . '_MSGVER_1');
		}
		else
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_NAME_'.mb_strtoupper($code)) ?: $code;
		}
	}

	/**
	 * Get short name.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function getShortNameByCode($code)
	{
		$code = $code === self::Organic ? self::Other : $code;
		return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_SHORT_NAME_'.mb_strtoupper($code)) ?: self::getNameByCode($code);
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
		if ($code === self::Organic)
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_DESC_OTHER');
		}
		if ($code === self::Sender)
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_DESC_SENDER-MAIL');
		}

		$name = $name ?: static::getNameByCode($code);
		if ($code === self::OneC)
		{
			return $name;
		}
		if ($code)
		{
			return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_ADS_DESC', ['%name%' => $name]);
		}

		return Loc::getMessage('CRM_TRACKING_SOURCE_BASE_TRAFFIC_DESC', ['%name%' => $name]);
	}


	/**
	 * Get code.
	 *
	 * @return string|null
	 */
	public function getCode()
	{
		return $this->code;
	}
}

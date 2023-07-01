<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Source;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

Loc::loadMessages(__FILE__);

/**
 * Class Factory
 *
 * @package Bitrix\Crm\Tracking\Source
 */
final class Factory
{
	/**
	 * Create source instance.
	 *
	 * @param string $code Code.
	 * @param string|null $value Value.
	 * @return Base
	 * @throws NotImplementedException
	 */
	public static function create($code, $value = null)
	{
		$class = self::getClass($code);
		if (!$class)
		{
			throw new NotImplementedException("Source with code `$code` not implemented.");
		}

		return new $class($value);
	}

	/**
	 * Return true if source is known by code.
	 *
	 * @param string $code Code.
	 * @return null|string
	 */
	public static function isKnown($code)
	{
		return !empty(self::getClass($code));
	}

	/**
	 * Get class.
	 *
	 * @param string $code Code.
	 * @return null|string
	 */
	public static function getClass($code)
	{
		$class = null;
		switch ($code)
		{
			case Base::Ga:
				$class = Service\Google\Source::class;
				break;
			case Base::Fb:
				$class = Service\Facebook\Source::class;
				break;
			case Base::Vkads:
			case Base::Vk:
				//$class = Vk::class;
				break;
			case Base::Ya:
				//$class = Yandex::class;
				break;
			case Base::Ig:
				$class = Service\Instagram\Source::class;
				break;
		}

		return $class;
	}

	/**
	 * Get list of codes.
	 *
	 * @return array
	 */
	public static function getCodes()
	{
		return [
			Base::Ga,
			Base::Fb,
			Base::Vk,
			Base::Ya,
			Base::Ig,
		];
	}

	/**
	 * Get list of names.
	 *
	 * @return array
	 */
	public static function getNames()
	{
		$list = [];
		foreach (self::getCodes() as $code)
		{
			$list[$code] = Base::getNameByCode($code);
		}

		return $list;
	}
}

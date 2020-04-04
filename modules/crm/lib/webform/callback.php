<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

class Callback
{
	protected static $phoneNumbers = null;

	/**
	 * Can use callbacks.
	 *
	 * @return bool
	 */
	public static function canUse()
	{
		return Loader::includeModule('voximplant');
	}

	/**
	 * Return false if there is no phone number.
	 *
	 * @return bool
	 */
	public static function hasPhoneNumbers()
	{
		return count(self::getPhoneNumbers()) > 0;
	}

	/**
	 * Send call event.
	 *
	 * @param array $eventParameters Event parameters
	 * @return bool
	 */
	public static function sendCallEvent($eventParameters)
	{
		if (!self::hasPhoneNumbers())
		{
			return false;
		}

		$callEvent = new Event(
			'crm',
			'OnCrmCallbackFormSubmitted',
			array($eventParameters)
		);

		EventManager::getInstance()->send($callEvent);
		return false;
	}

	/**
	 * Get callback phone number list.
	 *
	 * @return array
	 */
	public static function getPhoneNumbers()
	{
		if(!self::canUse())
		{
			return array();
		}

		if (self::$phoneNumbers === null)
		{
			$list = array();
			$numbers = \CVoxImplantConfig::GetCallbackNumbers();
			foreach ($numbers as $numberCode => $numberName)
			{
				if (!$numberCode)
				{
					continue;
				}

				$list[] = array(
					'CODE' => $numberCode,
					'NAME' => $numberName ? $numberName : $numberCode,
				);
			}

			self::$phoneNumbers = $list;
		}

		return self::$phoneNumbers;
	}
}
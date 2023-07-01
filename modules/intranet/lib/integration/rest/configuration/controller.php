<?php

namespace Bitrix\Intranet\Integration\Rest\Configuration;

use Bitrix\Intranet\Integration\Rest\Configuration\Entity;
use Bitrix\Main\Event;

class Controller
{
	const ENTITY_INTRANET_SETTING = 'INTRANET_SETTINGS';
	private static $entityList = [
		self::ENTITY_INTRANET_SETTING => 1000
	];
	private static $stepList = [
		Entity\Theme::ENTITY_INTRANET_THEME
	];

	/**
	 * @return array of entity
	 */
	public static function getEntityList()
	{
		return static::$entityList;
	}

	/**
	 * check can work with current step
	 * @param Event $event
	 *
	 * @return bool
	 */
	protected static function check(Event $event)
	{
		$code = $event->getParameter('CODE');
		if(!isset(static::$entityList[$code]) || !static::$entityList[$code])
		{
			return false;
		}

		return true;
	}

	/**
	 * @param Event $event
	 *
	 * @return array export result
	 * @return null for skip no access step
	 */
	public static function onExport(Event $event)
	{
		$result = null;

		if(static::check($event))
		{
			$params = $event->getParameters();
			switch (static::$stepList[$params['STEP']])
			{
				case Entity\Theme::ENTITY_INTRANET_THEME:
					$result = Entity\Theme::export($params);
					break;
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 *
	 * @return array import result
	 * @return null for skip no access step
	 */
	public static function onImport(Event $event)
	{
		$result = null;

		if(static::check($event))
		{
			$params = $event->getParameters();
			if(!empty($params['CONTENT']['DATA']['TYPE']))
			{
				switch ($params['CONTENT']['DATA']['TYPE'])
				{
					case Entity\Theme::ENTITY_INTRANET_THEME:
						$result = Entity\Theme::import($params);
						break;
				}
			}
		}

		return $result;
	}


}
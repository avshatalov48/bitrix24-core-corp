<?php

namespace Bitrix\Market\Integration\Intranet;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Market\Tag\Manager;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Intranet
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'intranet';

	/**
	 * Works with event onAfterChangeLeftMenuPreset.
	 *
	 * @param Event $event
	 */
	public static function onAfterChangeLeftMenuPreset(Event $event)
	{
		$param = $event->getParameters();
		if (!empty($param['VALUE']))
		{
			Manager::save(
				static::MODULE_ID,
				$param['SITE_ID'] . '|left_menu_preset',
				$param['VALUE']
			);
		}
	}

	/**
	 * Return all intranets tags.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return static::getPresetsTag();
	}

	private static function getPresetsTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$res = \CSite::getList();
			while ($item = $res->fetch())
			{
				$value = Option::getRealValue(static::MODULE_ID, 'left_menu_preset');
				if ($value !== null)
				{
					$result[] = [
						'MODULE_ID' => static::MODULE_ID,
						'CODE' => $item['SITE_ID'] . '|left_menu_preset',
						'VALUE' => $value,
					];
				}
			}
		}

		return $result;
	}
}

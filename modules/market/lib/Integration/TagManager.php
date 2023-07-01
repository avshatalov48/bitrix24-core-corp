<?php

namespace Bitrix\Market\Integration;

use \Bitrix\Market\Integration\Rest;
use \Bitrix\Market\Integration\Voximplant;
use \Bitrix\Market\Integration\Intranet;
use \Bitrix\Market\Integration\Landing;
use \Bitrix\Market\Integration\ImOpenLines;
use \Bitrix\Market\Integration\Main;

/**
 * class TagManager
 *
 * @package Bitrix\Market\Integration
 */
class TagManager
{
	private const INTEGRATION_MODULE = [
		'rest' => Rest\TagHandler::class,
		'voximplant' => Voximplant\TagHandler::class,
		'intranet' => Intranet\TagHandler::class,
		'landing' => Landing\TagHandler::class,
		'imopenlines' => ImOpenLines\TagHandler::class,
		'main' => Main\TagHandler::class,
		'bizproc' => BizProc\TagHandler::class,
	];

	/**
	 * Returns TagHandlers class by module.
	 * @param $moduleId
	 * @return string|null
	 */
	public static function getClass($moduleId): ?string
	{
		return static::isExist($moduleId) ? self::INTEGRATION_MODULE[$moduleId] : null;
	}

	/**
	 * Checks exist module integration.
	 * @param $moduleId
	 * @return bool
	 */
	public static function isExist($moduleId): bool
	{
		return !empty(self::INTEGRATION_MODULE[$moduleId]);
	}
}
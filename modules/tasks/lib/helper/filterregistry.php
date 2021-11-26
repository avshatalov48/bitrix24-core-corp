<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Helper;

class FilterRegistry
{

	public const FILTER_GRID = 'GRID';
	public const FILTER_GANTT = 'GANTT';

	/**
	 * @return string[]
	 */
	public static function getList(): array
	{
		return [
			self::FILTER_GRID,
			self::FILTER_GANTT,
		];
	}

	/**
	 * @param string $name
	 * @param int $groupId
	 * @return string
	 */
	public static function getId(string $name, int $groupId): string
	{
		$roleId = 4096;
		$typeFilter = 'ADVANCED';
		$presetSelected = 'N';

		return 'TASKS_'.$name.'_ROLE_ID_'.$roleId.'_'.$groupId.'_'.$typeFilter.'_'.$presetSelected;
	}
}
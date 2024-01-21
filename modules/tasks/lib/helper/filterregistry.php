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

	public static function getList(): array
	{
		return [
			self::FILTER_GRID,
			self::FILTER_GANTT,
		];
	}

	public static function getId(string $name, ?int $groupId, string $scope = ''): string
	{
		$roleId = 4096;
		$typeFilter = 'ADVANCED';
		$presetSelected = 'N';
		$scope = empty($scope) ? '' : mb_strtoupper("_{$scope}");
		$name = mb_strtoupper($name);

		return "TASKS_{$name}_ROLE_ID_{$roleId}_{$groupId}_{$typeFilter}_{$presetSelected}{$scope}";
	}
}
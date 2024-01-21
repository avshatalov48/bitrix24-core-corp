<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Enum;

use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Kanban\StagesTable;

final class ViewMode
{
	public const LIST = 'LIST';
	public const KANBAN = 'KANBAN';
	public const PLANNER = 'PLANNER';
	public const DEADLINE = 'DEADLINE';

	/**
	 * @param string $viewMode
	 * @return string
	 * @throws ArgumentException
	 */
	public static function validated(string $viewMode): string
	{
		if (!in_array($viewMode, self::values(), true))
		{
			throw new ArgumentException('Unexpected enum option');
		}

		return $viewMode;
	}

	/**
	 * @return string[]
	 */
	public static function values(): array
	{
		$reflector = new \ReflectionClass(__CLASS__);
		return $reflector->getConstants();
	}

	public static function resolveByWorkMode(?string $workMode): string
	{
		$map = [
			StagesTable::WORK_MODE_GROUP => self::KANBAN,
			StagesTable::WORK_MODE_TIMELINE => self::DEADLINE,
			StagesTable::WORK_MODE_USER => self::PLANNER,
		];

		return $map[$workMode] ?? self::LIST;
	}
}

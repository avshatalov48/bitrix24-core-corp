<?php

namespace Bitrix\Crm\Kanban;

class ViewMode
{
	public const MODE_STAGES = 'STAGES';
	public const MODE_ACTIVITIES = 'ACTIVITIES';

	public static function getDefault(): string
	{
		return self::MODE_STAGES;
	}

	/**
	 * @return string[]
	 */
	public static function getAll(): array
	{
		return [
			self::MODE_STAGES,
			self::MODE_ACTIVITIES,
		];
	}

	public static function normalize(string $mode): string
	{
		if (in_array($mode, self::getAll()))
		{
			return $mode;
		}

		return self::getDefault();
	}
}

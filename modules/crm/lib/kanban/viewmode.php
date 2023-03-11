<?php

namespace Bitrix\Crm\Kanban;

class ViewMode
{
	public const MODE_STAGES = 'STAGES';
	public const MODE_ACTIVITIES = 'ACTIVITIES';
	public const MODE_DEADLINES = 'DEADLINES';

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
			self::MODE_DEADLINES
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

	public static function isDatesBasedView(string $viewMode): bool
	{
		return in_array($viewMode, [self::MODE_DEADLINES, self::MODE_ACTIVITIES]);
	}
}

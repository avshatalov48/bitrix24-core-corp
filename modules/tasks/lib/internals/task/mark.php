<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Localization\Loc;

Loc::getMessage(__FILE__);

abstract class Mark extends Base
{
	public const POSITIVE = 'P';
	public const NEGATIVE = 'N';
	public const NO = '';

	public static function getMarks(): array
	{
		return array_filter(array_values(Mark::getAll()));
	}

	public static function getMessage(string $mark): ?string
	{
		return Loc::getMessage("TASKS_MARK_{$mark}");
	}

	public static function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_MARK_TITLE');
	}
}

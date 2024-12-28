<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Localization\Loc;

Loc::getMessage(__FILE__);

abstract class Priority extends Base
{
	public const LOW = 0;
	public const AVERAGE = 1;
	public const HIGH = 2;

	public static function getMessage(int $priority): ?string
	{
		return Loc::getMessage("TASKS_PRIORITY_{$priority}");
	}

	public static function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_PRIORITY_TITLE');
	}
}
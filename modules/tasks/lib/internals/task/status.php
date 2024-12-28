<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class Status extends Base
{
	public const NEW = 1;
	public const PENDING = 2;
	public const IN_PROGRESS = 3;
	public const SUPPOSEDLY_COMPLETED = 4;
	public const COMPLETED = 5;
	public const DEFERRED = 6;
	public const DECLINED = 7;

	public static function getMessage(int $status): ?string
	{
		return Loc::getMessage("TASKS_STATUS_{$status}");
	}

	public static function getTitle(): ?string
	{
		return Loc::getMessage('TASKS_STATUS_TITLE');
	}
}
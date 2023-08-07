<?php

namespace Bitrix\Tasks\Internals\Task;

use CTasks;
use ReflectionClass;

class Status
{
	public const NEW = CTasks::STATE_NEW;
	public const PENDING = CTasks::STATE_PENDING;
	public const IN_PROGRESS = CTasks::STATE_IN_PROGRESS;
	public const SUPPOSEDLY_COMPLETED = CTasks::STATE_SUPPOSEDLY_COMPLETED;
	public const COMPLETED = CTasks::STATE_COMPLETED;
	public const DEFERRED = CTasks::STATE_DEFERRED;
	public const DECLINED = CTasks::STATE_DECLINED;

	public static function getAll(): array
	{
		$reflection = new ReflectionClass(static::class);
		return $reflection->getConstants();
	}
}
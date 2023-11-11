<?php

namespace Bitrix\Tasks\Internals\Task;

abstract class TimeUnitType extends Base
{
	public const SECOND = 'secs';
	public const MINUTE = 'mins';
	public const HOUR = 'hours';
	public const DAY = 'days';
	public const WEEK = 'weeks';
	public const MONTH = 'monts'; // 5 chars max :)
	public const YEAR = 'years';
}
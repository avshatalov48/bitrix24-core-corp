<?php

namespace Bitrix\Tasks\Internals\Task;

abstract class Mark extends Base
{
	public const POSITIVE = 'P';
	public const NEGATIVE = 'N';
	public const NO = '';

	public static function getMarks(): array
	{
		return array_filter(array_values(Mark::getAll()));
	}
}

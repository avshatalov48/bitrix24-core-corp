<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Time\Enum;

abstract class RepeatType extends Base
{
	public const ENDLESS = 'endless';
	public const DATE = 'date';
	public const TIMES = 'times';
}
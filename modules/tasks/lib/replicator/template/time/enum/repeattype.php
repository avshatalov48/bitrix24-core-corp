<?php

namespace Bitrix\Tasks\Replicator\Template\Time\Enum;

abstract class RepeatType extends Base
{
	public const ENDLESS = 'endless';
	public const DATE = 'date';
	public const TIMES = 'times';
}
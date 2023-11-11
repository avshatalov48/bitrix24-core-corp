<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Time\Enum;

abstract class Period extends Base
{
	public const DAILY = 'daily';
	public const WEEKLY = 'weekly';
	public const MONTHLY = 'monthly';
	public const YEARLY = 'yearly';
}
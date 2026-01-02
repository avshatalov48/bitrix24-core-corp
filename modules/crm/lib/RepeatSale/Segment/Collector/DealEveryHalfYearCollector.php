<?php

namespace Bitrix\Crm\RepeatSale\Segment\Collector;

class DealEveryHalfYearCollector extends BasePeriodCollector
{
	protected function getIntervals(): array
	{
		return ['-6 months', '-1 year'];
	}
}

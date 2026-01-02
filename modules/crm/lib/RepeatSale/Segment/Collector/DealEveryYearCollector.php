<?php

namespace Bitrix\Crm\RepeatSale\Segment\Collector;

final class DealEveryYearCollector extends BasePeriodCollector
{
	protected function getIntervals(): array
	{
		return ['-1 year', '-2 years'];
	}
}

<?php

namespace Bitrix\Crm\RepeatSale\Segment\Collector;

use Bitrix\Crm\RepeatSale\Segment\SystemSegmentCode;
use Bitrix\Crm\Traits\Singleton;

final class Factory
{
	use Singleton;

	public function getCollector(?SystemSegmentCode $segmentCode): ?BaseCollector
	{
		if ($segmentCode === SystemSegmentCode::LOST_CLIENT)
		{
			return LostClientCollector::getInstance();
		}

		if ($segmentCode === SystemSegmentCode::SLEEPING_CLIENT)
		{
			return SleepingClientCollector::getInstance();
		}

		if ($segmentCode === SystemSegmentCode::DEAL_EVERY_YEAR)
		{
			return DealEveryYearCollector::getInstance();
		}

		if ($segmentCode === SystemSegmentCode::DEAL_EVERY_HALF_YEAR)
		{
			return DealEveryHalfYearCollector::getInstance();
		}

		if ($segmentCode === SystemSegmentCode::DEAL_EVERY_MONTH)
		{
			return DealEveryMonthCollector::getInstance();
		}

		return null;
	}
}

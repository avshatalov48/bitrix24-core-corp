<?php

namespace Bitrix\Crm\Kanban\Entity\Deadlines\Stagefilters;

use Bitrix\Crm\Kanban\Entity\Deadlines\DatePeriods;
use Bitrix\Crm\Kanban\Entity\Deadlines\FilterDateMerger;

final class Factory
{
	public static function make(string $entityTypeId): StageFilter
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Quote:
				return self::makeStageFilterNotEx();
			default:
				return new StageFilterEx(new DatePeriods());
		}
	}

	private static function makeStageFilterNotEx(): StageFilterNotEx
	{
		return new StageFilterNotEx(
			new DatePeriods(),
			new FilterDateMerger()
		);
	}
}
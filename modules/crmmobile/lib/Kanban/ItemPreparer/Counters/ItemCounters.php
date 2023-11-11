<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer\Counters;

interface ItemCounters
{
	public const DEFAULT_COUNT_WITH_RECKON_ACTIVITY = 1;

	public function counters(array $item, array $params, ?int $entityAssignedById): CountersResult;

}
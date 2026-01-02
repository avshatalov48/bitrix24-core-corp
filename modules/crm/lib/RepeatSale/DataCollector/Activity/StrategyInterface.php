<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

interface StrategyInterface
{
	public function getType(): ActivityType;
	public function collect(int $entityId, int $limit): array;
}

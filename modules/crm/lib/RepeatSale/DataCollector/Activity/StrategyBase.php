<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

abstract class StrategyBase implements StrategyInterface
{
	public function __construct(
		protected readonly int $entityTypeId,
		protected readonly TextNormalizer $textNormalizer,
		protected readonly ActivityQueryBuilder $queryBuilder
	) {}

	abstract public function getType(): ActivityType;
	abstract public function collect(int $entityId, int $limit): array;

	protected function filterValidData(array $data): array
	{
		return array_values(
			array_filter(
				$data,
				static fn($item) => is_string($item) && !empty(trim($item))
			)
		);
	}
}

<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

use Bitrix\Crm\RepeatSale\DataCollector\CopilotMarkerProviderInterface;

class ActivityDataCollector implements CopilotMarkerProviderInterface
{
	private const DEFAULT_LIMIT = 5;

	/** @var StrategyInterface[] */
	private array $strategies = [];

	public function __construct(
		private readonly int $entityTypeId,
		private readonly StrategyFactory $strategyFactory
	) {
		$this->initializeStrategies();
	}

	public function getMarkers(array $parameters = []): array
	{
		$entityId = (int)($parameters['entityId'] ?? 0);
		if ($entityId <= 0)
		{
			return [];
		}

		$limit = (int)($parameters['limit'] ?? self::DEFAULT_LIMIT);
		$result = [];

		foreach ($this->strategies as $strategy)
		{
			$data = $strategy->collect($entityId, $limit);
			if (!empty($data))
			{
				$result[$strategy->getType()->value] = $data;
			}
		}

		return $result;
	}

	private function initializeStrategies(): void
	{
		$this->strategies = [
			$this->strategyFactory->createCallRecordingStrategy($this->entityTypeId),
			$this->strategyFactory->createCommentsStrategy($this->entityTypeId),
			$this->strategyFactory->createTodosStrategy($this->entityTypeId),
			$this->strategyFactory->createEmailsStrategy($this->entityTypeId),
			$this->strategyFactory->createOpenLinesStrategy($this->entityTypeId),
		];
	}
}

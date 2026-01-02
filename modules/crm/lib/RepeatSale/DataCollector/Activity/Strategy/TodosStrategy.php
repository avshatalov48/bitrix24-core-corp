<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy;

use Bitrix\Crm\Activity\Provider\ToDo\ToDo;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\ActivityType;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\StrategyBase;

class TodosStrategy extends StrategyBase
{
	public function getType(): ActivityType
	{
		return ActivityType::TODOS;
	}

	public function collect(int $entityId, int $limit): array
	{
		$query = $this
			->queryBuilder
			->buildActivityQuery($this->entityTypeId, $entityId, ToDo::PROVIDER_ID, $limit)
		;

		$todosRaw = $query
			->setSelect(['SUBJECT', 'DESCRIPTION'])
			->whereNotNull('DESCRIPTION')
			->fetchAll()
		;
		if (empty($todosRaw))
		{
			return [];
		}

		$todos = [];
		foreach ($todosRaw as $row)
		{
			$subject = $row['SUBJECT'] ?? '';
			$description = $row['DESCRIPTION'] ?? '';
			if (empty($description))
			{
				continue;
			}

			$todoText = $this->formatTodoText($subject, $description);
			$normalizedText = $this
				->textNormalizer
				->normalize($todoText, $this->getType())
			;
			if ($normalizedText !== null)
			{
				$todos[] = $normalizedText;
			}
		}

		return $this->filterValidData($todos);
	}

	private function formatTodoText(string $subject, string $description): string
	{
		if (empty($subject))
		{
			return $description;
		}

		return sprintf('%s: %s', $subject, $description);
	}
}

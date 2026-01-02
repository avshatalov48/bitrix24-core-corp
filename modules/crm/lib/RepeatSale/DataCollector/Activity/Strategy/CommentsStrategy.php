<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy;

use Bitrix\Crm\RepeatSale\DataCollector\Activity\ActivityType;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\StrategyBase;

class CommentsStrategy extends StrategyBase
{
	public function getType(): ActivityType
	{
		return ActivityType::COMMENTS;
	}

	public function collect(int $entityId, int $limit): array
	{
		$query = $this
			->queryBuilder
			->buildCommentsQuery($this->entityTypeId, $entityId, $limit)
		;

		$commentsRaw = $query->fetchAll();
		if (empty($commentsRaw))
		{
			return [];
		}

		$comments = [];
		foreach ($commentsRaw as $row)
		{
			$comment = $row['COMMENT'] ?? '';
			$normalizedText = $this
				->textNormalizer
				->normalize($comment, $this->getType())
			;
			if ($normalizedText !== null)
			{
				$comments[] = $normalizedText;
			}
		}

		return $this->filterValidData($comments);
	}
}

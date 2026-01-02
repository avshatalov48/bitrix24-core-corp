<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity\Strategy;

use Bitrix\Crm\Activity\Provider\Email;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\ActivityType;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\StrategyBase;
use CCrmContentType;

class EmailsStrategy extends StrategyBase
{
	public function getType(): ActivityType
	{
		return ActivityType::EMAILS;
	}

	public function collect(int $entityId, int $limit): array
	{
		$query = $this
			->queryBuilder
			->buildActivityWithBindingQuery($this->entityTypeId, $entityId, Email::getId(), $limit)
		;

		$emailsRaw = $query->fetchAll();
		if (empty($emailsRaw))
		{
			return [];
		}

		$emails = [];
		foreach ($emailsRaw as $row)
		{
			$subject = $row['SUBJECT'] ?? '';
			$description = $row['DESCRIPTION'] ?? '';
			if (empty($description))
			{
				continue;
			}

			$emailText = $this->formatEmailText($subject, $description);
			$normalizedText = $this
				->textNormalizer
				->normalize($emailText, $this->getType(), CCrmContentType::Html)
			;
			if ($normalizedText !== null)
			{
				$emails[] = $normalizedText;
			}
		}

		return $this->filterValidData($emails);
	}

	private function formatEmailText(string $subject, string $description): string
	{
		if (empty($subject))
		{
			return $description;
		}

		return sprintf('%s: %s', $subject, $description);
	}
}

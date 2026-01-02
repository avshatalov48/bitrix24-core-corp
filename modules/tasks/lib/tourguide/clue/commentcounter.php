<?php

namespace Bitrix\Tasks\TourGuide\Clue;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\Bitrix24\Portal;

class CommentCounter
{
	private const SUITABLE_GRID_PREFIXES = [
		'TASKS_GRID',
		'TASKS_GANTT'
	];

	public function shouldShow(int $userId, string $gridId, int $groupId): bool
	{
		if ($groupId > 0)
		{
			return false;
		}

		$isCorrectGrid = false;
		foreach (self::SUITABLE_GRID_PREFIXES as $prefix)
		{
			if (str_starts_with($gridId, $prefix))
			{
				$isCorrectGrid = true;
				break;
			}
		}
		if (!$isCorrectGrid)
		{
			return false;
		}

		$optionValue = \CUserOptions::getOption(
			'ui-tour',
			'view_date_tasks_comment_counter',
			null,
			$userId,
		);

		if (!is_null($optionValue))
		{
			return false;
		}

		$portalCreateDate = (new Portal())->getCreationDateTime();
		$suitablePortalCreationDate = $this->getMinimumSuitablePortalCreationDate();

		return $portalCreateDate?->getTimestamp() > $suitablePortalCreationDate->getTimestamp();
	}

	private function getMinimumSuitablePortalCreationDate(): DateTime
	{
		return new DateTime('2025-02-01', 'Y-m-d');
	}
}
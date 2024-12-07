<?php

namespace Bitrix\Crm\Service\Communication\Search;

use Bitrix\Crm\Integrity\Duplicate;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateCriterion;
use Bitrix\Crm\Integrity\DuplicatePersonCriterion;

class DuplicateFinder
{
	/**
	 * @param DuplicateCriterion[] $criteria
	 * @return array
	 */
	public function getDuplicates(array $criteria): array
	{
		$hasCommunicationCriterion = $this->hasCommunicationCriterion($criteria);
		$found = false;
		$result = [];

		foreach ($criteria as $criterion)
		{
			if (
				($hasCommunicationCriterion || $found)
				&& $this->isPersonCriterionWithoutName($criterion)
			)
			{
				continue;
			}

			$duplicate = $this->getDuplicateByCriterion($criterion);
			if ($duplicate !== null)
			{
				if ($duplicate->getEntityIDs())
				{
					$found = true;
				}

				$result[] = $duplicate;
			}
		}

		return $result;
	}

	private function hasCommunicationCriterion(array $criteria): bool
	{
		foreach ($criteria as $index => $criterion)
		{
			if ($criterion instanceof DuplicateCommunicationCriterion)
			{
				return true;
			}
		}

		return false;
	}

	private function isPersonCriterionWithoutName(DuplicateCriterion $criterion): bool
	{
		return (
			$criterion instanceof DuplicatePersonCriterion
			&& (!$criterion->getSecondName() || !$criterion->getName())
		);
	}

	private function getDuplicateByCriterion(DuplicateCriterion $criterion): ?Duplicate
	{
		$criterion->sortDescendingByEntityTypeId();

		return $criterion->find(\CCrmOwnerType::Undefined, 250);
	}
}

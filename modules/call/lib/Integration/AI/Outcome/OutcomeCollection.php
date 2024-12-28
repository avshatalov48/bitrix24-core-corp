<?php
namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Model\CallOutcomeTable;
use Bitrix\Call\Model\CallOutcomePropertyTable;
use Bitrix\Call\Model\EO_CallOutcome_Collection;


class OutcomeCollection extends EO_CallOutcome_Collection
{
	public static function getOutcomesByCallId(int $callId): ?static
	{
		$outcomeCollection = CallOutcomeTable::query()
			->setSelect(['*'])
			->where('CALL_ID', $callId)
			->setOrder(['ID' => 'DESC'])
			->exec()
			?->fetchCollection()
		;
		if ($outcomeIds = $outcomeCollection->getIdList())
		{
			$properties = CallOutcomePropertyTable::query()
				->setSelect(['*'])
				->whereIn('OUTCOME_ID', $outcomeIds)
				->setOrder(['OUTCOME_ID' => 'ASC', 'ID' => 'ASC'])
				->exec()
			;
			while ($property = $properties->fetchObject())
			{
				foreach ($outcomeCollection as $outcome)
				{
					if ($outcome->getId() == $property->getOutcomeId())
					{
						$outcome->appendProps($property);
					}
				}
			}
		}

		return $outcomeCollection;
	}

	public function getOutcomeByType(string $type): ?Outcome
	{
		foreach ($this as $outcome)
		{
			if ($outcome->getType() == $type)
			{
				return $outcome;
			}
		}
		return null;
	}
}
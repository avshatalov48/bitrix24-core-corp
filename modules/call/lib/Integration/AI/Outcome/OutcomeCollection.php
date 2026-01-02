<?php
namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Model\CallOutcomeTable;
use Bitrix\Call\Model\CallOutcomePropertyTable;
use Bitrix\Call\Model\EO_CallOutcome_Collection;


class OutcomeCollection extends EO_CallOutcome_Collection
{
	public static function getOutcomesByCallId(int $callId, array $outcomeTypes = []): ?static
	{
		$outcomeQuery = CallOutcomeTable::query()
			->setSelect(['*'])
			->where('CALL_ID', $callId)
			->setOrder(['ID' => 'DESC'])
		;
		if ($outcomeTypes)
		{
			$outcomeQuery->whereIn('TYPE', $outcomeTypes);
		}
		$outcomeCollection = $outcomeQuery->exec()->fetchCollection();
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
				$outcomeCollection->getByPrimary($property->getOutcomeId())->appendProps($property);
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
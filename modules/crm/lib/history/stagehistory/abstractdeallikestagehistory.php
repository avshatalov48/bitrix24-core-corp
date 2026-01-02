<?php

namespace Bitrix\Crm\History\StageHistory;

use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/**
 * @internal
 */
abstract class AbstractDealLikeStageHistory extends AbstractStageHistory
{
	final protected function isRelevantOwnerInfoChanged(Difference $diff): bool
	{
		return (
			$diff->isChanged(Item::FIELD_NAME_ASSIGNED)
			|| $diff->isChanged(Item::FIELD_NAME_BEGIN_DATE)
			|| $diff->isChanged(Item::FIELD_NAME_CLOSE_DATE)
		);
	}

	final protected function actualizeOwnerInfo(Difference $diff, EntityObject $entry): void
	{
		$beginDate = $this->getBeginDate($diff);
		$closeDate = $this->getCloseDate($diff);

		$entry->set('START_DATE', $beginDate);
		$entry->set('END_DATE', $closeDate);
		$entry->set('RESPONSIBLE_ID', $this->getAssigned($diff));

		if ($entry->require('TYPE_ID') === HistoryEntryType::CREATION)
		{
			$entry->set('EFFECTIVE_DATE', $beginDate);
		}
		elseif ($entry->require('TYPE_ID') === HistoryEntryType::FINALIZATION)
		{
			$entry->set('EFFECTIVE_DATE', $closeDate);
		}
	}

	final protected function createCreationEntry(Difference $diff, DateTime $now): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->set('TYPE_ID', HistoryEntryType::CREATION);
		$entry->set('EFFECTIVE_DATE', $now);
		$entry->set('STAGE_SEMANTIC_ID', PhaseSemantics::PROCESS);
		$entry->set('IS_LOST', false);

		return $entry;
	}

	final protected function createModificationEntry(Difference $diff, DateTime $now): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->set('TYPE_ID', HistoryEntryType::MODIFICATION);
		$entry->set('EFFECTIVE_DATE', $now);
		$entry->set('STAGE_SEMANTIC_ID', PhaseSemantics::PROCESS);
		$entry->set('IS_LOST', false);

		return $entry;
	}

	final protected function createFinalizationEntry(Difference $diff, DateTime $now, string $semantics): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->set('TYPE_ID', HistoryEntryType::FINALIZATION);
		$entry->set('EFFECTIVE_DATE', $this->getCloseDate($diff));
		$entry->set('STAGE_SEMANTIC_ID', $semantics);
		$entry->set('IS_LOST', PhaseSemantics::isLost($semantics));

		return $entry;
	}

	final protected function createCategoryChangeEntry(Difference $diff, DateTime $now, string $semantics): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->set('TYPE_ID', HistoryEntryType::CATEGORY_CHANGE);
		$entry->set('EFFECTIVE_DATE', $now);
		$entry->set('STAGE_SEMANTIC_ID', $semantics);
		$entry->set('IS_LOST', PhaseSemantics::isLost($semantics));

		return $entry;
	}

	protected function createEntry(Difference $diff, DateTime $now): EntityObject
	{
		$nowDate = Date::createFromTimestamp($now->getTimestamp());

		$beginDate = $this->getBeginDate($diff);
		$closeDate = $this->getCloseDate($diff);

		return $this->getDataClass()::createObject([
			'OWNER_ID' => $diff->getValue(Item::FIELD_NAME_ID),
			'CREATED_TIME' => $now,
			'CREATED_DATE' => $nowDate,
			'START_DATE' => $beginDate,
			'END_DATE' => $closeDate,
			'PERIOD_YEAR' => \CCrmDateTimeHelper::getYear($now),
			'PERIOD_QUARTER' => \CCrmDateTimeHelper::getQuarter($now),
			'PERIOD_MONTH' => \CCrmDateTimeHelper::getMonth($now),
			'START_PERIOD_YEAR' => \CCrmDateTimeHelper::getYear($beginDate),
			'START_PERIOD_QUARTER' => \CCrmDateTimeHelper::getQuarter($beginDate),
			'START_PERIOD_MONTH' => \CCrmDateTimeHelper::getMonth($beginDate),
			'END_PERIOD_YEAR' => \CCrmDateTimeHelper::getYear($closeDate),
			'END_PERIOD_QUARTER' => \CCrmDateTimeHelper::getQuarter($closeDate),
			'END_PERIOD_MONTH' => \CCrmDateTimeHelper::getMonth($closeDate),
			'RESPONSIBLE_ID' => $this->getAssigned($diff),
			'CATEGORY_ID' => $diff->getValue(Item::FIELD_NAME_CATEGORY_ID),
			'STAGE_ID' => $diff->getValue(Item::FIELD_NAME_STAGE_ID),
		]);
	}

	private function getBeginDate(Difference $diff): Date
	{
		$beginDate = $diff->getValue(Item::FIELD_NAME_BEGIN_DATE);
		if (!($beginDate instanceof Date))
		{
			$beginDate = new Date();
		}

		return $beginDate;
	}

	private function getCloseDate(Difference $diff): Date
	{
		$closeDate = $diff->getValue(Item::FIELD_NAME_CLOSE_DATE);
		if (!($closeDate instanceof Date))
		{
			$closeDate = new Date('9999-12-31', 'Y-m-d');
		}

		return $closeDate;
	}
}

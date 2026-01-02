<?php

namespace Bitrix\Crm\History\StageHistory;

use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\History\Entity\EO_LeadStatusHistory;
use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;
use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/**
 * @internal
 *
 * @method EO_LeadStatusHistory[] getListFilteredByPermissions
 */
final class LeadStageHistory extends AbstractStageHistory
{
	/**
	 * @inheritDoc
	 */
	protected function getDataClass(): string
	{
		return LeadStatusHistoryTable::class;
	}

	protected function isRelevantOwnerInfoChanged(Difference $diff): bool
	{
		return $diff->isChanged(Item::FIELD_NAME_ASSIGNED);
	}

	protected function actualizeOwnerInfo(Difference $diff, EntityObject $entry): void
	{
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		if (!($entry instanceof EO_LeadStatusHistory))
		{
			throw new ArgumentTypeException('entry', EO_LeadStatusHistory::class);
		}

		$entry->setResponsibleId($this->getAssigned($diff));
	}

	protected function createCreationEntry(Difference $diff, DateTime $now): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->setTypeId(HistoryEntryType::CREATION);
		$entry->setIsInWork(false);
		$entry->setStatusSemanticId(PhaseSemantics::PROCESS);
		$entry->setIsJunk(false);

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $entry;
	}

	protected function createModificationEntry(Difference $diff, DateTime $now): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->setTypeId(HistoryEntryType::MODIFICATION);
		$entry->setIsInWork(true);
		$entry->setStatusSemanticId(PhaseSemantics::PROCESS);
		$entry->setIsJunk(false);

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $entry;
	}

	protected function createFinalizationEntry(Difference $diff, DateTime $now, string $semantics): EntityObject
	{
		$entry = $this->createEntry($diff, $now);

		$entry->setTypeId(HistoryEntryType::FINALIZATION);
		$entry->setIsInWork(true);
		$entry->setStatusSemanticId($semantics);
		$entry->setIsJunk(PhaseSemantics::isLost($semantics));

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $entry;
	}

	protected function createCategoryChangeEntry(Difference $diff, DateTime $now, string $semantics): EntityObject
	{
		throw new NotSupportedException('Lead dont have categories');
	}

	private function createEntry(Difference $diff, DateTime $now): EO_LeadStatusHistory
	{
		return $this->getDataClass()::createObject([
			'OWNER_ID' => $diff->getValue(Item::FIELD_NAME_ID),
			'CREATED_TIME' => $now,
			'CREATED_DATE' =>  Date::createFromTimestamp($now->getTimestamp()),
			'PERIOD_YEAR' => \CCrmDateTimeHelper::getYear($now),
			'PERIOD_QUARTER' => \CCrmDateTimeHelper::getQuarter($now),
			'PERIOD_MONTH' => \CCrmDateTimeHelper::getMonth($now),
			'RESPONSIBLE_ID' => $this->getAssigned($diff),
			'STATUS_ID' => $diff->getValue(Item::FIELD_NAME_STAGE_ID),
		]);
	}
}

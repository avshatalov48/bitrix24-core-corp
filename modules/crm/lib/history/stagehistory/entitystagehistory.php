<?php

namespace Bitrix\Crm\History\StageHistory;

use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\History\Entity\EntityStageHistoryTable;
use Bitrix\Crm\History\Entity\EO_EntityStageHistory;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\DateTime;

/**
 * @internal
 *
 * @method EO_EntityStageHistory[] getListFilteredByPermissions
 */
final class EntityStageHistory extends AbstractDealLikeStageHistory
{
	/**
	 * @inheritDoc
	 */
	protected function getDataClass(): string
	{
		return EntityStageHistoryTable::class;
	}

	protected function getOwnerFilter(int $ownerId): ConditionTree
	{
		return parent::getOwnerFilter($ownerId)
			->where('OWNER_TYPE_ID', $this->getEntityTypeId())
		;
	}

	protected function createEntry(Difference $diff, DateTime $now): EntityObject
	{
		return parent::createEntry($diff, $now)
			->set('OWNER_TYPE_ID', $this->getEntityTypeId())
		;
	}
}

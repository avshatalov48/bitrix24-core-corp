<?php

namespace Bitrix\Crm\History\StageHistoryWithSupposed;

use Bitrix\Crm\History\Entity\EntityStageHistoryWithSupposedTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Type\DateTime;

final class EntityStageHistoryWithSupposed extends AbstractStageHistoryWithSupposed
{
	public function __construct(
		TransitionsCalculator $calculator,
		private readonly int $entityTypeId,
	)
	{
		parent::__construct($calculator);
	}

	/**
	 * @inheritDoc
	 */
	protected function getDataClass(): string
	{
		return EntityStageHistoryWithSupposedTable::class;
	}

	protected function buildEntry(int $ownerId, TransitionDto $dto, DateTime $now): EntityObject
	{
		/** @var EntityObject $entry */
		$entry = $this->getDataClass()::createObject([
			'OWNER_TYPE_ID' => $this->entityTypeId,
			'OWNER_ID' => $ownerId,
			'CREATED_TIME' => $now,
			'CREATED_DATE' => $now,
			'CATEGORY_ID' => $dto->categoryId,
			'STAGE_SEMANTIC_ID' => $dto->semantics,
			'STAGE_ID' => $dto->stageId,
			'IS_LOST' => PhaseSemantics::isLost($dto->semantics),
			'IS_SUPPOSED' => $dto->isSupposed,
			'LAST_UPDATE_DATE' => $now,
			'CLOSE_DATE' => $this->getNullCloseDate(),
			'SPENT_TIME' => null,
		]);

		return $entry;
	}

	protected function getOwnerFilter(int $ownerId): ConditionTree
	{
		return parent::getOwnerFilter($ownerId)
			->where('OWNER_TYPE_ID', $this->entityTypeId)
		;
	}

	/**
	 * @inheritDoc
	 */
	protected function getOwnerFilterSql(int $ownerId): array
	{
		return ['OWNER_TYPE_ID = ?i AND OWNER_ID = ?i', [$this->entityTypeId, $ownerId]];
	}
}

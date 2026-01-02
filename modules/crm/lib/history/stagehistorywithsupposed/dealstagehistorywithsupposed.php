<?php

namespace Bitrix\Crm\History\StageHistoryWithSupposed;

use Bitrix\Crm\History\Entity\DealStageHistoryWithSupposedTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\DateTime;

final class DealStageHistoryWithSupposed extends AbstractStageHistoryWithSupposed
{
	/**
	 * @inheritDoc
	 */
	protected function getDataClass(): string
	{
		return DealStageHistoryWithSupposedTable::class;
	}

	protected function buildEntry(int $ownerId, TransitionDto $dto, DateTime $now): EntityObject
	{
		/** @var EntityObject $entry */
		$entry = $this->getDataClass()::createObject([
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
}

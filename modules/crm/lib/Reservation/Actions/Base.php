<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Crm;
use Bitrix\Crm\Reservation\QuantityCheckerTrait;
use Bitrix\Crm\Reservation\AvailabilityServicesCheckerTrait;

abstract class Base extends Crm\Service\Operation\Action
{
	use QuantityCheckerTrait;
	use AvailabilityServicesCheckerTrait;

	protected function isFinalStage(Crm\Item $item): bool
	{
		$semanticId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $item->getStageSemanticId()
				: null
		;

		return $semanticId && Crm\PhaseSemantics::isFinal($semanticId);
	}

	protected function isSuccessStage(Crm\Item $item): bool
	{
		$semanticId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $item->getStageSemanticId()
				: null
		;

		return $semanticId && Crm\PhaseSemantics::isSuccess($semanticId);
	}

	protected function isMovedToFinalStage(Crm\Item $item, Crm\Item $itemBeforeSave): bool
	{
		$previousStageId = $itemBeforeSave->remindActual(Crm\Item::FIELD_NAME_STAGE_ID);
		$currentStageId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_ID)
				? $item->getStageId()
				: null
		;

		return Crm\Comparer\ComparerBase::isMovedToFinalStage($item->getEntityTypeId(), $previousStageId, $currentStageId);
	}

	protected function isMovedToSuccessfulStage(Crm\Item $item): bool
	{
		if (!$item->hasField(Crm\Item::FIELD_NAME_STAGE_ID))
		{
			return false;
		}

		$previousStageId = $item->remindActual(Crm\Item::FIELD_NAME_STAGE_ID);
		$currentStageId = $item->getStageId();

		return Crm\Comparer\ComparerBase::isMovedToSuccessfulStage(
			$item->getEntityTypeId(),
			$previousStageId,
			$currentStageId
		);
	}
}

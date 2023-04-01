<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\Timeline\Interfaces\FinalSummaryController;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Result;
use Bitrix\Crm\Comparer\ComparerBase;

class CreateFinalSummaryTimelineHistoryItem extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();

		/**
		 * Quit if the timeline controller does not support final summary items
		 */
		$timelineController = TimelineManager::resolveController([
			'ASSOCIATED_ENTITY_TYPE_ID' => $item->getEntityTypeId()
		]);
		if (!$timelineController instanceof FinalSummaryController)
		{
			return $result;
		}

		/**
		 * Quit if the item's factory does not support payment and stages
		 */
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (
			!(
				$factory
				&& $factory->isPaymentsEnabled()
				&& $factory->isStagesEnabled()
			)
		)
		{
			return $result;
		}

		$itemBefore = $this->getItemBeforeSave();
		if (!$itemBefore)
		{
			return $result;
		}

		/**
		 * Categories
		 */
		$previousCategoryId = $itemBefore->remindActual(Item::FIELD_NAME_CATEGORY_ID);
		$currentCategoryId = $item->getCategoryId();
		$isCategoryChanged = (
			!is_null($previousCategoryId)
			&& !is_null($currentCategoryId)
			&& $previousCategoryId !== $currentCategoryId
		);

		/**
		 * Quit if the category has been changed
		 */
		if ($isCategoryChanged)
		{
			return $result;
		}

		/**
		 * Stages
		 */
		$previousStageId =
			$itemBefore->hasField(Item::FIELD_NAME_STAGE_ID)
				?  $itemBefore->remindActual(Item::FIELD_NAME_STAGE_ID)
				: null
		;
		$currentStageId =
			$item->hasField(Item::FIELD_NAME_STAGE_ID)
				? $item->getStageId()
				: null
		;
		$isStageChanged = (
			$previousStageId
			&& $currentStageId
			&& $previousStageId !== $currentStageId
		);

		/**
		 * Quit if the stage has not been changed
		 */
		if (!$isStageChanged)
		{
			return $result;
		}

		/**
		 * Ignore processed stage semantic
		 */
		$currentStageSemanticId = ComparerBase::getStageSemantics($item->getEntityTypeId(), $currentStageId);
		if ($currentStageSemanticId === PhaseSemantics::PROCESS)
		{
			return $result;
		}

		$timelineController->onCreateFinalSummary($item);

		return $result;
	}
}

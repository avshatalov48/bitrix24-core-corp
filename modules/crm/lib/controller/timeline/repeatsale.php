<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\ErrorCode as AIErrorCode;
use Bitrix\Crm\Integration\AI\Operation\Scenario;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Segment\SegmentItemChecker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Main\Engine\ActionFilter\Scope;
use CCrmOwnerType;

final class RepeatSale extends Activity
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}

	public function launchCopilotAction(int $activityId, int $ownerTypeId, int $ownerId): void
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return;
		}

		if (!$this->isUpdateEnable($ownerTypeId, $ownerId))
		{
			return;
		}

		if (
			$ownerTypeId === CCrmOwnerType::Deal
			&& AIManager::isAiCallProcessingEnabled()
		)
		{
			if (!Scenario::isEnabledScenario(Scenario::REPEAT_SALE_TIPS_SCENARIO))
			{
				$this->addError(
					AIErrorCode::getAIDisabledError(
						[
							'sliderCode' => Scenario::REPEAT_SALE_TIPS_SCENARIO_SLIDER_CODE,
						]
					)
				);

				return;
			}

			$segmentId = (int)($activity['PROVIDER_PARAMS']['SEGMENT_ID'] ?? 0);
			$segmentItem = SegmentItem::createFromEntity(
				RepeatSaleSegmentController::getInstance()->getById($segmentId, true)
			);
			$checkerResult = SegmentItemChecker::getInstance()
				->setItem($segmentItem)
				->run()
			;
			if (!$checkerResult->isSuccess())
			{
				$this->addError($checkerResult->getError());

				return;
			}

			$result = AIManager::launchFillRepeatSaleTips(
				$activityId,
				Container::getInstance()->getContext()->getUserId(),
				true
			);
			if (!$result->isSuccess())
			{
				$errors = $result->getErrors();
				if ($errors)
				{
					$this->addErrors($result->getErrors());
				}
				else
				{
					$this->addError(AIErrorCode::getAIEngineNotFoundError());
				}

				return;
			}

			// apply timeline
			$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
			if ($activity)
			{
				ActivityController::getInstance()->notifyTimelinesAboutActivityUpdate(
					$activity,
					(int)$activity['RESPONSIBLE_ID'],
					true
				);
			}

			return;
		}

		$this->addError(AIErrorCode::getAIEngineNotFoundError());
	}
}

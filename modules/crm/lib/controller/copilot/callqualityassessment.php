<?php

namespace Bitrix\Crm\Controller\Copilot;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Timeline\trait\ActivityLoader;
use Bitrix\Crm\Controller\Timeline\trait\ActivityPermissionsChecker;
use Bitrix\Crm\Copilot\AiQualityAssessment\Controller\AiQualityAssessmentController;
use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentTable;
use Bitrix\Crm\Copilot\AiQualityAssessment\ViewModeEnum;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItemChecker;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\CopilotLauncher;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\AI\ErrorCode as AIErrorCode;
use Bitrix\Crm\Integration\AI\Operation\OperationState;
use Bitrix\Crm\Integration\AI\Operation\Scenario;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\AutoWire\ExactParameter;

final class CallQualityAssessment extends Base
{
	use ActivityLoader;
	use ActivityPermissionsChecker;

	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}

	// region actions
	public function getAction(
		int $activityId,
		int $assessmentSettingsId,
		ItemIdentifier $itemIdentifier
	): ?array
	{
		$activity = $this->loadActivity(
			$activityId,
			$itemIdentifier->getEntityTypeId(),
			$itemIdentifier->getEntityId()
		);
		if (!$activity)
		{
			return null;
		}

		$quality = AiQualityAssessmentController::getInstance()->getList([
			'filter' => [
				'ACTIVITY_ID' => $activityId,
				'ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
				'ASSESSMENT_SETTING_ID' => $assessmentSettingsId,
			],
			'limit' => 1,
		])->current();

		if ($quality)
		{
			return (new \Bitrix\Crm\Controller\Timeline\AI())->getCopilotCallQualityAction(
				$activityId,
				$itemIdentifier->getEntityTypeId(),
				$itemIdentifier->getEntityId(),
				$quality->getJobId(),
			);
		}

		$callAssessment = CopilotCallAssessmentController::getInstance()->getById($assessmentSettingsId);
		if ($callAssessment === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$operationState = new OperationState(
			$activityId,
			$itemIdentifier->getEntityTypeId(),
			$itemIdentifier->getEntityId()
		);

		$prevAssessmentAvg = AiQualityAssessmentController::getInstance()->getPrevAvgAssessmentValue($activity['RESPONSIBLE_ID']);

		return [
			'callQuality' => [
				'ID' => null,
				'CREATED_AT' => null,
				'ASSESSMENT_SETTING_ID' => $callAssessment->getId(),
				'ASSESSMENT_SETTINGS_STATUS' => $callAssessment->getStatus(),
				'ASSESSMENT' => null,
				'ASSESSMENT_AVG' => $prevAssessmentAvg,
				'PREV_ASSESSMENT_AVG' => $prevAssessmentAvg,
				'IS_PROMPT_CHANGED' => false,
				'USE_IN_RATING' => false,
				'PROMPT' => $callAssessment->getPrompt(),
				'ACTUAL_PROMPT' => $callAssessment->getPrompt(),
				'PROMPT_UPDATED_AT' => $callAssessment->getUpdatedAt(),
				'TITLE' => $callAssessment->getTitle(),
				'SUMMARY' => null,
				'RECOMMENDATIONS' => null,
			],
			'viewMode' => (
				$operationState->isCallScoringScenarioPending()
					? ViewModeEnum::pending->value
					: ViewModeEnum::usedNotAssessmentScript->value
			),
		];
	}

	public function doAssessmentAction(
		int $activityId,
		int $assessmentSettingsId,
		ItemIdentifier $itemIdentifier
	): ?Result
	{
		$activity = $this->loadActivity(
			$activityId,
			$itemIdentifier->getEntityTypeId(),
			$itemIdentifier->getEntityId()
		);
		if (!$activity)
		{
			return null;
		}

		if (!$this->isUpdateEnable($itemIdentifier->getEntityTypeId(), $itemIdentifier->getEntityId()))
		{
			return null;
		}

		if (
			AIManager::isAiCallProcessingEnabled()
			&& in_array($itemIdentifier->getEntityTypeId(), AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
		)
		{
			if (!AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment))
			{
				$this->addError(AIErrorCode::getAIDisabledError(['sliderCode' => Scenario::CALL_SCORING_SCENARIO_SLIDER_CODE]));

				return null;
			}

			$entity = CopilotCallAssessmentController::getInstance()->getById($assessmentSettingsId);
			if ($entity === null)
			{
				$this->addError(ErrorCode::getNotFoundError());

				return null;
			}

			$item = CallAssessmentItem::createFromEntity($entity);
			$checkerResult = CallAssessmentItemChecker::getInstance()
				->setItem($item)
				->run()
			;

			if (!$checkerResult->isSuccess())
			{
				$this->addError($checkerResult->getError());

				return null;
			}

			$scenario = Scenario::CALL_SCORING_SCENARIO;

			$result = (new CopilotLauncher(
				$activityId,
				$this->getCurrentUserId(),
				$scenario,
			))->runCallScoringScenario($assessmentSettingsId);

			if ($result?->isSuccess() === false)
			{
				$this->addErrors($result?->getErrors());
			}

			return $result;
		}

		$this->addError(AIErrorCode::getAIEngineNotFoundError());

		return null;
	}
	// endregion

	private function getCurrentUserId(): int
	{
		return $this->getCurrentUser()?->getId() ?? Container::getInstance()->getContext()->getUserId();
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				ItemIdentifier::class,
				'itemIdentifier',
				static function($className, $ownerTypeId, $ownerId) {
					return new ItemIdentifier($ownerTypeId, $ownerId);
				}
			),
		];
	}
}

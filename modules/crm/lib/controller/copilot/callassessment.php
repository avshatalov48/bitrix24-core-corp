<?php

namespace Bitrix\Crm\Controller\Copilot;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\PromptsChecker;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Integration\AI;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class CallAssessment extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}

	// region actions
	public function saveAction(
		CallAssessmentItem $callAssessmentItem,
		?int $id = null,
		?string $eventId = null
	): Result
	{
		if (!Container::getInstance()->getUserPermissions()->canEditCopilotCallAssessmentSettings())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return new Result();
		}

		$userId = $this->getCurrentUserId();

		$controller = CopilotCallAssessmentController::getInstance();

		if ($id)
		{
			$entity = $controller->getById($id);
			if ($entity === null)
			{
				$this->addError(ErrorCode::getNotFoundError());

				return new Result();
			}

			$isNeedExtractCriteria = PromptsChecker::isChanged(
				$entity->getPrompt(),
				$callAssessmentItem->getPrompt()
			);

			if ($isNeedExtractCriteria)
			{
				$extractScoringCriteriaResult = JobRepository::getInstance()->getExtractScoringCriteriaResultById($id);
				if ($extractScoringCriteriaResult?->isPending())
				{
					$this->addError(
						new Error(
							'Operation by extract scoring criteria is already in progress',
							AI\ErrorCode::OPERATION_IS_PENDING
						)
					);

					return new Result();
				}

				if (!$this->isLaunchExtractCriteriaOperationEnabled($userId))
				{
					$this->addError(
						new Error(
							'Operation in not available',
							AI\ErrorCode::AI_NOT_AVAILABLE
						)
					);

					return new Result();
				}
			}

			$callAssessmentItem
				->setGist($isNeedExtractCriteria ? null : $entity->getGist())
				->setJobId($entity->getJobId())
				->setStatus($isNeedExtractCriteria ? QueueTable::EXECUTION_STATUS_PENDING : $entity->getStatus())
				->setCode($entity->getCode())
			;

			$context = clone(Container::getInstance()->getContext());
			$context->setEventId($eventId);
			$result = $controller->update($id, $callAssessmentItem, $context);
		}
		else
		{
			if (!$this->isLaunchExtractCriteriaOperationEnabled($userId))
			{
				$this->addError(AI\ErrorCode::getAINotAvailableError());

				return new Result();
			}

			$isNeedExtractCriteria = true;

			$callAssessmentItem->setStatus(QueueTable::EXECUTION_STATUS_PENDING);
			$result = $controller->add($callAssessmentItem);
		}

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return $result;
		}

		if ($isNeedExtractCriteria)
		{
			$launchOperationResult = AIManager::launchExtractScoringCriteria(
				$result->getId(),
				$callAssessmentItem->getPrompt(),
				$userId
			);
			if (!$launchOperationResult->isSuccess())
			{
				$this->addErrors($launchOperationResult->getErrors());

				return $result;
			}
		}

		return $result;
	}

	public function activeAction(int $id, string $isEnabled): Result
	{
		if (!Container::getInstance()->getUserPermissions()->canEditCopilotCallAssessmentSettings())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return new Result();
		}

		$entity = CopilotCallAssessmentController::getInstance()->getById($id);
		if ($entity === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return new Result();
		}

		$isEnabledValue = ($isEnabled === 'Y');

		$callAssessmentItem = (CallAssessmentItem::createFromEntity($entity))
			->setIsEnabled($isEnabledValue)
		;

		$result = CopilotCallAssessmentController::getInstance()->update($id, $callAssessmentItem);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result;
	}

	public function deleteAction(int $id): Result
	{
		if (!Container::getInstance()->getUserPermissions()->canEditCopilotCallAssessmentSettings())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return new Result();
		}

		$result = CopilotCallAssessmentController::getInstance()->delete($id);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result;
	}
	// endregion

	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				CallAssessmentItem::class,
				'callAssessmentItem',
				static function($className, $data) {
					return CallAssessmentItem::createFromArray($data);
				}
			),
		];
	}

	private function getCurrentUserId(): int
	{
		return $this->getCurrentUser()?->getId() ?? Container::getInstance()->getContext()->getUserId();
	}

	private function isLaunchExtractCriteriaOperationEnabled(int $userId): bool
	{
		return AIManager::isAiCallProcessingEnabled()
			&& AIManager::isAILicenceAccepted($userId)
			&& AIManager::isEnabledInGlobalSettings(AI\Enum\GlobalSetting::CallAssessment)
		;
	}
}

<?php

namespace Bitrix\Rpa;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Rpa\Model\Item;
use Bitrix\Rpa\Model\ItemHistory;
use Bitrix\Rpa\Model\ItemSortTable;
use Bitrix\Rpa\Model\Timeline;
use Bitrix\Rpa\UserField\UserFieldCollection;

abstract class Command
{
	public const ERROR_CODE_ITEM_MOVE_PERMISSION = 'RPA_ITEM_MOVE_ACCESS_DENIED';
	public const ERROR_CODE_ITEM_MODIFY_PERMISSION = 'RPA_ITEM_MODIFY_ACCESS_DENIED';
	public const ERROR_CODE_WRONG_STAGE = 'RPA_ITEM_WRONG_STAGE';
	public const ERROR_CODE_MANDATORY_FIELD_EMPTY = 'RPA_MANDATORY_FIELD_EMPTY';
	public const ERROR_CODE_ITEM_USER_HAS_TASKS = 'RPA_ITEM_USER_HAS_TASKS';
	public const ERROR_CODE_ITEM_TASKS_NOT_COMPLETED = 'RPA_ITEM_TASKS_NOT_COMPLETED';
	public const ERROR_CODE_ITEM_DELETE_PERMISSION = 'RPA_ITEM_DELETE_PERMISSION';

	protected $item;
	protected $userId;
	protected $scope;
	protected $taskId;
	protected $isCheckAccessEnabled = true;
	protected $isCheckTasksEnabled = true;
	protected $isCheckStageEnabled = true;
	protected $isCheckFieldsEnabled = true;
	protected $isAutomationEnabled = true;
	protected $isSaveToHistoryEnabled = true;
	protected $isTerminated = false;
	protected $pullEventId = '';

	public function __construct(Item $item, array $data = [])
	{
		$this->item = $item;
		$userId = null;
		if(isset($data['userId']))
		{
			$userId = (int) $data['userId'];
		}
		if(!$userId)
		{
			$userId = $this->getDefaultUserId();
		}
		if(isset($data['scope']))
		{
			$this->scope = (string) $data['scope'];
		}
		if(isset($data['taskId']))
		{
			$this->taskId = (int) $data['taskId'];
		}
		$this->userId = $userId;
	}

	public function setUserId(int $userId): Command
	{
		$this->userId = $userId;

		return $this;
	}

	public function getUserId(): ?int
	{
		return $this->userId;
	}

	public function setScope(string $scope): Command
	{
		$this->scope = $scope;

		return $this;
	}

	public function getScope(): ?string
	{
		return $this->scope;
	}

	public function setPullEventId(string $eventId): Command
	{
		$this->pullEventId = $eventId;

		return $this;
	}

	public function setTaskId(int $taskId): Command
	{
		$this->taskId = $taskId;

		return $this;
	}

	public function getTaskId(): ?int
	{
		return $this->taskId;
	}

	public function getItem(): Item
	{
		return $this->item;
	}

	protected function getDefaultUserId(): int
	{
		return Driver::getInstance()->getUserId();
	}

	public function run(): Result
	{
		$result = new Result();

		if($result->isSuccess() && $this->isCheckAccessEnabled())
		{
			$checkAccessResult = $this->checkAccess();
			if(!$checkAccessResult->isSuccess())
			{
				$result->addErrors($checkAccessResult->getErrors());
			}
		}

		if($result->isSuccess() && $this->isCheckStageEnabled())
		{
			$checkStageResult = $this->checkStage();
			if(!$checkStageResult->isSuccess())
			{
				$result->addErrors($checkStageResult->getErrors());
			}
		}

		if($result->isSuccess() && $this->isCheckFieldsEnabled())
		{
			$checkFieldsResult = $this->checkFields();
			if(!$checkFieldsResult->isSuccess())
			{
				$result->addErrors($checkFieldsResult->getErrors());
			}
		}

		if($result->isSuccess() && $this->isCheckTasksEnabled())
		{
			$checkTasksResult = $this->checkTasks();
			if(!$checkTasksResult->isSuccess())
			{
				$result->addErrors($checkTasksResult->getErrors());
			}
		}

		// some task could intercept execution - in this case there is not need to proceed
		if($this->isTerminated())
		{
			$this->item->fill();
			return $result;
		}

		if($result->isSuccess() && $this->isSaveToHistoryEnabled())
		{
			$historyRecord = ItemHistory::createByItem($this->item);
			if($this->scope)
			{
				$historyRecord->setScope($this->scope);
			}
		}

		$isDropSort = $this->item->isChanged('STAGE_ID');

		if($result->isSuccess())
		{
			$result = $this->save();
		}

		if($result->isSuccess() && $isDropSort)
		{
			ItemSortTable::removeForItem($this->item->getType()->getId(), $this->item->getId());
		}

		if($result->isSuccess() && $this->isSaveToHistoryEnabled())
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$historyResult = $this->saveToHistory($historyRecord);
			if($historyResult->isSuccess())
			{
				$timeline = $historyRecord->createTimelineRecord();
				if($timeline)
				{
					$timelineResult = $timeline->save();
					if($timelineResult->isSuccess())
					{
						$this->sendTimelinePullEvent($timeline);
					}
					else
					{
						$result->addErrors($timelineResult->getErrors());
					}
				}
			}
			else
			{
				$result->addErrors($historyResult->getErrors());
			}
		}

		if($result->isSuccess())
		{
			$this->sendPullEvent();
		}

		if($result->isSuccess() && $this->isAutomationEnabled())
		{
			$eventType = $this->getItemEntityEventName('OnAfterUpdate');
			$eventId = EventManager::getInstance()->addEventHandler(
				$this->item->sysGetEntity()->getModule(),
				$eventType,
				[$this, 'updateItemFromUpdateEvent']
			);
			$automationResult = $this->runAutomation();
			if(!$automationResult->isSuccess())
			{
				$result->addErrors($automationResult->getErrors());
			}
			EventManager::getInstance()->removeEventHandler(Driver::MODULE_ID, $eventType, $eventId);
		}

		return $result;
	}

	protected function getItemEntityEventName(string $eventName): string
	{
		return $this->item->sysGetEntity()->getNamespace() . $this->item->sysGetEntity()->getName() . '::' . $eventName;
	}

	public function updateItemFromUpdateEvent(Event $event): void
	{
		$item = $event->getParameter('object');
		if($item instanceof Item && $item->getId() === $this->item->getId())
		{
			$this->item = $item;
		}
	}

	abstract protected function save(): Result;

	public function disableAllChecks(): Command
	{
		return $this->disableCheckAccess()
			->disableCheckTasks()
			->disableCheckStage()
			->disableCheckFields();
	}

	public function enableAutomation(): Command
	{
		$this->isAutomationEnabled = true;

		return $this;
	}

	public function disableAutomation(): Command
	{
		$this->isAutomationEnabled = false;

		return $this;
	}

	public function isAutomationEnabled(): bool
	{
		return $this->isAutomationEnabled;
	}

	public function enableCheckAccess(): Command
	{
		$this->isCheckAccessEnabled = true;

		return $this;
	}

	public function disableCheckAccess(): Command
	{
		$this->isCheckAccessEnabled = false;

		return $this;
	}

	public function isCheckAccessEnabled(): bool
	{
		return $this->isCheckAccessEnabled;
	}

	public function enableCheckTasks(): Command
	{
		$this->isCheckTasksEnabled = true;

		return $this;
	}

	public function disableCheckTasks(): Command
	{
		$this->isCheckTasksEnabled = false;

		return $this;
	}

	public function isCheckTasksEnabled(): bool
	{
		return $this->isCheckTasksEnabled;
	}

	public function enableCheckStage(): Command
	{
		$this->isCheckStageEnabled = true;

		return $this;
	}

	public function disableCheckStage(): Command
	{
		$this->isCheckStageEnabled = false;

		return $this;
	}

	public function isCheckStageEnabled(): bool
	{
		return $this->isCheckStageEnabled;
	}

	public function enableCheckFields(): Command
	{
		$this->isCheckFieldsEnabled = true;

		return $this;
	}

	public function disableCheckFields(): Command
	{
		$this->isCheckFieldsEnabled = false;

		return $this;
	}

	public function isCheckFieldsEnabled(): bool
	{
		return $this->isCheckFieldsEnabled;
	}

	public function enableSaveToHistory(): Command
	{
		$this->isSaveToHistoryEnabled = true;

		return $this;
	}

	public function disableSaveToHistory(): Command
	{
		$this->isSaveToHistoryEnabled = false;

		return $this;
	}

	public function isSaveToHistoryEnabled(): bool
	{
		return $this->isSaveToHistoryEnabled;
	}

	protected function runAutomation(): Result
	{
		return new Result();
	}

	abstract public function checkStage(): Result;

	abstract public function checkAccess(): Result;

	public function checkFields(): Result
	{
		$result = new Result();

		if(
			$this->item->isChanged('STAGE_ID')
			&& $this->item->getMovedBy() === $this->userId
			&& $this->item->getPreviousStageId() === $this->item->getStageId()
		)
		{
			//skip checking fields if moving back
			return $result;
		}
		$currentStageId = $this->item->remindActualStageId() ?? $this->item->getStageId();
		if (!$currentStageId && $this->item->getId() <= 0)
		{
			$firstStage = $this->item->getType()->getFirstStage();
			if ($firstStage)
			{
				$currentStageId = $firstStage->getId();
			}
		}

		if (!$currentStageId)
		{
			return $result->addError(new Error('Stage is not found'));
		}

		$stage = $this->item->getType()->getStage($currentStageId);
		/** @noinspection NullPointerExceptionInspection */
		$userFields = $stage->getUserFieldCollection();
		$this->resetNotEditableFields($userFields);
		if($this->item->isChanged('STAGE_ID'))
		{
			$result = $this->checkRequiredFields($userFields);
		}

		return $result;
	}

	public function checkRequiredFields(UserFieldCollection $userFieldCollection): Result
	{
		$result = new Result();

		foreach($userFieldCollection as $userField)
		{
			if($userField->isMandatory() && $this->item->isEmptyUserFieldValue($userField->getName()))
			{
				$result->addError($this->getMandatoryFieldError($userField->getTitle()));
			}
		}

		return $result;
	}

	public function resetNotEditableFields(UserFieldCollection $userFieldCollection): Result
	{
		$resetedFields = [];

		foreach($userFieldCollection as $userField)
		{
			if(!$userField->isEditable() && $this->item->isValueChanged($userField->getName()))
			{
				$this->item->reset($userField->getName());
				$resetedFields[] = $userField->getName();
			}
		}

		return (new Result())->setData(['resetedFields' => $resetedFields]);
	}

	public function checkTasks(): Result
	{
		return new Result();
	}

	protected function getMandatoryFieldError(string $fieldName): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_MANDATORY_FIELD_IS_EMPTY', [
			'#FIELD#' => $fieldName,
		]), static::ERROR_CODE_MANDATORY_FIELD_EMPTY, [
			'fieldName' => $fieldName,
		]);
	}

	protected function getModifyAccessDeniedError(string $stageName): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_ITEM_MODIFY_PERMISSION', [
			'#STAGE#' => $stageName,
		]), static::ERROR_CODE_ITEM_MODIFY_PERMISSION);
	}

	protected function getMoveAccessDeniedError(string $stageName): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_ITEM_MOVE_PERMISSION', [
			'#STAGE#' => $stageName,
		]), static::ERROR_CODE_ITEM_MOVE_PERMISSION);
	}

	protected function getWrongStageError(string $stageName): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_ITEM_WRONG_STAGE', [
			'#STAGE#' => $stageName,
		]), static::ERROR_CODE_WRONG_STAGE);
	}

	protected function getDeletePermissionDeniedError(string $name): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_ITEM_DELETE_PERMISSION_DENIED', [
			'#NAME#' => $name,
		]), static::ERROR_CODE_ITEM_DELETE_PERMISSION);
	}

	public function saveToHistory(ItemHistory $historyRecord): Result
	{
		$historyRecord->setItemId($this->item->getId());
		$historyRecord->setUserId($this->userId);
		if($this->getTaskId() > 0)
		{
			$historyRecord->setTaskId($this->getTaskId());
		}
		$historyRecord->fillEmptyValues();
		return $historyRecord->save();
	}

	abstract protected function sendPullEvent(): bool;

	protected function sendTimelinePullEvent(Timeline $timeline): bool
	{
		return Driver::getInstance()->getPullManager()->sendTimelineAddEvent($timeline);
	}

	protected function terminate(): Command
	{
		$this->isTerminated = true;

		return $this;
	}

	public function isTerminated(): bool
	{
		return ($this->isTerminated === true);
	}
}
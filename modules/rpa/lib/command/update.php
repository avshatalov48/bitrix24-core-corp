<?php

namespace Bitrix\Rpa\Command;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rpa\Command;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Integration\Bizproc;
use Bitrix\Rpa\Model\ItemHistory;
use Bitrix\Rpa\Model\PrototypeItem;

class Update extends Command
{
	protected $isItemChanged;
	protected $historyItem;

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Driver::getInstance()->getUserPermissions($this->userId);
		if($this->item->isChanged('STAGE_ID'))
		{
			if(!$userPermissions->canMoveItem($this->item, $this->item->remindActualStageId(), $this->item->getStageId()))
			{
				$actualStage = $this->item->getType()->getStage($this->item->remindActualStageId());
				if($actualStage)
				{
					$actualStageName = $actualStage->getName();
				}
				else
				{
					$actualStageName = $this->item->remindActualStageId();
				}
				$result->addError($this->getMoveAccessDeniedError($actualStageName));
			}
			elseif(!$userPermissions->canModifyItemsInStage($this->item->getType(), $this->item->remindActualStageId()))
			{
				$userFields = $this->item->getType()->getUserFieldCollection();
				foreach($userFields as $userField)
				{
					if($this->item->isChanged($userField->getName()))
					{
						$result->addError($this->getModifyAccessDeniedError($this->item->getStage()->getName()));
						break;
					}
				}
			}
		}
		elseif(!$userPermissions->canModifyItemsInStage($this->item->getType(), $this->item->getStageId()))
		{
			$result->addError($this->getModifyAccessDeniedError($this->item->getStage()->getName()));
		}

		return $result;
	}

	public function checkStage(): Result
	{
		$result = new Result();

		if($this->item->isChanged('STAGE_ID'))
		{
			$userPermissions = Driver::getInstance()->getUserPermissions($this->userId);
			if(!$userPermissions->canMoveItem($this->item, $this->item->remindActualStageId(), $this->item->getStageId()))
			{
				$result->addError($this->getWrongStageError($this->item->getStage()->getName()));
			}
		}

		return $result;
	}

	protected function save(): Result
	{
		if(!$this->isItemChanged())
		{
			return new Result();
		}
		if($this->userId > 0)
		{
			$this->item->setUpdatedBy($this->userId);
			if($this->item->isChanged('STAGE_ID'))
			{
				$this->item->setMovedBy($this->userId);
			}
		}
		$this->item->setUpdatedTime(new DateTime());
		if($this->item->isChanged('STAGE_ID'))
		{
			$this->item->setMovedTime(new DateTime());
		}

		if (!$this->isCheckFieldsEnabled())
		{
			/** @var PrototypeItem $dataClass */
			$dataClass = $this->item->sysGetEntity()->getDataClass();
			$dataClass::disableUserFieldsCheck();
		}

		return $this->item->save();
	}

	protected function sendPullEvent(): bool
	{
		if($this->isItemChanged())
		{
			return Driver::getInstance()->getPullManager()->sendItemUpdatedEvent(
				$this->item,
				$this->pullEventId,
				$this->historyItem
			);
		}

		return true;
	}

	protected function isItemChanged(): bool
	{
		if($this->isItemChanged === null)
		{
			$this->isItemChanged = (
				$this->item->isChanged('STAGE_ID') ||
				!empty($this->item->getChangedUserFieldNames())
			);

			if ($this->isItemChanged)
			{
				$this->historyItem = clone $this->item;
			}
		}

		return $this->isItemChanged;
	}

	public function saveToHistory(ItemHistory $historyRecord): Result
	{
		// skip history if item is not changed
		if($this->taskId > 0 || $this->isItemChanged())
		{
			parent::saveToHistory($historyRecord);
		}

		return new Result();
	}

	protected function runAutomation(): Result
	{
		if($this->isItemChanged())
		{
			if($this->pullEventId)
			{
				Driver::getInstance()->getPullManager()->addItemUpdateEventId(
					$this->item->getType()->getId(),
					$this->item->getId(),
					$this->pullEventId
				);
			}
			return Bizproc\Listener::onItemUpdate($this->item, $this->historyItem);
		}

		return new Result();
	}

	public function checkTasks(): Result
	{
		$result = new Result();

		// do not check tasks for update on the same stage.
		if(!$this->item->isChanged('STAGE_ID'))
		{
			return $result;
		}

		if (!Bizproc\Automation\Factory::canUseAutomation())
		{
			return $result;
		}

		$taskManager = Driver::getInstance()->getTaskManager();
		if(!$taskManager)
		{
			return $result;
		}
		$stageChanged = $taskManager->onItemStageUpdate($this->item, $this->item->getStageId(), $this->userId);

		if ($stageChanged)
		{
			$this->terminate();
			return $result;
		}

		$participants = $taskManager->getItemTaskParticipants($this->item);

		// check tasks for actual stage. $this->item->remindActualStageId();
		//if current user has tasks for this item
		if(in_array($this->userId, $participants, true))
		{
			$result->addError(new Error(Loc::getMessage('RPA_COMMAND_ITEM_USER_HAS_TASKS'), static::ERROR_CODE_ITEM_USER_HAS_TASKS));
		}
		//if any user has tasks for this item
		elseif($participants)
		{
			$result->addError(new Error(Loc::getMessage('RPA_COMMAND_ITEM_TASKS_NOT_COMPLETED'), static::ERROR_CODE_ITEM_TASKS_NOT_COMPLETED));
		}

		return $result;
	}
}
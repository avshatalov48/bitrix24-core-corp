<?php

namespace Bitrix\Rpa\Command;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rpa\Command;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Integration\Bizproc;
use Bitrix\Rpa\Model\PrototypeItem;

class Add extends Command
{
	public const ERROR_CODE_FIRST_STAGE_NOT_FOUND = 'RPA_FIRST_STAGE_NOT_FOUND';

	// checking fields turned false by default during creation.
	protected $isCheckFieldsEnabled = false;

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Driver::getInstance()->getUserPermissions($this->userId);
		if(!$userPermissions->canAddItemsToType($this->item->getType()->getId()))
		{
			$result->addError($this->getModifyAccessDeniedError($this->item->getStage()->getName()));
		}

		return $result;
	}

	public function checkStage(): Result
	{
		$result = new Result();

		$firstStage = $this->item->getType()->getFirstStage();
		if(!$firstStage)
		{
			$result->addError($this->getEmptyStageError());
		}
		elseif($this->item->getStageId() !== $firstStage->getId())
		{
			$result->addError($this->getWrongStageError($firstStage->getName()));
		}

		return $result;
	}

	protected function getWrongStageError(string $stageName): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_ADD_ITEM_WRONG_STAGE', [
			'#STAGE#' => $stageName,
		]), static::ERROR_CODE_WRONG_STAGE);
	}

	protected function getEmptyStageError(): Error
	{
		return new Error(Loc::getMessage('RPA_COMMAND_ADD_ITEM_EMPTY_STAGE', [
		]), static::ERROR_CODE_FIRST_STAGE_NOT_FOUND);
	}

	protected function save(): Result
	{
		if (Driver::getInstance()->getBitrix24Manager()->isCreateItemRestricted($this->item->getType()->getId()))
		{
			return ((new Result())->addError(new Error(Loc::getMessage('RPA_LIMIT_CREATE_ITEM_ERROR'))));
		}
		if ($this->userId > 0)
		{
			$this->item->setCreatedBy($this->userId);
			//$this->item->setMovedBy($this->userId);
		}
		$this->item->setCreatedTime(new DateTime());
		//$this->item->setMovedTime(new DateTime());

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
		return Driver::getInstance()->getPullManager()->sendItemAddedEvent($this->item, $this->pullEventId);
	}

	protected function runAutomation(): Result
	{
		if($this->pullEventId)
		{
			Driver::getInstance()->getPullManager()->addItemUpdateEventId($this->item->getType()->getId(), $this->item->getId(), $this->pullEventId);
		}
		return Bizproc\Listener::onItemAdd($this->item);
	}
}
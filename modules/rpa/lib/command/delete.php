<?php

namespace Bitrix\Rpa\Command;

use Bitrix\Main\Result;
use Bitrix\Rpa\Command;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Integration\Bizproc;

class Delete extends Command
{
	protected $historyItem;

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Driver::getInstance()->getUserPermissions($this->userId);
		if(!$userPermissions->canDeleteItem($this->item))
		{
			$result->addError($this->getDeletePermissionDeniedError($this->item->getName()));
		}

		return $result;
	}

	public function checkStage(): Result
	{
		return new Result();
	}

	protected function save(): Result
	{
		$this->historyItem = clone $this->item;

		return $this->item->delete();
	}

	protected function sendPullEvent(): bool
	{
		return Driver::getInstance()->getPullManager()->sendItemDeletedEvent($this->historyItem);
	}

	protected function runAutomation(): Result
	{
		return Bizproc\Listener::onItemDelete($this->historyItem ?? $this->item);
	}

	/**
	 * There is not need to save to history because it will be purged right after deleting element
	 *
	 * @return bool
	 */
	public function isSaveToHistoryEnabled(): bool
	{
		return false;
	}
}
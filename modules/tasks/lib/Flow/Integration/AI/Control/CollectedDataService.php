<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Control;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\DeleteCommand;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\ReplaceCollectedDataCommand;
use Bitrix\Tasks\Flow\Internal\FlowCopilotCollectedDataTable;

class CollectedDataService
{
	public function replace(ReplaceCollectedDataCommand $command): void
	{
		$command->validateAdd();

		$this->update($command);
	}

	public function delete(DeleteCommand $command): void
	{
		$command->validateDelete();

		FlowCopilotCollectedDataTable::delete($command->flowId);
	}

	public function onFlowDeleted(Event $event): EventResult
	{
		$flowId = (int)$event->getParameter('flow');
		if ($flowId <= 0)
		{
			return new EventResult(EventResult::ERROR);
		}

		FlowCopilotCollectedDataTable::delete($flowId);

		return new EventResult(EventResult::SUCCESS);
	}

	protected function update(ReplaceCollectedDataCommand $command): void
	{
		$insertFields = ['FLOW_ID' => $command->flowId,];
		$updateFields = [];

		if (isset($command->status))
		{
			$insertFields['STATUS'] = $command->status->value;
			$updateFields['STATUS'] = $command->status->value;
		}

		if (!empty($command->data))
		{
			$insertFields['DATA'] = Json::encode($command->data, 0);
			$updateFields['DATA'] = Json::encode($command->data, 0);
		}

		$uniqueFields = ['FLOW_ID'];

		if (empty($updateFields))
		{
			return;
		}

		FlowCopilotCollectedDataTable::merge($insertFields, $updateFields, $uniqueFields);
	}
}

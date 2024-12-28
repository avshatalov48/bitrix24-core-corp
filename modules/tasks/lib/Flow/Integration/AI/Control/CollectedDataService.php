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
		$data = Json::encode($command->data, 0);

		$insertFields = [
			'FLOW_ID' => $command->flowId,
			'DATA' => $data,
		];

		$updateFields = ['DATA' => $data];

		$uniqueFields = ['FLOW_ID'];

		FlowCopilotCollectedDataTable::merge($insertFields, $updateFields, $uniqueFields);
	}
}

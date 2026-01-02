<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParticipantTrait;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Internals\Log\LogFacade;

class SendPush
{
	use ConfigTrait;
	use ParticipantTrait;

	public function __invoke(array $fullTaskData, array $sourceTaskData, array $changes): void
	{
		if ($this->config->isSkipPush())
		{
			return;
		}

		$taskId = (int)$fullTaskData['ID'];

		$newParticipants = $this->getParticipants($fullTaskData);
		$oldParticipants = $this->getParticipants($sourceTaskData);
		$participants = array_unique(array_merge($newParticipants, $oldParticipants));
		$removedParticipants = array_unique(array_diff($oldParticipants, $newParticipants));

		$before = [];
		$after = [];

		foreach ($changes as $field => $value)
		{
			$before[$field] = $value['FROM_VALUE'];
			$after[$field] = $value['TO_VALUE'];
		}

		$before['GROUP_ID'] = (int)$sourceTaskData['GROUP_ID'];
		$after['GROUP_ID'] = (int)$fullTaskData['GROUP_ID'];

		$resultService = Container::getInstance()->getResultService();
		$lastResult = $resultService->getLastResult($taskId);
		$isLastResultOpened = $lastResult && $lastResult->isOpen();
		$isResultRequired = $resultService->isResultRequired($taskId);

		$byPassParameters = $this->config->getByPassParameters();

		$params = [
			'TASK_ID' => $taskId,
			'USER_ID' => $this->config->getUserId(),
			'BEFORE' => $before,
			'AFTER' => $after,
			'TS' => time(),
			'event_GUID' => $this->config->getEventGuid(),
			'params' => [
				'HIDE' => (!array_key_exists('HIDE', $byPassParameters) || $byPassParameters['HIDE']),
				'updateCommentExists' => $this->config->getRuntime()->isCommentAdded(),
				'removedParticipants' => array_values($removedParticipants),
			],
			'taskRequireResult' => $isResultRequired ? 'Y' : 'N',
			'taskHasResult' => $lastResult ? 'Y' : 'N',
			'taskHasOpenResult' => $isLastResultOpened ? 'Y' : 'N',
			'updateDate' => strtotime($taskData['CHANGED_DATE'] ?? '') ?: null,
		];

		if (isset($after['STAGE']) || $after['GROUP_ID'] !== $before['GROUP_ID'])
		{
			$stageId = (int)$fullTaskData['STAGE_ID'];
			if ($stageId > 0)
			{
				$params['AFTER']['STAGE_INFO'] = Container::getInstance()->getStageRepository()->getById($stageId)?->toArray();
				$params['AFTER']['STAGE'] = $params['AFTER']['STAGE_INFO']['title'] ?? null;
			}
			else
			{
				$params['AFTER']['STAGE_INFO']['id'] = 0;
			}
		}

		try
		{
			PushService::addEvent($participants, [
				'module_id' => 'tasks',
				'command' => PushCommand::TASK_UPDATED,
				'params' => $params,
			]);
		}
		catch (\Exception $exception)
		{
			LogFacade::logThrowable($exception);
			return;
		}
	}
}

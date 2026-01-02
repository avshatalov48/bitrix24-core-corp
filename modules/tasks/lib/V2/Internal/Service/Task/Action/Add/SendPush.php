<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParticipantTrait;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Integration\SocialNetwork\User;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\ParameterTable;

class SendPush
{
	use ParticipantTrait;
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fields, array $fullTaskData): void
	{
		$currentUserId = $this->getOccurredUserId($this->config->getUserId());

		$mergedFields = array_merge(
			$fullTaskData,
			$fields,
			$this->config->getByPassParameters(),
			[
				'USER_ID' => $currentUserId,
				'URL' => $this->getTaskSliderUrl($fullTaskData, $currentUserId),
			],
		);

		$pushRecipients = $this->getParticipants($fullTaskData);

		try
		{
			$groupId = (int)$mergedFields['GROUP_ID'];
			if ($groupId > 0)
			{
				$pushRecipients = array_unique(
					array_merge(
						$pushRecipients,
						User::getUsersCanPerformOperation($groupId, 'view_all'),
					),
				);
			}

			PushService::addEvent($pushRecipients, [
				'module_id' => 'tasks',
				'command' => PushCommand::TASK_ADDED,
				'params' => $this->prepareAddPullEventParameters($mergedFields),
			]);
		}
		catch (\Exception $exception)
		{
			LogFacade::logThrowable($exception);
		}
	}

	private function prepareAddPullEventParameters(array $mergedFields): array
	{
		$taskId = (int)$mergedFields['ID'];

		return [
			'TASK_ID' => $taskId,
			'AFTER' => $mergedFields,
			'TS' => time(),
			'event_GUID' => $this->config->getEventGuid(),
			'params' => [
				'addCommentExists' => $this->config->getRuntime()->isCommentAdded(),
			],
			'taskRequireResult' => $this->resolveRequireResultForPush($mergedFields),
			'taskHasResult' => 'N',
			'taskHasOpenResult' => 'N',
		];
	}

	private function resolveRequireResultForPush(array $fields): string
	{
		$parameters = $fields['SE_PARAMETER'] ?? $fields['PARAMETER'] ?? [];

		foreach ($parameters as $parameter)
		{
			$code = (int)($parameter['CODE'] ?? 0);
			$value = (string)($parameter['VALUE'] ?? 'N');

			if ($code === ParameterTable::PARAM_RESULT_REQUIRED)
			{
				return $value;
			}
		}

		return 'N';
	}

	private function getTaskSliderUrl(array $fullTaskData, int $userId): string
	{
		$pathMaker = (!empty($fullTaskData['GROUP_ID']))
			? new TaskPathMaker(
				entityId: (int)$fullTaskData['ID'],
				action: PathMaker::DEFAULT_ACTION,
				ownerId: (int)$fullTaskData['GROUP_ID'],
				context: PathMaker::GROUP_CONTEXT,
			)
			:
			new TaskPathMaker(
				entityId: (int)$fullTaskData['ID'],
				action: PathMaker::DEFAULT_ACTION,
				ownerId: $userId,
				context: PathMaker::PERSONAL_CONTEXT,
			)
		;

		return (new Uri($pathMaker->makeEntityPath()))->getUri();
	}
}

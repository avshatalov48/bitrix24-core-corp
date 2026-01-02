<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Onboarding\Internal\Factory\CommandModelFactory;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;

final class OnFirstUserAuthorizeListener extends AbstractEventListener
{
	private const TASKS_LIMIT = 100;

	public function onUserInitialize(array $data): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		if (defined('BX_SECURITY_SESSION_VIRTUAL') && BX_SECURITY_SESSION_VIRTUAL === true)
		{
			return $eventResult;
		}

		$fields = $data['user_fields'] ?? [];
		$userId = (int)($fields['ID'] ?? 0);

		$isUpdate = $data['update'] ?? false;
		if (!$isUpdate)
		{
			return $eventResult;
		}

		if (!empty($fields['LAST_LOGIN']))
		{
			return $eventResult;
		}

		$userTaskIds = $this->getUserTaskIds($userId);

		$commandModels = new CommandModelCollection();
		foreach ($userTaskIds as $taskId)
		{
			$acceptedModel = CommandModelFactory::create(Type::ResponsibleInvitationAccepted, $taskId, $userId);
			$notViewedModel = CommandModelFactory::create(Type::InvitedResponsibleNotViewTaskTwoDays, $taskId, $userId);

			$commandModels->add($acceptedModel);
			$commandModels->add($notViewedModel);
		}

		$this->saveCommandModels($commandModels);

		$this->deleteByUserJob([Type::ResponsibleInvitationNotAcceptedOneDay], $userId);

		return $eventResult;
	}

	private function getUserTaskIds(int $userId): array
	{
		$query = (new TaskQuery($userId))
			->setSelect(['ID'])
			->setWhere(['RESPONSIBLE_ID' => $userId])
			->setLimit(self::TASKS_LIMIT);

		$tasks = (new TaskList())->getList($query);

		$taskIds = array_column($tasks, 'ID');

		Collection::normalizeArrayValuesByInt($taskIds, false);

		return $taskIds;
	}
}
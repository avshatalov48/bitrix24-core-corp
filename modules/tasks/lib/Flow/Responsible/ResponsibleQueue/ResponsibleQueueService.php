<?php

namespace Bitrix\Tasks\Flow\Responsible\ResponsibleQueue;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Provider\OptionProvider;
use Bitrix\Tasks\Util\User;

final class ResponsibleQueueService
{
	private static ResponsibleQueueService $instance;

	private ResponsibleQueueRepository $responsibleQueueRepository;

	public static function getInstance(): ResponsibleQueueService
	{
		if (!isset(self::$instance))
		{
			self::$instance = new ResponsibleQueueService(
				new ResponsibleQueueRepository(),
			);
		}

		return self::$instance;
	}

	public function __construct(ResponsibleQueueRepository $responsibleQueueRepository)
	{
		$this->responsibleQueueRepository = $responsibleQueueRepository;
	}

	public function getNextResponsibleId(Flow $flow): ?int
	{
		$supposedResponsibleId = $this->getSupposedResponsibleId($flow);

		$areNextResponsibleFound = $supposedResponsibleId > 0 && !$this->isUserAbsent($supposedResponsibleId);

		if ($areNextResponsibleFound)
		{
			return $supposedResponsibleId;
		}

		$responsibleQueue = $this->responsibleQueueRepository->get($flow->getId());

		if ($supposedResponsibleId > 0)
		{
			$userIds = $responsibleQueue->getUserIdsAfterSpecificUser($supposedResponsibleId);
		}
		else
		{
			$userIds = $responsibleQueue->getUserIds();
		}

		foreach ($userIds as $userId)
		{
			if (!$this->isUserAbsent($userId))
			{
				return $userId;
			}
		}

		return null;
	}

	private function getSupposedResponsibleId(Flow $flow): ?int
	{
		$latestResponsibleId = (new OptionProvider())->getResponsibleQueueLatestId($flow->getId());

		if ($latestResponsibleId)
		{
			$responsibleQueueItem = $this->responsibleQueueRepository->getQueueItemByUserId($flow->getId(), $latestResponsibleId);
			$responsibleId = $responsibleQueueItem?->getNextUserId();
		}
		else
		{
			$responsibleQueueItem = $this->responsibleQueueRepository->getFirstQueueItem($flow->getId());
			$responsibleId = $responsibleQueueItem?->getUserId();
		}

		return $responsibleId;
	}

	private function isUserAbsent(int $userId): bool
	{
		return !empty(User::isAbsence([$userId]));
	}

	/**
	 * @param int $flowId
	 * @param array<int> $userIds
	 */
	public function save(int $flowId, array $userIds): void
	{
		$userIds = array_unique(array_map('intval', $userIds));

		$queue = new ResponsibleQueue($flowId);

		foreach ($userIds as $i => $userId)
		{
			$sort = $i * 10;
			$nextUserId = !empty($userIds[$i + 1]) ? $userIds[$i + 1] : $userIds[0];
			$queueItem = new ResponsibleQueueItem(0, $flowId, $userId, $nextUserId, $sort);
			$queue->addItem($queueItem);
		}

		$this->responsibleQueueRepository->save($queue);
	}

	public function delete(int $flowId): void
	{
		$this->responsibleQueueRepository->delete($flowId);
	}

	public function getFlowIdsByUser(int $userId, $limit = 100): array
	{
		return $this->responsibleQueueRepository->getFlowIdsByUser($userId, $limit);
	}
}
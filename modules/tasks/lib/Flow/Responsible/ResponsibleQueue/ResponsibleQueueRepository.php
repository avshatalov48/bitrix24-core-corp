<?php

namespace Bitrix\Tasks\Flow\Responsible\ResponsibleQueue;

use Bitrix\Tasks\Flow\Internal\FlowResponsibleQueueTable;

final class ResponsibleQueueRepository
{
	public function getFirstQueueItem(int $flowId): ?ResponsibleQueueItem
	{
		if ($flowId <= 0)
		{
			return null;
		}

		$query = FlowResponsibleQueueTable::query()
			->setSelect(['*'])
			->where('FLOW_ID', $flowId)
			->addOrder('SORT')
			->setLimit(1)
			->exec()
		;

		$queryResultItem = $query->fetchObject();

		if (!$queryResultItem)
		{
			return null;
		}

		return new ResponsibleQueueItem(
			$queryResultItem->getId(),
			$queryResultItem->getFlowId(),
			$queryResultItem->getUserId(),
			$queryResultItem->getNextUserId(),
			$queryResultItem->getSort(),
		);
	}

	public function getQueueItemByUserId(int $flowId, int $userId): ?ResponsibleQueueItem
	{
		if ($flowId <= 0 || $userId <= 0)
		{
			return null;
		}

		$query = FlowResponsibleQueueTable::query()
			->setSelect(['*'])
			->where('FLOW_ID', $flowId)
			->where('USER_ID', $userId)
			->exec()
		;

		$queryResultItem = $query->fetchObject();

		if ($queryResultItem)
		{
			return new ResponsibleQueueItem(
				$queryResultItem->getId(),
				$queryResultItem->getFlowId(),
				$queryResultItem->getUserId(),
				$queryResultItem->getNextUserId(),
				$queryResultItem->getSort(),
			);
		}

		return null;
	}

	public function get(int $flowId): ResponsibleQueue
	{
		$queue = new ResponsibleQueue($flowId);

		if ($flowId <= 0)
		{
			return $queue;
		}

		$queryResult = FlowResponsibleQueueTable::query()
			->setSelect(['*'])
			->where('FLOW_ID', $flowId)
			->addOrder('SORT', 'ASC')
			->fetchCollection()
		;

		if ($queryResult->count() === 0)
		{
			return $queue;
		}

		foreach ($queryResult as $queryResultItem)
		{
			$queueItem = new ResponsibleQueueItem(
				$queryResultItem->getId(),
				$queryResultItem->getFlowId(),
				$queryResultItem->getUserId(),
				$queryResultItem->getNextUserId(),
				$queryResultItem->getSort(),
			);

			$queue->addItem($queueItem);
		}

		return $queue;
	}

	public function save(ResponsibleQueue $responsibleQueue): void
	{
		$itemsToAdd = [];
		foreach ($responsibleQueue->getItems() as $queueItem)
		{
			$itemsToAdd[] = [
				'FLOW_ID' => $queueItem->getFlowId(),
				'USER_ID' => $queueItem->getUserId(),
				'NEXT_USER_ID' => $queueItem->getNextUserId(),
				'SORT' => $queueItem->getSort(),
			];
		}

		FlowResponsibleQueueTable::deleteByFilter(['FLOW_ID' => $responsibleQueue->getFlowId()]);
		FlowResponsibleQueueTable::addMulti($itemsToAdd, true);
	}

	public function delete(int $flowId): void
	{
		if ($flowId <= 0)
		{
			return;
		}

		FlowResponsibleQueueTable::deleteByFilter(['FLOW_ID' => $flowId]);
	}

	public function getFlowIdsByUser(int $userId, int $limit): array
	{
		if ($userId <= 0 || $limit <= 0)
		{
			return [];
		}

		$queryResult = FlowResponsibleQueueTable::query()
			->setSelect(['FLOW_ID'])
			->where('USER_ID', $userId)
			->setLimit($limit)
			->fetchAll()
		;

		if (empty($queryResult))
		{
			return [];
		}

		return array_map(fn($item) => (int)$item['FLOW_ID'], $queryResult);
	}
}
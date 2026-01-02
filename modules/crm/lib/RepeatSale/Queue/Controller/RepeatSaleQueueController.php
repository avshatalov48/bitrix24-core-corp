<?php

namespace Bitrix\Crm\RepeatSale\Queue\Controller;

use Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueue;
use Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueueTable;
use Bitrix\Crm\RepeatSale\Queue\QueueItem;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Type\DateTime;

final class RepeatSaleQueueController
{
	use Singleton;

	public function add(QueueItem $queueItem): ?AddResult
	{
		$hash = $queueItem->getHash();
		if ($hash === null || !$this->hasItemInQueue($queueItem))
		{
			return RepeatSaleQueueTable::add($this->getFields($queueItem));
		}

		return null;
	}

	private function hasItemInQueue(QueueItem $queueItem): bool
	{
		return (bool)RepeatSaleQueueTable::query()
			->where('HASH', $queueItem->getHash())
			->where('JOB_ID', $queueItem->getJobId())
			->setLimit(1)
			->fetch()
		;
	}

	public function update(int $id, QueueItem $queueItem): UpdateResult
	{
		return RepeatSaleQueueTable::update($id, $this->getFields($queueItem));
	}

	private function getFields(QueueItem $queueItem): array
	{
		return [
			'JOB_ID' => $queueItem->getJobId(),
			'IS_ONLY_CALC' => $queueItem->isOnlyCalc(),
			'STATUS' => $queueItem->getStatus()->value,
			'LAST_ENTITY_TYPE_ID' => $queueItem->getLastEntityTypeId(),
			'LAST_ITEM_ID' => $queueItem->getLastItemId(),
			'LAST_ASSIGNMENT_ID' => $queueItem->getLastAssignmentId(),
			'ITEMS_COUNT' => $queueItem->getItemsCount(),
			'HANDLER_TYPE_ID' => $queueItem->getHandlerTypeId(),
			'RETRY_COUNT' => $queueItem->getRetryCount(),
			'HASH' => $queueItem->getHash(),
			'PARAMS' => $queueItem->getParams(),
			'UPDATED_AT' => new DateTime(),
		];
	}

	public function getList(array $params = []): Collection
	{
		$select = $params['select'] ?? ['*'];
		$filter = $params['filter'] ?? [];
		$order = $params['order'] ?? [
			'ID' => 'DESC',
		];
		$offset = $params['offset'] ?? 0;
		$limit = $params['limit'] ?? 10;

		$query = RepeatSaleQueueTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setOffset($offset)
			->setLimit($limit)
		;

		return QueryHelper::decompose($query);
	}

	public function getById(int $id): ?RepeatSaleQueue
	{
		return RepeatSaleQueueTable::query()
			->setSelect(['*'])
			->setFilter(['=ID' => $id])
			->fetchObject()
		;
	}

	public function delete(int $id): DeleteResult
	{
		return RepeatSaleQueueTable::delete($id);
	}

	public function deleteOnlyCalcItems(): Result
	{
		$connection = Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();
		$table = RepeatSaleQueueTable::getTableName();

		$sql = 'DELETE FROM ' . $sqlHelper->quote($table) . ' WHERE IS_ONLY_CALC = \'Y\'';

		return $connection->query($sql);
	}

	public function deleteByJobId(int $jobId): Result
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$table = RepeatSaleQueueTable::getTableName();

		$sql ='DELETE FROM ' . $sqlHelper->quote($table) . ' WHERE JOB_ID =' . $jobId;

		return Application::getConnection()->query($sql);
	}
}

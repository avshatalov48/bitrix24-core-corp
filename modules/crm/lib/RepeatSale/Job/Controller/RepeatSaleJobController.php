<?php

namespace Bitrix\Crm\RepeatSale\Job\Controller;

use Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJob;
use Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJobTable;
use Bitrix\Crm\RepeatSale\Job\JobItem;
use Bitrix\Crm\RepeatSale\Queue\Status;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Type\DateTime;

final class RepeatSaleJobController
{
	use Singleton;

	public function add(JobItem $jobItem): AddResult
	{
		return RepeatSaleJobTable::add($this->getFields($jobItem));
	}

	public function update(int $id, JobItem $jobItem, ?Context $context = null): UpdateResult
	{
		return RepeatSaleJobTable::update($id, $this->getFields($jobItem));
	}

	private function getFields(JobItem $jobItem): array
	{
		return [
			'SEGMENT_ID' => $jobItem->getSegmentId(),
			'SCHEDULE_TYPE' => $jobItem->getScheduleType(),
			'UPDATED_AT' => new DateTime(),
			'UPDATED_BY_ID' => Container::getInstance()->getContext()->getUserId(),
		];
	}

	public function getList(array $params = []): Collection
	{
		$select = $params['select'] ?? ['*', 'SEGMENT.*'];
		$filter = $params['filter'] ?? [];
		$order = $params['order'] ?? [
			'ID' => 'DESC',
		];
		$offset = $params['offset'] ?? 0;
		$limit = $params['limit'] ?? 10;

		$query = RepeatSaleJobTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setOffset($offset)
			->setLimit($limit)
		;

		return QueryHelper::decompose($query);
	}

	public function getById(int $id): ?RepeatSaleJob
	{
		return RepeatSaleJobTable::query()
			->setSelect(['*'])
			->setFilter(['=ID' => $id])
			->fetchObject()
		;
	}

	public function delete(int $id): DeleteResult
	{
		return RepeatSaleJobTable::delete($id);
	}

	public function getJobInProcess(): ?RepeatSaleJob
	{
		return RepeatSaleJobTable::query()
			->setSelect([
				'*',
				'STATUS' => '\Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueueTable:JOB.STATUS',
			])
			->where('STATUS', '=', Status::Progress->value)
			->fetchObject()
		;
	}
}

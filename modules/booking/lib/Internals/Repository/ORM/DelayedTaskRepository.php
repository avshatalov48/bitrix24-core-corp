<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Model\DelayedTaskTable;
use Bitrix\Booking\Internals\Model\EO_DelayedTask_Collection;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\DelayedTaskMapper;
use Bitrix\Booking\Internals\Service\DelayedTask\Data\DataInterface;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTask;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskCollection;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskStatus;
use Bitrix\Booking\Internals\Service\DelayedTask\DelayedTaskType;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

class DelayedTaskRepository
{
	public function __construct(private readonly DelayedTaskMapper $mapper)
	{
	}

	public function create(string $code, DataInterface $data): AddResult
	{
		return DelayedTaskTable::add([
			'CODE' => $code,
			'TYPE' => $data->getType()->value,
			'DATA' => Json::encode($data->toArray()),
			'STATUS' => DelayedTaskStatus::Pending->value,
		]);
	}

	public function setStatus(array $ids, DelayedTaskStatus $status = DelayedTaskStatus::Processed): void
	{
		DelayedTaskTable::updateMulti($ids, ['STATUS' => $status->value]);
	}

	public function setProcessing(int $id): void
	{
		DelayedTaskTable::updateByFilter(
			[
				'ID' => $id,
				'STATUS' => DelayedTaskStatus::Pending->value,
			],
			['STATUS' => DelayedTaskStatus::Processing->value],
		);
	}

	public function getPending(int $limit = 10): DelayedTaskCollection
	{
		$ormDelayedTasks = DelayedTaskTable::query()
			->where('STATUS', DelayedTaskStatus::Pending->value)
			->setLimit($limit)
			->setOrder('ID')
			->fetchCollection()
		;

		return $this->createCollection($ormDelayedTasks);
	}

	public function getById(int $id): DelayedTask|null
	{
		$ormDelayedTasks = DelayedTaskTable::query()
			->where('ID', $id)
			->fetchCollection()
		;

		return $this->createCollection($ormDelayedTasks)->getFirstCollectionItem();
	}

	public function getForUpdate(
		string $code,
		DelayedTaskType $delayedTaskType,
		DelayedTaskStatus $delayedTaskStatus,
	): DelayedTask|null
	{
		$ormDelayedTasks = DelayedTaskTable::query()
			->where('CODE', $code)
			->where('STATUS', $delayedTaskStatus->value)
			->where('TYPE', $delayedTaskType->value)
			->fetchCollection()
		;

		return $this->createCollection($ormDelayedTasks)->getFirstCollectionItem();
	}

	public function updateData(int $id, DataInterface $data): UpdateResult
	{
		return DelayedTaskTable::update($id, [
			'DATA' => Json::encode($data->toArray()),
			'UPDATED_AT' => new DateTime(),
		]);
	}

	private function createCollection(EO_DelayedTask_Collection $ormDelayedTasks): DelayedTaskCollection
	{
		$collection = new DelayedTaskCollection();

		foreach ($ormDelayedTasks as $ormDelayedTask)
		{
			$collection->add($this->mapper->convertFromOrm($ormDelayedTask));
		}

		return $collection;
	}
}


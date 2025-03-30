<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Exception\JournalAppendException;
use Bitrix\Booking\Internals\Service\Journal\JournalEvent;
use Bitrix\Booking\Internals\Service\Journal\JournalEventCollection;
use Bitrix\Booking\Internals\Service\Journal\JournalStatus;
use Bitrix\Booking\Internals\Service\Journal\JournalType;
use Bitrix\Booking\Internals\Model\JournalTable;
use Bitrix\Booking\Internals\Repository\JournalRepositoryInterface;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;

class JournalRepository implements JournalRepositoryInterface
{
	private const LOCK_KEY = 'booking.journallock';

	public function append(JournalEvent $event): void
	{
		$result = JournalTable::add([
			'ENTITY_ID' => $event->entityId,
			'TYPE' => $event->type->value,
			'DATA' => Json::encode($event->data),
			'STATUS' => JournalStatus::Pending->value,
		]);

		if (!$result->isSuccess())
		{
			throw new JournalAppendException($result->getErrors()[0]->getMessage());
		}
	}

	public function getPending(int $limit = 50): JournalEventCollection
	{
		if (!Application::getConnection()->lock(self::LOCK_KEY))
		{
			return new JournalEventCollection(...[]);
		}

		$result = JournalTable::query()
			->setSelect(['ID', 'ENTITY_ID', 'TYPE', 'DATA'])
			->where('STATUS', '=', JournalStatus::Pending->value)
			->setLimit($limit)
			->setOrder(['CREATED_AT' => 'ASC'])
			->exec()
			->fetchAll()
		;

		$events = [];

		foreach ($result as $row)
		{
			$events[] = $this->mapRowToEntity($row);
		}

		return new JournalEventCollection(...$events);
	}

	public function getById(int $id): JournalEvent|null
	{
		$result = JournalTable::query()
			->setSelect(['ID', 'ENTITY_ID', 'TYPE', 'DATA'])
			->where('ID', '=', $id)
			->setLimit(1)
			->exec()
			->fetch()
		;

		if (!$result)
		{
			return null;
		}

		return $this->mapRowToEntity($result);
	}

	public function markProcessed(JournalEventCollection $collection): void
	{
		if ($collection->isEmpty())
		{
			return;
		}

		$ids = [];

		foreach ($collection as $event)
		{
			$ids[] = $event->id;
		}

		JournalTable::updateMulti($ids, [
			'STATUS' => JournalStatus::Processed->value,
		]);

		Application::getConnection()->unlock(self::LOCK_KEY);
	}

	public function updateStatus(int $id, JournalStatus $status, string $info): void
	{
		JournalTable::update($id, [
			'STATUS' => $status->value,
			'INFO' => $info,
		]);

		Application::getConnection()->unlock(self::LOCK_KEY);
	}

	private function mapRowToEntity(array $row): JournalEvent
	{
		return new JournalEvent(
			entityId: (int)$row['ENTITY_ID'],
			type: JournalType::from($row['TYPE']),
			data: Json::decode($row['DATA']),
			id: (int)$row['ID'],
		);
	}
}

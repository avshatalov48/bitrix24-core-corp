<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer\Controller;

use Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBuffer;
use Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBufferItem;
use Bitrix\Crm\Copilot\AiQueueBuffer\Entity\AiQueueBufferTable;
use Bitrix\Crm\Copilot\AiQueueBuffer\Entity\EO_AiQueueBuffer_Collection;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Result;

final class AiQueueBufferController
{
	use Singleton;

	private const LIMIT = 10;

	public function add(AiQueueBufferItem $item): AddResult
	{
		return AiQueueBufferTable::add($item->toEntityFieldsArray());
	}

	public function getList(array $params = []): EO_AiQueueBuffer_Collection
	{
		$select = $params['select'] ?? ['*'];
		$filter = $params['filter'] ?? [];
		$limit = $params['limit'] ?? self::LIMIT;
		$order = ['ID' => 'ASC'];

		$query = AiQueueBufferTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setLimit($limit)
		;

		return $query->exec()->fetchCollection();
	}

	public function getById(int $id): ?AiQueueBuffer
	{
		if ($id <= 0)
		{
			return null;
		}

		return AiQueueBufferTable::getById($id)?->fetchObject();
	}

	public function delete(array $ids): Result
	{
		return AiQueueBufferTable::deleteByIds($ids);
	}

	public function deleteAll(): void
	{
		AiQueueBufferTable::deleteAll();
	}
}

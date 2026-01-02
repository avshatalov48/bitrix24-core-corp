<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Client\ClientTypeCollection;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use Bitrix\Booking\Internals\Repository\ClientTypeRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ClientTypeMapper;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class ClientTypeRepository implements ClientTypeRepositoryInterface
{
	private ClientTypeMapper $mapper;

	public function __construct(ClientTypeMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	/**
	 * @throws NotImplementedException
	 */
	public function getById(int $id, int $userId = 0): Entity\Client\ClientType|null
	{
		throw new NotImplementedException('Method getById() not implemented');
	}

	public function get(string $code, string $moduleId): Entity\Client\ClientType|null
	{
		$filter =
			(new ConditionTree())
				->where('CODE', '=', $code)
				->where('MODULE_ID', '=', $moduleId)
		;

		return $this->getList(
			limit: 1,
			filter: $filter,
		)->getFirstCollectionItem();
}

	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		ConditionTree|null $filter = null,
		array|null $sort = null,
	): ClientTypeCollection
	{
		$query = ClientTypeTable::query()->setSelect(['*']);

		if ($filter !== null)
		{
			$query->where($filter);
		}

		$queryResult = $query->exec();
		$clientTypeCollection = new ClientTypeCollection();

		while ($ormResourceType = $queryResult->fetchObject())
		{
			$clientTypeCollection->add($this->mapper->convertFromOrm($ormResourceType));
		}

		return $clientTypeCollection;
	}
}
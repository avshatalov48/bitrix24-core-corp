<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Model\BookingClientTable;
use Bitrix\Booking\Internals\Model\BookingTable;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use Bitrix\Booking\Internals\Model\WaitListItemTable;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Provider\Params\FilterInterface;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

class BookingClientRepository implements BookingClientRepositoryInterface
{
	public function link(int $entityId, EntityType $entityType, Entity\Client\ClientCollection $clientCollection): void
	{
		$data = [];

		$primaryClient = $clientCollection->getPrimaryClient();

		/** @var Entity\Client\Client $client */
		foreach ($clientCollection as $client)
		{
			$isPrimary = (
				$primaryClient
				&& $primaryClient->getId() === $client->getId()
				&& $this->getClientTypeId($primaryClient) === $this->getClientTypeId($client)
			);

			$data[] = [
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE' => $entityType->value,
				'CLIENT_ID' => $client->getId(),
				'CLIENT_TYPE_ID' => $this->getClientTypeId($client),
				'IS_PRIMARY' => $isPrimary ? 'Y' : 'N',
			];
		}

		if (!empty($data))
		{
			BookingClientTable::addMulti($data, true);
		}
	}

	public function unLink(
		int $entityId,
		EntityType $entityType,
		Entity\Client\ClientCollection $clientCollection
	): void
	{
		/** @var Entity\Client\Client $client */
		foreach ($clientCollection as $client)
		{
			$this->unLinkByFilter([
				'=ENTITY_ID' => $entityId,
				'=ENTITY_TYPE' => $entityType->value,
				'=CLIENT_ID' => $client->getId(),
				'=CLIENT_TYPE_ID' => $this->getClientTypeId($client),
			]);
		}
	}

	public function unLinkByFilter(array $filter): void
	{
		BookingClientTable::deleteByFilter($filter);
	}

	public function getTotalClients(): int
	{
		$query = BookingClientTable::query();

		$res = $query
			->setSelect(['CNT' => Query::expr()->countDistinct('CLIENT_ID')])
			->where('IS_PRIMARY', 'Y')
			->where(
				(new ConditionTree())
					->logic(ConditionTree::LOGIC_OR)
					->whereExists(
						new SqlExpression("
							SELECT 1
							FROM " . BookingTable::getTableName() . "
							WHERE
								ID = " . $query->getInitAlias() . ".ENTITY_ID
								AND ENTITY_TYPE = '" . EntityType::Booking->value . "'
								AND IS_DELETED = 'N'
						")
					)
					->whereExists(
						new SqlExpression("
							SELECT 1
							FROM " . WaitListItemTable::getTableName() . "
							WHERE
								ID = " . $query->getInitAlias() . ".ENTITY_ID
								AND ENTITY_TYPE = '" . EntityType::WaitList->value . "'
								AND IS_DELETED = 'N'
						")
					)
			)
			->setCacheTtl(3600) // # 1 hour
			->exec()
			->fetch()
		;

		return isset($res['CNT']) ? (int)$res['CNT'] : 0;
	}

	public function getTotalNewClientsToday(FilterInterface $filter): int
	{
		$conditions = $filter->prepareFilter();

		if (!$conditions->hasConditions())
		{
			return 0;
		}

		$query = BookingClientTable::query();
		$filter->prepareQuery($query);
		$res = $query
			->setSelect(['CNT' => Query::expr()->countDistinct('CLIENT_ID')])
			->where('IS_PRIMARY', 'Y')
			->whereNot('IS_RETURNING')
			->where($conditions)
			->exec()
			->fetch()
		;

		return isset($res['CNT']) ? (int)$res['CNT'] : 0;
	}

	private function getClientTypeId(Entity\Client\Client $client): ?int
	{
		$clientType = $client->getType();
		if (!$clientType)
		{
			return null;
		}

		$clientTypeCode = $clientType->getCode();
		$clientTypeModule = $clientType->getModuleId();
		if (
			!$clientTypeCode
			|| !$clientTypeModule
		)
		{
			return null;
		}

		$clientTypeRow = ClientTypeTable::query()
			->setSelect(['ID'])
			->where('CODE', '=', $clientTypeCode)
			->where('MODULE_ID', '=', $clientTypeModule)
			->setLimit(1)
			->exec()
			->fetch()
		;

		if ($clientTypeRow)
		{
			return (int)$clientTypeRow['ID'];
		}

		//@todo we need to check via client provider if the provided type is known to external module before we save it
		$addResult = ClientTypeTable::add([
			'CODE' => $clientTypeCode,
			'MODULE_ID' => $clientTypeModule,
		]);
		if ($addResult->isSuccess())
		{
			return $addResult->getId();
		}

		return null;
	}
}

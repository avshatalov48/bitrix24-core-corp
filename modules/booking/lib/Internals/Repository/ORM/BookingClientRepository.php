<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Model\BookingClientTable;
use Bitrix\Booking\Internals\Model\BookingTable;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use Bitrix\Booking\Internals\Repository\BookingClientRepositoryInterface;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Query;

class BookingClientRepository implements BookingClientRepositoryInterface
{
	public function link(Booking $booking, Entity\Booking\ClientCollection $clientCollection): void
	{
		$data = [];

		$primaryClient = $clientCollection->getPrimaryClient();

		/** @var Entity\Booking\Client $client */
		foreach ($clientCollection as $client)
		{
			$isPrimary = (
				$primaryClient
				&& $primaryClient->getId() === $client->getId()
				&& $this->getClientTypeId($primaryClient) === $this->getClientTypeId($client)
			);

			$data[] = [
				'BOOKING_ID' => $booking->getId(),
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

	public function unLink(Booking $booking, Entity\Booking\ClientCollection $clientCollection): void
	{
		/** @var Entity\Booking\Client $client */
		foreach ($clientCollection as $client)
		{
			$this->unLinkByFilter([
				'=BOOKING_ID' => $booking->getId(),
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
			->whereExists(
				new SqlExpression("
					SELECT 1
					FROM " . BookingTable::getTableName() . "
					WHERE
						ID = " . $query->getInitAlias() . ".BOOKING_ID
						AND IS_DELETED = 'N'
				")
			)
			->setCacheTtl(3600)
			->exec()
			->fetch()
		;

		return isset($res['CNT']) ? (int)$res['CNT'] : 0;
	}

	public function getTotalNewClientsToday(array $bookingIds): int
	{
		if (empty($bookingIds))
		{
			return 0;
		}

		$res = BookingClientTable::query()
			->setSelect(['CNT' => Query::expr()->countDistinct('CLIENT_ID')])
			->where('IS_PRIMARY', 'Y')
			->whereIn('BOOKING_ID', $bookingIds)
			->where('IS_RETURNING', false)
			->exec()
			->fetch()
		;

		return isset($res['CNT']) ? (int)$res['CNT'] : 0;
	}

	private function getClientTypeId(Entity\Booking\Client $client): ?int
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

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Exception\Booking\CreateBookingException;
use Bitrix\Booking\Internals\Exception\Booking\RemoveBookingException;
use Bitrix\Booking\Internals\Exception\Note\CreateNoteException;
use Bitrix\Booking\Internals\Exception\Note\RemoveNoteException;
use Bitrix\Booking\Internals\Model\BookingTable;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Model\EO_Booking;
use Bitrix\Booking\Internals\Model\NotesTable;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\BookingMapper;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Booking\BookingSort;
use Bitrix\Booking\Provider\Params\FilterInterface;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\QueryHelper;

class BookingRepository implements BookingRepositoryInterface
{
	private BookingMapper $mapper;

	public function __construct(BookingMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	public function getQuery(FilterInterface|null $filter = null): Query
	{
		$query = BookingTable::query();

		if ($filter !== null)
		{
			$filter->prepareQuery($query);
			$query->where($filter->prepareFilter());
		}

		return $query;
	}

	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		array|null $sort = null,
		array|null $select = null,
		int|null $userId = null,
	): Entity\Booking\BookingCollection
	{
		$query = BookingTable::query()
			->setSelect(array_merge(['*'], $select ?: []))
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		if ($offset !== null)
		{
			$query->setOffset($offset);
		}

		if ($filter !== null)
		{
			$filter->prepareQuery($query);
			$query->where($filter->prepareFilter());
		}

		if ($sort !== null)
		{
			$query->setOrder($sort);
		}

		$ormBookings = QueryHelper::decompose($query);

		$bookings = [];
		foreach ($ormBookings as $ormBooking)
		{
			$bookings[] = $this->mapper->convertFromOrm($ormBooking);
		}

		return new Entity\Booking\BookingCollection(...$bookings);
	}

	public function getIntersectionsList(
		Entity\Booking\Booking $booking,
		int|null $userId = null,
		int|null $limit = null
	): Entity\Booking\BookingCollection
	{
		$filter = [
			'WITHIN' => [
				'DATE_FROM' => $booking->getDatePeriod()?->getDateFrom()?->getTimestamp(),
				'DATE_TO' => $booking->getMaxDate()?->getTimestamp(),
			],
			'RESOURCE_ID' => $booking->getResourceCollection()->getEntityIds(),
		];
		if ($booking->getId())
		{
			$filter['!ID'] = $booking->getId();
		}

		$result = new Entity\Booking\BookingCollection();

		$intersectingBookingCollection = $this->getList(
			limit: $limit,
			filter: new BookingFilter($filter),
			sort: (new BookingSort([
				'DATE_FROM' => 'ASC',
				'IS_RECURRING' => 'ASC',
			]))->prepareSort(),
			select: (new BookingSelect(['RESOURCES']))->prepareSelect(),
			userId: $userId,
		);
		foreach ($intersectingBookingCollection as $intersectingBooking)
		{
			$intersect = $intersectingBooking->doEventsIntersect($booking);
			if (!$intersect)
			{
				continue;
			}

			$result->add($intersectingBooking);
			if ($limit !== null && $result->count() >= $limit)
			{
				break;
			}
		}

		return $result;
	}

	public function getById(
		int $id,
		int $userId = 0,
		bool $withCounters = true,
		bool $withClientsData = true,
		bool $withExternalData = true,
	): Entity\Booking\Booking|null
	{
		// todo: needs refactoring, repository should not know about providers
		$provider = new BookingProvider();

		$collection = $provider->getList(
			gridParams: new GridParams(
				limit: 1,
				filter: new BookingFilter(['ID' => $id]),
				select: new BookingSelect([
					'CLIENTS',
					'RESOURCES',
					'EXTERNAL_DATA',
					'NOTE',
				])
			),
			userId: $userId
		);

		if ($withCounters)
		{
			$provider->withCounters($collection, $userId);
		}

		if ($withClientsData)
		{
			$provider->withClientsData($collection);
		}

		if ($withExternalData)
		{
			$provider->withExternalData($collection);
		}

		return $collection->getFirstCollectionItem();
	}

	public function getByIdForManager(int $id): Entity\Booking\Booking|null
	{
		$select = new BookingSelect(['RESOURCES', 'NOTE']);

		$ormBooking = BookingTable::query()
			->setSelect(array_merge(['*'], $select->prepareSelect()))
			->where('ID', '=', $id)
			->setLimit(1)
			->exec()
			->fetchObject()
		;

		if (!$ormBooking)
		{
			return null;
		}

		return $this->mapper->convertFromOrm($ormBooking);
	}

	public function save(Entity\Booking\Booking $booking): int
	{
		$ormBooking = $this->mapper->convertToOrm($booking);
		$result = $ormBooking->save();
		if (!$result->isSuccess())
		{
			throw new CreateBookingException($result->getErrors()[0]->getMessage());
		}

		$this->handleNote($ormBooking, $booking->getNote(), $result->getId());

		return $result->getId();
	}

	public function remove(int $id): void
	{
		$result = BookingTable::update($id, ['IS_DELETED' => 'Y']);
		if (!$result->isSuccess())
		{
			throw new RemoveBookingException($result->getErrors()[0]->getMessage());
		}
	}

	private function handleNote(EO_Booking $ormBooking, string|null $noteDescription, int $bookingId): void
	{
		$note = $ormBooking->fillNote() ?? NotesTable::createObject();
		if (empty($noteDescription) && $note->getId())
		{
			$noteDeleteResult = $note->delete();
			if (!$noteDeleteResult->isSuccess())
			{
				throw new RemoveNoteException($noteDeleteResult->getErrors()[0]->getMessage());
			}

			return;
		}

		if ($noteDescription === null)
		{
			return;
		}

		$note->setDescription($noteDescription);
		$note->setEntityType(EntityType::Booking->value);
		$note->setEntityId($bookingId);
		$noteSaveResult = $note->save();

		if (!$noteSaveResult->isSuccess())
		{
			throw new CreateNoteException($noteSaveResult->getErrors()[0]->getMessage());
		}
	}
}

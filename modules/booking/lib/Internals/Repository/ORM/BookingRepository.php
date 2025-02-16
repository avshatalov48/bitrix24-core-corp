<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Exception\Booking\CreateBookingException;
use Bitrix\Booking\Exception\Booking\RemoveBookingException;
use Bitrix\Booking\Exception\Note\CreateNoteException;
use Bitrix\Booking\Exception\Note\RemoveNoteException;
use Bitrix\Booking\Internals\Model\BookingTable;
use Bitrix\Booking\Internals\Model\EO_Booking;
use Bitrix\Booking\Internals\Model\NotesTable;
use Bitrix\Booking\Internals\Query\Booking\GetListFilter;
use Bitrix\Booking\Internals\Query\Booking\GetListHandler;
use Bitrix\Booking\Internals\Query\Booking\GetListRequest;
use Bitrix\Booking\Internals\Query\Booking\GetListSelect;
use Bitrix\Booking\Internals\Query\Booking\GetListSort;
use Bitrix\Booking\Internals\Query\FilterInterface;
use Bitrix\Booking\Internals\Query\SelectInterface;
use Bitrix\Booking\Internals\Query\SortInterface;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Repository\BookingRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\BookingMapper;
use Bitrix\Main\ORM\Query\QueryHelper;
use DateTimeImmutable;
use DateTimeZone;

class BookingRepository implements BookingRepositoryInterface
{
	private BookingMapper $mapper;

	public function __construct(BookingMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		SortInterface|null $sort = null,
		SelectInterface|null $select = null,
	): Entity\Booking\BookingCollection
	{
		$query = BookingTable::query()
			->setSelect(array_merge(['*'], $select ? $select->prepareSelect() : []))
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		if ($offset !== null)
		{
			$query->setOffset($offset);
		}

		$filter?->prepareQuery($query);
		$preparedFilter = $filter?->prepareFilter();
		if ($preparedFilter)
		{
			$query->where($preparedFilter);
		}

		$preparedSort = $sort?->prepareSort();
		if ($preparedSort)
		{
			$query->setOrder($preparedSort);
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
		int $limit = 1
	): Entity\Booking\BookingCollection
	{
		$result = new Entity\Booking\BookingCollection();

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
		$filter = new GetListFilter($filter);

		$query = BookingTable::query()
			->setSelect([
				'ID',
				'DATE_FROM',
				'DATE_TO',
				'IS_RECURRING',
				'RRULE',
				'TIMEZONE_FROM',
				'TIMEZONE_TO',
			])
			->where('IS_DELETED', '=', 'N')
			->where($filter->prepareFilter())
			->setOrder(
				(new GetListSort([
					'DATE_FROM' => 'ASC',
					'IS_RECURRING' => 'ASC',
				]))->prepareSort()
			)
		;
		$filter->prepareQuery($query);

		$queryResult = $query->exec();
		while ($bookingRow = $queryResult->fetchRaw())
		{
			$existingBooking = (new Entity\Booking\Booking())
				->setId((int)$bookingRow['ID'])
				->setDatePeriod($this->getDatePeriodFromRow($bookingRow))
				->setRrule($this->getRruleFromRow($bookingRow))
			;

			$intersect = $existingBooking->doEventsIntersect($booking);
			if ($intersect)
			{
				$result->add($existingBooking);
				if ($result->count() >= $limit)
				{
					break;
				}
			}
		}

		return $result;
	}

	private function getRruleFromRow(array $bookingRow): ?string
	{
		if (
			$bookingRow['IS_RECURRING'] === 'Y'
			&& $bookingRow['RRULE'] !== ''
		)
		{
			return (string)$bookingRow['RRULE'];
		}

		return null;
	}

	private function getDatePeriodFromRow(array $bookingRow): Entity\DatePeriod
	{
		return new Entity\DatePeriod(
			$this->createDateTimeFromTimestamp(
				(int)$bookingRow['DATE_FROM'],
				(string)$bookingRow['TIMEZONE_FROM'],
			),
			$this->createDateTimeFromTimestamp(
				(int)$bookingRow['DATE_TO'],
				(string)$bookingRow['TIMEZONE_TO'],
			),
		);
	}

	private function createDateTimeFromTimestamp(int $timestamp, string $timezone): DateTimeImmutable
	{
		return (new DateTimeImmutable('@' . $timestamp))
			->setTimezone(new DateTimeZone($timezone));
	}

	public function getById(int $id, int $userId = 0): Entity\Booking\Booking|null
	{
		return (new GetListHandler())(
			new GetListRequest(
				userId: $userId,
				limit: 1,
				offset: null,
				filter: new GetListFilter(['ID' => $id]),
				sort: null,
				select: new GetListSelect([
					'CLIENTS',
					'RESOURCES',
					'EXTERNAL_DATA',
					'NOTE',
				]),
				withCounters: $userId > 0,
				withClientData: true,
				withExternalData: true,
			)
		)->getFirstCollectionItem();
	}

	public function getByIdForManager(int $id): Entity\Booking\Booking|null
	{
		$select = new GetListSelect(['RESOURCES', 'NOTE']);

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

	public function save(Entity\Booking\Booking $booking): Entity\Booking\Booking
	{
		$ormBooking = $this->mapper->convertToOrm($booking);
		$result = $ormBooking->save();
		if (!$result->isSuccess())
		{
			throw new CreateBookingException($result->getErrors()[0]->getMessage());
		}

		$this->handleNote($ormBooking, $booking->getNote(), $result->getId());

		return $this->getById($result->getId());
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
		$note->setBookingId($bookingId);
		$noteSaveResult = $note->save();

		if (!$noteSaveResult->isSuccess())
		{
			throw new CreateNoteException($noteSaveResult->getErrors()[0]->getMessage());
		}
	}
}

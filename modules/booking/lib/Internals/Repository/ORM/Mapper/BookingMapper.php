<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Entity\Booking\ClientCollection;
use Bitrix\Booking\Entity\Booking\ExternalDataCollection;
use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Model\BookingTable;
use Bitrix\Booking\Internals\Model\EO_BookingResource;
use Bitrix\Booking\Internals\Model\EO_Resource;
use Bitrix\Booking\Internals\Model\EO_Booking;
use Bitrix\Booking\Internals\Service\Rrule;
use Bitrix\Main\Type\DateTime;
use DateTimeImmutable;
use DateTimeZone;

class BookingMapper
{
	private ResourceMapper $resourceMapper;
	private ClientMapper $clientMapper;
	private ExternalDataItemMapper $externalDataItemMapper;

	public function __construct(
		ResourceMapper $resourceMapper,
		ClientMapper $clientMapper,
		ExternalDataItemMapper $externalDataItemMapper
	)
	{
		$this->resourceMapper = $resourceMapper;
		$this->clientMapper = $clientMapper;
		$this->externalDataItemMapper = $externalDataItemMapper;
	}

	public function convertFromOrm(EO_Booking $ormBooking): Booking
	{
		$booking = new Booking();

		$booking
			->setId($ormBooking->getId())
			->setName($ormBooking->getName())
			->setDescription($ormBooking->getDescription())
			->setConfirmed($ormBooking->getIsConfirmed())
			->setDeleted($ormBooking->getIsDeleted())
			->setDatePeriod(
				new DatePeriod(
					$this->createDateTimeFromTimestamp(
						$ormBooking->getDateFrom(),
						$ormBooking->getTimezoneFrom()
					),
					$this->createDateTimeFromTimestamp(
						$ormBooking->getDateTo(),
						$ormBooking->getTimezoneTo()
					)
				)
			)
			->setVisitStatus(BookingVisitStatus::from($ormBooking->getVisitStatus()))
			->setCreatedBy($ormBooking->getCreatedBy())
			->setCreatedAt($ormBooking->getCreatedAt()->getTimestamp())
			->setUpdatedAt($ormBooking->getUpdatedAt()->getTimestamp())
		;

		if ($ormBooking->getIsRecurring())
		{
			$rrule = $ormBooking->getRrule();
			if ($rrule !== null)
			{
				$booking->setRrule($rrule);
			}
		}

		$this->setResourceCollection($booking, $ormBooking);
		$this->setClientCollection($booking, $ormBooking);
		$this->setExternalDataCollection($booking, $ormBooking);
		$this->setNote($booking, $ormBooking);

		return $booking;
	}

	public function convertToOrm(Booking $booking): EO_Booking
	{
		$result = $booking->getId()
			? EO_Booking::wakeUp($booking->getId())
			: BookingTable::createObject();

		$dateFrom = $booking->getDatePeriod()?->getDateFrom();
		$timezoneFrom = $dateFrom?->getTimezone();

		$dateTo = $booking->getDatePeriod()?->getDateTo();
		$timezoneTo = $dateTo?->getTimezone();

		$result
			->setName($booking->getName())
			->setDescription($booking->getDescription())
			->setVisitStatus($booking->getVisitStatus()->value)
			->setIsConfirmed($booking->isConfirmed())
			->setDateFrom($dateFrom?->getTimestamp())
			->setTimezoneFrom($timezoneFrom?->getName())
			->setTimezoneFromOffset($timezoneFrom?->getOffset($dateFrom))
			->setDateTo($dateTo?->getTimestamp())
			->setTimezoneTo($timezoneTo?->getName())
			->setTimezoneToOffset($timezoneTo?->getOffset($dateTo))
			->setCreatedBy($booking->getCreatedBy())
			->setParentId($booking->getParent()?->getId())
			->setUpdatedAt(new DateTime())
		;

		$rrule = $booking->getRrule();
		$isRecurring = (bool)$rrule;
		$result->setIsRecurring($isRecurring);
		if ($isRecurring)
		{
			$result->setRrule($rrule);
			$result->setDateMax(
				(new Rrule($rrule, $booking->getDatePeriod()))->getUntil()->getTimestamp()
			);
		}
		else
		{
			$result->setRrule('');
			$result->setDateMax($booking->getDatePeriod()?->getDateTo()?->getTimestamp());
		}

		return $result;
	}

	private function createDateTimeFromTimestamp(int $timestamp, string $timezone): DateTimeImmutable
	{
		return (new DateTimeImmutable('@' . $timestamp))
			->setTimezone(new DateTimeZone($timezone));
	}

	private function setResourceCollection(Booking $booking, EO_Booking $ormBooking): void
	{
		$resources = [];

		$ormBookingResources = $ormBooking->getResources();
		if (!$ormBookingResources)
		{
			return;
		}

		/**
		 * Sort resource relationship by b_booking_booking_resource.ID in ascending order
		 */
		$ormBookingResources = iterator_to_array($ormBookingResources);
		usort($ormBookingResources, fn($resource1, $resource2) => $resource1->getId() <=> $resource2->getId());

		/** @var EO_BookingResource $ormBookingResource */
		foreach ($ormBookingResources as $ormBookingResource)
		{
			$resources[] = $this->resourceMapper->convertFromOrm($ormBookingResource->getResource());
		}

		$booking->setResourceCollection(new ResourceCollection(...$resources));
	}

	private function setClientCollection(Booking $booking, EO_Booking $ormBooking): void
	{
		$clients = [];

		/** @var EO_Resource $resource */
		$ormClients = $ormBooking->getClients() ?? [];
		foreach ($ormClients as $ormClient)
		{
			$clients[] = $this->clientMapper->convertFromOrm($ormClient);
		}

		$booking->setClientCollection(new ClientCollection(...$clients));
	}

	private function setExternalDataCollection(Booking $booking, EO_Booking $ormBooking): void
	{
		$externalDataItems = [];

		$externalData = $ormBooking->getExternalData() ?? [];
		foreach ($externalData as $externalDataItem)
		{
			$externalDataItems[] = $this->externalDataItemMapper->convertFromOrm($externalDataItem);
		}

		$booking->setExternalDataCollection(new ExternalDataCollection(...$externalDataItems));
	}

	private function setNote(Booking $booking, EO_Booking $ormBooking): void
	{
		$ormNote = $ormBooking->getNote();

		if ($ormNote === null)
		{
			return;
		}

		$booking->setNote($ormNote->getDescription());
	}
}

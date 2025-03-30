<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Service\BookingFeature;
use Bitrix\Booking\Command\Booking\AddBookingCommand;
use Bitrix\Booking\Command\Booking\RemoveBookingCommand;
use Bitrix\Booking\Command\Booking\UpdateBookingCommand;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\BookingProvider;
use Bitrix\Booking\Provider\Params\Booking\BookingFilter;
use Bitrix\Booking\Provider\Params\Booking\BookingSelect;
use Bitrix\Booking\Provider\Params\Booking\BookingSort;
use Bitrix\Booking\Provider\Params\GridParams;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UI\PageNavigation;

class Booking extends BaseController
{
	public function listAction(
		PageNavigation $navigation,
		array $filter = [],
		array $sort = [],
		array $select = [],
		bool $withCounters = false,
		bool $withClientData = false,
		bool $withExternalData = false,
	): Entity\Booking\BookingCollection
	{
		$userId = (int)CurrentUser::get()->getId();
		$provider = new BookingProvider();

		$bookings = $provider->getList(
			new GridParams(
				limit: $navigation->getLimit(),
				offset: $navigation->getOffset(),
				filter: new BookingFilter($filter),
				sort: new BookingSort($sort),
				select: new BookingSelect($select),
			),
			userId: $userId,
		);

		if ($withCounters)
		{
			$provider->withCounters($bookings, $userId);
		}

		if ($withClientData)
		{
			$provider->withClientsData($bookings);
		}

		if ($withExternalData)
		{
			$provider->withExternalData($bookings);
		}

		return $bookings;
	}

	public function getAction(int $id): Entity\Booking\Booking|null
	{
		try
		{
			return (new BookingProvider())->getById(
				userId: (int)CurrentUser::get()->getId(),
				id: $id,
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function getIntersectionsListAction(array $booking): Entity\Booking\BookingCollection|null
	{
		try
		{
			return (new BookingProvider())->getIntersectionsList(
				userId: (int)CurrentUser::get()->getId(),
				booking: Entity\Booking\Booking::mapFromArray($booking),
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	public function addAction(array $booking): Entity\Booking\Booking|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(ErrorBuilder::build('Access denied'));

			return null;
		}

		try
		{
			$booking = Entity\Booking\Booking::mapFromArray($booking);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$addBookingCommand = new AddBookingCommand(
			createdBy: (int)CurrentUser::get()->getId(),
			booking: $booking,
		);

		$result = $addBookingCommand->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getBooking();
	}

	public function addListAction(array $bookingList): Entity\Booking\BookingCollection|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(ErrorBuilder::build('Access denied'));

			return null;
		}

		$collection = new Entity\Booking\BookingCollection();

		foreach ($bookingList as $fields)
		{
			try
			{
				$booking = Entity\Booking\Booking::mapFromArray($fields);
			}
			catch (Exception $exception)
			{
				$booking = null;
			}

			if ($booking === null)
			{
				continue;
			}

			$addBookingCommand = new AddBookingCommand(
				createdBy: (int)CurrentUser::get()->getId(),
				booking: Entity\Booking\Booking::mapFromArray($fields),
			);

			$result = $addBookingCommand->run();
			if ($result->isSuccess() && $result->getBooking())
			{
				$collection->add($result->getBooking());
			}
		}

		return $collection;
	}

	public function updateAction(array $booking): Entity\Booking\Booking|null
	{
		if (!BookingFeature::isFeatureEnabled())
		{
			$this->addError(ErrorBuilder::build('Access denied'));

			return null;
		}

		if (empty($booking['id']))
		{
			$this->addError(ErrorBuilder::build('Booking identifier is not specified.'));

			return null;
		}

		$entity = Container::getBookingRepository()->getById((int)$booking['id']);
		if (!$entity)
		{
			$this->addError(ErrorBuilder::build('Booking has not been found.'));

			return null;
		}

		try
		{
			$booking = Entity\Booking\Booking::mapFromArray([...$entity->toArray(), ...$booking]);
		}
		catch (Exception $exception)
		{
			$this->addError(ErrorBuilder::buildFromException($exception));

			return null;
		}

		$command = new UpdateBookingCommand(
			updatedBy: (int)CurrentUser::get()->getId(),
			booking: $booking,
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getBooking();
	}

	public function deleteAction(int $id): array|null
	{
		$command = new RemoveBookingCommand(
			id: $id,
			removedBy: (int)CurrentUser::get()->getId(),
		);

		$result = $command->run();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}

	public function deleteListAction(array $ids): array
	{
		foreach ($ids as $id)
		{
			$command = new RemoveBookingCommand(
				id: $id,
				removedBy: (int)CurrentUser::get()->getId(),
			);

			$command->run();
		}

		return [];
	}
}

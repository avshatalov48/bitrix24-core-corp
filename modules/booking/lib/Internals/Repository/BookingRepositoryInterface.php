<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Internals\Query\FilterInterface;
use Bitrix\Booking\Internals\Query\SelectInterface;
use Bitrix\Booking\Internals\Query\SortInterface;
use Bitrix\Booking\Entity;

interface BookingRepositoryInterface
{
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		SortInterface|null $sort = null,
		SelectInterface|null $select = null,
	): Entity\Booking\BookingCollection;

	public function getIntersectionsList(
		Entity\Booking\Booking $booking,
		int $limit = 1
	): Entity\Booking\BookingCollection;

	public function getById(int $id, int $userId = 0): Entity\Booking\Booking|null;

	public function getByIdForManager(int $id): Entity\Booking\Booking|null;

	public function save(Entity\Booking\Booking $booking): Entity\Booking\Booking;

	public function remove(int $id): void;
}

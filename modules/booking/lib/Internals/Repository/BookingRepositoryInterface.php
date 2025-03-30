<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\Params\FilterInterface;
use Bitrix\Main\ORM\Query\Query;

interface BookingRepositoryInterface
{
	public function getQuery(): Query;
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		array|null $sort = null,
		array|null $select = null,
		int|null $userId = null,
	): Entity\Booking\BookingCollection;

	public function getIntersectionsList(
		Entity\Booking\Booking $booking,
		int|null $userId = null,
		int $limit = 1
	): Entity\Booking\BookingCollection;

	public function getById(int $id, int $userId = 0): Entity\Booking\Booking|null;

	public function getByIdForManager(int $id): Entity\Booking\Booking|null;

	public function save(Entity\Booking\Booking $booking): int;

	public function remove(int $id): void;
}

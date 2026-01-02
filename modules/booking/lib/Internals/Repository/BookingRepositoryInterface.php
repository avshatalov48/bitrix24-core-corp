<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Provider\Params\FilterInterface;
use Bitrix\Main\ORM\Query\Query;

interface BookingRepositoryInterface
{
	public function getQuery(FilterInterface|null $filter = null): Query;
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
		int|null $limit = null
	): Entity\Booking\BookingCollection;

	public function getById(
		int $id,
		int $userId = 0,
		bool $withCounters = true,
		bool $withClientsData = true,
		bool $withExternalData = true,
	): Entity\Booking\Booking|null;

	public function getByIdForManager(int $id): Entity\Booking\Booking|null;

	public function save(Entity\Booking\Booking $booking): int;

	public function remove(int $id): void;
}

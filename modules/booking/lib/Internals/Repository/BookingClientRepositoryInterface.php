<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Provider\Params\FilterInterface;

interface BookingClientRepositoryInterface
{
	public function getTotalClients(): int;

	public function getTotalNewClientsToday(FilterInterface $filter): int;

	public function link(int $entityId, EntityType $entityType, Entity\Client\ClientCollection $clientCollection): void;

	public function unLink(
		int $entityId,
		EntityType $entityType,
		Entity\Client\ClientCollection $clientCollection,
	): void;

	public function unLinkByFilter(array $filter): void;
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Entity\Resource\Resource;

interface FavoritesRepositoryInterface
{
	public function getList(int $managerId): Favorites;
	public function addPrimary(int $managerId, array $resourceIds): void;
	public function addSecondary(int $managerId, array $resourceIds): void;
	public function removeSecondary(int $managerId, array $resourcesIds): void;
	public function removePrimary(int $managerId, array $resourcesIds): void;
	public function filterSecondary(array $resourceIds, array $primaryResourceIds): array;
	public function pushPrimary(array $resourceIds): void;
}

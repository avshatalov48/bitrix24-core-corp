<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Model\EO_Favorites;
use Bitrix\Booking\Internals\Model\FavoritesTable;

class FavoritesMapper
{
	public function convertFromOrm(EO_Favorites ...$ormFavorites): Favorites
	{
		$favorites = new Favorites();

		$resources = [];
		foreach ($ormFavorites as $ormFavorite)
		{
			$resources[] = (new ResourceMapper())->convertFromOrm($ormFavorite->getResource());
		}

		$resourceCollection = new ResourceCollection(...$resources);

		return $favorites->setResources($resourceCollection);
	}

	public function convertToOrm(Resource $resource, int $managerId): EO_Favorites
	{
		$ormFavoriteResource = FavoritesTable::createObject();
		$ormFavoriteResource->setManagerId($managerId);
		$ormFavoriteResource->setResourceId($resource->getId());

		return $ormFavoriteResource;
	}
}

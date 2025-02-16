<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;
use Bitrix\Booking\Internals\Query;
use Bitrix\Booking\Internals\Query\ResourceType\ResourceTypeFilter;
use Bitrix\Booking\Internals\Query\ResourceType\ResourceTypeSort;
use Bitrix\Booking\Entity;

class ResourceTypeProvider
{
	public function getList(
		int $userId,
		int|null $limit = null,
		int|null $offset = null,
		array|null $filter = null,
		array|null $sort = null,
	): ResourceTypeCollection
	{
		$request = new Query\ResourceType\GetListRequest(
			userId: $userId,
			limit: $limit,
			offset: $offset,
			filter: new ResourceTypeFilter($filter ?? []),
			sort: new ResourceTypeSort($sort ?? []),
		);

		return (new Query\ResourceType\GetListHandler())($request);
	}

	public function getById(int $userId, int $id): Entity\ResourceType\ResourceType|null
	{
		$response = (new Query\ResourceType\GetByIdHandler())($id, $userId);

		return $response->resourceType;
	}
}

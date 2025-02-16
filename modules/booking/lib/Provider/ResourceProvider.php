<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Exception\Resource\ResourceNotFoundException;
use Bitrix\Booking\Internals\Query;
use Bitrix\Booking\Internals\Query\Resource\ResourceFilter;
use Bitrix\Booking\Internals\Query\Resource\ResourceSort;

class ResourceProvider
{
	public function getList(
		int $userId,
		int $limit = null,
		int $offset = null,
		array|null $filter = null,
		array|null $sort = null,
	): ResourceCollection
	{
		$request = new Query\Resource\GetListRequest(
			userId: $userId,
			limit: $limit,
			offset: $offset,
			filter: new ResourceFilter($filter ?? []),
			sort: new ResourceSort($sort ?? []),
		);

		return (new Query\Resource\GetListHandler())($request);
	}

	/**
	 * @throws ResourceNotFoundException
	 */
	public function getById(int $userId, int $resourceId): Query\Resource\GetByIdResponse
	{
		return (new Query\Resource\GetByIdHandler())(
			resourceId: $resourceId,
			userId: $userId,
		);
	}

	public function getTotal(
		array|null $filter = null,
	): int
	{
		$request = new Query\Resource\GetTotalRequest(new ResourceFilter($filter ?? []));

		return (new Query\Resource\GetTotalHandler())($request);
	}
}

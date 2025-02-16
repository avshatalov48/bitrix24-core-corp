<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Exception\Resource\ResourceNotFoundException;
use Bitrix\Booking\Internals\Container;

class GetByIdHandler
{
	public function __invoke(int $resourceId, int $userId): GetByIdResponse
	{
		$resource = Container::getResourceRepository()->getById($resourceId);

		if (!$resource)
		{
			throw new ResourceNotFoundException();
		}

		return new GetByIdResponse(
			resource: $resource,
		);
	}
}

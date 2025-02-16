<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\ResourceType;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Exception\ResourceType\ResourceTypeNotFoundException;

class GetByIdHandler
{
	public function __invoke(int $resourceTypeId, int $userId): GetByIdResponse
	{
		try
		{
			$resourceType = Container::getResourceTypeRepository()->getById($resourceTypeId);

			return new GetByIdResponse(
				resourceType: $resourceType,
			);

		}
		catch (ResourceTypeNotFoundException $e)
		{
			return new GetByIdResponse(
				resourceType: null,
			);
		}
	}
}

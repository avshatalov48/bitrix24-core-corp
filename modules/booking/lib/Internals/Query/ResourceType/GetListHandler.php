<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\ResourceType;

use Bitrix\Booking\Entity\ResourceType\ResourceTypeCollection;
use Bitrix\Booking\Internals\Container;

class GetListHandler
{
	public function __invoke(GetListRequest $request): ResourceTypeCollection
	{
		return Container::getResourceTypeRepository()->getList(
			limit: $request->limit,
			offset: $request->offset,
			filter: $request->filter,
			sort: $request->sort,
		);
	}
}

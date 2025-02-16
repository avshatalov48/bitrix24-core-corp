<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Container;

class GetListHandler
{
	public function __invoke(GetListRequest $request): ResourceCollection
	{
		return Container::getResourceRepository()->getList(
			limit: $request->limit,
			offset: $request->offset,
			filter: $request->filter,
			sort: $request->sort,
		);
	}
}

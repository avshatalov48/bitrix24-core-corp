<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query\Resource;

use Bitrix\Booking\Internals\Container;

class GetTotalHandler
{
	public function __invoke(GetTotalRequest $request): int
	{
		return Container::getResourceRepository()->getTotal(
			filter: $request->filter,
		);
	}
}

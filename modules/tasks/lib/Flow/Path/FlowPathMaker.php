<?php

namespace Bitrix\Tasks\Flow\Path;

use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\Slider\Path\PathMaker;

class FlowPathMaker extends PathMaker
{
	public function makeEntityPath(): string
	{
		return '';
	}

	public function makeEntitiesListPath(): string
	{
		return str_replace('#user_id#', $this->ownerId, RouteDictionary::PATH_TO_FLOWS)
			. $this->queryParams;
	}
}
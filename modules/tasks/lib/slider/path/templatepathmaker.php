<?php

namespace Bitrix\Tasks\Slider\Path;

use Bitrix\Tasks\Internals\Routes\RouteDictionary;

class TemplatePathMaker extends PathMaker
{
	public function makeEntityPath(): string
	{
		$path = str_replace(
			['#user_id#', '#action#', '#template_id#'],
			[$this->ownerId, $this->action, $this->entityId],
			RouteDictionary::PATH_TO_USER_TEMPLATE
		);

		return $path . $this->queryParams;
	}

	public function makeEntitiesListPath(): string
	{
		return str_replace(['#user_id#'], [$this->ownerId], RouteDictionary::PATH_TO_USER_TEMPLATES_LIST);
	}
}
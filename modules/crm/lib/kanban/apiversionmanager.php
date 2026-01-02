<?php

namespace Bitrix\Crm\Kanban;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;

final class ApiVersionManager
{
	use Singleton;

	public function getVersion(): int
	{
		return Option::get('crm', 'kanban_api_version', 2);
	}

	public function setVersion(int $version): void
	{
		Option::set('crm', 'kanban_api_version', $version);
	}
}

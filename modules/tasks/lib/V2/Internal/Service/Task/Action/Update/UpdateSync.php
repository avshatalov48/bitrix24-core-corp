<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use CDavExchangeTasks;

class UpdateSync
{
	public function __invoke(array $fields, array $sourceTaskData): void
	{
		if (!ModuleManager::isModuleInstalled('dav'))
		{
			return;
		}

		if (!Loader::includeModule('dav'))
		{
			return;
		}

		if (!CDavExchangeTasks::IsExchangeEnabled())
		{
			return;
		}

		(new Async\Message\UpdateDavSync($fields, $sourceTaskData))->sendByTaskId((int)$sourceTaskData['ID']);
	}
}

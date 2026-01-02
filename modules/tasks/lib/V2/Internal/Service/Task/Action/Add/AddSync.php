<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use CDavExchangeTasks;

class AddSync
{
	use ConfigTrait;

	public function __invoke(array $fields): void
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

		(new Async\Message\AddDavSync($fields))->sendByTaskId($fields['ID']);
	}
}

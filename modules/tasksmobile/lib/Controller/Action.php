<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\StatefulList\BaseAction;

class Action extends BaseAction
{
	protected function checkModules(): void
	{
		if (
			!Loader::includeModule('tasks')
			|| !Loader::includeModule('tasksmobile')
		)
		{
			$this->errorCollection[] = new Error('Required modules "tasks" or "tasksmobile" was not found');
		}
	}
}

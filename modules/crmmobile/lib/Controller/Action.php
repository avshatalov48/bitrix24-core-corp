<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Mobile\UI\StatefulList\BaseAction;

class Action extends BaseAction
{
	protected function checkModules(): void
	{
		if (
			!Loader::includeModule('crm')
			|| !Loader::includeModule('crmmobile')
		)
		{
			$this->errorCollection[] = new Error('Required modules crm or crmmobile was not found');
		}
	}
}

<?php

namespace Bitrix\HumanResources\Rest\View;

use Bitrix\Main\Engine\Controller;
use Bitrix\Rest\RestException;
use Bitrix\Rest\Integration\ViewManager;
use Bitrix\HumanResources\Rest\Controllers;
use Bitrix\HumanResources\Rest\View;

final class HumanResourcesViewManager extends ViewManager
{
	public function getView(Controller $controller)
	{
		if ($controller instanceof Controllers\Role)
		{
			return new View\Role();
		}

		if ($controller instanceof Controllers\Structure)
		{
			return new View\Structure();
		}

		if ($controller instanceof Controllers\Structure\Node)
		{
			return new View\Node();
		}

		if ($controller instanceof Controllers\Structure\Member)
		{
			return new View\Member();
		}

		throw new RestException('Unknown object ' . $controller::class);
	}
}
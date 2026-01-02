<?php

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Trait;

use Bitrix\Main\Engine\Controller;

trait ActionTrait
{
	protected function getControllerAction(Controller $controller): string
	{
		$action = $controller->getRequest()->get('action');
		$parts = explode('.', $action);
		$method = end($parts);

		return $method . 'Action';
	}
}
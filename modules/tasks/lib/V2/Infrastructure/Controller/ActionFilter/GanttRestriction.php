<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\V2\Internal\DI\Container;

class GanttRestriction extends Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		$tariffService = Container::getInstance()->getTariffService();
		$userId = (int)CurrentUser::get()->getId();

		if ($tariffService->canCreateDependence($userId))
		{
			return null;
		}

		$this->addError(new Error('Action is not allowed for your tariff plan.'));

		return new EventResult(type: EventResult::ERROR, handler: $this);
	}
}

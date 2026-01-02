<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\V2\Internal\DI\Container;

class OnlyAllowedPortal extends Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		$host = Container::getInstance()->getUrlService()->getHost();
		$featureService = Container::getInstance()->getFeatureService();

		if ($featureService->isHostAllowed($host))
		{
			return null;
		}

		$this->addError(new Error('Action is not allowed for this portal'));

		return new EventResult(type: EventResult::ERROR, handler: $this);
	}
}

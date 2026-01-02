<?php

namespace Bitrix\Crm\RepeatSale\Service\Handler;

use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Crm\Traits\Singleton;

final class Factory
{
	use Singleton;

	public function getHandler(
		HandlerType $handlerType,
		?string $segmentCode = null,
		?Context $context = null
	): ?BaseHandler
	{
		if ($handlerType === HandlerType::SystemHandler && $segmentCode)
		{
			return new SystemHandler($segmentCode, $context);
		}

		if ($handlerType === HandlerType::ConfigurableHandler)
		{
			return new ConfigurableHandler($context);
		}

		return null;
	}
}

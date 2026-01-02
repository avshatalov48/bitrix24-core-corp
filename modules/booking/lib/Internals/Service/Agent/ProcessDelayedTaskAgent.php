<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Agent;

use Bitrix\Booking\Internals\Container;

class ProcessDelayedTaskAgent
{
	public static function execute(): string
	{
		Container::getDelayedTaskService()->processPending();

		return '\\' . self::class . '::execute();';
	}
}

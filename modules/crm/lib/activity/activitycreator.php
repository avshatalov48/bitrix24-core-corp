<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\Provider\EventRegistrarInterface;
use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventRegistrar;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class ActivityCreator
{
	public static function create(ChannelEventRegistrar $eventRegistrar): Result
	{
		$activityProvider = ProviderFactory::getProviderInstanceByChannelCode(
			$eventRegistrar->getChannelCode()
		);

		if ($activityProvider === null)
		{
			return (new Result())->addError(new Error('Activity provider not found'));
		}

		if (!($activityProvider instanceof EventRegistrarInterface))
		{
			return (new Result())->addError(new Error('Unsupported activity provider'));
		}

		return $activityProvider->createActivityFromChannelEvent($eventRegistrar);
	}
}

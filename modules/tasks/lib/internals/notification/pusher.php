<?php

namespace Bitrix\Tasks\Internals\Notification;

use Bitrix\Main\Application;

class Pusher implements PusherInterface
{
	public const PUSHER_JOB_PRIORITY =  Application::JOB_PRIORITY_LOW - 3; // DON'T CHANGE!!!

	public function push(ProviderCollection $providers): void
	{
		foreach ($providers as $provider)
		{
			Application::getInstance()->addBackgroundJob(
				[$this, 'process'],
				[$provider],
				self::PUSHER_JOB_PRIORITY
			);
		}
	}

	public function process(ProviderInterface $notificationProvider)
	{
		$notificationProvider->pushMessages();
	}
}
<?php

namespace Bitrix\BIConnector\Integration\Pull\Notifyer;

use Bitrix\Main\Loader;

class PullNotifyer implements NotifyerInterface
{
	public function __construct()
	{
		if (!Loader::includeModule('pull'))
		{
			throw new \Bitrix\Main\SystemException('Cannot init PullNotifyer. Module "pull" is not installed.');
		}
	}

	public function notifyByTag(string $tag, string $command, array $params = []): void
	{
		\CPullWatch::AddToStack($tag, [
			'module_id' => 'biconnector',
			'command' => $command,
			'params' => $params,
		]);
	}
}

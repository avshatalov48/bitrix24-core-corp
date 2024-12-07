<?php

namespace Bitrix\BIConnector\Integration\Pull\Notifyer;

class NullNotifyer implements NotifyerInterface
{
	public function notifyByTag(string $tag, string $command, array $params = []): void
	{
		// Nothing do
	}
}

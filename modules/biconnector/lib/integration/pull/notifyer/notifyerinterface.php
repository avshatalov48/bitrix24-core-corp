<?php

namespace Bitrix\BIConnector\Integration\Pull\Notifyer;

interface NotifyerInterface
{
	public function notifyByTag(string $tag, string $command, array $params = []): void;
}

<?php

namespace Bitrix\Transformer\Log;

use Bitrix\Main\Diag\Logger;

final class AddMessage2LogLogger extends Logger
{
	protected function logMessage(string $level, string $message): void
	{
		AddMessage2Log($message, 'transformer');
	}
}

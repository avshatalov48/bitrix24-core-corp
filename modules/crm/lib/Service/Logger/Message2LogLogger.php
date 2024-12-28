<?php

namespace Bitrix\Crm\Service\Logger;

use Bitrix\Main\Application;

class Message2LogLogger extends \Bitrix\Main\Diag\Logger
{
	public function __construct(
		private readonly string $loggerId = '',
		private readonly int $traceDepthLevel = 0
	)
	{
	}

	protected function logMessage(string $level, string $message)
	{
		$host = Application::getInstance()->getContext()->getServer()->getHttpHost();
		$loggerId = $this->loggerId ? ($this->loggerId . ' ') : '';
		AddMessage2Log("{$loggerId}{$host} {$level} {$message}", 'crm', $this->traceDepthLevel);
	}
}

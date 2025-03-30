<?php

namespace Bitrix\Crm\Service\Logger;

use Bitrix\Crm\Service\Logger\Model\LogTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Application;

class DbLogger extends \Bitrix\Main\Diag\Logger
{
	public function __construct(private readonly string $loggerId, private readonly int $hoursTtl)
	{
	}

	protected function logMessage(string $level, string $message): void
	{
		$validTo = new DateTime();
		$validTo->add('+' . $this->hoursTtl . ' hours');
		$validTo->disableUserTime();

		$context = $this->context;
		unset($context['date'], $context['host']);

		$url = Application::getInstance()->getContext()->getRequest()->getRequestUri();

		LogTable::add([
			'LOGGER_ID' => $this->loggerId,
			'LOG_LEVEL' => $level,
			'VALID_TO' => $validTo,
			'MESSAGE' => $message,
			'CONTEXT' => $context,
			'URL' => (string)$url,
		]);

		$this->addCleanerAgent($validTo);
	}

	private function addCleanerAgent(DateTime $agentExecTime): void
	{
		static $cleanerAgentAdded = [];
		if (isset($cleanerAgentAdded[$this->loggerId]))
		{
			return;
		}
		$cleanerAgentAdded[$this->loggerId] = true;

		\CAgent::AddAgent(
			\Bitrix\Crm\Agent\Logger\CleanerAgent::getAgentString(),
			'crm',
			'N',
			60 * 60 * 24,
			'',
			'Y',
			$agentExecTime->toString(),
			100,
			false,
			false
		);
	}
}

<?php
namespace Bitrix\ImConnector;

/**
 * Class Agent
 * @package Bitrix\ImConnector
 */
class Agent
{
	/**
	 * @param int $step
	 * @param int $line
	 * @return string
	 */
	public static function agentDisconnectConnectorVK($step = 0, $line = 0)
	{
		$connector = 'vkgroup';
		$statuses = array();
		$maxNumberAttempts = 10;

		$statusesRaw = Status::getInstanceAllLine($connector);

		foreach ($statusesRaw as $status)
		{
			if($status->getActive())
			{
				$statuses[] = $status->getLine();
			}
			else
			{
				Status::delete($connector, $status->getLine());
				Connector::cleanCacheConnector($status->getLine(), Connector::getCacheIdConnector($status->getLine(), $connector));
			}
		}

		if(!empty($statuses))
		{
			if(empty($line) || empty($statuses[$line]))
			{
				$line = reset($statuses);
			}

			$connectorOutput = new Output($connector, $line, true);

			$rawDelete = $connectorOutput->deleteConnector();

			if($rawDelete->isSuccess() || $step > $maxNumberAttempts)
			{
				Status::delete($connector, $line);

				Connector::cleanCacheConnector($line, Connector::getCacheIdConnector($line, $connector));

				$step = 0;
				$line = 0;
			}
			else
			{
				$step++;
			}

			return '\\Bitrix\\ImConnector\\Agent::agentDisconnectConnectorVK(' . $step . ', ' . $line . ');';
		}
	}
}
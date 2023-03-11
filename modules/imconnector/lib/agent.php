<?php
namespace Bitrix\ImConnector;

use Bitrix\ImOpenLines\Im;
use Bitrix\ImOpenLines\Session;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Class Agent
 *
 * @package Bitrix\ImConnector
 */
class Agent
{
	/**
	 * @param int $step
	 * @param int $line
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
				Status::delete($connector, (int)$status->getLine());
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
				Status::delete($connector, (int)$line);

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

	/**
	 * @return string
	 */
	public static function notifyUndelivered(): string
	{
		if (!Loader::includeModule('imopenlines'))
		{
			return __METHOD__ . '();';
		}

		$connection = Application::getInstance()->getConnection();

		$query = '
			SELECT s.*
			FROM b_imopenlines_session s
			INNER JOIN (
				SELECT MAX(MESSAGE_ID), CHAT_ID 
				FROM b_imconnectors_delivery_mark
				WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
				GROUP BY CHAT_ID 
			) m ON m.CHAT_ID = s.CHAT_ID
			WHERE s.STATUS = ' . Session::STATUS_OPERATOR . '
		';

		$result = $connection->query($query);

		while ($session = $result->fetch())
		{
			$sessionObj = new Session();
			if (
				$sessionObj->load($session)
				&& $sessionObj->getData('STATUS') > Session::STATUS_CLIENT
				&& $sessionObj->getData('STATUS') < Session::STATUS_CLOSE
			)
			{
				$sessionObj->update(['STATUS' => Session::STATUS_CLIENT]);

				$connection->query('DELETE FROM b_imconnectors_delivery_mark WHERE CHAT_ID=' . (int)$session['CHAT_ID']);

				Im::addMessage([
					'TO_CHAT_ID' => $session['CHAT_ID'],
					'MESSAGE' => Loc::getMessage('IMCONNECTOR_YOUR_MESSAGE_NOT_DELIVERED'),
					'SYSTEM' => 'Y',
				]);
			}

		}

		return __METHOD__ . '();';
	}
}

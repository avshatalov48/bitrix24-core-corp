<?php
namespace Bitrix\ImOpenLines\Integrations\Report\Statistics;

use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Session;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Event;
use Bitrix\Main\Type\DateTime;

/**
 * Class EventHandler
 * @package Bitrix\ImOpenLines\Integrations\Report\Statistics
 */
class EventHandler
{
	private static $sessionStatesCollection = array();
	private static $newCreateSessionIdsWithAttachingToAll = array();

	/**
	 * @param Event $event
	 */
	public static function onSessionStart(Event $event)
	{
		$parameters = $event->getParameters();
		/** @var Session $session */
		$session = $parameters['RUNTIME_SESSION'];


	}


	/**
	 * @param Event $event
	 */
	public static function onSessionFinish(Event $event)
	{
		$parameters = $event->getParameters();
		/** @var Session $session */
		$session = $parameters['RUNTIME_SESSION'];
		$params['DATE'] = $session->getData('DATE_CREATE');
		$params['OPEN_LINE_ID'] = $session->getData('CONFIG_ID');
		$params['SOURCE_ID'] = $session->getData('SOURCE');
		$params['OPERATOR_ID'] = $session->getData('OPERATOR_ID');
		$params['MARK'] = $session->getData('VOTE');

		if ($params['MARK'] == 0)
		{
			Manager::addToQueue($session->getData('ID'), Manager::MARK_STATISTIC_KEY, $params);
		}

	}


	/**
	 * @param Event $event
	 */
	public static function onSessionCreate(Event $event)
	{
		$parameters = $event->getParameters();
		$configId = $parameters['fields']['CONFIG_ID'];
		$sessionId = $parameters['id'];
		$params['DATE'] = $parameters['fields']['DATE_CREATE'];
		$params['OPEN_LINE_ID'] = $parameters['fields']['CONFIG_ID'];
		$params['SOURCE_ID'] = $parameters['fields']['SOURCE'];
		$params['OPERATOR_ID'] = $parameters['fields']['OPERATOR_ID'];
		$params['STATUS'] = Dialog::STATUS_NO_PRECESSED;

		$configManager = new Config();
		$config = $configManager->get($configId);

		switch ($config['QUEUE_TYPE'])
		{
			case Config::QUEUE_TYPE_ALL:
				$queue = $config['QUEUE'];
				foreach ($queue as $operatorIdFromQueue)
				{
					$params['OPERATOR_ID'] = $operatorIdFromQueue;
					Manager::addToQueue($sessionId, Manager::DIALOG_CREATE_STATISTIC_KEY, $params);
				}
				self::$newCreateSessionIdsWithAttachingToAll[$sessionId] = $sessionId;
				break;
			case Config::QUEUE_TYPE_EVENLY:
			case Config::QUEUE_TYPE_STRICTLY:
				break;
		}
	}

	/**
	 * @param Event $event
	 */
	public static function onSessionBeforeUpdate(Event $event)
	{
		$parameters = $event->getParameters();
		if (!isset(self::$newCreateSessionIdsWithAttachingToAll[$parameters['id']['ID']]) && !empty($parameters['fields']['OPERATOR_ID']))
		{
			$query = new Query(SessionTable::getEntity());
			$query->addSelect('DATE_CREATE');
			$query->addSelect('OPERATOR_ID');
			$query->addSelect('CONFIG_ID');
			$query->addSelect('SOURCE');
			$query->where('ID', $parameters['id']['ID']);
			$result = $query->exec()->fetchRaw();
			self::$sessionStatesCollection[(int)$parameters['id']['ID']] = $result;
		}

	}

	/**
	 * @param Event $event
	 */
	public static function onSessionUpdate(Event $event)
	{
		$parameters = $event->getParameters();
		if (!empty($parameters['fields']['OPERATOR_ID']) && isset(self::$sessionStatesCollection[(int)$parameters['id']['ID']]))
		{
			$beforeUpdateSessionState = self::$sessionStatesCollection[(int)$parameters['id']['ID']];
			if ($beforeUpdateSessionState['OPERATOR_ID'] != $parameters['fields']['OPERATOR_ID'])
			{
				$sessionId = $parameters['id']['ID'];
				$params['DATE'] = new DateTime($beforeUpdateSessionState['DATE_CREATE'], 'Y-m-d H:i:s');
				$params['OPEN_LINE_ID'] = $beforeUpdateSessionState['CONFIG_ID'];
				$params['SOURCE_ID'] = $beforeUpdateSessionState['SOURCE'];
				$params['OPERATOR_ID'] = $parameters['fields']['OPERATOR_ID'];
				$params['STATUS'] = Dialog::STATUS_NO_PRECESSED;
				Manager::addToQueue($sessionId, Manager::DIALOG_CREATE_STATISTIC_KEY, $params);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public static function onSessionVote(Event $event)
	{
		$parameters = $event->getParameters();



		$sessionData = $parameters['SESSION_DATA'];
		$newVote = $parameters['VOTE'];
		$params['DATE'] = $sessionData['DATE_CREATE'];
		$params['OPEN_LINE_ID'] = $sessionData['CONFIG_ID'];
		$params['SOURCE_ID'] = $sessionData['SOURCE'];
		$params['OPERATOR_ID'] = $sessionData['OPERATOR_ID'];

		if ($sessionData['VOTE'] == 0 && $params['CLOSED'] == 'Y' || $sessionData['VOTE'] != 0)
		{
			$params['OLD_MARK'] = $sessionData['VOTE'];
		}
		$params['MARK'] = $newVote;
		Manager::addToQueue($sessionData['ID'], Manager::MARK_STATISTIC_KEY, $params);
	}


	/**
	 * @param Event $event
	 */
	public static function onChatAnswer(Event $event)
	{
		$parameters = $event->getParameters();

		$userId = $parameters['USER_ID'];
		if ($userId)
		{
			/** @var Session $session */
			$session = $parameters['RUNTIME_SESSION'];

			$params['DATE'] = $session->getData('DATE_CREATE');
			$params['OPEN_LINE_ID'] = $session->getData('CONFIG_ID');
			$params['SOURCE_ID'] = $session->getData('SOURCE');
			$params['OPERATOR_ID'] = $session->getData('OPERATOR_ID');
			$params['IS_CHAT_CREATED_NEW'] = $session->getData('IS_FIRST');

			Manager::addToQueue($session->getData('ID'), Manager::TREATMENT_STATISTIC_KEY, $params);
			Manager::addToQueue($session->getData('ID'), Manager::TREATMENT_BY_HOUR_STATISTIC_KEY, $params);


			$params['DATE'] = $session->getData('DATE_CREATE');
			$params['OPEN_LINE_ID'] = $session->getData('CONFIG_ID');
			$params['SOURCE_ID'] = $session->getData('SOURCE');
			$params['OPERATOR_ID'] = $userId;
			$params['STATUS'] = Dialog::STATUS_ANSWERED;
			$params['SECS_TO_ANSWER'] = $session->getData('TIME_ANSWER');
			Manager::addToQueue($session->getData('ID'), Manager::DIALOG_ANSWER_STATISTIC_KEY, $params);
		}

	}

	/**
	 * @param Event $event
	 */
	public static function onChatSkip(Event $event)
	{
		$parameters = $event->getParameters();

		$userId = $parameters['USER_ID'];
		if ($userId)
		{
			/** @var Session $session */
			$session = $parameters['RUNTIME_SESSION'];
			$params['DATE'] = $session->getData('DATE_CREATE');
			$params['OPEN_LINE_ID'] = $session->getData('CONFIG_ID');
			$params['SOURCE_ID'] = $session->getData('SOURCE');
			$params['OPERATOR_ID'] = $userId;
			$params['STATUS'] = Dialog::STATUS_SKIPPED;
			Manager::addToQueue($session->getData('ID'), Manager::DIALOG_SKIP_STATISTIC_KEY, $params);
		}
	}

}
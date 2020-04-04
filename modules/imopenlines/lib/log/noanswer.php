<?php
namespace Bitrix\ImOpenLines\Log;

use \Bitrix\ImOpenLines\Queue,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\LogTable;
use \Bitrix\Im\User;

class NoAnswer
{
	/**
	 * @param $params
	 * @throws \Exception
	 */
	public static function add($params)
	{
		if(defined('IMOPENLINES_LOG_NO_ANSWER'))
		{
			$parsedUserCode = Session\Common::parseUserCode($params['USER_CODE']);

			$lineId = $parsedUserCode['CONFIG_ID'];
			$connectorId = $params['SOURCE'];
			$sessionId = $params['SESSION_ID'];
			$type = 'No Answer';

			$queueHistory = $params['QUEUE_HISTORY'];

			$users = array();

			$res = Queue::getList(array(
				'select' => Array('ID', 'USER_ID', 'IS_ONLINE_CUSTOM', 'LAST_ACTIVITY_DATE', 'LAST_ACTIVITY_DATE_EXACT'),
				'filter' => Array('=CONFIG_ID' => $params['CONFIG_ID'])
			));

			while($queueUser = $res->fetch())
			{
				$users[$queueUser['USER_ID']] = array(
					'FULL_NAME' => User::getInstance($queueUser['USER_ID'])->getFullName(),
					'ACTIVE' => User::getInstance($queueUser['USER_ID'])->isActive(),
					'ABSENT' => User::getInstance($queueUser['USER_ID'])->isAbsent(),
					'IS_ONLINE' => $queueUser['IS_ONLINE_CUSTOM'],
					'ACTIVE_STATUS_BY_TIMEMAN' => Queue::getActiveStatusByTimeman($queueUser['USER_ID']),
					'LAST_ACTIVITY_DATE' => $queueUser['LAST_ACTIVITY_DATE']->toString(),
					'LAST_ACTIVITY_DATE_EXACT' => $queueUser['LAST_ACTIVITY_DATE_EXACT']
				);
			}

			$data = array(
				'QUEUE_HISTORY' => $queueHistory,
				'USERS' => $users
			);

			LogTable::add(array(
				'LINE_ID' => $lineId,
				'CONNECTOR_ID' => $connectorId,
				'SESSION_ID' => $sessionId,
				'TYPE' => $type,
				'DATA' => $data,
			));
		}
	}
}
<?php
namespace Bitrix\ImOpenLines\Tools;

use \Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

use \Bitrix\Im\Model\MessageTable;

use \Bitrix\Main\Type\DateTime;

/**
 * Class Correction
 * @package Bitrix\ImOpenLines\Tools
 */
class Correction
{
	/**
	 * The correct status is set for sessions that are closed.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function setStatusClosedSessions()
	{
		$result = [];

		$sessionManager = SessionTable::getList([
			'select'  => [
				'ID',
				'CHECK_SESSION_ID' => 'CHECK.SESSION_ID'
			],
			'filter'  => [
				'<STATUS' => Session::STATUS_CLOSE,
				'CLOSED' => 'Y'
			],
		]);

		while ($session = $sessionManager->fetch())
		{
			$resultSessionUpdate = SessionTable::update($session['ID'], ['STATUS' => Session::STATUS_CLOSE]);

			if($resultSessionUpdate->isSuccess())
			{
				if($session['CHECK_SESSION_ID'] > 0)
				{
					SessionCheckTable::delete($session['CHECK_SESSION_ID']);
				}

				$result[] = $session['ID'];
			}
		}

		return $result;
	}

	/**
	 * Marks the date of the closing of the session and closes the old session.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function closeOldSession()
	{
		$result = [
				'CLOSE' => [],
				'UPDATE' => []
		];

		$sessionCheckManager = SessionCheckTable::getList([
			'select'  => [
				'SESSION_ID',
				'CHAT_ID' => 'SESSION.CHAT_ID',
				'LAST_MESSAGE_ID' => 'SESSION.CHAT.LAST_MESSAGE_ID'
			],
			'filter'  => [
				'=DATE_CLOSE' => null
			]
		]);

		$oldCloseTime =  new DateTime();
		$closeTime = new DateTime();
		$oldCloseTime->add('-30 DAY');
		$closeTime->add('30 DAY');

		while ($sessionCheck = $sessionCheckManager->fetch())
		{
			$message = MessageTable::getById($sessionCheck['LAST_MESSAGE_ID'])->fetch();
			if(!empty($message))
			{
				if($message['DATE_CREATE']->getTimestamp() < $oldCloseTime->getTimestamp())
				{
					$chat = new Chat($sessionCheck['CHAT_ID']);

					$chat->dismissedOperatorFinish();

					$result['CLOSE'][] = $sessionCheck['SESSION_ID'];
				}
				else
				{
					$resultSessionUpdate = SessionCheckTable::update($sessionCheck['SESSION_ID'], ['DATE_CLOSE' => $closeTime]);

					if($resultSessionUpdate->isSuccess())
					{
					$result['UPDATE'][] = $sessionCheck['SESSION_ID'];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Fix sessions that have lost the consistency of the data structure. And closing old sessions.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function repairBrokenSessions()
	{
		$result = [
			'CLOSE' => [],
			'UPDATE' => []
		];

		$sessionManager = SessionTable::getList([
			'select'  => [
				'ID',
				'CHAT_ID',
				'LAST_MESSAGE_ID' => 'CHAT.LAST_MESSAGE_ID'
			],
			'filter'  => [
				'!=CLOSED' => 'Y',
				'=CHECK.SESSION_ID' => null
			]
		]);

		$oldCloseTime =  new DateTime();
		$closeTime = new DateTime();
		$oldCloseTime->add('-30 DAY');
		$closeTime->add('30 DAY');

		while ($session = $sessionManager->fetch())
		{
			$message = MessageTable::getById($session['LAST_MESSAGE_ID'])->fetch();
			if(!empty($message))
			{
				if($message['DATE_CREATE']->getTimestamp() < $oldCloseTime->getTimestamp())
				{
					$chat = new Chat($session['CHAT_ID']);

					$chat->dismissedOperatorFinish();

					$result['CLOSE'][] = $session['ID'];
				}
				else
				{
					$resultSessionUpdate = SessionCheckTable::add(['SESSION_ID' => $session['ID'], 'DATE_CLOSE' => $closeTime]);

					if($resultSessionUpdate->isSuccess())
					{
						$result['UPDATE'][] = $session['ID'];
					}
				}
			}
		}

		return $result;
	}
}
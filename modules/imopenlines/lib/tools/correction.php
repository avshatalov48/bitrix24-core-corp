<?php
namespace Bitrix\ImOpenLines\Tools;

use \Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Queue,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

use \Bitrix\Im,
	\Bitrix\Im\Model\MessageTable;

use \Bitrix\Main\ORM,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Application,
	\Bitrix\Main\Type\DateTime;

/**
 * Class Correction
 * @package Bitrix\ImOpenLines\Tools
 */
class Correction
{
	/**
	 * @return int
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function getCountBrokenSessions()
	{
		$connection = Application::getConnection();

		$sql = 'SELECT
				   count(*) AS COUNT
			FROM
				 b_imopenlines_session
			WHERE
				0 = (
					SELECT count(*)
					FROM b_imopenlines_session_check
					WHERE b_imopenlines_session_check.SESSION_ID = b_imopenlines_session.ID
					) AND
				b_imopenlines_session.CLOSED !=\'Y\'';

		$raw = $connection->query($sql);

		$result = $raw->fetch()['COUNT'];

		if(empty($result) || !is_numeric($result))
		{
			$result = 0;
		}

		return $result;
	}

	/**
	 * Fix sessions that have lost the consistency of the data structure. And closing old sessions.
	 *
	 * @param bool $correction
	 * @param bool $closeDay
	 * @param int $limit
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function repairBrokenSessions($correction = true, $closeDay = false, $limit = 0)
	{
		$result = [
			'CLOSE' => [],
			'UPDATE' => []
		];

		$closeDay = intval($closeDay);
		if($closeDay<=0)
		{
			$closeDay = false;
		}

		$query = new ORM\Query\Query(SessionTable::getEntity());
		$selectFields = [
			'ID',
			'CHAT_ID',
			'STATUS',
			'OPERATOR_ID'
		];

		if (Loader::includeModule('im'))
		{
			$selectFields['LAST_MESSAGE_ID'] = 'CHAT.LAST_MESSAGE_ID';
		}

		$query->setSelect($selectFields);
		$query->setFilter([
			'!=CLOSED' => 'Y',
			'=CHECK.SESSION_ID' => null
		]);
		if(!empty($limit))
		{
			$query->setLimit($limit);
		}
		$sessionManager = $query->exec();

		$oldCloseTime = new DateTime();
		$closeTime = (new DateTime())->add('30 DAY');
		if($closeDay)
		{
			$oldCloseTime->add('-' . $closeDay . ' DAY');
		}

		while ($session = $sessionManager->fetch())
		{
			$message = 0;

			if(!empty($session['LAST_MESSAGE_ID']) && Loader::includeModule('im'))
			{
				$message = MessageTable::getById($session['LAST_MESSAGE_ID'])->fetch();
			}

			if (empty($message) || ($closeDay && $message['DATE_CREATE'] instanceof DateTime && $message['DATE_CREATE']->getTimestamp() < $oldCloseTime->getTimestamp()))
			{
				if($correction)
				{
					$chat = new Chat($session['CHAT_ID']);

					$chat->dismissedOperatorFinish();
				}

				$result['CLOSE'][] = $session['ID'];
			}
			else
			{
				if($correction)
				{
					$addFields = [
						'SESSION_ID' => $session['ID'],
						'DATE_CLOSE' =>  $closeTime
					];

					if(
						$session['STATUS'] < Session::STATUS_ANSWER ||
						empty($session['OPERATOR_ID']) ||
						!Queue::isRealOperator($session['OPERATOR_ID']) ||
						!(Im\User::getInstance($session['OPERATOR_ID'])->isActive()))
					{
						$addFields['DATE_QUEUE'] = new DateTime();
					}

					$resultSessionUpdate = SessionCheckTable::add($addFields);

					if ($resultSessionUpdate->isSuccess())
					{
						$result['UPDATE'][] = $session['ID'];
					}
				}
				else
				{
					$result['UPDATE'][] = $session['ID'];
				}
			}
		}

		return $result;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function getCountSessionsThatNotShown()
	{
		$connection = Application::getConnection();

		$sql = 'SELECT
				   count(*) AS COUNT
			FROM
				 b_imopenlines_session_check,
				 b_imopenlines_session
			WHERE
				0 = (
					SELECT
						   count(*)
					FROM
						 b_im_relation
					WHERE
						b_im_relation.CHAT_ID = b_imopenlines_session.CHAT_ID AND
						b_im_relation.USER_ID != b_imopenlines_session.USER_ID
					) AND
				0 < (
					SELECT count(*)
					FROM b_imopenlines_session_check
					WHERE b_imopenlines_session_check.SESSION_ID = b_imopenlines_session.ID
					) AND
				b_imopenlines_session.ID = b_imopenlines_session_check.SESSION_ID AND
				b_imopenlines_session_check.DATE_QUEUE IS NULL AND
				b_imopenlines_session.CLOSED !=\'Y\'';

		$raw = $connection->query($sql);

		$result = $raw->fetch()['COUNT'];

		if(empty($result) || !is_numeric($result))
		{
			$result = 0;
		}

		return $result;
	}

	/**
	 * Get a list of sessions that are not shown to any operator and are not distributed.
	 *
	 * @param int $limit
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function setSessionsThatNotShown($limit = 0)
	{
		$result = [];

		$connection = Application::getConnection();

		$sql = 'SELECT
		   b_imopenlines_session.ID AS SESSION_ID,
		   b_im_chat.LAST_MESSAGE_ID AS LAST_MESSAGE_ID,
		   b_im_chat.ID AS CHAT_ID,
		   (
			   SELECT b_im_message.DATE_CREATE
			   FROM b_im_message
			   WHERE b_im_message.CHAT_ID = b_imopenlines_session.CHAT_ID AND
					 b_im_message.AUTHOR_ID != 0
			   ORDER BY b_im_message.DATE_CREATE DESC
			   LIMIT 1
			   ) DATE_SEND_LAST_MESSAGE
			FROM
				 b_imopenlines_session_check,
				 b_imopenlines_session,
				 b_im_chat,
				 b_imopenlines_config
			WHERE
				0 = (
					SELECT
						   count(*)
					FROM
						 b_im_relation
					WHERE
						b_im_relation.CHAT_ID = b_imopenlines_session.CHAT_ID AND
						b_im_relation.USER_ID != b_imopenlines_session.USER_ID
					) AND
				0 < (
					SELECT count(*)
					FROM b_imopenlines_session_check
					WHERE b_imopenlines_session_check.SESSION_ID = b_imopenlines_session.ID
					) AND
				b_imopenlines_session.ID = b_imopenlines_session_check.SESSION_ID AND
				b_imopenlines_session_check.DATE_QUEUE IS NULL AND
				b_imopenlines_session.CLOSED !=\'Y\' AND
				b_im_chat.ID = b_imopenlines_session.CHAT_ID AND
				b_imopenlines_session.CONFIG_ID = b_imopenlines_config.ID
			ORDER BY
				DATE_SEND_LAST_MESSAGE DESC';

		if(!empty($limit))
		{
			$sql = $sql . '
			LIMIT 10';
		}


		$raw = $connection->query($sql);

		while ($value = $raw->fetch())
		{
			$result[] = $value;
		}

		return $result;
	}

	/**
	 * Fix sessions that are not displayed to any operator and will not be allocated.
	 *
	 * @param bool $correction
	 * @param bool $closeDay
	 * @param int $limit
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function repairSessionsThatNotShown($correction = true, $closeDay = false, $limit = 0)
	{
		$result = [
			'CLOSE' => [],
			'UPDATE' => []
		];

		if(is_numeric($closeDay) && $closeDay<=0)
		{
			$closeDay = false;
		}

		$oldCloseTime = new DateTime();
		$queueTime = new DateTime();
		if($closeDay)
		{
			$oldCloseTime->add('-' . $closeDay . ' DAY');
		}

		$sessions = self::setSessionsThatNotShown($limit);

		foreach ($sessions as $session)
		{
			$message = 0;

			if(!empty($session['LAST_MESSAGE_ID']) && Loader::includeModule('im'))
			{
				$message = MessageTable::getById($session['LAST_MESSAGE_ID'])->fetch();
			}

			if (empty($message) || ($closeDay && $message['DATE_CREATE'] instanceof DateTime && $message['DATE_CREATE']->getTimestamp() < $oldCloseTime->getTimestamp()))
			{
				if($correction)
				{
					$chat = new Chat($session['CHAT_ID']);

					$chat->dismissedOperatorFinish();
				}

				$result['CLOSE'][] = $session['SESSION_ID'];
			}
			else
			{
				if($correction)
				{
					$resultSessionUpdate = SessionCheckTable::update($session['SESSION_ID'], ['DATE_QUEUE' => $queueTime]);

					if ($resultSessionUpdate->isSuccess())
					{
						$result['UPDATE'][] = $session['SESSION_ID'];
					}
				}
				else
				{
					$result['UPDATE'][] = $session['SESSION_ID'];
				}
			}
		}

		return $result;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function getCountSessionsNoDateClose()
	{
		$connection = Application::getConnection();

		$sql = 'SELECT
				   count(*) AS COUNT
			FROM
				 b_imopenlines_session_check,
				 b_imopenlines_session
			WHERE
				b_imopenlines_session.ID = b_imopenlines_session_check.SESSION_ID AND
				b_imopenlines_session_check.DATE_CLOSE IS NULL';

		$raw = $connection->query($sql);

		$result = $raw->fetch()['COUNT'];

		if(empty($result) || !is_numeric($result))
		{
			$result = 0;
		}

		return $result;
	}

	/**
	 * Marks the date of the closing of the session and closes the old session.
	 *
	 * @param bool $correction
	 * @param bool $closeDay
	 * @param int $limit
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function closeOldSession($correction = true, $closeDay = false, $limit = 0)
	{
		$result = [
			'CLOSE' => [],
			'UPDATE' => []
		];

		$closeDay = intval($closeDay);
		if($closeDay<=0)
		{
			$closeDay = false;
		}

		$query = new ORM\Query\Query(SessionCheckTable::getEntity());
		$query->setSelect([
			'SESSION_ID',
			'CHAT_ID' => 'SESSION.CHAT_ID',
			'LAST_MESSAGE_ID' => 'SESSION.CHAT.LAST_MESSAGE_ID'
		]);
		$query->setFilter([
			'=DATE_CLOSE' => null
		]);
		if(!empty($limit))
		{
			$query->setLimit($limit);
		}
		$sessionCheckManager = $query->exec();

		$oldCloseTime = new DateTime();
		$closeTime = (new DateTime())->add('30 DAY');
		if($closeDay)
		{
			$oldCloseTime->add('-' . $closeDay . ' DAY');
		}

		while ($sessionCheck = $sessionCheckManager->fetch())
		{
			$message = 0;

			if(!empty($sessionCheck['LAST_MESSAGE_ID']) && Loader::includeModule('im'))
			{
				$message = MessageTable::getById($sessionCheck['LAST_MESSAGE_ID'])->fetch();
			}

			if (empty($message) || ($closeDay && $message['DATE_CREATE'] instanceof DateTime && $message['DATE_CREATE']->getTimestamp() < $oldCloseTime->getTimestamp()))
			{
				if($correction)
				{
					$chat = new Chat($sessionCheck['CHAT_ID']);

					$chat->dismissedOperatorFinish();
				}

				$result['CLOSE'][] = $sessionCheck['SESSION_ID'];
			}
			else
			{
				if($correction)
				{
					$resultSessionUpdate = SessionCheckTable::update($sessionCheck['SESSION_ID'], ['DATE_CLOSE' => $closeTime]);

					if ($resultSessionUpdate->isSuccess())
					{
						$result['UPDATE'][] = $sessionCheck['SESSION_ID'];
					}
				}
				else
				{
					$result['UPDATE'][] = $sessionCheck['SESSION_ID'];
				}
			}
		}

		return $result;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function getCountStatusClosedSessions()
	{
		$connection = Application::getConnection();

		$sql = 'SELECT
				   count(*) AS COUNT
			FROM
				 b_imopenlines_session
			WHERE
				CLOSED = \'Y\' AND
				STATUS < \'60\'';

		$raw = $connection->query($sql);

		$result = $raw->fetch()['COUNT'];

		if(empty($result) || !is_numeric($result))
		{
			$result = 0;
		}

		return $result;
	}

	/**
	 * The correct status is set for sessions that are closed.
	 *
	 * @param bool $correction
	 * @param int $limit
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function setStatusClosedSessions($correction = true, $limit = 0)
	{
		$result = [];

		$query = new ORM\Query\Query(SessionTable::getEntity());
		$query->setSelect([
			'ID',
			'CHECK_SESSION_ID' => 'CHECK.SESSION_ID'
		]);
		$query->setFilter([
			'<STATUS' => Session::STATUS_CLOSE,
			'CLOSED' => 'Y'
		]);
		if(!empty($limit))
		{
			$query->setLimit($limit);
		}
		$sessionManager = $query->exec();

		while ($session = $sessionManager->fetch())
		{
			if($correction)
			{
				$resultSessionUpdate = SessionTable::update($session['ID'], ['STATUS' => Session::STATUS_CLOSE]);

				if ($resultSessionUpdate->isSuccess())
				{
					if ($session['CHECK_SESSION_ID'] > 0)
					{
						SessionCheckTable::delete($session['CHECK_SESSION_ID']);
					}

					$result[] = $session['ID'];
				}
			}
			else
			{
				$result[] = $session['ID'];
			}
		}

		return $result;
	}
}
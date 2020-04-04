<?php
namespace Bitrix\ImOpenLines\Log;

use \Bitrix\ImOpenLines\Error;
use \Bitrix\Imopenlines\Model\EventLogTable;
use \Bitrix\Main\Result;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ArgumentException;
use \Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class EventLog
{
	private static $eventMap = array(
		Library::EVENT_SESSION_START => 'onSessionStart',
		Library::EVENT_SESSION_LOAD => 'onSessionLoad',
		Library::EVENT_SESSION_PAUSE => 'onSessionPause',
		Library::EVENT_SESSION_SPAM => 'onSessionSpam',
		Library::EVENT_SESSION_CLOSE => 'onSessionClose',
		Library::EVENT_SESSION_QUEUE_NEXT => 'onSessionQueueNext',
		Library::EVENT_SESSION_DISMISSED_OPERATOR_FINISH => 'onSessionDismissedOperatorFinish',
		Library::EVENT_SESSION_VOTE_USER => 'onSessionVoteUser',
		Library::EVENT_SESSION_VOTE_HEAD => 'onSessionVoteHead'
	);

	/**
	 * Add all events elements to EventLog table
	 *
	 * @param Result|array $fieldsResult
	 * @param string $event
	 * @param int $lineId
	 * @param int $sessionId
	 * @param int $messageId
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult|Result|mixed
	 * @throws \Exception
	 */
	public static function addEvent($event, $fieldsResult, $lineId = 0, $sessionId = 0, $messageId = 0)
	{
		$result = new Result();
		$lineId = intval($lineId);
		$data = array();

		if (is_array($fieldsResult))
		{
			$resultData = $fieldsResult;
			$fieldsResult = new Result();
			$fieldsResult->setData($resultData);
		}

		if (!($fieldsResult instanceof Result))
			throw new ArgumentException(Loc::getMessage('IMOL_EVENTLOG_WRONG_FIELD_RESULT_TYPE_EXCEPTION'));

		if (empty(self::$eventMap[$event]) || !method_exists(__CLASS__, self::$eventMap[$event]))
			$result->addError(new Error(Loc::getMessage('IMOL_EVENTLOG_NOT_ACTUAL_EVENT_ERROR', array('#EVENT#' => $event)), Library::EVENTS_ERROR_NOT_ACTUAL_EVENT_ERROR_CODE, __METHOD__));

		if ($lineId <= 0)
			$result->addError(new Error(Loc::getMessage('IMOL_EVENTLOG_EMPTY_LINE_ID_ERROR'), Library::EVENTS_ERROR_EMPTY_LINE_ID_ERROR_CODE, __METHOD__));

		if ($result->isSuccess())
		{
			$result = call_user_func_array([__CLASS__, self::$eventMap[$event]], [$lineId, $sessionId, $messageId, $fieldsResult]);

			if ($result->isSuccess())
			{
				$data = $result->getData();
			}
		}

		if (empty($data))
		{
			$data = self::getErrorEventData($event, $result, $lineId, $sessionId, $messageId);
		}

		return EventLogTable::add($data);
	}

	/**
	 * Prepare event Result item data and errors as array
	 *
	 * @param Result $fieldsResult
	 * @param bool $setErrorEventMessage
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function getEventFieldsData(Result $fieldsResult, $setErrorEventMessage = true)
	{
		$result['ADDITIONAL_DATA'] = $fieldsResult->getData();
		$result['EVENT_FIELDS_DATA'] = array(
			'DATE_TIME' => new DateTime(),
			'ADDITIONAL_FIELDS' => $result['ADDITIONAL_DATA']
		);

		if (!$fieldsResult->isSuccess())
		{
			$result['EVENT_FIELDS_DATA']['IS_ERROR'] = 'Y';

			if ($setErrorEventMessage)
			{
				$result['EVENT_FIELDS_DATA']['EVENT_MESSAGE'] = implode(PHP_EOL, $fieldsResult->getErrorMessages());
			}
		}

		return $result;
	}

	/**
	 * Event handler base for common event cases
	 *
	 * @param $eventType
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function baseEventHandler($eventType, $lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = new Result();
		$resultData = array(
			'EVENT_TYPE' => $eventType,
			'LINE_ID' => $lineId,
			'SESSION_ID' => $sessionId,
			'MESSAGE_ID' => $messageId,
		);
		$eventData = self::getEventFieldsData($fieldsResult);
		$resultData = array_merge($resultData, $eventData['EVENT_FIELDS_DATA']);

		$result->setData($resultData);

		return $result;
	}

	/**
	 * Prepare element in case of addEvent error
	 *
	 * @param $event
	 * @param Result $fieldsResult
	 * @param int $lineId
	 * @param int $sessionId
	 * @param int $messageId
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function getErrorEventData($event, Result $fieldsResult, $lineId = 0, $sessionId = 0, $messageId = 0)
	{
		$eventFieldsData = self::getEventFieldsData($fieldsResult);
		$result = $eventFieldsData['EVENT_FIELDS_DATA'];
		$result['LINE_ID'] = $lineId;
		$result['SESSION_ID'] = $sessionId;
		$result['MESSAGE_ID'] = $messageId;
		$result['EVENT_TYPE'] = $event;

		return $result;
	}

	/*Session events handlers start*/

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionStart($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_START, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionLoad($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_LOAD, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionPause($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_PAUSE, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionSpam($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_SPAM, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionClose($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_CLOSE, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionQueueNext($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_QUEUE_NEXT, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionDismissedOperatorFinish($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_DISMISSED_OPERATOR_FINISH, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionVoteUser($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_VOTE_USER, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/**
	 * @param $lineId
	 * @param $sessionId
	 * @param $messageId
	 * @param Result $fieldsResult
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected static function onSessionVoteHead($lineId, $sessionId, $messageId, Result $fieldsResult)
	{
		$result = self::baseEventHandler(Library::EVENT_SESSION_VOTE_HEAD, $lineId, $sessionId, $messageId, $fieldsResult);

		return $result;
	}

	/*Session events handlers end*/
}
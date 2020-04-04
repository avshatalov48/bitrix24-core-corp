<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage xdimport
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\XDImport\Integration\Socialnetwork;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Socialnetwork\Item\LogIndex;

class LogComment
{
	const EVENT_ID_DATA_COMMENT = 'data_comment';

	public static function getEventIdList()
	{
		return array(
			self::EVENT_ID_DATA_COMMENT
		);
	}

	/**
	 * Return content for LogIndex.
	 *
	 * @param Event $event Event from LogIndex::setIndex().
	 * @return EventResult
	 */
	public static function onIndexGetContent(Event $event)
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			array(),
			'xdimport'
		);

		$eventId = $event->getParameter('eventId');
		$itemId = $event->getParameter('itemId');

		if (!in_array($eventId, self::getEventIdList()))
		{
			return $result;
		}

		$content = "";

		if (intval($itemId) > 0)
		{
			$res = \Bitrix\Socialnetwork\LogCommentTable::getList(array(
				'filter' => array(
					'=ID' => $itemId
				),
				'select' => array('USER_ID', 'MESSAGE')
			));

			if ($commentFields = $res->fetch())
			{
				if (intval($commentFields['USER_ID']) > 0)
				{
					$content .= LogIndex::getUserName($commentFields["USER_ID"])." ";
				}
				$content .= \CTextParser::clearAllTags($commentFields["MESSAGE"]);

			}
		}

		$result = new EventResult(
			EventResult::SUCCESS,
			array(
				'content' => $content,
			),
			'xdimport'
		);

		return $result;
	}
}


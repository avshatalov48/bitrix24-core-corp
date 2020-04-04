<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Intranet\Integration\Socialnetwork;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Item\LogIndex;

class LogComment
{
	const EVENT_ID_INTRANET_NEW_USER_COMMENT = 'intranet_new_user_comment';

	public static function getEventIdList()
	{
		return array(
			self::EVENT_ID_INTRANET_NEW_USER_COMMENT
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
			'intranet'
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
				'select' => array('USER_ID', 'MESSAGE', 'UF_SONET_COM_URL_PRV')
			));

			if ($commentFields = $res->fetch())
			{
				if (intval($commentFields['USER_ID']) > 0)
				{
					$content .= LogIndex::getUserName($commentFields["USER_ID"])." ";
				}
				$content .= \CTextParser::clearAllTags($commentFields["MESSAGE"]);

				if (!empty($commentFields['UF_SONET_COM_URL_PRV']))
				{
					$metadata = \Bitrix\Main\UrlPreview\UrlMetadataTable::getRowById($commentFields['UF_SONET_COM_URL_PRV']);
					if (
						$metadata
						&& !empty($metadata['TITLE'])
					)
					{
						$content .= ' '.$metadata['TITLE'];
					}
				}
			}
		}

		$result = new EventResult(
			EventResult::SUCCESS,
			array(
				'content' => $content,
			),
			'intranet'
		);

		return $result;
	}
}


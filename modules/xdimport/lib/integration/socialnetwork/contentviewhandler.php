<?php

namespace Bitrix\XDImport\Integration\Socialnetwork;

use Bitrix\Main\Loader;

/**
 * Class for content view event handlers
 *
 * Class ContentViewHandler
 * @package Bitrix\XDImport\Integration\Socialnetwork
 */
final class ContentViewHandler
{
	const CONTENT_TYPE_ID_COMMENT = 'LOG_COMMENT';

	final static function getContentTypeIdList(): array
	{
		return [
			self::CONTENT_TYPE_ID_COMMENT
		];
	}

	/**
	 * Handles content view event, marking IM notifications as read
	 *
	 * @param \Bitrix\Main\Event $event Event.
	 * @return int|false
	 */
	public static function onContentViewed(\Bitrix\Main\Event $event)
	{
		$userId = (int)$event->getParameter('userId');
		$contentTypeId = $event->getParameter('typeId');
		$contentEntityId = (int)$event->getParameter('entityId');
		$logId = (int)$event->getParameter('logId');

		if (
			$userId <= 0
			|| $contentEntityId <= 0
			|| !Loader::includeModule('im')
			|| !in_array($contentTypeId, self::getContentTypeIdList(), true)
		)
		{
			return false;
		}

		$subTagList = [];

		if ($contentTypeId === self::CONTENT_TYPE_ID_COMMENT)
		{
			$subTagList[] = "XDIMPORT|COMMENT_MENTION|".$logId.'|'.$userId;
		}

		if (!empty($subTagList))
		{
			$CIMNotify = new \CIMNotify();
			$CIMNotify->MarkNotifyReadBySubTag($subTagList);
		}

		return true;
	}
}

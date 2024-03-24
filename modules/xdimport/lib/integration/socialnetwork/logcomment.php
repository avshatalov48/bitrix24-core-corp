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
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\Item\LogIndex;
use Bitrix\Socialnetwork\Livefeed;
use Bitrix\Socialnetwork\CommentAux;

Loc::loadMessages(__FILE__);

class LogComment
{
	public const EVENT_ID_DATA_COMMENT = 'data_comment';

	public static function getEventIdList(): array
	{
		return [
			self::EVENT_ID_DATA_COMMENT
		];
	}

	/**
	 * Return content for LogIndex.
	 *
	 * @param Event $event Event from LogIndex::setIndex().
	 * @return EventResult
	 */
	public static function onIndexGetContent(Event $event): EventResult
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			[],
			'xdimport'
		);

		$eventId = $event->getParameter('eventId');
		$itemId = $event->getParameter('itemId');

		if (!in_array($eventId, self::getEventIdList(), true))
		{
			return $result;
		}

		$content = "";

		if ((int)$itemId > 0)
		{
			$res = \Bitrix\Socialnetwork\LogCommentTable::getList([
				'filter' => [
					'=ID' => $itemId,
				],
				'select' => [ 'USER_ID', 'MESSAGE', 'UF_SONET_COM_URL_PRV' ],
			]);

			if ($commentFields = $res->fetch())
			{
				if ((int)$commentFields['USER_ID'] > 0)
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
			[
				'content' => $content,
			],
			'xdimport'
		);

		return $result;
	}

	public static function setSource(array $commentFields = []): array
	{
		global $USER;

		$result = [
			'NO_SOURCE' => 'Y'
		];

		if (empty($commentFields['MESSAGE']))
		{
			return $result;
		}

		$logId = (int)$commentFields['LOG_ID'];
		if ($logId <= 0)
		{
			return $result;
		}

		$authorId = (int)$commentFields['USER_ID'];

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$mentionedUserIdList = \Bitrix\Socialnetwork\Helper\Mention::getUserIds($commentFields['MESSAGE']);
		if (empty($mentionedUserIdList))
		{
			return $result;
		}

		$mentionedUserIdList = array_filter(
			$mentionedUserIdList,
			static function ($userId) use ($authorId) {
				return (int)$userId !== $authorId;
			}
		);

		self::sendNotification([
			'type' => 'mention',
			'userIdList' => $mentionedUserIdList,
			'authorId' => $authorId,
			'logId' => $commentFields['LOG_ID'],
		]);

		$shareUserId = array_filter(
			$mentionedUserIdList,
			static function ($userId) use ($logId) {
				return !\CSocNetLogRights::checkForUser($logId, $userId);
			}
		);

		if (empty($shareUserId))
		{
			return $result;
		}

		$shareCodesList = array_map(
			static function ($userId) {
				return 'U' . $userId;
			},
			$shareUserId
		);
		\CSocNetLogRights::add($logId, $shareCodesList);

		$currentUserId = (int)$USER->getId();

		$commentProvider = Livefeed\Provider::init([
			'ENTITY_TYPE' => Livefeed\Provider::DATA_ENTITY_TYPE_LOG_COMMENT,
			'LOG_ID' => $logId,
			'CLONE_DISK_OBJECTS' => false,
		]);

		if (!$commentProvider)
		{
			return $result;
		}

		$commentProvider->add([
			'SITE_ID' => SITE_ID,
			'AUTHOR_ID' => $currentUserId,
			'MESSAGE' => CommentAux\Share::getPostText(),
			'SHARE_DEST' => 'mention|'.implode(',', $shareCodesList),
			'MODULE' => '',
		]);

		return $result;
	}

	protected static function sendNotification(array $params = []): array
	{
		$result = [];

		if (
			!Loader::includeModule('im')
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$type = (isset($params['type']) ? (string)$params['type'] : '');
		if ($type !== 'mention')
		{
			return $result;
		}

		$userIdList = (
			isset($params['userIdList'])
			&& is_array($params['userIdList'])
				? $params['userIdList']
				: []
		);
		$userIdList = self::processUserList($userIdList);
		if (empty($userIdList))
		{
			return $result;
		}

		$authorId = (isset($params['authorId']) ? (int)$params['authorId'] : 0);
		if ($authorId <= 0)
		{
			return $result;
		}

		$logId = (isset($params['logId']) ? (int)$params['logId'] : 0);
		if ($logId <= 0)
		{
			return $result;
		}

		$notifyEvent = '';
		$notifyTag = '';
		$pushAction = '';

		switch ($type)
		{
			case 'mention':
				$notifyEvent = 'mention_comment';
				$notifyTag = 'XDIMPORT|COMMENT_MENTION|' . $logId . '|';
				$pushAction = 'mention';
				break;
			default:
		}

		$notificationFields = [
			'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
			'TO_USER_ID' => null,
			'FROM_USER_ID' => $authorId,
			'NOTIFY_TYPE' => IM_NOTIFY_FROM,
			'NOTIFY_ANSWER' => 'N',
			'NOTIFY_MODULE' => 'xdimport',
			'PARSE_LINK' => 'N',
			'PUSH_PARAMS' => [
				'ACTION' => $pushAction,
				'TAG' => $notifyTag,
				'ADVANCED_PARAMS' => [],
			],
			'NOTIFY_EVENT' => $notifyEvent,
			'NOTIFY_TAG' => $notifyTag,
		];

		$authorFields = self::getUserData($authorId);
		$authorGenderSuffix = (string)$authorFields['PERSONAL_GENDER'];
		$authorAvatarUrl = self::getAvatarUrl([
			'fileId' => (int)$authorFields['PERSONAL_PHOTO'],
		]);

		if (!empty($authorAvatarUrl))
		{
			$notificationFields['PUSH_PARAMS']['ADVANCED_PARAMS']['avatarUrl'] = $authorAvatarUrl;
		}

		foreach ($userIdList as $userId)
		{
			$userSite = self::getUserSite($userId);

			$currentNotificationFields = $notificationFields;
			$currentNotificationFields['TO_USER_ID'] = $userId;

			$provider = new Livefeed\LogEvent();
			$provider->setEntityId($logId);
			$provider->setLogId($logId);
			$provider->setSiteId($userSite);

			$postUrl = $provider->getLiveFeedUrl();

			$message = '';
			$messageOut = '';
			$messagePush = '';
			$notifySubTag = '';

			switch ($type)
			{
				case 'mention':
					$notifySubTag = 'XDIMPORT|COMMENT_MENTION|' . $logId . '|' . $userId;
					$message = ($authorGenderSuffix === 'F' ? 'XDIMPORT_COMMENT_MENTION_NOTIFICATION_MESSAGE2_F' : 'XDIMPORT_COMMENT_MENTION_NOTIFICATION_MESSAGE2');
					$messageOut = ($authorGenderSuffix === 'F' ? 'XDIMPORT_COMMENT_MENTION_NOTIFICATION_MESSAGE2_OUT_F' : 'XDIMPORT_COMMENT_MENTION_NOTIFICATION_MESSAGE2_OUT');
					$messagePush = ($authorGenderSuffix === 'F' ? 'XDIMPORT_COMMENT_MENTION_NOTIFICATION_MESSAGE2_PUSH_F' : 'XDIMPORT_COMMENT_MENTION_NOTIFICATION_MESSAGE2_PUSH');
					break;
				default:
			}

			$currentNotificationFields['NOTIFY_SUB_TAG'] = $notifySubTag;

			$currentNotificationFields['NOTIFY_MESSAGE'] = Loc::getMessage(
				$message,
				[
					'#A_BEGIN#' => '<a href="' . htmlspecialcharsbx($postUrl) . '" class="bx-notifier-item-action">',
					'#A_END#' => '</a>',
				]
			);

			$currentNotificationFields['NOTIFY_MESSAGE_OUT'] = Loc::getMessage(
				$messageOut,
				[
					'#URL#' => self::getAbsoluteUrl([
						'url' => $postUrl,
						'siteId' => $userSite,
					]),
				]
			);

			$authorName = \CUser::formatName(\CSite::getNameFormat(null, $userSite), $authorFields, true, false);

			$currentNotificationFields['PUSH_MESSAGE'] = Loc::getMessage(
				$messagePush,
				[
					"#AUTHOR#" => htmlspecialcharsbx($authorName),
				]
			);

			$currentNotificationFields['PUSH_PARAMS']['ADVANCED_PARAMS']['senderName'] = $authorName;

			if (\CIMNotify::add($currentNotificationFields))
			{
				$result[] = $userId;
			}
		}

		return $result;
	}

	protected static function processUserList(array $userIdList = []): array
	{
		$userIdList = array_map(
			static function ($userId) {
				return (int)$userId;
			},
			$userIdList
		);
		$userIdList = array_filter(
			$userIdList,
			static function ($userId) {
				return ($userId > 0);
			}
		);
		return array_unique($userIdList);
	}

	protected static function getUserData(int $userId = 0): array
	{
		static $cache = [];

		if (isset($cache[$userId]))
		{
			return $cache[$userId];
		}

		$result = [];

		$res = UserTable::getList([
			'filter' => [
				'=ID' => $userId
			],
			'select' => [ 'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_GENDER', 'PERSONAL_PHOTO' ]
		]);
		if ($userFields = $res->fetch())
		{
			$cache[$userId] = $userFields;
			$result = $userFields;
		}

		return $result;
	}

	protected static function getUserSite(int $userId = 0): string
	{
		static $cacheIntranetUsersList = null;
		static $cacheExtranetSiteId = null;

		$result = SITE_ID;

		if (
			$userId <= 0
			|| !ModuleManager::isModuleInstalled('extranet')
		)
		{
			return $result;
		}

		if ($cacheIntranetUsersList === null)
		{
			$cacheIntranetUsersList = \CExtranet::getIntranetUsers();
		}

		if (in_array($userId, $cacheIntranetUsersList, false))
		{
			return $result;
		}

		if ($cacheExtranetSiteId === null)
		{
			$cacheExtranetSiteId = \CExtranet::getExtranetSiteId();
		}

		$result = $cacheExtranetSiteId;

		return $result;
	}

	protected static function getAbsoluteUrl(array $params = []): string
	{
		static $cacheSiteData = null;

		$url = (isset($params['url']) ? (string)$params['url'] : '');
		$siteId = (isset($params['siteId']) ? (string)$params['siteId'] : '');

		$result = $url;

		if (empty($siteId))
		{
			return $result;
		}

		if ($cacheSiteData === null)
		{
			$cacheSiteData = \CSocNetLogTools::getSiteData();
		}

		if (!isset($cacheSiteData[$siteId]))
		{
			return $result;
		}

		$serverName = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http') . '://' . $cacheSiteData[$siteId]['SERVER_NAME'];
		$result = $serverName . $url;

		return $result;
	}

	protected static function getAvatarUrl(array $params = []): string
	{
		static $cache = [];

		$result = '';

		$fileId = (isset($params['fileId']) ? (int)$params['fileId'] : 0);
		if ($fileId <= 0)
		{
			return $result;
		}

		if (isset($cache[$fileId]))
		{
			return $cache[$fileId];
		}

		if (!Loader::includeModule('im'))
		{
			return $result;
		}

		$avatarSize = (isset($params['avatarSize']) ? (int)$params['avatarSize'] : 100);

		$imageResized = \CFile::resizeImageGet(
			$fileId,
			[
				'width' => $avatarSize,
				'height' => $avatarSize
			],
			BX_RESIZE_IMAGE_EXACT
		);

		if (!$imageResized)
		{
			return $result;
		}

		if (mb_strpos($imageResized['src'], 'http') !== 0)
		{
			$imageResized['src'] = \Bitrix\Im\Common::getPublicDomain() . $imageResized['src'];
		}

		$cache[$fileId] = $imageResized['src'];
		$result = $cache[$fileId];

		return $result;
	}
}

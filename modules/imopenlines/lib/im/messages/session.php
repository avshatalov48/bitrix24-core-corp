<?php
namespace Bitrix\ImOpenLines\Im\Messages;

use Bitrix\UI;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenLines;
use Bitrix\ImOpenLines\Im;
use Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);

/**
 * Class Session
 * @package Bitrix\ImOpenLines\Im\Messages
 */
class Session
{
	/**
	 * @param $chatId
	 * @param $sessionId
	 * @return bool|int
	 */
	public static function sendMessageStartSession($chatId, $sessionId)
	{
		$messageFields = [
			'SYSTEM' => 'Y',
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_MESSAGE_SESSION_START', [
				'#LINK#' => ImOpenLines\Session\Common::getUrlImHistoryBbCode($sessionId, $sessionId)
			]),
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-start',
				'COMPONENT_ID' => 'StartDialogMessage',
			]
		];

		return Im::addMessage($messageFields);
	}

	/**
	 * @param $chatId
	 * @param $sessionId
	 * @param $sessionIdParent
	 * @return bool|int
	 */
	public static function sendMessageStartSessionByMessage($chatId, $sessionId, $sessionIdParent)
	{
		$messageFields = [
			'SYSTEM' => 'Y',
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_MESSAGE_SESSION_START_BY_MESSAGE', [
				'#LINK#' => ImOpenLines\Session\Common::getUrlImHistoryBbCode($sessionId, $sessionId),
				'#LINK2#' => ImOpenLines\Session\Common::getUrlImHistoryBbCode($sessionIdParent, $sessionIdParent)
			]),
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-start',
				'COMPONENT_ID' => 'StartDialogMessage',
			]
		];

		return Im::addMessage($messageFields);
	}

	public static function sendMessageStartSessionByMultiDialogToParentChat(int $chatIdParent, int $sessionId, int $chatIdChild): ?bool
	{
		$messageFields = [
			'SYSTEM' => 'Y',
			'TO_CHAT_ID' => $chatIdParent,
			'MESSAGE' => Loc::getMessage('IMOL_MESSAGE_SESSION_START_BY_MULTI_DIALOG_SOURCE', [
				'#LINK#' => ImOpenLines\Chat::getUrlImChat($chatIdChild, $sessionId)
			]),
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-start',
				'COMPONENT_ID' => 'StartDialogMessage',
			]
		];

		return Im::addMessage($messageFields);
	}

	/**
	 * @param $chatId
	 * @param $sessionIdParent
	 * @return bool|int
	 */
	public static function sendMessageNewMultidialog($sessionId, $chatId, $parentChatId, $parentSessionId)
	{
		$messageFields = [
			'SYSTEM' => 'Y',
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_MESSAGE_SESSION_START_BY_MESSAGE_MULTI_MSGVER_1', [
				'#LINK1#' => ImOpenLines\Session\Common::getUrlImHistoryBbCode($sessionId, $sessionId),
				'#LINK2#' => ImOpenLines\Chat::getUrlImChat($parentChatId, $parentSessionId),
			]),
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-start',
				'COMPONENT_ID' => 'StartDialogMessage',
			]
		];

		return Im::addMessage($messageFields);
	}

	/**
	 * @param $chatId
	 * @param $sessionId
	 * @return bool|int
	 */
	public static function sendMessageReopenSession($chatId, $sessionId)
	{
		$messageFields = [
			'SYSTEM' => 'Y',
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_MESSAGE_SESSION_REOPEN', [
				'#LINK#' => ImOpenLines\Session\Common::getUrlImHistoryBbCode($sessionId, $sessionId)
			]),
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-start',
				'COMPONENT_ID' => 'StartDialogMessage',
			],
			'RECENT_ADD' => 'N'
		];
		return Im::addMessage($messageFields);
	}

	/**
	 * @param $chatId
	 * @param $blockReason
	 * @return bool|int|null
	 */
	public static function sendMessageTimeLimit(int $chatId, string $blockReason)
	{
		if (Loader::includeModule('ui'))
		{
			$messageFields = [
				'SYSTEM' => 'Y',
				'FROM_USER_ID' => 0,
				'TO_CHAT_ID' => $chatId,
				'URL_PREVIEW' => 'N',
				'MESSAGE' => Loc::getMessage('IMOL_MESSAGE_SESSION_REPLY_TIME_LIMIT_'.$blockReason, [
					'#A_START#' => '[URL=' . UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT) . ']',
					'#A_END#' => '[/URL]',
				]),
			];

			return Im::addMessage($messageFields);
		}

		return null;
	}
}
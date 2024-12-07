<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Im;
use Bitrix\Im\Counter;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Operator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class OpenLineManager
{
	private static $isEnabled;

	/**
	 * List of supported IM types
	 *
	 * @var array
	 */
	private static array $supportedTypes = [
		'IM' => [
			'IMOL' => true,
			'OPENLINE' => true,
			'BITRIX24' => true,
			'FACEBOOK' => true,
			'TELEGRAM' => true,
			'VK' => true,
			'VIBER' => true,
			'INSTAGRAM' => true,
		]
	];

	private static array $supportedConnectors = [
		'livechat',
		'fbinstagram',
		'viber',
		'wechat',
		'network',
		'facebook',
		'facebookmessenger',
		'facebookcomments',
		'fbinstagramdirect',
		'imessage',
		'olx',
		'notifications',
		'whatsappbyedna',
		'whatsappbytwilio',
		'vkgroup',
		'vkgrouporder',
		'ok',
		'avito',
		'telegrambot',
		'telegram',
		'imessage',
	];

	public static function isEnabled()
	{
		if (self::$isEnabled === null)
		{
			self::$isEnabled = ModuleManager::isModuleInstalled('imopenlines')
				&& Loader::includeModule('imopenlines');
		}

		return self::$isEnabled;
	}

	public static function prepareMultiFieldLinkAttributes($typeName, $valueTypeID, $value)
	{
		if (!(isset(self::$supportedTypes[$typeName]) && isset(self::$supportedTypes[$typeName][$valueTypeID])))
		{
			return null;
		}

		$items = explode('|', $value);
		if (!(is_array($items) && count($items) > 2 && $items[0] === 'imol'))
		{
			return null;
		}

		$typeID = $items[1];
		$suffix = mb_strtoupper(preg_replace('/[^a-z0-9]/i', '', $typeID));
		$text =
			Loc::getMessage("CRM_OPEN_LINE_{$suffix}")
			?? Loc::getMessage("CRM_OPEN_LINE_{$suffix}_MSGVER_1")
			?? Loc::getMessage('CRM_OPEN_LINE_SEND_MESSAGE')
		;

		return [
			'HREF' => '#',
			'ONCLICK' => "if(typeof(top.BXIM)!=='undefined') top.BXIM.openMessengerSlider('{$value}', {RECENT: 'N', MENU: 'N'}); return BX.PreventDefault(event);",
			'TEXT' => $text,
			'TITLE' => $text,
		];
	}

	public static function getSessionMessages($sessionID, $limit = 20)
	{
		if (
			!Loader::includeModule('im')
			|| !Loader::includeModule('imopenlines')
		)
		{
			return [];
		}

		$sessionID = (int)$sessionID;
		if ($limit <= 0)
		{
			$limit = 20;
		}

		$query = "
			SELECT 
				MESSAGE, 
				AUTHOR_ID,
				FILE.ID as MESSAGE_FILE,
				ATTACH.PARAM_VALUE as MESSAGE_ATTACH
			FROM
			   b_imopenlines_session S
			   INNER JOIN b_im_message M ON 
					M.CHAT_ID = S.CHAT_ID 
					AND M.ID+0 >= S.START_ID 
					AND ( M.ID+0 <= S.END_ID OR S.END_ID = 0 ) 
					AND M.AUTHOR_ID > 0
				LEFT JOIN b_im_message_param as FILE ON FILE.MESSAGE_ID = M.ID and FILE.PARAM_NAME = 'FILE_ID'
				LEFT JOIN b_im_message_param as ATTACH ON ATTACH.MESSAGE_ID = M.ID and ATTACH.PARAM_NAME = 'ATTACH'
			WHERE S.ID = ".$sessionID."
			ORDER BY M.ID+0 ASC
		";

		$connection = \Bitrix\Main\Application::getConnection();
		$dbResult = $connection->query($connection->getSqlHelper()->getTopSql($query, $limit));
		$results = [];
		while ($messageFields = $dbResult->fetch())
		{
			$messageFields['MESSAGE'] = Im\Text::parse($messageFields['MESSAGE']);
			$messageFields['MESSAGE'] = Im\Text::removeBbCodes(
				$messageFields['MESSAGE'],
				$messageFields['MESSAGE_FILE'] > 0,
				$messageFields['MESSAGE_ATTACH']
			);
			$messageFields['IS_EXTERNAL'] = Im\User::getInstance($messageFields['AUTHOR_ID'])->isConnector();

			$results[] = $messageFields;
		}

		return $results;
	}

	public static function getLineConnectorType(?string $code): ?string
	{
		if (!isset($code) || !self::isEnabled())
		{
			return null;
		}

		$connectorId = Chat::parseLinesChatEntityId($code)['connectorId'] ?? '';
		if (!in_array($connectorId, static::$supportedConnectors, true))
		{
			return null;
		}

		return $connectorId;
	}

	public static function getLineTitle(?string $code): ?string
	{
		if (!isset($code) || !self::isEnabled())
		{
			return null;
		}

		$lineId = Chat::parseLinesChatEntityId($code)['lineId'];
		if (!isset($lineId))
		{
			return null;
		}

		return (new Config())->get($lineId)['LINE_NAME'] ?? null;
	}

	public static function isImOpenLinesValue(string $value): bool
	{
		return preg_match('/^imol\|/', $value) === 1;
	}

	public static function getOpenLineTitle(string $value): ?string
	{
		return self::getLineTitle(mb_substr($value, 5));
	}

	public static function getChatTitle(?string $code): ?string
	{
		if (!isset($code) || !self::isEnabled())
		{
			return null;
		}

		$chat = new Chat();
		$isLoaded = $chat->load(['USER_CODE' => $code, 'ONLY_LOAD' => 'Y']);

		return $isLoaded ? $chat->getData('TITLE') : null;
	}

	public static function getSessionData(?int $sessionId): array
	{
		if (!isset($sessionId) || !self::isEnabled())
		{
			return [];
		}

		$session = SessionTable::getById($sessionId)->fetch();

		return $session ?: [];
	}

	public static function getChatUnReadMessages(?string $userCode, ?int $userId): int
	{
		if (
			!isset($userCode)
			|| !Loader::includeModule('im')
			|| !Loader::includeModule('imopenlines')
		)
		{
			return 0;
		}

		$chatId = Chat::getChatIdByUserCode($userCode);
		if ($chatId > 0)
		{
			$counters = Counter::get($userId);

			return isset($counters['LINES'][$chatId]) ? (int)$counters['LINES'][$chatId] : 0;
		}

		return 0;
	}

	public static function closeDialog(?string $userCode, ?int $userId = null): ?Result
	{
		if (
			!isset($userCode)
			|| !Loader::includeModule('im')
			|| !Loader::includeModule('imopenlines')
		)
		{
			return null;
		}

		$chatId = Chat::getChatIdByUserCode($userCode);
		if (isset($chatId))
		{
			$control = new Operator($chatId, $userId ?: null);

			return $control->closeDialog();
		}

		return null;
	}
}

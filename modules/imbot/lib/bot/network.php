<?php

namespace Bitrix\ImBot\Bot;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\Im\Bot\Keyboard;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;
use Bitrix\ImBot\DialogSession;

class Network extends Base implements NetworkBot
{
	public const
		BOT_CODE = "network",

		COMMAND_UNREGISTER = 'unregister',
		COMMAND_OPERATOR_MESSAGE_ADD = 'operatorMessageAdd',
		COMMAND_OPERATOR_MESSAGE_UPDATE = 'operatorMessageUpdate',
		COMMAND_OPERATOR_MESSAGE_DELETE = 'operatorMessageDelete',
		COMMAND_OPERATOR_MESSAGE_RECEIVED = 'operatorMessageReceived',
		COMMAND_OPERATOR_START_WRITING = 'operatorStartWriting',
		COMMAND_OPERATOR_CHANGE_LINE = 'operatorChangeLine',
		COMMAND_START_DIALOG_SESSION = 'startDialogSession',
		COMMAND_FINISH_DIALOG_SESSION = 'finishDialogSession',
		COMMAND_CHECK_PUBLIC_URL = 'checkPublicUrl',
		COMMAND_NETWORK_SESSION = 'session',
		COMMAND_CONNECTOR_REGISTER = 'RegisterConnector',
		COMMAND_CONNECTOR_UPDATE = 'UpdateConnector',
		COMMAND_CONNECTOR_UNREGISTER = 'UnRegisterConnector',

		BUTTON_DISABLED_COLOR = '#aaa',
		BUTTON_DEFAULT_COLOR = '#29619b',

		MESSAGE_PARAM_ALLOW_QUOTE = 'IMOL_QUOTE_MSG',// allow|disallow to quote message
		MESSAGE_PARAM_SESSION_ID = 'IMOL_SID',// OL session Id
		MESSAGE_PARAM_IMOL_VOTE = 'IMOL_VOTE',// vote flag
		MESSAGE_PARAM_IMOL_VOTE_DISLIKE = 'IMOL_VOTE_DISLIKE',// vote dislike
		MESSAGE_PARAM_IMOL_VOTE_LIKE = 'IMOL_VOTE_LIKE',// vote like
		MESSAGE_PARAM_CONNECTOR_MID = 'CONNECTOR_MID',
		MESSAGE_PARAM_KEYBOARD = 'KEYBOARD',
		MESSAGE_PARAM_ATTACH = 'ATTACH',
		MESSAGE_PARAM_SENDING = 'SENDING',
		MESSAGE_PARAM_SENDING_TIME = 'SENDING_TS',
		MESSAGE_PARAM_DELIVERED = 'IS_DELIVERED',

		PORTAL_PATH = '/pub/imbot.php',

		SUPPORT_LEVEL_NONE = 'none',
		SUPPORT_LEVEL_FREE = 'free',
		SUPPORT_LEVEL_PAID = 'paid',
		SUPPORT_LEVEL_PARTNER = 'partner';

	protected const
		USER_LEVEL_ADMIN = 'ADMIN',
		USER_LEVEL_INTEGRATOR = 'INTEGRATOR',
		USER_LEVEL_BUSINESS = 'BUSINESS',
		USER_LEVEL_REGULAR = 'USER';

	/** @var \Bitrix\ImBot\Http */
	protected static $httpClient;

	protected static $blackListOfCodes = [
		'1' => "88c8eccd63f6ff5a59ba04e5b0f2012a",
		'2' => "a588e1a88baf601b9d0b0b33b1eefc2b",
		'3' => "acb238d508bfbb0df68f200f21ae9b71",
		'4' => "9020c408d2d43f407b68bbc88601dbe7",
		'5' => "a588e1a88baf601b9d0b0b33b1eefc2b",
		'6' => "511dda9c421cdd21270a5f31d11f2fe5",
		'7' => "ae8cf733b2725127f755f8e75650a07a",
		'8' => "ae8cf733b2725127f755f8e75650a07a",
		'9' => "239e498332e63b5ee62b9e9fb0ff5a8d",
	];

	//region Bot commands

	/**
	 * Register bot at portal.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\RegisterBot
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(string) CODE
	 * 	(string) LINE_NAME
	 * 	(string) LINE_DESC
	 * 	(string) LINE_AVATAR
	 * 	(string) LINE_WELCOME_MESSAGE
	 * 	(string) AGENT Agent mode - Y|N
	 * 	(array) OPTIONS
	 * 	(string) OPTIONS['TYPE']
	 * 	(string) OPTIONS['PARTNER_NAME']
	 * ]
	 * </pre>
	 *
	 * @return bool|int|string
	 */
	public static function register(array $params = [])
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (empty($params['CODE']))
		{
			return false;
		}

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		$botId = self::getNetworkBotId($params['CODE'], true);
		if ($botId)
		{
			return $agentMode ? "" : $botId;
		}

		$properties = [
			'NAME' => mb_substr($params['LINE_NAME'], 0, 50),
			'WORK_POSITION' => $params['LINE_DESC'] ? mb_substr($params['LINE_DESC'], 0, 255) : Loc::getMessage('IMBOT_NETWORK_BOT_WORK_POSITION'),
		];

		$avatarData = self::uploadAvatar($params['LINE_AVATAR']);
		if ($avatarData)
		{
			$properties['PERSONAL_PHOTO'] = $avatarData;
		}

		$botParams = [
			'APP_ID' => $params['CODE'],
			'CODE' => self::BOT_CODE.'_'.$params['CODE'],
			'MODULE_ID' => self::MODULE_ID,
			'TYPE' => Im\Bot::TYPE_NETWORK,
			'INSTALL_TYPE' => Im\Bot::INSTALL_TYPE_SILENT,
			'CLASS' => isset($params['CLASS']) ? $params['CLASS'] : static::class,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see Network::onMessageAdd */
			'METHOD_MESSAGE_UPDATE' => 'onMessageUpdate',/** @see Network::onMessageUpdate */
			'METHOD_MESSAGE_DELETE' => 'onMessageDelete',/** @see Network::onMessageDelete */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see Network::onBotDelete */
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see Network::onChatStart */
			'TEXT_PRIVATE_WELCOME_MESSAGE' => isset($params['LINE_WELCOME_MESSAGE']) ? $params['LINE_WELCOME_MESSAGE'] : '',
			'PROPERTIES' => $properties
		];

		$botId = static::getBotId();
		if ($botId > 0)
		{
			Im\Bot::update(['BOT_ID' => $botId], $botParams);
		}
		else
		{
			$botId = Im\Bot::register($botParams);
		}

		if ($botId)
		{
			$sendParams = [
				'CODE' => $params['CODE'],
				'BOT_ID' => $botId
			];
			if (isset($params['OPTIONS']) && !empty($params['OPTIONS']))
			{
				$sendParams['OPTIONS'] = $params['OPTIONS'];
			}

			$http = self::instanceHttpClient();
			$result = $http->query('RegisterBot', $sendParams, true);
			if (isset($result['error']))
			{
				self::unRegister($params['CODE'], false);
				return false;
			}

			self::setNetworkBotId($params['CODE'], $botId);

			$avatarId = Im\User::getInstance($botId)->getAvatarId();
			if ($avatarId > 0)
			{
				Im\Model\ExternalAvatarTable::add([
					'LINK_MD5' => md5($params['LINE_AVATAR']),
					'AVATAR_ID' => $avatarId
				]);
			}

			// Add commands for Bot\Network only. Children do it by its self.
			if (__CLASS__ == static::getClassName())
			{
				self::registerCommands($botId);
				self::registerApps($botId);
			}
		}

		return $agentMode ? "" : $botId;
	}

	/**
	 * Unregister bot at portal.
	 *
	 * @param string $code Open Line Id.
	 * @param bool $notifyController Send unregister notification request to controller.
	 *
	 * @return bool
	 */
	public static function unRegister($code = '', $notifyController = true)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!$code)
		{
			return false;
		}

		$botId = self::getNetworkBotId($code, true);
		if (!$botId)
		{
			return false;
		}

		$result = (bool)Im\Bot::unRegister(['BOT_ID' => $botId]);
		if (!$result)
		{
			return false;
		}

		self::setNetworkBotId($code, 0);

		if ($notifyController)
		{
			$result = (bool)self::sendUnregisterRequest($code, $botId);
		}

		(new DialogSession)->clearSessions(['BOT_ID' => $botId]);

		return $result;
	}

	/**
	 * Returns command's property list.
	 * @return array{class: string, handler: string, visible: bool, context: string}[]
	 */
	public static function getCommandList(): array
	{
		return [
			self::COMMAND_UNREGISTER => [
				'command' => self::COMMAND_UNREGISTER,
				'handler' => 'onCommandAdd',/** @see ImBot\Bot\Network::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'TEXTAREA',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
					],
				],
			],
			self::COMMAND_NETWORK_SESSION => [
				'command' => self::COMMAND_NETWORK_SESSION,
				'handler' => 'onCommandAdd',/** @see ImBot\Bot\Network::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => static::getBotId(),
					],
				],
			],
		];
	}

	/**
	 * Returns app's property list.
	 * @return array{command: string, icon: string, js: string, context: string, lang: string}[]
	 */
	public static function getAppList(): array
	{
		return [];
	}

	/**
	 * Returns event handler list.
	 * @return array{module: string, event: string, class: string, handler: string}[]
	 */
	public static function getEventHandlerList(): array
	{
		return [];
	}

	/**
	 * Register bot's command.
	 * @return bool
	 */
	public static function registerCommands(?int $botId = null): bool
	{
		$botId = $botId ?: static::getBotId();
		if (!$botId)
		{
			return false;
		}

		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$commandList = [];
		$res = Im\Model\CommandTable::getList([
			'filter' => [
				'=MODULE_ID' => static::MODULE_ID,
				'=BOT_ID' => $botId,
			]
		]);
		while ($row = $res->fetch())
		{
			$commandList[$row['COMMAND']] = $row;
		}

		Im\Command::clearCache();
		foreach (static::getCommandList() as $commandParam)
		{
			if (!isset($commandList[$commandParam['command']]))
			{
				Im\Command::register([
					'MODULE_ID' => static::MODULE_ID,
					'BOT_ID' => $botId,
					'COMMAND' => $commandParam['command'],
					'HIDDEN' => $commandParam['visible'] === true ? 'N' : 'Y',
					'CLASS' => $commandParam['class'] ?? static::class,
					'METHOD_COMMAND_ADD' => $commandParam['handler'] ?? 'onCommandAdd'
				]);
			}
			elseif (
				($commandList[$commandParam['command']]['CLASS'] != ($commandParam['class'] ?? static::class))
				|| ($commandList[$commandParam['command']]['METHOD_COMMAND_ADD'] != ($commandParam['handler'] ?? 'onCommandAdd'))
			)
			{
				Im\Command::update(
					['COMMAND_ID' => $commandList[$commandParam['command']]['ID']],
					[
						'HIDDEN' => $commandParam['visible'] === true ? 'N' : 'Y',
						'CLASS' => $commandParam['class'] ?? static::class,
						'METHOD_COMMAND_ADD' => $commandParam['handler'] ?? 'onCommandAdd'
					]
				);
			}
			unset($commandList[$commandParam['command']]);
		}
		foreach ($commandList as $commandParam)
		{
			Im\Command::unRegister(['COMMAND_ID' => $commandParam['ID']]);
		}

		return true;
	}

	/**
	 * Register bot's command.
	 * @return bool
	 */
	public static function registerApps(?int $botId = null): bool
	{
		$botId = $botId ?: static::getBotId();
		if (!$botId)
		{
			return false;
		}

		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$appList = [];
		$res = Im\Model\AppTable::getList([
			'filter' => [
				'=MODULE_ID' => static::MODULE_ID,
				'=BOT_ID' => $botId,
				'=CLASS' => static::class,
			]
		]);
		while ($row = $res->fetch())
		{
			$appList[$row['CODE']] = $row;
		}

		Im\App::clearCache();
		foreach (static::getAppList() as $appParam)
		{
			if (!isset($appList[$appParam['command']]))
			{
				$iconId = '';
				if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot() . $appParam['icon']))
				{
					$iconId = Main\Application::getDocumentRoot() . $appParam['icon'];
				}
				if ($iconId)
				{
					$icon = \CFile::makeFileArray($iconId);
					$icon['MODULE_ID'] = static::MODULE_ID;
					$iconId = \CFile::saveFile($icon, static::MODULE_ID);
				}
				Im\App::register([
					'MODULE_ID' => static::MODULE_ID,
					'BOT_ID' => $botId,
					'CODE' => $appParam['command'],
					'ICON_ID' => $iconId,
					'JS' => $appParam['js'] ?? '',
					'CONTEXT' => $appParam['context'],
					'CLASS' => $appParam['class'] ?? static::class,
					'METHOD_LANG_GET' => $appParam['lang'] ?? 'onAppLang',
				]);
			}
			unset($appList[$appParam['command']]);
		}
		foreach ($appList as $appParam)
		{
			Im\App::unRegister(['ID' => $appParam['ID']]);
		}

		return true;
	}

	/**
	 * Detects command by message.
	 * @param array $messageFields Message params.
	 * @return array|null
	 */
	protected static function getCommandByMessage(array $messageFields): ?array
	{
		if (
			(isset($messageFields['SYSTEM']) && $messageFields['SYSTEM'] === 'Y')
			|| empty($messageFields['COMMAND'])
		)
		{
			return null;
		}

		$command = static::getCommandList()[$messageFields['COMMAND']] ?? null;
		if (!$command)
		{
			return null;
		}

		$result = null;
		foreach ($command['context'] as $context)
		{
			$diff = array_intersect_assoc($messageFields, $context);
			if (count($diff) == count($context))
			{
				$result = $command;
				break;
			}
		}

		return $result;
	}

		/**
	 * Check if network bot exists and registers it.
	 *
	 * @param string $code Open line code.
	 * @param array $options Additional bot user parameters.
	 *
	 * @return bool|int
	 */
	public static function join($code, $options = [])
	{
		if (!$code)
		{
			return false;
		}

		$botId = self::getNetworkBotId($code, true);
		if ($botId)
		{
			return $botId;
		}

		$result = self::search($code, true);
		if ($result)
		{
			if (!empty($options))
			{
				$result[0]['OPTIONS'] = $options;
			}
			$result = self::register($result[0]);
		}

		return $result;
	}

	/**
	 * Looks for the network openline.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientSearchLine
	 *
	 * @param string $text Search string. Openline's name or exact code.
	 * @param bool $doNotCheckBlackList
	 *
	 * @return bool|array Command arguments.
	 * <pre>
	 * [
	 * 	0 => [
	 * 		(string) CODE,
	 * 		(string) LINE_NAME,
	 * 		(string) LINE_DESC,
	 * 		(string) LINE_WELCOME_MESSAGE,
	 * 		(string) LINE_AVATAR,
	 * 		(bool) VERIFIED,
	 * 	],
	 * 	...
	 * </pre>
	 */
	public static function search($text, $doNotCheckBlackList = false)
	{
		$text = trim($text);
		if (mb_strlen($text) <= 3)
		{
			return false;
		}

		if (!$doNotCheckBlackList && in_array($text, self::$blackListOfCodes))
		{
			return false;
		}

		$http = self::instanceHttpClient();
		$result = $http->query(
			'clientSearchLine',
			['TEXT' => $text],
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}

		return $result['result'];
	}

	/**
	 * Sends unregister bot request.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\UnRegisterBot
	 * @param string $code Open line code.
	 * @param int $botId Bot id.
	 *
	 * @return bool
	 */
	public static function sendUnregisterRequest($code, $botId): bool
	{
		$http = self::instanceHttpClient();

		$result = $http->query(
			'UnRegisterBot',
			['CODE' => $code, 'BOT_ID' => $botId],
			true
		);

		if (isset($result['error']))
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_'.mb_strtoupper($result['error']['code']));
			if (empty($message))
			{
				$message = $result['error']['msg'];
			}
			self::$lastError = new ImBot\Error(
				__METHOD__,
				$result['error']['code'],
				$message
			);

			return false;
		}

		return true;
	}

	/**
	 * Sends notify change licence.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientChangeLicence
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(string) PREVIOUS_LICENCE_TYPE
	 * 	(string) PREVIOUS_LICENCE_NAME
	 * 	(string) CURRENT_LICENCE_TYPE
	 * 	(string) CURRENT_LICENCE_NAME
	 * 	(string) PREVIOUS_BOT_CODE
	 * 	(string) CURRENT_BOT_CODE
	 * 	(string) MESSAGE
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function sendNotifyChangeLicence(array $params)
	{
		if (!static::getBotId())
		{
			return false;
		}

		if (isset($params['CURRENT_LICENCE_TYPE']))
		{
			$currentLicence = $params['CURRENT_LICENCE_TYPE'];
			$currentLicenceName = $params['CURRENT_LICENCE_NAME'];
			$previousLicence = $params['PREVIOUS_LICENCE_TYPE'];
			$previousLicenceName = $params['PREVIOUS_LICENCE_NAME'];
		}
		elseif (Main\Loader::includeModule('bitrix24'))
		{
			$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);
			$currentLicenceName = \CBitrix24::getLicenseName($currentLicence);
			$previousLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
			$previousLicenceName = \CBitrix24::getLicenseName($previousLicence);
		}
		else
		{
			$currentLicence = 'box';
			$currentLicenceName = 'Box';
			$previousLicence = 'box';
			$previousLicenceName = 'Box';
		}

		$message = $params['MESSAGE'] ?? '';

		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientChangeLicence',
			[
				'BOT_ID' => static::getBotId(),
				'PREVIOUS_LICENCE_TYPE' => $previousLicence,
				'PREVIOUS_LICENCE_NAME' => $previousLicenceName,
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => $currentLicenceName,
				'PREVIOUS_BOT_CODE' => $params['PREVIOUS_BOT_CODE'],
				'CURRENT_BOT_CODE' => $params['CURRENT_BOT_CODE'],
				'MESSAGE' => $message,
			],
			false
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * Sends finalize session notification.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientRequestFinalizeSession
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(string) LICENSE_TYPE
	 * 	(string) LICENSE_NAME
	 * 	(string) BOT_CODE
	 * 	(string) MESSAGE
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function sendRequestFinalizeSession(array $params = [])
	{
		if (!static::getBotId())
		{
			return false;
		}
		if (!static::getBotCode())
		{
			return false;
		}

		(new DialogSession)->clearSessions(['BOT_ID' => static::getBotId()]);

		if (Main\Loader::includeModule('bitrix24'))
		{
			$currentLicence = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_CURRENT);
			$currentLicenceName = \CBitrix24::getLicenseName($currentLicence);
		}
		elseif (isset($params['LICENSE_TYPE']))
		{
			$currentLicence = $params['LICENSE_TYPE'];
			$currentLicenceName = $params['LICENSE_NAME'];
		}
		else
		{
			$currentLicence = 'box';
			$currentLicenceName = 'Box';
		}

		$message = $params['MESSAGE'] ?? '';
		$botCode = $params['BOT_CODE'] ?? static::getBotCode();

		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientRequestFinalizeSession',
			[
				'BOT_ID' => static::getBotId(),
				'CURRENT_LICENCE_TYPE' => $currentLicence,
				'CURRENT_LICENCE_NAME' => $currentLicenceName,
				'CURRENT_BOT_CODE' => $botCode,
				'MESSAGE' => $message,
			],
			false
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * Loads bot settings from controller.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\SettingsSupport
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * ]
	 * </pre>
	 *
	 * @return array|null
	 */
	public static function getBotSettings(array $params = []): ?array
	{
		$http = self::instanceHttpClient();
		$result = $http->query('settingsSupport', $params,true);

		if (isset($result['error']))
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_'. mb_strtoupper($result['error']['code']));
			if (empty($message))
			{
				if ($result['error']['msg'] !== '')
				{
					$message = $result['error']['msg'];
				}
				else
				{
					$message = Loc::getMessage('IMBOT_NETWORK_ERROR_SETTINGS_FAIL', ['#ERROR#' => $result['error']['code']]);
				}
			}
			self::$lastError = new ImBot\Error(
				__METHOD__,
				$result['error']['code'],
				$message
			);

			return null;
		}

		return $result;
	}

	/**
	 * Checks availability of the external public url.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\CheckPublicUrl
	 * @param string $publicUrl Portal public url.
	 * @return bool
	 */
	public static function checkPublicUrl($publicUrl = null)
	{
		$publicUrl = $publicUrl ?? ImBot\Http::getServerAddress();
		if (empty($publicUrl))
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_PUBLIC_URL_EMPTY');
			if (empty($message))
			{
				$message = 'Cannot detect a value of the portal public url.';
			}

			self::$lastError = new ImBot\Error(
				__METHOD__,
				'PUBLIC_URL_EMPTY',
				$message
			);

			return false;
		}

		if (
			!($parsedUrl = \parse_url($publicUrl))
			|| empty($parsedUrl['host'])
			|| strpos($parsedUrl['host'], '.') === false
			|| !in_array($parsedUrl['scheme'], ['http', 'https'])
		)
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_PUBLIC_URL_MALFORMED');
			if (empty($message))
			{
				$message = 'Portal public url is malformed.';
			}
			self::$lastError = new ImBot\Error(
				__METHOD__,
				'PUBLIC_URL_MALFORMED',
				$message
			);

			return false;
		}

		// check for local address
		$host = $parsedUrl['host'];
		if (
			strtolower($host) == 'localhost'
			|| $host == '0.0.0.0'
			||
			(
				preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host)
				&& preg_match('#^(127|10|172\.16|192\.168)\.#', $host)
			)
		)
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_PUBLIC_URL_LOCALHOST', ['#HOST#' => $host]);
			if (empty($message))
			{
				$message = 'Portal public url points to localhost: '.$host;
			}

			self::$lastError = new ImBot\Error(
				__METHOD__,
				'PUBLIC_URL_LOCALHOST',
				$message
			);

			return false;
		}

		$error = (new \Bitrix\Main\Web\Uri($publicUrl))->convertToPunycode();
		if ($error instanceof \Bitrix\Main\Error)
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_CONVERTING_PUNYCODE', ['#HOST#' => $host, '#ERROR#' => $error->getMessage()]);
			if (empty($message))
			{
				$message = 'Error converting hostname '.$host.' to punycode: '.$error->getMessage();
			}

			self::$lastError = new ImBot\Error(
				__METHOD__,
				'PUBLIC_URL_MALFORMED',
				$message
			);

			return false;
		}

		$documentRoot = '';
		$siteList = \CSite::getList('', '', ['DOMAIN' => $host, 'ACTIVE' => 'Y']);
		if ($site = $siteList->fetch())
		{
			$documentRoot = $site['ABS_DOC_ROOT'];
		}
		else
		{
			$siteList = \CSite::getList('', '', ['DEFAULT' => 'Y', 'ACTIVE' => 'Y']);
			if ($site = $siteList->fetch())
			{
				$documentRoot = $site['ABS_DOC_ROOT'];
			}
		}
		if ($documentRoot)
		{
			$documentRoot = Main\IO\Path::normalize($documentRoot);
			$publicHandler = new Main\IO\File($documentRoot. self::PORTAL_PATH);
			if (!$publicHandler->isExists())
			{
				$message = Loc::getMessage('IMBOT_NETWORK_ERROR_PUBLIC_URL_HANDLER_PATH', ['#PATH#' => self::PORTAL_PATH]);
				if (empty($message))
				{
					$message = 'The file handler has not been found within the site document root. Expected: '.self::PORTAL_PATH;
				}

				self::$lastError = new ImBot\Error(
					__METHOD__,
					'PUBLIC_URL_HANDLER_PATH',
					$message
				);

				return false;
			}
		}

		$port = '';
		if (
			isset($parsedUrl['port'])
			&& (int)$parsedUrl['port'] > 0
		)
		{
			$port = ':'.(int)$parsedUrl['port'];
		}

		$http = self::instanceHttpClient();

		$http->setPortalDomain($parsedUrl['scheme'].'://'.$parsedUrl['host']. $port);

		$result = $http->query(self::COMMAND_CHECK_PUBLIC_URL, [], true);

		if (isset($result['error']))
		{
			$message = Loc::getMessage('IMBOT_NETWORK_ERROR_'. mb_strtoupper($result['error']['code']));
			if (empty($message))
			{
				if ($result['error']['msg'] !== '')
				{
					$message = $result['error']['msg'];
				}
				else
				{
					$message = Loc::getMessage('IMBOT_NETWORK_ERROR_PUBLIC_URL_FAIL', ['#ERROR#' => $result['error']['code']]);
				}
			}
			self::$lastError = new ImBot\Error(
				__METHOD__,
				$result['error']['code'],
				$message,
				[
					($result['error']['errorStack'] ?? ''),
					($result['error']['errorResult'] ?? '')
				]
			);

			return false;
		}

		return isset($result['result']) && ($result['result'] === 'OK');
	}

	//endregion

	//region Interface Network

	/**
	 * Allows updating bot fields (name, desc, avatar, welcome mess) using data from incoming message.
	 *
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage(): bool
	{
		return true;
	}
	/**
	 * Allows updating bot's avatar using data from incoming message.
	 *
	 * @return bool
	 */
	public static function isNeedUpdateBotAvatarAfterNewMessage(): bool
	{
		return true;
	}

	/**
	 * @return string
	 */
	public static function getSupportLevel(): string
	{
		return self::SUPPORT_LEVEL_NONE;
	}

	/**
	 * Detects client's support level.
	 * @return string
	 */
	public static function getAccessLevel(): string
	{
		$userAccess = self::USER_LEVEL_REGULAR;
		if (self::isUserAdmin(self::getCurrentUser()->getId()))
		{
			$userAccess = self::USER_LEVEL_ADMIN;
		}
		elseif (self::isUserIntegrator(self::getCurrentUser()->getId()))
		{
			$userAccess = self::USER_LEVEL_INTEGRATOR;
		}

		return $userAccess;
	}

	//endregion

	//region Command dispatcher

	/**
	 * Event handler on answer add.
	 * Alias for @see \Bitrix\Imbot\Bot\ChatBot::onAnswerAdd
	 * Called from @see \Bitrix\ImBot\Controller::sendToBot
	 *
	 * @param string $command Text command alias.
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) MESSAGE_ID
	 * 	(int) SESSION_ID
	 * 	(int) BOT_ID
	 * 	(string) BOT_CODE
	 * 	(string) DIALOG_ID
	 * 	(string) MESSAGE
	 * 	(array) FILES
	 * 	(array) ATTACH
	 * 	(array) KEYBOARD
	 * 	(array) PARAMS
	 * 	(array) USER
	 * 	(array) LINE
	 * 	(int) CONNECTOR_MID
	 * ]</pre>
	 *
	 * @return ImBot\Error|array
	 */
	public static function onReceiveCommand($command, $params)
	{
		$result = ['RESULT' => 'OK'];

		if ($command === self::COMMAND_OPERATOR_MESSAGE_ADD)
		{
			Log::write($params, 'NETWORK: operatorMessageAdd');

			static::operatorMessageAdd($params['MESSAGE_ID'], [
				'BOT_ID' => $params['BOT_ID'],
				'BOT_CODE' => $params['BOT_CODE'] ?? '',
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'FILES' => $params['FILES'] ?? '',
				'ATTACH' => $params['ATTACH'] ?? '',
				'KEYBOARD' => $params['KEYBOARD'] ?? '',
				'PARAMS' => $params['PARAMS'] ?? '',
				'USER' => $params['USER'] ?? '',
				'LINE' => $params['LINE'] ?? ''
			]);
		}

		else if($command === self::COMMAND_OPERATOR_MESSAGE_UPDATE)
		{
			Log::write($params, 'NETWORK: operatorMessageUpdate');

			static::operatorMessageUpdate($params['MESSAGE_ID'], [
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'FILES' => $params['FILES'] ?? '',
				'ATTACH' => $params['ATTACH'] ?? '',
				'KEYBOARD' => $params['KEYBOARD'] ?? '',
				'PARAMS' => $params['PARAMS'] ?? '',
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			]);
		}

		else if($command === self::COMMAND_OPERATOR_MESSAGE_DELETE)
		{
			Log::write($params, 'NETWORK: operatorMessageDelete');

			static::operatorMessageDelete($params['MESSAGE_ID'], [
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			]);
		}

		else if($command === self::COMMAND_OPERATOR_START_WRITING)
		{
			Log::write($params, 'NETWORK: operatorStartWriting');

			static::operatorStartWriting([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'USER' => isset($params['USER'])? $params['USER']: ''
			]);
		}

		else if($command === self::COMMAND_OPERATOR_MESSAGE_RECEIVED)
		{
			Log::write($params, 'NETWORK: operatorMessageReceived');

			static::operatorMessageReceived([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
				'SESSION_ID' => $params['SESSION_ID']
			]);
		}

		// operator OL session start
		else if($command === self::COMMAND_START_DIALOG_SESSION)
		{
			Log::write($params, 'NETWORK: startDialogSession');

			static::startDialogSession([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'PARENT_ID' => $params['PARENT_ID'],
				'CLOSE_TERM' => $params['CLOSE_TERM'],
			]);
		}

		// operator OL session finish
		else if($command === self::COMMAND_FINISH_DIALOG_SESSION)
		{
			Log::write($params, 'NETWORK: finishDialogSession');

			static::finishDialogSession([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'PARENT_ID' => $params['PARENT_ID'],
				'CLOSE_TERM' => $params['CLOSE_TERM'],
			]);
		}

		// checking access to public url
		else if($command === self::COMMAND_CHECK_PUBLIC_URL)
		{
			Log::write($params, 'NETWORK: '.self::COMMAND_CHECK_PUBLIC_URL);

			$result = ['PONG' => 'OK'];
		}

		else
		{
			$result = new ImBot\Error(__METHOD__, 'UNKNOWN_COMMAND', 'Command is not found');
		}

		return $result;
	}

	//endregion

	//region Client commands

	/**
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkMembershipRestriction(array $messageFields): bool
	{
		// Allow only one-to-one conversation
		return $messageFields['MESSAGE_TYPE'] === \IM_MESSAGE_PRIVATE;
	}

	/**
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkMessageRestriction(array $messageFields): bool
	{
		$bot = Im\Bot::getCache($messageFields['BOT_ID']);
		if (mb_substr($bot['CODE'], 0, 7) != self::BOT_CODE)
		{
			return false;
		}

		// Allow only this bot as recipient
		return $messageFields['TO_USER_ID'] == $messageFields['BOT_ID'];
	}

	/**
	 * @param array $messageFields
	 * @return bool
	 */
	protected static function checkTypingRestriction(array $messageFields): bool
	{
		// Allow only one-to-one conversation
		return empty($messageFields['CHAT']) && empty($messageFields['RELATION']);
	}

	/**
	 * Sends new client message into network line.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientMessageAdd
	 * @param array $fields Command arguments.
	 * <pre>
	 * [
	 * 	(int) USER_ID
	 * 	(int|string) DIALOG_ID
	 * 	(string|array) MESSAGE
	 * 	(int) BOT_ID
	 * 	(array) FILES
	 * 	(array) ATTACH
	 * 	(array) PARAMS
	 * ]
	 * </pre>
	 *
	 * @return bool|array
	 */
	public static function clientMessageAdd(array $fields)
	{
		$user = self::getUserInfo((int)$fields['USER_ID']);

		$portalTariff = 'box';
		$portalTariffLevel = 'paid';
		$userLevel = self::USER_LEVEL_ADMIN;
		$portalType = 'PRODUCTION';
		$portalTariffName = '';
		$portalCreateTime = '';
		$demoStartTime = 0;
		$botVersion = '';

		if (Main\Loader::includeModule('bitrix24'))
		{
			$portalTariff = \CBitrix24::getLicenseType();
			$portalTariffName = \CBitrix24::getLicenseName();
			$portalCreateTime = \CBitrix24::getCreateTime();

			if (\CBitrix24::isDemoLicense())
			{
				$portalTariff = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
				$portalTariff = $portalTariff.'+demo';
				$portalTariffName = \CBitrix24::getLicenseName("", \CBitrix24::LICENSE_TYPE_PREVIOUS);

				$demoStartTime = (int)Option::get("bitrix24", "DEMO_START");
			}

			if (!$portalCreateTime)
			{
				$portalCreateTime = \time();
			}

			if (\CBitrix24::isIntegrator($fields['USER_ID']))
			{
				$userLevel = self::USER_LEVEL_INTEGRATOR;
			}
			elseif (\CBitrix24::isPortalAdmin($fields['USER_ID']))
			{
				$userLevel = self::USER_LEVEL_ADMIN;
			}
			else
			{
				$userLevel = self::USER_LEVEL_REGULAR;
			}

			$portalType = self::getPortalStage();
			$portalTariffLevel = Support24::getSupportLevel();
		}
		else
		{
			$botVersion = Main\ModuleManager::getVersion('imbot');
		}

		$user = array_merge($user, [
			'TARIFF' => $portalTariff,
			'TARIFF_NAME' => $portalTariffName,
			'TARIFF_LEVEL' => $portalTariffLevel,
			'GEO_DATA' => self::getUserGeoData(),
			'REGISTER' => $portalCreateTime,
			'DEMO' => $demoStartTime,
			'USER_LEVEL' => $userLevel,
			'PORTAL_TYPE' => $portalType,
			'BOT_VERSION' => $botVersion,
		]);

		$messageId = is_array($fields['MESSAGE']) ? (int)$fields['MESSAGE']['ID'] : 0;
		$messageText = is_array($fields['MESSAGE']) ? (string)$fields['MESSAGE']['TEXT'] : (string)$fields['MESSAGE'];

		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientMessageAdd',
			[
				'BOT_ID' => $fields['BOT_ID'],
				'DIALOG_ID' => $fields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'MESSAGE_TEXT' => $messageText,
				'FILES' => $fields['FILES'] ?? null,
				'ATTACH' => $fields['ATTACH'] ?? null,
				'PARAMS' => $fields['PARAMS'] ?? null,
				'USER' => $user,
				'FILES_RAW' => $fields['FILES_RAW'] ?? null,
				'EXTRA_DATA' => $fields['EXTRA_DATA'] ?? null,
			]
		);

		return $response;
	}

	/**
	 * Sends command for update client message in network line.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientMessageUpdate
	 * @param int $messageId Message Id.
	 * @param array $messageFields Command arguments.
	 *
	 * @return bool
	 */
	protected static function clientMessageUpdate($messageId, $messageFields)
	{
		$messageFields['MESSAGE'] = self::removeMentions($messageFields['MESSAGE'] ?? '');

		if ($relatedMessages = (new \CIMHistory)->getRelatedMessages($messageId, 1, 0, false, false))
		{
			$relatedMessageText = '';
			foreach ($relatedMessages['message'] as $message)
			{
				if (
					$message['system'] != 'Y'
					&& isset($message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE])
					&& $message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE] == 'Y'
				)
				{
					$relatedMessageText = $message['text'];
					break;
				}
			}
			if ($relatedMessageText)
			{
				$messageFields['MESSAGE'] =
					str_repeat("-", 54)."\n".
					$relatedMessageText."\n".
					str_repeat("-", 54)."\n".
					$messageFields['MESSAGE'];
			}
		}

		$user = self::getUserInfo((int)($messageFields['FROM_USER_ID'] ?? $messageFields['AUTHOR_ID']));

		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientMessageUpdate',
			[
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $messageFields['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID][0] ?? '',
				'MESSAGE_TEXT' => $messageFields['MESSAGE'],
				'USER' => $user,
			]
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * Sends command to remove client message in network line.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientMessageDelete
	 * @param int $messageId Message Id.
	 * @param array $messageFields Command arguments.
	 *
	 * @return bool
	 */
	protected static function clientMessageDelete($messageId, $messageFields)
	{
		$user = self::getUserInfo((int)($messageFields['FROM_USER_ID'] ?? $messageFields['AUTHOR_ID']));

		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientMessageDelete',
			[
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $messageFields['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID][0] ?? '',
				'USER' => $user,
			]
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * Sends command from user to network line.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientCommandSend
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(int) USER_ID
	 * 	(string) DIALOG_ID
	 * 	(int) MESSAGE_ID
	 * 	(int) CONNECTOR_MID
	 * 	(string) COMMAND
	 * 	(int) COMMAND_ID
	 * 	(string) COMMAND_PARAMS
	 * 	(string) COMMAND_CONTEXT
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function clientCommandSend(array $params)
	{
		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientCommandSend',
			[
				'BOT_ID' => $params['BOT_ID'],
				'USER_ID' => $params['USER_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
				'COMMAND' => $params['COMMAND'],
				'COMMAND_ID' => $params['COMMAND_ID'],
				'COMMAND_PARAMS' => $params['COMMAND_PARAMS'],
				'COMMAND_CONTEXT' => $params['COMMAND_CONTEXT'],
			],
			false
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientStartWriting
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) USER_ID
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function clientStartWriting($params)
	{
		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientStartWriting',
			[
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'USER_ID' => $params['USER_ID'],
			],
			false
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientSessionVote
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) USER_ID
	 * 	(int) SESSION_ID
	 * 	(int) MESSAGE_ID
	 * 	(array) MESSAGE
	 * 	(string) ACTION
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function clientSessionVote($params)
	{
		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientSessionVote',
			[
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'MESSAGE_ID' => $params['MESSAGE']['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID][0],
				'ACTION' => $params['ACTION'],
				'USER_ID' => $params['USER_ID'],
				'VOTE_IP' => $_SERVER['REMOTE_ADDR'],
			],
			false
		);

		return $response !== false && !isset($response['error']);
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientMessageReceived
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) MESSAGE_ID
	 * 	(int) CONNECTOR_MID
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function clientMessageReceived($params)
	{
		$http = self::instanceHttpClient();
		$response = $http->query(
			'clientMessageReceived',
			[
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			]
		);

		return $response !== false && !isset($response['error']);
	}

	//endregion

	//region Operator commands

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) BOT_CODE
	 * 	(string) DIALOG_ID
	 * 	(string) MESSAGE
	 * 	(array) FILES
	 * 	(array) ATTACH
	 * 	(array) KEYBOARD
	 * 	(array) PARAMS
	 * 	(array) USER
	 * 	(array) LINE
	 * ]
	 * </pre>
	 * @return bool
	 */
	protected static function operatorMessageAdd($messageId, $messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		if (!empty($messageFields['BOT_CODE']))
		{
			$list = Im\Bot::getListCache();
			foreach ($list as $botData)
			{
				if ($botData['TYPE'] != Im\Bot::TYPE_NETWORK)
				{
					continue;
				}

				if ($messageFields['BOT_CODE'] == $botData['APP_ID'])
				{
					$messageFields['BOT_ID'] = (int)$botData['BOT_ID'];
					break;
				}
			}
			if ((int)$messageFields['BOT_ID'] <= 0)
			{
				return false;
			}
		}

		$message = [
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE'],
			'PARAMS' => [],
			'URL_PREVIEW' => 'Y',
		];

		if (!empty($messageFields['PARAMS']))
		{
			$message['PARAMS'] = $messageFields['PARAMS'];
		}

		if ($messageId > 0)
		{
			$message['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID] = [$messageId];
		}

		if (!empty($messageFields['KEYBOARD']))
		{
			$message['KEYBOARD'] = self::processIncomingKeyboard($messageFields);
		}

		if (!empty($messageFields['ATTACH']))
		{
			$message['ATTACH'] = \CIMMessageParamAttach::getAttachByJson($messageFields['ATTACH']);
		}

		if (!empty($messageFields['FILES']))
		{
			if (!($message['ATTACH'] instanceof \CIMMessageParamAttach))
			{
				$message['ATTACH'] = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $value)
			{
				if ($value['type'] === 'image')
				{
					$message['ATTACH']->addImages([[
						'NAME' => $value['name'],
						'LINK' => $value['link'],
						'WIDTH' => (int)$value['width'],
						'HEIGHT' => (int)$value['height'],
					]]);
				}
				else
				{
					$message['ATTACH']->addFiles([[
						'NAME' => $value['name'],
						'LINK' => $value['link'],
						'SIZE' => $value['size'],
					]]);
				}
			}
		}

		// url preview
		if (isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] === 'N')
		{
			$message['URL_PREVIEW'] = 'N';
		}

		// system
		if (
			isset($messageFields['PARAMS'], $messageFields['PARAMS']['CLASS'])
			&& $messageFields['PARAMS']['CLASS'] === 'bx-messenger-content-item-ol-output'
		)
		{
			$message['URL_PREVIEW'] = 'N';
		}

		if (!empty($messageFields['USER']))
		{
			$message['PARAMS']['USER_ID'] = $messageFields['USER']['ID'];
			$nameTemplateSite = \CSite::getNameFormat(false);
			$userName = \CUser::formatName($nameTemplateSite, $messageFields['USER'], true, false);
			if ($userName)
			{
				$message['PARAMS']['NAME'] = $userName;
			}
			$userAvatar = Im\User::uploadAvatar($messageFields['USER']['PERSONAL_PHOTO'], $messageFields['BOT_ID']);
			if ($userAvatar)
			{
				$message['PARAMS']['AVATAR'] = $userAvatar;
			}
		}

		// Update bot fields (name, desc, avatar, welcome mess) using data from incoming message
		if (!empty($messageFields['LINE']))
		{
			$needUpdateBotFields = true;
			$needUpdateBotAvatar = true;

			$bot = Im\Bot::getCache($messageFields['BOT_ID']);
			if ($bot['MODULE_ID'] && Main\Loader::includeModule($bot['MODULE_ID']) && class_exists($bot["CLASS"]))
			{
				if (method_exists($bot["CLASS"], 'isNeedUpdateBotFieldsAfterNewMessage'))
				{
					$needUpdateBotFields = call_user_func_array([$bot["CLASS"], 'isNeedUpdateBotFieldsAfterNewMessage'], []);
				}
				if (method_exists($bot["CLASS"], 'isNeedUpdateBotAvatarAfterNewMessage'))
				{
					$needUpdateBotAvatar = call_user_func_array([$bot["CLASS"], 'isNeedUpdateBotAvatarAfterNewMessage'], []);
				}
			}
			if ($needUpdateBotFields || $needUpdateBotAvatar)
			{
				$botData = Im\User::getInstance($messageFields['BOT_ID']);

				if ($needUpdateBotFields)
				{
					$updateFields = [];
					if ($messageFields['LINE']['NAME'] != htmlspecialcharsback($botData->getName()))
					{
						$updateFields['NAME'] = $messageFields['LINE']['NAME'];
					}
					if ($messageFields['LINE']['DESC'] != htmlspecialcharsback($botData->getWorkPosition()))
					{
						$updateFields['WORK_POSITION'] = $messageFields['LINE']['DESC'];
					}
					if ($messageFields['LINE']['WELCOME_MESSAGE'] != $bot['TEXT_PRIVATE_WELCOME_MESSAGE'])
					{
						Im\Bot::update(['BOT_ID' => $messageFields['BOT_ID']], [
							'TEXT_PRIVATE_WELCOME_MESSAGE' => $messageFields['LINE']['WELCOME_MESSAGE']
						]);
					}
					if (!empty($updateFields))
					{
						self::getCurrentUser()->update($messageFields['BOT_ID'], $updateFields);
					}
				}

				if ($needUpdateBotAvatar && !empty($messageFields['LINE']['AVATAR']))
				{
					$botAvatar = Im\User::uploadAvatar($messageFields['LINE']['AVATAR'], $messageFields['BOT_ID']);
					if ($botAvatar && $botData->getAvatarId() != $botAvatar)
					{
						$connection = Main\Application::getConnection();
						$connection->query("UPDATE b_user SET PERSONAL_PHOTO = ".(int)$botAvatar." WHERE ID = ".(int)$messageFields['BOT_ID']);
					}
				}

			}
		}

		$connectorMid = Im\Bot::addMessage(['BOT_ID' => $messageFields['BOT_ID']], $message);

		if ($messageId > 0)
		{
			self::clientMessageReceived([
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $connectorMid,
			]);
		}

		return true;
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) BOT_CODE
	 * 	(string) DIALOG_ID
	 * 	(string) MESSAGE
	 * 	(int) CONNECTOR_MID
	 * 	(array) KEYBOARD
	 * 	(array) FILES
	 * 	(array) ATTACH
	 * 	(string) URL_PREVIEW - Y|N
	 * ]</pre>
	 *
	 * @return bool
	 */
	protected static function operatorMessageUpdate($messageId, $messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$messageParamData = Im\Model\MessageParamTable::getList([
			'select' => ['PARAM_VALUE'],
			'filter' => ['=MESSAGE_ID' => $messageId, '=PARAM_NAME' => self::MESSAGE_PARAM_CONNECTOR_MID]
		])->fetch();
		if (!$messageParamData || $messageParamData['PARAM_VALUE'] != $messageFields['CONNECTOR_MID'])
		{
			return false;
		}

		$attach = null;
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::getAttachByJson($messageFields['ATTACH']);
		}

		$keyboard = [];
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = self::processIncomingKeyboard($messageFields);
		}

		if (!empty($messageFields['FILES']))
		{
			if (!$attach)
			{
				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $key => $value)
			{
				$attach->addFiles([[
					'NAME' => $value['name'],
					'LINK' => $value['link'],
					'SIZE' => $value['size'],
				]]);
			}
		}

		$messageFields['URL_PREVIEW'] = isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == 'N'? 'N': 'Y';

		return Im\Bot::updateMessage(['BOT_ID' => $messageFields['BOT_ID']], [
			'MESSAGE_ID' => $messageId,
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE'],
			'URL_PREVIEW' => $messageFields['URL_PREVIEW'],
			'KEYBOARD' => $keyboard,
			'ATTACH' => $attach,
			'SKIP_CONNECTOR' => 'Y',
			'EDIT_FLAG' => 'Y',
		]);
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Command arguments.
	 *
	 * @return bool
	 */
	protected static function operatorMessageDelete($messageId, $messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$messageParamData = Im\Model\MessageParamTable::getList([
			'select' => ['PARAM_VALUE'],
			'filter' => ['=MESSAGE_ID' => $messageId, '=PARAM_NAME' => self::MESSAGE_PARAM_CONNECTOR_MID]
		])->fetch();
		if (!$messageParamData || $messageParamData['PARAM_VALUE'] != $messageFields['CONNECTOR_MID'])
		{
			return false;
		}

		return Im\Bot::deleteMessage(['BOT_ID' => $messageFields['BOT_ID']], $messageId);
	}

	/**
	 * @param array $params Command arguments.
	 *
	 * @return bool
	 */
	protected static function operatorStartWriting($params)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$userName = '';
		if (!empty($params['USER']))
		{
			$params['USER_ID'] = $params['USER']['ID'];
			$nameTemplateSite = \CSite::getNameFormat(false);
			$userName = \CUser::formatName($nameTemplateSite, $params['USER'], true, false);
			if ($userName)
			{
				$params['NAME'] = $userName;
			}
		}

		return Im\Bot::startWriting(['BOT_ID' => $params['BOT_ID']], $params['DIALOG_ID'], $userName);
	}

	/**
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) MESSAGE_ID
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) CONNECTOR_MID
	 * 	(int) SESSION_ID
	 * ]
	 * </pre>
	 * @return bool
	 */
	protected static function operatorMessageReceived($params)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		$messageData = Im\Model\MessageTable::getList([
			'select' => ['CHAT_ID'],
			'filter' => ['=ID' => $params['MESSAGE_ID']]
		])->fetch();
		if (!$messageData)
		{
			return false;
		}

		$chatId = -1;
		if (\Bitrix\Im\Common::isChatId($params['DIALOG_ID']))
		{
			$chatCheckRes = \Bitrix\Im\Model\BotChatTable::getList([
				'select' => ['CHAT_ID'],
				'filter' => [
					'=BOT_ID' => (int)$params['BOT_ID'],
					'=CHAT_ID' => (int)\Bitrix\Im\Dialog::getChatId($params['DIALOG_ID']),
				]
			]);
			if ($chatCheck = $chatCheckRes->fetch())
			{
				$chatId = (int)$chatCheck['CHAT_ID'];
			}
		}
		else
		{
			$chatId = (int)\CIMMessage::getChatId($params['BOT_ID'], $params['DIALOG_ID']);
		}
		if ($chatId <= 0)
		{
			return false;
		}

		if ($messageData['CHAT_ID'] != $chatId)
		{
			return false;
		}

		$messageParamData = Im\Model\MessageParamTable::getList([
			'select' => ['PARAM_VALUE'],
			'filter' => [
				'=MESSAGE_ID' => $params['MESSAGE_ID'],
				'=PARAM_NAME' => self::MESSAGE_PARAM_SENDING
			]
		])->fetch();
		if (!$messageParamData || $messageParamData['PARAM_VALUE'] != 'Y')
		{
			return false;
		}

		$messageParams = [
			self::MESSAGE_PARAM_SENDING => 'N',
			self::MESSAGE_PARAM_SENDING_TIME => 0,
		];
		if ($params['CONNECTOR_MID'])
		{
			$messageParams[self::MESSAGE_PARAM_CONNECTOR_MID] = $params['CONNECTOR_MID'];
		}
		if ((int)$params['SESSION_ID'] > 0)
		{
			$messageParams[self::MESSAGE_PARAM_SESSION_ID] = $params['SESSION_ID'];
		}

		\CIMMessageParam::set($params['MESSAGE_ID'], $messageParams);
		\CIMMessageParam::sendPull($params['MESSAGE_ID'], array_keys($messageParams));

		if ((int)$params['SESSION_ID'] > 0)
		{
			self::instanceDialogSession((int)$params['BOT_ID'], $params['DIALOG_ID'])
				->setSessionId((int)$params['SESSION_ID']);
		}

		return true;
	}

	//endregion

	//region Event handlers

	/**
	 * Event handler when bot join to chat.
	 *
	 * @param string $dialogId Dialog Id.
	 * @param array $joinFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields)
	{
		$botData = Im\Bot::getListCache();

		if (
			($bot = $botData[$joinFields['BOT_ID']])
			&& $bot["TEXT_PRIVATE_WELCOME_MESSAGE"] <> ''
			&& $joinFields['CHAT_TYPE'] == \IM_MESSAGE_PRIVATE
			&& $joinFields['FROM_USER_ID'] != $joinFields['BOT_ID']
		)
		{
			$messageFields = [
				'DIALOG_ID' => $joinFields['USER_ID'],
				'FROM_USER_ID' => $joinFields['BOT_ID'],
				'MESSAGE' => static::replacePlaceholders($bot['TEXT_PRIVATE_WELCOME_MESSAGE'], $joinFields['USER_ID']),
				'URL_PREVIEW' => 'N',
				'PARAMS' => [self::MESSAGE_PARAM_ALLOW_QUOTE => 'N'],
			];

			Application::getInstance()->addBackgroundJob(
				[static::class, 'delayShowingMessage'],
				[$joinFields['BOT_ID'], $messageFields],
				Application::JOB_PRIORITY_LOW
			);
		}

		return true;
	}

	/**
	 * @param array $messageFields
	 * @return bool
	 */
	public static function delayShowingMessage($botId, $messageFields)
	{
		return Im\Bot::addMessage(['BOT_ID' => $botId], $messageFields);
	}

	/**
	 * Event handler on message add.
	 * @see \Bitrix\Im\Bot::onMessageAdd
	 *
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(int) CHAT_ID
	 * 	(int) FROM_USER_ID
	 * 	(string) DIALOG_ID
	 * 	(string) MESSAGE_TYPE
	 * 	(string) MESSAGE
	 * 	(string) SYSTEM = Y|N
	 * 	(array) FILES
	 * 	(array) PARAMS
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		if (isset($messageFields['SYSTEM']) && $messageFields['SYSTEM'] === 'Y')
		{
			return false;
		}

		if (!static::checkMembershipRestriction($messageFields))
		{
			(new \CIMChat($messageFields['BOT_ID']))->deleteUser($messageFields['CHAT_ID'], $messageFields['BOT_ID']);
			return false;
		}

		if (!static::checkMessageRestriction($messageFields))
		{
			return false;
		}

		self::instanceDialogSession((int)$messageFields['BOT_ID'], $messageFields['DIALOG_ID'])->start();

		// check user vote for session by direct text input '1' or '0'
		if (self::checkSessionVoteMessage($messageFields))
		{
			return true;
		}

		$files = [];
		if (isset($messageFields['FILES']) && Main\Loader::includeModule('disk'))
		{
			foreach ($messageFields['FILES'] as $file)
			{
				$fileModel = \Bitrix\Disk\File::loadById($file['id']);
				if (!$fileModel)
				{
					continue;
				}

				$file['link'] = \CIMDisk::getFileLink($fileModel);
				if (!$file['link'])
				{
					continue;
				}

				$merged = false;
				if (\Bitrix\Disk\TypeFile::isImage($fileModel))
				{
					$source = $fileModel->getFile();
					if ($source)
					{
						$files[] = [
							'name' => $file['name'],
							'type' => $file['type'],
							'link' => $file['link'],
							'width' => (int)$source["WIDTH"],
							'height' => (int)$source["HEIGHT"],
							'size' => $file['size']
						];
						$merged = true;
					}
				}

				if (!$merged)
				{
					$files[] = [
						'name' => $file['name'],
						'type' => $file['type'],
						'link' => $file['link'],
						'size' => $file['size']
					];
				}
			}
		}

		$messageFields['MESSAGE'] = self::removeMentions($messageFields['MESSAGE'] ?? '');

		$messageParams = $messageFields['PARAMS'] ?? [];

		if ($relatedMessages = (new \CIMHistory)->getRelatedMessages($messageId, 1, 0, false, false))
		{
			foreach ($relatedMessages['message'] as $message)
			{
				if (
					$message['system'] != 'Y'
					&& isset($message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE])
					&& $message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE] === 'Y'
				)
				{
					$messageFields['MESSAGE'] =
						str_repeat("-", 54)."\n".
						$message['text']. "\n".
						str_repeat("-", 54)."\n".
						$messageFields['MESSAGE'];
					break;
				}
			}
		}

		\CIMMessageParam::set($messageId, [
			self::MESSAGE_PARAM_SENDING => 'Y',
			self::MESSAGE_PARAM_SENDING_TIME => \time()
		]);

		$isSuccessful = true;

		$result = self::clientMessageAdd([
			'BOT_ID' => $messageFields['BOT_ID'],
			'USER_ID' => $messageFields['FROM_USER_ID'],
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => [
				'ID' => $messageId,
				'TYPE' => $messageFields['MESSAGE_TYPE'],
				'TEXT' => $messageFields['MESSAGE'],
			],
			'FILES' => $files,
			'PARAMS' => $messageParams,
		]);
		if (isset($result['error']))
		{
			$isSuccessful = false;
			self::$lastError = new ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);

			$message = '';

			if (self::getError()->code == 'LINE_DISABLED')
			{
				if (class_exists('Bitrix\ImBot\Bot\Support24'))
				{
					$message = ImBot\Bot\Support24::replacePlaceholders(
						ImBot\Bot\Support24::getMessage('LINE_DISABLED'),
						$messageFields['FROM_USER_ID']
					);
				}

				if (empty($message))
				{
					$message = Loc::getMessage('IMBOT_NETWORK_ERROR_LINE_DISABLED');
				}
			}
			else
			{
				$message = Loc::getMessage('IMBOT_NETWORK_ERROR_NOT_FOUND');
			}

			Im\Bot::addMessage(['BOT_ID' => $messageFields['BOT_ID']], [
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => $message,
				'SYSTEM' => 'Y'
			]);

			\CIMMessageParam::set($messageId, [
				self::MESSAGE_PARAM_DELIVERED => 'N',
				self::MESSAGE_PARAM_SENDING => 'N',
				self::MESSAGE_PARAM_SENDING_TIME => 0
			]);
		}

		\CIMMessageParam::sendPull($messageId, [
			self::MESSAGE_PARAM_DELIVERED,
			self::MESSAGE_PARAM_SENDING,
			self::MESSAGE_PARAM_SENDING_TIME
		]);

		return $isSuccessful;
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageUpdate($messageId, $messageFields)
	{
		if (!static::checkMessageRestriction($messageFields))
		{
			return false;
		}

		return static::clientMessageUpdate($messageId, $messageFields);
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageDelete($messageId, $messageFields)
	{
		if (!static::checkMessageRestriction($messageFields))
		{
			return false;
		}

		return static::clientMessageDelete($messageId, $messageFields);
	}

	/**
	 * @see \Bitrix\ImBot\Event::onStartWriting
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onStartWriting($params)
	{
		if ($params['BOT_ID'] == $params['DIALOG_ID'])
		{
			$params['DIALOG_ID'] = (string)$params['USER_ID'];
		}

		if (!static::checkTypingRestriction($params))
		{
			return false;
		}

		return static::clientStartWriting($params);
	}

	/**
	 * Handler for "im:OnSessionVote" event.
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onSessionVote(array $params): bool
	{
		if ($params['BOT_ID'] == $params['DIALOG_ID'])
		{
			$params['DIALOG_ID'] = (string)$params['USER_ID'];
		}

		return static::clientSessionVote($params);
	}

	/**
	 * Performs checking if it is user voting for session by direct text input '1' or '0'.
	 * @param array $messageFields Message arguments.
	 * @return bool
	 */
	public static function checkSessionVoteMessage(array $messageFields): bool
	{
		$botId = static::getBotId() ?: (int)$messageFields['BOT_ID'];
		if (
			$botId > 0
			&& isset($messageFields['MESSAGE'], $messageFields['COMMAND_CONTEXT'])
			&& $messageFields['COMMAND_CONTEXT'] === 'TEXTAREA'
			&& ($messageFields['MESSAGE'] === '1' || $messageFields['MESSAGE'] === '0')
		)
		{

			$voteMessage = null;

			$fromUserId = (int)$messageFields['FROM_USER_ID'];
			if (Im\Common::isChatId($messageFields['DIALOG_ID']))
			{
				if (!static::checkMessageRestriction($messageFields))
				{
					return false;
				}
				$dialogId = (string)$messageFields['DIALOG_ID'];
				$lastMessages = (new \CIMChat)->getLastMessage((int)$messageFields['CHAT_ID'], $botId);
			}
			else
			{
				$dialogId = (string)$messageFields['FROM_USER_ID'];
				$lastMessages = (new \CIMMessage)->getLastMessage($fromUserId, $botId, false, false);
			}

			$i = 0;
			foreach ($lastMessages['message'] as $message)
			{
				if (
					isset($message['params'])
					&& isset($message['params'][self::MESSAGE_PARAM_IMOL_VOTE])
					&& isset($message['params'][self::MESSAGE_PARAM_IMOL_VOTE_LIKE])
					&& isset($message['params'][self::MESSAGE_PARAM_IMOL_VOTE_DISLIKE])
					&& (int)$message['params'][self::MESSAGE_PARAM_IMOL_VOTE] > 0 //SESSION_ID
				)
				{
					$voteMessage = $message;
					break;
				}
				// check only 7 last messages
				if (++$i > 10)
				{
					break;
				}
			}
			if ($voteMessage)
			{
				$isActionLike = $messageFields['MESSAGE'] === '1';
				$sessionId = (int)$voteMessage['params'][self::MESSAGE_PARAM_IMOL_VOTE];

				\CIMMessageParam::set($voteMessage['id'], [self::MESSAGE_PARAM_IMOL_VOTE => ($isActionLike ? 'like' : 'dislike')]);
				\CIMMessageParam::sendPull($voteMessage['id'], [self::MESSAGE_PARAM_IMOL_VOTE]);

				self::sendMessage([
					'DIALOG_ID' => $dialogId,
					'MESSAGE' => $isActionLike
						? $voteMessage['params'][self::MESSAGE_PARAM_IMOL_VOTE_LIKE]
						: $voteMessage['params'][self::MESSAGE_PARAM_IMOL_VOTE_DISLIKE],
					'SYSTEM' => 'N',
					'URL_PREVIEW' => 'N',
				]);

				$voteParams = [
					'BOT_ID' => $botId,
					'DIALOG_ID' => $dialogId,
					'USER_ID' => $fromUserId,
					'ACTION' => ($isActionLike ? 'like' : 'dislike'),
					'SESSION_ID' => $sessionId,
					'MESSAGE' => [
						'MESSAGE' => $voteMessage['text'],
						'PARAMS' => $voteMessage['params'],//CONNECTOR_MID
					]
				];

				static::onSessionVote($voteParams);

				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $command Text command alias.
	 * @param array $params Command arguments.
	 *
	 * @return array|ImBot\Error
	 */
	public static function onAnswerAdd($command, $params)
	{
		return static::onReceiveCommand($command, $params);
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 * <pre>
	 * [
	 * 	(string) COMMAND
	 * 	(string) COMMAND_PARAMS
	 * 	(string) COMMAND_CONTEXT == KEYBOARD
	 * 	(int) COMMAND_ID
	 * 	(string) MESSAGE_TYPE == P
	 * 	(string) SYSTEM != Y
	 * 	(int) TO_USER_ID
	 * 	(int) FROM_USER_ID
	 * 	(int) CONNECTOR_MID
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields)
	{
		$command = static::getCommandByMessage($messageFields);
		if (!$command)
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_NETWORK_SESSION)
		{
			if (empty($messageFields['CONNECTOR_MID']))
			{
				$messageParams = \CIMMessageParam::get($messageId, self::MESSAGE_PARAM_CONNECTOR_MID);
				$messageFields['CONNECTOR_MID'] = $messageParams[0];
			}

			self::disableMessageButtons((int)$messageId);

			self::clientCommandSend([
				'BOT_ID' => static::getBotId(),
				'USER_ID' => $messageFields['FROM_USER_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageFields['CONNECTOR_MID'],
				'CONNECTOR_MID' => $messageId,
				'COMMAND' => $messageFields['COMMAND'],
				'COMMAND_ID' => $messageFields['COMMAND_ID'],
				'COMMAND_PARAMS' => $messageFields['COMMAND_PARAMS'],
				'COMMAND_CONTEXT' => $messageFields['COMMAND_CONTEXT'],
			]);

			return true;
		}

		elseif ($messageFields['COMMAND'] === self::COMMAND_UNREGISTER)
		{
			$grantAccess = Main\ModuleManager::isModuleInstalled('bitrix24')
				? self::getCurrentUser()->canDoOperation('bitrix24_config')
				: self::getCurrentUser()->isAdmin();

			if ($grantAccess)
			{
				$botData = Im\Bot::getCache($messageFields['TO_USER_ID']);
				if ($botData['CLASS'] === __CLASS__)
				{
					return self::unRegister($botData['APP_ID']);
				}
			}
		}

		return false;
	}


	/**
	 * Compatibility alias to the onCommandAdd method.
	 * @todo Remove it.
	 */
	public static function onLocalCommandAdd($messageId, $messageFields)
	{
		return self::onCommandAdd($messageId, $messageFields);
	}

	//endregion

	//region User roles

	/**
	 * Returns current context user.
	 * @return \CUser
	 */
	protected static function getCurrentUser(): \CUser
	{
		global $USER;
		if ($USER instanceof \CUser)
		{
			return $USER;
		}

		return (new \CUser());
	}

	/**
	 * Checks if user has an integrator access role.
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isUserIntegrator($userId)
	{
		if (!$userId)
		{
			return false;
		}

		static $isIntegrator = [];

		if (!isset($isIntegrator[$userId]))
		{
			$result = false;
			if (Main\Loader::includeModule('bitrix24'))
			{
				$result = \CBitrix24::isIntegrator($userId);
			}

			$isIntegrator[$userId] = $result;
		}

		return $isIntegrator[$userId];
	}

	/**
	 * Checks if user has an portal administrator access role.
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public static function isUserAdmin($userId)
	{
		static $isAdmin = [];
		if (!isset($isAdmin[$userId]))
		{
			$user = self::getCurrentUser();
			if (Main\Loader::includeModule('bitrix24'))
			{
				if (
					$user->isAuthorized()
					&& $user->getId() === $userId
					&& $user->isAdmin()
				)
				{
					$result = true;
				}
				else
				{
					$result = \CBitrix24::isPortalAdmin($userId);
				}
			}
			else
			{
				if ($user->isAuthorized() && $user->getId() === $userId)
				{
					$result = $user->isAdmin();
				}
				else
				{
					$result = false;

					$groups = Main\UserTable::getUserGroupIds($userId);
					foreach ($groups as $groupId)
					{
						if ($groupId == 1)
						{
							$result = true;
							break;
						}
					}
				}
			}

			$isAdmin[$userId] = $result;
		}

		return $isAdmin[$userId];
	}

	//endregion

	//region Bitrix24

	/**
	 * Detects bitrix24 portal's stage type.
	 * @return string
	 */
	protected static function getPortalStage(): string
	{
		static $portalType;
		if ($portalType === null)
		{
			$portalType = 'PRODUCTION';

			if (Main\Loader::includeModule('bitrix24'))
			{
				// BX24_IS_STAGE && BX24_IS_ETALON
				// true true - is an etalon
				// true false - is a stage
				// false false - is a production
				if (\CBitrix24::isStage() && \CBitrix24::isEtalon())
				{
					$portalType = 'ETALON';
				}
				elseif (\CBitrix24::isStage() && !\CBitrix24::isEtalon())
				{
					$portalType = 'STAGE';
				}
			}
		}

		return $portalType;
	}

	//endregion

	//region Dialogs

	/**
	 * Returns bot's recent dialogs.
	 *
	 * @param int $hoursDepth Depth into past. Default: 7 days.
	 *
	 * @return \Generator|iterable
	 * <pre>
	 * [
	 *   0 => [
	 *      (int) USER_ID
	 *      (int) CHAT_ID
	 *      (string) RECENTLY_TALK
	 *      (int) MESSAGE_ID
	 *   ],
	 *   ...
	 * </pre>
	 */
	public static function getRecentDialogs(int $hoursDepth = 168): iterable
	{
		$botId = static::getBotId();
		$depth = $hoursDepth * 3600;
		$query = "
			SELECT
				RU.USER_ID,
				RU.CHAT_ID,
				RU.MESSAGE_TYPE, 
				IF(UNIX_TIMESTAMP(M.DATE_CREATE) > UNIX_TIMESTAMP() - {$depth}, 'Y', 'N') RECENTLY_TALK,
				M.ID AS MESSAGE_ID
			FROM
				b_im_relation RB
				INNER JOIN b_im_relation RU 
					ON RB.CHAT_ID = RU.CHAT_ID
				LEFT JOIN b_im_message M 
					ON RU.LAST_ID = M.ID
			WHERE
				RB.USER_ID = {$botId}
				and RU.USER_ID != {$botId}
				and RB.MESSAGE_TYPE IN('".\IM_MESSAGE_PRIVATE."', '".\IM_MESSAGE_CHAT."')
				and RU.MESSAGE_TYPE IN('".\IM_MESSAGE_PRIVATE."', '".\IM_MESSAGE_CHAT."')
		";
		if ($res = Main\Application::getInstance()->getConnection()->query($query))
		{
			while ($dialog = $res->fetch())
			{
				if ($dialog['USER_ID'] == $botId)
				{
					continue;
				}

				yield $dialog;
			}
		}
	}

	/**
	 * @param string $dialogId
	 * @return int
	 */
	protected static function getChatId(string $dialogId): int
	{
		$chatId = -1;
		if (Im\Common::isChatId($dialogId))
		{
			$chatCheckRes = \Bitrix\Im\Model\BotChatTable::getList([
				'select' => ['CHAT_ID'],
				'filter' => [
					'=BOT_ID' => (int)static::getBotId(),
					'=CHAT_ID' => (int)Im\Dialog::getChatId($dialogId),
				]
			]);
			if ($chatCheck = $chatCheckRes->fetch())
			{
				$chatId = (int)$chatCheck['CHAT_ID'];
			}
		}
		else
		{
			$chatId = (int)\CIMMessage::getChatId(static::getBotId(), $dialogId);
		}

		return $chatId;
	}

	//endregion

	//region Service functions

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function checkCodeBlacklist(string $code): bool
	{
		return in_array($code, self::$blackListOfCodes, true);
	}

	/**
	 * @return \CMain
	 */
	protected static function getApplication(): \CMain
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * Returns web client.
	 *
	 * @return ImBot\Http
	 */
	protected static function instanceHttpClient(): ImBot\Http
	{
		if (!(self::$httpClient instanceof ImBot\Http))
		{
			$botCode = self::BOT_CODE;
			if (self::BOT_CODE != static::BOT_CODE)
			{
				$botCode .= '.'. static::BOT_CODE; //network.support
			}

			self::$httpClient = new ImBot\Http($botCode);
		}

		return self::$httpClient;
	}

	/**
	 * Replace web client.
	 *
	 * @return ImBot\Http
	 */
	public static function initHttpClient(ImBot\Http $httpClient): void
	{
		self::$httpClient = $httpClient;
	}

	/**
	 * Returns user info.
	 *
	 * @param int $userId User Id.
	 *
	 * @return array User data:
	 * <pre>
	 * [
	 * 	(int) ID
	 * 	(string) NAME
	 * 	(string) LAST_NAME
	 * 	(string) PERSONAL_GENDER
	 * 	(string) WORK_POSITION
	 * 	(string) EMAIL
	 * 	(string) PERSONAL_PHOTO
	 * ]
	 * </pre>.
	 */
	protected static function getUserInfo(int $userId): array
	{
		$result = [];

		$orm = Main\UserTable::getById($userId);
		if ($user = $orm->fetch())
		{
			$avatarUrl = '';
			if ($user['PERSONAL_PHOTO'])
			{
				$fileTmp = \CFile::resizeImageGet(
					$user['PERSONAL_PHOTO'],
					['width' => 300, 'height' => 300],
					\BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				if ($fileTmp['src'])
				{
					$avatarUrl = mb_substr($fileTmp['src'], 0, 4) == 'http'
						? $fileTmp['src']
						: ImBot\Http::getServerAddress(). $fileTmp['src'];

					$avatarUrl = \CHTTP::urnEncode($avatarUrl);
				}
			}

			$result = [
				'ID' => $user['ID'],
				'NAME' => $user['NAME'],
				'LAST_NAME' => $user['LAST_NAME'],
				'PERSONAL_GENDER' => $user['PERSONAL_GENDER'],
				'WORK_POSITION' => $user['WORK_POSITION'],
				'EMAIL' => $user['EMAIL'],
				'PERSONAL_PHOTO' => $avatarUrl,
			];
		}

		return $result;
	}

	/**
	 * Collects some available geo data.
	 * @return string
	 */
	public static function getUserGeoData()
	{
		if (isset(Main\Application::getInstance()->getKernelSession()['IMBOT']['GEO_DATA']))
		{
			return Main\Application::getInstance()->getKernelSession()['IMBOT']['GEO_DATA'];
		}

		$countryCode = Main\Service\GeoIp\Manager::getCountryCode();
		if (!$countryCode)
		{
			return defined('BOT_CLIENT_GEO_DATA') ? BOT_CLIENT_GEO_DATA : '';
		}

		$countryName = Main\Service\GeoIp\Manager::getCountryName('', 'ru');
		if (!$countryName)
		{
			$countryName = Main\Service\GeoIp\Manager::getCountryName();
		}

		$cityName = Main\Service\GeoIp\Manager::getCityName('', 'ru');
		if (!$cityName)
		{
			$cityName = Main\Service\GeoIp\Manager::getCityName();
		}

		$result = $countryCode.($countryName? ' / '.$countryName: '').($cityName? ' / '.$cityName: '');
		
		Main\Application::getInstance()->getKernelSession()['IMBOT']['GEO_DATA'] = $result;

		return $result;
	}


	/**
	 * List of unlimited users.
	 *
	 * @see \CBitrix24BusinessTools::getUnlimUsers
	 * @return int[]
	 */
	public static function getBusinessUsers()
	{
		$users = [];
		$option = Option::get('bitrix24', 'business_tools_unlim_users', false);
		if ($option)
		{
			$users = array_map('intVal', explode(",", $option));
		}

		return $users;
	}

	/**
	 * List of administrator users.
	 * @return int[]
	 */
	public static function getAdministrators()
	{
		static $users;
		if ($users === null)
		{
			$users = [];

			if (Main\Loader::includeModule('bitrix24'))
			{
				$users = \CBitrix24::getAllAdminId();
			}
			else
			{
				$res = \CGroup::getGroupUserEx(1);
				while ($row = $res->fetch())
				{
					$users[] = (int)$row["USER_ID"];
				}
			}
		}

		return $users;
	}

	/**
	 * Returns phrase according its code.
	 * @param string $messageCode Phrase code.
	 *
	 * @return string
	 */
	public static function getLangMessage($messageCode = ''): string
	{
		return Loc::getMessage($messageCode);
	}

	/**
	 * Replaces standard placeholders.
	 *
	 * @param string $message
	 * @param int $userId
	 *
	 * @return string
	 */
	public static function replacePlaceholders($message, $userId = 0): string
	{
		if (!Main\Loader::includeModule('im'))
		{
			return $message;
		}

		if ($userId)
		{
			$message = str_replace(
				[
					'#USER_NAME#',
					'#USER_LAST_NAME#',
					'#USER_FULL_NAME#',
				],
				[
					Im\User::getInstance($userId)->getName(false),
					Im\User::getInstance($userId)->getLastName(false),
					Im\User::getInstance($userId)->getFullName(false),
				],
				$message
			);
		}

		return $message;
	}

	/**
	 * Removes mentions from message.
	 *
	 * @param string $messageText
	 *
	 * @return string
	 */
	protected static function removeMentions(string $messageText): string
	{
		if ($messageText === '0')
		{
			return '#ZERO#';
		}
		$messageText = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageText);
		$messageText = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageText);

		return $messageText;
	}

	/**
	 * Dowlloads user avatar via supplied image url.
	 * @param string $avatarUrl Image url.
	 * @param string $hash Salt string for auto naming.
	 *
	 * @return array|string
	 */
	public static function uploadAvatar($avatarUrl = '', $hash = '')
	{
		if (!$avatarUrl)
		{
			return '';
		}

		if (!$ar = parse_url($avatarUrl))
		{
			return '';
		}

		if (!preg_match('#\.(png|jpg|jpeg|gif|webp)$#i', $ar['path'], $matches))
		{
			return '';
		}

		try
		{
			$hash = md5($hash . $avatarUrl);
			$tempPath =  \CFile::getTempName('', $hash.'.'.$matches[1]);

			$http = new Main\Web\HttpClient();
			$http->setPrivateIp(false);
			if ($http->download($avatarUrl, $tempPath))
			{
				$recordFile = \CFile::makeFileArray($tempPath);
			}
			else
			{
				return '';
			}
		}
		catch (Main\IO\IoException $exception)
		{
			return '';
		}

		if (!\CFile::isImage($recordFile['name'], $recordFile['type']))
		{
			return '';
		}

		if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0 && $recordFile['size'] < 1000000)
		{
			$recordFile['MODULE_ID'] = 'imbot';
		}
		else
		{
			$recordFile = '';
		}

		return $recordFile;
	}

	/**
	 * @param array $messageFields
	 * @return Keyboard|null
	 */
	protected static function processIncomingKeyboard(array $messageFields): ?Keyboard
	{
		$keyboard = null;
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboardData = [];
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboardData['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboardData = $messageFields['KEYBOARD'];
			}
			if (is_string($keyboardData))
			{
				$keyboardData = \CUtil::jsObjectToPhp($keyboardData);
			}

			$keyboardData['BOT_ID'] = $messageFields['BOT_ID'] ?? static::getBotId();
			$keyboard = Keyboard::getKeyboardByJson($keyboardData);
		}

		return $keyboard;
	}

	//endregion



	//region Message

	/**
	 * Sends message to client.
	 *
	 * @param array $messageFields Command arguments.
	 * <pre>
	 * [
	 * 	(int) TO_USER_ID
	 * 	(int) FROM_USER_ID
	 * 	(string) DIALOG_ID
	 * 	(array) PARAMS
	 * 	(string) MESSAGE
	 * 	(array | \CIMMessageParamAttach) ATTACH
	 * 	(array | Keyboard) KEYBOARD
	 * 	(string) SYSTEM - N|Y
	 * 	(string) URL_PREVIEW  - N|Y
	 * ]
	 * </pre>
	 *
	 * @return array
	 */
	public static function sendMessage($messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return [];
		}

		$userId = 0;
		if (isset($messageFields['TO_USER_ID']))
		{
			$userId = $messageFields['TO_USER_ID'];
		}
		elseif (isset($messageFields['DIALOG_ID']))
		{
			if (preg_match('/^[0-9]+$/i', $messageFields['DIALOG_ID']))
			{
				$userId = $messageFields['DIALOG_ID'];
			}
			elseif (
				$messageFields['DIALOG_ID'] === self::USER_LEVEL_ADMIN
				|| $messageFields['DIALOG_ID'] === self::USER_LEVEL_BUSINESS
			)
			{
				$users = [];
				if ($messageFields['DIALOG_ID'] === self::USER_LEVEL_ADMIN)
				{
					$users = self::getAdministrators();
				}
				elseif ($messageFields['DIALOG_ID'] === self::USER_LEVEL_BUSINESS)
				{
					$users = self::getBusinessUsers();
				}

				$result = [];
				foreach ($users as $userId)
				{
					$messageFields['DIALOG_ID'] = $userId;
					$result = array_merge($result, self::sendMessage($messageFields));
				}

				return $result;
			}
		}

		$messageFields['FROM_USER_ID'] = static::getBotId();

		if (!isset($messageFields['PARAMS'], $messageFields['PARAMS'][self::MESSAGE_PARAM_ALLOW_QUOTE]))
		{
			$messageFields['PARAMS'][self::MESSAGE_PARAM_ALLOW_QUOTE] = 'Y';
		}

		$messageFields['MESSAGE'] = static::replacePlaceholders($messageFields['MESSAGE'], $userId);

		$messageId = \CIMMessenger::add($messageFields);
		if ($messageId)
		{
			return [$messageId];
		}

		return [];
	}

	/**
	 * Updates message with undelivered mark.
	 *
	 * @param int $messageId Message Id.
	 *
	 * @return bool
	 */
	protected static function markMessageUndelivered(int $messageId)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}
		if ($messageId <= 0)
		{
			return false;
		}

		$result = (bool)\CIMMessageParam::set($messageId, [
			self::MESSAGE_PARAM_DELIVERED => 'N',
			self::MESSAGE_PARAM_SENDING => 'N',
			self::MESSAGE_PARAM_SENDING_TIME => 0
		]);

		\CIMMessageParam::sendPull($messageId, [
			self::MESSAGE_PARAM_DELIVERED,
			self::MESSAGE_PARAM_SENDING,
			self::MESSAGE_PARAM_SENDING_TIME
		]);

		return $result;
	}

	/**
	 * Drops message completely.
	 *
	 * @param int $messageId Message Id.
	 *
	 * @return bool
	 */
	protected static function dropMessage(int $messageId)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}
		if ($messageId <= 0)
		{
			return false;
		}

		\CIMMessenger::disableMessageCheck();
		$result = (bool)\CIMMessenger::delete($messageId, null, true, false);
		\CIMMessenger::enableMessageCheck();

		return $result;
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Command arguments.
	 * <pre>
	 * [
	 *  (int) TO_USER_ID
	 *  (int) FROM_USER_ID
	 *  (array | \CIMMessageParamAttach) ATTACH
	 *  (array | Keyboard) KEYBOARD
	 *  (array) FILES
	 *  (string) MESSAGE
	 *  (string) URL_PREVIEW
	 *  (string) EDIT_FLAG
	 * ]</pre>
	 *
	 * @return bool
	 */
	protected static function updateMessage(int $messageId, array $messageFields)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}
		if ($messageId <= 0)
		{
			return false;
		}

		$messageRes = Im\Model\MessageTable::getByPrimary($messageId);
		if (!($message = $messageRes->fetch()))
		{
			return false;
		}

		if (isset($messageFields['ATTACH']) && (!$messageFields['ATTACH'] instanceof \CIMMessageParamAttach))
		{
			$messageFields['ATTACH'] = \CIMMessageParamAttach::getAttachByJson($messageFields['ATTACH']);
		}

		if (isset($messageFields['KEYBOARD']) && (!$messageFields['KEYBOARD'] instanceof Keyboard))
		{
			$keyboard = ['BOT_ID' => static::getBotId()];
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$messageFields['KEYBOARD'] = Keyboard::getKeyboardByJson($keyboard, [], ['ENABLE_FUNCTIONS' => 'Y']);
		}

		if (!empty($messageFields['FILES']) && is_array($messageFields['FILES']))
		{
			if (!$messageFields['ATTACH'])
			{
				$messageFields['ATTACH'] = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $value)
			{
				if ($value['type'] === 'image')
				{
					$messageFields['ATTACH']->addImages([[
						'NAME' => $value['name'],
						'LINK' => $value['link'],
						'WIDTH' => (int)$value['width'],
						'HEIGHT' => (int)$value['height'],
					]]);
				}
				else
				{
					$messageFields['ATTACH']->addFiles([[
						'NAME' => $value['name'],
						'LINK' => $value['link'],
						'SIZE' => $value['size'],
					]]);
				}
			}
		}

		$userId = 0;
		if (isset($messageFields['TO_USER_ID']))
		{
			$userId = (int)$messageFields['TO_USER_ID'];
		}
		elseif (isset($messageFields['DIALOG_ID']))
		{
			if (!Im\Common::isChatId($messageFields['DIALOG_ID']))
			{
				$userId = (int)$messageFields['DIALOG_ID'];
			}
		}

		$messageFields['MESSAGE'] = static::replacePlaceholders($messageFields['MESSAGE'], $userId);

		return Im\Bot::updateMessage(['BOT_ID' => static::getBotId()], [
			'MESSAGE_ID' => $messageId,
			'MESSAGE' => $messageFields['MESSAGE'],
			'KEYBOARD' => $messageFields['KEYBOARD'] ?? null,
			'ATTACH' => $messageFields['ATTACH'] ?? null,
			'URL_PREVIEW' => ($messageFields['URL_PREVIEW'] === 'Y' ? 'Y' : 'N'),
			'EDIT_FLAG' => ($messageFields['EDIT_FLAG'] === 'N' ? 'N' : 'Y'),
			'SKIP_CONNECTOR' => 'Y',
		]);
	}

	/**
	 * Enables keyboard buttons in message.
	 *
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	protected static function enableMessageButtons(int $messageId, bool $sendPullNotify = true)
	{
		return self::switchButtonsAvailability(true, $messageId, $sendPullNotify);
	}

	/**
	 * Disables keyboard buttons in message.
	 *
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	protected static function disableMessageButtons(int $messageId, bool $sendPullNotify = true)
	{
		return self::switchButtonsAvailability(false, $messageId, $sendPullNotify);
	}

	/**
	 * Disables keyboard buttons in message.
	 *
	 * @param bool $availability Availability flat to set.
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	private static function switchButtonsAvailability(bool $availability, int $messageId, bool $sendPullNotify = true)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}
		if ($messageId <= 0)
		{
			return false;
		}

		$messageRes = Im\Model\MessageTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $messageId,
				'=AUTHOR_ID' => static::getBotId(),
			],
		]);
		if (!($message = $messageRes->fetch()))
		{
			return false;
		}

		$messageParamRes = Im\Model\MessageParamTable::getList([
			'select' => [
				'PARAM_NAME',
				'PARAM_VALUE',
				'PARAM_JSON',
			],
			'filter' => [
				'=MESSAGE_ID' => $messageId,
				'=PARAM_NAME' => self::MESSAGE_PARAM_KEYBOARD,
			]
		]);
		$messageParamData = [];
		while ($row = $messageParamRes->fetch())
		{
			$messageParamData[$row['PARAM_NAME']] = $row;
		}
		if (empty($messageParamData) || !isset($messageParamData[self::MESSAGE_PARAM_KEYBOARD]))
		{
			return false;
		}

		try
		{
			$buttons = Main\Web\Json::decode($messageParamData[self::MESSAGE_PARAM_KEYBOARD]['PARAM_JSON']);
		}
		catch (Main\ArgumentException $e)
		{
		}
		if (empty($buttons) || !is_array($buttons))
		{
			return false;
		}

		$keyboard = new Keyboard(static::getBotId());

		foreach ($buttons as $buttonData)
		{
			if ($availability)
			{
				$buttonData['BG_COLOR'] = static::BUTTON_DEFAULT_COLOR;
				$buttonData['DISABLED'] = 'N';
			}
			else
			{
				$buttonData['BG_COLOR'] = static::BUTTON_DISABLED_COLOR;
				$buttonData['DISABLED'] = 'Y';
			}
			$keyboard->addButton($buttonData);
		}

		\CIMMessageParam::set($messageId, [self::MESSAGE_PARAM_KEYBOARD => $keyboard]);
		if ($sendPullNotify)
		{
			\CIMMessageParam::sendPull($messageId, [self::MESSAGE_PARAM_KEYBOARD]);
		}

		return true;
	}

	//endregion

	//region Connector

	/**
	 * @param int $lineId
	 * @param array $fields
	 *
	 * @return array|bool
	 */
	public static function registerConnector($lineId, $fields = [])
	{
		$send = [];
		$send['LINE_ID'] = (int)$lineId;
		if ($send['LINE_ID'] <= 0)
		{
			return false;
		}
		if (!Main\Loader::includeModule('imopenlines'))
		{
			return false;
		}
		/** @var \Bitrix\ImOpenLines\Config $configManager */
		$configManager = ServiceLocator::getInstance()->get('ImOpenLines.Config');
		$config = $configManager->get($lineId);
		if (!$config)
		{
			return false;
		}

		if (!empty($fields['NAME']) && trim($fields['NAME']) != '')
		{
			$send['LINE_NAME'] = trim($fields['NAME']);
		}
		else
		{
			$send['LINE_NAME'] = $config['LINE_NAME'];
		}

		if (empty($send['FIRST_MESSAGE']))
		{
			$send['FIRST_MESSAGE'] = $config['WELCOME_MESSAGE_TEXT'];
		}

		$send['LINE_DESC'] = isset($fields['DESC']) ? trim($fields['DESC']) : '';
		$send['FIRST_MESSAGE'] = isset($fields['FIRST_MESSAGE']) ? $fields['FIRST_MESSAGE'] : '';

		$send['AVATAR'] = '';
		if (!empty($fields['AVATAR']) && (int)$fields['AVATAR'] > 0)
		{
			$fields['AVATAR'] = (int)$fields['AVATAR'];
			$fileTmp = \CFile::resizeImageGet(
				$fields['AVATAR'],
				['width' => 300, 'height' => 300],
				\BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			if ($fileTmp['src'])
			{
				$send['AVATAR'] = mb_substr($fileTmp['src'], 0, 4) == 'http'
					? $fileTmp['src']
					: ImBot\Http::getServerAddress().$fileTmp['src'];
			}
		}

		$send['ACTIVE'] = isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'N'? 'N': 'Y';
		$send['HIDDEN'] = isset($fields['HIDDEN']) && $fields['HIDDEN'] == 'Y'? 'Y': 'N';

		$http = self::instanceHttpClient();
		$result = $http->query(
			self::COMMAND_CONNECTOR_REGISTER,
			$send,
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}
		if ($result['result'])
		{
			$result = [
				'CODE' => $result['result'],
				'NAME' => $send['LINE_NAME'],
				'DESC' => $send['LINE_DESC'],
				'FIRST_MESSAGE' => $send['FIRST_MESSAGE'],
				'AVATAR' => ($fields['AVATAR'] ?? ''),
				'ACTIVE' => $send['ACTIVE'],
				'HIDDEN' => $send['HIDDEN'],
			];
		}

		return $result;
	}

	/**
	 * @param int $lineId
	 * @param array $fields
	 *
	 * @return bool|array
	 */
	public static function updateConnector($lineId, $fields)
	{
		$update = [];
		$update['LINE_ID'] = (int)$lineId;
		if ($update['LINE_ID'] <= 0)
		{
			return false;
		}

		if (isset($fields['NAME']))
		{
			$fields['NAME'] = trim($fields['NAME']);
			if (mb_strlen($fields['NAME']) >= 3)
			{
				$update['FIELDS']['LINE_NAME'] = $fields['NAME'];
			}
			else
			{
				self::$lastError = new ImBot\Error(__METHOD__, 'NAME_LENGTH', 'Field NAME should be 3 or more characters');
				return false;
			}
		}

		if (isset($fields['DESC']))
		{
			$update['FIELDS']['LINE_DESC'] = trim($fields['DESC']);
		}

		if (isset($fields['FIRST_MESSAGE']))
		{
			$update['FIELDS']['FIRST_MESSAGE'] = trim($fields['FIRST_MESSAGE']);
		}

		if (isset($fields['AVATAR']))
		{
			$update['FIELDS']['AVATAR'] = '';

			$fields['AVATAR'] = (int)$fields['AVATAR'];
			if ($fields['AVATAR'])
			{
				$fileTmp = \CFile::resizeImageGet(
					$fields['AVATAR'],
					['width' => 300, 'height' => 300],
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				if ($fileTmp['src'])
				{
					$update['FIELDS']['AVATAR'] = mb_substr($fileTmp['src'], 0, 4) == 'http'
						? $fileTmp['src']
						: ImBot\Http::getServerAddress().$fileTmp['src'];
				}
			}
		}

		if (isset($fields['ACTIVE']))
		{
			$update['FIELDS']['ACTIVE'] = $fields['ACTIVE'] == 'N'? 'N': 'Y';
		}

		if (isset($fields['HIDDEN']))
		{
			$update['FIELDS']['HIDDEN'] = $fields['HIDDEN'] == 'Y'? 'Y': 'N';
		}

		$http = self::instanceHttpClient();
		$result = $http->query(
			self::COMMAND_CONNECTOR_UPDATE,
			$update,
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}

		return $result['result'];
	}

	/**
	 * @param int $lineId
	 *
	 * @return bool|array
	 */
	public static function unRegisterConnector($lineId)
	{
		$update = [];
		$update['LINE_ID'] = (int)$lineId;
		if ($update['LINE_ID'] <= 0)
		{
			return false;
		}

		$http = self::instanceHttpClient();
		$result = $http->query(
			self::COMMAND_CONNECTOR_UNREGISTER,
			['LINE_ID' => $lineId],
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}

		return $result['result'];
	}

	//endregion

	//region OL session

	/**
	 * @param int $botId
	 * @param string|null $dialogId
	 * @param int|null $sessionId
	 * @return ImBot\DialogSession
	 */
	protected static function instanceDialogSession(int $botId, ?string $dialogId = null): ImBot\DialogSession
	{
		static $dialogSession;
		if (!($dialogSession instanceof ImBot\DialogSession))
		{
			$dialogSession = new ImBot\DialogSession($botId ?: static::getBotId(), $dialogId);
			$dialogSession->load();
		}

		return $dialogSession;
	}

	/**
	 * Start openlines session.
	 * @see \Bitrix\ImBot\DialogSession::start
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * 	(int) PARENT_ID Previous session Id.
	 * 	(int) CLOSE_TERM Delay time (minutes) to close session.
	 * 	(string) GREETING_SHOWN - Y|N
	 * 	(array) MENU_STATE
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function startDialogSession($params)
	{
		if (empty($params['BOT_ID']) || empty($params['DIALOG_ID']))
		{
			return false;
		}

		return self::instanceDialogSession((int)$params['BOT_ID'], $params['DIALOG_ID'])->start($params);
	}

	/**
	 * Finalizes openlines session.
	 * @see \Bitrix\ImBot\DialogSession::finish
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * 	(int) CLOSE_TERM Delay time (minutes) to close session.
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	protected static function finishDialogSession($params)
	{
		if (empty($params['DIALOG_ID']) || empty($params['SESSION_ID']))
		{
			return false;
		}

		sleep(1);
		$session = self::instanceDialogSession((int)$params['BOT_ID'], $params['DIALOG_ID']);
		if ($session->getSessionId() == (int)$params['SESSION_ID'])
		{
			return $session->finish($params);
		}

		return false;
	}

	//endregion

	//region Bot's parameters

	/**
	 * Saves new open line bot id.
	 *
	 * @param string $lineCode Line code.
	 * @param int $botId Bot Id.
	 *
	 * @return bool
	 */
	public static function setNetworkBotId($lineCode, $botId)
	{
		if (!$lineCode)
		{
			return false;
		}

		$optionId = self::BOT_CODE. '_' . $lineCode. '_bot_id';
		if ($botId > 0)
		{
			Option::set(self::MODULE_ID, $optionId, $botId);
		}
		else
		{
			Option::delete(self::MODULE_ID, ['name' => $optionId]);
		}

		return true;
	}

	/**
	 * Returns open line bot id.
	 * @param string $code Line code.
	 * @param bool $getFromDb Do not use Main\Config\Option to detect it.
	 *
	 * @return bool|int
	 */
	public static function getNetworkBotId($code, $getFromDb = false)
	{
		if (!$code)
		{
			return false;
		}

		$optionId = self::BOT_CODE. '_'. $code. '_bot_id';

		if ($getFromDb)
		{
			$row = Im\Model\BotTable::getList([
				'filter' => [
					'=TYPE' => Im\Bot::TYPE_NETWORK,
					'=APP_ID' => $code
				]
			])->fetch();
			if (!$row)
			{
				return 0;
			}

			$botId = Option::get(self::MODULE_ID, $optionId, 0);
			if ($botId !== $row['BOT_ID'])
			{
				self::setNetworkBotId($code, $row['BOT_ID']);
			}

			return $row['BOT_ID'];
		}

		return Option::get(self::MODULE_ID, $optionId, 0);
	}

	/**
	 * @return bool|int
	 */
	public static function getBotId(): int
	{
		return 0;
	}
	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public static function setBotId($id)
	{
		return false;
	}

	/**
	 * Returns OL code.
	 * @return string
	 */
	public static function getBotCode(): string
	{
		return '';
	}

	//endregion

	//region Schedule actions

	/**
	 * Adds agent.
	 *
	 * @param array $params
	 * <pre>
	 * [
	 *    (string) agent
	 *    (string) class
	 *    (string) next_execution
	 *    (int) delay
	 *    (bool) regular
	 *    (int) interval
	 * ]
	 * </pre>
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function addAgent(array $params): bool
	{
		if (empty($params['agent']))
		{
			throw new Main\ArgumentException('Agent name must be defined');
		}
		$agentName = $params['agent'];
		$className = $params['class'] ?? static::class;

		$regular = (bool)($params['regular'] ?? false);
		$interval = (int)($params['interval'] ?? 86400);
		$delay = (int)($params['delay'] ?? 0);
		$nextExecutionTime = $params['next_execution'] ?? '';
		if (empty($nextExecutionTime) && $delay > 0)
		{
			$nextExecutionTime = \ConvertTimeStamp(\time() + \CTimeZone::getOffset() + $delay, 'FULL');
		}

		$agentAdded = true;
		$agents = \CAgent::getList([], ['MODULE_ID' => 'imbot', '=NAME' => $className.'::'.$agentName.';']);
		if (!$agents->fetch())
		{
			$agentAdded = (bool)(\CAgent::addAgent(
					$className.'::'.$agentName.';',
					'imbot',
					($regular ? 'Y' : 'N'),
					$interval,
					'',
					'Y',
					$nextExecutionTime
				) !== false);
		}

		return $agentAdded;
	}

	/**
	 * Removes agents.
	 *
	 * @param array $params
	 * <pre>
	 * [
	 *    (string) agent
	 *    (string) mask
	 *    (string) class
	 * ]
	 * </pre>
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function deleteAgent(array $params): bool
	{
		$filter = ['MODULE_ID' => 'imbot'];
		$className = $params['class'] ?? static::class;
		if (!empty($params['agent']))
		{
			$filter['=NAME'] = $className.'::'.$params['agent'].';';
		}
		elseif (!empty($params['mask']))
		{
			$filter['NAME'] = $className.'::'.$params['mask'].'%';
		}
		if (empty($filter['NAME']) && empty($filter['=NAME']))
		{
			throw new Main\ArgumentException('Agent name must be defined');
		}

		$agents = \CAgent::getList([], $filter);
		while ($agent = $agents->fetch())
		{
			\CAgent::delete($agent['ID']);
		}

		return true;
	}

	//endregion

	//region First days customer

	/**
	 * @deprecated
	 * @param string $text
	 *
	 * @return bool
	 */
	public static function isFdcCode($text)
	{
		return self::checkCodeBlacklist($text);
	}

	//endregion
}

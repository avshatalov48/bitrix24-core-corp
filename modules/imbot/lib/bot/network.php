<?php

namespace Bitrix\ImBot\Bot;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;

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

		COMMAND_MENU = 'menu',
		MENU_EXIT_ID = 'exit',
		MENU_ENTRANCE_ID = 'default',
		MENU_ACTION_NEXT = 'MENU',
		MENU_ACTION_LINK = 'LINK',
		MENU_ACTION_HELP = 'HELPCODE',
		MENU_ACTION_QUEUE = 'QUEUE',
		MENU_BUTTON_DISABLED = '#aaa',
		MENU_BUTTON_ACTIVE = "#29619b",

		MESSAGE_PARAM_MENU_ACTION = 'IMB_MENU_ACTION',// menu action parameter
		MESSAGE_PARAM_ALLOW_QUOTE = 'IMOL_QUOTE_MSG',// allow|disallow to quote message
		MESSAGE_PARAM_SESSION_ID = 'IMOL_SID',// OL session Id
		MESSAGE_PARAM_IMOL_VOTE = 'IMOL_VOTE',
		MESSAGE_PARAM_IMOL_VOTE_DISLIKE = 'IMOL_VOTE_DISLIKE',
		MESSAGE_PARAM_IMOL_VOTE_LIKE = 'IMOL_VOTE_LIKE',
		MESSAGE_PARAM_CONNECTOR_MID = 'CONNECTOR_MID',
		MESSAGE_PARAM_KEYBOARD = 'KEYBOARD',
		MESSAGE_PARAM_ATTACH = 'ATTACH',
		MESSAGE_PARAM_SENDING = 'SENDING',
		MESSAGE_PARAM_SENDING_TIME = 'SENDING_TS',
		MESSAGE_PARAM_DELIVERED = 'IS_DELIVERED',

		PORTAL_PATH = '/pub/imbot.php';

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
			'NAME' => $params['LINE_NAME'],
			'WORK_POSITION' => $params['LINE_DESC'] ? $params['LINE_DESC'] : Loc::getMessage('IMBOT_NETWORK_BOT_WORK_POSITION'),
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
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see ImBot\Bot\Network::onMessageAdd */
			'METHOD_BOT_DELETE' => 'onBotDelete',/** @see ImBot\Bot\Network::onBotDelete */
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

			Im\Command::register([
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => self::COMMAND_UNREGISTER,
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onLocalCommandAdd'/** @see ImBot\Bot\Network::onLocalCommandAdd */
			]);

			Im\Command::register([
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => self::COMMAND_NETWORK_SESSION,
				'HIDDEN' => 'Y',
				'CLASS' => isset($params['CLASS']) ? $params['CLASS'] : static::class,
				'METHOD_COMMAND_ADD' => 'onCommandAdd'/** @see ImBot\Bot\Network::onCommandAdd */
			]);
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

		$result = Im\Bot::unRegister(['BOT_ID' => $botId]);
		if (!$result)
		{
			return false;
		}

		self::setNetworkBotId($code, 0);

		if ($notifyController)
		{
			$result = self::sendUnregisterRequest($code, $botId);
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

		$result = self::getNetworkBotId($code, true);
		if ($result)
		{
			return $result;
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
	public static function sendUnregisterRequest($code, $botId)
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
		$http->query(
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

		return true;
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
		$http->query(
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

		return true;
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
	public static function getBotSettings(array $params = [])
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

		if (!($parsedUrl = \parse_url($publicUrl)) || empty($parsedUrl['host']) || !in_array($parsedUrl['scheme'], ['http', 'https']))
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
			strtolower($host) == 'localhost' ||
			preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host) &&
			preg_match('#^(127|10|172\.16|192\.168)\.#', $host)
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

		$http = self::instanceHttpClient();
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

	/**
	 * Allows to update bot fields (name, desc, avatar, welcome mess) using data from imcomming message
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage()
	{
		return true;
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
	 * 	(string) LINE
	 * 	(string) CONNECTOR_MID
	 * ]</pre>
	 *
	 * @return ImBot\Error|array
	 */
	public static function onReceiveCommand($command, $params)
	{
		if($command === self::COMMAND_OPERATOR_MESSAGE_ADD)
		{
			Log::write($params, 'NETWORK: operatorMessageAdd');

			static::operatorMessageAdd($params['MESSAGE_ID'], [
				'BOT_ID' => $params['BOT_ID'],
				'BOT_CODE' => $params['BOT_CODE'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'FILES' => isset($params['FILES'])? $params['FILES']: '',
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'PARAMS' => isset($params['PARAMS'])? $params['PARAMS']: '',
				'USER' => isset($params['USER'])? $params['USER']: '',
				'LINE' => isset($params['LINE'])? $params['LINE']: ''
			]);

			$result = ['RESULT' => 'OK'];
		}

		else if($command === self::COMMAND_OPERATOR_MESSAGE_UPDATE)
		{
			Log::write($params, 'NETWORK: operatorMessageUpdate');

			static::operatorMessageUpdate($params['MESSAGE_ID'], [
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'FILES' => isset($params['FILES'])? $params['FILES']: '',
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'PARAMS' => isset($params['PARAMS'])? $params['PARAMS']: '',
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			]);
			$result = ['RESULT' => 'OK'];
		}

		else if($command === self::COMMAND_OPERATOR_MESSAGE_DELETE)
		{
			Log::write($params, 'NETWORK: operatorMessageDelete');

			static::operatorMessageDelete($params['MESSAGE_ID'], [
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			]);

			$result = ['RESULT' => 'OK'];
		}

		else if($command === self::COMMAND_OPERATOR_START_WRITING)
		{
			Log::write($params, 'NETWORK: operatorStartWriting');

			static::operatorStartWriting([
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'USER' => isset($params['USER'])? $params['USER']: ''
			]);

			$result = ['RESULT' => 'OK'];
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

			$result = ['RESULT' => 'OK'];
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
			]);

			$result = ['RESULT' => 'OK'];
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
			]);

			$result = ['RESULT' => 'OK'];
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
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	protected static function clientMessageAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
		{
			return false;
		}

		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
		{
			$chat = new \CIMChat($messageFields['BOT_ID']);
			$chat->DeleteUser($messageFields['CHAT_ID'], $messageFields['BOT_ID']);
			return false;
		}

		if ($messageFields['TO_USER_ID'] != $messageFields['BOT_ID'])
		{
			return false;
		}

		$bot = Im\Bot::getCache($messageFields['BOT_ID']);
		if (mb_substr($bot['CODE'], 0, 7) != self::BOT_CODE)
		{
			return false;
		}

		$files = [];
		if (isset($messageFields['FILES']) && Main\Loader::includeModule('disk'))
		{
			foreach ($messageFields['FILES'] as $file)
			{
				$fileModel = \Bitrix\Disk\File::loadById($file['id']);
				if (!$fileModel)
					continue;

				$file['link'] = \CIMDisk::GetFileLink($fileModel);
				if (!$file['link'])
					continue;

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

		$messageFields['MESSAGE'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageFields['MESSAGE']);
		$messageFields['MESSAGE'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageFields['MESSAGE']);

		$botMessageText = '';
		$CIMHistory = new \CIMHistory();
		if ($result = $CIMHistory->GetRelatedMessages($messageId, 1, 0, false, false))
		{
			foreach($result['message'] as $message)
			{
				if (isset($message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE]) && $message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE] == 'Y')
				{
					$botMessageText = $message['text'];
				}
				break;
			}
		}
		if ($botMessageText)
		{
			$messageFields['MESSAGE'] =
				str_repeat("-", 54)."\n".
				$botMessageText."\n".
				str_repeat("-", 54)."\n".
				$messageFields['MESSAGE'];
		}

		\CIMMessageParam::Set($messageId, [
			self::MESSAGE_PARAM_SENDING => 'Y',
			self::MESSAGE_PARAM_SENDING_TIME => time()
		]);

		$result = self::clientMessageSend([
			'BOT_ID' => $messageFields['BOT_ID'],
			'USER_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => [
				'ID' => $messageId,
				'TYPE' => $messageFields['MESSAGE_TYPE'],
				'TEXT' => $messageFields['MESSAGE'],
			],
			'FILES' => $files,
		]);
		if (isset($result['error']))
		{
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

			\CIMMessageParam::Set($messageId, [
				self::MESSAGE_PARAM_DELIVERED => 'N',
				self::MESSAGE_PARAM_SENDING => 'N',
				self::MESSAGE_PARAM_SENDING_TIME => 0
			]);
		}
		\CIMMessageParam::SendPull($messageId, [
			self::MESSAGE_PARAM_DELIVERED,
			self::MESSAGE_PARAM_SENDING,
			self::MESSAGE_PARAM_SENDING_TIME
		]);

		return true;
	}

	/**
	 * Sends new client message into network line.
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\ClientMessageAdd
	 * @param array $fields Command arguments.
	 * <pre>
	 * [
	 * 	(int) USER_ID
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
	public static function clientMessageSend(array $fields)
	{
		$user = self::getUserInfo((int)$fields['USER_ID']);

		$portalTariff = 'box';
		$portalTariffLevel = 'paid';
		$userLevel = self::USER_LEVEL_ADMIN;
		$portalType = 'PRODUCTION';
		$portalTariffName = '';
		$portalCreateTime = '';
		$demoStartTime = 0;
		if (Main\Loader::includeModule('bitrix24'))
		{
			$portalTariff = \CBitrix24::getLicenseType();
			$portalTariffName = \CBitrix24::getLicenseName();
			$portalCreateTime = \CBitrix24::getCreateTime();

			if ($portalTariff == 'demo')
			{
				$portalTariff = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
				$portalTariff = $portalTariff.'+demo';
				$portalTariffName = \CBitrix24::getLicenseName("", \CBitrix24::LICENSE_TYPE_PREVIOUS);

				$demoStartTime = (int)Option::get("bitrix24", "DEMO_START");
			}

			if (!$portalCreateTime)
			{
				$portalCreateTime = time();
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

		$user = array_merge($user, [
			'TARIFF' => $portalTariff,
			'TARIFF_NAME' => $portalTariffName,
			'TARIFF_LEVEL' => $portalTariffLevel,
			'GEO_DATA' => self::getUserGeoData(),
			'REGISTER' => $portalCreateTime,
			'DEMO' => $demoStartTime,
			'USER_LEVEL' => $userLevel,
			'PORTAL_TYPE' => $portalType,
		]);

		$messageId = is_array($fields['MESSAGE']) ? (int)$fields['MESSAGE']['ID'] : 0;
		$messageText = is_array($fields['MESSAGE']) ? (string)$fields['MESSAGE']['TEXT'] : (string)$fields['MESSAGE'];

		$http = self::instanceHttpClient();
		$result = $http->query(
			'clientMessageAdd',
			[
				'BOT_ID' => $fields['BOT_ID'],
				'DIALOG_ID' => $fields['USER_ID'],
				'MESSAGE_ID' => $messageId,
				'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
				'MESSAGE_TEXT' => $messageText,
				'FILES' => $fields['FILES'] ?? null,
				'ATTACH' => $fields['ATTACH'] ?? null,
				'PARAMS' => $fields['PARAMS'] ?? null,
				'USER' => $user,
			]
		);

		return $result;
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
		if (
			$messageFields['MESSAGE_TYPE'] != \IM_MESSAGE_PRIVATE
			|| $messageFields['TO_USER_ID'] != $messageFields['BOT_ID']
		)
		{
			return false;
		}

		$bot = Im\Bot::getCache($messageFields['BOT_ID']);
		if (mb_substr($bot['CODE'], 0, 7) != self::BOT_CODE)
		{
			return false;
		}

		$messageFields['MESSAGE'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageFields['MESSAGE']);
		$messageFields['MESSAGE'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageFields['MESSAGE']);

		$history = new \CIMHistory();
		if ($result = $history->GetRelatedMessages($messageId, 1, 0, false, false))
		{
			$botMessageText = '';
			foreach ($result['message'] as $message)
			{
				if (
					isset($message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE])
					&& $message['params'][self::MESSAGE_PARAM_ALLOW_QUOTE] == 'Y'
				)
				{
					$botMessageText = $message['text'];
				}
				break;
			}
			if ($botMessageText)
			{
				$messageFields['MESSAGE'] =
					str_repeat("-", 54)."\n".
					$botMessageText."\n".
					str_repeat("-", 54)."\n".
					$messageFields['MESSAGE'];
			}
		}

		$http = self::instanceHttpClient();
		$http->query(
			'clientMessageUpdate',
			[
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $messageFields['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID][0],
				'MESSAGE_TEXT' => $messageFields['MESSAGE'],
				'USER' => self::getUserInfo((int)$messageFields['FROM_USER_ID'])
			]
		);

		return true;
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
		if (
			$messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE
			|| $messageFields['TO_USER_ID'] != $messageFields['BOT_ID']
		)
		{
			return false;
		}

		$bot = Im\Bot::getCache($messageFields['BOT_ID']);
		if (mb_substr($bot['CODE'], 0, 7) != self::BOT_CODE)
		{
			return false;
		}

		$messageFields['MESSAGE'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageFields['MESSAGE']);
		$messageFields['MESSAGE'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageFields['MESSAGE']);

		$http = self::instanceHttpClient();
		$http->query(
			'clientMessageDelete',
			[
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $messageFields['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID][0],
				'USER' => self::getUserInfo((int)$messageFields['FROM_USER_ID'])
			]
		);

		return true;
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
	 * ]</pre>
	 *
	 * @return bool
	 */
	protected static function clientCommandSend(array $params)
	{
		$http = self::instanceHttpClient();
		$http->query(
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

		return true;
	}

	/**
	 * @param array $params Command arguments.
	 *
	 * @return bool
	 */
	protected static function clientStartWriting($params)
	{
		$http = self::instanceHttpClient();
		$http->query(
			'clientStartWriting',
			[
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['USER_ID'],
				'USER_ID' => $params['USER_ID'],
			],
			false
		);

		return true;
	}

	/**
	 * @param array $params Command arguments.
	 *
	 * @return bool
	 */
	protected static function clientSessionVote($params)
	{
		$http = self::instanceHttpClient();
		$http->query(
			'clientSessionVote',
			[
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['USER_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'MESSAGE_ID' => $params['MESSAGE']['PARAMS'][self::MESSAGE_PARAM_CONNECTOR_MID][0],
				'ACTION' => $params['ACTION'],
				'USER_ID' => $params['USER_ID'],
			],
			false
		);

		return true;
	}

	/**
	 * @param array $params Command arguments.
	 *
	 * @return bool
	 */
	protected static function clientMessageReceived($params)
	{
		$http = self::instanceHttpClient();
		$query = $http->query(
			'clientMessageReceived',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
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
	 * 	(string) LINE
	 * ]</pre>
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
			foreach ($list as $botId => $botData)
			{
				if ($botData['TYPE'] != Im\Bot::TYPE_NETWORK)
				{
					continue;
				}

				if ($messageFields['BOT_CODE'] == $botData['APP_ID'])
				{
					$messageFields['BOT_ID'] = intval($botData['BOT_ID']);
					break;
				}
			}
			if (intval($messageFields['BOT_ID']) <= 0)
			{
				return false;
			}
		}

		$attach = null;
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		$keyboard = [];
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = ['BOT_ID' => $messageFields['BOT_ID']];
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard = Im\Bot\Keyboard::getKeyboardByJson($keyboard, [], ['ENABLE_FUNCTIONS' => 'Y']);
		}

		if (!empty($messageFields['FILES']))
		{
			if (!$attach)
			{
				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $key => $value)
			{
				if ($value['type'] === 'image')
				{
					$attach->AddImages([[
						"NAME" => $value['name'],
						"LINK" => $value['link'],
						"WIDTH" => (int)$value['width'],
						"HEIGHT" => (int)$value['height'],
					]]);
				}
				else
				{
					$attach->AddFiles([[
						"NAME" => $value['name'],
						"LINK" => $value['link'],
						"SIZE" => $value['size'],
					]]);
				}
			}
		}

		$params = [];
		if (!empty($messageFields['PARAMS']))
		{
			$params = $messageFields['PARAMS'];
		}

		$params[self::MESSAGE_PARAM_CONNECTOR_MID] = [$messageId];

		if (!empty($messageFields['USER']))
		{
			$params['USER_ID'] = $messageFields['USER']['ID'];
			$nameTemplateSite = \CSite::GetNameFormat(false);
			$userName = \CUser::FormatName($nameTemplateSite, $messageFields['USER'], true, false);
			if ($userName)
			{
				$params['NAME'] = $userName;
			}
			if (Main\Loader::includeModule('im'))
			{
				$userAvatar = Im\User::uploadAvatar($messageFields['USER']['PERSONAL_PHOTO'], $messageFields['BOT_ID']);
				if ($userAvatar)
				{
					$params['AVATAR'] = $userAvatar;
				}
			}
		}

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

		// Update bot fields (name, desc, avatar, welcome mess) using data from imcomming message
		if (!empty($messageFields['LINE']) && ($needUpdateBotFields || $needUpdateBotAvatar))
		{
			$botData = Im\User::getInstance($messageFields['BOT_ID']);

			$updateFields = [];

			if ($needUpdateBotFields)
			{
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
			}

			if ($needUpdateBotAvatar && !empty($messageFields['LINE']['AVATAR']))
			{
				$userAvatar = Im\User::uploadAvatar($messageFields['LINE']['AVATAR'], $messageFields['BOT_ID']);
				if ($userAvatar && $botData->getAvatarId() != $userAvatar)
				{
					$connection = Main\Application::getConnection();
					$connection->query("UPDATE b_user SET PERSONAL_PHOTO = ".(int)$userAvatar." WHERE ID = ".(int)$messageFields['BOT_ID']);
				}
			}

			if (!empty($updateFields))
			{
				self::getCurrentUser()->update($messageFields['BOT_ID'], $updateFields);
			}
		}

		$messageFields['URL_PREVIEW'] = isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == 'N'? 'N': 'Y';
		$connectorMid = Im\Bot::addMessage(['BOT_ID' => $messageFields['BOT_ID']], [
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE'],
			'URL_PREVIEW' => $messageFields['URL_PREVIEW'],
			'ATTACH' => $attach,
			'KEYBOARD' => $keyboard,
			'PARAMS' => $params
		]);

		self::clientMessageReceived([
			'BOT_ID' => $messageFields['BOT_ID'],
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'CONNECTOR_MID' => $connectorMid,
		]);

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
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		$keyboard = [];
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = ['BOT_ID' => $messageFields['BOT_ID']];
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard = Im\Bot\Keyboard::getKeyboardByJson($keyboard, [], ['ENABLE_FUNCTIONS' => 'Y']);
		}

		if (!empty($messageFields['FILES']))
		{
			if (!$attach)
			{
				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $key => $value)
			{
				$attach->AddFiles([[
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
			$nameTemplateSite = \CSite::GetNameFormat(false);
			$userName = \CUser::FormatName($nameTemplateSite, $params['USER'], true, false);
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
	 * 	(int) DIALOG_ID
	 * 	(int) CONNECTOR_MID
	 * 	(int) SESSION_ID
	 * ]</pre>
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

		$chatId = \CIMMessage::GetChatId($params['BOT_ID'], $params['DIALOG_ID']);
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

		\CIMMessageParam::Set($params['MESSAGE_ID'], [
			self::MESSAGE_PARAM_CONNECTOR_MID => $params['CONNECTOR_MID'],
			self::MESSAGE_PARAM_SENDING => 'N',
			self::MESSAGE_PARAM_SENDING_TIME => 0,
			self::MESSAGE_PARAM_SESSION_ID => $params['SESSION_ID']
		]);
		\CIMMessageParam::SendPull($params['MESSAGE_ID'], [
			self::MESSAGE_PARAM_CONNECTOR_MID,
			self::MESSAGE_PARAM_SENDING,
			self::MESSAGE_PARAM_SENDING_TIME,
			self::MESSAGE_PARAM_SESSION_ID,
		]);

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
		return true;
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields)
	{
		return static::clientMessageAdd($messageId, $messageFields);
	}

	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onMessageUpdate($messageId, $messageFields)
	{
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
		return static::clientMessageDelete($messageId, $messageFields);
	}

	/**
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onStartWriting($params)
	{
		return static::clientStartWriting($params);
	}

	/**
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onSessionVote($params)
	{
		return static::clientSessionVote($params);
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
	 * ]</pre>
	 *
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] === 'Y')
		{
			return false;
		}

		if ($messageFields['COMMAND_CONTEXT'] !== 'KEYBOARD')
		{
			return false;
		}

		if ($messageFields['MESSAGE_TYPE'] !== IM_MESSAGE_PRIVATE)
		{
			return false;
		}

		if ($messageFields['TO_USER_ID'] != static::getBotId())
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_NETWORK_SESSION)
		{
			if (empty($messageFields['CONNECTOR_MID']))
			{
				$messageParams = \CIMMessageParam::Get($messageId, self::MESSAGE_PARAM_CONNECTOR_MID);
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

		elseif ($messageFields['COMMAND'] === self::COMMAND_MENU)
		{
			if (
				$messageFields['COMMAND_PARAMS'] === self::MENU_EXIT_ID
				|| preg_match("/^".self::MENU_EXIT_ID.";([a-z0-9;:_\/]+)/i", $messageFields['COMMAND_PARAMS'], $commandParams)
			)
			{
				$menuState = self::getMenuState((int)$messageFields['FROM_USER_ID']) or [];
				$menuState['track'][] = self::MENU_EXIT_ID;//finish

				self::disableMessageButtons((int)$messageId, false);

				\CIMMessageParam::Set($messageId, [
					self::MESSAGE_PARAM_SENDING => 'Y',
					self::MESSAGE_PARAM_SENDING_TIME => time(),
				]);
				\CIMMessageParam::SendPull($messageId, [
					self::MESSAGE_PARAM_SENDING,
					self::MESSAGE_PARAM_SENDING_TIME,
					self::MESSAGE_PARAM_KEYBOARD
				]);

				$params = [
					self::MESSAGE_PARAM_ALLOW_QUOTE => 'N'
				];
				if (isset($commandParams, $commandParams[1]))
				{
					$menuState['menu_action'] = $commandParams[1];
					$params[self::MESSAGE_PARAM_MENU_ACTION] = $commandParams[1];
				}
				self::sendMenuResult(
					[
						'BOT_ID' => static::getBotId(),
						'DIALOG_ID' => $messageFields['FROM_USER_ID'],
						'MESSAGE_ID' => $messageId,
						'PARAMS' => $params,
					],
					$menuState
				);

				/*
				self::sendMessage([
					'FROM_USER_ID' => static::getBotId(),
					'DIALOG_ID' => $messageFields['FROM_USER_ID'],
					'MESSAGE' => Loc::getMessage('IMBOT_NETWORK_BOT_DIALOG_FORWARD'),
					'SYSTEM' => 'Y',
					'URL_PREVIEW' => 'N',
					'PARAMS' => [self::MESSAGE_PARAM_ALLOW_QUOTE => 'N'],
				]);
				*/
			}
			else
			{
				$menuState = self::showMenu([
					'BOT_ID' => static::getBotId(),
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'COMMAND' => $messageFields['COMMAND'],
					'COMMAND_PARAMS' => $messageFields['COMMAND_PARAMS'],
					'MESSAGE_ID' => (int)$messageId,
				]);
			}

			self::saveMenuState(
				(int)$messageFields['FROM_USER_ID'],
				$menuState
			);

			return true;
		}

		return false;
	}


	/**
	 * @param int $messageId Message Id.
	 * @param array $messageFields Event arguments.
	 *
	 * @return bool
	 */
	public static function onLocalCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
		{
			return false;
		}

		if ($messageFields['COMMAND_CONTEXT'] != 'TEXTAREA')
		{
			return false;
		}

		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
		{
			return false;
		}

		if ($messageFields['COMMAND'] === self::COMMAND_UNREGISTER)
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
	protected static function getPortalStage()
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
	protected static function getRecentDialogs(int $hoursDepth = 168): iterable
	{
		$botId = static::getBotId();
		$depth = $hoursDepth * 3600;
		$query = "
			SELECT
				RU.USER_ID,
				RU.CHAT_ID,
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
				and RB.MESSAGE_TYPE = '".\IM_MESSAGE_PRIVATE."'
				and RU.MESSAGE_TYPE = '".\IM_MESSAGE_PRIVATE."'
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

	//endregion

	//region Service functions

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
	protected static function getUserInfo(int $userId)
	{
		$result = [];

		$orm = Main\UserTable::getById($userId);
		if ($user = $orm->fetch())
		{
			$avatarUrl = '';
			if ($user['PERSONAL_PHOTO'])
			{
				$fileTmp = \CFile::ResizeImageGet(
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
		$users = [];
		if (Main\Loader::includeModule('bitrix24'))
		{
			$users = \CBitrix24::getAllAdminId();
		}
		else
		{
			$res = \CGroup::GetGroupUserEx(1);
			while ($row = $res->fetch())
			{
				$users[] = (int)$row["USER_ID"];
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
	public static function getLangMessage($messageCode = '')
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
	public static function replacePlaceholders($message, $userId = 0)
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
			$tempPath =  \CFile::GetTempName('', $hash.'.'.$matches[1]);

			$http = new Main\Web\HttpClient();
			$http->setPrivateIp(false);
			if ($http->download($avatarUrl, $tempPath))
			{
				$recordFile = \CFile::MakeFileArray($tempPath);
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

		if (!\CFile::IsImage($recordFile['name'], $recordFile['type']))
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

	//endregion

	//region Menu

	/**
	 * Checks if bot has ITR menu.
	 * @see \Bitrix\Imbot\Bot\MenuBot::hasBotMenu
	 *
	 * @return bool
	 */
	public static function hasBotMenu()
	{
		return false;
	}

	/**
	 * Returns stored data for ITR menu.
	 * @see \Bitrix\Imbot\Bot\MenuBot::getBotMenu
	 *
	 * @return array
	 */
	public static function getBotMenu()
	{
		return [];
	}

	/**
	 * Returns user's menu track.
	 * @see \Bitrix\Imbot\Bot\MenuBot::getMenuState
	 *
	 * @param int $dialogId User id.
	 *
	 * @return array|null
	 */
	public static function getMenuState(int $dialogId)
	{
		static $menuState;
		if ($menuState === null)
		{
			$res = ImBot\Model\NetworkSessionTable::getList([
				'select' => [
					'MENU_STATE',
				],
				'filter' => [
					'=BOT_ID' => static::getBotId(),
					'=DIALOG_ID' => $dialogId,
				]
			]);
			if ($sessData = $res->fetch())
			{
				if (!empty($sessData['MENU_STATE']))
				{
					try
					{
						$menuState = Main\Web\Json::decode($sessData['MENU_STATE']);
					}
					catch (Main\ArgumentException $e)
					{
					}
					if (
						!is_array($menuState)
						|| !array_key_exists('track', $menuState)
					)
					{
						$menuState = null;
					}
				}
			}
		}

		return $menuState;
	}

	/**
	 * Saves user's menu track.
	 * @see \Bitrix\Imbot\Bot\MenuBot::saveMenuState
	 *
	 * @param int $dialogId User id.
	 * @param array|null $menuState User menu track.
	 *
	 * @return void
	 */
	public static function saveMenuState(int $dialogId, ?array $menuState = null)
	{
		self::startDialogSession([
			'BOT_ID' => static::getBotId(),
			'DIALOG_ID' => $dialogId,
			'GREETING_SHOWN' => 'Y',
			'MENU_STATE' => $menuState,
		]);
	}

	/**
	 * Checks if menu track has been completed.
	 *
	 * @param int $dialogId User id.
	 * @param array|null $menuState User menu track.
	 *
	 * @return bool
	 */
	public static function isMenuTrackFinished(int $dialogId, ?array $menuState = null)
	{
		if (!is_array($menuState))
		{
			$menuState = self::getMenuState($dialogId);
		}
		$lastMenuItemId = is_array($menuState['track']) ? end($menuState['track']) : null;

		return ($lastMenuItemId === self::MENU_EXIT_ID);
	}

	/**
	 * Stops show menu to user.
	 *
	 * @param int $dialogId User id.
	 *
	 * @return void
	 */
	public static function stopMenuTrack(int $dialogId)
	{
		$menuState = self::getMenuState($dialogId) or [];
		$lastMenuItemId = is_array($menuState['track']) ? end($menuState['track']) : null;
		if (self::MENU_EXIT_ID !== $lastMenuItemId)
		{
			$menuState['track'][] = self::MENU_EXIT_ID;//do not show menu
			self::saveMenuState($dialogId, $menuState);
		}
		$messageId = isset($menuState['message_id']) ? (int)$menuState['message_id'] : null;
		if ($messageId)
		{
			self::disableMessageButtons((int)$messageId);
		}
	}

	/**
	 * Display ITR menu.
	 * @see \Bitrix\Imbot\Bot\MenuBot::showMenu
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (int) BOT_ID Bot id.
	 *   (int) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Previous message id.
	 *   (string) COMMAND
	 *   (string) COMMAND_PARAMS
	 *   (bool) FULL_REDRAW Drop previous menu block.
	 * ]
	 * </pre>.
	 *
	 * @return array|null
	 */
	public static function showMenu(array $params)
	{
		$getLast = function ($arr)
		{
			return !empty($arr) && is_array($arr) ? end($arr) : null;
		};

		$newMenuState = ['message_id' => 0, 'track' => [], 'messages' => []];
		$previousMenuState = self::getMenuState((int)$params['DIALOG_ID']);
		if ($previousMenuState)
		{
			$lastMenuItemId = $getLast($previousMenuState['track']);
			if ($lastMenuItemId === self::MENU_EXIT_ID)
			{
				return $previousMenuState;//finish has reached
			}

			$newMenuState = $previousMenuState;
		}

		$menuData = static::getBotMenu();
		if (!is_array($menuData))
		{
			return null;
		}

		$getMenuItem = function ($itemId) use ($menuData)
		{
			foreach ($menuData['elements'] as $item)
			{
				if ($item['id'] === $itemId)
				{
					return $item;
				}
			}
			return null;
		};

		$previousMessageId = null;

		$currentMenuItemId = $menuData['start'] ?? self::MENU_ENTRANCE_ID;

		// go to next menu level
		if (
			$params['COMMAND'] === self::COMMAND_MENU &&
			!empty($params['COMMAND_PARAMS']) &&
			is_string($params['COMMAND_PARAMS']) &&
			$getMenuItem($params['COMMAND_PARAMS'])
		)
		{
			$currentMenuItemId = $params['COMMAND_PARAMS'];
			$previousMessageId = (int)$params['MESSAGE_ID'];
		}
		// redraw menu
		elseif ($previousMenuState)
		{
			$previousMessageId = (int)$previousMenuState['message_id'];
			$lastMenuItemId = $getLast($previousMenuState['track']);
			if ($lastMenuItemId !== null)
			{
				$currentMenuItemId = $lastMenuItemId;
			}
		}

		$menuItem = $getMenuItem($currentMenuItemId);
		if ($menuItem)
		{
			if ($currentMenuItemId !== $getLast($newMenuState['track']))
			{
				$newMenuState['track'][] = $currentMenuItemId;//append track
			}

			$message = [
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $menuItem['text'],
				'SYSTEM' => 'N',
				'URL_PREVIEW' => 'N',
				'MESSAGE_TYPE' => IM_MESSAGE_PRIVATE,
			];

			if (isset($menuItem['buttons']))
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

				$keyboard = new Im\Bot\Keyboard(static::getBotId());

				foreach ($menuItem['buttons'] as $buttonData)
				{
					// check access
					if (isset($buttonData['access']))
					{
						$buttonAccess = preg_split("/[\s,]+/", $buttonData['access']);
						if ($buttonAccess && !in_array($userAccess, $buttonAccess, true))
						{
							continue;
						}
					}

					$button = [
						"TEXT" => $buttonData['text'],
						"DISPLAY" => ($buttonData['display'] ?? "BLOCK"),
						"BG_COLOR" => ($buttonData['back_color'] ??  self::MENU_BUTTON_ACTIVE),
						"TEXT_COLOR" => ($buttonData['text_color'] ?? "#fff"),
						"BLOCK" => "Y",
					];
					switch ($buttonData['action'])
					{
						case self::MENU_ACTION_NEXT:
							$button["COMMAND"] = self::COMMAND_MENU;
							$button["COMMAND_PARAMS"] = $buttonData['action_value'];
							break;

						case self::MENU_ACTION_LINK:
							$button["LINK"] = $buttonData['action_value'];
							break;

						case self::MENU_ACTION_HELP:
							if ($buttonData['action_value'] && !empty($buttonData['action_value']))
							{
								$button["FUNCTION"] = "BX.Helper.show(\'redirect=detail&HD_ID=".$buttonData['action_value']."\')";
							}
							else
							{
								$button["FUNCTION"] = "BX.Helper.show()";
							}
							break;

						case self::MENU_ACTION_QUEUE:
							$button["COMMAND"] = self::COMMAND_MENU;
							$button["COMMAND_PARAMS"] = self::MENU_EXIT_ID;
							// add some additional action params
							if ($buttonData['action_value'] && !empty($buttonData['action_value']))
							{
								$button["COMMAND_PARAMS"] .= ';'.$buttonData['action_value'];
							}
							break;
					}
					$keyboard->addButton($button);
				}
				$message['KEYBOARD'] = $keyboard;
			}
			else
			{
				if ($previousMessageId)
				{
					self::disableMessageButtons((int)$previousMessageId);
				}
				// reset menu if there are no buttons further
				$newMenuState = null;
				$previousMessageId = null;
			}

			$fullRedraw = (bool)($params['FULL_REDRAW'] === true);

			if ($previousMessageId && !$fullRedraw)
			{
				$message['EDIT_FLAG'] = 'N';
				self::updateMessage($previousMessageId, $message);
			}
			else
			{
				if ($previousMessageId && $fullRedraw)
				{
					self::dropMessage($previousMessageId);
				}

				$result = self::sendMessage($message);

				if ($newMenuState && $result[0])
				{
					$newMenuState['message_id'] = $result[0];
				}
			}
		}

		return $newMenuState;
	}

	/**
	 * Sends result of the user interaction with ITR menu to operator.
	 * @see \Bitrix\Imbot\Bot\MenuBot::sendMenuResult
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (int) BOT_ID Bot id.
	 *   (int) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Message id.
	 *   (array) PARAMS Some extra action data.
	 * ]
	 * @param array|null $menuState Saved user track.
	 *
	 * @return bool
	 */
	public static function sendMenuResult(array $params, ?array $menuState = null)
	{
		$menuState = $menuState ?: self::getMenuState((int)$params['DIALOG_ID']);
		if (
			!is_array($menuState) ||
			!array_key_exists('track', $menuState) ||
			!array_key_exists('message_id', $menuState)
		)
		{
			return false;
		}

		$menuData = static::getBotMenu();
		if (!is_array($menuData))
		{
			return false;
		}

		$userId = (int)$params['DIALOG_ID'];

		$getMenuItem = function ($itemId) use ($menuData)
		{
			foreach ($menuData['elements'] as $item)
			{
				if ($item['id'] === $itemId)
				{
					return $item;
				}
			}
			return null;
		};

		$startMenuItemId = $menuData['start'] ?? self::MENU_ENTRANCE_ID;
		$previousMenuItem = $getMenuItem($startMenuItemId);

		$level = 0;
		$blocks = [];
		foreach ($menuState['track'] as $itemId)
		{
			$menuItem = $getMenuItem($itemId);
			if (!$menuItem && $itemId != self::MENU_EXIT_ID)
			{
				continue;
			}
			if ($itemId == $startMenuItemId)
			{
				$previousMenuItem = $menuItem;
				continue;
			}

			$answer = '';
			if (isset($previousMenuItem['buttons']))
			{
				foreach ($previousMenuItem['buttons'] as $buttonData)
				{
					if ($buttonData['action'] == self::MENU_ACTION_NEXT && $buttonData['action_value'] == $itemId)
					{
						$answer = $buttonData['text'];
						break;
					}
					elseif ($buttonData['action'] == self::MENU_ACTION_QUEUE)
					{
						if (
							isset($menuState['menu_action'])
							&& !empty($menuState['menu_action'])
							&& isset($buttonData['action_value'])
							&& !empty($buttonData['action_value'])
							&& $menuState['menu_action'] === $buttonData['action_value']
						)
						{
							$answer = $buttonData['text'];
							break;
						}
						elseif (
							(
								!isset($menuState['menu_action'])
								|| empty($menuState['menu_action'])
							)
							&&
							(
								!isset($buttonData['action_value'])
								|| empty($buttonData['action_value'])
							)
						)
						{
							$answer = $buttonData['text'];
							break;
						}
					}
					elseif ($buttonData['action_value'] == self::MENU_EXIT_ID)
					{
						$answer = $buttonData['text'];
						break;
					}
				}
			}
			$level ++;
			if ($level > 1)
			{
				$blocks[] = ["DELIMITER" => ['SIZE' => 200, 'COLOR' => "#c6c6c6"]];
			}
			$blocks[] = ["GRID" => [[
				"NAME" => $level.". ". static::replacePlaceholders($previousMenuItem['text'], $userId),
				"VALUE" => static::replacePlaceholders($answer, $userId),
				'COLOR' => "#239991",
				"DISPLAY" => "BLOCK",
				"WIDTH" => "500"
			]]];

			$previousMenuItem = $menuItem;
		}

		return self::clientMessageSend([
			'BOT_ID' => static::getBotId(),
			'USER_ID' => (int)$params['DIALOG_ID'],
			'ATTACH' => Main\Web\Json::encode([
				'ID' => 1,
				'COLOR' => "#239991",
				'BLOCKS' => $blocks,
			]),
			'MESSAGE' => [
				'ID' => ($params['MESSAGE_ID'] ?: 0),
				'TEXT' => Loc::getMessage('IMBOT_NETWORK_BOT_MENU_RESULT'),
			],
			'PARAMS' => ($params['PARAMS'] ?? null),
		]);
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
	 * 	(int|string) DIALOG_ID
	 * 	(array) PARAMS
	 * 	(string) MESSAGE
	 * 	(array | \CIMMessageParamAttach) ATTACH
	 * 	(array | Im\Bot\Keyboard) KEYBOARD
	 * 	(string) SYSTEM - N|Y
	 * 	(string) URL_PREVIEW  - N|Y
	 * ]</pre>
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
		else if (isset($messageFields['DIALOG_ID']))
		{
			if (preg_match('/^[0-9]+$/i', $messageFields['DIALOG_ID']))
			{
				$userId = $messageFields['DIALOG_ID'];
			}
			else if (
				$messageFields['DIALOG_ID'] === self::USER_LEVEL_ADMIN
				|| $messageFields['DIALOG_ID'] === self::USER_LEVEL_BUSINESS
			)
			{
				$users = [];
				if ($messageFields['DIALOG_ID'] === self::USER_LEVEL_ADMIN)
				{
					$users = self::getAdministrators();
				}
				else if ($messageFields['DIALOG_ID'] === self::USER_LEVEL_BUSINESS)
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

		$messageId = \CIMMessenger::Add($messageFields);
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

		$result = (bool)\CIMMessageParam::Set($messageId, [
			self::MESSAGE_PARAM_DELIVERED => 'N',
			self::MESSAGE_PARAM_SENDING => 'N',
			self::MESSAGE_PARAM_SENDING_TIME => 0
		]);

		\CIMMessageParam::SendPull($messageId, [
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

		\CIMMessenger::DisableMessageCheck();
		$result = (bool)\CIMMessage::Delete($messageId, null, true);
		\CIMMessenger::EnableMessageCheck();

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
	 *  (array | Im\Bot\Keyboard) KEYBOARD
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

		if (isset($messageFields['ATTACH']) && (!$messageFields['ATTACH'] instanceof \CIMMessageParamAttach))
		{
			$messageFields['ATTACH'] = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		if (isset($messageFields['KEYBOARD']) && (!$messageFields['KEYBOARD'] instanceof Im\Bot\Keyboard))
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
			$messageFields['KEYBOARD'] = Im\Bot\Keyboard::getKeyboardByJson($keyboard, [], ['ENABLE_FUNCTIONS' => 'Y']);
		}

		if (!empty($messageFields['FILES']) && is_array($messageFields['FILES']))
		{
			if (!$messageFields['ATTACH'])
			{
				$messageFields['ATTACH'] = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $key => $value)
			{
				$messageFields['ATTACH']->AddFiles([[
					'NAME' => $value['name'],
					'LINK' => $value['link'],
					'SIZE' => $value['size'],
				]]);
			}
		}

		$userId = (int)$messageFields['TO_USER_ID'];
		$messageFields['MESSAGE'] = static::replacePlaceholders($messageFields['MESSAGE'], $userId);

		return Im\Bot::updateMessage(['BOT_ID' => static::getBotId()], [
			'MESSAGE_ID' => $messageId,
			'MESSAGE' => $messageFields['MESSAGE'],
			'KEYBOARD' => $messageFields['KEYBOARD'],
			'ATTACH' => $messageFields['ATTACH'],
			'URL_PREVIEW' => ($messageFields['URL_PREVIEW'] === 'Y' ? 'Y' : 'N'),
			'EDIT_FLAG' => ($messageFields['EDIT_FLAG'] === 'N' ? 'N' : 'Y'),
			'SKIP_CONNECTOR' => 'Y',
		]);
	}

	/**
	 * Disables keyboard buttons in ITR menu message.
	 *
	 * @param int $messageId Message Id.
	 * @param bool $sendPullNotify Allow send push request.
	 *
	 * @return bool
	 */
	protected static function disableMessageButtons(int $messageId, bool $sendPullNotify = true)
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

		$keyboard = new Im\Bot\Keyboard(static::getBotId());

		foreach ($buttons as $buttonData)
		{
			$buttonData['BG_COLOR'] = self::MENU_BUTTON_DISABLED;
			$buttonData['DISABLED'] = 'Y';
			$keyboard->addButton($buttonData);
		}

		\CIMMessageParam::Set($messageId, [self::MESSAGE_PARAM_KEYBOARD => $keyboard]);
		if ($sendPullNotify)
		{
			\CIMMessageParam::SendPull($messageId, [self::MESSAGE_PARAM_KEYBOARD]);
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

		$send['LINE_NAME'] = trim($fields['NAME']);
		if ($send['LINE_NAME'] == '')
		{
			$send['LINE_NAME'] = $config['LINE_NAME'];
		}

		if ($send['FIRST_MESSAGE'] == '')
		{
			$send['FIRST_MESSAGE'] = $config['WELCOME_MESSAGE_TEXT'];
		}

		$send['LINE_DESC'] = isset($fields['DESC']) ? trim($fields['DESC']) : '';
		$send['FIRST_MESSAGE'] = isset($fields['FIRST_MESSAGE']) ? $fields['FIRST_MESSAGE'] : '';

		$send['AVATAR'] = '';

		$fields['AVATAR'] = intval($fields['AVATAR']);
		if ($fields['AVATAR'])
		{
			$fileTmp = \CFile::ResizeImageGet(
				$fields['AVATAR'],
				['width' => 300, 'height' => 300],
				BX_RESIZE_IMAGE_EXACT,
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
				'AVATAR' => $fields['AVATAR'],
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

			$fields['AVATAR'] = intval($fields['AVATAR']);
			if ($fields['AVATAR'])
			{
				$fileTmp = \CFile::ResizeImageGet(
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
	 * Start openlines session.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * 	(int) PARENT_ID Ancestor session Id.
	 * 	(string) GREETING_SHOWN - Y|N
	 * 	(array) MENU_STATE
	 * ]</pre>
	 *
	 * @return bool
	 */
	public static function startDialogSession($params)
	{
		if (empty($params['BOT_ID']) || empty($params['DIALOG_ID']))
		{
			return false;
		}

		$newData = [];

		if (!empty($params['GREETING_SHOWN']))
		{
			$newData['GREETING_SHOWN'] = $params['GREETING_SHOWN'];
		}
		if (array_key_exists('MENU_STATE', $params))
		{
			if (is_array($params['MENU_STATE']))
			{
				$params['MENU_STATE'] = Main\Web\Json::encode($params['MENU_STATE']);
			}
			$newData['MENU_STATE'] = $params['MENU_STATE'];
		}

		$filter = [
			'=BOT_ID' => $params['BOT_ID'],
			'=DIALOG_ID' => $params['DIALOG_ID'],
		];
		if (!empty($params['SESSION_ID']))
		{
			$newData['SESSION_ID'] = (int)$params['SESSION_ID'];
			/*
			$filter['=SESSION_ID'] = [0, (int)$params['SESSION_ID']];
			if (!empty($params['PARENT_ID']))
			{
				$filter['=SESSION_ID'][] = (int)$params['PARENT_ID'];
			}
			*/
		}

		$res = ImBot\Model\NetworkSessionTable::getList([
			'select' => ['*'],
			'filter' => $filter
		]);
		if ($sessData = $res->fetch())
		{
			foreach ($newData as $field => $value)
			{
				if ($sessData[$field] === $newData[$field])
				{
					unset($newData[$field]);
				}
			}
			if (!empty($newData))
			{
				ImBot\Model\NetworkSessionTable::update($sessData['ID'], $newData);
			}
		}
		else
		{
			$newData['BOT_ID'] = $params['BOT_ID'];
			$newData['DIALOG_ID'] = $params['DIALOG_ID'];

			ImBot\Model\NetworkSessionTable::add($newData);
		}

		return true;
	}

	/**
	 * Finalizes openlines session.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 * 	(int) BOT_ID
	 * 	(string) DIALOG_ID
	 * 	(int) SESSION_ID Current session Id.
	 * 	(int) PARENT_ID Ancestor session Id.
	 * ]</pre>
	 *
	 * @return bool
	 */
	public static function finishDialogSession($params)
	{
		if (empty($params['BOT_ID']) || empty($params['DIALOG_ID']))
		{
			return false;
		}

		$res = ImBot\Model\NetworkSessionTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=BOT_ID' => $params['BOT_ID'],
				'=DIALOG_ID' => $params['DIALOG_ID'],
				'=SESSION_ID' => (int)$params['SESSION_ID'],
			]
		]);
		if ($sess = $res->fetch())
		{
			ImBot\Model\NetworkSessionTable::update($sess['ID'], ['SESSION_ID' => 0, 'MENU_STATE' => null]);
		}

		return true;
	}

	//endregion

	//region Bot's parameters

	/**
	 * Saves new open line bot id.
	 * @param string $code Line code.
	 * @param int $id Bot Id.
	 *
	 * @return bool
	 */
	public static function setNetworkBotId($code, $id)
	{
		Option::set(self::MODULE_ID, self::BOT_CODE.'_'.$code."_bot_id", $id);

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
	public static function getBotId()
	{
		return false;
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

	//endregion

	//region First days customer

	/**
	 * @param string $text
	 *
	 * @return bool
	 */
	public static function isFdcCode($text)
	{
		return in_array($text, self::$blackListOfCodes);
	}

	/**
	 * @deprecated
	 */
	public static function addFdc($userId)
	{
		return "";
	}

	/**
	 * @deprecated
	 */
	public static function sendTextFdc($userId, $text = '30-1')
	{
		return '';
	}

	/**
	 * @deprecated
	 */
	public static function removeFdc($userId)
	{
		return "";
	}

	/**
	 * @deprecated
	 */
	public static function fdcOnChatStart($dialogId, $joinFields)
	{
		return false;
	}

	/**
	 * @deprecated
	 */
	public static function fdcOnMessageAdd($messageId, $messageFields)
	{
		return false;
	}

	/**
	 * @deprecated
	 */
	public static function fdcOnAfterUserAuthorize($params)
	{
		return true;
	}

	/**
	 * @deprecated
	 */
	public static function fdcAddWelcomeMessageAgent($userId)
	{
		return "";
	}

	//endregion
}
<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\ImBot\Log;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class Network extends Base
{
	const BOT_CODE = "network";

	protected static $blackListOfCodes = Array(
		'1' => "88c8eccd63f6ff5a59ba04e5b0f2012a",
		'2' => "a588e1a88baf601b9d0b0b33b1eefc2b",
		'3' => "acb238d508bfbb0df68f200f21ae9b71",
		'4' => "9020c408d2d43f407b68bbc88601dbe7",
		'5' => "a588e1a88baf601b9d0b0b33b1eefc2b",
		'6' => "511dda9c421cdd21270a5f31d11f2fe5",
		'7' => "ae8cf733b2725127f755f8e75650a07a",
		'8' => "ae8cf733b2725127f755f8e75650a07a",
		'9' => "239e498332e63b5ee62b9e9fb0ff5a8d",
	);

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (empty($params['CODE']))
			return false;

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		$botId = self::getNetworkBotId($params['CODE'], true);
		if ($botId)
			return $agentMode? "": $botId;

		$avatarData = self::uploadAvatar($params['LINE_AVATAR']);

		$botId = \Bitrix\Im\Bot::register(Array(
			'APP_ID' => $params['CODE'],
			'CODE' => self::BOT_CODE.'_'.$params['CODE'],
			'MODULE_ID' => self::MODULE_ID,
			'TYPE' => \Bitrix\Im\Bot::TYPE_NETWORK,
			'INSTALL_TYPE' => \Bitrix\Im\Bot::INSTALL_TYPE_SILENT,
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'TEXT_PRIVATE_WELCOME_MESSAGE' => isset($params['LINE_WELCOME_MESSAGE'])? $params['LINE_WELCOME_MESSAGE']: '',
			'PROPERTIES' => Array(
				'NAME' => $params['LINE_NAME'],
				'WORK_POSITION' => $params['LINE_DESC']? $params['LINE_DESC']: Loc::getMessage('IMBOT_NETWORK_BOT_WORK_POSITION'),
				'PERSONAL_PHOTO' => $avatarData,
			)
		));

		if ($botId)
		{
			$sendParams = Array('CODE' => $params['CODE'], 'BOT_ID' => $botId);
			if (isset($params['OPTIONS']) && !empty($params['OPTIONS']))
			{
				$sendParams['OPTIONS'] = $params['OPTIONS'];
			}

			$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
			$result = $http->query('RegisterBot', $sendParams, true);
			if (isset($result['error']))
			{
				self::unRegister($params['CODE'], false);
				return false;
			}

			self::setNetworkBotId($params['CODE'], $botId);

			$avatarId = \Bitrix\Im\User::getInstance($botId)->getAvatarId();
			if ($avatarId > 0)
			{
				\Bitrix\Im\Model\ExternalAvatarTable::add(Array(
					'LINK_MD5' => md5($params['LINE_AVATAR']),
					'AVATAR_ID' => $avatarId
				));
			}

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'unregister',
				'HIDDEN' => 'Y',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onLocalCommandAdd'
			));
		}

		return $agentMode? "": $botId;
	}

	public static function unRegister($code = '', $serverRequest = true)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
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

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => $botId));
		if (!$result)
		{
			return $result;
		}

		self::setNetworkBotId($code, 0);

		if ($serverRequest)
		{
			$result = self::sendUnregisterRequest($code, $botId);
		}

		return $result;
	}

	public static function sendUnregisterRequest($code, $botId)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);

		$result = $http->query(
			'UnRegisterBot',
			Array('CODE' => $code, 'BOT_ID' => $botId),
			true
		);

		return $result;
	}

	public static function isNeedUpdateBotFieldsAfterNewMessage()
	{
		return true;
	}

	public static function onReceiveCommand($command, $params)
	{
		if($command == "operatorMessageAdd")
		{
			self::operatorMessageAdd($params['MESSAGE_ID'], Array(
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
			));

			$result = Array('RESULT' => 'OK');
		}
		else if($command == "operatorMessageUpdate")
		{
			Log::write($params, 'NETWORK: operatorMessageUpdate');

			self::operatorMessageUpdate($params['MESSAGE_ID'], Array(
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'FILES' => isset($params['FILES'])? $params['FILES']: '',
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'PARAMS' => isset($params['PARAMS'])? $params['PARAMS']: '',
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			));
			$result = Array('RESULT' => 'OK');
		}
		else if($command == "operatorMessageDelete")
		{
			Log::write($params, 'NETWORK: operatorMessageDelete');

			self::operatorMessageDelete($params['MESSAGE_ID'], Array(
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			));

			$result = Array('RESULT' => 'OK');
		}
		else if($command == "operatorStartWriting")
		{
			Log::write($params, 'NETWORK: operatorStartWriting');

			self::operatorStartWriting(Array(
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'USER' => isset($params['USER'])? $params['USER']: ''
			));

			$result = Array('RESULT' => 'OK');
		}
		else if($command == "operatorMessageReceived")
		{
			Log::write($params, 'NETWORK: operatorMessageReceived');

			self::operatorMessageReceived(Array(
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE_ID' => $params['MESSAGE_ID'],
				'CONNECTOR_MID' => $params['CONNECTOR_MID'],
				'SESSION_ID' => $params['SESSION_ID']
			));

			$result = Array('RESULT' => 'OK');
		}
		else
		{
			$result = new \Bitrix\ImBot\Error(__METHOD__, 'UNKNOWN_COMMAND', 'Command is not found');
		}

		return $result;
	}


	private static function clientMessageAdd($messageId, $messageFields)
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

		$bot = \Bitrix\Im\Bot::getCache($messageFields['BOT_ID']);
		if (substr($bot['CODE'], 0, 7) != self::BOT_CODE)
			return false;

		$files = Array();
		if (isset($messageFields['FILES']) && \Bitrix\Main\Loader::includeModule('disk'))
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
						$files[] = array(
							'name' => $file['name'],
							'type' => $file['type'],
							'link' => $file['link'],
							'width' => (int)$source["WIDTH"],
							'height' => (int)$source["HEIGHT"],
							'size' => $file['size']
						);
						$merged = true;
					}
				}

				if (!$merged)
				{
					$files[] = array(
						'name' => $file['name'],
						'type' => $file['type'],
						'link' => $file['link'],
						'size' => $file['size']
					);
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
				if (isset($message['params']['IMOL_QUOTE_MSG']) && $message['params']['IMOL_QUOTE_MSG'] == 'Y')
				{
					$botMessageText = $message['text'];
				}
				break;
			}
		}
		if ($botMessageText)
		{
			$messageFields['MESSAGE'] = str_repeat("-", 54)."\n".$botMessageText."\n".str_repeat("-", 54)."\n".$messageFields['MESSAGE'];
		}

		\CIMMessageParam::Set($messageId, Array('SENDING' => 'Y', 'SENDING_TS' => time()));

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
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);

			$message = '';

			if (self::getError()->code == 'LINE_DISABLED')
			{
				if (class_exists('Bitrix\ImBot\Bot\Support24'))
				{
					$message = \Bitrix\ImBot\Bot\Support24::replacePlaceholders(
						\Bitrix\ImBot\Bot\Support24::getMessage('LINE_DISABLED'),
						$messageFields['FROM_USER_ID']
					);
				}

				if (empty($message))
				{
					$message = Loc::getMessage('IMBOT_NETWORK_ERROR_BOT_NOT_FOUND');
				}
			}
			else
			{
				$message = Loc::getMessage('IMBOT_NETWORK_ERROR_NOT_FOUND');
			}

			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $messageFields['BOT_ID']), Array(
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE' => $message,
				'SYSTEM' => 'Y'
			));

			\CIMMessageParam::Set($messageId, Array('IS_DELIVERED' => 'N', 'SENDING' => 'N', 'SENDING_TS' => 0));
		}
		\CIMMessageParam::SendPull($messageId, Array('IS_DELIVERED', 'SENDING', 'SENDING_TS'));

		return true;
	}

	public static function clientMessageSend(array $fields)
	{
		$orm = \Bitrix\Main\UserTable::getById($fields['USER_ID']);
		$user = $orm->fetch();

		$avatarUrl = '';
		if ($user['PERSONAL_PHOTO'])
		{
			$fileTmp = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				array('width' => 300, 'height' => 300),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			if ($fileTmp['src'])
			{
				$avatarUrl = substr($fileTmp['src'], 0, 4) == 'http'? $fileTmp['src']: \Bitrix\ImBot\Http::getServerAddress().$fileTmp['src'];
			}
		}

		$portalTariff = 'box';
		$userLevel = 'ADMIN';
		$portalType = 'PRODUCTION';
		$portalTariffName = '';
		$demoStartTime = 0;
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			$portalTariff = \CBitrix24::getLicenseType();
			$portalTariffName = \CBitrix24::getLicenseName();

			if ($portalTariff == 'demo')
			{
				$portalTariff = \CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS);
				$portalTariff = $portalTariff.'+demo';
				$portalTariffName = \CBitrix24::getLicenseName("", \CBitrix24::LICENSE_TYPE_PREVIOUS);

				$demoStartTime = intval(\COption::GetOptionInt("bitrix24", "DEMO_START"));
			}

			if (\CBitrix24::isIntegrator($fields['USER_ID']))
			{
				$userLevel = 'INTEGRATOR';
			}
			else if (\CBitrix24::IsPortalAdmin($fields['USER_ID']))
			{
				$userLevel = 'ADMIN';
			}
			else
			{
				$userLevel = 'USER';
			}

			if (\CBitrix24::isStage())
			{
				$portalType = 'STAGE';
			}
		}

		$messageId = (int)is_array($fields['MESSAGE'])? $fields['MESSAGE']['ID']: 0;
		$messageText = (string)(is_array($fields['MESSAGE'])? $fields['MESSAGE']['TEXT']: $fields['MESSAGE']);

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$result = $http->query(
			'clientMessageAdd',
			Array(
				'BOT_ID' => $fields['BOT_ID'],
				'DIALOG_ID' => $fields['USER_ID'],
				'MESSAGE_ID' => $messageId,
				'MESSAGE_TYPE' => IM_MESSAGE_PRIVATE,
				'MESSAGE_TEXT' => $messageText,
				'FILES' => $fields['FILES'],
				'ATTACH' => $fields['ATTACH'],
				'USER' => Array(
					'ID' => $user['ID'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'PERSONAL_GENDER' => $user['PERSONAL_GENDER'],
					'WORK_POSITION' => $user['WORK_POSITION'],
					'EMAIL' => $user['EMAIL'],
					'PERSONAL_PHOTO' => \CHTTP::urnEncode($avatarUrl),
					'TARIFF' => $portalTariff,
					'TARIFF_NAME' => $portalTariffName,
					'TARIFF_LEVEL' => Support24::getSupportLevel(),
					'GEO_DATA' => self::getUserGeoData(),
					'REGISTER' => $portalTariff != 'box'? \COption::GetOptionInt('main', '~controller_date_create', time()): '',
					'DEMO' => $demoStartTime,
					'USER_LEVEL' => $userLevel,
					'PORTAL_TYPE' => $portalType,
				),
			)
		);

		return $result;
	}

	private static function clientMessageUpdate($messageId, $messageFields)
	{
		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE || $messageFields['TO_USER_ID'] != $messageFields['BOT_ID'])
			return false;

		$bot = \Bitrix\Im\Bot::getCache($messageFields['BOT_ID']);
		if (substr($bot['CODE'], 0, 7) != self::BOT_CODE)
			return false;

		$messageFields['MESSAGE'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageFields['MESSAGE']);
		$messageFields['MESSAGE'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageFields['MESSAGE']);

		$botMessageText = '';
		$CIMHistory = new \CIMHistory();
		if ($result = $CIMHistory->GetRelatedMessages($messageId, 1, 0, false, false))
		{
			foreach($result['message'] as $message)
			{
				if (isset($message['params']['IMOL_QUOTE_MSG']) && $message['params']['IMOL_QUOTE_MSG'] == 'Y')
				{
					$botMessageText = $message['text'];
				}
				break;
			}
		}
		if ($botMessageText)
		{
			$messageFields['MESSAGE'] = str_repeat("-", 54)."\n".$botMessageText."\n".str_repeat("-", 54)."\n".$messageFields['MESSAGE'];
		}

		$orm = \Bitrix\Main\UserTable::getById($messageFields['FROM_USER_ID']);
		$user = $orm->fetch();

		$avatarUrl = '';
		if ($user['PERSONAL_PHOTO'])
		{
			$fileTmp = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				array('width' => 300, 'height' => 300),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			if ($fileTmp['src'])
			{
				$avatarUrl = substr($fileTmp['src'], 0, 4) == 'http'? $fileTmp['src']: \Bitrix\ImBot\Http::getServerAddress().$fileTmp['src'];
			}
		}


		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$http->query(
			'clientMessageUpdate',
			Array(
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $messageFields['PARAMS']['CONNECTOR_MID'][0],
				'MESSAGE_TEXT' => $messageFields['MESSAGE'],
				'USER' => Array(
					'ID' => $user['ID'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'PERSONAL_GENDER' => $user['PERSONAL_GENDER'],
					'WORK_POSITION' =>  $user['WORK_POSITION'],
					'EMAIL' => $user['EMAIL'],
					'PERSONAL_PHOTO' => $avatarUrl
				)
			)
		);

		return true;
	}

	private static function clientMessageDelete($messageId, $messageFields)
	{
		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE || $messageFields['TO_USER_ID'] != $messageFields['BOT_ID'])
			return false;

		$bot = \Bitrix\Im\Bot::getCache($messageFields['BOT_ID']);
		if (substr($bot['CODE'], 0, 7) != self::BOT_CODE)
			return false;

		$messageFields['MESSAGE'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageFields['MESSAGE']);
		$messageFields['MESSAGE'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageFields['MESSAGE']);

		$orm = \Bitrix\Main\UserTable::getById($messageFields['FROM_USER_ID']);
		$user = $orm->fetch();

		$avatarUrl = '';
		if ($user['PERSONAL_PHOTO'])
		{
			$fileTmp = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				array('width' => 300, 'height' => 300),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			if ($fileTmp['src'])
			{
				$avatarUrl = substr($fileTmp['src'], 0, 4) == 'http'? $fileTmp['src']: \Bitrix\ImBot\Http::getServerAddress().$fileTmp['src'];
			}
		}


		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$http->query(
			'clientMessageDelete',
			Array(
				'BOT_ID' => $messageFields['BOT_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'CONNECTOR_MID' => $messageFields['PARAMS']['CONNECTOR_MID'][0],
				'USER' => Array(
					'ID' => $user['ID'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'PERSONAL_GENDER' => $user['PERSONAL_GENDER'],
					'WORK_POSITION' =>  $user['WORK_POSITION'],
					'EMAIL' => $user['EMAIL'],
					'PERSONAL_PHOTO' => $avatarUrl
				)
			)
		);

		return true;
	}

	private static function clientStartWriting($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$http->query(
			'clientStartWriting',
			Array(
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['USER_ID'],
				'USER_ID' => $params['USER_ID'],
			),
			false
		);

		return true;
	}

	private static function clientSessionVote($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$http->query(
			'clientSessionVote',
			Array(
				'BOT_ID' => $params['BOT_ID'],
				'DIALOG_ID' => $params['USER_ID'],
				'SESSION_ID' => $params['SESSION_ID'],
				'MESSAGE_ID' => $params['MESSAGE']['PARAMS']['CONNECTOR_MID'][0],
				'ACTION' => $params['ACTION'],
				'USER_ID' => $params['USER_ID'],
			),
			false
		);

		return true;
	}

	private static function clientMessageReceived($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
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


	private static function operatorMessageAdd($messageId, $messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		if (!empty($messageFields['BOT_CODE']))
		{
			$list = \Bitrix\Im\Bot::getListCache();
			foreach ($list as $botId => $botData)
			{
				if ($botData['TYPE'] != \Bitrix\Im\Bot::TYPE_NETWORK)
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

		$keyboard = Array();
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = Array('BOT_ID' => $messageFields['BOT_ID']);
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard, Array(), Array('ENABLE_FUNCTIONS' => 'Y'));
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

		$params = Array();
		if (!empty($messageFields['PARAMS']))
		{
			$params = $messageFields['PARAMS'];
		}

		$params['CONNECTOR_MID'] = Array($messageId);

		if (!empty($messageFields['USER']))
		{
			$params['USER_ID'] = $messageFields['USER']['ID'];
			$nameTemplateSite = \CSite::GetNameFormat(false);
			$userName = \CUser::FormatName($nameTemplateSite, $messageFields['USER'], true, false);
			if ($userName)
			{
				$params['NAME'] = $userName;
			}
			if (\Bitrix\Main\Loader::includeModule('im'))
			{
				$userAvatar = \Bitrix\Im\User::uploadAvatar($messageFields['USER']['PERSONAL_PHOTO'], $messageFields['BOT_ID']);
				if ($userAvatar)
				{
					$params['AVATAR'] = $userAvatar;
				}
			}
		}

		$needUpdateBotFields = true;
		$needUpdateBotAvatar = true;

		$bot = \Bitrix\Im\Bot::getCache($messageFields['BOT_ID']);
		if ($bot['MODULE_ID'] && \Bitrix\Main\Loader::includeModule($bot['MODULE_ID']) && class_exists($bot["CLASS"]))
		{
			if (method_exists($bot["CLASS"], 'isNeedUpdateBotFieldsAfterNewMessage'))
			{
				$needUpdateBotFields = call_user_func_array(array($bot["CLASS"], 'isNeedUpdateBotFieldsAfterNewMessage'), Array());
			}
			if (method_exists($bot["CLASS"], 'isNeedUpdateBotAvatarAfterNewMessage'))
			{
				$needUpdateBotAvatar = call_user_func_array(array($bot["CLASS"], 'isNeedUpdateBotAvatarAfterNewMessage'), Array());
			}
		}

		if (!empty($messageFields['LINE']))
		{
			$botData = \Bitrix\Im\User::getInstance($messageFields['BOT_ID']);

			$updateFields = Array();

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
					\Bitrix\Im\Bot::update(Array('BOT_ID' => $messageFields['BOT_ID']), Array(
						'TEXT_PRIVATE_WELCOME_MESSAGE' => $messageFields['LINE']['WELCOME_MESSAGE']
					));
				}
			}

			if ($needUpdateBotAvatar && !empty($messageFields['LINE']['AVATAR']))
			{
				$userAvatar = \Bitrix\Im\User::uploadAvatar($messageFields['LINE']['AVATAR'], $messageFields['BOT_ID']);
				if ($userAvatar && $botData->getAvatarId() != $userAvatar)
				{
					$connection = \Bitrix\Main\Application::getConnection();
					$connection->query("UPDATE b_user SET PERSONAL_PHOTO = ".intval($userAvatar)." WHERE ID = ".intval($messageFields['BOT_ID']));
				}
			}

			if (!empty($updateFields))
			{
				global $USER;
				$USER->Update($messageFields['BOT_ID'], $updateFields);
			}
		}

		$messageFields['URL_PREVIEW'] = isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == 'N'? 'N': 'Y';
		$connectorMid = \Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $messageFields['BOT_ID']), Array(
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE'],
			'URL_PREVIEW' => $messageFields['URL_PREVIEW'],
			'ATTACH' => $attach,
			'KEYBOARD' => $keyboard,
			'PARAMS' => $params
		));

		self::clientMessageReceived(Array(
			'BOT_ID' => $messageFields['BOT_ID'],
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'CONNECTOR_MID' => $connectorMid,
		));

		return true;
	}

	private static function operatorMessageUpdate($messageId, $messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$messageParamData = \Bitrix\Im\Model\MessageParamTable::getList(Array(
			'select' => Array('PARAM_VALUE'),
			'filter' => array('=MESSAGE_ID' => $messageId, '=PARAM_NAME' => 'CONNECTOR_MID')
		))->fetch();
		if (!$messageParamData || $messageParamData['PARAM_VALUE'] != $messageFields['CONNECTOR_MID'])
		{
			return false;
		}

		$attach = null;
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		if (!empty($messageFields['FILES']))
		{
			if (!$attach)
			{
				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
			}
			foreach ($messageFields['FILES'] as $key => $value)
			{
				$attach->AddFiles(array(
					array(
						"NAME" => $value['name'],
						"LINK" => $value['link'],
						"SIZE" => $value['size'],
					)
				));
			}
		}

		$messageFields['URL_PREVIEW'] = isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == 'N'? 'N': 'Y';

		\Bitrix\Im\Bot::updateMessage(Array('BOT_ID' => $messageFields['BOT_ID']), Array(
			'MESSAGE_ID' => $messageId,
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE'],
			'URL_PREVIEW' => $messageFields['URL_PREVIEW'],
			'ATTACH' => $attach,
			'SKIP_CONNECTOR' => 'Y',
			'EDIT_FLAG' => 'Y',
		));

		return true;
	}

	private static function operatorMessageDelete($messageId, $messageFields)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$messageParamData = \Bitrix\Im\Model\MessageParamTable::getList(Array(
			'select' => Array('PARAM_VALUE'),
			'filter' => array('=MESSAGE_ID' => $messageId, '=PARAM_NAME' => 'CONNECTOR_MID')
		))->fetch();
		if (!$messageParamData || $messageParamData['PARAM_VALUE'] != $messageFields['CONNECTOR_MID'])
		{
			return false;
		}

		\Bitrix\Im\Bot::deleteMessage(Array('BOT_ID' => $messageFields['BOT_ID']), $messageId);

		return true;
	}

	private static function operatorStartWriting($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

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

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $params['BOT_ID']), $params['DIALOG_ID'], $userName);

		return true;
	}

	private static function operatorMessageReceived($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$messageData = \Bitrix\Im\Model\MessageTable::getList(Array(
			'select' => Array('CHAT_ID'),
			'filter' => array('=ID' => $params['MESSAGE_ID'])
		))->fetch();
		if (!$messageData)
		{
			return false;
		}

		$chatId = \CIMMessage::GetChatId($params['BOT_ID'], $params['DIALOG_ID']);
		if ($messageData['CHAT_ID'] != $chatId)
		{
			return false;
		}

		$messageParamData = \Bitrix\Im\Model\MessageParamTable::getList(Array(
			'select' => Array('PARAM_VALUE'),
			'filter' => array('=MESSAGE_ID' => $params['MESSAGE_ID'], '=PARAM_NAME' => 'SENDING')
		))->fetch();
		if (!$messageParamData || $messageParamData['PARAM_VALUE'] != 'Y')
		{
			return false;
		}

		\CIMMessageParam::Set($params['MESSAGE_ID'], Array(
			'CONNECTOR_MID' => $params['CONNECTOR_MID'],
			'SENDING' => 'N',
			'SENDING_TS' => 0,
			'IMOL_SID' => $params['SESSION_ID']
		));
		\CIMMessageParam::SendPull($params['MESSAGE_ID'], Array('CONNECTOR_MID', 'SENDING', 'SENDING_TS', 'IMOL_SID'));

		return true;
	}




	public static function onChatStart($dialogId, $joinFields)
	{
		return true;
	}

	public static function onMessageAdd($messageId, $messageFields)
	{
		return self::clientMessageAdd($messageId, $messageFields);
	}

	public static function onMessageUpdate($messageId, $messageFields)
	{
		return self::clientMessageUpdate($messageId, $messageFields);
	}

	public static function onMessageDelete($messageId, $messageFields)
	{
		return self::clientMessageDelete($messageId, $messageFields);
	}

	public static function onStartWriting($params)
	{
		return self::clientStartWriting($params);
	}

	public static function onSessionVote($params)
	{
		return self::clientSessionVote($params);
	}

	public static function onAnswerAdd($command, $params)
	{
		return self::onReceiveCommand($command, $params);
	}

	public static function onLocalCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;

		if ($messageFields['COMMAND_CONTEXT'] != 'TEXTAREA')
			return false;

		if ($messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE)
			return false;

		if ($messageFields['COMMAND'] != 'unregister')
			return false;

		global $GLOBALS;
		$grantAccess = \IsModuleInstalled('bitrix24')? $GLOBALS['USER']->CanDoOperation('bitrix24_config'): $GLOBALS["USER"]->IsAdmin();
		if (!$grantAccess)
			return false;

		$botData = \Bitrix\Im\Bot::getCache($messageFields['TO_USER_ID']);

		if ($botData['CLASS'] != __CLASS__)
			return false;

		self::unRegister($botData['APP_ID']);

		return true;
	}




	public static function getUserGeoData()
	{
		if (isset($_SESSION["SESS_AUTH"]['GEO_DATA']))
		{
			return $_SESSION["SESS_AUTH"]['GEO_DATA'];
		}

		$contryCode = \Bitrix\Main\Service\GeoIp\Manager::getCountryCode();
		if (!$contryCode)
		{
			return defined('BOT_CLIENT_GEO_DATA')? BOT_CLIENT_GEO_DATA: '';
		}

		$countryName = \Bitrix\Main\Service\GeoIp\Manager::getCountryName('', 'ru');
		if (!$countryName)
		{
			$countryName = \Bitrix\Main\Service\GeoIp\Manager::getCountryName();
		}

		$cityName = \Bitrix\Main\Service\GeoIp\Manager::getCityName('', 'ru');
		if (!$cityName)
		{
			$cityName = \Bitrix\Main\Service\GeoIp\Manager::getCityName();
		}

		$result = $_SESSION["SESS_AUTH"]['GEO_DATA'] = $contryCode.($countryName? ' / '.$countryName: '').($cityName? ' / '.$cityName: '');

		return $result;
	}

	public static function getLangMessage($messageCode = '')
	{
		return Loc::getMessage($messageCode);
	}

	public static function uploadAvatar($avatarUrl = '')
	{
		if (!$avatarUrl)
			return '';

		if (!in_array(strtolower(\GetFileExtension($avatarUrl)), Array('png', 'jpg')))
			return '';

		$recordFile = \CFile::MakeFileArray($avatarUrl);
		if (!\CFile::IsImage($recordFile['name'], $recordFile['type']))
			return '';

		if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0 && $recordFile['size'] < 1000000)
		{
			$recordFile = array_merge($recordFile, array('MODULE_ID' => 'imbot'));
		}
		else
		{
			$recordFile = '';
		}

		return $recordFile;
	}

	public static function join($code, $options = array())
	{
		if (!$code)
		{
			return false;
		}

		$result = \Bitrix\ImBot\Bot\Network::getNetworkBotId($code, true);
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
			$result = \Bitrix\ImBot\Bot\Network::register($result[0]);
		}

		return $result;
	}

	public static function search($text, $register = false)
	{
		$text = trim($text);
		if (strlen($text) <= 3)
		{
			return false;
		}

		if (!$register && in_array($text, self::$blackListOfCodes))
		{
			return false;
		}

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$result = $http->query(
			'clientSearchLine',
			Array('TEXT' => $text),
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}

		return $result['result'];
	}

	public static function registerConnector($lineId, $fields = array())
	{
		$send['LINE_ID'] = intval($lineId);
		if ($send['LINE_ID'] <= 0)
		{
			return false;
		}
		$configManager = new \Bitrix\ImOpenLines\Config();
		$config = $configManager->get($lineId);
		if (!$config)
		{
			return false;
		}

		$send['LINE_NAME'] = trim($fields['NAME']);
		if (strlen($send['LINE_NAME']) <= 0)
		{
			$send['LINE_NAME'] = $config['LINE_NAME'];
		}

		if (strlen($send['FIRST_MESSAGE']) <= 0)
		{
			$send['FIRST_MESSAGE'] = $config['WELCOME_MESSAGE_TEXT'];
		}

		$send['LINE_DESC'] = isset($fields['DESC'])? trim($fields['DESC']): '';
		$send['FIRST_MESSAGE'] = isset($fields['FIRST_MESSAGE'])? $fields['FIRST_MESSAGE']: '';

		$send['AVATAR'] = '';

		$fields['AVATAR'] = intval($fields['AVATAR']);
		if ($fields['AVATAR'])
		{
			$fileTmp = \CFile::ResizeImageGet(
				$fields['AVATAR'],
				array('width' => 300, 'height' => 300),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			if ($fileTmp['src'])
			{
				$send['AVATAR'] = substr($fileTmp['src'], 0, 4) == 'http'? $fileTmp['src']: \Bitrix\ImBot\Http::getServerAddress().$fileTmp['src'];
			}
		}

		$send['ACTIVE'] = isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'N'? 'N': 'Y';
		$send['HIDDEN'] = isset($fields['HIDDEN']) && $fields['HIDDEN'] == 'Y'? 'Y': 'N';

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$result = $http->query(
			'RegisterConnector',
			$send,
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}
		if ($result['result'])
		{
			$result = Array(
				'CODE' => $result['result'],
				'NAME' => $send['LINE_NAME'],
				'DESC' => $send['LINE_DESC'],
				'FIRST_MESSAGE' => $send['FIRST_MESSAGE'],
				'AVATAR' => $fields['AVATAR'],
				'ACTIVE' => $send['ACTIVE'],
				'HIDDEN' => $send['HIDDEN'],
			);
		}
		return $result;
	}

	public static function updateConnector($lineId, $fields)
	{
		$update['LINE_ID'] = intval($lineId);
		if ($update['LINE_ID'] <= 0)
		{
			return false;
		}

		if (isset($fields['NAME']))
		{
			$fields['NAME'] = trim($fields['NAME']);
			if (strlen($fields['NAME']) >= 3)
			{
				$update['FIELDS']['LINE_NAME'] = $fields['NAME'];
			}
			else
			{
				self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, 'NAME_LENGTH', 'Field NAME should be 3 or more characters');
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
					array('width' => 300, 'height' => 300),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				if ($fileTmp['src'])
				{
					$update['FIELDS']['AVATAR'] = substr($fileTmp['src'], 0, 4) == 'http'? $fileTmp['src']: \Bitrix\ImBot\Http::getServerAddress().$fileTmp['src'];
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

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$result = $http->query(
			'UpdateConnector',
			$update,
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}

		return $result['result'];
	}

	public static function unRegisterConnector($lineId)
	{
		$update['LINE_ID'] = intval($lineId);
		if ($update['LINE_ID'] <= 0)
		{
			return false;
		}

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$result = $http->query(
			'UnRegisterConnector',
			Array('LINE_ID' => $lineId),
			true
		);
		if (isset($result['error']))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $result['error']['code'], $result['error']['msg']);
			return false;
		}

		return $result['result'];
	}




	public static function setNetworkBotId($code, $id)
	{
		\Bitrix\Main\Config\Option::set(self::MODULE_ID, self::BOT_CODE.'_'.$code."_bot_id", $id);

		return true;
	}

	public static function getNetworkBotId($code, $getFromDb = false)
	{
		if (!$code)
		{
			return false;
		}

		if ($getFromDb)
		{
			$row = \Bitrix\Im\Model\BotTable::getList(Array(
				'filter' => Array(
					'=TYPE' => 'N',
					'=APP_ID' => $code
				)
			))->fetch();
			if (!$row)
			{
				return 0;
			}

			$botId = \Bitrix\Main\Config\Option::get(self::MODULE_ID, self::BOT_CODE.'_'.$code."_bot_id", 0);
			if ($botId !== $row['BOT_ID'])
			{
				\Bitrix\Main\Config\Option::set(self::MODULE_ID, self::BOT_CODE.'_'.$code."_bot_id", $row['BOT_ID']);
			}

			return $row['BOT_ID'];
		}

		return \Bitrix\Main\Config\Option::get(self::MODULE_ID, self::BOT_CODE.'_'.$code."_bot_id", 0);
	}

	public static function getBotId()
	{
		return false;
	}

	public static function isFdcCode($text)
	{
		return in_array($text, self::$blackListOfCodes);
	}

	public static function setBotId($id)
	{
		return false;
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
}
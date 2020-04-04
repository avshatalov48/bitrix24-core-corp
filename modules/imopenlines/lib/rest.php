<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

if(!\Bitrix\Main\Loader::includeModule('rest'))
	return;

Loc::loadMessages(__FILE__);

class Rest extends \IRestService
{
	public static function onRestServiceBuildDescription()
	{
		return array(
			'imopenlines' => array(
				'imopenlines.operator.answer' => array(__CLASS__, 'operatorAnswer'),
				'imopenlines.operator.skip' => array(__CLASS__, 'operatorSkip'),
				'imopenlines.operator.spam' => array(__CLASS__, 'operatorSpam'),
				'imopenlines.operator.transfer' => array(__CLASS__, 'operatorTransfer'),
				'imopenlines.operator.finish' => array(__CLASS__, 'operatorFinish'),

				'imopenlines.session.intercept' => array(__CLASS__, 'sessionIntercept'),

				'imopenlines.bot.session.operator' => array(__CLASS__, 'botSessionOperator'),
				'imopenlines.bot.session.send.message' =>  array('callback' => array(__CLASS__, 'botSessionSendAutoMessage'), 'options' => array('private' => true)), // legacy
				'imopenlines.bot.session.message.send' => array(__CLASS__, 'botSessionSendAutoMessage'),
				'imopenlines.bot.session.transfer' => array(__CLASS__, 'botSessionTransfer'),
				'imopenlines.bot.session.finish' => array(__CLASS__, 'botSessionFinish'),

				'imopenlines.network.join' => array(__CLASS__, 'networkJoin'),
				'imopenlines.network.message.add' => array(__CLASS__, 'networkMessageAdd'),
				'imopenlines.config.path.get' => array(__CLASS__, 'configGetPath'),

				'imopenlines.widget.config.get' =>  array('callback' => array(__CLASS__, 'widgetConfigGet'), 'options' => array()),
				'imopenlines.widget.dialog.get' =>  array('callback' => array(__CLASS__, 'widgetDialogGet'), 'options' => array()),
				'imopenlines.widget.user.register' =>  array('callback' => array(__CLASS__, 'widgetUserRegister'), 'options' => array()),
				'imopenlines.widget.user.consent.apply' =>  array('callback' => array(__CLASS__, 'widgetUserConsentApply'), 'options' => array()),
				'imopenlines.widget.user.get' =>  array('callback' => array(__CLASS__, 'widgetUserGet'), 'options' => array()),
				'imopenlines.widget.vote.send' =>  array('callback' => array(__CLASS__, 'widgetVoteSend'), 'options' => array()),
				'imopenlines.widget.form.send' =>  array('callback' => array(__CLASS__, 'widgetFormSend'), 'options' => array()),
			),
		);
	}

	public static function operatorAnswer($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->answer();
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function operatorSkip($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->skip();
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}


		return true;
	}

	public static function operatorSpam($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->markSpam();
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function operatorFinish($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->closeDialog();
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function operatorTransfer($arParams, $n, \CRestServer $server)
	{
		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$transferId = null;
		if (isset($arParams['TRANSFER_ID']))
		{
			if (substr($arParams['TRANSFER_ID'], 0, 5) == 'queue')
			{
				$arParams['QUEUE_ID'] = substr($arParams['TRANSFER_ID'], 5);
			}
			else
			{
				$arParams['USER_ID'] = $arParams['TRANSFER_ID'];
			}
		}

		if (isset($arParams['USER_ID']))
		{
			$arParams['USER_ID'] = intval($arParams['USER_ID']);

			if ($arParams['USER_ID'] <= 0)
			{
				throw new \Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = $arParams['USER_ID'];
		}
		else if (isset($arParams['QUEUE_ID']))
		{
			$arParams['QUEUE_ID'] = intval($arParams['QUEUE_ID']);

			if ($arParams['QUEUE_ID'] <= 0)
			{
				throw new \Bitrix\Rest\RestException("QUEUE ID can't be empty", "QUEUE_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = 'queue'.$arParams['QUEUE_ID'];
		}
		else
		{
			throw new \Bitrix\Rest\RestException("Queue ID or User ID can't be empty", "TRANSFER_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->transfer(Array(
			'TRANSFER_ID' => $transferId,
		));
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("You can not redirect to this operator", "OPERATOR_WRONG", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function sessionIntercept($arParams, $n, \CRestServer $server)
	{
		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->interceptSession();

		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("You can not redirect to this operator", "OPERATOR_WRONG", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}


	public static function botSessionOperator($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method not allowed by session authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$result = $chat->endBotSession();
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("Operator is not a bot", "WRONG_CHAT", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botSessionSendAutoMessage($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method not allowed by session authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$chat->sendAutoMessage($arParams['NAME']);

		return true;
	}

	public static function botSessionTransfer($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method not allowed by session authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		$arParams['LEAVE'] = isset($arParams['LEAVE']) && $arParams['LEAVE'] == 'Y'? 'Y': 'N';

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$transferId = null;
		if (isset($arParams['TRANSFER_ID']))
		{
			if (substr($arParams['TRANSFER_ID'], 0, 5) == 'queue')
			{
				$arParams['QUEUE_ID'] = substr($arParams['TRANSFER_ID'], 5);
			}
			else
			{
				$arParams['USER_ID'] = $arParams['TRANSFER_ID'];
			}
		}

		if (isset($arParams['USER_ID']))
		{
			$arParams['USER_ID'] = intval($arParams['USER_ID']);

			if ($arParams['USER_ID'] <= 0)
			{
				throw new \Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = $arParams['USER_ID'];
		}
		else if (isset($arParams['QUEUE_ID']))
		{
			$arParams['QUEUE_ID'] = intval($arParams['QUEUE_ID']);

			if ($arParams['QUEUE_ID'] <= 0)
			{
				throw new \Bitrix\Rest\RestException("QUEUE ID can't be empty", "QUEUE_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = 'queue'.$arParams['QUEUE_ID'];
		}
		else
		{
			throw new \Bitrix\Rest\RestException("Queue ID or User ID can't be empty", "TRANSFER_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$bots = \Bitrix\Im\Bot::getListCache();
		$botFound = false;
		$botId = 0;
		foreach ($bots as $bot)
		{
			if ($bot['APP_ID'] == $server->getAppId())
			{
				$botFound = true;
				$botId = $bot['BOT_ID'];
				break;
			}
		}
		if (!$botFound)
		{
			throw new \Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$result = $chat->transfer(Array(
			'FROM' => $botId,
			'TO' => $transferId,
			'MODE' => Chat::TRANSFER_MODE_BOT,
			'LEAVE' => $arParams['LEAVE']
		));
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("You can not redirect to this operator", "OPERATOR_WRONG", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botSessionFinish($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method not allowed by session authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$bots = \Bitrix\Im\Bot::getListCache();
		$botFound = false;
		$botId = 0;
		foreach ($bots as $bot)
		{
			if ($bot['APP_ID'] == $server->getAppId())
			{
				$botFound = true;
				$botId = $bot['BOT_ID'];
				break;
			}
		}
		if (!$botFound)
		{
			throw new \Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$chat->answer($botId);
		$chat->finish();

		return true;
	}

	public static function configGetPath($arParams, $n, \CRestServer $server)
	{
		return array(
			'SERVER_ADDRESS' => \Bitrix\ImOpenLines\Common::getServerAddress(),
			'PUBLIC_PATH' => \Bitrix\ImOpenLines\Common::getPublicFolder()
		);
	}

	public static function networkJoin($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);
		if (!isset($arParams['CODE']) || strlen($arParams['CODE']) != 32)
		{
			throw new \Bitrix\Rest\RestException("You entered an invalid code", "CODE", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\Main\Loader::includeModule('imbot'))
		{
			throw new \Bitrix\Rest\RestException("Module IMBOT is not installed", "IMBOT_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (\Bitrix\ImBot\Bot\Network::isFdcCode($arParams['CODE']))
		{
			throw new \Bitrix\Rest\RestException("Line not found", "NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$network = new \Bitrix\ImOpenLines\Network();
		$result = $network->join($arParams['CODE']);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($network->getError()->msg, $network->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return $result;
	}

	public static function networkMessageAdd($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method not allowed by session authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);
		if (!isset($arParams['CODE']) || strlen($arParams['CODE']) != 32)
		{
			throw new \Bitrix\Rest\RestException("You entered an invalid code", "CODE", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\Main\Loader::includeModule('imbot'))
		{
			throw new \Bitrix\Rest\RestException("Module IMBOT is not installed", "IMBOT_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (\Bitrix\ImBot\Bot\Network::isFdcCode($arParams['CODE']))
		{
			throw new \Bitrix\Rest\RestException("Line not found", "NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$networkBot = null;

		$bots = \Bitrix\Im\Bot::getListCache();
		foreach ($bots as $bot)
		{
			if ($bot['APP_ID'] == $arParams['CODE'])
			{
				$networkBot = $bot;
				break;
			}
		}
		if (!$networkBot)
		{
			throw new \Bitrix\Rest\RestException("Line not found", "NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$arMessageFields = Array();

		$arMessageFields['DIALOG_ID'] = intval($arParams['USER_ID']);
		if (empty($arMessageFields['DIALOG_ID']))
		{
			throw new \Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$isBitrix24 = \Bitrix\Main\Loader::includeModule('bitrix24');
		if (!$isBitrix24 || !\CBitrix24::IsNfrLicense())
		{
			$dateLimit = new \Bitrix\Main\Type\DateTime();
			$dateLimit->add('-1 WEEK');

			$check = \Bitrix\Imopenlines\Model\RestNetworkLimitTable::getList(Array(
				'filter' => Array(
					'=BOT_ID' => $networkBot['BOT_ID'],
					'=USER_ID' => $arMessageFields['DIALOG_ID'],
					'>DATE_CREATE' => $dateLimit
				)
			))->fetch();
			if ($check)
			{
				throw new \Bitrix\Rest\RestException("You cant send more than one message per week to each user.", "USER_MESSAGE_LIMIT", \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$arMessageFields['MESSAGE'] = trim($arParams['MESSAGE']);
		if (strlen($arMessageFields['MESSAGE']) <= 0)
		{
			throw new \Bitrix\Rest\RestException("Message can't be empty", "MESSAGE_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['ATTACH']) && !empty($arParams['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
			if ($attach)
			{
				if ($attach->IsAllowSize())
				{
					$arMessageFields['ATTACH'] = $attach;
				}
				else
				{
					throw new \Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", \CRestServer::STATUS_WRONG_REQUEST);
				}
			}
			else if ($arParams['ATTACH'])
			{
				throw new \Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['KEYBOARD']) && !empty($arParams['KEYBOARD']))
		{
			$keyboard = Array();
			if (!isset($arParams['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $arParams['KEYBOARD'];
			}
			else
			{
				$keyboard = $arParams['KEYBOARD'];
			}
			$keyboard['BOT_ID'] = $arParams['BOT_ID'];

			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
			if ($keyboard)
			{
				$arMessageFields['KEYBOARD'] = $keyboard;
			}
			else
			{
				throw new \Bitrix\Rest\RestException("Incorrect keyboard params", "KEYBOARD_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == 'N')
		{
			$arMessageFields['URL_PREVIEW'] = 'N';
		}
		$arMessageFields['PARAMS']['IMOL_QUOTE_MSG'] = 'Y';

		$id = \Bitrix\Im\Bot::addMessage(array('BOT_ID' => $networkBot['BOT_ID']), $arMessageFields);
		if (!$id)
		{
			throw new \Bitrix\Rest\RestException("Message isn't added", "WRONG_REQUEST", \CRestServer::STATUS_WRONG_REQUEST);
		}

		\Bitrix\Imopenlines\Model\RestNetworkLimitTable::add(Array('BOT_ID' => $networkBot['BOT_ID'], 'USER_ID' => $arMessageFields['DIALOG_ID']));

		return true;
	}

	public static function widgetUserRegister($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);
		if (
			$_SESSION['LIVECHAT']['REGISTER']
			&& !(
				isset($params['USER_HASH']) && trim($params['USER_HASH']) && preg_match("/^[a-fA-F0-9]{32}$/i", $params['USER_HASH'])
			)
		)
		{
			return $_SESSION['LIVECHAT']['REGISTER'];
		}

		$params['CONFIG_ID'] = intval($params['CONFIG_ID']);
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Config id is not specified.", "CONFIG_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$config = \Bitrix\Imopenlines\Model\ConfigTable::getById($params['CONFIG_ID'])->fetch();
		if (!$config)
		{
			throw new \Bitrix\Rest\RestException("Config is not found.", "CONFIG_NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$userData = \Bitrix\Imopenlines\Widget\User::register([
			'NAME' => $params['NAME'],
			'LAST_NAME' => $params['LAST_NAME'],
			'AVATAR' => $params['AVATAR'],
			'EMAIL' => $params['EMAIL'],
			'PERSONAL_WWW' => $params['WWW'],
			'PERSONAL_GENDER' => $params['GENDER'],
			'WORK_POSITION' => $params['POSITION'],
			'USER_HASH' => $params['USER_HASH'],
		]);
		if (!$userData)
		{
			throw new \Bitrix\Rest\RestException(
				\Bitrix\Imopenlines\Widget\User::getError()->msg,
				\Bitrix\Imopenlines\Widget\User::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$dialogData = \Bitrix\Imopenlines\Widget\Dialog::register($userData['ID'], $config['ID']);
		if (!$dialogData)
		{
			throw new \Bitrix\Rest\RestException(
				\Bitrix\Imopenlines\Widget\Dialog::getError()->msg,
				\Bitrix\Imopenlines\Widget\Dialog::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		\Bitrix\Imopenlines\Widget\Auth::authorizeById($userData['ID'], true, true);

		$result = [
			'id' => (int)$userData['ID'],
			'hash' => $userData['HASH'],
			'chatId' => (int)$dialogData['CHAT_ID'],
			'dialogId' => 'chat'.$dialogData['CHAT_ID'],
			'userConsent' => false,
		];

		$_SESSION['LIVECHAT']['REGISTER'] = $result;

		$_SESSION['LIVECHAT']['TRACE_DATA'] = (string)$params['TRACE_DATA'];
		$_SESSION['LIVECHAT']['CUSTOM_DATA'] = (string)$params['CUSTOM_DATA'];

		return $result;
	}

	public static function widgetUserConsentApply($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only for authorized users.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = intval($params['CONFIG_ID']);
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Config id is not specified.", "CONFIG_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$config = \Bitrix\Imopenlines\Model\ConfigTable::getById($params['CONFIG_ID'])->fetch();
		if (!$config)
		{
			throw new \Bitrix\Rest\RestException("Config is not found.", "CONFIG_NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			throw new \Bitrix\Rest\RestException("Messenger is not installed.", "IM_NOT_INSTALLED", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($config['AGREEMENT_MESSAGE'] != 'Y')
		{
			return false;
		}
		
		$chat = \Bitrix\Im\Model\ChatTable::getList(array(
			'select' => ['ID'],
			'filter' => array(
				'=ENTITY_TYPE' => 'LIVECHAT',
				'=ENTITY_ID' => $config['ID'].'|'.$USER->GetID()
			),
			'limit' => 1
		))->fetch();
		if (!$chat)
		{
			throw new \Bitrix\Rest\RestException("Chat is not found.", "CHAT_NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		\Bitrix\Main\UserConsent\Consent::addByContext(
			intval($config['AGREEMENT_ID']),
			'imopenlines/livechat',
			$chat['ID'],
			Array('URL' => trim($params['CONSENT_URL']))
		);

		return true;
	}

	public static function widgetVoteSend($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['SESSION_ID'] = intval($params['SESSION_ID']);
		if ($params['SESSION_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Session id is not specified.", "SESSION_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$action = strtolower($params['ACTION']) !== 'like';

		\Bitrix\ImOpenlines\Session::voteAsUser($params['SESSION_ID'], $action);

		return true;
	}

	public static function widgetFormSend($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CHAT_ID'] = intval($params['CHAT_ID']);
		if ($params['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat id is not specified.", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$params['FIELDS'] = array_change_key_case($params['FIELDS'], CASE_UPPER);
		if (empty($params['FIELDS']))
		{
			throw new \Bitrix\Rest\RestException("Form fields is not specified.", "FIELDS_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$control = new \Bitrix\ImOpenLines\Widget\Form($params['CHAT_ID']);
		$result = $control->saveForm($params['FORM'], $params['FIELDS']);
		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			$error = current($errors);

			throw new \Bitrix\Rest\RestException('Form error: "'.$error->getMessage().'" ['.$error->getCode().']', "SAVE_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function widgetUserGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only for authorized users.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$result = \Bitrix\Imopenlines\Widget\User::get($USER->GetID());

		return self::objectEncode($result);
	}

	public static function widgetDialogGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only for authorized users.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = intval($params['CONFIG_ID']);
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Config id is not specified.", "WRONG_REQUEST", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$_SESSION['LIVECHAT']['TRACE_DATA'] = (string)$params['TRACE_DATA'];

		$_SESSION['LIVECHAT']['CUSTOM_DATA'] = (string)$params['CUSTOM_DATA'];

		$result = \Bitrix\Imopenlines\Widget\Dialog::get($USER->GetID(), $params['CONFIG_ID']);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException(
				\Bitrix\Imopenlines\Widget\Dialog::getError()->msg,
				\Bitrix\Imopenlines\Widget\Dialog::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		return self::objectEncode($result);
	}

	public static function widgetConfigGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$config = \Bitrix\Imopenlines\Widget\Config::getByCode($params['CODE']);
		if (!$config)
		{
			throw new \Bitrix\Rest\RestException(
				\Bitrix\Imopenlines\Widget\Config::getError()->msg,
				\Bitrix\Imopenlines\Widget\Config::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		shuffle($config['OPERATORS']);
		$config['OPERATORS'] = array_slice($config['OPERATORS'], 0, 3);

		$result = self::objectEncode($config);

		$coreMessages = \CJSCore::GetCoreMessages();
		$result['serverVariables'] = Array(
			'FORMAT_DATE' => $coreMessages['FORMAT_DATE'],
			'FORMAT_DATETIME' => $coreMessages['FORMAT_DATETIME'],
			'AMPM_MODE' => IsAmPmMode(true),
		);

		return $result;
	}

	public static function objectEncode($data, $options = [])
	{
		if (!is_array($options['IMAGE_FIELD']))
		{
			$options['IMAGE_FIELD'] = ['AVATAR', 'AVATAR_HR'];
		}

		if (is_array($data))
		{
			$result = [];
			foreach ($data as $key => $value)
			{
				if (is_array($value))
				{
					$value = self::objectEncode($value, $options);
				}
				else if ($value instanceof \Bitrix\Main\Type\DateTime)
				{
					$value = date('c', $value->getTimestamp());
				}
				else if (is_string($key) && in_array($key, $options['IMAGE_FIELD']) && is_string($value) && $value && strpos($value, 'http') !== 0)
				{
					$value = \Bitrix\ImOpenLines\Common::getServerAddress().$value;
				}

				$key = str_replace('_', '', lcfirst(ucwords(strtolower($key), '_')));

				$result[$key] = $value;
			}
			$data = $result;
		}

		return $data;
	}
}

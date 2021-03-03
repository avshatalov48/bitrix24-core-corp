<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImOpenLines\Crm\Common;


if(!\Bitrix\Main\Loader::includeModule('rest'))
	return;

Loc::loadMessages(__FILE__);

class Rest extends \IRestService
{
	public static function onRestServiceBuildDescription()
	{
		return array(
			'imopenlines' => array(
				'imopenlines.revision.get' => array(__CLASS__, 'revisionGet'),

				'imopenlines.dialog.get' => array(__CLASS__, 'dialogGet'),
				'imopenlines.dialog.user.depersonalization' => array('callback' => array(__CLASS__, 'dialogUserDepersonalization'), 'options' => array('private' => true)),

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

				'imopenlines.widget.config.get' =>  array('callback' => array(__CLASS__, 'widgetConfigGet'), 'options' => array()),
				'imopenlines.widget.dialog.get' =>  array('callback' => array(__CLASS__, 'widgetDialogGet'), 'options' => array()),
				'imopenlines.widget.user.register' =>  array('callback' => array(__CLASS__, 'widgetUserRegister'), 'options' => array()),
				'imopenlines.widget.user.consent.apply' =>  array('callback' => array(__CLASS__, 'widgetUserConsentApply'), 'options' => array()),
				'imopenlines.widget.user.get' =>  array('callback' => array(__CLASS__, 'widgetUserGet'), 'options' => array()),
				'imopenlines.widget.operator.get' =>  array('callback' => array(__CLASS__, 'widgetOperatorGet'), 'options' => array()),
				'imopenlines.widget.vote.send' =>  array('callback' => array(__CLASS__, 'widgetVoteSend'), 'options' => array()),
				'imopenlines.widget.action.send' =>  array('callback' => array(__CLASS__, 'widgetActionSend'), 'options' => array()),
				'imopenlines.widget.form.send' =>  array('callback' => array(__CLASS__, 'widgetFormSend'), 'options' => array()),
//				'imopenlines.widget.form.fill' =>  array('callback' => array(__CLASS__, 'widgetFormFill'), 'options' => array()),

				'imopenlines.config.path.get' => array(__CLASS__, 'configGetPath'),
				'imopenlines.config.get' => array(__CLASS__, 'configGet'),
				'imopenlines.config.list.get' => array(__CLASS__, 'configListGet'),
				'imopenlines.config.update' => array(__CLASS__, 'configUpdate'),
				'imopenlines.config.add' => array(__CLASS__, 'configAdd'),
				'imopenlines.config.delete' => array(__CLASS__, 'configDelete'),

				'imopenlines.crm.chat.user.add' => array(__CLASS__, 'crmChatUserAdd'),
				'imopenlines.crm.chat.getLastId' => array(__CLASS__, 'crmLastChatIdGet')
			),
		);
	}

	public static function revisionGet($arParams, $n, \CRestServer $server)
	{
		return \Bitrix\Imopenlines\Revision::get();
	}

	public static function dialogGet($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		if (!Main\Loader::includeModule('im'))
		{
			throw new \Bitrix\Rest\RestException("Messenger is not installed.", "IM_NOT_INSTALLED", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chatId = \Bitrix\ImOpenLines\Rest::getChatId($params);
		if (!$chatId)
		{
			throw new \Bitrix\Rest\RestException("You do not have access to the specified dialog", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\ImOpenLines\Chat::hasAccess($chatId))
		{
			throw new \Bitrix\Rest\RestException("You do not have access to the specified dialog", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = \Bitrix\Im\Chat::getById($chatId, ['LOAD_READED' => true, 'JSON' => true]);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("You do not have access to the specified dialog", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result['dialog_id'] = 'chat'.$chatId;

		return $result;
	}

	public static function dialogUserDepersonalization($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		if ($server->getAuthType() == \Bitrix\Rest\SessionAuth\Auth::AUTH_TYPE)
		{
			throw new \Bitrix\Rest\RestException("Access for this method not allowed by session authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Main\Loader::includeModule('im'))
		{
			throw new \Bitrix\Rest\RestException("Messenger is not installed.", "IM_NOT_INSTALLED", \CRestServer::STATUS_WRONG_REQUEST);
		}

		global $USER;
		$userId = $USER->GetID();

		if (!(
			$USER->IsAdmin()
			|| \Bitrix\Main\Loader::includeModule('bitrix24') && \CBitrix24::IsPortalAdmin($userId)
		))
		{
			throw new \Bitrix\Rest\RestException("You don't have access to this method", "ACCESS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!isset($params['USER_CODE']) || empty($params['USER_CODE']))
		{
			throw new \Bitrix\Rest\RestException("User code is not specified", "USER_CODE_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$guestUserId = \Bitrix\ImOpenLines\Common::getUserIdByCode($params['USER_CODE']);
		if (!$guestUserId)
		{
			throw new \Bitrix\Rest\RestException("The specified user is not a client of openlines module", "USER_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return \Bitrix\ImOpenLines\Common::depersonalizationLinesUser($guestUserId);
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

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Bitrix\Rest\RestException
	 */
	public static function operatorFinish($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new \Bitrix\ImOpenLines\Operator($arParams['CHAT_ID']);
		$result = $control->closeDialog();
		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			$error = current($errors);
			throw new \Bitrix\Rest\RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
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
			if (mb_substr($arParams['TRANSFER_ID'], 0, 5) == 'queue')
			{
				$arParams['QUEUE_ID'] = mb_substr($arParams['TRANSFER_ID'], 5);
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
		$arParams['MESSAGE'] = !empty($arParams['MESSAGE']) ? $arParams['MESSAGE'] : '';
		$chat->sendAutoMessage($arParams['NAME'], $arParams['MESSAGE']);

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
			if (mb_substr($arParams['TRANSFER_ID'], 0, 5) == 'queue')
			{
				$arParams['QUEUE_ID'] = mb_substr($arParams['TRANSFER_ID'], 5);
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
		if (!isset($arParams['CODE']) || mb_strlen($arParams['CODE']) != 32)
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
		if (!isset($arParams['CODE']) || mb_strlen($arParams['CODE']) != 32)
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
		if (
			$isBitrix24 && !\CBitrix24::IsNfrLicense()
			|| !$isBitrix24 && !defined('IMOPENLINES_NETWORK_LIMIT')
		)
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
		if ($arMessageFields['MESSAGE'] == '')
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

		if (
			$_SESSION['LIVECHAT']['REGISTER']
			&& !(
				isset($params['USER_HASH']) && trim($params['USER_HASH']) && preg_match("/^[a-fA-F0-9]{32}$/i", $params['USER_HASH'])
			)
		)
		{
			$params['USER_HASH'] = $_SESSION['LIVECHAT']['REGISTER']['hash'];
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

		\Bitrix\ImOpenLines\Widget\Cache::set($userData['ID'], [
			'TRACE_DATA' => (string)$params['TRACE_DATA'],
	 		'CUSTOM_DATA' => (string)$params['CUSTOM_DATA'],
		]);

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

		$action = mb_strtolower($params['ACTION']);

		\Bitrix\ImOpenlines\Session::voteAsUser($params['SESSION_ID'], $action);

		return true;
	}

	public static function widgetActionSend($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['MESSAGE_ID']))
		{
			$arParams['ID'] = $arParams['MESSAGE_ID'];
		}

		$arParams['ID'] = intval($arParams['ID']);
		if ($arParams['ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = \Bitrix\ImOpenLines\Widget\Action::execute($arParams['ID'], $arParams['ACTION_VALUE']);
		if ($result === false)
		{
			throw new \Bitrix\Rest\RestException("Incorrect params", "PARAMS_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

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

		if ($params['FORM'] === \Bitrix\ImOpenLines\Widget\Form::FORM_HISTORY)
		{
			return true; // TODO temporary blocked fix 0134384
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

//	public static function widgetFormFill($params, $n, \CRestServer $server)
//	{
//		if ($server->getAuthType() != \Bitrix\Imopenlines\Widget\Auth::AUTH_TYPE)
//		{
//			throw new \Bitrix\Rest\RestException("Access for this method allowed only by livechat authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_FORBIDDEN);
//		}
//
//		\CIMMessageParam::Set($params['MESSAGE_ID'], ['CRM_FORM_VALUE' => $params['CRM_FORM_VALUE']]);
//		\CIMMessageParam::SendPull($params['MESSAGE_ID'], ['CRM_FORM_VALUE']);
//
//		return true;
//	}

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

	public function widgetOperatorGet($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		$type = \CPullChannel::TYPE_PRIVATE;
		if ($params['APPLICATION'] == 'Y')
		{
			$clientId = $server->getClientId();
			if (!$clientId)
			{
				throw new \Bitrix\Rest\RestException("Get application public channel available only for application authorization.", "WRONG_AUTH_TYPE", \CRestServer::STATUS_WRONG_REQUEST);
			}
			$type = $clientId;
		}

		$users = Array();
		if (is_string($params['USERS']))
		{
			$params['USERS'] = \CUtil::JsObjectToPhp($params['USERS']);
		}
		if (is_array($params['USERS']))
		{
			foreach ($params['USERS'] as $userId)
			{
				$userId = intval($userId);
				if ($userId > 0)
				{
					$users[$userId] = $userId;
				}
			}
		}

		if (empty($users))
		{
			throw new \Bitrix\Rest\RestException("A wrong format for the USERS field is passed", "INVALID_FORMAT", \CRestServer::STATUS_WRONG_REQUEST);
		}

		global $USER;
		$operators = \Bitrix\ImOpenLines\Model\SessionTable::getList([
			'select' => ['OPERATOR_ID'],
			'filter' => [
				'=USER_ID' => $USER->GetID(),
				'=CLOSED' => 'N'
			]])->fetchAll();

		$operators = array_map(function($operator){
			return $operator['OPERATOR_ID'];
		}, $operators);

		foreach ($users as $user)
		{
			if (!in_array((string)$user, $operators, true))
			{
				//TODO: Exception details
				throw new \Bitrix\Rest\RestException("Wrong operator ID");
			}
		}

		$configParams = Array();
		$configParams['TYPE'] = $type;
		$configParams['USERS'] = $users;
		$configParams['JSON'] = true;

		$config = \Bitrix\Pull\Channel::getPublicIds($configParams);
		if (!$config)
		{
			throw new \Bitrix\Rest\RestException("Push & Pull server is not configured", "SERVER_ERROR", \CRestServer::STATUS_INTERNAL);
		}

		return $config;
	}

	/**
	 * @param $params
	 * @param $n
	 * @param \CRestServer $server
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Bitrix\Rest\RestException
	 */
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

		\Bitrix\ImOpenLines\Widget\Cache::set($USER->GetId(), [
	 		'TRACE_DATA' => (string)$params['TRACE_DATA'],
	 		'CUSTOM_DATA' => (string)$params['CUSTOM_DATA'],
		]);

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

	/**
	 * @param $params
	 * @param $n
	 * @param \CRestServer $server
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Bitrix\Rest\RestException
	 */
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
		$result['serverVariables'] = [
			'FORMAT_DATE' => $coreMessages['FORMAT_DATE'],
			'FORMAT_DATETIME' => $coreMessages['FORMAT_DATETIME'],
			'AMPM_MODE' => IsAmPmMode(true),
			'UTF_MODE' => \Bitrix\Main\Application::getInstance()->isUtfMode() ? 'Y' : 'N',
		];

		return $result;
	}

	public static function configGet($arParams, $n, \CRestServer $server)
	{
		$arParams['CONFIG_ID'] = intval($arParams['CONFIG_ID']);

		if ($arParams['CONFIG_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Config ID can't be empty", "CONFIG_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['WITH_QUEUE'] = isset($arParams['WITH_QUEUE']) ? $arParams['WITH_QUEUE'] == 'Y' : true;
		$arParams['SHOW_OFFLINE'] = isset($arParams['SHOW_OFFLINE']) ? $arParams['SHOW_OFFLINE'] == 'Y' : true;

		$config = new Config();

		return $config->get($arParams['CONFIG_ID'], $arParams['WITH_QUEUE'], $arParams['SHOW_OFFLINE']);
	}

	public static function configListGet($arParams, $n, \CRestServer $server)
	{
		$config = new Config();

		$arParams['PARAMS'] = !empty($arParams['PARAMS']) && is_array($arParams['PARAMS']) ? $arParams['PARAMS'] : [];
		$arParams['OPTIONS'] = !empty($arParams['OPTIONS']) && is_array($arParams['OPTIONS']) ? $arParams['OPTIONS'] : [];

		return $config->getList($arParams['PARAMS'], $arParams['OPTIONS']);
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return bool
	 * @throws \Bitrix\Rest\RestException
	 */
	public static function configUpdate($arParams, $n, \CRestServer $server)
	{
		$arParams['CONFIG_ID'] = (int)$arParams['CONFIG_ID'];

		if ($arParams['CONFIG_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Config ID can't be empty", "CONFIG_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Config::canEditLine($arParams['CONFIG_ID']))
		{
			throw new \Bitrix\Rest\RestException("Permission denied", "CONFIG_WRONG_USER_PERMISSION", \CRestServer::STATUS_FORBIDDEN);
		}

		$arParams['PARAMS'] = !empty($arParams['PARAMS']) && is_array($arParams['PARAMS']) ? $arParams['PARAMS'] : [];
		$config = new Config();

		return $config->update($arParams['CONFIG_ID'], $arParams['PARAMS']);
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return array|bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function configAdd($arParams, $n, \CRestServer $server)
	{
		$arParams['PARAMS'] = !empty($arParams['PARAMS']) && is_array($arParams['PARAMS']) ? $arParams['PARAMS'] : [];
		$config = new Config();

		return $config->create($arParams['PARAMS']);
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Bitrix\Rest\RestException
	 */
	public static function configDelete($arParams, $n, \CRestServer $server)
	{
		$arParams['CONFIG_ID'] = (int)$arParams['CONFIG_ID'];

		if ($arParams['CONFIG_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Config ID can't be empty", "CONFIG_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Config::canEditLine($arParams['CONFIG_ID']))
		{
			throw new \Bitrix\Rest\RestException("Permission denied", "CONFIG_WRONG_USER_PERMISSION", \CRestServer::STATUS_FORBIDDEN);
		}

		$config = new Config();

		return $config->delete($arParams['CONFIG_ID']);
	}

	/**
	 * Add user to chat by connected crm entity data
	 */
	public static function crmChatUserAdd($arParams, $n, \CRestServer $server)
	{
		if (empty($arParams['CRM_ENTITY_TYPE']) || empty($arParams['CRM_ENTITY']))
		{
			throw new \Bitrix\Rest\RestException("Empty CRM data", "CRM_CHAT_EMPTY_CRM_DATA", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Main\Loader::includeModule('im'))
		{
			throw new \Bitrix\Rest\RestException("Messenger is not installed.", "IM_NOT_INSTALLED", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chatId = Common::getLastChatIdByCrmEntity($arParams['CRM_ENTITY_TYPE'], $arParams['CRM_ENTITY']);

		if ($chatId > 0)
		{
			if (!Config::canJoin($chatId, $arParams['CRM_ENTITY_TYPE'], $arParams['CRM_ENTITY']))
			{
				throw new \Bitrix\Rest\RestException("You don't have access to join user to chat", "CHAT_JOIN_PERMISSION_DENIED", \CRestServer::STATUS_FORBIDDEN);
			}

			$arParams['USER_ID'] = intval($arParams['USER_ID']);
			if ($arParams['USER_ID'] <= 0)
			{
				throw new \Bitrix\Rest\RestException("Empty User ID", "CRM_CHAT_EMPTY_USER", \CRestServer::STATUS_WRONG_REQUEST);
			}

			$user = \Bitrix\Im\User::getInstance($arParams['USER_ID']);

			if (!$user->isExists() || !$user->isActive())
			{
				throw new \Bitrix\Rest\RestException("User not active", "CRM_CHAT_USER_NOT_ACTIVE", \CRestServer::STATUS_WRONG_REQUEST);
			}

			$CIMChat = new \CIMChat(0);
			$result = $CIMChat->AddUser($chatId, $arParams['USER_ID']);

			if (!$result)
			{
				throw new \Bitrix\Rest\RestException("You don't have access or user already member in chat", "WRONG_REQUEST", \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		return $chatId;
	}

	/**
	 * Get last chat id from crm entity data
	 */
	public static function crmLastChatIdGet($arParams, $n, \CRestServer $server): int
	{
		if (empty($arParams['CRM_ENTITY_TYPE']) || empty($arParams['CRM_ENTITY']))
		{
			throw new \Bitrix\Rest\RestException('Empty CRM data', 'CRM_CHAT_EMPTY_CRM_DATA', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chatId = Common::getLastChatIdByCrmEntity($arParams['CRM_ENTITY_TYPE'], $arParams['CRM_ENTITY']);

		if ($chatId === 0)
		{
			throw new \Bitrix\Rest\RestException('Could not find CRM entity', 'CRM_CHAT_EMPTY_CRM_DATA', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return $chatId;
	}

	private static function getChatId(array $params)
	{
		if (!Main\Loader::includeModule('im'))
		{
			return null;
		}

		if (isset($params['CHAT_ID']))
		{
			return intval($params['CHAT_ID']);
		}

		if (isset($params['DIALOG_ID']))
		{
			if (\Bitrix\Im\Common::isChatId($params['DIALOG_ID']))
			{
				return \Bitrix\Im\Dialog::getChatId($params['DIALOG_ID']);
			}

			return null;
		}

		if (isset($params['SESSION_ID']))
		{
			return \Bitrix\ImOpenLines\Chat::getChatIdBySession($params['SESSION_ID']);
		}

		if (isset($params['USER_CODE']))
		{
			if (mb_substr($params['USER_CODE'], 0, 5) === 'imol|')
			{
				$params['USER_CODE'] = mb_substr($params['USER_CODE'], 5);
			}

			return \Bitrix\ImOpenLines\Chat::getChatIdByUserCode($params['USER_CODE']);
		}

		return null;
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
				else if (is_string($key) && in_array($key, $options['IMAGE_FIELD']) && is_string($value) && $value && mb_strpos($value, 'http') !== 0)
				{
					$value = \Bitrix\ImOpenLines\Common::getServerAddress().$value;
				}

				$key = str_replace('_', '', lcfirst(ucwords(mb_strtolower($key), '_')));

				$result[$key] = $value;
			}
			$data = $result;
		}

		return $data;
	}
}

<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Type\DateTime;
use	Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UserConsent\Consent;

use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Widget\FormHandler;

use Bitrix\Im;

use Bitrix\Rest\SessionAuth;
use Bitrix\Rest\RestException;

use Bitrix\Crm\WebForm;

use Bitrix\ImBot\Bot\Network;

if (!Loader::includeModule('rest'))
{
	return;
}

Loc::loadMessages(__FILE__);

/**
 * Class Rest
 *
 * @package Bitrix\ImOpenLines
 */
class Rest extends \IRestService
{
	/**
	 * @return array[]
	 */
	public static function onRestServiceBuildDescription(): array
	{
		return [
			'imopenlines' => [
				'imopenlines.revision.get' => [__CLASS__, 'revisionGet'],

				'imopenlines.dialog.get' => [__CLASS__, 'dialogGet'],
				'imopenlines.dialog.user.depersonalization' => ['callback' => [__CLASS__, 'dialogUserDepersonalization'], 'options' => ['private' => true]],
				'imopenlines.dialog.form.send' => [__CLASS__, 'dialogFormSend'],

				'imopenlines.operator.answer' => [__CLASS__, 'operatorAnswer'],
				'imopenlines.operator.skip' => [__CLASS__, 'operatorSkip'],
				'imopenlines.operator.spam' => [__CLASS__, 'operatorSpam'],
				'imopenlines.operator.transfer' => [__CLASS__, 'operatorTransfer'],
				'imopenlines.operator.finish' => [__CLASS__, 'operatorFinish'],

				'imopenlines.session.intercept' => [__CLASS__, 'sessionIntercept'],

				'imopenlines.bot.session.operator' => [__CLASS__, 'botSessionOperator'],
				'imopenlines.bot.session.send.message' => ['callback' => [__CLASS__, 'botSessionSendAutoMessage'], 'options' => ['private' => true]], // legacy
				'imopenlines.bot.session.message.send' => [__CLASS__, 'botSessionSendAutoMessage'],
				'imopenlines.bot.session.transfer' => [__CLASS__, 'botSessionTransfer'],
				'imopenlines.bot.session.finish' => [__CLASS__, 'botSessionFinish'],

				'imopenlines.network.join' => [__CLASS__, 'networkJoin'],
				'imopenlines.network.message.add' => [__CLASS__, 'networkMessageAdd'],

				'imopenlines.widget.config.get' => ['callback' => [__CLASS__, 'widgetConfigGet'], 'options' => []],
				'imopenlines.widget.dialog.get' => ['callback' => [__CLASS__, 'widgetDialogGet'], 'options' => []],
				'imopenlines.widget.dialog.list' => ['callback' => [__CLASS__, 'widgetDialogList'], 'options' => []],
				'imopenlines.widget.user.register' => ['callback' => [__CLASS__, 'widgetUserRegister'], 'options' => []],
				'imopenlines.widget.chat.create' => ['callback' => [__CLASS__, 'widgetChatCreate'], 'options' => []],
				'imopenlines.widget.user.consent.apply' => ['callback' => [__CLASS__, 'widgetUserConsentApply'], 'options' => []],
				'imopenlines.widget.user.get' => ['callback' => [__CLASS__, 'widgetUserGet'], 'options' => []],
				'imopenlines.widget.operator.get' => ['callback' => [__CLASS__, 'widgetOperatorGet'], 'options' => []],
				'imopenlines.widget.vote.send' => ['callback' => [__CLASS__, 'widgetVoteSend'], 'options' => []],
				'imopenlines.widget.action.send' => ['callback' => [__CLASS__, 'widgetActionSend'], 'options' => []],
				'imopenlines.widget.crm.bindings.get' => ['callback' => [__CLASS__, 'widgetCrmBindingsGet'], 'options' => []],

				'imopenlines.config.path.get' => [__CLASS__, 'configGetPath'],
				'imopenlines.config.get' => [__CLASS__, 'configGet'],
				'imopenlines.config.list.get' => [__CLASS__, 'configListGet'],
				'imopenlines.config.update' => [__CLASS__, 'configUpdate'],
				'imopenlines.config.add' => [__CLASS__, 'configAdd'],
				'imopenlines.config.delete' => [__CLASS__, 'configDelete'],

				'imopenlines.crm.chat.user.add' => [__CLASS__, 'crmChatUserAdd'],
				'imopenlines.crm.chat.getLastId' => [__CLASS__, 'crmLastChatIdGet']
			],
		];
	}

	public static function revisionGet($arParams, $n, \CRestServer $server)
	{
		return \Bitrix\Imopenlines\Revision::get();
	}

	public static function dialogGet($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		if (!Loader::includeModule('im'))
		{
			throw new RestException('Messenger is not installed.', 'IM_NOT_INSTALLED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chatId = self::getChatId($params);
		if (!$chatId)
		{
			throw new RestException('You do not have access to the specified dialog', 'ACCESS_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\ImOpenLines\Chat::hasAccess($chatId))
		{
			throw new RestException('You do not have access to the specified dialog', 'ACCESS_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = Im\Chat::getById($chatId, ['LOAD_READED' => true, 'JSON' => true]);
		if (!$result)
		{
			throw new RestException('You do not have access to the specified dialog', 'ACCESS_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result['dialog_id'] = 'chat'.$chatId;

		return $result;
	}

	public static function dialogFormSend($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		if (!Loader::includeModule('im'))
		{
			throw new RestException('Messenger is not installed.', 'IM_NOT_INSTALLED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Loader::includeModule('crm'))
		{
			throw new RestException('CRM module is not installed.', 'CRM_NOT_INSTALLED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!isset($params['SESSION_ID']))
		{
			throw new RestException('You need to specify session id', 'SESSION_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (
			!isset($params['CRM_FORM']['ID'], $params['CRM_FORM']['CODE'], $params['CRM_FORM']['SEC'], $params['CRM_FORM']['NAME'])
		)
		{
			throw new RestException('You need to specify CRM-form details', 'FORM_INFO_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$sessionData = SessionTable::getByIdPerformance($params['SESSION_ID'])->fetch();
		if (!$sessionData)
		{
			throw new RestException('Error getting session info', 'NO_SESSION_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}
		$session = new Session();
		$session->load([
			'USER_CODE' => $sessionData['USER_CODE'],
			'SKIP_CREATE' => 'Y'
		]);

		if (!Im\Chat::isUserInChat($session->getData('CHAT_ID')))
		{
			throw new RestException('You do not have access to the specified dialog', 'ACCESS_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$formLink = WebForm\Script::getPublicUrl([
			'ID' => $params['CRM_FORM']['ID'],
			'CODE' => $params['CRM_FORM']['CODE'],
			'SECURITY_CODE' => $params['CRM_FORM']['SEC']
		]);
		$formLinkWithParams = $formLink;

		// for other connectors we send public link, need to attach properties and crm bindings to the link
		if ($session->getData('SOURCE') !== Connector::TYPE_LIVECHAT)
		{
			$operatorChat = $session->getChat();
			$crmBindings = $operatorChat->getFieldData(Chat::FIELD_CRM);

			$signedData = new WebForm\Embed\Sign();
			$signedData->setProperty('eventNamePostfix', FormHandler::EVENT_POSTFIX);
			$userCode = FormHandler::encodeConnectorName($session->getData('USER_CODE'));
			$signedData->setProperty('openlinesCode', $userCode);

			foreach ($crmBindings as $bindingType => $bindingId)
			{
				if ($bindingId > 0)
				{
					$signedData->addEntity(\CCrmOwnerType::ResolveId($bindingType), $bindingId);
				}
			}

			$uri = new Uri($formLink);
			$signedData->appendUriParameter($uri);

			$urlManager = UrlManager::getInstance();
			$host = $urlManager->getHostUrl();
			$formLinkWithParams = $host . \CBXShortUri::GetShortUri($uri->getLocator());
		}

		return \Bitrix\ImOpenlines\Im::addMessage([
			'TO_CHAT_ID' => $session->getData('CHAT_ID'),
			'MESSAGE' => FormHandler::buildSentFormMessageForClient($formLinkWithParams),
			'AUTHOR_ID' => CurrentUser::get()->getId(),
			'FROM_USER_ID' => CurrentUser::get()->getId(),
			'IMPORTANT_CONNECTOR' => 'Y',
			'PARAMS' => [
				'COMPONENT_ID' => FormHandler::FORM_COMPONENT_NAME,
				'CRM_FORM_ID' => $params['CRM_FORM']['ID'],
				'CRM_FORM_SEC' => $params['CRM_FORM']['SEC'],
				'CRM_FORM_FILLED' => 'N',
			]
		]);
	}

	public static function dialogUserDepersonalization($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		if ($server->getAuthType() == SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method not allowed by session authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Loader::includeModule('im'))
		{
			throw new RestException('Messenger is not installed.', 'IM_NOT_INSTALLED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		global $USER;
		$userId = $USER->GetID();

		if (!(
			$USER->IsAdmin()
			|| (
				Loader::includeModule('bitrix24')
				&& \CBitrix24::IsPortalAdmin($userId)
			)
		))
		{
			throw new RestException('You don\'t have access to this method', 'ACCESS_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!isset($params['USER_CODE']) || empty($params['USER_CODE']))
		{
			throw new RestException('User code is not specified', 'USER_CODE_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$guestUserId = Common::getUserIdByCode($params['USER_CODE']);
		if (!$guestUserId)
		{
			throw new RestException('The specified user is not a client of openlines module', 'USER_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return Common::depersonalizationLinesUser($guestUserId);
	}

	public static function operatorAnswer($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new Operator($arParams['CHAT_ID']);
		$result = $control->answer();
		if (!$result)
		{
			throw new RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function operatorSkip($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new Operator($arParams['CHAT_ID']);
		$result = $control->skip();
		if (!$result)
		{
			throw new RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}


		return true;
	}

	public static function operatorSpam($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new Operator($arParams['CHAT_ID']);
		$result = $control->markSpam();
		if (!$result)
		{
			throw new RestException($control->getError()->msg, $control->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return bool
	 * @throws RestException
	 */
	public static function operatorFinish($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$control = new Operator($arParams['CHAT_ID']);
		$result = $control->closeDialog();
		if (!$result->isSuccess())
		{
			$errors = $result->getErrors();
			$error = current($errors);
			throw new RestException($error->getMessage(), $error->getCode(), \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function operatorTransfer($arParams, $n, \CRestServer $server)
	{
		$arParams['CHAT_ID'] = (int)$arParams['CHAT_ID'];
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new RestException('Chat ID can\'t be empty', 'CHAT_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
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
			$arParams['USER_ID'] = (int)$arParams['USER_ID'];

			if ($arParams['USER_ID'] <= 0)
			{
				throw new RestException('User ID can\'t be empty', 'USER_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = $arParams['USER_ID'];
		}
		else if (isset($arParams['QUEUE_ID']))
		{
			$arParams['QUEUE_ID'] = (int)$arParams['QUEUE_ID'];

			if ($arParams['QUEUE_ID'] <= 0)
			{
				throw new RestException('QUEUE ID can\'t be empty', 'QUEUE_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = 'queue'.$arParams['QUEUE_ID'];
		}
		else
		{
			throw new RestException('Queue ID or User ID can\'t be empty', 'TRANSFER_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$control = new Operator($arParams['CHAT_ID']);
		$result = $control->transfer([
			'TRANSFER_ID' => $transferId,
		]);
		if (!$result)
		{
			throw new RestException('You can not redirect to this operator', 'OPERATOR_WRONG', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function sessionIntercept($arParams, $n, \CRestServer $server)
	{
		$arParams['CHAT_ID'] = (int)$arParams['CHAT_ID'];
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new RestException('Chat ID can\'t be empty', 'CHAT_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$control = new Operator($arParams['CHAT_ID']);
		$result = $control->interceptSession();

		if (!$result)
		{
			throw new RestException('You can not redirect to this operator', 'OPERATOR_WRONG', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}


	public static function botSessionOperator($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method not allowed by session authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = (int)$arParams['CHAT_ID'];
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new RestException('Chat ID can\'t be empty', 'CHAT_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new Chat($arParams['CHAT_ID']);
		$result = $chat->endBotSession();
		if (!$result)
		{
			throw new RestException('Operator is not a bot', 'WRONG_CHAT', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botSessionSendAutoMessage($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method not allowed by session authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = (int)$arParams['CHAT_ID'];
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new RestException('Chat ID can\'t be empty', 'CHAT_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new Chat($arParams['CHAT_ID']);
		$arParams['MESSAGE'] = !empty($arParams['MESSAGE']) ? $arParams['MESSAGE'] : '';
		$chat->sendAutoMessage($arParams['NAME'], $arParams['MESSAGE']);

		return true;
	}

	public static function botSessionTransfer($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method not allowed by session authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams['CHAT_ID'] = (int)$arParams['CHAT_ID'];

		$arParams['LEAVE'] = isset($arParams['LEAVE']) && $arParams['LEAVE'] == 'Y'? 'Y': 'N';

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new RestException('Chat ID can\'t be empty', 'CHAT_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
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
			$arParams['USER_ID'] = (int)$arParams['USER_ID'];

			if ($arParams['USER_ID'] <= 0)
			{
				throw new RestException('User ID can\'t be empty', 'USER_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = $arParams['USER_ID'];
		}
		else if (isset($arParams['QUEUE_ID']))
		{
			$arParams['QUEUE_ID'] = (int)$arParams['QUEUE_ID'];

			if ($arParams['QUEUE_ID'] <= 0)
			{
				throw new RestException('QUEUE ID can\'t be empty', 'QUEUE_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
			}

			$transferId = 'queue'.$arParams['QUEUE_ID'];
		}
		else
		{
			throw new RestException('Queue ID or User ID can\'t be empty', 'TRANSFER_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$bots = Im\Bot::getListCache();
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
			throw new RestException('Bot not found', 'BOT_ID_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new Chat($arParams['CHAT_ID']);
		$result = $chat->transfer(Array(
			'FROM' => $botId,
			'TO' => $transferId,
			'MODE' => Chat::TRANSFER_MODE_BOT,
			'LEAVE' => $arParams['LEAVE']
		));
		if (!$result)
		{
			throw new RestException('You can not redirect to this operator', 'OPERATOR_WRONG', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botSessionFinish($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method not allowed by session authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = (int)$arParams['CHAT_ID'];
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new RestException('Chat ID can\'t be empty', 'CHAT_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$bots = Im\Bot::getListCache();
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
			throw new RestException('Bot not found', 'BOT_ID_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new Chat($arParams['CHAT_ID']);
		$chat->answer($botId);
		$chat->finish();

		return true;
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return array
	 */
	public static function configGetPath($arParams, $n, \CRestServer $server): array
	{
		return [
			'SERVER_ADDRESS' => Common::getServerAddress(),
			'PUBLIC_PATH' => Common::getContactCenterPublicFolder()
		];
	}

	public static function networkJoin($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);
		if (!isset($arParams['CODE']) || mb_strlen($arParams['CODE']) != 32)
		{
			throw new RestException('You entered an invalid code', 'CODE', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Loader::includeModule('imbot'))
		{
			throw new RestException('Module IMBOT is not installed', 'IMBOT_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (Network::isFdcCode($arParams['CODE']))
		{
			throw new RestException('Line not found', 'NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = Network::join($arParams['CODE']);
		if (!$result)
		{
			throw new RestException(Network::getError()->msg, Network::getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return $result;
	}

	public static function networkMessageAdd($arParams, $n, \CRestServer $server)
	{
		if ($server->getAuthType() == SessionAuth\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method not allowed by session authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);
		if (!isset($arParams['CODE']) || mb_strlen($arParams['CODE']) != 32)
		{
			throw new RestException('You entered an invalid code', 'CODE', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Loader::includeModule('imbot'))
		{
			throw new RestException('Module IMBOT is not installed', 'IMBOT_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (Network::isFdcCode($arParams['CODE']))
		{
			throw new RestException('Line not found', 'NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$networkBot = null;

		$bots = Im\Bot::getListCache();
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
			throw new RestException('Line not found', 'NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$arMessageFields = Array();

		$arMessageFields['DIALOG_ID'] = (int)$arParams['USER_ID'];
		if (empty($arMessageFields['DIALOG_ID']))
		{
			throw new RestException('User ID can\'t be empty', 'USER_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$isBitrix24 = Loader::includeModule('bitrix24');
		if (
			$isBitrix24
			&& !\CBitrix24::IsNfrLicense()
			|| !$isBitrix24
			&& !defined('IMOPENLINES_NETWORK_LIMIT')
		)
		{
			$dateLimit = new DateTime();
			$dateLimit->add('-1 WEEK');

			$check = Model\RestNetworkLimitTable::getList([
				'filter' => [
					'=BOT_ID' => $networkBot['BOT_ID'],
					'=USER_ID' => $arMessageFields['DIALOG_ID'],
					'>DATE_CREATE' => $dateLimit
				]
			])->fetch();
			if ($check)
			{
				throw new RestException('You cant send more than one message per week to each user.', 'USER_MESSAGE_LIMIT', \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$arMessageFields['MESSAGE'] = trim($arParams['MESSAGE']);
		if ($arMessageFields['MESSAGE'] == '')
		{
			throw new RestException('Message can\'t be empty', 'MESSAGE_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
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
					throw new RestException('You have exceeded the maximum allowable size of attach', 'ATTACH_OVERSIZE', \CRestServer::STATUS_WRONG_REQUEST);
				}
			}
			else if ($arParams['ATTACH'])
			{
				throw new RestException('Incorrect attach params', 'ATTACH_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
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

			$keyboard = Im\Bot\Keyboard::getKeyboardByJson($keyboard);
			if ($keyboard)
			{
				$arMessageFields['KEYBOARD'] = $keyboard;
			}
			else
			{
				throw new RestException('Incorrect keyboard params', 'KEYBOARD_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == 'N')
		{
			$arMessageFields['URL_PREVIEW'] = 'N';
		}
		$arMessageFields['PARAMS']['IMOL_QUOTE_MSG'] = 'Y';

		$id = Im\Bot::addMessage(array('BOT_ID' => $networkBot['BOT_ID']), $arMessageFields);
		if (!$id)
		{
			throw new RestException('Message isn\'t added', 'WRONG_REQUEST', \CRestServer::STATUS_WRONG_REQUEST);
		}

		Model\RestNetworkLimitTable::add(Array('BOT_ID' => $networkBot['BOT_ID'], 'USER_ID' => $arMessageFields['DIALOG_ID']));

		return true;
	}

	public static function widgetUserRegister($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = (int)$params['CONFIG_ID'];
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config id is not specified.', 'CONFIG_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$config = Model\ConfigTable::getById($params['CONFIG_ID'])->fetch();
		if (!$config)
		{
			throw new RestException('Config is not found.', 'CONFIG_NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
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

		$userDataFields = [
			'NAME' => $params['NAME'],
			'LAST_NAME' => $params['LAST_NAME'],
			'AVATAR' => $params['AVATAR'],
			'EMAIL' => $params['EMAIL'],
			'PERSONAL_WWW' => $params['WWW'],
			'PERSONAL_GENDER' => $params['GENDER'],
			'WORK_POSITION' => $params['POSITION'],
			'USER_HASH' => $params['USER_HASH'],
		];
		$userData = Widget\User::register($userDataFields);
		if (!$userData)
		{
			throw new RestException(
				Widget\User::getError()->msg,
				Widget\User::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$dialogData = Widget\Dialog::register($userData['ID'], $config['ID']);
		if (!$dialogData)
		{
			throw new RestException(
				Widget\Dialog::getError()->msg,
				Widget\Dialog::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		Widget\Auth::authorizeById($userData['ID'], true, true);

		$result = [
			'id' => (int)$userData['ID'],
			'hash' => $userData['HASH'],
			'chatId' => (int)$dialogData['CHAT_ID'],
			'dialogId' => 'chat'.$dialogData['CHAT_ID'],
			'userConsent' => false,
		];

		$_SESSION['LIVECHAT']['REGISTER'] = $result;

		self::checkWelcomeFormNeeded($params, (int)$dialogData['CHAT_ID']);

		Widget\Cache::set($userData['ID'], [
			'TRACE_DATA' => (string)$params['TRACE_DATA'],
	 		'CUSTOM_DATA' => (string)$params['CUSTOM_DATA'],
		]);

		return $result;
	}

	public static function widgetChatCreate($params, $n, \CRestServer $server)
	{
		// method disabled because of beta status
		throw new RestException('Method is unavailable', 'WRONG_REQUEST', \CRestServer::STATUS_FORBIDDEN);

		if ($server->getAuthType() !== Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = (int)$params['CONFIG_ID'];
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config id is not specified.', 'CONFIG_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$config = Model\ConfigTable::getById($params['CONFIG_ID'])->fetch();
		if (!$config)
		{
			throw new RestException('Config is not found.', 'CONFIG_NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
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

		global $USER;
		$userData = [];
		if ($USER->IsAuthorized())
		{
			$userData = [
				'ID' => $USER->getId(),
				'HASH' => $params['USER_HASH']
			];
		}

		if (count($userData) === 0)
		{
			throw new RestException(
				Widget\User::getError()->msg,
				Widget\User::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		$dialogData = Widget\Dialog::register($userData['ID'], $config['ID']);
		if (!$dialogData)
		{
			throw new RestException(
				Widget\Dialog::getError()->msg,
				Widget\Dialog::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		Widget\Auth::authorizeById($userData['ID'], true, true);

		$result = [
			'id' => (int)$userData['ID'],
			'hash' => $userData['HASH'],
			'chatId' => (int)$dialogData['CHAT_ID'],
			'dialogId' => 'chat'.$dialogData['CHAT_ID'],
			'userConsent' => false,
		];

		$_SESSION['LIVECHAT']['REGISTER'] = $result;

		self::checkWelcomeFormNeeded($params, (int)$dialogData['CHAT_ID']);

		Widget\Cache::set($userData['ID'], [
			'TRACE_DATA' => (string)$params['TRACE_DATA'],
			'CUSTOM_DATA' => (string)$params['CUSTOM_DATA'],
		]);

		return $result;
	}

	public static function widgetUserConsentApply($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new RestException('Access for this method allowed only for authorized users.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = (int)$params['CONFIG_ID'];
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config id is not specified.', 'CONFIG_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$config = Model\ConfigTable::getById($params['CONFIG_ID'])->fetch();
		if (!$config)
		{
			throw new RestException('Config is not found.', 'CONFIG_NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Loader::includeModule('im'))
		{
			throw new RestException('Messenger is not installed.', 'IM_NOT_INSTALLED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($config['AGREEMENT_MESSAGE'] != 'Y')
		{
			return false;
		}

		$chat = Im\Model\ChatTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE' => 'LIVECHAT',
				'=ENTITY_ID' => $config['ID'].'|'.$USER->GetID()
			],
			'limit' => 1
		])->fetch();
		if (!$chat)
		{
			throw new RestException('Chat is not found.', 'CHAT_NOT_FOUND', \CRestServer::STATUS_WRONG_REQUEST);
		}

		Consent::addByContext(
			(int)$config['AGREEMENT_ID'],
			'imopenlines/livechat',
			$chat['ID'],
			['URL' => trim($params['CONSENT_URL'])]
		);

		return true;
	}

	public static function widgetVoteSend($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['SESSION_ID'] = (int)$params['SESSION_ID'];
		if ($params['SESSION_ID'] <= 0)
		{
			throw new RestException('Session id is not specified.', 'SESSION_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$action = mb_strtolower($params['ACTION']);

		Session::voteAsUser($params['SESSION_ID'], $action);

		return true;
	}

	public static function widgetActionSend($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['MESSAGE_ID']))
		{
			$arParams['ID'] = $arParams['MESSAGE_ID'];
		}

		$arParams['ID'] = (int)$arParams['ID'];
		if ($arParams['ID'] <= 0)
		{
			throw new RestException('Message ID can\'t be empty', 'MESSAGE_ID_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = Widget\Action::execute($arParams['ID'], $arParams['ACTION_VALUE']);
		if ($result === false)
		{
			throw new RestException('Incorrect params', 'PARAMS_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function widgetCrmBindingsGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		if (!Loader::includeModule('crm'))
		{
			throw new RestException('CRM module is not installed.', 'NO_CRM', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		if (!is_string($params['OPENLINES_CODE']) || $params['OPENLINES_CODE' === ''])
		{
			throw new RestException('Wrong imopenlines code.', 'WRONG_IMOL_CODE', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$parsedOpenlinesCode = Chat::parseLinesChatEntityId($params['OPENLINES_CODE']);
		$configId = $parsedOpenlinesCode['lineId'];
		$clientChatId = $parsedOpenlinesCode['connectorChatId'];
		$userId = $parsedOpenlinesCode['connectorUserId'];

		if (!$configId || !$clientChatId || !$userId)
		{
			throw new RestException('Wrong imopenlines code.', 'WRONG_IMOL_CODE', \CRestServer::STATUS_WRONG_REQUEST);
		}

		// get operator chat from IMOL code
		$operatorChat = new Chat();
		$chatLoadResult = $operatorChat->load(['USER_CODE' => $params['OPENLINES_CODE'], 'ONLY_LOAD' => 'Y']);
		if (!$chatLoadResult)
		{
			throw new RestException('Error loading chat', 'CHAT_LOAD_ERROR', \CRestServer::STATUS_WRONG_REQUEST);
		}

		// check if current user is in chat
		$isUserInChat = Im\Chat::isUserInChat($operatorChat->getData('ID'));
		if (!$isUserInChat)
		{
			throw new RestException('You dont have access to this chat', 'ACCESS_DENIED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		// get crm bindings from field data
		$crmBindings = $operatorChat->getFieldData(Chat::FIELD_CRM);

		// sign bindings if they exist
		$signedData = (new WebForm\Embed\Sign);
		$bindingsExist = false;
		foreach ($crmBindings as $bindingType => $bindingId)
		{
			if ($bindingId > 0)
			{
				$bindingsExist = true;
				$signedData->addEntity(\CCrmOwnerType::ResolveId($bindingType), $bindingId);
			}
		}

		if (!$bindingsExist)
		{
			return '';
		}

		return $signedData->pack();
	}

	public static function widgetUserGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new RestException('Access for this method allowed only for authorized users.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$result = Widget\User::get($USER->GetID());

		return self::objectEncode($result);
	}

	/**
	 * 	Check if welcome form needed (if we have name/lastName and email - we dont show form)
	 * @param array $params
	 * @param int $chatId
	 *
	 * @return void
	 */
	private static function checkWelcomeFormNeeded(array $params, int $chatId): void
	{
		if (($params['NAME'] !== '' || $params['LAST_NAME'] !== '') && $params['EMAIL'] !== '')
		{
			$clientChat = new Chat($chatId);
			$clientChat->updateFieldData([Chat::FIELD_LIVECHAT => ['WELCOME_FORM_NEEDED' => 'N']]);
		}
	}

	/**
	 * @param array $params
	 * @param int $n
	 * @param \CRestServer $server
	 * @return array
	 * @throws RestException
	 */
	public static function widgetOperatorGet($params, $n, \CRestServer $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);

		$type = \CPullChannel::TYPE_PRIVATE;
		if ($params['APPLICATION'] == 'Y')
		{
			$clientId = $server->getClientId();
			if (!$clientId)
			{
				throw new RestException('Get application public channel available only for application authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_WRONG_REQUEST);
			}
			$type = $clientId;
		}

		$users = [];
		if (is_string($params['USERS']))
		{
			$params['USERS'] = \CUtil::JsObjectToPhp($params['USERS']);
		}
		if (is_array($params['USERS']))
		{
			foreach ($params['USERS'] as $userId)
			{
				$userId = (int)$userId;
				if ($userId > 0)
				{
					$users[$userId] = $userId;
				}
			}
		}

		if (empty($users))
		{
			throw new RestException('A wrong format for the USERS field is passed', 'INVALID_FORMAT', \CRestServer::STATUS_WRONG_REQUEST);
		}

		global $USER;
		$operators = Model\SessionTable::getList([
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
				throw new RestException('Wrong operator ID');
			}
		}

		$configParams = [];
		$configParams['TYPE'] = $type;
		$configParams['USERS'] = $users;
		$configParams['JSON'] = true;

		$config = \Bitrix\Pull\Channel::getPublicIds($configParams);
		if (!$config)
		{
			throw new RestException('Push & Pull server is not configured', 'SERVER_ERROR', \CRestServer::STATUS_INTERNAL);
		}

		return $config;
	}

	/**
	 * @param $params
	 * @param $n
	 * @param \CRestServer $server
	 * @return array
	 * @throws RestException
	 */
	public static function widgetDialogGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new RestException('Access for this method allowed only for authorized users.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = (int)$params['CONFIG_ID'];
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config id is not specified.', 'WRONG_REQUEST', \CRestServer::STATUS_WRONG_REQUEST);
		}

		Widget\Cache::set($USER->GetId(), [
	 		'TRACE_DATA' => (string)$params['TRACE_DATA'],
	 		'CUSTOM_DATA' => (string)$params['CUSTOM_DATA'],
		]);

		$chatId = isset($params['CHAT_ID']) ? (int)$params['CHAT_ID'] : 0;
		$result = Widget\Dialog::get($USER->GetID(), $params['CONFIG_ID'], $chatId);
		if (!$result)
		{
			throw new RestException(
				Widget\Dialog::getError()->msg,
				Widget\Dialog::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		return self::objectEncode($result);
	}

	public static function widgetDialogList($params, $offset = 0, \CRestServer $server)
	{
		// method disabled because of beta status
		throw new RestException('Method is unavailable', 'WRONG_REQUEST', \CRestServer::STATUS_FORBIDDEN);

		if ($server->getAuthType() !== Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			throw new RestException('Access for this method allowed only for authorized users.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$params['CONFIG_ID'] = (int)$params['CONFIG_ID'];
		if ($params['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config id is not specified.', 'WRONG_REQUEST', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($offset > 0)
		{
			$params['OFFSET'] = $offset;
		}

		$result = Widget\Dialog::getList($USER->GetID(), $params);
		if (!is_array($result))
		{
			throw new RestException(
				Widget\Dialog::getError()->msg,
				Widget\Dialog::getError()->code,
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
	 * @throws RestException
	 */
	public static function widgetConfigGet($params, $n, \CRestServer $server)
	{
		if ($server->getAuthType() != Widget\Auth::AUTH_TYPE)
		{
			throw new RestException('Access for this method allowed only by livechat authorization.', 'WRONG_AUTH_TYPE', \CRestServer::STATUS_FORBIDDEN);
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$config = Widget\Config::getByCode($params['CODE']);
		if (!$config)
		{
			throw new RestException(
				Widget\Config::getError()->msg,
				Widget\Config::getError()->code,
				\CRestServer::STATUS_WRONG_REQUEST
			);
		}

		shuffle($config['OPERATORS']);
		$config['OPERATORS'] = array_slice($config['OPERATORS'], 0, 3);

		//get security code and statuses texts for welcome CRM-form
		if ($config['CRM_FORMS_SETTINGS']['USE_WELCOME_FORM'] && Loader::includeModule('crm'))
		{
			$welcomeFormId = (int)$config['CRM_FORMS_SETTINGS']['WELCOME_FORM_ID'];
			$welcomeForm = new WebForm\Form($welcomeFormId);
			if ($welcomeForm && $welcomeForm->isActive())
			{
				$config['CRM_FORMS_SETTINGS']['WELCOME_FORM_SEC'] = $welcomeForm->get()['SECURITY_CODE'];
			}

			$config['CRM_FORMS_SETTINGS']['SUCCESS_TEXT'] = $welcomeForm->get()['RESULT_SUCCESS_TEXT'];
			$config['CRM_FORMS_SETTINGS']['ERROR_TEXT'] = $welcomeForm->get()['RESULT_FAILURE_TEXT'];
		}

		$result = self::objectEncode($config);

		$coreMessages = \CJSCore::GetCoreMessages();
		$result['serverVariables'] = [
			'FORMAT_DATE' => $coreMessages['FORMAT_DATE'],
			'FORMAT_DATETIME' => $coreMessages['FORMAT_DATETIME'],
			'AMPM_MODE' => IsAmPmMode(true),
			'UTF_MODE' => Main\Application::getInstance()->isUtfMode() ? 'Y' : 'N',
			'isCloud' => \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'),
		];

		return $result;
	}

	public static function configGet($arParams, $n, \CRestServer $server)
	{
		$arParams['CONFIG_ID'] = (int)$arParams['CONFIG_ID'];

		if ($arParams['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config ID can\'t be empty', 'CONFIG_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
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

		if (isset($arParams['PARAMS']['select']) && !is_array($arParams['PARAMS']['select']))
		{
			throw new RestException('A wrong format for the PARAMS field \'select\' is passed', 'INVALID_FORMAT', \CRestServer::STATUS_WRONG_REQUEST);
		}
		if (isset($arParams['PARAMS']['order']) && !is_array($arParams['PARAMS']['order']))
		{
			throw new RestException('A wrong format for the PARAMS field \'order\' is passed', 'INVALID_FORMAT', \CRestServer::STATUS_WRONG_REQUEST);
		}
		if (isset($arParams['PARAMS']['filter']) && !is_array($arParams['PARAMS']['filter']))
		{
			throw new RestException('A wrong format for the PARAMS field \'filter\' is passed', 'INVALID_FORMAT', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return $config->getList($arParams['PARAMS'], $arParams['OPTIONS']);
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return bool
	 * @throws RestException
	 */
	public static function configUpdate($arParams, $n, \CRestServer $server)
	{
		$result = false;

		$arParams['CONFIG_ID'] = (int)$arParams['CONFIG_ID'];

		if ($arParams['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config ID can\'t be empty', 'CONFIG_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Config::canEditLine($arParams['CONFIG_ID']))
		{
			throw new RestException('Permission denied', 'CONFIG_WRONG_USER_PERMISSION', \CRestServer::STATUS_FORBIDDEN);
		}

		$arParams['PARAMS'] = !empty($arParams['PARAMS']) && is_array($arParams['PARAMS']) ? $arParams['PARAMS'] : [];
		$config = new Config();

		$resultUpdate = $config->update($arParams['CONFIG_ID'], $arParams['PARAMS']);
		if($resultUpdate->isSuccess())
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $arParams
	 * @param $n
	 * @param \CRestServer $server
	 * @return array|bool|int
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
	 * @throws RestException
	 */
	public static function configDelete($arParams, $n, \CRestServer $server)
	{
		$arParams['CONFIG_ID'] = (int)$arParams['CONFIG_ID'];

		if ($arParams['CONFIG_ID'] <= 0)
		{
			throw new RestException('Config ID can\'t be empty', 'CONFIG_ID_EMPTY', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Config::canEditLine($arParams['CONFIG_ID']))
		{
			throw new RestException('Permission denied', 'CONFIG_WRONG_USER_PERMISSION', \CRestServer::STATUS_FORBIDDEN);
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
			throw new RestException('Empty CRM data', 'CRM_CHAT_EMPTY_CRM_DATA', \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!Loader::includeModule('im'))
		{
			throw new RestException('Messenger is not installed.', 'IM_NOT_INSTALLED', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chatId = Crm\Common::getLastChatIdByCrmEntity($arParams['CRM_ENTITY_TYPE'], $arParams['CRM_ENTITY']);

		if ($chatId > 0)
		{
			if (!Config::canJoin($chatId, $arParams['CRM_ENTITY_TYPE'], $arParams['CRM_ENTITY']))
			{
				throw new RestException('You don\'t have access to join user to chat', 'CHAT_JOIN_PERMISSION_DENIED', \CRestServer::STATUS_FORBIDDEN);
			}

			$arParams['USER_ID'] = (int)$arParams['USER_ID'];
			if ($arParams['USER_ID'] <= 0)
			{
				throw new RestException('Empty User ID', 'CRM_CHAT_EMPTY_USER', \CRestServer::STATUS_WRONG_REQUEST);
			}

			$user = Im\User::getInstance($arParams['USER_ID']);

			if (!$user->isExists() || !$user->isActive())
			{
				throw new RestException('User not active', 'CRM_CHAT_USER_NOT_ACTIVE', \CRestServer::STATUS_WRONG_REQUEST);
			}

			$CIMChat = new \CIMChat(0);
			$result = $CIMChat->AddUser($chatId, $arParams['USER_ID']);

			if (!$result)
			{
				throw new RestException('You don\'t have access or user already member in chat', 'WRONG_REQUEST', \CRestServer::STATUS_WRONG_REQUEST);
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
			throw new RestException('Empty CRM data', 'CRM_CHAT_EMPTY_CRM_DATA', \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chatId = Crm\Common::getLastChatIdByCrmEntity($arParams['CRM_ENTITY_TYPE'], $arParams['CRM_ENTITY']);

		if ($chatId === 0)
		{
			throw new RestException('Could not find CRM entity', 'CRM_CHAT_EMPTY_CRM_DATA', \CRestServer::STATUS_WRONG_REQUEST);
		}

		return $chatId;
	}

	private static function getChatId(array $params)
	{
		if (!Loader::includeModule('im'))
		{
			return null;
		}

		if (isset($params['CHAT_ID']))
		{
			return (int)$params['CHAT_ID'];
		}

		if (isset($params['DIALOG_ID']))
		{
			if (Im\Common::isChatId($params['DIALOG_ID']))
			{
				return Im\Dialog::getChatId($params['DIALOG_ID']);
			}

			return null;
		}

		if (isset($params['SESSION_ID']))
		{
			return Chat::getChatIdBySession((int)$params['SESSION_ID']);
		}

		if (isset($params['USER_CODE']))
		{
			if (mb_strpos($params['USER_CODE'], 'imol|') === 0)
			{
				$params['USER_CODE'] = mb_substr($params['USER_CODE'], 5);
			}

			return Chat::getChatIdByUserCode($params['USER_CODE']);
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
				else if ($value instanceof DateTime)
				{
					$value = date('c', $value->getTimestamp());
				}
				else if (is_string($key) && in_array($key, $options['IMAGE_FIELD']) && is_string($value) && $value && mb_strpos($value, 'http') !== 0)
				{
					$value = Common::getServerAddress().$value;
				}

				$key = str_replace('_', '', lcfirst(ucwords(mb_strtolower($key), '_')));

				$result[$key] = $value;
			}
			$data = $result;
		}

		return $data;
	}
}

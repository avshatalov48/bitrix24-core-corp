<?php
namespace Bitrix\ImOpenLines\AutomaticAction;

use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Connector;
use Bitrix\ImOpenLines\Im;
use	Bitrix\ImOpenLines\Session;
use Bitrix\ImOpenLines\Widget\FormHandler;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Welcome
 * @package Bitrix\ImOpenLines\AutomaticAction
 */
class Welcome
{
	/** @var Session */
	protected $sessionManager = null;
	protected $session = [];
	protected $config = [];
	/** @var Chat */
	protected $clientChat = null;
	/** @var Chat */
	protected $chat = null;

	/**
	 * Welcome constructor.
	 * @param Session $session
	 */
	public function __construct($session)
	{
		$this->sessionManager = $session;
		$this->session = $session->getData();
		$this->config = $session->getConfig();
		$this->chat = $session->getChat();
	}

	/**
	 * Automatic processing on incoming message.
	 *
	 * @return bool|int
	 */
	public function automaticAddMessage()
	{
		$result = false;
		$isNewSession = (int)$this->session['MESSAGE_COUNT'] === 0;
		$sendWelcomeEachSession = $this->config['SEND_WELCOME_EACH_SESSION'] === 'Y';
		$isInboundCall = $this->session['MODE'] === Session::MODE_INPUT;
		$isBotAnswered = $this->session['JOIN_BOT'] ?? false;

		$isAllowedAutomaticMessage = $isInboundCall && !$isBotAnswered;
		$isNeededAutomaticMessage = $this->chat->isNowCreated()
			|| $this->isTextAfterWelcomeFormIsNeeded()
			|| ($isNewSession && $sendWelcomeEachSession);

		if ($isAllowedAutomaticMessage && $isNeededAutomaticMessage)
		{
			$result = $this->sendMessage();
		}

		return $result;
	}

	/**
	 * Automatic processing of outgoing message.
	 *
	 * @return bool
	 */
	public function automaticSendMessage()
	{
		$result = true;

		return $result;
	}

	/**
	 * Send a welcome message.
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendMessage()
	{
		$result = false;

		if (
			$this->config['WELCOME_MESSAGE'] == 'Y' &&
			isset($this->config['WELCOME_MESSAGE_TEXT']) &&
			$this->session['SOURCE'] != 'network' &&
			$this->sessionManager->isEnableSendSystemMessage()
		)
		{
			$result = Im::addAutomaticSystemMessage(
				$this->session['CHAT_ID'],
				$this->config['WELCOME_MESSAGE_TEXT']
			);

			if ($this->session['SOURCE'] === Connector::TYPE_LIVECHAT && $this->clientChat)
			{
				$this->clientChat->updateFieldData([Chat::FIELD_LIVECHAT => ['WELCOME_TEXT_SENT' => 'Y']]);
			}
		}

		if ($this->isWelcomeFormNeeded())
		{
			$this->sendWelcomeForm();
		}

		return $result;
	}

	public function sendWelcomeForm(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		$welcomeForm = new \Bitrix\Crm\WebForm\Form($this->config['WELCOME_FORM_ID']);
		if (!$welcomeForm || !$welcomeForm->isActive())
		{
			return false;
		}

		$welcomeFormLink = \Bitrix\Crm\WebForm\Script::getPublicUrl([
			'ID' => $this->config['WELCOME_FORM_ID'],
			'CODE' => $welcomeForm->get()['CODE'],
			'SECURITY_CODE' => $welcomeForm->get()['SECURITY_CODE']
		]);
		$welcomeFormName = $welcomeForm->get()['NAME'];

		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
		$attach->AddLink([
			"NAME" => $welcomeFormLink,
			"LINK" => $welcomeFormLink
		]);

		Im::addMessage([
			"TO_CHAT_ID" => $this->session['CHAT_ID'],
			"MESSAGE" => FormHandler::buildSentFormMessageForOperator($welcomeFormName),
			"ATTACH" => $attach,
			"SYSTEM" => 'Y',
			"IMPORTANT_CONNECTOR" => 'Y',
			'NO_SESSION_OL' => 'Y',
			"PARAMS" => [
				"COMPONENT_ID" => FormHandler::FORM_COMPONENT_NAME,
				"IS_WELCOME_FORM" => 'Y',
				"CRM_FORM_ID" => $this->config['WELCOME_FORM_ID'],
				"CRM_FORM_SEC" => $welcomeForm->get()['SECURITY_CODE'],
				"CRM_FORM_FILLED" => 'N'
			]
		]);

		return true;
	}

	private function isWelcomeFormNeeded(): bool
	{
		if (
			$this->session['SOURCE'] !== Connector::TYPE_LIVECHAT ||
			$this->config['USE_WELCOME_FORM'] !== 'Y' ||
			$this->config['WELCOME_FORM_DELAY'] !== 'Y')
		{
			return false;
		}

		if (!$this->clientChat)
		{
			$clientChatId = Chat::parseLinesChatEntityId($this->session['USER_CODE'])['connectorChatId'];
			$this->clientChat = new Chat($clientChatId);
		}
		$isFormNeeded =
			$this->clientChat->getFieldData(Chat::FIELD_LIVECHAT)['WELCOME_FORM_NEEDED'] === 'Y'
			&& $this->chat->isNowCreated();

		return $isFormNeeded;
	}

	private function isTextAfterWelcomeFormIsNeeded(): bool
	{
		if ($this->session['SOURCE'] !== Connector::TYPE_LIVECHAT)
		{
			return false;
		}

		$useWelcomeForm = $this->config['USE_WELCOME_FORM'] === 'Y';
		$welcomeFormDelay = $this->config['WELCOME_FORM_DELAY'] === 'Y';

		// if we show welcome form before dialog start
		if ($useWelcomeForm && !$welcomeFormDelay)
		{
			$clientChatId = Chat::parseLinesChatEntityId($this->session['USER_CODE'])['connectorChatId'];
			$this->clientChat = new Chat($clientChatId);
			$livechatData = $this->clientChat->getFieldData(Chat::FIELD_LIVECHAT);

			// if welcome form was filled and welcome text wasn't sent in this chat - we need to send it
			return $livechatData['WELCOME_FORM_FILLED'] === 'Y' && $livechatData['WELCOME_TEXT_SENT'] === 'N';
		}

		return false;
	}
}

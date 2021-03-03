<?php
namespace Bitrix\ImOpenLines\AutomaticAction;

use \Bitrix\ImOpenLines\Im,
	\Bitrix\ImOpenLines\Session;

/**
 * Class Welcome
 * @package Bitrix\ImOpenLines\AutomaticAction
 */
class Welcome
{
	/**Session*/
	protected $sessionManager = null;
	protected $session = [];
	protected $config = [];
	/**Chat*/
	protected $chat = null;

	/**
	 * Welcome constructor.
	 * @param Session $session
	 */
	function __construct($session)
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function automaticAddMessage()
	{
		$result = false;

		if (
			$this->session['MODE'] == Session::MODE_INPUT &&
			$this->session['JOIN_BOT'] == false &&
			$this->chat->isNowCreated()
		)
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
			$result = Im::addMessage([
				"TO_CHAT_ID" => $this->session['CHAT_ID'],
				"MESSAGE" => $this->config['WELCOME_MESSAGE_TEXT'],
				"SYSTEM" => 'Y',
				"IMPORTANT_CONNECTOR" => 'Y',
				"PARAMS" => [
					"CLASS" => "bx-messenger-content-item-ol-output",
				]
			]);
		}

		return $result;
	}
}
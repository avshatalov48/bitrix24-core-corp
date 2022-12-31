<?php
namespace Bitrix\ImOpenLines;

/**
 * Class AutomaticAction
 * @package Bitrix\ImOpenLines
 */
class AutomaticAction
{
	/** @var Session*/
	protected $sessionManager = null;
	//protected $session = [];
	//protected $config = [];
	///**Chat*/
	//protected $chat = null;

	/**
	 * AutomaticAction constructor.
	 * @param Session $session
	 */
	public function __construct($session)
	{
		$this->sessionManager = $session;
		//$this->session = $session->getData();
		//$this->config = $session->getConfig();
		//$this->chat = $session->getChat();
	}

	/**
	 * The automatic action for an incoming message from an external source
	 *
	 * @param $messageId
	 * @param bool $finish
	 * @param bool $vote
	 * @return bool
	 */
	public function automaticAddMessage($messageId, $finish = false, $vote = false)
	{
		//Welcome
		(new AutomaticAction\Welcome($this->sessionManager))->automaticAddMessage();

		//Work Time
		(new AutomaticAction\WorkTime($this->sessionManager))->automaticAddMessage($finish, $vote);

		//Automatic action
		$this->sessionManager->execAutoAction([
			'MESSAGE_ID' => $messageId,
		]);

		return true;
	}

	/**
	 * Outbound message to an external channel.
	 *
	 * @param $messageId
	 * @return bool
	 */
	public function automaticSendMessage($messageId)
	{
		//Welcome
		(new AutomaticAction\Welcome($this->sessionManager))->automaticSendMessage();

		//Work Time
		(new AutomaticAction\WorkTime($this->sessionManager))->automaticSendMessage();

		//Automatic action
		$this->sessionManager->execAutoAction([
			'MESSAGE_ID' => $messageId
		]);

		return true;
	}
}
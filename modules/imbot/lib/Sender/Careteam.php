<?php

namespace Bitrix\ImBot\Sender;

use Bitrix\Main\Result;

class Careteam extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	public function sendKeyboardCommand(array $messageFields): Result
	{
		return $this->performRequest(
			'botcontroller.Careteam.sendKeyboardCommand',
			[
				'messageFields' => \Bitrix\Main\Web\Json::encode($messageFields),
			]
		);
	}

	public function sendMessage(array $messageFields): Result
	{
		return $this->performRequest(
			'botcontroller.Careteam.sendMessage',
			[
				'messageFields' => \Bitrix\Main\Web\Json::encode($messageFields),
			]
		);
	}
}

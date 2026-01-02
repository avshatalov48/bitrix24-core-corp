<?php

namespace Bitrix\ImBot\Sender;

use Bitrix\Main\Result;

class Onboardingbot extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	public function sendKeyboardCommand(array $messageFields): Result
	{
		return $this->performRequest(
			'botcontroller.Onboardingbot.sendKeyboardCommand',
			[
				'messageFields' => \Bitrix\Main\Web\Json::encode($messageFields),
			]
		);
	}

	public function sendMessage(array $messageFields): Result
	{
		return $this->performRequest(
			'botcontroller.Onboardingbot.sendMessage',
			[
				'messageFields' => \Bitrix\Main\Web\Json::encode($messageFields)
			]
		);
	}
}

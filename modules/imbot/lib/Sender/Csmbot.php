<?php

namespace Bitrix\ImBot\Sender;

use Bitrix\Main\Application;
use Bitrix\Main\Result;

class Csmbot extends Base
{
	public function __construct()
	{
		parent::__construct();
	}

	public function sendKeyboardCommand(array $messageFields): Result
	{
		return $this->performRequest(
			'botcontroller.Csmbot.sendKeyboardCommand',
			[
				'messageFields' => \Bitrix\Main\Web\Json::encode($messageFields),
				'region' => Application::getInstance()->getLicense()->getRegion() ?: 'ru',
			]
		);
	}

	public function sendMessage(array $messageFields): Result
	{
		return $this->performRequest(
			'botcontroller.Csmbot.sendMessage',
			[
				'messageFields' => \Bitrix\Main\Web\Json::encode($messageFields),
				'region' => Application::getInstance()->getLicense()->getRegion() ?: 'ru',
			]
		);
	}
}

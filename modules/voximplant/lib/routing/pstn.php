<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;

class Pstn extends Node
{
	protected $phoneNumber;
	protected $failureRule;
	protected $userId;

	public function __construct($phoneNumber, $failureRule, $userId = 0)
	{
		parent::__construct();
		$this->phoneNumber = $phoneNumber;
		$this->failureRule = $failureRule;
		$this->userId = $userId;
	}

	public function getFirstAction(Call $call)
	{
		return new Action(Command::PSTN, [
			'PHONE_NUMBER' => \CVoxImplantPhone::stripLetters($this->phoneNumber),
			'USER_ID' => $this->userId ?: null
		]);
	}

	public function getNextAction(Call $call, array $request = [])
	{
		if($this->failureRule == Command::VOICEMAIL)
		{
			return new Action(Command::VOICEMAIL);
		}
		else
		{
			return new Action(Command::HANGUP);
		}
	}
}
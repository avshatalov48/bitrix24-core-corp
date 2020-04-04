<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;

class Voicemail extends Node
{
	protected $reason;
	protected $userId;

	public function __construct($userId, $reason = '')
	{
		parent::__construct();
		$this->reason = $reason;
		$this->userId = $userId;
	}

	public function getFirstAction(Call $call)
	{
		return new Action(Command::VOICEMAIL, ['REASON' => $this->reason, 'USER_ID' => $this->userId]);
	}

	public function getNextAction(Call $call, array $request = [])
	{
		return new Action(Command::VOICEMAIL, ['REASON' => $this->reason, 'USER_ID' => $this->userId]);
	}

}
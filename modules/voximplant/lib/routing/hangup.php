<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;

class Hangup extends Node
{
	protected $code;
	protected $reason;

	public function __construct($code, $reason)
	{
		parent::__construct();

		$this->code = $code;
		$this->reason = $reason;
	}

	public function getFirstAction(Call $call)
	{
		return new Action(Command::HANGUP, ['CODE' => $this->code, 'REASON' => $this->reason]);
	}

	public function getNextAction(Call $call, array $request = [])
	{
		return new Action(Command::HANGUP, ['CODE' => $this->code, 'REASON' => $this->reason]);
	}
}
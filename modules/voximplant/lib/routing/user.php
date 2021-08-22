<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;

class User extends Node
{
	protected $userId;
	protected $connectType;
	protected $failureRule;
	protected $passIfBusy;

	/**
	 * Crm constructor.
	 * @param int $userId
	 * @param string $connectType
	 * @param string $failureRule
	 */
	public function __construct($userId, $connectType, $failureRule, $passIfBusy = false)
	{
		parent::__construct();
		$this->userId = $userId;
		$this->connectType = $connectType;
		$this->failureRule = $failureRule;
		$this->passIfBusy = $passIfBusy;
	}

	/**
	 * @param Call $call
	 * @return Action|false
	 */
	public function getFirstAction(Call $call)
	{
		$userData = \CVoxImplantIncoming::getUserInfo($this->userId, false);

		if($userData['BUSY'] == 'Y' && !$this->passIfBusy)
		{
			return new Action(Command::BUSY, [
				'USERS' => [$userData],
				'TYPE_CONNECT' => $this->connectType
			]);
		}

		if($call->getIncoming() == \CVoxImplantMain::CALL_OUTGOING && $userData['USER_ID'] == $call->getUserId())
		{
			return new Action(Command::BUSY, [
				'REASON' => 'User can\'t call himself',
				'USERS' => [$userData],
				'TYPE_CONNECT' => $this->connectType
			]);
		}

		if($userData['AVAILABLE'] === 'Y')
		{
			return Action::create(Command::INVITE, [
				'USERS' => [$userData],
				'TYPE_CONNECT' => $this->connectType
			]);
		}

		if ($this->failureRule === \CVoxImplantIncoming::RULE_HUNGUP)
		{
			return Action::create(Command::HANGUP, [
				'USERS' => [$userData],
				'CODE' => 480,
				'REASON' => 'User is not available',
			]);
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getNextAction(Call $call, array $request = [])
	{
		return false;
	}
}
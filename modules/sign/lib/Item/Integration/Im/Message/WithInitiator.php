<?php

namespace Bitrix\Sign\Item\Integration\Im\Message;

use Bitrix\Sign\Contract\Chat\Message\HasInitiator;
use Bitrix\Sign\Item\Integration\Im\Message;
use Bitrix\Sign\Type\User\Gender;

abstract class WithInitiator extends Message implements HasInitiator
{
	protected int $initiatorUserId;
	protected string $initiatorName;
	protected Gender $initiatorGender = Gender::DEFAULT;

	public function __construct(
		int $fromUser,
		int $toUser,
		int $initiatorUserId,
		string $initiatorName,
		Gender $initiatorGender = Gender::DEFAULT,
	)
	{
		parent::__construct($fromUser, $toUser);
		$this->initiatorUserId = $initiatorUserId;
		$this->initiatorName = $initiatorName;
		$this->initiatorGender = $initiatorGender;
	}

	public function getInitiatorName(): string
	{
		return $this->initiatorName;
	}

	public function getInitiatorUserId(): int
	{
		return $this->initiatorUserId;
	}

	public function getInitiatorGender(): Gender
	{
		return $this->initiatorGender;
	}
}

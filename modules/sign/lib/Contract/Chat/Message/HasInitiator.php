<?php

namespace Bitrix\Sign\Contract\Chat\Message;

use Bitrix\Sign\Contract\Chat\Message;
use Bitrix\Sign\Type\User\Gender;

interface HasInitiator extends Message
{
	// who did the action
	public function getInitiatorName(): string;
	public function getInitiatorUserId(): int;
	public function getInitiatorGender(): Gender;
}

<?php

namespace Bitrix\Crm\Integration\Im\Message\Type\Assessment;

use Bitrix\Crm\Integration\Im\Message\Message;

final class ToEmployee extends Message
{
	public function __construct(int $fromUser, int $toUser)
	{
		parent::__construct($fromUser, $toUser);
	}

	public function getTypeId(): string
	{
		return 'doneEmployee';
	}

	public function getFallbackText(): string
	{
		return 'Some message to manager';
	}
}

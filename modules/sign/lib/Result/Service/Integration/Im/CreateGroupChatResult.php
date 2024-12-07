<?php

namespace Bitrix\Sign\Result\Service\Integration\Im;

use Bitrix\Sign\Result\SuccessResult;

class CreateGroupChatResult extends SuccessResult
{
	public function __construct(
		public readonly int $chatId,
	)
	{
		parent::__construct();
	}
}
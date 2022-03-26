<?php

namespace Bitrix\Mobile\Push;

use Bitrix\Main\Result;

abstract class Channel
{
	protected const APP_ID = 'Bitrix24';

	protected const COMMON_MOBILE_PUSH_EVENT = 'CommonMobilePushEvent';

	protected const MODULE_ID = 'mobile';

	abstract public function send(int $userId, Message $message): Result;

	public function __invoke(int $userId, Message $message): Result
	{
		return $this->send($userId, $message);
	}
}

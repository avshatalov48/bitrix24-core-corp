<?php

namespace Bitrix\Call\Controller\Filter;

use Bitrix\Call\Idempotence;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class UniqueRequestFilter extends Base
{
	public function onBeforeAction(Event $event)
	{
		$callRequest = $this->getAction()->getArguments()['callRequest'];

		if ($callRequest && $callRequest->requestId && !Idempotence::isUnique((string)$callRequest->requestId))
		{
			$this->addError(new Error("Request is not unique. It has already been processed.", "REQUEST_NOT_UNIQUE"));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}

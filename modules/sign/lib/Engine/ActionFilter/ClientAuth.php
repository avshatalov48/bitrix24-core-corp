<?php

namespace Bitrix\Sign\Engine\ActionFilter;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

final class ClientAuth extends Base
{
	private const SIGN_SAFE_CLIENT_TOKEN_OPTION = '~sign_safe_client_token_id';

	public function onBeforeAction(Event $event): ?EventResult
	{
		$context = Context::getCurrent();

		$clientToken = $context->getRequest()->get('token');
		$storedClientToken = Option::get('sign', self::SIGN_SAFE_CLIENT_TOKEN_OPTION, false);

		if($clientToken !== $storedClientToken)
		{
			$this->addError(new Error('Client token is invalid', 'CLIENT_TOKEN_IS_INVALID'));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
<?php

namespace Bitrix\BIConnector\Superset\ActionFilter;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

final class ProxyAuth extends Base
{
	public const SUPERSET_PROXY_TOKEN_OPTION = '~superset_proxy_token';

	public function onBeforeAction(Event $event): ?EventResult
	{
		$context = Context::getCurrent();

		$clientToken = $context->getRequest()->getJsonList()->get('token');
		$storedClientToken = Option::get('biconnector', self::SUPERSET_PROXY_TOKEN_OPTION, false);

		if($clientToken !== $storedClientToken)
		{
			$this->addError(new Error('Proxy token is invalid', 'PROXY_TOKEN_IS_INVALID'));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
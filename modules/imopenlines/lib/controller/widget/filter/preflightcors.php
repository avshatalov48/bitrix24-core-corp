<?php

namespace Bitrix\ImOpenLines\Controller\Widget\Filter;

use Bitrix\Main\Event;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\EventResult;

class PreflightCors extends Base
{
	/**
	 * Prefilter add CORS headers to the response for preflight CORS request.
	 *
	 * @param Event $event
	 *
	 * @return null|EventResult
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		$requestMethod = $this->action->getController()->getRequest()->getRequestMethod();
		if ($requestMethod !== 'OPTIONS')
		{
			return null;
		}

		$origin = \Bitrix\Main\Context::getCurrent()->getRequest()->getHeader('Origin');
		if ($origin)
		{
			$response = \Bitrix\Main\Context::getCurrent()->getResponse();
			$response->addHeader('Access-Control-Allow-Origin', $origin);
			$response->addHeader('Access-Control-Allow-Credentials', 'true');
			$response->addHeader(
				'Access-Control-Allow-Headers',
				'origin, content-type, accept, content-range, livechat-dialog-id, livechat-auth-id, x-upload-content-type'
			);

			return new EventResult(EventResult::UNDEFINED, null, null, $this);
		}

		return null;
	}
}
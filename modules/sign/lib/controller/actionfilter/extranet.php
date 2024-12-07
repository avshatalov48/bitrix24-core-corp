<?php
namespace Bitrix\Sign\Controller\ActionFilter;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

final class Extranet extends \Bitrix\Main\Engine\ActionFilter\Base
{
	/**
	 * Check that current user is intranet before action.
	 * @param Event $event Event instance.
	 * @return EventResult|null
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		if (!\Bitrix\Sign\Main\User::isIntranet())
		{
			$this->addError(new Error('Extranet site is not allowed.', 'EXTRANET_IS_NOT_ALLOWED'));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}

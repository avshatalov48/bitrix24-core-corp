<?php
namespace Bitrix\ImOpenLines\Controller\Session\Filter;

use Bitrix\Main\Error,
	Bitrix\Main\Event,
	Bitrix\Main\EventResult,
	Bitrix\Main\Engine\ActionFilter\Base;

use \Bitrix\ImOpenlines\Security\Permissions;

class AccessCheck extends Base
{
	/**
	 * @param Event $event
	 * @return EventResult|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		$userPermissions = Permissions::createWithCurrentUser();

		if(!$userPermissions->canPerform(Permissions::ENTITY_SESSION, Permissions::ACTION_VIEW))
		{
			$this->addError(new Error('Access denied'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
<?php
namespace Bitrix\ImConnector\Controller\Filter;

use Bitrix\ImConnector\Controller\Openlines;
use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;

class LinesAccess extends Base
{
	public function onBeforeAction(Event $event)
	{
		$arguments = $this->getAction()->getArguments();

		if(!Loader::includeModule('imopenlines'))
		{
			$this->addError(new Error(
				Openlines::ERROR_ACCESS_DENIED['message'],
				Openlines::ERROR_ACCESS_DENIED['code']
			));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		if (isset($arguments['userId']))
		{
			$userPermissions = Permissions::createWithUserId($arguments['userId']);
		}
		else
		{
			$userPermissions = Permissions::createWithCurrentUser();
		}

		if (!$userPermissions->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY))
		{
			$this->addError(new Error(
				Openlines::ERROR_ACCESS_DENIED['message'],
				Openlines::ERROR_ACCESS_DENIED['code']
			));
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}

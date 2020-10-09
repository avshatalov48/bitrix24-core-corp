<?php

namespace Bitrix\DocumentGenerator\Engine;

use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckAccess extends Base
{
	public function onBeforeAction(Event $event)
	{
		foreach($this->action->getArguments() as $argument)
		{
			if($argument instanceof Document)
			{
				$userId = $this->action->getController()->getCurrentUser()->getId();
				if($userId > 0)
				{
					$argument->setUserId($userId);
				}
				if(!$argument->hasAccess())
				{
					$this->errorCollection[] = new Error(
						'Access denied', \Bitrix\DocumentGenerator\Controller\Document::ERROR_ACCESS_DENIED
					);

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		}

		return null;
	}
}
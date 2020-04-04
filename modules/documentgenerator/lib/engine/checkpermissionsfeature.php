<?php

namespace Bitrix\DocumentGenerator\Engine;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckPermissionsFeature extends Base
{
	public function onBeforeAction(Event $event)
	{
		if(!Bitrix24Manager::isPermissionsFeatureEnabled())
		{
			$this->errorCollection[] = new Error(
				'Your plan does not support this operation', \Bitrix\DocumentGenerator\Controller\Document::ERROR_ACCESS_DENIED
			);

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
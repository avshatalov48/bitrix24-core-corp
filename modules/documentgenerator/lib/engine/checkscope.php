<?php

namespace Bitrix\DocumentGenerator\Engine;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckScope extends Base
{
	/**
	 * @param Event $event
	 * @return EventResult|null
	 * @throws \Bitrix\Main\SystemException
	 */
	public function onBeforeAction(Event $event)
	{
		$module = null;
		$restServer = null;
		foreach($this->action->getArguments() as $name => $argument)
		{
			if($argument instanceof \CRestServer)
			{
				$restServer = $argument;
			}
			elseif($argument instanceof Document)
			{
				$template = $argument->getTemplate();
				if($template)
				{
					$module = $template->MODULE_ID;
				}
			}
			elseif($argument instanceof Template)
			{
				$module = $argument->MODULE_ID;
			}
		}

		if($restServer && $module !== Driver::REST_MODULE_ID && $module !== null)
		{
			$this->errorCollection[] = new Error('Access denied', \Bitrix\DocumentGenerator\Controller\Base::ERROR_ACCESS_DENIED);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function listAllowedScopes()
	{
		return [
			Controller::SCOPE_REST,
		];
	}
}
<?php

namespace Bitrix\Crm\Controller\DocumentGenerator;

use Bitrix\DocumentGenerator\Template;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckModule extends Base
{
	public function onBeforeAction(Event $event)
	{
		foreach($this->action->getArguments() as $name => $argument)
		{
			if($argument instanceof Template)
			{
				if($argument->MODULE_ID !== \Bitrix\Crm\Controller\DocumentGenerator\Base::MODULE_ID)
				{
					$this->errorCollection[] = new Error(
						'Template not found'
					);
					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
			elseif($argument instanceof \Bitrix\DocumentGenerator\Document)
			{
				$template = $argument->getTemplate();
				if($template && $template->MODULE_ID !== \Bitrix\Crm\Controller\DocumentGenerator\Base::MODULE_ID)
				{
					$this->errorCollection[] = new Error(
						'Document not found'
					);
					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		}

		return null;
	}
}
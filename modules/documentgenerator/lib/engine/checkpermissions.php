<?php

namespace Bitrix\DocumentGenerator\Engine;

use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Template;
use Bitrix\DocumentGenerator\UserPermissions;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CheckPermissions extends Base
{
	protected $entity;
	protected $actionType;

	public function __construct($entity, $actionType = null)
	{
		parent::__construct();
		$this->entity = $entity;
		$this->actionType = $actionType;
	}

	public function onBeforeAction(Event $event)
	{
		if($this->entity === UserPermissions::ENTITY_SETTINGS)
		{
			if(!Driver::getInstance()->getUserPermissions()->canModifySettings())
			{
				$this->errorCollection[] = new Error('You do not have permissions to modify settings');
			}
		}
		elseif($this->entity === UserPermissions::ENTITY_DOCUMENTS)
		{
			if($this->actionType === UserPermissions::ACTION_CREATE)
			{
				foreach($this->action->getArguments() as $argument)
				{
					if($argument instanceof Template)
					{
						if(!Driver::getInstance()->getUserPermissions()->canCreateDocumentOnTemplate($argument->ID))
						{
							$this->errorCollection[] = new Error('You do not have permissions to create document on this template');
						}
					}
				}
			}
			elseif($this->actionType === UserPermissions::ACTION_MODIFY)
			{
				foreach($this->action->getArguments() as $argument)
				{
					if($argument instanceof Document)
					{
						if(!Driver::getInstance()->getUserPermissions()->canModifyDocument($argument->ID))
						{
							$this->errorCollection[] = new Error('You do not have permissions to modify this document');
						}
					}
				}
			}
			elseif(!Driver::getInstance()->getUserPermissions()->canViewDocuments())
			{
				$this->errorCollection[] = new Error('You do not have permissions to view documents');
			}
		}
		elseif($this->entity === UserPermissions::ENTITY_TEMPLATES)
		{
			if(!Driver::getInstance()->getUserPermissions()->canModifyTemplates())
			{
				$this->errorCollection[] = new Error('You do not have permissions to modify templates');
			}
			else
			{
				foreach($this->action->getArguments() as $argument)
				{
					if($argument instanceof Template)
					{
						if(!Driver::getInstance()->getUserPermissions()->canModifyTemplate($argument->ID))
						{
							$this->errorCollection[] = new Error('You do not have permissions to modify this template');
						}
					}
				}
			}
		}

		if(!$this->errorCollection->isEmpty())
		{
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}
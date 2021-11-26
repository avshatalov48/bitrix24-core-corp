<?php

namespace Bitrix\Rpa\Engine;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Stage;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\UserPermissions;

class CheckPermissions extends Base
{
	protected $entity;
	protected $actionType;
	protected $userPermissions;

	public function __construct($entity, $actionType = null)
	{
		parent::__construct();
		$this->entity = $entity;
		$this->actionType = $actionType;
		$this->userPermissions = Driver::getInstance()->getUserPermissions();
	}

	public function onBeforeAction(Event $event)
	{
		if($this->entity === UserPermissions::ENTITY_TYPE)
		{
			if($this->actionType === UserPermissions::ACTION_CREATE)
			{
				if(!$this->userPermissions->canCreateType())
				{
					$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_TYPE_CREATE_DENIED')));
				}
			}
			else
			{
				/** @var Type $type */
				$type = $this->getObjectFromArguments(Type::class);
				if(!$type)
				{
					$this->addError(new Error(Loc::getMessage('RPA_NOT_FOUND_ERROR')));
				}
				else
				{
					if($this->actionType === UserPermissions::ACTION_VIEW && !$this->userPermissions->canViewType($type->getId()))
					{
						$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_TYPE_VIEW_DENIED')));
					}
					if($this->actionType === UserPermissions::ACTION_MODIFY && !$this->userPermissions->canModifyType($type->getId()))
					{
						$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_TYPE_MODIFY_DENIED')));
					}
					if($this->actionType === UserPermissions::ACTION_DELETE && !$this->userPermissions->canDeleteType($type->getId()))
					{
						$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_TYPE_MODIFY_DENIED')));
					}
				}
			}
		}
		elseif($this->entity === UserPermissions::ENTITY_STAGE)
		{
			$typeId = null;
			if($this->actionType === UserPermissions::ACTION_CREATE)
			{
				$fields = $this->getFieldsFromArguments();
				if($fields)
				{
					$typeId = $fields['typeId'];
				}
			}
			else
			{
				/** @var Stage $stage */
				$stage = $this->getObjectFromArguments(Stage::class);
				if($stage)
				{
					$typeId = $stage->getTypeId();
				}
				else
				{
					$this->addError(new Error(Loc::getMessage('RPA_STAGE_NOT_FOUND_ERROR')));
				}
			}

			if ($this->errorCollection->isEmpty())
			{
				if(!$typeId)
				{
					$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_TYPE_NOT_FOUND')));
				}
				elseif($this->actionType === UserPermissions::ACTION_VIEW && !$this->userPermissions->canViewType($typeId))
				{
					$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_STAGE_VIEW_DENIED')));
				}
				elseif(
					(
						$this->actionType === UserPermissions::ACTION_MODIFY
						|| $this->actionType === UserPermissions::ACTION_CREATE
					)
					&& !$this->userPermissions->canModifyType($typeId)
				)
				{
					$this->addError(new Error(Loc::getMessage('RPA_PERMISSION_STAGE_MODIFY_DENIED')));
				}
			}
		}

		if(!$this->errorCollection->isEmpty())
		{
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	protected function getObjectFromArguments(string $className)
	{
		$result = null;
		foreach($this->action->getArguments() as $argument)
		{
			if($argument instanceof $className)
			{
				$result = $argument;
				break;
			}
		}

		return $result;
	}

	protected function getFieldsFromArguments(): ?array
	{
		$arguments = $this->action->getArguments();
		if(isset($arguments['fields']))
		{
			return $arguments['fields'];
		}

		return null;
	}
}
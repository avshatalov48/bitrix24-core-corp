<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Add extends Operation
{
	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());
		$canAddItem = $userPermissions->canAddItem($this->item);

		if(!$canAddItem)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_ADD_DENIED'),
					static::ERROR_CODE_ITEM_ADD_ACCESS_DENIED
				)
			);
		}

		return $result;
	}

	protected function save(): Result
	{
		return $this->item->save($this->isCheckFieldsEnabled());
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController(['ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()]);
		if ($timelineController)
		{
			$timelineController->onCreate($this->item->getId(), $this->item->getData());
		}
	}

	protected function sendPullEvent(): void
	{
		parent::sendPullEvent();

		\Bitrix\Crm\Kanban\SupervisorTable::sendItem(
			$this->item->getId(),
			\CCrmOwnerType::ResolveName($this->item->getEntityTypeId()),
			'kanban_add'
		);

		PullManager::getInstance()->sendItemAddedEvent($this->pullItem, $this->pullParams);
	}

	protected function runAutomation(): Result
	{
		$result = parent::runAutomation();

		if($result->isSuccess())
		{
			/** @var \Bitrix\Crm\Automation\Starter $starter */
			$starter = $result->getData()['starter'];
			return $starter->runOnAdd();
		}

		return $result;
	}

	protected function checkLimits(): Result
	{
		$result = parent::checkLimits();

		$restriction = RestrictionManager::getDynamicTypesLimitRestriction();
		if ($restriction->isCreateItemRestricted($this->item->getEntityTypeId()))
		{
			$result->addError($restriction->getCreateItemRestrictedError());
		}

		return $result;
	}
}

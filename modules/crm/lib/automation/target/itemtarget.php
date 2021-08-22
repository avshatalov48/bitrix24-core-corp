<?php

namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\Automation;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;

class ItemTarget extends BaseTarget
{
	protected $factory;

	public function __construct(int $entityTypeId)
	{
		$this->factory = Container::getInstance()->getFactory($entityTypeId);
	}

	public function isAvailable()
	{
		return Automation\Factory::isAutomationAvailable($this->getEntityTypeId());
	}

	public function getEntityTypeId()
	{
		return $this->factory->getEntityTypeId();
	}

	public function getEntityId()
	{
		return $this->getEntity()->getId();
	}

	public function setEntityById($entityId)
	{
		$entityId = (int)$entityId;
		if ($entityId > 0)
		{
			if (!is_null($this->factory))
			{
				$this->setEntity($this->factory->getItem($entityId));
				$this->setDocumentId(\CCrmOwnerType::ResolveName($this->getEntityTypeId()) . "_" . $entityId);
			}
		}
	}

	public function getResponsibleId()
	{
		return $this->getEntity()->getAssignedById();
	}

	public function getEntityStatus()
	{
		return $this->getEntity()->getStageId();
	}

	public function setEntityStatus($statusId)
	{
		$context = Container::getInstance()->getContext();
		$context->setUserId(0);
		$context->setScope(Context::SCOPE_AUTOMATION);
		$operation = $this->factory->getUpdateOperation(
			$this->getEntity()->setStageId($statusId),
			$context
		);
		$operation
			->disableCheckFields()
			->disableCheckAccess()
			// automation will be launched right after
			->disableAutomation()
		;

		return $operation->launch()->isSuccess();
	}

	public function getStatusInfos($categoryId = 0)
	{
		if ($categoryId === 0 && $this->factory->isCategoriesSupported())
		{
			$categoryId = $this->factory->getDefaultCategory()->getId();
		}

		$statusInfos = [];
		foreach ($this->factory->getStages($categoryId) as $status)
		{
			$statusInfos[$status->getStatusId()] = $status;
		}
		return $statusInfos;
	}

	public function getEntityStatuses()
	{
		$categoryId = $this->factory->isCategoriesSupported() ? $this->getEntity()->getCategoryId() : 0;
		return array_keys($this->getStatusInfos($categoryId));
	}

	public function getEntity()
	{
		if (is_null($this->entity) && $this->getDocumentId())
		{
			$documentId = mb_split('_(?=[^_]*$)', $this->getDocumentId());
			$this->setEntityById($documentId[1] ?? 0);
		}
		if (is_null($this->entity))
		{
			return $this->factory->createItem([]);
		}

		return $this->entity;
	}
}

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

	public function canTriggerSetExecuteBy(): bool
	{
		return true;
	}

	public function getEntityTypeId()
	{
		return $this->factory->getEntityTypeId();
	}

	public function getEntityId()
	{
		$entity = $this->getEntity();

		return isset($entity) ? $entity->getId() : 0;
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
		$entity = $this->getEntity();

		return isset($entity) ? $entity->getAssignedById() : 0;
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntity();

		return isset($entity) ? $entity->getStageId() : '';
	}

	public function setEntityStatus($statusId, $executeBy = null)
	{
		$entity = $this->getEntity();
		if (!isset($entity))
		{
			return false;
		}

		$context = clone Container::getInstance()->getContext();
		$context->setUserId($executeBy ?? 0);
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
		$entity = $this->getEntity();
		if ($categoryId === 0 && $this->factory->isCategoriesSupported() && isset($entity))
		{
			$categoryId = $entity->getCategoryId();
		}

		$statusInfos = [];
		foreach ($this->factory->getStages($categoryId) as $status)
		{
			$statusInfos[$status->getStatusId()] = $status->collectValues();
		}
		return $statusInfos;
	}

	public function getEntityStatuses()
	{
		$entity = $this->getEntity();

		$categoryId = 0;
		if ($this->factory->isCategoriesEnabled() && isset($entity))
		{
			$categoryId = $entity->getCategoryId();
		}

		return array_keys($this->getStatusInfos($categoryId));
	}

	public function getEntity()
	{
		if (is_null($this->entity) && $this->getDocumentId())
		{
			$documentId = mb_split('_(?=[^_]*$)', $this->getDocumentId());
			$this->setEntityById($documentId[1] ?? 0);
		}

		return $this->entity;
	}
}

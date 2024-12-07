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

	protected function getEntityIdByDocumentId(string $documentId): int
	{
		return (int)str_replace(
			\CCrmOwnerType::ResolveName($this->getEntityTypeId()) . '_',
			'' ,
			$documentId
		);
	}

	protected function getEntityFields(array $select): array
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return [];
		}

		$item = $this->factory->getItem($id, $select);
		if ($item)
		{
			return $item->getData();
		}

		return [];
	}

	public function getResponsibleId()
	{
		$entity = $this->getEntityFields(['ASSIGNED_BY_ID']);

		return (int)$entity['ASSIGNED_BY_ID'];
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntityFields(['STAGE_ID']);

		return $entity['STAGE_ID'] ?? '';
	}

	public function setEntityStatus($statusId, $executeBy = null)
	{
		$id = $this->getEntityId();
		if ($id)
		{
			$entity = $this->factory->getItem($id, ['ID']);
		}

		if (!isset($entity))
		{
			return false;
		}

		$context = clone Container::getInstance()->getContext();
		$context->setUserId($executeBy ?? 0);
		$context->setScope(Context::SCOPE_AUTOMATION);

		$operation = $this->factory->getUpdateOperation(
			$entity->setStageId($statusId),
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
			$id = $this->getEntityId();
			if ($id)
			{
				$entity = $this->factory->getItem($id, ['ID', 'CATEGORY_ID']);
				if ($entity)
				{
					$categoryId = $entity->getCategoryId();
				}
			}
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
		$id = $this->getEntityId();
		if ($id)
		{
			$entity = $this->factory->getItem($id, ['ID']);
		}

		$categoryId = 0;
		if ($this->factory->isCategoriesEnabled() && isset($entity))
		{
			$categoryId = $entity->getCategoryId();
		}

		return array_keys($this->getStatusInfos($categoryId));
	}
}

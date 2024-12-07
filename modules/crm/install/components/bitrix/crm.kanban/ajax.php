<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Exclusion\Manager;
use Bitrix\Crm\Integration\Recyclebin\RecyclingManager;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Recyclebin\Recyclebin;

class KanbanAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param array $entityIds
	 * @param int $entityTypeId
	 * @return array|null
	 */
	public function restoreAction(array $entityIds, int $entityTypeId): ?array
	{
		try
		{
			Loader::includeModule('recyclebin');
			Loader::includeModule('crm');

			$result = [];

			foreach ($entityIds as $entityId)
			{

				if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
				{
					$entityType = \Bitrix\Crm\Integration\Recyclebin\Dynamic::getEntityName($entityTypeId);
				}
				else
				{
					$entityType = RecyclingManager::resolveRecyclableEntityType($entityTypeId);
				}

				if ($entityType === '')
				{
					continue;
				}

				$recyclebinEntityId = Recyclebin::findId('crm', $entityType, $entityId);

				$result[$recyclebinEntityId] = Recyclebin::restore($recyclebinEntityId);
			}

			return $result;
		}
		catch (\Exception $e)
		{
			$this->errorCollection[] = new Error($e->getMessage(), $e->getCode());

			return null;
		}
	}

	/**
	 * @deprecated
	 * @todo remove?
	 */
	public function getFieldsAction(string $entityType, string $viewType): array
	{
		Loader::includeModule('crm');

		$entity = Kanban\Entity::getInstance($entityType);
		if(!$entity)
		{
			$this->addError(new \Bitrix\Main\Error('Entity not found'));
		}
		elseif(!$entity->checkReadPermissions(0))
		{
			$this->addError(new \Bitrix\Main\Error('Access denied'));
		}
		if($this->getErrors())
		{
			return [];
		}

		return array_values($entity->getPopupFields($viewType));
	}

	public function getPreparedFieldsAction(string $entityType, string $viewType, array $selectedFields = []): array
	{
		Loader::includeModule('crm');

		$entity = Kanban\Entity::getInstance($entityType);

		if (!$entity)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return [];
		}

		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		if (!Container::getInstance()->getUserPermissions()->canReadType($entityTypeId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return [];
		}

		return $entity->getPreparedCustomFieldsConfig($viewType, $selectedFields);
	}

	public function excludeEntityAction(string $entityType, array $ids): void
	{
		Loader::includeModule('crm');

		$entity = Kanban\Entity::getInstance($entityType);
		if(!$entity)
		{
			$this->addError(new \Bitrix\Main\Error('Entity not found'));
			return;
		}

		$entityTypeId = $entity->getTypeId();
		try
		{
			foreach ($ids as $id)
			{
				Manager::excludeEntity($entityTypeId, $id);
			}
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$this->addError(new \Bitrix\Main\Error($exception->getMessage()));
		}
	}
}

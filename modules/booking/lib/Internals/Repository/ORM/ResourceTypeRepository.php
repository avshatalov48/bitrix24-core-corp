<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Exception\Resource\CreateResourceException;
use Bitrix\Booking\Exception\ResourceType\CreateResourceTypeException;
use Bitrix\Booking\Exception\ResourceType\RemoveResourceTypeException;
use Bitrix\Booking\Exception\ResourceType\UpdateResourceTypeException;
use Bitrix\Booking\Internals\Model\ResourceTypeNotificationSettingsTable;
use Bitrix\Booking\Internals\Model\ResourceTypeTable;
use Bitrix\Booking\Internals\Query\FilterInterface;
use Bitrix\Booking\Internals\Query\ResourceType\ResourceTypeFilter;
use Bitrix\Booking\Internals\Query\SortInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceTypeMapper;
use Bitrix\Booking\Internals\Repository\ResourceTypeRepositoryInterface;

class ResourceTypeRepository implements ResourceTypeRepositoryInterface
{
	private ResourceTypeMapper $mapper;

	public function __construct(ResourceTypeMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	//@todo add select
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		SortInterface|null $sort = null,
	): Entity\ResourceType\ResourceTypeCollection
	{
		$query = ResourceTypeTable::query()
			->setSelect([
				'*',
				'NOTIFICATION_SETTINGS',
			])
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		if ($offset !== null)
		{
			$query->setOffset($offset);
		}

		$preparedFilter = $filter?->prepareFilter();
		if ($preparedFilter)
		{
			$query->where($preparedFilter);
		}

		$preparedSort = $sort?->prepareSort();
		if ($preparedSort)
		{
			$query->setOrder($preparedSort);
		}

		$queryResult = $query->exec();

		$resourceTypes = [];

		while ($ormResourceType = $queryResult->fetchObject())
		{
			$resourceTypes[] = $this->mapper->convertFromOrm($ormResourceType);
		}

		return	new Entity\ResourceType\ResourceTypeCollection(...$resourceTypes);
	}

	public function getById(int $id): Entity\ResourceType\ResourceType|null
	{
		return $this->getList(
			limit: 1,
			filter: new ResourceTypeFilter([
				'ID' => $id,
			]),
		)->getFirstCollectionItem();
	}

	public function getByModuleIdAndCode(string $moduleId, string $code): Entity\ResourceType\ResourceType|null
	{
		$filter = new ResourceTypeFilter(['MODULE_ID' => $moduleId, 'CODE' => $code]);
		$preparedFilter = $filter->prepareFilter();

		$rowResourceType = ResourceTypeTable::query()
			->setSelect(['*'])
			->setLimit(1)
			->where($preparedFilter)
			->exec()
			->fetchObject()
		;

		if (!$rowResourceType)
		{
			return null;
		}

		return $this->mapper->convertFromOrm($rowResourceType);
	}

	public function save(Entity\ResourceType\ResourceType $resourceType): Entity\ResourceType\ResourceType
	{
		return $resourceType->getId()
			? $this->update($resourceType)
			: $this->insert($resourceType)
		;
	}

	public function remove(int $resourceTypeId): void
	{
		$ormResourceType = ResourceTypeTable::getByPrimary($resourceTypeId)->fetchObject();
		if (!$ormResourceType)
		{
			throw new RemoveResourceTypeException('Resource type has not been found');
		}

		$ormResourceTypeNotificationSettings = $ormResourceType->fillNotificationSettings();
		if ($ormResourceTypeNotificationSettings)
		{
			$notificationSettingsDeleteResult = $ormResourceTypeNotificationSettings->delete();
			if (!$notificationSettingsDeleteResult->isSuccess())
			{
				throw new RemoveResourceTypeException($notificationSettingsDeleteResult->getErrors()[0]->getMessage());
			}
		}

		$deleteResult = $ormResourceType->delete();
		if (!$deleteResult->isSuccess())
		{
			throw new RemoveResourceTypeException($deleteResult->getErrors()[0]->getMessage());
		}
	}

	private function insert(Entity\ResourceType\ResourceType $resourceType): Entity\ResourceType\ResourceType
	{
		$result = $this->mapper->convertToOrm($resourceType)->save();

		if (!$result->isSuccess())
		{
			throw new CreateResourceTypeException($result->getErrors()[0]->getMessage());
		}

		$notificationSettingsSaveResult = ResourceTypeNotificationSettingsTable
			::createObject()
			->setTypeId($result->getId())
			->setIsInfoOn($resourceType->isInfoNotificationOn())
			->setTemplateTypeInfo($resourceType->getTemplateTypeInfo())
			->setIsConfirmationOn($resourceType->isConfirmationNotificationOn())
			->setTemplateTypeConfirmation($resourceType->getTemplateTypeConfirmation())
			->setIsReminderOn($resourceType->isReminderNotificationOn())
			->setTemplateTypeReminder($resourceType->getTemplateTypeReminder())
			->setIsFeedbackOn($resourceType->isFeedbackNotificationOn())
			->setTemplateTypeFeedback($resourceType->getTemplateTypeFeedback())
			->setIsDelayedOn($resourceType->isDelayedNotificationOn())
			->setTemplateTypeDelayed($resourceType->getTemplateTypeDelayed())
			->save()
		;
		if (!$notificationSettingsSaveResult->isSuccess())
		{
			throw new CreateResourceException($notificationSettingsSaveResult->getErrors()[0]->getMessage());
		}

		return $this->getById($result->getId());
	}

	private function update(Entity\ResourceType\ResourceType $resourceType): Entity\ResourceType\ResourceType
	{
		$ormResourceType = $this->mapper->convertToOrm($resourceType);
		$resourceTypeSaveResult = $ormResourceType->save();
		if (!$resourceTypeSaveResult->isSuccess())
		{
			throw new UpdateResourceTypeException($resourceTypeSaveResult->getErrors()[0]->getMessage());
		}

		$notificationSettings = $ormResourceType->fillNotificationSettings();
		if (!$notificationSettings)
		{
			$notificationSettings = ResourceTypeNotificationSettingsTable::createObject();
			$notificationSettings->setTypeId($resourceType->getId());
		}

		$notificationSettingsSaveResult = $notificationSettings
			->setIsInfoOn($resourceType->isInfoNotificationOn())
			->setTemplateTypeInfo($resourceType->getTemplateTypeInfo())
			->setIsConfirmationOn($resourceType->isConfirmationNotificationOn())
			->setTemplateTypeConfirmation($resourceType->getTemplateTypeConfirmation())
			->setIsReminderOn($resourceType->isReminderNotificationOn())
			->setTemplateTypeReminder($resourceType->getTemplateTypeReminder())
			->setIsFeedbackOn($resourceType->isFeedbackNotificationOn())
			->setTemplateTypeFeedback($resourceType->getTemplateTypeFeedback())
			->setIsDelayedOn($resourceType->isDelayedNotificationOn())
			->setTemplateTypeDelayed($resourceType->getTemplateTypeDelayed())
			->save()
		;
		if (!$notificationSettingsSaveResult->isSuccess())
		{
			throw new UpdateResourceTypeException($notificationSettingsSaveResult->getErrors()[0]->getMessage());
		}

		return $this->getById($ormResourceType->getId());
	}
}

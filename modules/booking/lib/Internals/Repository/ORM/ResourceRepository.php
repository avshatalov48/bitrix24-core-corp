<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Exception\Resource\CreateResourceException;
use Bitrix\Booking\Exception\Resource\RemoveResourceException;
use Bitrix\Booking\Exception\Resource\UpdateResourceException;
use Bitrix\Booking\Internals\Model\ResourceNotificationSettingsTable;
use Bitrix\Booking\Internals\Model\ResourceTable;
use Bitrix\Booking\Internals\Query\FilterInterface;
use Bitrix\Booking\Internals\Query\Resource\ResourceFilter;
use Bitrix\Booking\Internals\Query\SortInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceDataMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceMapper;
use Bitrix\Booking\Internals\Repository\ResourceRepositoryInterface;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\QueryHelper;

class ResourceRepository implements ResourceRepositoryInterface
{
	private ResourceMapper $mapper;
	private ResourceDataMapper $resourceDataMapper;

	public function __construct(ResourceMapper $mapper, ResourceDataMapper $resourceDataMapper)
	{
		$this->mapper = $mapper;
		$this->resourceDataMapper = $resourceDataMapper;
	}

	//@todo add select
	public function getList(
		int|null $limit = null,
		int|null $offset = null,
		FilterInterface|null $filter = null,
		SortInterface|null $sort = null,
	): Entity\Resource\ResourceCollection
	{
		$query = ResourceTable::query()
			->setSelect([
				'*',
				'TYPE',
				'TYPE.NOTIFICATION_SETTINGS',
				'DATA',
				'SETTINGS',
				'NOTIFICATION_SETTINGS',
			]);

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

		$ormResources = QueryHelper::decompose($query);
		$resources = [];
		foreach ($ormResources as $ormResource)
		{
			$resources[] = $this->mapper->convertFromOrm($ormResource);
		}

		return new Entity\Resource\ResourceCollection(...$resources);
	}

	public function getTotal(
		FilterInterface|null $filter = null,
	): int
	{
		$query = ResourceTable::query()
			->setSelect(['COUNT'])
			->registerRuntimeField('COUNT', new ExpressionField('COUNT', 'COUNT(*)'));

		$preparedFilter = $filter?->prepareFilter();
		if ($preparedFilter)
		{
			$query->where($preparedFilter);
		}

		return (int)$query->fetch()['COUNT'];
	}

	public function getById(int $id): Entity\Resource\Resource|null
	{
		return $this->getList(
			limit: 1,
			filter: new ResourceFilter([
				'ID' => $id,
			]),
		)->getFirstCollectionItem();
	}

	public function save(Entity\Resource\Resource $resource): Entity\Resource\Resource
	{
		return $resource->getId()
			? $this->update($resource)
			: $this->insert($resource)
		;
	}

	public function remove(int $resourceId): void
	{
		$ormResource = ResourceTable::getByPrimary($resourceId)->fetchObject();
		if (!$ormResource)
		{
			throw new RemoveResourceException('Resource has not been found');
		}

		$ormResourceData = $ormResource->fillData();
		if ($ormResourceData)
		{
			$dataDeleteResult = $ormResourceData->delete();
			if (!$dataDeleteResult->isSuccess())
			{
				throw new RemoveResourceException($dataDeleteResult->getErrors()[0]->getMessage());
			}
		}

		$ormResourceNotificationSettings = $ormResource->fillNotificationSettings();
		if ($ormResourceNotificationSettings)
		{
			$notificationSettingsDeleteResult = $ormResourceNotificationSettings->delete();
			if (!$notificationSettingsDeleteResult->isSuccess())
			{
				throw new RemoveResourceException($notificationSettingsDeleteResult->getErrors()[0]->getMessage());
			}
		}

		$deleteResult = $ormResource->delete();
		if (!$deleteResult->isSuccess())
		{
			throw new RemoveResourceException($deleteResult->getErrors()[0]->getMessage());
		}
	}

	/**
	 * @throws CreateResourceException
	 */
	private function insert(Entity\Resource\Resource $resource): Entity\Resource\Resource
	{
		$ormResource = $this->mapper->convertToOrm($resource);
		$resourceSaveResult = $ormResource->save();
		if (!$resourceSaveResult->isSuccess())
		{
			throw new CreateResourceException($resourceSaveResult->getErrors()[0]->getMessage());
		}

		$resource->setId($resourceSaveResult->getId());

		if (!$resource->isExternal())
		{
			$dataSaveResult = $this->resourceDataMapper->convertToOrm($resource)->save();
			if (!$dataSaveResult->isSuccess())
			{
				throw new CreateResourceException($dataSaveResult->getErrors()[0]->getMessage());
			}
		}

		$notificationSettingsSaveResult = ResourceNotificationSettingsTable
			::createObject()
			->setResourceId($resource->getId())
			->setIsInfoOn($resource->isInfoNotificationOn())
			->setTemplateTypeInfo($resource->getTemplateTypeInfo())
			->setIsConfirmationOn($resource->isConfirmationNotificationOn())
			->setTemplateTypeConfirmation($resource->getTemplateTypeConfirmation())
			->setIsReminderOn($resource->isReminderNotificationOn())
			->setTemplateTypeReminder($resource->getTemplateTypeReminder())
			->setIsFeedbackOn($resource->isFeedbackNotificationOn())
			->setTemplateTypeFeedback($resource->getTemplateTypeFeedback())
			->setIsDelayedOn($resource->isDelayedNotificationOn())
			->setTemplateTypeDelayed($resource->getTemplateTypeDelayed())
			->save()
		;
		if (!$notificationSettingsSaveResult->isSuccess())
		{
			throw new CreateResourceException($notificationSettingsSaveResult->getErrors()[0]->getMessage());
		}

		return $this->getById($resourceSaveResult->getId());
	}

	private function update(Entity\Resource\Resource $resource): Entity\Resource\Resource
	{
		$ormResource = $this->mapper->convertToOrm($resource);
		$resourceSaveResult = $ormResource->save();
		if (!$resourceSaveResult->isSuccess())
		{
			throw new CreateResourceException($resourceSaveResult->getErrors()[0]->getMessage());
		}

		$dataSaveResult = $this->resourceDataMapper->convertToOrm($resource)->save();
		if (!$dataSaveResult->isSuccess())
		{
			throw new UpdateResourceException($dataSaveResult->getErrors()[0]->getMessage());
		}

		$notificationSettings = $ormResource->fillNotificationSettings();
		if (!$notificationSettings)
		{
			$notificationSettings = ResourceNotificationSettingsTable::createObject();
			$notificationSettings->setResourceId($resource->getId());
		}

		$notificationSettingsSaveResult = $notificationSettings
			->setIsInfoOn($resource->isInfoNotificationOn())
			->setTemplateTypeInfo($resource->getTemplateTypeInfo())
			->setIsConfirmationOn($resource->isConfirmationNotificationOn())
			->setTemplateTypeConfirmation($resource->getTemplateTypeConfirmation())
			->setIsReminderOn($resource->isReminderNotificationOn())
			->setTemplateTypeReminder($resource->getTemplateTypeReminder())
			->setIsFeedbackOn($resource->isFeedbackNotificationOn())
			->setTemplateTypeFeedback($resource->getTemplateTypeFeedback())
			->setIsDelayedOn($resource->isDelayedNotificationOn())
			->setTemplateTypeDelayed($resource->getTemplateTypeDelayed())
			->save()
		;
		if (!$notificationSettingsSaveResult->isSuccess())
		{
			throw new UpdateResourceException($notificationSettingsSaveResult->getErrors()[0]->getMessage());
		}

		return $this->getById($resource->getId());
	}
}

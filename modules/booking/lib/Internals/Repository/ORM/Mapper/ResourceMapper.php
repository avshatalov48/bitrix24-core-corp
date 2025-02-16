<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Model\EO_Resource;
use Bitrix\Booking\Internals\Model\ResourceTable;

class ResourceMapper
{
	public function convertFromOrm(EO_Resource $ormResource): Resource
	{
		$resource = (new Resource())
			->setId($ormResource->getId())
			->setExternalId($ormResource->getExternalId())
			->setMain($ormResource->getIsMain())
		;

		$ormResourceType = $ormResource->getType();
		if ($ormResourceType)
		{
			$resource->setType((new ResourceTypeMapper())->convertFromOrm($ormResourceType));
		}

		$ormResourceSettings = $ormResource->getSettings();
		if ($ormResourceSettings)
		{
			$resource->setSlotRanges((new ResourceSlotMapper())->convertFromOrm($ormResourceSettings));
		}

		$ormNotificationSettings = $ormResource->getNotificationSettings();
		if ($ormNotificationSettings)
		{
			$resource
				->setIsInfoNotificationOn($ormNotificationSettings->getIsInfoOn())
				->setTemplateTypeInfo($ormNotificationSettings->getTemplateTypeInfo())
				->setIsConfirmationNotificationOn($ormNotificationSettings->getIsConfirmationOn())
				->setTemplateTypeConfirmation($ormNotificationSettings->getTemplateTypeConfirmation())
				->setIsReminderNotificationOn($ormNotificationSettings->getIsReminderOn())
				->setTemplateTypeReminder($ormNotificationSettings->getTemplateTypeReminder())
				->setIsFeedbackNotificationOn($ormNotificationSettings->getIsFeedbackOn())
				->setTemplateTypeFeedback($ormNotificationSettings->getTemplateTypeFeedback())
				->setIsDelayedNotificationOn($ormNotificationSettings->getIsDelayedOn())
				->setTemplateTypeDelayed($ormNotificationSettings->getTemplateTypeDelayed())
			;
		}

		$ormResourceData = $ormResource->getData();
		if (!$resource->isExternal() && $ormResourceData)
		{
			$resource
				->setName($ormResourceData->getName())
				->setDescription($ormResourceData->getDescription())
				->setCreatedBy($ormResourceData->getCreatedBy())
				->setCreatedAt($ormResourceData->getCreatedAt()->getTimestamp())
				->setUpdatedAt($ormResourceData->getUpdatedAt()->getTimestamp())
			;
		}

		return $resource;
	}

	public function convertToOrm(Resource $resource): EO_Resource
	{
		$ormResource = $resource->getId()
			? EO_Resource::wakeUp($resource->getId())
			: ResourceTable::createObject();

		$type = $resource->getType();
		if ($type)
		{
			$ormResource->setTypeId($type->getId());
		}

		if ($resource->getExternalId())
		{
			$ormResource->setExternalId($resource->getExternalId());
		}

		$ormResource->setIsMain($resource->isMain());

		return $ormResource;
	}
}

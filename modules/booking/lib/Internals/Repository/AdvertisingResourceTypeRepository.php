<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Booking\Internals\Service\Notifications\NotificationTemplateType;
use Bitrix\Main\Localization\Loc;

class AdvertisingResourceTypeRepository
{
	public function getList(): array
	{
		return [
			[
				'code' => 'medicalServices',
				'name' => Loc::getMessage('ADVERTISING_TYPE_NAME_MEDICAL_SERVICES'),
				'description' => Loc::getMessage('ADVERTISING_TYPE_DESCRIPTION_MEDICAL_SERVICES'),
				'resourceType' => [
					'code' => 'doctor',
					'name' => Loc::getMessage('ADVERTISING_TYPE_RESOURCE_TYPE_NAME_DOCTOR'),
					'isInfoNotificationOn' => true,
					'templateTypeInfo' => NotificationTemplateType::Animate->value,
					'isConfirmationNotificationOn' => true,
					'templateTypeConfirmation' => NotificationTemplateType::Animate->value,
					'isReminderNotificationOn' => true,
					'templateTypeReminder' => NotificationTemplateType::Base->value,
					'isFeedbackNotificationOn' => false,
					'templateTypeFeedback' => NotificationTemplateType::Animate->value,
					'isDelayedNotificationOn' => true,
					'templateTypeDelayed' => NotificationTemplateType::Animate->value,
				],
			],
			[
				'code' => 'equipmentRent',
				'name' => Loc::getMessage('ADVERTISING_TYPE_NAME_EQUIPMENT_RENT'),
				'description' => Loc::getMessage('ADVERTISING_TYPE_DESCRIPTION_EQUIPMENT_RENT'),
				'resourceType' => [
					'moduleId' => ResourceType::INTERNAL_MODULE_ID,
					'code' => 'equipment',
					'name' => Loc::getMessage('ADVERTISING_TYPE_RESOURCE_TYPE_NAME_EQUIPMENT'),
					'isInfoNotificationOn' => true,
					'templateTypeInfo' => NotificationTemplateType::Inanimate->value,
					'isConfirmationNotificationOn' => true,
					'templateTypeConfirmation' => NotificationTemplateType::Inanimate->value,
					'isReminderNotificationOn' => true,
					'templateTypeReminder' => NotificationTemplateType::Base->value,
					'isFeedbackNotificationOn' => false,
					'templateTypeFeedback' => NotificationTemplateType::Inanimate->value,
					'isDelayedNotificationOn' => true,
					'templateTypeDelayed' => NotificationTemplateType::Inanimate->value,
				],
			],
			[
				'code' => 'expertServices',
				'name' => Loc::getMessage('ADVERTISING_TYPE_NAME_EXPERT_SERVICES'),
				'description' => Loc::getMessage('ADVERTISING_TYPE_DESCRIPTION_EXPERT_SERVICES'),
				'resourceType' => [
					'moduleId' => ResourceType::INTERNAL_MODULE_ID,
					'code' => 'expert',
					'name' => Loc::getMessage('ADVERTISING_TYPE_RESOURCE_TYPE_NAME_EXPERT'),
					'isInfoNotificationOn' => true,
					'templateTypeInfo' => NotificationTemplateType::Animate->value,
					'isConfirmationNotificationOn' => true,
					'templateTypeConfirmation' => NotificationTemplateType::Animate->value,
					'isReminderNotificationOn' => true,
					'templateTypeReminder' => NotificationTemplateType::Base->value,
					'isFeedbackNotificationOn' => false,
					'templateTypeFeedback' => NotificationTemplateType::Animate->value,
					'isDelayedNotificationOn' => true,
					'templateTypeDelayed' => NotificationTemplateType::Animate->value,
				],
			],
			[
				'code' => 'carRent',
				'name' => Loc::getMessage('ADVERTISING_TYPE_NAME_CAR_RENT'),
				'description' => Loc::getMessage('ADVERTISING_TYPE_DESCRIPTION_CAR_RENT'),
				'resourceType' => [
					'moduleId' => ResourceType::INTERNAL_MODULE_ID,
					'code' => 'car',
					'name' => Loc::getMessage('ADVERTISING_TYPE_RESOURCE_TYPE_NAME_CAR'),
					'isInfoNotificationOn' => true,
					'templateTypeInfo' => NotificationTemplateType::Inanimate->value,
					'isConfirmationNotificationOn' => true,
					'templateTypeConfirmation' => NotificationTemplateType::Inanimate->value,
					'isReminderNotificationOn' => true,
					'templateTypeReminder' => NotificationTemplateType::Base->value,
					'isFeedbackNotificationOn' => false,
					'templateTypeFeedback' => NotificationTemplateType::Inanimate->value,
					'isDelayedNotificationOn' => true,
					'templateTypeDelayed' => NotificationTemplateType::Inanimate->value,
				],
			],
			[
				'code' => 'roomRent',
				'name' => Loc::getMessage('ADVERTISING_TYPE_NAME_ROOM_RENT'),
				'description' => Loc::getMessage('ADVERTISING_TYPE_DESCRIPTION_ROOM_RENT'),
				'resourceType' => [
					'moduleId' => ResourceType::INTERNAL_MODULE_ID,
					'code' => 'room',
					'name' => Loc::getMessage('ADVERTISING_TYPE_RESOURCE_TYPE_NAME_ROOM'),
					'isInfoNotificationOn' => true,
					'templateTypeInfo' => NotificationTemplateType::Inanimate->value,
					'isConfirmationNotificationOn' => true,
					'templateTypeConfirmation' => NotificationTemplateType::Inanimate->value,
					'isReminderNotificationOn' => true,
					'templateTypeReminder' => NotificationTemplateType::Base->value,
					'isFeedbackNotificationOn' => false,
					'templateTypeFeedback' => NotificationTemplateType::Inanimate->value,
					'isDelayedNotificationOn' => true,
					'templateTypeDelayed' => NotificationTemplateType::Inanimate->value,
				],
			],
		];
	}

	public function getByResourceTypeCode(string $resourceTypeCode): array|null
	{
		$advertisingTypes = $this->getList();

		foreach ($advertisingTypes as $advertisingType)
		{
			if ($advertisingType['resourceType']['code'] === $resourceTypeCode)
			{
				return $advertisingType['code'];
			}
		}

		return null;
	}
}

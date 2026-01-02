<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Provider\ResourceTypeProvider;
use Bitrix\Booking\Rest\V1\ErrorCode;
use Bitrix\Main;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Enum\TemplateType;

class Resource extends EntityFactory
{
	private ResourceTypeProvider $resourceTypeProvider;

	public function __construct()
	{
		$this->resourceTypeProvider = new ResourceTypeProvider();
	}


	public function validateRestFields(array $fields, int $userId = 0): Main\Result
	{
		$validationResult = new Main\Result();

		if (isset($fields['TYPE_ID']))
		{
			$resourceTypeId = (int)$fields['TYPE_ID'];

			/** @todo add access check */
			$resourceType = $this
				->resourceTypeProvider
				->getById(
					userId: $userId,
					id: $resourceTypeId,
				)
			;
			if (!$resourceType)
			{
				$validationResult->addError(
					ErrorBuilder::build(
						message: "Resource type with id $resourceTypeId does not exist",
						code: Exception::CODE_RESOURCE_TYPE_NOT_FOUND,
					)
				);
			}
		}

		$templateTypeReminderKey = 'TEMPLATE_TYPE_REMINDER';
		if (
			isset($fields[$templateTypeReminderKey])
			&& !TemplateType\TemplateTypeReminder::isValid($fields[$templateTypeReminderKey])
		)
		{
			$validationResult->addError(ErrorCode::getInvalidArgumentError($templateTypeReminderKey));
		}

		$templateTypeInfoKey = 'TEMPLATE_TYPE_INFO';
		if (
			isset($fields[$templateTypeInfoKey])
			&& !TemplateType\TemplateTypeInfo::isValid($fields[$templateTypeInfoKey])
		)
		{
			$validationResult->addError(ErrorCode::getInvalidArgumentError($templateTypeInfoKey));
		}

		$templateTypeConfirmationKey = 'TEMPLATE_TYPE_CONFIRMATION';
		if (
			isset($fields[$templateTypeConfirmationKey])
			&& !TemplateType\TemplateTypeConfirmation::isValid($fields[$templateTypeConfirmationKey])
		)
		{
			$validationResult->addError(ErrorCode::getInvalidArgumentError($templateTypeConfirmationKey));
		}

		$templateTypeFeedbackKey = 'TEMPLATE_TYPE_FEEDBACK';
		if (
			isset($fields[$templateTypeFeedbackKey])
			&& !TemplateType\TemplateTypeFeedback::isValid($fields[$templateTypeFeedbackKey])
		)
		{
			$validationResult->addError(ErrorCode::getInvalidArgumentError($templateTypeFeedbackKey));
		}

		$templateTypeDelayedKey = 'TEMPLATE_TYPE_DELAYED';
		if (
			isset($fields[$templateTypeDelayedKey])
			&& !TemplateType\TemplateTypeDelayed::isValid($fields[$templateTypeDelayedKey])
		)
		{
			$validationResult->addError(ErrorCode::getInvalidArgumentError($templateTypeDelayedKey));
		}

		return $validationResult;
	}

	public function createFromRestFields(array $fields, int $userId = 0): Entity\Resource\Resource
	{
		$resource = new Entity\Resource\Resource();

		$resource->setName((string)$fields['NAME']);

		if (isset($fields['TYPE_ID']))
		{
			$resourceType = $this
				->resourceTypeProvider
				->getById(
					userId: $userId,
					id: (int)$fields['TYPE_ID'],
				)
			;
			$resource->setType($resourceType);
		}

		if (isset($fields['DESCRIPTION']))
		{
			$resource->setDescription((string)$fields['DESCRIPTION']);
		}

		if (isset($fields['IS_INFO_NOTIFICATION_ON']))
		{
			$resource->setIsInfoNotificationOn((bool)$fields['IS_INFO_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_INFO']))
		{
			$resource->setTemplateTypeInfo((string)$fields['TEMPLATE_TYPE_INFO']);
		}

		if (isset($fields['INFO_NOTIFICATION_DELAY']))
		{
			$resource->setInfoNotificationDelay((int)$fields['INFO_NOTIFICATION_DELAY']);
		}

		if (isset($fields['IS_CONFIRMATION_NOTIFICATION_ON']))
		{
			$resource->setIsConfirmationNotificationOn((bool)$fields['IS_CONFIRMATION_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_CONFIRMATION']))
		{
			$resource->setTemplateTypeConfirmation((string)$fields['TEMPLATE_TYPE_CONFIRMATION']);
		}

		if (isset($fields['CONFIRMATION_NOTIFICATION_DELAY']))
		{
			$resource->setConfirmationNotificationDelay((int)$fields['CONFIRMATION_NOTIFICATION_DELAY']);
		}

		if (isset($fields['CONFIRMATION_NOTIFICATION_REPETITIONS']))
		{
			$resource->setConfirmationNotificationRepetitions((int)$fields['CONFIRMATION_NOTIFICATION_REPETITIONS']);
		}

		if (isset($fields['CONFIRMATION_NOTIFICATION_REPETITIONS_INTERVAL']))
		{
			$resource->setConfirmationNotificationRepetitionsInterval((int)$fields['CONFIRMATION_NOTIFICATION_REPETITIONS_INTERVAL']);
		}

		if (isset($fields['CONFIRMATION_COUNTER_DELAY']))
		{
			$resource->setConfirmationCounterDelay((int)$fields['CONFIRMATION_COUNTER_DELAY']);
		}

		if (isset($fields['IS_REMINDER_NOTIFICATION_ON']))
		{
			$resource->setIsReminderNotificationOn((bool)$fields['IS_REMINDER_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_REMINDER']))
		{
			$resource->setTemplateTypeReminder((string)$fields['TEMPLATE_TYPE_REMINDER']);
		}

		if (isset($fields['REMINDER_NOTIFICATION_DELAY']))
		{
			$resource->setReminderNotificationDelay((int)$fields['REMINDER_NOTIFICATION_DELAY']);
		}

		if (isset($fields['IS_FEEDBACK_NOTIFICATION_ON']))
		{
			$resource->setIsFeedbackNotificationOn((bool)$fields['IS_FEEDBACK_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_FEEDBACK']))
		{
			$resource->setTemplateTypeFeedback((string)$fields['TEMPLATE_TYPE_FEEDBACK']);
		}

		if (isset($fields['IS_DELAYED_NOTIFICATION_ON']))
		{
			$resource->setIsDelayedNotificationOn((bool)$fields['IS_DELAYED_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_DELAYED']))
		{
			$resource->setTemplateTypeDelayed((string)$fields['TEMPLATE_TYPE_DELAYED']);
		}

		if (isset($fields['DELAYED_NOTIFICATION_DELAY']))
		{
			$resource->setDelayedNotificationDelay((int)$fields['DELAYED_NOTIFICATION_DELAY']);
		}

		if (isset($fields['DELAYED_COUNTER_DELAY']))
		{
			$resource->setDelayedCounterDelay((int)$fields['DELAYED_COUNTER_DELAY']);
		}

		if (isset($fields['IS_MAIN']))
		{
			$resource->setMain((bool)$fields['IS_MAIN']);
		}
		else
		{
			$resource->setMain(true);
		}

		return $resource;
	}

	public function validateResourceIds(mixed $resourceIds): Main\Result
	{
		$validationResult = new Main\Result();

		if (!is_array($resourceIds))
		{
			return $validationResult->addError(
				ErrorBuilder::build(
					message: "Parameter resourceIds must be array",
					code: EXCEPTION::CODE_INVALID_ARGUMENT,
				),
			);
		}

		foreach ($resourceIds as $resourceId)
		{
			if ((int)$resourceId > 0)
			{
				continue;
			}

			return $validationResult->addError(
				ErrorBuilder::build(
					message: "Parameter resourceIds must be array of integers",
					code: EXCEPTION::CODE_INVALID_ARGUMENT,
				),
			);
		}

		return $validationResult;
	}

	public function createCollectionFromResourceIds(array $resourceIds): Entity\Resource\ResourceCollection
	{
		$resourceCollection = new Entity\Resource\ResourceCollection();

		foreach ($resourceIds as $resourceId)
		{
			$resource = new Entity\Resource\Resource();
			$resource->setId((int)$resourceId);

			$resourceCollection->add($resource);
		}

		return $resourceCollection;
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\Factory\Entity;

use Bitrix\Main;
use Bitrix\Booking\Rest\V1\ErrorCode;
use Bitrix\Booking\Rest\V1\Factory\EntityFactory;
use Bitrix\Booking\Entity;
use Bitrix\Booking\Entity\Enum\TemplateType;

class ResourceType extends EntityFactory
{

	public function validateRestFields(array $fields): Main\Result
	{
		$validationResult = new Main\Result();

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

	public function createFromRestFields(array $fields): Entity\ResourceType\ResourceType
	{
		$resourceType = new Entity\ResourceType\ResourceType();

		$resourceType->setModuleId('booking');
		$resourceType->setCode((string)$fields['CODE']);
		$resourceType->setName((string)$fields['NAME']);

		if (isset($fields['IS_INFO_NOTIFICATION_ON']))
		{
			$resourceType->setIsInfoNotificationOn((bool)$fields['IS_INFO_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_INFO']))
		{
			$resourceType->setTemplateTypeInfo((string)$fields['TEMPLATE_TYPE_INFO']);
		}

		if (isset($fields['INFO_NOTIFICATION_DELAY']))
		{
			$resourceType->setInfoNotificationDelay((int)$fields['INFO_NOTIFICATION_DELAY']);
		}

		if (isset($fields['IS_CONFIRMATION_NOTIFICATION_ON']))
		{
			$resourceType->setIsConfirmationNotificationOn((bool)$fields['IS_CONFIRMATION_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_CONFIRMATION']))
		{
			$resourceType->setTemplateTypeConfirmation((string)$fields['TEMPLATE_TYPE_CONFIRMATION']);
		}

		if (isset($fields['CONFIRMATION_NOTIFICATION_DELAY']))
		{
			$resourceType->setConfirmationNotificationDelay((int)$fields['CONFIRMATION_NOTIFICATION_DELAY']);
		}

		if (isset($fields['CONFIRMATION_NOTIFICATION_REPETITIONS']))
		{
			$resourceType->setConfirmationNotificationRepetitions((int)$fields['CONFIRMATION_NOTIFICATION_REPETITIONS']);
		}

		if (isset($fields['CONFIRMATION_NOTIFICATION_REPETITIONS_INTERVAL']))
		{
			$resourceType->setConfirmationNotificationRepetitionsInterval((int)$fields['CONFIRMATION_NOTIFICATION_REPETITIONS_INTERVAL']);
		}

		if (isset($fields['CONFIRMATION_COUNTER_DELAY']))
		{
			$resourceType->setConfirmationCounterDelay((int)$fields['CONFIRMATION_COUNTER_DELAY']);
		}

		if (isset($fields['IS_REMINDER_NOTIFICATION_ON']))
		{
			$resourceType->setIsReminderNotificationOn((bool)$fields['IS_REMINDER_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_REMINDER']))
		{
			$resourceType->setTemplateTypeReminder((string)$fields['TEMPLATE_TYPE_REMINDER']);
		}

		if (isset($fields['REMINDER_NOTIFICATION_DELAY']))
		{
			$resourceType->setReminderNotificationDelay((int)$fields['REMINDER_NOTIFICATION_DELAY']);
		}

		if (isset($fields['IS_FEEDBACK_NOTIFICATION_ON']))
		{
			$resourceType->setIsFeedbackNotificationOn((bool)$fields['IS_FEEDBACK_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_FEEDBACK']))
		{
			$resourceType->setTemplateTypeFeedback((string)$fields['TEMPLATE_TYPE_FEEDBACK']);
		}

		if (isset($fields['IS_DELAYED_NOTIFICATION_ON']))
		{
			$resourceType->setIsDelayedNotificationOn((bool)$fields['IS_DELAYED_NOTIFICATION_ON']);
		}

		if (isset($fields['TEMPLATE_TYPE_DELAYED']))
		{
			$resourceType->setTemplateTypeDelayed((string)$fields['TEMPLATE_TYPE_DELAYED']);
		}

		if (isset($fields['DELAYED_NOTIFICATION_DELAY']))
		{
			$resourceType->setDelayedNotificationDelay((int)$fields['DELAYED_NOTIFICATION_DELAY']);
		}

		if (isset($fields['DELAYED_COUNTER_DELAY']))
		{
			$resourceType->setDelayedCounterDelay((int)$fields['DELAYED_COUNTER_DELAY']);
		}


		return $resourceType;
	}
}

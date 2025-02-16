import { ResourceTypeModel } from 'booking.model.resource-types';
import { ResourceTypeDto } from './types';

export function mapDtoToModel(resourceDto: ResourceTypeDto): ResourceTypeModel
{
	return {
		id: resourceDto.id,
		moduleId: resourceDto.moduleId,
		name: resourceDto.name,
		code: resourceDto.code,
		isConfirmationNotificationOn: resourceDto.isConfirmationNotificationOn,
		isFeedbackNotificationOn: resourceDto.isFeedbackNotificationOn,
		isInfoNotificationOn: resourceDto.isInfoNotificationOn,
		isDelayedNotificationOn: resourceDto.isDelayedNotificationOn,
		isReminderNotificationOn: resourceDto.isReminderNotificationOn,
		templateTypeConfirmation: resourceDto.templateTypeConfirmation,
		templateTypeFeedback: resourceDto.templateTypeFeedback,
		templateTypeInfo: resourceDto.templateTypeInfo,
		templateTypeDelayed: resourceDto.templateTypeDelayed,
		templateTypeReminder: resourceDto.templateTypeReminder,
	};
}

export function mapModelToDto(resourceType: ResourceTypeModel): ResourceTypeDto
{
	return {
		id: resourceType.id,
		moduleId: resourceType.moduleId,
		name: resourceType.name,
		code: resourceType.code,
		isConfirmationNotificationOn: resourceType.isConfirmationNotificationOn,
		isFeedbackNotificationOn: resourceType.isFeedbackNotificationOn,
		isInfoNotificationOn: resourceType.isInfoNotificationOn,
		isDelayedNotificationOn: resourceType.isDelayedNotificationOn,
		isReminderNotificationOn: resourceType.isReminderNotificationOn,
		templateTypeConfirmation: resourceType.templateTypeConfirmation,
		templateTypeFeedback: resourceType.templateTypeFeedback,
		templateTypeInfo: resourceType.templateTypeInfo,
		templateTypeDelayed: resourceType.templateTypeDelayed,
		templateTypeReminder: resourceType.templateTypeReminder,
	};
}

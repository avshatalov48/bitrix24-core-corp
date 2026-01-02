import { ResourceTypeModel } from 'booking.model.resource-types';
import { ResourceTypeDto } from './types';

export function mapDtoToModel(resourceTypeDto: ResourceTypeDto): ResourceTypeModel
{
	return {
		id: resourceTypeDto.id,
		moduleId: resourceTypeDto.moduleId,
		name: resourceTypeDto.name,
		code: resourceTypeDto.code,
		resourcesCnt: resourceTypeDto.resourcesCnt,

		// info
		isInfoNotificationOn: resourceTypeDto.isInfoNotificationOn,
		templateTypeInfo: resourceTypeDto.templateTypeInfo,
		infoNotificationDelay: resourceTypeDto.infoNotificationDelay,

		// confirmation
		isConfirmationNotificationOn: resourceTypeDto.isConfirmationNotificationOn,
		templateTypeConfirmation: resourceTypeDto.templateTypeConfirmation,
		confirmationNotificationDelay: resourceTypeDto.confirmationNotificationDelay,
		confirmationNotificationRepetitions: resourceTypeDto.confirmationNotificationRepetitions,
		confirmationNotificationRepetitionsInterval: resourceTypeDto.confirmationNotificationRepetitionsInterval,
		confirmationCounterDelay: resourceTypeDto.confirmationCounterDelay,

		// reminder
		isReminderNotificationOn: resourceTypeDto.isReminderNotificationOn,
		templateTypeReminder: resourceTypeDto.templateTypeReminder,
		reminderNotificationDelay: resourceTypeDto.reminderNotificationDelay,

		// delayed
		isDelayedNotificationOn: resourceTypeDto.isDelayedNotificationOn,
		templateTypeDelayed: resourceTypeDto.templateTypeDelayed,
		delayedNotificationDelay: resourceTypeDto.delayedNotificationDelay,
		delayedCounterDelay: resourceTypeDto.delayedCounterDelay,

		// feedback
		isFeedbackNotificationOn: resourceTypeDto.isFeedbackNotificationOn,
		templateTypeFeedback: resourceTypeDto.templateTypeFeedback,
	};
}

export function mapModelToDto(resourceType: ResourceTypeModel): ResourceTypeDto
{
	return {
		id: resourceType.id,
		moduleId: resourceType.moduleId,
		name: resourceType.name,
		code: resourceType.code,
		resourcesCnt: resourceType.resourcesCnt,

		// info
		isInfoNotificationOn: resourceType.isInfoNotificationOn,
		templateTypeInfo: resourceType.templateTypeInfo,
		infoNotificationDelay: resourceType.infoNotificationDelay,

		// confirmation
		isConfirmationNotificationOn: resourceType.isConfirmationNotificationOn,
		templateTypeConfirmation: resourceType.templateTypeConfirmation,
		confirmationNotificationDelay: resourceType.confirmationNotificationDelay,
		confirmationNotificationRepetitions: resourceType.confirmationNotificationRepetitions,
		confirmationNotificationRepetitionsInterval: resourceType.confirmationNotificationRepetitionsInterval,
		confirmationCounterDelay: resourceType.confirmationCounterDelay,

		// reminder
		isReminderNotificationOn: resourceType.isReminderNotificationOn,
		templateTypeReminder: resourceType.templateTypeReminder,
		reminderNotificationDelay: resourceType.reminderNotificationDelay,

		// delayed
		isDelayedNotificationOn: resourceType.isDelayedNotificationOn,
		templateTypeDelayed: resourceType.templateTypeDelayed,
		delayedNotificationDelay: resourceType.delayedNotificationDelay,
		delayedCounterDelay: resourceType.delayedCounterDelay,

		// feedback
		isFeedbackNotificationOn: resourceType.isFeedbackNotificationOn,
		templateTypeFeedback: resourceType.templateTypeFeedback,
	};
}

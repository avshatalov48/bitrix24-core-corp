import type { ResourceModel } from 'booking.model.resources';
import type { ResourceDto } from './types';

export function mapDtoToModel(resourceDto: ResourceDto): ResourceModel
{
	return {
		id: resourceDto.id,
		typeId: resourceDto.type.id,
		name: resourceDto.name,
		description: resourceDto.description,
		slotRanges: resourceDto.slotRanges.map((slotRange) => ({
			...slotRange,
			weekDays: Object.values(slotRange.weekDays),
		})),
		counter: resourceDto.counter,
		isMain: resourceDto.isMain,
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
		createdBy: resourceDto.createdBy,
		createdAt: resourceDto.createdAt,
		updatedAt: resourceDto.updatedAt,
	};
}

export function mapModelToDto(resource: ResourceModel): ResourceDto
{
	return {
		id: resource.id,
		type: {
			id: resource.typeId,
		},
		name: resource.name,
		description: resource.description,
		slotRanges: resource.slotRanges,
		counter: null,
		isMain: resource.isMain,
		isConfirmationNotificationOn: resource.isConfirmationNotificationOn,
		isFeedbackNotificationOn: resource.isFeedbackNotificationOn,
		isInfoNotificationOn: resource.isInfoNotificationOn,
		isDelayedNotificationOn: resource.isDelayedNotificationOn,
		isReminderNotificationOn: resource.isReminderNotificationOn,
		templateTypeConfirmation: resource.templateTypeConfirmation,
		templateTypeFeedback: resource.templateTypeFeedback,
		templateTypeInfo: resource.templateTypeInfo,
		templateTypeDelayed: resource.templateTypeDelayed,
		templateTypeReminder: resource.templateTypeReminder,
		createdBy: null,
		createdAt: null,
		updatedAt: null,
	};
}

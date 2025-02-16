import { toRaw } from 'ui.vue3';
import { Core } from 'booking.core';
import { ResourceModel } from './types';

export function getResource(resourceId: number): ResourceModel
{
	const store = Core.getStore();
	const resource = store.getters['resources/getById'](resourceId);

	return structuredClone(toRaw(resource));
}

export function getEmptyResource(): ResourceModel
{
	return {
		id: null,
		typeId: null,
		name: '',
		description: null,
		slotRanges: [],
		counter: null,
		isMain: true,
		isConfirmationNotificationOn: false,
		isFeedbackNotificationOn: false,
		isInfoNotificationOn: false,
		isDelayedNotificationOn: false,
		isReminderNotificationOn: false,
		templateTypeConfirmation: 'animate',
		templateTypeFeedback: 'animate',
		templateTypeInfo: 'animate',
		templateTypeDelayed: 'animate',
		templateTypeReminder: 'base',
		createdBy: 0,
		createdAt: 0,
		updatedAt: null,
	};
}

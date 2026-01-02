import type { SlotRange } from 'booking.model.resources';
import type { AdvertisingResourceType, NotificationsSettings } from './types';

export class ResourceCreationWizardDataExtractor
{
	#data;

	constructor(data)
	{
		this.#data = data;
	}

	getAdvertisingTypes(): AdvertisingResourceType[]
	{
		return this.#data.advertisingResourceTypes ?? [];
	}

	getNotifications(): NotificationsSettings
	{
		return this.#data.notificationsSettings.notifications;
	}

	getSenders(): NotificationsSettings
	{
		return this.#data.notificationsSettings.senders;
	}

	getCompanyScheduleSlots(): SlotRange[]
	{
		return this.#data.companyScheduleSlots;
	}

	isCompanyScheduleAccess(): boolean
	{
		return Boolean(this.#data.isCompanyScheduleAccess);
	}

	getCompanyScheduleUrl(): string
	{
		return this.#data.companyScheduleUrl;
	}

	getWeekStart(): string
	{
		return this.#data.weekStart;
	}

	isChannelChoiceAvailable(): boolean
	{
		return this.#data.isChannelChoiceAvailable;
	}
}

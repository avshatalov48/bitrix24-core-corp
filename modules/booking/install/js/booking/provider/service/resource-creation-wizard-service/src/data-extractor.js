import type { SlotRange } from 'booking.model.resources';
import type { AdvertisingResourceType, NotificationsSettings } from './types';

export class ResourceCreationWizardDataExtractor
{
	#data;

	constructor(data)
	{
		this.#data = data;
	}

	getAdvertisingResourceTypes(): AdvertisingResourceType[]
	{
		return this.#data.advertisingResourceTypes ?? [];
	}

	getNotificationsSettings(): NotificationsSettings
	{
		return this.#data.notificationsSettings;
	}

	getCompanyScheduleSlots(): SlotRange[]
	{
		return this.#data.companyScheduleSlots;
	}

	isCompanyScheduleAccess(): boolean
	{
		return Boolean(this.#data.isCompanyScheduleAccess);
	}

	getWeekStart(): string
	{
		return this.#data.weekStart;
	}
}

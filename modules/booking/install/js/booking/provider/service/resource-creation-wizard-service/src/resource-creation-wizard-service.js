import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';
import { Model } from 'booking.const';
import { ResourceCreationWizardDataExtractor } from './data-extractor';

class ResourceCreationWizardService
{
	async fetchData(): Promise<void>
	{
		await this.loadData();
	}

	async loadData()
	{
		try
		{
			const api = new ApiClient();
			const data = await api.post('ResourceWizard.get', {});

			const extractor = new ResourceCreationWizardDataExtractor(data);
			const store = Core.getStore();

			const { notifications, senders } = extractor.getNotificationsSettings();

			await Promise.all([
				store.dispatch(
					`${Model.ResourceCreationWizard}/setAdvertisingResourceTypes`,
					extractor.getAdvertisingResourceTypes(),
				),
				store.dispatch(`${Model.Notifications}/upsertMany`, notifications),
				store.dispatch(`${Model.Notifications}/upsertManySenders`, senders),
				store.dispatch(
					`${Model.ResourceCreationWizard}/setCompanyScheduleSlots`,
					extractor.getCompanyScheduleSlots(),
				),
				store.dispatch(
					`${Model.ResourceCreationWizard}/setCompanyScheduleAccess`,
					extractor.isCompanyScheduleAccess(),
				),
				store.dispatch(
					`${Model.ResourceCreationWizard}/setWeekStart`,
					extractor.getWeekStart(),
				),
			]);
		}
		catch (error)
		{
			console.error('ResourceCreationWizardService load data error', error);
		}
	}
}

export const resourceCreationWizardService = new ResourceCreationWizardService();

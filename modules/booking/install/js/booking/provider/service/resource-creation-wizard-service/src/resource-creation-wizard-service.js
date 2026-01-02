import type { Store } from 'ui.vue3.vuex';

import { Core } from 'booking.core';
import { Model, Option } from 'booking.const';
import { apiClient } from 'booking.lib.api-client';
import { optionService } from 'booking.provider.service.option-service';

import { ResourceCreationWizardDataExtractor } from './data-extractor';

class ResourceCreationWizardService
{
	async fetchData(): Promise<void>
	{
		await this.loadData();
	}

	async loadData(): Promise<void>
	{
		try
		{
			const data = await apiClient.post('ResourceWizard.get', {});

			const extractor = new ResourceCreationWizardDataExtractor(data);
			const wizardModel = Model.ResourceCreationWizard;

			await Promise.all([
				this.$store.dispatch(`${wizardModel}/setAdvertisingTypes`, extractor.getAdvertisingTypes()),
				this.$store.dispatch(`${wizardModel}/setCompanyScheduleSlots`, extractor.getCompanyScheduleSlots()),
				this.$store.dispatch(`${wizardModel}/setCompanyScheduleAccess`, extractor.isCompanyScheduleAccess()),
				this.$store.dispatch(`${wizardModel}/setCompanyScheduleUrl`, extractor.getCompanyScheduleUrl()),
				this.$store.dispatch(`${wizardModel}/setWeekStart`, extractor.getWeekStart()),
				this.$store.dispatch(`${wizardModel}/setIsChannelChoiceAvailable`, extractor.isChannelChoiceAvailable()),
				this.$store.dispatch(`${Model.Notifications}/upsertMany`, extractor.getNotifications()),
				this.$store.dispatch(`${Model.Notifications}/upsertManySenders`, extractor.getSenders()),
			]);
		}
		catch (error)
		{
			console.error('ResourceCreationWizardService loadData error', error);
		}
	}

	async updateNotificationExpanded(type: string, isExpanded: boolean): Promise<void>
	{
		await this.$store.dispatch(`${Model.Notifications}/setIsExpanded`, { type, isExpanded });

		const notifications = Object.fromEntries(this.$store.getters[`${Model.Notifications}/get`]
			.map((notification) => [notification.type, notification.isExpanded]));

		try
		{
			await optionService.set(Option.NotificationsExpanded, JSON.stringify(notifications));
		}
		catch (error)
		{
			await this.$store.dispatch(`${Model.Notifications}/setIsExpanded`, { type, isExpanded: !isExpanded });

			console.error('ResourceCreationWizardService updateNotificationExpanded error', error);
		}
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const resourceCreationWizardService = new ResourceCreationWizardService();

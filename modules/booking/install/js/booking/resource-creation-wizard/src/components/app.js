import { mapState, mapGetters, mapActions, mapMutations } from 'ui.vue3.vuex';
import { resourceCreationWizardService } from 'booking.provider.service.resource-creation-wizard-service';
import { ResourceCreationWizardLayout } from './layout/layout';
import { ResourceCreationWizardHeader } from './header/header';
import { ResourceCreationWizardFooter } from './footer/footer';
import { ResourceCategoryCard } from './resource-category-card/resource-category-card';
import { ResourceSettingsCard } from './resource-settings-card/resource-settings-card';
import { ResourceNotificationCard } from './resource-notification-card/resource-notification-card';

export const App = {
	name: 'ResourceCreationWizardApp',
	components: {
		ResourceCreationWizardLayout,
		ResourceCreationWizardHeader,
		ResourceCreationWizardFooter,
		ResourceCategoryCard,
		ResourceSettingsCard,
		ResourceNotificationCard,
	},
	data(): { init: boolean }
	{
		return {
			init: true,
		};
	},
	computed: {
		...mapState({
			step: (state) => state['resource-creation-wizard'].step,
			fetching: (state) => state['resource-creation-wizard'].fetching,
		}),
		...mapGetters('resource-creation-wizard', [
			'invalidCurrentCard',
			'resourceId',
		]),
		currentView(): string
		{
			const step = this.step || 1;

			switch (step)
			{
				case 2:
					return 'ResourceSettingsCard';
				case 3:
					return 'ResourceNotificationCard';
				default:
					return 'ResourceCategoryCard';
			}
		},
		isEditForm(): boolean
		{
			return this.resourceId !== null;
		},
	},
	async beforeMount()
	{
		await this.initApp();
	},
	methods: {
		...mapActions(
			'resource-creation-wizard',
			['initState'],
		),
		...mapMutations(
			'resource-creation-wizard',
			[
				'prevStep',
				'updateFetching',
				'setGlobalSchedule',
			],
		),
		async initApp()
		{
			await this.initState();
			await this.loadWizardData();
			this.init = false;
		},
		async loadWizardData()
		{
			try
			{
				this.updateFetching(true);
				await resourceCreationWizardService.fetchData();
			}
			catch (error)
			{
				console.error('Loading wizard data error', error);
			}
			finally
			{
				this.updateFetching(false);
				if (!this.isEditForm)
				{
					this.setGlobalSchedule(true);
				}
			}
		},
	},
	template: `
		<div id="booking-resource-creation-wizard">
			<ResourceCreationWizardLayout :loading="fetching" :step>
				<template #header>
					<ResourceCreationWizardHeader/>
				</template>
				<template v-if="!init">
					<component :is="currentView"/>
				</template>
				<template #footer>
					<ResourceCreationWizardFooter :step/>
				</template>
			</ResourceCreationWizardLayout>
		</div>
	`,
};

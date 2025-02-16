import { limit } from 'booking.lib.limit';
import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.actions';

import { AhaMoment, HelpDesk, Model } from 'booking.const';
import { ahaMoments } from 'booking.lib.aha-moments';
import { ResourceCreationWizard } from 'booking.resource-creation-wizard';
import './add-resource-button.css';

export const AddResourceButton = {
	data(): Object
	{
		return {
			IconSet,
		};
	},
	computed: mapGetters({
		isLoaded: `${Model.Interface}/isLoaded`,
		resourcesIds: `${Model.Interface}/resourcesIds`,
		isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
	}),
	methods: {
		addResource(): void
		{
			if (!this.isFeatureEnabled)
			{
				limit.show();

				return;
			}

			new ResourceCreationWizard().open();
		},
		async showAhaMoment(): Promise<void>
		{
			await ahaMoments.show({
				id: 'booking-add-resource',
				title: this.loc('BOOKING_AHA_ADD_RESOURCES_TITLE'),
				text: this.loc('BOOKING_AHA_ADD_RESOURCES_TEXT'),
				article: HelpDesk.AhaAddResource,
				target: this.$refs.button,
			});

			ahaMoments.setShown(AhaMoment.AddResource);
		},
	},
	watch: {
		isLoaded(): void
		{
			if (ahaMoments.shouldShow(AhaMoment.AddResource))
			{
				void this.showAhaMoment();
			}
		},
	},
	components: {
		Icon,
	},
	template: `
		<div
			class="booking-booking-header-add-resource"
			ref="button"
			@click="addResource"
		>
			<div
				class="booking-booking-header-add-resource-icon"
				:class="{'--lock': !isFeatureEnabled}"
			>
				<Icon v-if="isFeatureEnabled" :name="IconSet.PLUS_20"/>
				<Icon v-else :name="IconSet.LOCK" :size="16"/>
			</div>
			<div class="booking-booking-header-add-resource-text">
				{{ loc('BOOKING_BOOKING_ADD_RESOURCE') }}
			</div>
		</div>
	`,
};

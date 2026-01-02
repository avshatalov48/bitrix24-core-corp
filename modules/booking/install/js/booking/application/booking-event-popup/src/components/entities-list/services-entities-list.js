import { h } from 'ui.vue3';
import { mapGetters } from 'ui.vue3.vuex';
import { Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'booking.const';

import { EntitiesList } from './entities-list';

// @vue/component
export const ServicesEntitiesList = {
	name: 'ServicesEntitiesList',
	components: {
		EntitiesList,
	},
	computed: {
		...mapGetters({
			services: `${Model.BookingInfo}/services`,
		}),
	},
	render(): ?Object
	{
		if (this.services.length === 0)
		{
			return null;
		}

		const hasNoAccess = this.services.some(({ permissions }) => !permissions.read);

		return h(EntitiesList, {
			title: this.loc('BOOKING_EVENT_POPUP_SERVICES_TITLE'),
			iconName: Outline.THREE_PERSONS,
			entities: hasNoAccess ? [] : this.services,
			hasNoAccess,
		});
	},
};

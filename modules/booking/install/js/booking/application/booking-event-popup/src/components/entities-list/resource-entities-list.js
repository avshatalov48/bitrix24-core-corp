import { h } from 'ui.vue3';
import { mapGetters } from 'ui.vue3.vuex';
import { Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { Model } from 'booking.const';

import { EntitiesList } from './entities-list';

// @vue/component
export const ResourceEntitiesList = {
	name: 'ResourceEntitiesList',
	components: {
		EntitiesList,
	},
	computed: {
		...mapGetters({
			resources: `${Model.BookingInfo}/resources`,
		}),
	},
	render(): ?Object
	{
		if (this.resources.length === 0)
		{
			return null;
		}

		const hasNoAccess = this.resources.some(({ permissions }) => !permissions.read);

		return h(EntitiesList, {
			title: this.loc('BOOKING_EVENT_POPUP_RESOURCES_TITLE'),
			iconName: Outline.PRODUCT,
			entities: hasNoAccess ? [] : this.resources,
			hasNoAccess,
		});
	},
};

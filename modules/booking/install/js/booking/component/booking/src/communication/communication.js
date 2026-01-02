// @vue/component

import { hint } from 'ui.vue3.directives.hint';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import './communication.css';

export const Communication = {
	name: 'Communication',
	directives: { hint },
	components: {
		Icon,
	},
	setup(): Object
	{
		return {
			IconSet,
		};
	},
	computed: {
		soonHint(): Object
		{
			return {
				text: this.loc('BOOKING_BOOKING_SOON_HINT'),
				popupOptions: {
					offsetLeft: -60,
				},
			};
		},
	},
	template: `
		<div v-hint="soonHint" class="booking--booking-base-communication">
			<Icon :name="IconSet.TELEPHONY_HANDSET_1"/>
			<Icon :name="IconSet.CHATS_2"/>
		</div>
	`,
};

import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
// eslint-disable-next-line no-unused-vars
import type { WaitListItemModel } from 'booking.model.wait-list';

import { WaitListItemActionsPopup } from './actions-popup/actions-popup';
import './actions.css';

// @vue/component
export const WaitListItemActions = {
	name: 'WaitListItemActions',
	components: {
		WaitListItemActionsPopup,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		waitListItem: {
			type: Object,
			required: true,
		},
	},
	data(): { showPopup: boolean }
	{
		return {
			showPopup: false,
		};
	},
	computed: {
		...mapGetters({
			editingWaitListItemId: `${Model.Interface}/editingWaitListItemId`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
		}),
	},
	mounted(): void
	{
		if (this.isEditingBookingMode && this.editingWaitListItemId === this.waitListItem.id)
		{
			this.showPopup = true;
		}
	},
	methods: {
		clickHandler(): void
		{
			this.showPopup = !this.showPopup;
		},
	},
	template: `
		<div
			ref="node"
			class="booking-booking-booking-actions booking--wait-list-item-actions"
			data-element="booking-wait-list-item-actions-button"
			:data-id="waitListItem.id"
			@click="clickHandler"
		>
			<div class="booking-booking-booking-actions-inner">
				<div class="ui-icon-set --chevron-down"></div>
			</div>
		</div>
		<WaitListItemActionsPopup
			v-if="showPopup"
			:waitListItem
			:bindElement="this.$refs.node"
			@close="showPopup = false"
		/>
	`,
};

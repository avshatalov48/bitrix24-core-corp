import { Loc } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { Model } from 'booking.const';

import { Statistics } from '../statistics/statistics';

export const Clients = {
	data(): Object
	{
		return {
			increasedValue: 0,
		};
	},
	computed: {
		...mapGetters({
			totalNewClientsToday: `${Model.Interface}/totalNewClientsToday`,
			totalClients: `${Model.Interface}/totalClients`,
		}),
		popupId(): string
		{
			return 'booking-booking-after-title-clients-popup';
		},
		title(): string
		{
			return this.loc('BOOKING_BOOKING_AFTER_TITLE_CLIENTS_POPUP_TITLE');
		},
		rows(): { title: string, value: string }[]
		{
			return [
				{
					title: this.loc('BOOKING_BOOKING_AFTER_TITLE_CLIENTS_POPUP_TOTAL_CLIENTS_TODAY'),
					value: `+${this.totalNewClientsToday}`,
				},
				{
					title: this.loc('BOOKING_BOOKING_AFTER_TITLE_CLIENTS_POPUP_TOTAL_CLIENTS'),
					value: `<div>${this.totalClients}</div>`,
				},
			];
		},
		button(): Object
		{
			return {
				title: this.loc('BOOKING_BOOKING_CLIENTS_LIST'),
				click: () => BX.SidePanel.Instance.open('/crm/contact/list/'),
			};
		},
		clientsProfitFormatted(): string
		{
			return Loc.getMessagePlural('BOOKING_BOOKING_PLUS_NUM_CLIENTS', this.totalNewClientsToday, {
				'#NUM#': this.totalNewClientsToday,
			});
		},
		increasedValueFormatted(): string
		{
			return Loc.getMessagePlural('BOOKING_BOOKING_PLUS_NUM_CLIENTS', this.increasedValue, {
				'#NUM#': this.increasedValue,
			});
		},
	},
	watch: {
		totalNewClientsToday(newValue: number, previousValue: number): void
		{
			this.increasedValue = newValue - previousValue;
		},
	},
	components: {
		Statistics,
	},
	template: `
		<Statistics
			:value="totalNewClientsToday"
			:valueFormatted="clientsProfitFormatted"
			:increasedValue="increasedValue"
			:increasedValueFormatted="increasedValueFormatted"
			:popupId="popupId"
			:title="title"
			:rows="rows"
			:button="button"
		/>
	`,
};

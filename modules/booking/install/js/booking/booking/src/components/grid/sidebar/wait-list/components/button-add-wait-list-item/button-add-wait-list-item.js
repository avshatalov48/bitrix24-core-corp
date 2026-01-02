import { Text } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';

import { Model } from 'booking.const';
import { DealData } from 'booking.model.bookings';
import { ClientData } from 'booking.model.clients';
import { WaitListAnalytics } from 'booking.lib.analytics';
import { Button, ButtonColor, ButtonSize } from 'booking.component.button';
import { waitListService } from 'booking.provider.service.wait-list-service';

import './button-add-wait-list-item.css';

// @vue/component
export const ButtonAddWaitListItem = {
	name: 'ButtonAddWaitListItem',
	components: {
		Button,
	},
	setup(): Object
	{
		return {
			ButtonColor,
			ButtonSize,
		};
	},
	computed: {
		...mapGetters({
			embedItems: `${Model.Interface}/embedItems`,
			isLoaded: `${Model.Interface}/isLoaded`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			waitListItems: `${Model.WaitList}/get`,
			waitListExpanded: `${Model.Interface}/waitListExpanded`,
		}),
		addButtonText(): string
		{
			return this.isLoaded && this.waitListItems.length === 0
				? this.loc('BOOKING_BOOKING_WAIT_LIST_ADD').replace('[plus]', '+')
				: '+';
		},
		disabled(): boolean
		{
			return this.isEditingBookingMode || !this.isLoaded;
		},
		clients(): ClientData[]
		{
			const clients = this.embedItems.filter((item: DealData) => {
				return item.entityTypeId === 'CONTACT' || item.entityTypeId === 'COMPANY';
			});

			return clients.map((item: DealData) => {
				return {
					id: item.value,
					type: {
						code: item.entityTypeId,
						module: item.moduleId,
					},
				};
			});
		},
	},
	methods: {
		async addWaitListItem(): Promise<void>
		{
			if (this.disabled || !this.isLoaded)
			{
				return;
			}

			if (!this.waitListExpanded)
			{
				await this.expandWaitListWidget();
			}

			const now = Date.now();
			const id = `tmp-id-${now}-${Text.getRandom(4)}`;
			await this.addCreatedFromEmbedWaitListItem(id);
			const result = await waitListService.add({
				id,
				clients: this.clients,
				externalData: this.embedItems,
				createdAt: now,
				updatedAt: now,
			});

			if (result.success && result.waitListItem)
			{
				WaitListAnalytics.sendAddBooking();
				await this.addCreatedFromEmbedWaitListItem(result.waitListItem.id);
			}
		},
		async addCreatedFromEmbedWaitListItem(id: number | string): Promise<void>
		{
			await this.$store.dispatch(`${Model.Interface}/addCreatedFromEmbedWaitListItem`, id);
		},
		async expandWaitListWidget(): Promise<void>
		{
			await this.$store.commit('interface/setWaitListExpanded', true);
		},
	},
	template: `
		<Button
			class="booking-wait-list-add"
			:text="addButtonText"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.SECONDARY_LIGHT"
			:disabled
			:round="true"
			@click="addWaitListItem"
		/>
	`,
};

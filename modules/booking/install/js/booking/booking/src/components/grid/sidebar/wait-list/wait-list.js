import { mapGetters } from 'ui.vue3.vuex';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.actions';

import { Model, Option } from 'booking.const';
import { BookingAnalytics } from 'booking.lib.analytics';
import { Button, ButtonColor, ButtonSize } from 'booking.component.button';
import { waitListService } from 'booking.provider.service.wait-list-service';
import { isRealId } from 'booking.lib.is-real-id';
import { optionService } from 'booking.provider.service.option-service';

import { WaitListLayout } from './components/wait-list-layout/wait-list-layout';
import { WaitListGroups } from './components/wait-list-groups/wait-list-groups';
import { WaitListItem } from './components/wait-list-item/wait-list-item';
import { ButtonAddWaitListItem } from './components/button-add-wait-list-item/button-add-wait-list-item';
import './wait-list.css';

// @vue/component
export const WaitList = {
	name: 'WaitList',
	components: {
		Button,
		Icon,
		ButtonAddWaitListItem,
		WaitListLayout,
		WaitListGroups,
		WaitListItem,
	},
	data(): Object
	{
		return {
			IconSet,
			ButtonColor,
			ButtonSize,
		};
	},
	computed: {
		...mapGetters({
			getBookingById: `${Model.Bookings}/getById`,
			waitListItems: `${Model.WaitList}/get`,
			waitListExpanded: `${Model.Interface}/waitListExpanded`,
			draggedBookingId: `${Model.Interface}/draggedBookingId`,
			draggedBookingResourceId: `${Model.Interface}/draggedBookingResourceId`,
			editingBookingId: `${Model.Interface}/editingBookingId`,
			editingWaitListItemId: `${Model.Interface}/editingWaitListItemId`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			isBookingCreatedFromEmbed: `${Model.Interface}/isBookingCreatedFromEmbed`,
			embedItems: `${Model.Interface}/embedItems`,
			getResourceById: `${Model.Resources}/getById`,
		}),
		isEmpty(): boolean
		{
			return this.waitListItems.length === 0;
		},
		isAvailableToDrop(): boolean
		{
			return Boolean((
				this.draggedBookingId
				&& !this.getResourceById(this.draggedBookingResourceId)?.isDeleted
			));
		},
		showEmptyState(): boolean
		{
			return this.isEmpty && !this.draggedBookingId;
		},
		embedEditingMode(): boolean
		{
			return (
				this.isFeatureEnabled
				&& (
					this.editingBookingId > 0
					|| this.editingWaitListItemId > 0
					|| (this.embedItems?.length ?? 0) > 0
				)
			);
		},
	},
	watch: {
		// wait list can be expanded externally (currently in ButtonAddWaitListItem)
		async waitListExpanded(newValue, oldValue): void
		{
			if (newValue !== oldValue)
			{
				await optionService.setBool(Option.WaitListExpanded, newValue);
			}
		},
	},
	methods: {
		async onMouseUp(): Promise<void>
		{
			if (!this.draggedBookingId)
			{
				return;
			}

			const bookingId = this.draggedBookingId;
			await this.$store.dispatch(`${Model.Interface}/addDeletingBooking`, bookingId);

			if (isRealId(bookingId))
			{
				if (this.editingBookingId === bookingId)
				{
					await this.setEditingWaitListItemId(bookingId);
				}

				const booking = this.getBookingById(bookingId);
				const result = await waitListService.createFromBooking(
					bookingId,
					{
						id: bookingId,
						clients: booking.clients,
						primaryClient: booking.primaryClient,
						externalData: booking.externalData,
						createdAt: Date.now(),
						updatedAt: Date.now(),
					},
				);

				if (result.success && result.waitListItem)
				{
					BookingAnalytics.sendAddWaitListItem();
					if (this.editingWaitListItemId === bookingId)
					{
						await this.setEditingWaitListItemId(result.waitListItem.id);
					}
				}
			}
			else
			{
				if (this.isBookingCreatedFromEmbed(bookingId))
				{
					await this.addCreatedFromEmbedWaitListItem(bookingId);
				}

				const result = await waitListService.add({
					id: bookingId,
					clients: [],
					createdAt: Date.now(),
					updatedAt: Date.now(),
				});

				if (result.success && result.waitListItem)
				{
					await this.addCreatedFromEmbedWaitListItem(result.waitListItem.id);
				}
			}
		},
		async addCreatedFromEmbedWaitListItem(id: number | string): Promise<void>
		{
			await this.$store.dispatch(`${Model.Interface}/addCreatedFromEmbedWaitListItem`, id);
		},
		async setEditingWaitListItemId(id: number | string): Promise<void>
		{
			await Promise.all([
				this.$store.dispatch(`${Model.Interface}/setEditingWaitListItemId`, id),
				this.$store.dispatch(`${Model.Interface}/setEditingBookingId`, null),
			]);
		},
		async collapseToggle(): Promise<void>
		{
			await this.$store.dispatch(`${Model.Interface}/setWaitListExpanded`, !this.waitListExpanded);
		},
	},
	template: `
		<WaitListLayout
			:dragging="isAvailableToDrop"
			:showEmptyState
			:expanded="waitListExpanded"
			:waitListItemsCount="waitListItems.length"
			:waitListClass="{
				'--expand': waitListExpanded,
				'embed-editing-mode': embedEditingMode,
			}"
			@mouseUp="onMouseUp"
		>
			<template #header>
				<ButtonAddWaitListItem/>
				<div class="booking-sidebar-button" @click="collapseToggle">
					<Icon :name="waitListExpanded ? IconSet.COLLAPSE : IconSet.EXPAND_1"/>
				</div>
			</template>
			<template #waitlist>
				<WaitListGroups>
					<template #item="{ item }">
						<WaitListItem :item/>
					</template>
				</WaitListGroups>
			</template>
		</WaitListLayout>
	`,
};

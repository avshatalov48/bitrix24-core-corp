import { mapGetters } from 'ui.vue3.vuex';

import {
	BookingBase,
	Communication,
	DisabledPopup,
} from 'booking.component.booking';
import { Model, DraggedElementKind } from 'booking.const';
// eslint-disable-next-line no-unused-vars
import type { WaitListItemModel } from 'booking.model.wait-list';

import { WaitListItemName } from './name/name';
import { WaitListItemNote } from './note/note';
import { WaitListItemProfit } from './profit/profit';
import { WaitListItemActions } from './actions/actions';
import { WaitListItemAddClient } from './add-client/add-client';
import { WaitListItemCrmButton } from './crm-button/crm-button';

import './wait-list-item.css';

// @vue/component
export const WaitListItem = {
	name: 'WaitListItem',
	components: {
		BookingBase,
		Communication,
		DisabledPopup,
		WaitListItemName,
		WaitListItemNote,
		WaitListItemProfit,
		WaitListItemActions,
		WaitListItemAddClient,
		WaitListItemCrmButton,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		item: {
			type: Object,
			required: true,
		},
	},
	data(): { isDisabledPopupShown: boolean }
	{
		return {
			isDisabledPopupShown: false,
			visibleNotePopup: false,
		};
	},
	computed: {
		...mapGetters({
			editingWaitListItemId: `${Model.Interface}/editingWaitListItemId`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			isWaitListItemCreatedFromEmbed: `${Model.Interface}/isWaitListItemCreatedFromEmbed`,
			animationPause: `${Model.Interface}/animationPause`,
		}),
		hasClient(): boolean
		{
			return this.item.clients.length > 0;
		},
		disabled(): boolean
		{
			return this.isEditingBookingMode && this.editingWaitListItemId !== this.item.id;
		},
		dataAttributes(): {[key: string]: string}
		{
			return {
				'data-id': this.item.id,
				'data-kind': DraggedElementKind.WaitListItem,
				'data-element': 'booking-wait-list-item',
			};
		},
		hasAccent(): boolean
		{
			return this.editingWaitListItemId === this.item.id || this.isWaitListItemCreatedFromEmbed(this.item.id);
		},
	},
	methods: {
		onClick(): void
		{
			if (this.disabled)
			{
				this.isDisabledPopupShown = true;
			}
		},
		onNoteMouseEnter(): void
		{
			this.showNoteTimeout = setTimeout((): void => {
				this.visibleNotePopup = true;
			}, 100);
		},
		onNoteMouseLeave(): void
		{
			clearTimeout(this.showNoteTimeout);
			this.visibleNotePopup = false;
		},
	},
	template: `
		<BookingBase
			:disabled
			:bookingClass="{
				'booking--draggable-item': true,
				'booking-wait-list-item': true,
				'--disabled': disabled,
				'--accent': hasAccent,
				'no-transition': animationPause,
			}"
			:dataAttributes="dataAttributes"
			@click="onClick"
		>
			<template #upper-content-row>
				<div
					class="booking-booking-booking-name-container"
					@mouseenter="onNoteMouseEnter"
					@mouseleave="onNoteMouseLeave"
				>
					<WaitListItemName :waitListItem="item"/>
					<WaitListItemNote
						:waitListItem="item"
						:bindElement="() => $el"
						:visiblePopup="visibleNotePopup"
					/>
				</div>
				<WaitListItemProfit :waitListItem="item"/>
			</template>
			<template #lower-content-row>
				<div class="booking--wait-list-item--space"></div>
				<div v-if="hasClient" class="booking--booking-base-buttons">
					<Communication/>
					<WaitListItemCrmButton :waitListItem="item"/>
				</div>
				<WaitListItemAddClient
					v-else
					:waitListItem="item"
				/>
			</template>
			<template #end>
				<DisabledPopup
					v-if="isDisabledPopupShown"
					:popupId="'booking-wait-list-item-disabled-popup-' + item.id"
					:bindElement="() => $el"
					@close="isDisabledPopupShown = false"
				/>
			</template>
			<template #actions>
				<WaitListItemActions :waitListItem="item"/>
			</template>
		</BookingBase>
	`,
};

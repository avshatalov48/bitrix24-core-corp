import { Popup, PopupManager } from 'main.popup';
import type { PopupOptions } from 'main.popup';
import { StickyPopup } from 'booking.component.popup';
import type { BookingModel } from 'booking.model.bookings';
import { PopupMakerItem, PopupMaker } from 'booking.component.popup-maker';

import { Client } from './client/client';
import { Deal } from './deal/deal';
import { Document } from './document/document';
import { Message } from './message/message';
import { Confirmation } from './confirmation/confirmation';
import { Visit } from './visit/visit';
import { FullForm } from './full-form/full-form';
import { Overbooking } from './overbooking/overbooking';
import { Waitlist } from './waitlist/waitlist';
import { RemoveBtn } from './remove-btn/remove-btn';
import './actions-popup.css';

export const ActionsPopup = {
	name: 'BookingActionsPopup',
	emits: ['close'],
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	data(): Object
	{
		return {
			soonTmp: false,
		};
	},
	beforeCreate(): void
	{
		PopupManager.getPopups()
			.filter((popup: Popup) => /booking-booking-actions-popup/.test(popup.getId()))
			.forEach((popup: Popup) => popup.destroy())
		;
	},
	computed: {
		popupId(): string
		{
			return `booking-booking-actions-popup-${this.bookingId}`;
		},
		config(): PopupOptions
		{
			return {
				className: 'booking-booking-actions-popup',
				bindElement: this.bindElement,
				width: 325,
				offsetLeft: this.bindElement.offsetWidth,
				offsetTop: -200,
				animation: 'fading-slide',
			};
		},
		contentStructure(): Array<PopupMakerItem | Array<PopupMakerItem>>
		{
			return [
				{
					id: 'client',
					props: {
						bookingId: this.bookingId,
					},
					component: Client,
				},
				[
					{
						id: 'deal',
						props: {
							bookingId: this.bookingId,
						},
						component: Deal,
					},
					{
						id: 'document',
						props: {
							bookingId: this.bookingId,
						},
						component: Document,
					},
				],
				{
					id: 'message',
					props: {
						bookingId: this.bookingId,
					},
					component: Message,
				},
				{
					id: 'confirmation',
					props: {
						bookingId: this.bookingId,
					},
					component: Confirmation,
				},
				{
					id: 'visit',
					props: {
						bookingId: this.bookingId,
					},
					component: Visit,
				},
				{
					id: 'fullForm',
					props: {
						bookingId: this.bookingId,
					},
					component: FullForm,
				},
			];
		},
		booking(): BookingModel
		{
			return this.$store.getters['bookings/getById'](this.bookingId);
		},
	},
	components: {
		StickyPopup,
		PopupMaker,
		Client,
		Deal,
		Document,
		Message,
		Confirmation,
		Visit,
		FullForm,
		Overbooking,
		Waitlist,
		RemoveBtn,
	},
	template: `
		<StickyPopup
			v-slot="{freeze, unfreeze}"
			:id="popupId"
			:config="config"
			@close="$emit('close')"
		>
			<PopupMaker
				:contentStructure="contentStructure"
				@freeze="freeze"
				@unfreeze="unfreeze"
			/>
			<div class="booking-booking-actions-popup-footer">
				<template v-if="soonTmp">
					<Overbooking :bookingId />
					<Waitlist :bookingId />
				</template>
				<RemoveBtn :bookingId @close="$emit('close')" />
			</div>
		</StickyPopup>
	`,
};

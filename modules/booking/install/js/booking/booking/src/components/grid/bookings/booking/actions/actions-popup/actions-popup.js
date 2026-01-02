import { Model } from 'booking.const';
import { ActionsPopup, FullForm, Info } from 'booking.component.actions-popup';
import type { PopupMakerItem, PopupOptions } from 'booking.component.actions-popup';
import type { BookingModel } from 'booking.model.bookings';

import { BookingClient } from './client/client';
import { BookingDeal } from './deal/deal';
import { BookingDocument } from './document/document';
import { BookingExtraResourcesInfo } from './extra-resources-info/booking-extra-resources-info';
import { BookingMessage } from './message/message';
import { BookingConfirmation } from './confirmation/confirmation';
import { BookingVisit } from './visit/visit';
import { Overbooking } from './overbooking/overbooking';
import { Waitlist } from './waitlist/waitlist';
import { BookingRemoveBtn } from './remove-btn/remove-btn';
import { ActionsPopupActionEnum } from './model';
import type { ActionsPopupOptions } from './types';

export type { ActionsPopupOptions };

type ActionsPopupData = {
	soonTmp: boolean,
};

// @vue/component
export const BookingActionsPopup = {
	name: 'BookingActionsPopup',
	components: {
		ActionsPopup,
		Overbooking,
		Waitlist,
		BookingRemoveBtn,
	},
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		bookingId: {
			type: [Number, String],
			required: true,
		},
		resourceId: {
			type: Number,
			required: true,
		},
		/**
		 * @type ActionsPopupOptions
		 */
		options: {
			type: Object,
			default: null,
		},
	},
	emits: ['close'],
	data(): ActionsPopupData
	{
		return {
			soonTmp: false,
		};
	},
	computed: {
		config(): PopupOptions
		{
			return {
				offsetLeft: this.getOffsetLeft(),
				offsetTop: -200,
			};
		},
		contentStructure(): Array<PopupMakerItem | Array<PopupMakerItem>>
		{
			return [
				{
					id: ActionsPopupActionEnum.client,
					props: {
						bookingId: this.bookingId,
					},
					component: BookingClient,
				},
				{
					id: ActionsPopupActionEnum.extraResourcesInfo,
					props: {
						bookingId: this.bookingId,
						resourceId: this.resourceId,
					},
					component: BookingExtraResourcesInfo,
				},
				[
					{
						id: ActionsPopupActionEnum.deal,
						props: {
							bookingId: this.bookingId,
						},
						component: BookingDeal,
					},
					{
						id: ActionsPopupActionEnum.document,
						props: {
							bookingId: this.bookingId,
						},
						component: BookingDocument,
					},
				],
				{
					id: ActionsPopupActionEnum.message,
					props: {
						bookingId: this.bookingId,
					},
					component: BookingMessage,
				},
				{
					id: ActionsPopupActionEnum.confirmation,
					props: {
						bookingId: this.bookingId,
					},
					component: BookingConfirmation,
				},
				{
					id: ActionsPopupActionEnum.visit,
					props: {
						bookingId: this.bookingId,
					},
					component: BookingVisit,
				},
				[
					{
						id: ActionsPopupActionEnum.fullForm,
						props: {
							bookingId: this.bookingId,
						},
						component: FullForm,
					},
					{
						id: ActionsPopupActionEnum.info,
						class: '--shrink',
						props: {
							bookingId: this.bookingId,
						},
						component: Info,
					},
				],
			];
		},
		booking(): BookingModel
		{
			return this.$store.getters[`${Model.Bookings}/getById`](this.bookingId);
		},
	},
	methods: {
		getOffsetLeft(): number
		{
			const { left } = this.bindElement.getBoundingClientRect();
			if (window.innerWidth - left < 325)
			{
				return -325;
			}

			return this.bindElement.offsetWidth;
		},
	},
	template: `
		<ActionsPopup
			:popupId="bookingId"
			:bindElement="bindElement"
			:contentStructure="contentStructure"
			:popupOptions="config"
			@close="$emit('close')"
		>
			<template #footer>
				<Overbooking
					v-if="!options?.overbooking?.hidden"
					:bookingId
					:resourceId
					:disabled="Boolean(options?.overbooking?.disabled)"
					@close="$emit('close')"
				/>
				<Waitlist v-if="!options?.waitList?.hidden" :bookingId/>
				<BookingRemoveBtn :bookingId @close="$emit('close')"/>
			</template>
		</ActionsPopup>
	`,
};

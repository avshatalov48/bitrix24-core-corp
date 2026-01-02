import { BIcon } from 'ui.icon-set.api.vue';
import { Main, Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';
import 'ui.icon-set.outline';

import { Counter as UiCounter, CounterSize, CounterColor } from 'booking.component.counter';
import { CardId } from '../../../../const/card-id';
import { BookingCard } from './booking-card';
import './booking.css';

// @vue/component
export const Booking = {
	components: {
		BookingCard,
		BIcon,
		UiCounter,
	},
	props: {
		type: {
			type: String,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Main,
			Outline,
			CounterSize,
			CounterColor,
			CardId,
		};
	},
	template: `
		<div>
			<template v-if="type === CardId.Overbooking">
				<BookingCard class="--left"/>
				<BookingCard class="--right"/>
			</template>
			<BookingCard v-else :class="'--' + type">
				<div class="booking-cycle-popup-preview-booking-row">
					<div class="booking-cycle-popup-preview-booking-row-line"></div>
					<div class="booking-cycle-popup-preview-booking-icons">
						<BIcon :name="Outline.PHONE_UP"/>
						<BIcon :name="Outline.CHATS"/>
						<BIcon :name="Outline.CRM_LETTERS"/>
					</div>
				</div>
				<div class="booking-cycle-popup-preview-counter">
					<UiCounter
						v-if="[CardId.Unconfirmed, CardId.Late].includes(type)"
						:value="1"
						:color="CounterColor.DANGER"
						:size="CounterSize.MEDIUM"
						:border="true"
					/>
					<div v-if="type === CardId.Confirmed" class="booking-cycle-popup-preview-counter-icon">
						<BIcon :name="Main.CHECK"/>
					</div>
				</div>
				<div v-if="type === CardId.Late" class="booking-cycle-popup-preview-line"></div>
			</BookingCard>
		</div>
	`,
};

import { DateTimeFormat } from 'main.date';
import { CardId } from '../../../../const/card-id';
import './crm.css';

// @vue/component
export const Crm = {
	props: {
		type: {
			type: String,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			CardId,
		};
	},
	computed: {
		title(): string
		{
			return {
				[CardId.Waitlist]: this.loc('BOOKING_CYCLE_POPUP_BOOKING_WAITLIST'),
				[CardId.Overbooking]: this.loc('BOOKING_CYCLE_POPUP_BOOKING_OVERBOOKING'),
			}[this.type] ?? this.loc('BOOKING_CYCLE_POPUP_BOOKING');
		},
		fields(): string[]
		{
			if (this.type === CardId.Waitlist)
			{
				return [this.loc('BOOKING_CYCLE_POPUP_CLIENT')];
			}

			return [
				this.loc('BOOKING_CYCLE_POPUP_DATE_TIME'),
				this.loc('BOOKING_CYCLE_POPUP_RESOURCE'),
			];
		},
		day(): string
		{
			return DateTimeFormat.format('d');
		},
	},
	template: `
		<div class="booking-cycle-popup-preview-crm" :class="'--' + type">
			<div class="booking-cycle-popup-preview-crm-header">
				<div class="booking-cycle-popup-preview-crm-checkbox"></div>
				<div class="booking-cycle-popup-preview-crm-title">{{ title }}</div>
				<div v-if="[CardId.Unconfirmed, CardId.Late].includes(type)" class="booking-cycle-popup-preview-crm-pill"></div>
			</div>
			<div class="booking-cycle-popup-preview-crm-main">
				<div class="booking-cycle-popup-preview-calendar-container">
					<div class="booking-cycle-popup-preview-calendar">
						<div class="booking-cycle-popup-preview-calendar-header"></div>
						<div class="booking-cycle-popup-preview-calendar-content">
							<div v-if="type === CardId.Waitlist" class="booking-cycle-popup-preview-calendar-days"></div>
							<template v-else>
								<div class="booking-cycle-popup-preview-calendar-day">{{ day }}</div>
								<div class="booking-cycle-popup-preview-calendar-line"></div>
							</template>
						</div>
					</div>
				</div>
				<div class="booking-cycle-popup-preview-crm-fields">
					<template v-for="field of fields" :key="field">
						<div class="booking-cycle-popup-preview-crm-field-title">{{ field }}</div>
						<div class="booking-cycle-popup-preview-crm-field-value"></div>
					</template>
				</div>
			</div>
		</div>
	`,
};

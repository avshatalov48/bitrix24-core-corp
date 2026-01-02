import { Module } from 'booking.const';
import { SegmentButton, type SegmentOptions } from './segment-button/segment-button';
import { Preview } from './preview/preview';
import './card.css';

// @vue/component
export const Card = {
	components: {
		SegmentButton,
		Preview,
	},
	inject: ['context'],
	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
	},
	data(): Object
	{
		return {
			previewMode: this.context ?? Module.Booking,
		};
	},
	computed: {
		segments(): SegmentOptions[]
		{
			return [
				{
					id: Module.Booking,
					title: this.loc('BOOKING_CYCLE_POPUP_SEE_BOOKING'),
				},
				{
					id: Module.Crm,
					title: this.loc('BOOKING_CYCLE_POPUP_SEE_CRM'),
				},
			];
		},
	},
	template: `
		<div class="booking-cycle-popup-card" :data-card-id="id">
			<div class="booking-cycle-popup-card-main">
				<div class="booking-cycle-popup-card-title">{{ title }}</div>
				<div class="booking-cycle-popup-description">{{ description }}</div>
				<div class="booking-cycle-popup-choose-preview">
					<div class="booking-cycle-popup-see-how">{{ loc('BOOKING_CYCLE_POPUP_SEE_HOW') }}</div>
					<SegmentButton v-model:selectedSegmentId="previewMode" :segments="segments"/>
				</div>
			</div>
			<Preview :mode="previewMode" :type="id"/>
		</div>
	`,
};

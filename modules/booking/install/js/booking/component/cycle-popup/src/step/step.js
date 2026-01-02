import { Card } from './card/card';
import './step.css';

// @vue/component
export const Step = {
	components: {
		Card,
	},
	props: {
		id: {
			type: String,
			required: true,
		},
		ordinal: {
			type: Number,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		/** @type CardOptions[] */
		cards: {
			type: Array,
			required: true,
		},
	},
	template: `
		<div class="booking-cycle-popup-step" :data-step-id="id">
			<div class="booking-cycle-popup-step-header">
				<div class="booking-cycle-popup-step-number">{{ ordinal }}</div>
				<div class="booking-cycle-popup-step-title">{{ title }}</div>
			</div>
			<template v-for="(card, index) of cards" :key="index">
				<Card
					:id="card.id"
					:title="card.title"
					:description="card.description"
				/>
			</template>
		</div>
	`,
};

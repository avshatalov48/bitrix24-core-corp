import './segment-button.css';

export type SegmentOptions = {
	id: string,
	title: string,
};

// @vue/component
export const SegmentButton = {
	props: {
		/** @type SegmentOptions[] */
		segments: {
			type: Array,
			required: true,
		},
		selectedSegmentId: {
			type: String,
			required: true,
		},
	},
	emits: ['update:selectedSegmentId'],
	template: `
		<div class="booking-cycle-popup-segment-button">
			<template v-for="segment of segments" :key="segment.title">
				<div
					class="booking-cycle-popup-segment"
					:class="{ '--selected': segment.id === selectedSegmentId }"
					@click="$emit('update:selectedSegmentId', segment.id)"
				>
					{{ segment.title }}
				</div>
			</template>
		</div>
	`,
};

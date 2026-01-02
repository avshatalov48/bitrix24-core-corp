import { HelpDesk } from 'booking.const';

import './wait-list-layout.css';

// @vue/component
export const WaitListLayout = {
	name: 'WaitListLayout',
	props: {
		waitListItemsCount: {
			type: Number,
			default: 0,
		},
		waitListClass: {
			type: [String, Object, Array],
			default: '',
		},
		showEmptyState: {
			type: Boolean,
			default: false,
		},
		dragging: {
			type: Boolean,
			default: false,
		},
		expanded: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['mouseUp'],
	methods: {
		showHelpDesk(): void
		{
			if (!top.BX.Helper)
			{
				return;
			}

			const anchor = HelpDesk.WaitListDescription.anchorCode || null;
			const params = {
				redirect: 'detail',
				code: HelpDesk.WaitListDescription.code,
				...(anchor !== null && { anchor }),
			};

			const queryString = Object.entries(params)
				.map(([key, value]) => `${key}=${value}`)
				.join('&');

			top.BX.Helper.show(queryString);
		},
	},
	template: `
		<div
			class="booking-wait-list"
			:class="waitListClass"
			@mouseup.capture="$emit('mouseUp', $event)"
		>
			<div class="booking-wait-list-header">
				<div class="booking-wait-list-title">{{ loc('BOOKING_BOOKING_WAIT_LIST') }}</div>
				<div v-if="waitListItemsCount > 0" class="booking--wait-list-title-count">{{ waitListItemsCount }}</div>
				<div class="booking-wait-list-buttons">
					<slot name="header" />
				</div>
			</div>
			<div
				v-if="expanded"
				class="booking--wait-list-content"
			>
				<div v-if="showEmptyState" class="booking-wait-list-empty">
					<div class="booking-wait-list-empty-icon"></div>
					<div class="booking-wait-list-empty-title">{{ loc('BOOKING_BOOKING_WAIT_LIST') }}</div>
					<div class="booking-wait-list-empty-subtitle">{{ loc('BOOKING_BOOKING_WAIT_LIST_DESCRIPTION') }}</div>
					<div
						class="booking-wait-list-empty-help"
						@click="showHelpDesk"
					>
						{{ loc('BOOKING_BOOKING_WAIT_LIST_HOW') }}
					</div>
				</div>
				<div v-if="dragging" class="booking-wait-list-drag-area">
					{{ loc('BOOKING_BOOKING_WAIT_LIST_DRAG_AREA') }}
				</div>
				<template v-else>
					<slot name="waitlist" />
				</template>
			</div>
		</div>
	`,
};

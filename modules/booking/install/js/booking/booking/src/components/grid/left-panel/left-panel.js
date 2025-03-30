import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';
import { range } from 'booking.lib.range';

import { OffHours } from './off-hours/off-hours';
import { QuickFilter } from './quick-filter/quick-filter';
import './left-panel.css';

type Hour = {
	value: number,
	formatted: string,
	offHours: boolean,
	last: boolean,
};

export const LeftPanel = {
	computed: {
		...mapGetters({
			offHoursHover: 'interface/offHoursHover',
			offHoursExpanded: 'interface/offHoursExpanded',
			fromHour: 'interface/fromHour',
			toHour: 'interface/toHour',
		}),
		panelHours(): Hour[]
		{
			const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
			const lastHour = this.offHoursExpanded ? 24 : this.toHour;

			return range(0, 24).map((hour) => {
				const timestamp = new Date().setHours(hour, 0) / 1000;

				return {
					value: hour,
					formatted: DateTimeFormat.format(timeFormat, timestamp),
					offHours: hour < this.fromHour || hour >= this.toHour,
					last: hour === lastHour,
				};
			});
		},
	},
	components: {
		OffHours,
		QuickFilter,
	},
	template: `
		<div class="booking-booking-grid-left-panel-container">
			<div class="booking-booking-grid-left-panel">
				<OffHours/>
				<OffHours :bottom="true"/>
				<template v-for="hour of panelHours" :key="hour.value">
					<div
						v-if="hour.last"
						class="booking-booking-grid-left-panel-time-text"
					>
						{{ hour.formatted }}
					</div>
					<div
						v-if="hour.value !== 24"
						class="booking-booking-grid-left-panel-time"
						:class="{'--off-hours': hour.offHours}"
					>
						<div class="booking-booking-grid-left-panel-time-text">
							{{ hour.formatted }}
						</div>
						<QuickFilter :hour="hour.value"/>
					</div>
				</template>
			</div>
		</div>
	`,
};

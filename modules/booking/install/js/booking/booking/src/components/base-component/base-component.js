import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';

import { Grid } from '../grid/grid';
import { Header } from '../header/header';
import { Intersections } from '../intersections/intersections';
import './base-component.css';

const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');

// @vue/component
export const BaseComponent = {
	name: 'BaseComponent',
	components: {
		Header,
		Intersections,
		Grid,
	},
	computed: {
		...mapGetters({
			fromHour: 'interface/fromHour',
			toHour: 'interface/toHour',
			zoom: 'interface/zoom',
		}),
		isAmPmMode(): boolean
		{
			if (DateTimeFormat.isAmPmMode())
			{
				return true;
			}

			const now: string = DateTimeFormat.format(timeFormat, Date.now());

			return now.endsWith('am') || now.endsWith('pm');
		},
	},
	template: `
		<div
			id="booking-content"
			class="booking --ui-context-content-light"
			:style="{
				'--from-hour': fromHour,
				'--to-hour': toHour,
				'--zoom': zoom,
			}"
			:class="{
				'--zoom-is-less-than-07': zoom < 0.7,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--am-pm-mode': isAmPmMode,
			}"
		>
			<Header/>
			<Intersections/>
			<Grid/>
		</div>
	`,
};

import { DateTimeFormat } from 'main.date';
import { mapGetters } from 'ui.vue3.vuex';
import { Grid } from '../grid/grid';
import { Header } from '../header/header';
import { Intersections } from '../intersections/intersections';
import './base-component.css';

export const BaseComponent = {
	data(): Object
	{
		return {
			DateTimeFormat,
		};
	},
	computed: mapGetters({
		fromHour: 'interface/fromHour',
		toHour: 'interface/toHour',
		zoom: 'interface/zoom',
	}),
	components: {
		Header,
		Intersections,
		Grid,
	},
	template: `
		<div
			id="booking-content"
			class="booking"
			:style="{
				'--from-hour': fromHour,
				'--to-hour': toHour,
				'--zoom': zoom,
			}"
			:class="{
				'--zoom-is-less-than-07': zoom < 0.7,
				'--zoom-is-less-than-08': zoom < 0.8,
				'--am-pm-mode': DateTimeFormat.isAmPmMode(),
			}"
		>
			<Header/>
			<Intersections/>
			<Grid/>
		</div>
	`,
};

import {Vue} from "ui.vue";
import "./timeline.css";
import {Chart} from "./chart/chart";
import {Legend} from "./legend/legend";

Vue.component('bx-timeman-component-timeline', {
	components:
	{
		Chart,
		Legend
	},
	props: {
		chart: Array,
		fixedSizeType: String,
		legend: Array,
		readOnly: Boolean,
	},
	methods:
	{
		onIntervalClick(event)
		{
			this.$emit('intervalClick', event);
		},
	},
	// language=Vue
	template: `
		<div class="bx-timeman-component-timeline">
			<Chart
				:intervals="chart"
				:fixedSizeType="fixedSizeType"
				:readOnly="readOnly"
				@intervalClick="onIntervalClick"
			/>
			
			<Legend
				:items="legend"
			/>
		</div>
	`
});
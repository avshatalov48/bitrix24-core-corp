import {Vue} from "ui.vue";
import {Type} from "main.core";

import "ui.design-tokens";
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
		legend: Array,
		fixedSizeType: String,
		readOnly: Boolean,
		overChart: Array,
	},
	computed:
	{
		Type: () => Type,
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

			<transition appear name="bx-timeman-component-timeline-fade">
				<Chart
					v-if="Type.isArrayFilled(overChart)"
					:intervals="overChart"
					:fixedSizeType="fixedSizeType"
					:readOnly="true"
					:showMarkers="false"
					:isOverChart="true"
				/>
			</transition>
		</div>
	`
});
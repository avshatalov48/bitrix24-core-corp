import {BitrixVue} from "ui.vue";
import "./chart.css";

import {Interval} from "./interval/interval";

export const Chart = BitrixVue.localComponent('bx-timeman-component-timeline-chart',{
	components:
	{
		Interval,
	},
	props: {
		intervals: Array,
		fixedSizeType: String,
		readOnly: Boolean
	},
	computed:
	{
		processedIntervals()
		{
			const oneHour = 3600000;

			let intervals = this.intervals.map(interval => {
				interval.time = interval.finish - interval.start;

				return interval;
			})

			const totalTime = intervals
				.reduce((sum, interval) => sum + interval.time, 0)
			;

			const totalDynamicTime = totalTime - intervals
				.filter(interval => interval.type === this.fixedSizeType
					&& interval.time > oneHour
					&& !interval.stretchable
				)
				.reduce((sum, interval) => sum + interval.time, 0)
			;

			let lastStartMarkerTime = null;

			intervals = intervals.map((interval, index, pureIntervals) => {
				if (index === 0)
				{
					interval.showStartMarker = true;
					lastStartMarkerTime = interval.start;
				}
				else if (interval.start - lastStartMarkerTime >= oneHour)
				{
					interval.showStartMarker = true;
					lastStartMarkerTime = interval.start;
				}

				interval.showFinishMarker = (
					index === pureIntervals.length - 1
				);

				interval.fixedSize = (
					interval.type === this.fixedSizeType
					&& interval.time > oneHour
					&& !interval.stretchable
				);

				if (!interval.fixedSize)
				{
					interval.size = 100 / (totalDynamicTime / interval.time);
				}
				else
				{
					interval.size = null;
				}

				return interval;
			});

			intervals[0].isFirst = true;
			intervals[intervals.length - 1].isLast = true;

			//to avoid collisions with the start marker of the last interval, which is always displayed
			if (intervals.length > 3)
			{
				intervals[intervals.length - 1].showStartMarker = true;

				for (let i = intervals.length - 2; i > 0; i--)
				{
					if (
						intervals[i].showStartMarker
						&& intervals[intervals.length - 1].start - intervals[i].start < oneHour
					)
					{
						intervals[i].showStartMarker = false;
						break;
					}
				}
			}
			else if (intervals.length === 3)
			{
				intervals[intervals.length - 1].showStartMarker = true;
				intervals[intervals.length - 2].showStartMarker = true;
			}

			return intervals;
		},
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
		<div class="bx-timeman-component-timeline-chart">
			<div class="bx-timeman-component-timeline-chart-outline">
				<div class="bx-timeman-component-timeline-chart-outline-background"/>
			</div>
			
			<transition-group 
				name="bx-timeman-component-timeline-chart"
				class="bx-timeman-component-timeline-chart-container"
				tag="div"
			>

			<Interval
				v-for="interval of processedIntervals"
				:key="interval.start.getTime()"
				:type="interval.type"
				:start="interval.start"
				:finish="interval.finish"
				:showStartMarker="interval.showStartMarker"
				:showFinishMarker="interval.showFinishMarker"
				:clickable="!readOnly ? interval.clickable : false"
				:hint="!readOnly ? interval.clickableHint : null"
				:fixedSize="interval.fixedSize"
				:size="interval.size"
				:isFirst="interval.isFirst"
				:isLast="interval.isLast"
				@intervalClick="onIntervalClick"
			/>

			</transition-group>
		</div>
	`
});
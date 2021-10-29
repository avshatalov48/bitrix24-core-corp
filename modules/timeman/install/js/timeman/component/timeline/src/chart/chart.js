import {BitrixVue} from "ui.vue";
import {TimeFormatter} from "timeman.timeformatter";
import {Interval} from "./interval/interval";
import "./chart.css";

export const Chart = BitrixVue.localComponent('bx-timeman-component-timeline-chart',{
	components:
	{
		Interval,
	},
	props: {
		intervals: Array,
		fixedSizeType: String,
		readOnly: Boolean,
		showMarkers: {
			type: Boolean,
			default: true,
		},
		isOverChart: {
			type: Boolean,
			default: false,
		},
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

			if (
				intervals[intervals.length - 1].finish.getHours() === 23
				&& intervals[intervals.length - 1].finish.getMinutes() === 59
			)
			{
				intervals[intervals.length - 1].finishAlias = '24:00';
			}

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

			//to avoid collisions between markers of the last interval
			if (intervals[intervals.length - 1].finish - intervals[intervals.length - 1].start <= oneHour)
			{
				intervals[intervals.length - 1].showStartMarker = false;
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
		<div 
			:class="{
				'bx-timeman-component-timeline-chart': !this.isOverChart,
				'bx-timeman-component-timeline-over-chart': this.isOverChart,
		  	}"
		>
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
				:finishAlias="interval.finishAlias ? interval.finishAlias : null"
				:showStartMarker="showMarkers ? interval.showStartMarker: false"
				:showFinishMarker="showMarkers ? interval.showFinishMarker: false"
				:clickable="!readOnly ? interval.clickable : false"
				:hint="!readOnly ? interval.clickableHint : null"
				:fixedSize="interval.fixedSize"
				:size="interval.size"
				:isFirst="interval.isFirst"
				:isLast="interval.isLast"
				:display="interval.display"
				@intervalClick="onIntervalClick"
			/>

			</transition-group>
		</div>
	`
});
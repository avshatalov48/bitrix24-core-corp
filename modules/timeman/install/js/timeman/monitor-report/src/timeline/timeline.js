import {BitrixVue} from "ui.vue";
import "timeman.component.timeline";
import {EntityGroup} from "timeman.const";
import {Time} from "../mixin/time";
import {Type} from "main.core"

import "./timeline.css";

export const Timeline = BitrixVue.localComponent('bx-timeman-monitor-report-timeline', {
	props: {
		readOnly: Boolean,
		selectedPrivateCode: {
			type: String,
			default: null,
		}
	},
	mixins: [Time],
	computed:
	{
		EntityGroup: () => EntityGroup,
		Type: () => Type,
		chartData()
		{
			return this.$store.getters['monitor/getChartData'];
		},
		overChartData()
		{
			if (this.selectedPrivateCode)
			{
				return this.$store.getters['monitor/getOverChartData'](this.selectedPrivateCode);
			}

			return [];
		},
		legendData()
		{
			return [
				{
					id: 1,
					type: EntityGroup.working.value,
					title: EntityGroup.working.title + ': ' + this.formatSeconds(this.workingTime),
				},
				{
					id: 2,
					type: EntityGroup.personal.value,
					title: EntityGroup.personal.title + ': ' + this.formatSeconds(this.personalTime),
				},
			];
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
		<div class="bx-timeman-component-monitor-timeline">
			<bx-timeman-component-timeline
				v-if="Type.isArrayFilled(chartData)"
				:chart="chartData"
				:overChart="overChartData"
				:legend="legendData"
				:fixedSizeType="EntityGroup.inactive.value"
				:readOnly="readOnly"
				@intervalClick="onIntervalClick"
			/>
		</div>
	`
});
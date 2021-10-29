import {BitrixVue} from "ui.vue";
import {TimeFormatter} from "timeman.timeformatter";
import "ui.vue.components.hint";
import "./interval.css";

export const Interval = BitrixVue.localComponent('bx-timeman-component-timeline-chart-interval',{
	props: {
		type: String,
		start: Date,
		finish: Date,
		finishAlias: String,
		size: Number,
		fixedSize: Boolean,
		showStartMarker: Boolean,
		showFinishMarker: Boolean,
		clickable: Boolean,
		hint: String,
		isFirst: Boolean,
		isLast: Boolean,
		display: String,
	},
	computed:
	{
		intervalItemClass()
		{
			return [
				'bx-timeman-component-timeline-chart-interval-item',
				this.type ? 'bx-timeman-component-timeline-chart-interval-item-' + this.type : '',
				this.clickable ? 'bx-timeman-component-timeline-chart-interval-item-clickable' : '',
				this.isFirst && !(this.isFirst && this.isLast) ? 'bx-timeman-component-timeline-chart-interval-first' : '',
				this.isLast && !(this.isFirst && this.isLast) ? 'bx-timeman-component-timeline-chart-interval-last' : '',
				this.isFirst && this.isLast ? 'bx-timeman-component-timeline-chart-interval-round' : '',
				this.display ? 'bx-timeman-component-timeline-chart-interval-item-' + this.display : '',
			]
		},
		intervalInlineStyle()
		{
			const style = {};

			if (this.fixedSize)
			{
				style.width = '50px';
			}
			else
			{
				style.width = this.size + '%';
			}

			return style;
		},
	},
	methods:
	{
		toShortTime(time)
		{
			if (TimeFormatter.isInit())
			{
				return TimeFormatter.toShort(time);
			}

			return BX.date.format('H:i', time);
		},
		intervalClick()
		{
			this.$emit('intervalClick', {
				type: this.type,
				start: this.start,
				finish: this.finish,
			});
		},
	},
	// language=Vue
	template: `
		<div 
			class="bx-timeman-component-timeline-chart-interval"
			:style="intervalInlineStyle"
		>
			<div
				v-if="clickable && hint"
				v-bx-hint="{
					text: hint, 
					position: 'top'
				}"
				:class="intervalItemClass"
				@click="intervalClick"
			/>
			<div
				v-else
				:class="intervalItemClass"
			/>
			
			<div
				class="bx-timeman-component-timeline-chart-interval-marker-container"
			>
				<div 
					v-if="showStartMarker"
					class="
						bx-timeman-component-timeline-chart-interval-marker 
						bx-timeman-component-timeline-chart-interval-marker-start
					"
				>
					<div class="bx-timeman-component-timeline-chart-interval-marker-line"/>
					<div class="bx-timeman-component-timeline-chart-interval-marker-title">
						{{ toShortTime(start) }}
					</div>
				</div>
				<div
					v-if="showFinishMarker"
					class="
						bx-timeman-component-timeline-chart-interval-marker 
						bx-timeman-component-timeline-chart-interval-marker-finish
					"
				>
					<div class="bx-timeman-component-timeline-chart-interval-marker-line"/>
					<div class="bx-timeman-component-timeline-chart-interval-marker-title">
						{{ finishAlias ? finishAlias : toShortTime(finish) }}
					</div>
				</div>
			</div>
		</div>
	`
});
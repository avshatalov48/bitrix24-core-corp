import {BitrixVue} from "ui.vue";
import {TimeFormatter} from "timeman.timeformatter";

import "./interval.css"

export const Interval = BitrixVue.localComponent('bx-timeman-monitor-report-popup-selectintervalpopup-interval', {
	props: {
		start: Date,
		finish: Date,
	},
	computed:
	{
		TimeFormatter: () => TimeFormatter,
		safeFinish()
		{
			let safeFinish = this.finish;

			const currentDateTime = new Date();
			currentDateTime.setSeconds(0);
			currentDateTime.setMilliseconds(0);

			if (safeFinish > currentDateTime)
			{
				safeFinish = currentDateTime;
			}

			return safeFinish;
		}
	},
	methods:
	{
		intervalSelected()
		{
			this.$emit('intervalSelected', {
				start: this.start,
				finish: this.safeFinish,
			});
		}
	},
	// language=Vue
	template: `
		<div class="bx-timeman-monitor-report-popup-selectintervalpopup-interval">
			<div
				@click="intervalSelected"
                class="bx-timeman-monitor-report-popup-item"
			>
			  <div class="bx-timeman-monitor-report-popup-title">
                {{ TimeFormatter.toShort(start) }} - {{ TimeFormatter.toShort(safeFinish) }}
			  </div>
			</div>
		</div>
	`
});
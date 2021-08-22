import {BitrixVue} from "ui.vue";

import "./pausepopup.css";

export const PausePopup = BitrixVue.localComponent('bx-timeman-monitor-report-popup-pause', {
	props: {
		popupInstance: Object
	},
	mounted()
	{
		this.popupInstance.show();
	},
	beforeDestroy()
	{
		this.close();
	},
	methods: {
		hourPause()
		{
			let pauseUntilTime = new Date();
			pauseUntilTime.setHours(pauseUntilTime.getHours() + 1);
			pauseUntilTime.setSeconds(0);
			pauseUntilTime.setMilliseconds(0);

			this.pause(pauseUntilTime);
			this.close();
		},
		fourHourPause()
		{
			let pauseUntilTime = new Date();
			pauseUntilTime.setHours(pauseUntilTime.getHours() + 4);
			pauseUntilTime.setSeconds(0);
			pauseUntilTime.setMilliseconds(0);

			this.pause(pauseUntilTime);
			this.close();
		},
		dayPause()
		{
			let pauseUntilTime = new Date();
			pauseUntilTime.setDate(pauseUntilTime.getDate() + 1);
			pauseUntilTime.setHours(0);
			pauseUntilTime.setMinutes(0);
			pauseUntilTime.setSeconds(0);
			pauseUntilTime.setMilliseconds(0);

			this.pause(pauseUntilTime);
			this.close();
		},
		pause(dateTime)
		{
			this.$emit('monitorPause', dateTime);
		},
		close()
		{
			this.popupInstance.destroy();
		}
	},
	//language=Vue
	template: `
		<div class="bx-timeman-monitor-report-popup-pause">
			<button @click="hourPause" class="ui-btn ui-btn-light ui-btn-no-caps bx-timeman-pwt-popup-pause-btn">
			  {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_ONE_HOUR_BUTTON') }}
			</button>
			<button @click="fourHourPause" class="ui-btn ui-btn-light ui-btn-no-caps bx-timeman-pwt-popup-pause-btn">
			  {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_FOUR_HOURS_BUTTON') }}
			</button>
			<button @click="dayPause" class="ui-btn ui-btn-light ui-btn-no-caps bx-timeman-pwt-popup-pause-btn">
			  {{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_UNTIL_TOMORROW_BUTTON') }}
			</button>
		</div>
	`
});
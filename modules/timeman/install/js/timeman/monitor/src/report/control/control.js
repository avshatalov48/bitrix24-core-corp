import {BitrixVue} from "ui.vue";
import {DayState} from "timeman.const";
import {MonitorModel} from "../../model/monitor";
import {PULL as Pull, PullClient} from "pull.client";
import {ZIndexManager} from "main.core";

import "ui.icons";

export const Control = BitrixVue.localComponent('bx-timeman-monitor-report-control', {
	data: function()
	{
		return {
			status: DayState.unknown,
		};
	},
	computed:
	{
		DayState: () => DayState,
	},
	mounted()
	{
		this.getDayStatus();

		Pull.subscribe({
			type: PullClient.SubscriptionType.Server,
			moduleId: 'timeman',
			command: 'changeDayState',
			callback: (params, extra, command) => {
				this.getDayStatus();
			}
		});
	},
	methods:
	{
		getDayStatus()
		{
			this.callRestMethod('timeman.status', {}, this.setStatusByResult);
		},
		closeDay()
		{
			//tmp hack to close day in desktop app via old popup and link this to a component update.

			let dayControl = BX('tm-component-pwt-day-control');
			if (dayControl)
			{
				dayControl.style.display = 'block';
				dayControl.style.position = 'absolute';
				dayControl.style.left = 'calc(100vw - 115px)';
				dayControl.style.top = 0;
			}

			let callPopup = BX('bx_tm');

			if (!dayControl && callPopup)
			{
				callPopup.style.position = 'absolute';
				callPopup.style.left = 'calc(100vw - 115px)';
				callPopup.style.top = 0;
			}

			if (callPopup)
			{
				callPopup.click();

				let popup = BX('tm-popup');

				if (!ZIndexManager.getComponent(popup))
				{
					ZIndexManager.register(popup);
				}

				ZIndexManager.bringToFront(popup);
			}

			//this.callRestMethod('timeman.close', {}, this.setStatusByResult);
		},
		openDayAndSendHistory()
		{
			BX.Timeman.Monitor.send();

			this.callRestMethod('timeman.open', {}, this.setStatusByResult);
		},
		callRestMethod(method, params, callback)
		{
			this.$Bitrix.RestClient.get().callMethod(method, params, callback)
		},
		setStatusByResult(result)
		{
			if(!result.error())
			{
				this.status = result.data().STATUS
			}
		},
		closeReport()
		{
			BX.SidePanel.Instance.close();
		},
		isAllowedToStartDayAndSendHistory()
		{
			let currentDateLog = new Date(MonitorModel.prototype.getDateLog());
			let reportDateLog = new Date(this.$store.state.monitor.reportState.dateLog);
			let isHistorySent = BX.Timeman.Monitor.isHistorySent;

			if (currentDateLog > reportDateLog && !isHistorySent)
			{
				return true;
			}

			return false;
		}
	},
	// language=Vue
	template: `
		<div class="bx-timeman-component-day-control-wrap">
			<button
				v-if="this.status === DayState.unknown"
				class="ui-btn ui-btn-default ui-btn-wait ui-btn-disabled"
				style="width: 130px"
			/>

			<button
				v-if="
						this.status === DayState.opened
						|| this.status === DayState.paused
						|| this.status === DayState.expired
					"
				@click="closeDay"
				class="ui-btn ui-btn-danger ui-btn-icon-stop"
			>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SEND_BUTTON') }}
			</button>

			<button
				v-if="
					this.status === DayState.closed
					&& this.isAllowedToStartDayAndSendHistory()
				"
				@click="openDayAndSendHistory"
				class="ui-btn ui-btn-success ui-btn-icon-start"
			>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_OPEN_SEND_BUTTON') }}
			</button>

			<button
				class="ui-btn ui-btn-light-border"
				@click="closeReport"
			>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}
			</button>
		</div>
	`
});
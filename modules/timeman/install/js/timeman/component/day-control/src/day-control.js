import {Vue} from "ui.vue";
import {DayState} from 'timeman.const';
import {PULL as Pull, PullClient} from 'pull.client';

Vue.component('bx-timeman-component-day-control', {
	props: ['isButtonCloseHidden'],
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
		openDay()
		{
			this.callRestMethod('timeman.open', {}, this.setStatusByResult);
		},
		pauseDay()
		{
			this.callRestMethod('timeman.pause', {}, this.setStatusByResult);
		},
		closeDay()
		{
			this.callRestMethod('timeman.close', {}, this.setStatusByResult);
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
				v-if="this.status === DayState.closed" 
				@click="openDay"
				class="ui-btn ui-btn-success ui-btn-icon-start"
			>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_REOPEN') }}
			</button>
			
			<template v-if="this.status === DayState.opened">
				<button
					@click="pauseDay"
					class="ui-btn ui-btn-icon-pause tm-btn-pause"
				>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_PAUSE') }}
				</button>
				<button
					v-if="!isButtonCloseHidden"
					@click="closeDay"
					class="ui-btn ui-btn-danger ui-btn-icon-stop"
				>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_FINISH') }}
				</button>
			</template>
			
			<template v-if="this.status === DayState.paused">
				<button
					@click="openDay"
					class="ui-btn ui-btn-icon-start tm-btn-start"
				>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_RESUME') }}
				</button>
				<button
					v-if="!isButtonCloseHidden"
					@click="closeDay"
					class="ui-btn ui-btn-danger ui-btn-icon-stop"
				>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_FINISH') }}
				</button>
			</template>
			
			<button
				v-if="this.status === DayState.expired && !isButtonCloseHidden"
				@click="closeDay"
				class="ui-btn ui-btn-danger ui-btn-icon-stop"
			>
				{{ $Bitrix.Loc.getMessage('TIMEMAN_DAY_CONTROL_REPORT_DAY_FINISH') }}
			</button>
		</div>
	`
});
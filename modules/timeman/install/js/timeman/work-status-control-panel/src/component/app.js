import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BIcon, Outline } from 'ui.icon-set.api.vue';
import { Button as UiButton, AirButtonStyle, ButtonSize } from 'ui.vue3.components.button';

import './app.css';


// @vue/component
export const App = {
	name: 'WorkStatusControlPanel',
	components: {
		UiButton,
		BIcon,
	},
	provide(): Object
	{
		return {};
	},
	props: {},
	setup(): Object
	{
		return {
			Outline,
			Loc,
		};
	},
	data(): Object
	{
		return {
			dataId: '',
			workStatus: '',
			reportReq: '',
			canOpen: '',
			canOpenAndRelaunch: '',
			canEdit: '',
			timerWorkingDayValue: 0,
			timerPauseValue: 0,
		};
	},
	computed: {

		titleText(): string
		{
			if (this.workStatus === 'PAUSED')
			{
				return Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_PAUSED');
			}

			if (this.workStatus === 'CLOSED')
			{
				return this.canOpen === 'OPEN'
					? Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_NOT_STARTED')
					: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_CLOSED');
			}

			if (this.workStatus === 'EXPIRED')
			{
				return Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_NOT_CLOSED');
			}

			return Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_STATUS_STARTED');
		},

		isEditingAvailable(): boolean
		{
			return (this.dataId && this.canEdit === 'Y' && !(this.workStatus === 'EXPIRED' && this.reportReq !== 'A'));
		},

		styleForTimerProps(): string
		{
			if (this.workStatus === 'PAUSED')
			{
				return {
					icon: null,
					status: 'paused',
				};
			}

			if (this.workStatus === 'EXPIRED')
			{
				return {
					icon: Outline.ALERT,
					status: 'expired',
				};
			}

			return null;
		},


		// control buttons

		buttonStartProps(): any
		{
			return {
				text: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_START'),
				icon: Outline.PLAY_L,
				size: ButtonSize.LARGE,
				onClick: async (): void => {
					event.preventDefault();
					event.stopPropagation();
					window.BXTIMEMAN.WND.ACTIONS.OPEN(event);
				},
			};
		},

		buttonPauseProps(): any
		{
			return {
				text: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_PAUSE'),
				icon: Outline.PAUSE_L,
				size: ButtonSize.LARGE,
				style: AirButtonStyle.OUTLINE_ACCENT_2,
				onClick: async (): void => {
					event.preventDefault();
					event.stopPropagation();
					window.BXTIMEMAN.WND.ACTIONS.PAUSE(event);
				},
			};
		},

		buttonContinueProps(): any
		{
			return {
				text: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_CONTINUE'),
				icon: Outline.PLAY_L,
				size: ButtonSize.LARGE,
				onClick: async (): void => {
					event.preventDefault();
					event.stopPropagation();
					window.BXTIMEMAN.WND.ACTIONS.REOPEN(event);
				},
			};
		},

		buttonStopProps(): any
		{
			return {
				text: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_STOP'),
				icon: Outline.CROSS_L,
				size: ButtonSize.LARGE,
				style: AirButtonStyle.OUTLINE,
				onClick: async (): void => {
					event.preventDefault();
					event.stopPropagation();
					window.BXTIMEMAN.WND.ACTIONS.CLOSE(event);
				},
			};
		},

		buttonRestartProps(): any
		{
			return {
				text: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_RESTART'),
				icon: Outline.REFRESH,
				size: ButtonSize.LARGE,
				style: AirButtonStyle.OUTLINE_ACCENT_2,
				onClick: async (): void => {
					event.preventDefault();
					event.stopPropagation();
					window.BXTIMEMAN.WND.ACTIONS.REOPEN(event);
				},
			};
		},

		buttonFinishExpiredProps(): any
		{
			return {
				text: Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_FINISH_EXPIRED'),
				icon: Outline.ALERT_ACCENT,
				size: ButtonSize.LARGE,
				style: AirButtonStyle.TINTED_ALERT,
				onClick: async (): void => {
					event.preventDefault();
					event.stopPropagation();
					window.BXTIMEMAN.WND.ACTIONS.CLOSE(event);
				},
			};
		},

		// control buttons end

		actions(): any[]
		{
			const actionItems = [];

			if (this.workStatus === 'OPENED')
			{
				actionItems.push(this.buttonPauseProps);
				actionItems.push(this.buttonStopProps);
			}

			if (this.workStatus === 'PAUSED')
			{
				actionItems.push(this.buttonContinueProps);
				actionItems.push(this.buttonStopProps);
			}

			if (this.workStatus === 'CLOSED')
			{
				if (this.canOpen === 'OPEN')
				{
					actionItems.push(this.buttonStartProps);
				}
				else
				{
					actionItems.push(this.buttonRestartProps);
				}
			}

			if (this.workStatus === 'EXPIRED')
			{
				actionItems.push(this.buttonFinishExpiredProps);
			}

			return actionItems;
		},
	},
	watch: {},
	mounted(): void
	{
		this.updateDayState();
		this.updateWorkingDayTimer();
		setInterval(() => {
			this.updateWorkingDayTimer();
		}, 1000);

		EventEmitter.subscribe('onTimeManDataRecieved', this.handleTimemanDataRecieved);
		EventEmitter.subscribe('onPlannerDataRecieved', this.handleTimemanDataRecieved);
		EventEmitter.subscribe('onTimeManNeedRebuild', this.handleTimemanDataRecieved);
		EventEmitter.subscribe('onTopPanelCollapse', this.handleTimemanDataRecieved);
		EventEmitter.subscribe('onTimeManWindowBuild', this.handleTimemanDataRecieved);
		EventEmitter.subscribe('onTimemanInit', this.handleTimemanDataRecieved);
	},
	beforeUnmount(): void
	{},
	unmounted(): void
	{},
	methods: {
		convertMillisecondsToHrMinSec(time: number): any
		{
			const timeFullSeconds = Math.ceil(time / 1000);
			const hours = Math.floor(timeFullSeconds / 3600);
			const minutes = Math.floor(timeFullSeconds / 60) - (hours * 60);
			const seconds = timeFullSeconds - (minutes * 60) - (hours * 3600);

			return {
				hours,
				minutes,
				seconds,
			};
		},

		timeNumToDoubleDigitString(num): string
		{
			return num > 9 ? String(num) : ('00' + num).slice(-2);
		},

		updateWorkingDayTimer()
		{
			const dateNow = Math.floor(Date.now() / 1000) * 1000;
			const timerInfo = { ...window.BXTIMEMAN.DATA.INFO };
			const dateStart = parseInt(timerInfo.DATE_START) * 1000;
			const dateWorkingDayStopped = parseInt(timerInfo.DATE_FINISH) * 1000;
			const timeTimeLeaks = parseInt(timerInfo.TIME_LEAKS) * 1000;
			const delta = dateNow - dateStart;
			const deltaPast = dateWorkingDayStopped - dateStart;
			const deltaPause = dateNow - dateWorkingDayStopped;

			if (this.workStatus === 'CLOSED')
			{
				if (this.canOpen === 'OPEN')
				{
					this.timerWorkingDayValue = 0;
					this.timerPauseValue = 0;
				}
				else if (this.canOpen === 'REOPEN')
				{
					this.timerWorkingDayValue = deltaPast - timeTimeLeaks;
					this.timerPauseValue = timeTimeLeaks;
				}
			}
			else if (this.workStatus === 'OPENED')
			{
				this.timerWorkingDayValue = delta - timeTimeLeaks;
				this.timerPauseValue = timeTimeLeaks;
			}
			else if (this.workStatus === 'PAUSED')
			{
				this.timerWorkingDayValue = deltaPast - timeTimeLeaks;
				this.timerPauseValue = deltaPause + timeTimeLeaks;
			}
			else if (this.workStatus === 'EXPIRED')
			{
				this.timerWorkingDayValue = delta - timeTimeLeaks;
				this.timerPauseValue = timeTimeLeaks;
			}
		},

		getDataId(): any
		{
			return window.BXTIMEMAN.DATA.ID || '';
		},

		getWorkStatus(): any
		{
			return window.BXTIMEMAN.DATA.STATE || '';
		},

		getReportReq(): any
		{
			return window.BXTIMEMAN.DATA.REPORT_REQ || '';
		},

		getCanOpen(): any
		{
			return window.BXTIMEMAN.DATA.CAN_OPEN || '';
		},

		getCanOpenAndRelaunch(): any
		{
			return window.BXTIMEMAN.DATA.CAN_OPEN_AND_RELAUNCH || '';
		},

		getCanEdit(): any
		{
			return window.BXTIMEMAN.DATA.CAN_EDIT || '';
		},

		updateDayState(): any
		{
			this.dataId = this.getDataId();
			this.workStatus = this.getWorkStatus();
			this.reportReq = this.getReportReq();
			this.canOpen = this.getCanOpen();
			this.canOpenAndRelaunch = this.getCanOpenAndRelaunch();
			this.canEdit = this.getCanEdit();
		},

		// handlers

		handleTimemanDataRecieved(): any
		{
			this.updateDayState();
		},

		handleClickTimerEditorOpener(): any
		{
			window.BXTIMEMAN.WND.ShowEditVue(event.target);
		},

		handleClickTimemanOpener(): void
		{
			event.preventDefault();
			event.stopPropagation();

			window.BXTIMEMAN.setBindOptions({
				node: event.target,
				mode: 'popup',
				popupOptions: {
					autoHide: true,
					angle: false,
					offsetTop: -40,
					closeByEsc: true,
					bindOptions: {
						forceBindPosition: true,
						forceTop: true,
						forceLeft: false,
					},
					events: {
						onClose: () => {},
						onDestroy: () => {},
					},
					fixed: true,
				},
			});

			window.BXTIMEMAN.OpenVue();
		},

		// handlers end

	},
	template: `
		<div class="tm-control-panel">
			<div class="tm-control-panel__info">
				<div
					:class="[
						'tm-control-panel__timer',
						'tm-timer',
						this.styleForTimerProps?.status ? ('tm-timer_' + this.styleForTimerProps.status) : null,
					]"
				>
					<div
						v-if="this.styleForTimerProps?.icon"
						class="tm-timer__visual"
					>
						<BIcon
							class="tm-timer__visual-img"
							:name="this.styleForTimerProps.icon"
						/>
					</div>
					<p class="tm-timer__title">{{ this.titleText }}</p>
					<p class="tm-timer__value">
						<span class="tm-timer__value-number tm-timer__value-number_hours">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerWorkingDayValue).hours)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_minutes">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerWorkingDayValue).minutes)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_seconds">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerWorkingDayValue).seconds)
							}}</span>
						<button
							v-if="isEditingAvailable"
							class="tm-timer__editor-opener"
							@click="handleClickTimerEditorOpener"
						>
							<BIcon
								class="tm-timer__editor-opener-img"
								:size="16"
								:name="Outline.EDIT_L"
							/>
						</button>
					</p>
				</div>
				<div class="tm-control-panel__widget-opener-container">
					<!-- span instead of button is used to collapse element width when text is wrapped on multiple lines -->
					<span
						class="tm-control-panel__widget-opener"
						@click="this.handleClickTimemanOpener"
					>{{ Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_ACTION_OPEN_PLAN') }}</span>
				</div>
			</div>
			<div
				v-if="Boolean(this.timerPauseValue)"
				class="tm-control-panel__info tm-control-panel__info_pause"
			>
				<div
					:class="[
						'tm-control-panel__timer',
						'tm-control-panel__timer_pause',
						'tm-timer',
						this.styleForTimerProps?.status ? ('tm-timer_' + this.styleForTimerProps.status) : null,
					]"
				>
					<p class="tm-timer__title">{{ Loc.getMessage('TIMEMAN_WORK_STATUS_CONTROL_PANEL_NOTE_PAUSE_LENGTH') }}</p>
					<p class="tm-timer__value">
						<span class="tm-timer__value-number tm-timer__value-number_hours">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerPauseValue).hours)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_minutes">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerPauseValue).minutes)
							}}</span>
						<span class="tm-timer__value-number tm-timer__value-number_seconds">{{
								timeNumToDoubleDigitString(convertMillisecondsToHrMinSec(this.timerPauseValue).seconds)
							}}</span>
					</p>
				</div>
			</div>
			<ul class="tm-control-panel__actions-list">
				<li
					v-for="action in this.actions"
					:key="action.text"
					class="tm-control-panel__actions-item"
				>
					<UiButton
						class="tm-control-panel__action"
						:wide="true"
						:size="action.size"
						:left-icon="action.icon"
						:text="action.text"
						:disabled="action.isDisabled"
						:loading="action.isLoading"
						:style="action.style || null"
						@click="action.onClick"
					/>
				</li>
			</ul>
		</div>
	`,
};

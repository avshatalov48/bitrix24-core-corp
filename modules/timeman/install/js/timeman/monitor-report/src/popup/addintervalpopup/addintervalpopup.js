import {BitrixVue} from "ui.vue";
import {EntityType} from "timeman.const";
import {TimeFormatter} from "timeman.timeformatter";
import {DateFormatter} from "timeman.dateformatter";
import {Type} from "main.core";

import 'ui.design-tokens';
import "ui.icons";
import "../popup.css";
import "ui.forms";
import "ui.layout-form";

export const AddIntervalPopup = BitrixVue.localComponent('bx-timeman-monitor-report-popup-addinterval', {
	directives:
	{
		'bx-focus':
		{
			inserted(element)
			{
				element.focus()
			}
		}
	},
	props:
	{
		minStart: Date,
		maxFinish: Date,
	},
	data: function() {
		return {
			title: '',
			start: this.getTime(this.minStart),
			finish: this.getTime(this.maxFinish),
			comment: '',
		}
	},
	created()
	{
		this.minStart.setSeconds(0);
		this.minStart.setMilliseconds(0);

		this.maxFinish.setSeconds(0);
		this.maxFinish.setMilliseconds(0);

		if (this.createDateFromTimeString(this.finish) > this.saveMaxFinish)
		{
			this.finish = this.getTime(this.saveMaxFinish);
		}
	},
	computed:
	{
		TimeFormatter: () => TimeFormatter,
		DateFormatter: () => DateFormatter,
		Type: () => Type,
		saveMaxFinish()
		{
			let safeMaxFinish = this.maxFinish;

			const currentDateTime = new Date();
			currentDateTime.setSeconds(0);
			currentDateTime.setMilliseconds(0);

			if (safeMaxFinish > currentDateTime)
			{
				safeMaxFinish = currentDateTime;
			}

			return safeMaxFinish;
		},
		canAddInterval()
		{
			if (this.title.trim() === '' || !this.start || !this.finish)
			{
				return false;
			}

			const start = this.createDateFromTimeString(this.start);
			const finish = this.createDateFromTimeString(this.finish);

			const isStartError = (start < this.minStart);
			const isFinishError = (finish > this.saveMaxFinish);
			const isIntervalsConfusedError = (start > finish);

			return !(
				isStartError
				|| isFinishError
				|| isIntervalsConfusedError
			);
		},
	},
	methods:
	{
		addInterval()
		{
			if(!this.canAddInterval)
			{
				return;
			}

			const start = this.createDateFromTimeString(this.start);
			const finish = this.createDateFromTimeString(this.finish);

			this.$store.dispatch('monitor/addHistory', {
				dateLog: DateFormatter.toString(start),
				title: this.title,
				type: EntityType.custom,
				comments: [{
					dateLog: DateFormatter.toString(start),
					text: this.comment
				}],
				time: [{
					start,
					preFinish: null,
					finish,
				}],
			});

			this.addIntervalPopupClose();
		},
		addIntervalPopupClose()
		{
			this.$emit('addIntervalPopupClose');
		},
		addIntervalPopupHide()
		{
			this.$emit('addIntervalPopupHide');
		},
		inputStart(value)
		{
			const start = this.createDateFromTimeString(this.start);

			if (start < this.minStart || value === '')
			{
				this.start = this.getTime(this.minStart);
				return;
			}

			if (start < this.minStart)
			{
				this.start = this.getTime(this.minStart);
				return;
			}

			if (this.finish)
			{
				const finish = this.createDateFromTimeString(this.finish);

				if (start >= finish || start >= this.getTime(this.saveMaxFinish))
				{
					start.setHours(this.saveMaxFinish.getHours());
					start.setMinutes(this.saveMaxFinish.getMinutes() - 1);

					this.start = this.getTime(start);
					return;
				}
			}

			this.start = value;
		},
		inputFinish(value)
		{
			const finish = this.createDateFromTimeString(this.finish);

			if (finish > this.saveMaxFinish || value === '')
			{
				this.finish = this.getTime(this.saveMaxFinish);
				return;
			}

			if (this.start)
			{
				const start = this.createDateFromTimeString(this.start);

				if (finish <= start || finish <= this.getTime(this.minStart))
				{
					finish.setHours(start.getHours());
					finish.setMinutes(start.getMinutes() + 1);

					this.finish = this.getTime(finish);
					return;
				}
			}

			this.finish = value;
		},
		getTime(date)
		{
			if (!Type.isDate(date))
			{
				date = new Date(date);
			}

			const addZero = num => (num >= 0 && num <= 9) ? '0' + num : num;

			const hour = addZero(date.getHours());
			const min = addZero(date.getMinutes());

			return hour + ':' + min;
		},
		createDateFromTimeString(time)
		{
			const baseDate = this.minStart;

			const year = baseDate.getFullYear();
			const month = baseDate.getMonth();
			const day = baseDate.getDate();

			const hourMin = time.split(':');

			return new Date(
				year,
				month,
				day,
				hourMin[0],
				hourMin[1],
				0,
				0
			);
		}
	},
	// language=Vue
	template: `
		<div class="bx-monitor-group-wrap">
			<div class="bx-timeman-monitor-report-popup-wrap">
				<div class="popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height" style="padding: 0">
					<div class="popup-window-titlebar">
						<span class="popup-window-titlebar-text">
							{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_CLICKABLE_HINT') }}
						</span>
					</div>
					<div
						class="
							popup-window-content
							bx-timeman-monitor-popup-window-content
						"
						style="
							overflow: auto; 
							background: transparent;
							width: 440px;
						"
					>
					  
						<div class="ui-form">
							<div class="ui-form-row">
								<div class="ui-form-label">
									<div class="ui-ctl-label-text">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_TITLE') }}
									</div>
								</div>
								<div class="ui-form-content">
									<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
										<input
											v-model="title"
											v-bx-focus
											type="text" 
											class="ui-ctl-element"
										>
									</div>
								</div>
							</div>
							<div class="ui-form-row-inline">
								<div class="ui-form-row">
									<div class="ui-form-label">
										<div class="ui-ctl-label-text">
											{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_START') }}
										</div>
										<div class="ui-ctl-label-text">
											{{ 
												$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MIN_START_HINT')
										  			.replace('#TIME#', TimeFormatter.toShort(minStart))
										  	}}
										</div>
									</div>
									<div class="ui-form-content">
										<div class="ui-ctl ui-ctl-after-icon ui-ctl-time">
											<input
												v-model="start"
												v-on:blur="inputStart($event.target.value)"
												type="time" 
												class="ui-ctl-element" 
												style="padding-right: 4px !important;"
											>
										</div>
									</div>
								</div>
								<div class="ui-form-row">
									<div class="ui-form-label">
										<div class="ui-ctl-label-text">
											{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_FINISH') }}
										</div>
										<div class="ui-ctl-label-text">
											{{
												$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_MAX_FINISH_HINT')
													.replace('#TIME#', TimeFormatter.toShort(saveMaxFinish))
											}}
										</div>
									</div>
									<div class="ui-form-content">
										<div class="ui-ctl ui-ctl-after-icon ui-ctl-time">
											<input
												v-model="finish"
												v-on:blur="inputFinish($event.target.value)"
												type="time" 
												class="ui-ctl-element" 
												style="padding-right: 4px !important;"
											>
										</div>
									</div>
								</div>
							</div>
							<div class="ui-form-row">
								<div class="ui-form-label">
									<div class="ui-ctl-label-text">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_COMMENT') }}
									</div>
								</div>
								<div class="ui-form-content">
									<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize">
										<textarea
											v-model="comment"
											class="ui-ctl-element" 
										>
										</textarea>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="popup-window-buttons">
						<button
							@click="addInterval"
							:class="[
								'ui-btn',
								'ui-btn-md',
								'ui-btn-primary',
								!canAddInterval ? 'ui-btn-disabled' : ''
							]"
						>
							<span class="ui-btn-text">
								{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ADD_BUTTON') }}
							</span>
						</button>
						<button 
							@click="addIntervalPopupHide" 
							class="ui-btn ui-btn-md ui-btn-light"
						>
							<span class="ui-btn-text">
								{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}
							</span>
						 </button>
					</div>
				</div>
			</div>
		</div>
	`
});
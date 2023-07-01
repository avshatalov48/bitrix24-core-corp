import { Date as DateFormatter, DateTimeFormat } from 'main.date';
import { Planner } from 'calendar.planner';
import { Factory } from 'crm.datetime';
import { Events } from 'crm.activity.settings-popup';
import { ajax as Ajax} from 'main.core';
import { DatetimeConverter } from 'crm.timeline.tools';

import { RecallButton } from './recall-button';

import 'ui.design-tokens';
import './calendar.css';

const Recall = {
	today: {
		id: 'today',
		days: 0,
	},
	tomorrow: {
		id: 'tomorrow',
		days: 1,
	},
	after2days: {
		id: 'after2days',
		days: 2,
	},
	after3days: {
		id: 'after3days',
		days: 3,
	},
}

const DurationPeriods = {
	minute: {
		id: 'minute',
		seconds: 60,
	},
	hour: {
		id: 'hour',
		seconds: 60 * 60,
	},
	day: {
		id: 'day',
		seconds: 60 * 60 * 24,
	},
}

export const Calendar = {
	components: {
		RecallButton,
	},
	props: {
		params: {
			type: Object,
			default: {},
		},
	},
	data(): Object
	{
		const timestamp = this.params.from || (Factory.getUserNow().getTime() / 1000);
		const from = Math.round(timestamp / 60) * 60; // round timestamp to minutes

		let duration = 1;
		let durationPeriodId = DurationPeriods.hour.id;
		if (this.params.duration)
		{
			durationPeriodId = this.getPeriodIdBySeconds(this.params.duration);
			duration = this.params.duration / DurationPeriods[durationPeriodId].seconds;
		}

		const to = from + duration * DurationPeriods[durationPeriodId].seconds;

		return {
			id: this.getId(),

			from,
			to,

			duration,
			durationPeriodId,

			timeFromClockInstance: null,
			timeToClockInstance: null,
			plannerInstance: null,
		};
	},
	computed: {
		DurationPeriods: () => DurationPeriods,
		Recall: () => Recall,
		fromDateFormatted: {
			get(): string
			{
				return DateFormatter.format(BX.Crm.DateTime.Dictionary.Format.SHORT_DATE_FORMAT, this.from);
			},
			set(value: string)
			{
				const date = DateFormatter.parse(value);
				const currentDate = this.createDateInstance(this.from);

				date.setHours(currentDate.getHours(), currentDate.getMinutes(), 0, 0);

				this.from = date.getTime() / 1000;
				this.to = this.from + this.duration;
			}
		},
		fromTimeFormatted: {
			get(): string
			{
				return this.getFormattedTime('from');
			},
			set(newTime: string)
			{
				const date = this.getDateInstanceWithTime(this.from, newTime);

				this.from = date.getTime() / 1000;
				this.timeFromClockInstance.closeWnd();
			}
		},
		toDateFormatted: {
			get(): string
			{
				const toTime = this.from + this.duration * DurationPeriods[this.durationPeriodId].seconds;
				return DateFormatter.format(BX.Crm.DateTime.Dictionary.Format.SHORT_DATE_FORMAT, toTime);
			},
			set(value: string)
			{
				const date = DateFormatter.parse(value);
				const toTime = this.from + this.duration * DurationPeriods[this.durationPeriodId].seconds;
				const currentDate = Factory.createFromTimestampInUserTimezone(toTime);

				date.setHours(currentDate.getHours(), currentDate.getMinutes(), 0, 0);
				this.calcDuration(date);
			}
		},
		toTimeFormatted: {
			get(): string
			{
				const toTime = this.from + this.duration * DurationPeriods[this.durationPeriodId].seconds;
				return DateFormatter.format(BX.Crm.DateTime.Dictionary.Format.SHORT_TIME_FORMAT, toTime);
			},
			set(newTime: string)
			{
				const date = this.getDateInstanceWithTime(this.to, newTime);

				this.calcDuration(date);
				this.timeToClockInstance.closeWnd();
			}
		},
		activeRecallId(): ?string
		{
			const isDatesAreEqual = (date1: Date, date2: Date): Boolean => date1.getTime() === date2.getTime();
			const fromDate = this.createDateInstance(this.from, true);

			const date = this.createDateInstance(null, true);

			if (isDatesAreEqual(fromDate, date))
			{
				return Recall.today.id;
			}

			const addDay = (date: Date) => date.setDate(date.getDate() + 1);

			addDay(date);
			if (isDatesAreEqual(fromDate, date))
			{
				return Recall.tomorrow.id;
			}

			addDay(date);
			if (isDatesAreEqual(fromDate, date))
			{
				return Recall.after2days.id;
			}

			addDay(date);
			if (isDatesAreEqual(fromDate, date))
			{
				return Recall.after3days.id;
			}

			return null;
		},
	},
	mounted(): void
	{
		this.plannerInstance = new Planner({
			wrap: this.$refs.plannerContainer,
			compactMode: true,
			minWidth: 770,
			minHeight: 104,
			height: 104,
			width: 770,
			//dayOfWeekMonthFormat: this.dayOfWeekMonthFormat
		});

		this.plannerInstance.subscribe('onDateChange', this.handlePlannerSelectorChanges.bind(this));
		this.plannerInstance.show();

		this.getAccessibilityForUsers();

		this.onDataUpdate();
	},
	unmounted(): void
	{
		this.emitSettingsChange(false);
	},
	watch: {
		duration()
		{
			this.onDataUpdate();
		},
		durationPeriodId()
		{
			this.onDataUpdate();
		},
		from()
		{
			this.onDataUpdate();
		},
		to()
		{
			this.onDataUpdate();
		},
	},
	methods: {
		getId()
		{
			return 'calendar';
		},
		getDateInstanceWithTime(timestamp: Number, time: String): Date
		{
			const timeArr = time.split(':');
			const date = Factory.createFromTimestampInUserTimezone(timestamp);

			let hours = Number(timeArr[0]);
			let minutes = timeArr[1];
			const isAmPm = (minutes.includes('am') || minutes.includes('pm'));
			if (isAmPm)
			{
				if (minutes.includes('pm') && hours !== 12)
				{
					hours += 12;
				}

				minutes = parseInt(minutes, 10);
			}

			date.setHours(hours, minutes, 0, 0);

			return date;
		},
		calcDuration(date: Date): void
		{
			const durationSeconds = date.getTime() / 1000 - this.from;
			if (durationSeconds % DurationPeriods[this.durationPeriodId].seconds === 0)
			{
				this.duration = durationSeconds/DurationPeriods[this.durationPeriodId].seconds;
			}
			else
			{
				this.duration = durationSeconds/DurationPeriods.minute.seconds;
				this.durationPeriodId = DurationPeriods.minute.id;
			}
		},
		getPeriodIdBySeconds(value: Number): string
		{
			if (value % DurationPeriods.day.seconds === 0)
			{
				return DurationPeriods.day.id;
			}

			if (value % DurationPeriods.hour.seconds === 0)
			{
				return DurationPeriods.hour.id;
			}

			return DurationPeriods.minute.id;
		},
		handlePlannerSelectorChanges({ data: { dateFrom, dateTo } })
		{
			this.from = dateFrom.getTime() / 1000;
			this.calcDuration(dateTo);
		},
		onDataUpdate()
		{
			this.updatePlanner();
			this.emitSettingsChange();
		},
		updatePlanner()
		{
			const dateFrom = this.createDateInstance(this.from);
			const durationSeconds = this.getDurationSeconds();
			const dateTo = this.createDateInstance(this.from + durationSeconds);

			this.plannerInstance.updateSelector(dateFrom, dateTo);
		},
		emitSettingsChange(active: boolean = true): void
		{
			const data = this.exportParams(active);
			if (
				(active && this.validateParams(data))
				|| !active
			)
			{
				this.$Bitrix.eventEmitter.emit(Events.EVENT_SETTINGS_CHANGE, data);
			}
			else
			{
				this.$Bitrix.eventEmitter.emit(Events.EVENT_SETTINGS_VALIDATION, { isValid: false });
			}
		},
		getDurationSeconds(): number
		{
			return this.duration * DurationPeriods[this.durationPeriodId].seconds;
		},
		getFormattedDate(id: string): string
		{
			return this.getFormattedValue(id, BX.Crm.DateTime.Dictionary.Format.SHORT_DATE_FORMAT);
		},
		getFormattedTime(id: string): string
		{
			return this.getFormattedValue(id, BX.Crm.DateTime.Dictionary.Format.SHORT_TIME_FORMAT);
		},
		getFormattedValue(id: string, format: string): string
		{
			const timestamp = (id === 'from' ? this.from : this.to);
			return DateFormatter.format(format, timestamp);
		},
		getSecondsFromStartOfDay(timestamp: Number): Number
		{
			const startOfDay = this.createDateInstance(timestamp, true);
			return timestamp - startOfDay.getTime() / 1000;
		},
		getDurationPeriodTitle(periodId: string): string
		{
			const code = `CRM_SETTINGS_POPUP_CALENDAR_DURATION_${periodId.toUpperCase()}S`;
			return this.$Bitrix.Loc.getMessage(code);
		},
		onDateFromClick()
		{
			BX.calendar(
				{
					node: this.$refs.dateFrom,
					field: this.$refs.dateFrom,
					bTime: false,
				}
			);
		},
		onDateFromChange(event)
		{
			this.fromDateFormatted = event.target.value;
		},
		onDateToChange(event)
		{
			this.toDateFormatted = event.target.value;
		},
		onDateToClick()
		{
			BX.calendar(
				{
					node: this.$refs.dateTo,
					field: this.$refs.dateTo,
					bTime: false,
				}
			);
		},
		onTimeFromClick()
		{
			this.showClockSelector(
				'timeFromClockInstance',
				this.from,
				this.$refs.timeFrom,
				'fromTimeFormatted'
			);
		},
		onTimeToClick()
		{
			this.showClockSelector(
				'timeToClockInstance',
				this.to,
				this.$refs.timeTo,
				'toTimeFormatted'
			);
		},
		showClockSelector(instanceName: String, startTimestamp: Number, node: HTMLElement, propertyName: String): void
		{
			if (!this[instanceName])
			{
				this[instanceName] = new BX.CClockSelector({
					start_time: this.getSecondsFromStartOfDay(startTimestamp),
					node,
					callback: (time) => {
						this[propertyName] = time;
					},
				});
			}

			this[instanceName].Show();
		},
		onDurationKeyUp()
		{
			this.duration = this.duration.replace(/\D/g,'');
		},
		onRecallClick(id)
		{
			const fromDate = this.createDateInstance(this.from);
			const todayDate = this.createDateInstance(null, true);
			todayDate.setHours(fromDate.getHours(), fromDate.getMinutes());

			this.from = todayDate.setDate(todayDate.getDate() + Recall[id].days) / 1000;

			const duration = this.duration * DurationPeriods[this.durationPeriodId].seconds;
			this.to = this.from + duration;
		},
		createDateInstance(timestamp: number | null = null, startOfDay: boolean = false): Date
		{
			if (!timestamp)
			{
				timestamp = Date.now() / 1000;
			}

			const date = new Date(timestamp * 1000);
			if (startOfDay)
			{
				date.setHours(0, 0, 0, 0);
			}

			return date;
		},
		isActiveRecall(name: string): boolean
		{
			return (name === this.activeRecallId);
		},
		exportParams(active: boolean = true): Object
		{
			const from = this.from;
			const duration = this.duration * DurationPeriods[this.durationPeriodId].seconds;
			const to = this.from + duration;

			return {
				id: this.id,
				from,
				duration,
				to,
				active,
				fromText: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), from),
				toText: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), to),
			}
		},
		validateParams(data: Object): boolean
		{
			if (data.duration < 0)
			{
				this.$refs.durationRegion.classList.add('ui-ctl-danger');
				return false;
			}

			this.$refs.durationRegion.classList.remove('ui-ctl-danger');
			return true;
		},
		getAccessibilityForUsers(): Array
		{
			const offset = 12 * 24 * 3600; //12 days
			Ajax.runAction('crm.activity.settings.calendar.getAccessibilityForUsers', {
				data: {
					from: this.from - offset,
					to: this.from + offset,
					// @todo add currentEventId
					//currentEventId:
				}
			}).then(({ data }) => this.plannerInstance.update(data.entries, data.accessibility));
		},
		updateSettings(data: Object | null): void
		{
			if (!data || !data.deadline)
			{
				return;
			}

			this.from = data.deadline.getTime() / 1000;
		},
	},
	template: `
		<div class="ui-form">
			<div class="ui-form-row-inline">
				<div class="ui-form-row">
					<div class="ui-form-label">
                		<div class="ui-ctl-label-text">
							{{ $Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_FROM_DATETIME') }}
						</div>
            		</div>
            		<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
							<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
							<input
								ref="dateFrom"
								type="text"
								class="ui-ctl-element"
								@click="onDateFromClick"
								@change="onDateFromChange"
								readonly
								v-model="fromDateFormatted"
							>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-time">
    						<div class="ui-ctl-after ui-ctl-icon-clock"></div>
							<input
								ref="timeFrom"
								type="text"
								class="ui-ctl-element"
								@click="onTimeFromClick"
								readonly
								v-model="fromTimeFormatted"
							>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
                		<div class="ui-ctl-label-text">
							{{ $Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_DURATION') }}
						</div>
            		</div>
            		<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-date" ref="durationRegion">
							<input
								ref="durationValue"
								type="text" 
								v-model="duration"
								class="ui-ctl-element"
								@keyup="onDurationKeyUp"
							>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
    						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select
								ref="durationPeriod"
								class="ui-ctl-element"
								v-model="durationPeriodId"
							>
								<option
									v-for="duration in DurationPeriods"
									key="id"
									:selected="duration.id === durationPeriodId"
									:value="duration.id"
								>
									{{ getDurationPeriodTitle(duration.id) }}
								</option>
							</select>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
                		<div class="ui-ctl-label-text">
							{{ $Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_TO_DATETIME') }}
						</div>
            		</div>
            		<div class="ui-form-content">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-date">
							<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
							<input
								ref="dateTo"
								type="text"
								class="ui-ctl-element"
								@click="onDateToClick"
								@change="onDateToChange"
								readonly
								v-model="toDateFormatted"
							>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-time">
    						<div class="ui-ctl-after ui-ctl-icon-clock"></div>
							<input
								ref="timeTo"
								type="text"
								class="ui-ctl-element"
								@click="onTimeToClick"
								readonly
								v-model="toTimeFormatted"
							>
						</div>
					</div>
				</div>
			</div>
			<div class="ui-form-row-inline crm-activity__settings_popup__calendar__recall-container">
				<RecallButton
					:id=Recall.today.id
					:active=isActiveRecall(Recall.today.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_TODAY')"
				/>
				<RecallButton
					:id=Recall.tomorrow.id
					:active=isActiveRecall(Recall.tomorrow.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_TOMORROW')"
				/>
				<RecallButton
					:id=Recall.after2days.id
					:active=isActiveRecall(Recall.after2days.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_AFTER_2_DAYS')"
				/>
				<RecallButton
					:id=Recall.after3days.id
					:active=isActiveRecall(Recall.after3days.id)
					@onClick="this.onRecallClick"
					:title="$Bitrix.Loc.getMessage('CRM_SETTINGS_POPUP_CALENDAR_RECALL_AFTER_3_DAYS')"
				/>
			</div>
			<div ref="plannerContainer" class="crm-activity__settings_popup__calendar__planner-container"></div>
		</div>
	`
};

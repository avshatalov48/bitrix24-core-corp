import { DatetimeConverter } from 'crm.timeline.tools';
import { Runtime, Text } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { Action } from '../../action';

export const DatePillColor = Object.freeze({
	DEFAULT: 'default',
	WARNING: 'warning',
});


export default {
	props: {
		value: Number,
		withTime: Boolean,
		backgroundColor: {
			type: String,
			required: false,
			default: DatePillColor.DEFAULT,
			validator(value: string) {
				return Object.values(DatePillColor).includes(value);
			},
		},
		action: Object|null,
	},
	inject: ['isReadOnly'],
	data(): Object
	{
		return {
			// in server timezone
			currentTimestamp: this.value,
			// in server timezone
			initialTimestamp: this.value,
			actionTimeoutId: null,
		};
	},
	computed: {
		className() {
			return [
				'crm-timeline__date-pill',
				`--color-${this.backgroundColor}`, {
				'--readonly': this.isPillReadonly,
				}
			]
		},
		formattedDate(): string {
			if (!this.currentTimestamp)
			{
				return null;
			}

			return DatetimeConverter.createFromServerTimestamp(this.currentTimestamp).toUserTime().toDatetimeString({ withDayOfWeek: true, delimiter:', ' })
		},
		currentDateInSiteFormat(): ?string
		{
			return DateTimeFormat.format(
				this.withTime
					? DatetimeConverter.getSiteDateTimeFormat()
					: DatetimeConverter.getSiteDateFormat(),
				(DatetimeConverter.createFromServerTimestamp(this.currentTimestamp)).toUserTime().getValue()
			);
		},

		calendarParams(): Object {
			return {
				value: this.currentDateInSiteFormat,
				bTime: this.withTime,
				bHideTime: !this.withTime,
				bSetFocus: false,
			}
		},

		isPillReadonly(): boolean {
			return this.isReadOnly || !this.action;
		},

		// todo remove after fixing main
		browserToUserOffset(): number {
			const userToUTCOffset = BX.Main.Timezone.Offset.SERVER_TO_UTC + BX.Main.Timezone.Offset.USER_TO_SERVER;

			return userToUTCOffset + Text.toInteger((new Date()).getTimezoneOffset() * 60);
		},
	},
	watch: {
		value(newDate): void // update date from push
		{
			this.initialTimestamp = newDate;
			this.currentTimestamp = newDate;
		}
	},
	methods: {
		openCalendar(event: PointerEvent): void
		{
			if (this.isPillReadonly)
			{
				return;
			}
			this.cancelScheduledActionExecution();

			// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
			BX.calendar({
				node: event.target,
				callback_after: (newDate: Date) => {
					// we assume that user selected time in his timezone
					this.currentTimestamp = Math.floor(newDate.getTime() / 1000) - this.browserToUserOffset;

					this.executeAction();
				},
				...this.calendarParams,
			});
		},
		cancelScheduledActionExecution(): void
		{
			if (this.actionTimeoutId)
			{
				clearTimeout(this.actionTimeoutId);
				this.actionTimeoutId = null;
			}
		},
		executeAction(): void
		{
			if (!this.action)
			{
				return;
			}

			if (this.currentTimestamp === this.initialTimestamp)
			{
				return;
			}

			// to avoid unintended props mutation
			const actionDescription = Runtime.clone(this.action);

			actionDescription.actionParams ??= {};
			actionDescription.actionParams.value = this.currentDateInSiteFormat;
			actionDescription.actionParams.valueTs = this.currentTimestamp;

			const action = new Action(actionDescription);
			action.execute(this);

			this.initialTimestamp = this.currentTimestamp;
		},
	},
	template: `
		<span
			:class="className"
			@click="openCalendar"
		>
			<span>
				{{ formattedDate }}
			</span>
			<span class="crm-timeline__date-pill_caret"></span>
		</span>`
};

import { Runtime } from "main.core";
import { Action } from "../../action";
import { DatetimeConverter } from "crm.timeline.tools";
import { DateTimeFormat } from "main.date";
import { TimestampConverter } from "crm.datetime";

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
			currentTimestamp: this.value,
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
		}
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

			// eslint-disable-next-line bitrix-rules/no-bx
			BX.calendar({
				node: event.target,
				callback_after: (newDate: Date) => {
					this.currentTimestamp = TimestampConverter.userToBrowser(newDate.getTime() / 1000);

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
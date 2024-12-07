import { DatetimeConverter } from 'crm.timeline.tools';
import { Runtime, Type } from 'main.core';
import { DateTimeFormat, Timezone } from 'main.date';
import { Action } from '../../action';

export const DatePillColor = Object.freeze({
	DEFAULT: 'default',
	WARNING: 'warning',
	NONE: 'none',
});

export const PillStyle = Object.freeze({
	DEFAULT: 'pill',
	INLINE_GROUP: 'pill-inline-group',
});

export default {
	props: {
		value: Number,
		withTime: Boolean,
		duration: {
			type: Number,
			required: false,
			default: null,
		},
		backgroundColor: {
			type: String,
			required: false,
			default: DatePillColor.DEFAULT,
			validator(value: string) {
				return Object.values(DatePillColor).includes(value);
			},
		},
		action: Object | null,
		styleValue: String,
	},
	inject: ['isReadOnly'],
	data(): Object
	{
		return {
			currentTimestamp: this.value,
			initialTimestamp: this.value,
		};
	},
	computed: {
		className(): []
		{
			return [
				'crm-timeline__date-pill',
				`--color-${this.backgroundColor}`,
				{
					'--readonly': this.isPillReadonly,
				},
				{
					'--inline-group': this.styleValue === PillStyle.INLINE_GROUP,
				},
			];
		},
		formattedDate(): string {
			if (!this.currentTimestamp)
			{
				return null;
			}

			const converter = this.getDatetimeConverter();

			let result = converter.toDatetimeString({
				withDayOfWeek: true,
				withFullMonth: true,
				delimiter: ', ',
			});
			if (Type.isNumber(this.duration))
			{
				const converterWithDuration = this.getDatetimeConverterWithDuration();
				result = `${result}-${converterWithDuration.toTimeString()}`;
			}

			return result;
		},
		currentDateInSiteFormat(): ?string
		{
			return DateTimeFormat.format(
				this.withTime
					? DatetimeConverter.getSiteDateTimeFormat()
					: DatetimeConverter.getSiteDateFormat(),
				this.getDatetimeConverter().getValue()
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

			// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
			BX.calendar({
				node: event.target,
				callback_after: (newDate: Date) => {
					// we assume that user selected time in his timezone
					this.currentTimestamp = Timezone.UserTime.toUTCTimestamp(newDate);

					this.executeAction();
				},
				...this.calendarParams,
			});
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

			this.$emit('onChange', this.initialTimestamp);
		},
		getDatetimeConverter(): DatetimeConverter
		{
			return (DatetimeConverter.createFromServerTimestamp(this.currentTimestamp)).toUserTime();
		},
		getDatetimeConverterWithDuration(): DatetimeConverter
		{
			return (DatetimeConverter.createFromServerTimestamp(this.currentTimestamp + this.duration)).toUserTime();
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

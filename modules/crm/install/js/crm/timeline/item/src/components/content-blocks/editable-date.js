import { DateTimeFormat } from "main.date";
import { Runtime } from "main.core";
import Link from './link';
import { Action } from "../../action";
import { DatetimeConverter } from "crm.timeline.tools";

export default {
	components: {
		Link,
	},
	props: {
		value: Number,
		withTime: Boolean,
		action: Object,
	},
	data(): Object
	{
		return {
			currentDate: this.value,
			initialDate: this.value,
			actionTimeoutId: null,
		};
	},
	computed: {
		currentDateObject(): ?Date
		{
			return this.currentDate ? new Date(this.currentDate * 1000) : null;
		},
		currentDateInSiteFormat(): ?string
		{
			if (!this.currentDateObject)
			{
				return null;
			}

			return DateTimeFormat.format(DatetimeConverter.getSiteDateFormat(), this.currentDateObject);
		},
		textProps(): Object
		{
			return {
				text: this.currentDateInSiteFormat,
			};
		},
	},
	methods: {
		openCalendar(event: PointerEvent): void
		{
			this.cancelScheduledActionExecution();

			// eslint-disable-next-line bitrix-rules/no-bx
			BX.calendar({
				node: event.target,
				value: this.currentDateInSiteFormat,
				bTime: this.withTime,
				bHideTime: !this.withTime,
				bSetFocus: false,
				callback_after: (newDate: Date) => {
					this.currentDate = Math.round(newDate.getTime() / 1000);

					this.scheduleActionExecution();
				}
			});
		},

		scheduleActionExecution(): void
		{
			this.cancelScheduledActionExecution();

			this.actionTimeoutId = setTimeout(this.executeAction.bind(this), 3 * 1000);
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

			if (this.currentDate === this.initialDate)
			{
				return;
			}

			// to avoid unintended props mutation
			const actionDescription = Runtime.clone(this.action);

			actionDescription.actionParams ??= {};
			actionDescription.actionParams.value = this.currentDateObject;

			const action = new Action(actionDescription);
			action.execute(this);

			this.initialDate = this.currentDate;
		},
	},
	template: `<Link @click="openCalendar" v-bind="textProps"></Link>`,
};

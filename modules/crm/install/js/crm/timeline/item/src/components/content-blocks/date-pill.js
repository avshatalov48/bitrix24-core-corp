import { DateTimeFormat } from "main.date";
import { Runtime } from "main.core";
import { Action } from "../../action";

export default {
	props: {
		date: Number,
		action: Object,
	},
	data(): Object
	{
		return {
			currentDate: this.date,
			initialDate: this.date,
			actionTimeoutId: null,
		};
	},
	computed: {
		currentFormatDate() {
			if (!this.currentDateObject)
			{
				return null;
			}

			return DateTimeFormat.format(this.dateFormat, this.currentDateObject);
		},

		dateFormat() {
			return 'D, j M, H:i';
		},

		currentDateObject(): ?Date {
			return this.currentDate ? new Date(this.currentDate * 1000) : null;
		},

		calendarParams() {
			return {
				value: this.currentFormatDate,
				bTime: true,
				bHideTime: false,
				bSetFocus: false,
			}
		},
	},
	methods: {
		openCalendar(event: PointerEvent): void
		{
			this.cancelScheduledActionExecution();

			// eslint-disable-next-line bitrix-rules/no-bx
			BX.calendar({
				node: event.target,
				callback_after: (newDate: Date) => {
					this.currentDate = Math.round(newDate.getTime() / 1000);

					this.scheduleActionExecution();
				},
				...this.calendarParams,
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
	template: `
		<span class="crm-timeline__date-pill" @click="openCalendar">
			<span>
				{{ currentFormatDate }}
			</span>
			<span class="crm-timeline__date-pill_caret"></span>
		</span>`
};
import { PingSelector, PingSelectorEvents } from 'crm.field.ping-selector';
import { DatetimeConverter } from 'crm.timeline.tools';
import { Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Timezone } from 'main.date';

import { Action } from '../../../action';

const SAVE_OFFSETS_REQUEST_DELAY = 1000;

export default {
	props: {
		valuesList: {
			type: Array,
			required: true,
			default: [],
		},
		value: {
			type: Array,
			default: [],
		},
		deadline: {
			type: Number,
		},
		saveAction: {
			type: Object,
			required: true,
		},
		icon: {
			type: String,
			default: null,
			required: false,
		},
	},

	data(): Object
	{
		return {
			deadlineData: this.deadline,
		};
	},

	watch: {
		deadline(deadline: number): void
		{
			this.deadlineData = deadline;
		},
	},

	mounted(): void
	{
		this.initPingSelector();
	},

	beforeUnmount()
	{
		EventEmitter.unsubscribe(
			this.pingSelector,
			PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE,
			this.onItemSelectorValueChange,
		);
	},

	methods: {
		onItemSelectorValueChange(event: BaseEvent): void
		{
			Runtime.debounce(
				() => {
					const data = event.getData();
					if (data)
					{
						this.executeSaveAction(data.value);
					}
				},
				SAVE_OFFSETS_REQUEST_DELAY,
				this,
			)();
		},

		executeSaveAction(items: Array): void
		{
			if (!this.saveAction)
			{
				return;
			}

			if (this.value.sort().toString() === items.sort().toString())
			{
				return;
			}

			// to avoid unintended props mutation
			const actionDescription = Runtime.clone(this.saveAction);

			actionDescription.actionParams ??= {};
			actionDescription.actionParams.value = items;

			const action = new Action(actionDescription);

			void action.execute(this);
		},

		initPingSelector(): void
		{
			const deadlineDate = this.createDateFromDeadline();
			const deadlineTime: null | number = deadlineDate?.getTime();
			const currentTime = Date.now();

			const deadline = deadlineTime > currentTime
				? deadlineDate
				: new Date()
			;

			this.pingSelector = new PingSelector({
				target: this.$el,
				valuesList: this.valuesList,
				selectedValues: this.value,
				icon: Type.isStringFilled(this.icon) ? this.icon : null,
				deadline,
			});

			EventEmitter.subscribe(
				this.pingSelector,
				PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE,
				this.onItemSelectorValueChange,
			);
		},

		createDateFromDeadline(): null | Date
		{
			if (!Type.isNumber(this.deadlineData))
			{
				return null;
			}

			return DatetimeConverter.createFromServerTimestamp(this.deadlineData).getValue();
		},

		setDeadline(deadline: number): void
		{
			const date = Timezone.UserTime.getDate(deadline);

			this.deadlineData = date.getTime() / 1000;
			this.pingSelector.setDeadline(date);
		},
	},

	template: '<div></div>',
};

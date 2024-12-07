import { Events, ItemSelector } from 'crm.field.item-selector';
import { Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

import { Action } from '../../action';

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
		saveAction: {
			type: Object,
			required: true,
		},
		compactMode: {
			type: Boolean,
			default: false,
		},
		icon: {
			type: String,
			default: null,
			required: false,
		},
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
	},

	mounted(): void
	{
		this.itemSelector = new ItemSelector({
			target: this.$el,
			valuesList: this.valuesList,
			selectedValues: this.value,
			compactMode: this.compactMode ?? false,
			icon: Type.isStringFilled(this.icon) ? this.icon : null,
		});

		EventEmitter.subscribe(
			this.itemSelector,
			Events.EVENT_ITEMSELECTOR_VALUE_CHANGE,
			this.onItemSelectorValueChange,
		);
	},

	beforeUnmount()
	{
		EventEmitter.unsubscribe(
			this.itemSelector,
			Events.EVENT_ITEMSELECTOR_VALUE_CHANGE,
			this.onItemSelectorValueChange,
		);
	},

	computed: {
		styles(): Object
		{
			if (this.compactMode)
			{
				return {};
			}

			return {
				width: '100%',
			};
		},
	},

	template: '<div :style="styles"></div>',
};

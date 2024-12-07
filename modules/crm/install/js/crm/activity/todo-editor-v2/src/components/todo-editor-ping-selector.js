import { CompactIcons, PingSelector, PingSelectorEvents } from 'crm.field.ping-selector';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { hint } from 'ui.vue3.directives.hint';
import { Events } from './todo-editor';

export const TodoEditorPingSelector = {
	directives: { hint },
	emits: ['onChange'],
	props: {
		valuesList: {
			type: Array,
			required: true,
			default: [],
		},
		selectedValues: {
			type: Array,
			default: [],
		},
		deadline: {
			type: Date,
		},
	},

	computed: {
		hint(): Object
		{
			return {
				text: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_PING_SELECTOR_HINT'),
				popupOptions: {
					angle: {
						offset: 34,
						position: 'top',
					},
				},
			};
		},
	},

	methods: {
		onPingSelectorValueChange(): void
		{
			this.$emit('onChange');
		},

		onDeadlineChange(event: BaseEvent): void
		{
			const { deadline } = event.getData();
			if (deadline)
			{
				this.itemSelector?.setDeadline(deadline);
			}
		},

		getValue(): Array
		{
			if (this.itemSelector)
			{
				return this.itemSelector.getValue();
			}

			return [];
		},

		setValue(values: Array): void
		{
			this.itemSelector?.setValue(values);
		},
	},

	mounted(): void
	{
		this.itemSelector = new PingSelector({
			target: this.$refs.container,
			valuesList: this.valuesList,
			selectedValues: this.selectedValues,
			icon: CompactIcons.BELL,
			deadline: this.deadline,
		});

		EventEmitter.subscribe(
			this.itemSelector,
			PingSelectorEvents.EVENT_PINGSELECTOR_VALUE_CHANGE,
			this.onPingSelectorValueChange,
		);

		this.$Bitrix.eventEmitter.subscribe(Events.EVENT_DEADLINE_CHANGE, this.onDeadlineChange);
	},

	template: '<div style="width: 100%;"><div ref="container" v-hint="hint"></div></div>',
};

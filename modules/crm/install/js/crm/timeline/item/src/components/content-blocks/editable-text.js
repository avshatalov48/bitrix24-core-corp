import Text from './text';
import { Action } from "../../action";
import {Runtime} from "main.core";
import {BitrixVue} from 'ui.vue3';

export default BitrixVue.cloneComponent(Text, {
	components: {
		Text,
	},
	props: {
		action: Object,
	},
	data(): Object {
		return {
			isEdit: false,
			currentValue: this.value,
			initialValue: this.value,
			actionTimeoutId: null,
		};
	},
	computed: {
		textProps(): Object {
			return {
				...this.$props,
				value: this.currentValue,
			};
		},
	},
	methods: {
		enableEdit(): void
		{
			this.cancelScheduledActionExecution();

			this.isEdit = true;

			this.$nextTick(() => {
				this.$refs.input.focus();
			});
		},
		disableEdit(): void
		{
			this.isEdit = false;

			this.scheduleActionExecution();
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
			if (!this.action || this.currentValue === this.initialValue)
			{
				return;
			}

			// to avoid unintended props mutation
			const actionDescription = Runtime.clone(this.action);

			actionDescription.actionParams ??= {};
			actionDescription.actionParams.value = this.currentValue;

			const action = new Action(actionDescription);
			action.execute(this);

			this.initialValue = this.currentValue;
		},
	},
	template: `
			<input
				v-if="isEdit"
				ref="input"
				type="text"
				v-model.trim="currentValue"
				@focusout="disableEdit"
			>
			<Text
				v-else
				v-bind="textProps"
				@click="enableEdit"
			/>
		`
});


import { ReminderAdd } from './reminder-add';
import { ReminderItem } from './reminder-item';
import './reminder.css';

// @vue/component
export const Reminder = {
	name: 'UiReminder',
	components: {
		ReminderAdd,
		ReminderItem,
	},
	props: {
		/**
		 * @type {Array<number>}
		 */
		modelValue: {
			type: Array,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['update:modelValue'],
	methods: {
		addRemind(remind: number): void
		{
			if (this.disabled)
			{
				return;
			}

			const reminds = new Set(this.modelValue);
			reminds.add(remind);

			this.$emit('update:modelValue', [...reminds]);
		},
		removeRemind(remind: number): void
		{
			if (this.disabled)
			{
				return;
			}

			this.$emit('update:modelValue', this.modelValue.filter((value) => value !== remind));
		},
		updateRemind({ remind, selected }): void
		{
			if (this.disabled)
			{
				return;
			}

			const index = this.modelValue.indexOf(remind);
			if (index >= 0 && !this.modelValue.includes(selected))
			{
				const items = [...this.modelValue];
				items[index] = selected;
				this.$emit('update:modelValue', items);
			}
		},
	},
	template: `
		<div class="booking--reminder-row">
			<ReminderItem
				v-for="remind of modelValue"
				:key="remind"
				:remind
				:disabled
				@update="updateRemind"
				@remove="removeRemind"
			/>
			<ReminderAdd :disabled @add="addRemind"/>
		</div>
	`,
};

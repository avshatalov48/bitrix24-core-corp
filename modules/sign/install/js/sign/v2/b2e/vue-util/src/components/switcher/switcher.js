import { Switcher as UiSwitcher } from 'ui.vue3.components.switcher';
import { SwitcherSize } from 'ui.switcher';
import { Hint } from 'ui.vue3.components.hint';
import './style.css';

// @vue/component
export const Switcher = {
	// eslint-disable-next-line vue/multi-word-component-names
	name: 'Switcher',
	components: {
		UiSwitcher,
		Hint,
	},
	props: {
		modelValue: {
			type: Boolean,
			default: false,
		},
		title: {
			type: String,
			required: false,
			default: '',
		},
		hint: {
			type: String,
			required: false,
			default: '',
		},
		isEnabled: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['update:modelValue'],
	data(): Object
	{
		return {
			isChecked: false,
			switcherSize: SwitcherSize.extraSmall,
		};
	},
	watch: {
		modelValue(newValue: boolean): void
		{
			this.isChecked = newValue;
		},
	},
	mounted()
	{
		this.isChecked = this.isEnabled && this.modelValue;
	},
	methods: {
		setValue(value: boolean): void
		{
			if (this.isEnabled === false)
			{
				return;
			}

			this.isChecked = value;
			this.$emit('update:modelValue', value);
		},
	},
	template: `
		<div>
			<UiSwitcher class="sign-b2e_switcher"
					  :isChecked="isChecked"
					  :options="{ size: switcherSize }"
					  @check="setValue(true)" 
					  @uncheck="setValue(false)"
			/>
			<span @click="setValue(!isChecked)" class="sign-b2e-settings__item_title sign-b2e_switcher__title">{{ title }}</span>
			<Hint v-if="hint" class="sign-b2e_switcher__hint" :text="hint"/>
		</div>
	`,
};

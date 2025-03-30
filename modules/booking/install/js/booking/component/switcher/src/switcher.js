import { Dom } from 'main.core';
import { Switcher as UISwitcher, SwitcherSize, SwitcherColor } from 'ui.switcher';
import './switcher.css';

export { SwitcherColor, SwitcherSize };

export const Switcher = {
	name: 'UiSwitcher',
	emits: [
		'update:model-value',
		'toggle',
		'checked',
		'unchecked',
		'lock',
		'unlock',
	],
	props: {
		modelValue: {
			type: Boolean,
			required: true,
		},
		id: {
			type: String,
			default: undefined,
		},
		inputName: {
			type: String,
			default: '',
		},
		size: {
			type: String,
			default: SwitcherSize.small,
			validator(val): boolean
			{
				return Object.values(SwitcherSize).includes(val);
			},
		},
		color: {
			type: String,
			default: SwitcherColor.primary,
			validator(val): boolean
			{
				return Object.values(SwitcherColor).includes(val);
			},
		},
		disabled: Boolean,
		loading: Boolean,
		hiddenText: Boolean,
	},
	beforeCreate(): void
	{
		this.switcher = new UISwitcher({
			id: this.id,
			inputName: this.inputName,
			checked: this.modelValue,
			size: this.size,
			color: this.color,
			disabled: this.disabled,
			loading: this.loading,
			handlers: {
				toggled: this.toggle,
				checked: this.checked,
				unchecked: this.unchecked,
				lock: this.lock,
				unlock: this.unlock,
			},
		});
	},
	mounted(): void
	{
		this.switcher.renderTo(this.$refs.switcherWrapper);
	},
	methods: {
		checked(): void
		{
			this.$emit('checked');
		},
		unchecked(): void
		{
			this.$emit('unchecked');
		},
		lock(): void
		{
			this.$emit('lock');
		},
		unlock(): void
		{
			this.$emit('unlock');
		},
		toggle(): void
		{
			const checked = this.switcher.isChecked();

			this.$emit('update:model-value', checked);
			this.$emit('toggle', checked);
		},
		toggleTextVisibility(hidden: boolean): void
		{
			const node = this.switcher.getNode();
			const elOn = node.querySelector('.ui-switcher-enabled');
			const elOff = node.querySelector('.ui-switcher-disabled');

			if (hidden)
			{
				Dom.addClass(elOn, 'switcher-transparent-text');
				Dom.addClass(elOff, 'switcher-transparent-text');
			}
			else
			{
				Dom.removeClass(elOn, 'switcher-transparent-text');
				Dom.removeClass(elOff, 'switcher-transparent-text');
			}
		},
	},
	watch: {
		disabled: {
			handler(disabled)
			{
				if (disabled !== this.switcher.isDisabled())
				{
					this.switcher.disable(disabled);
				}
			},
		},
		loading: {
			handler(loading)
			{
				if (loading !== this.switcher.isLoading())
				{
					this.switcher.setLoading(loading);
				}
			},
		},
		modelValue: {
			handler(checked)
			{
				if (checked !== this.switcher.checked)
				{
					this.switcher.check(checked);
				}
			},
		},
		hiddenText: {
			handler(hidden)
			{
				this.toggleTextVisibility(hidden);
			},
			immediate: true,
		},
	},
	template: `
		<div ref="switcherWrapper"></div>
	`,
};

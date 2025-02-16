import { CounterSize, CounterColor } from 'ui.cnt';

export {
	CounterSize,
	CounterColor,
};

export const Counter = {
	name: 'UiCounter',
	props: {
		value: {
			type: [String, Number],
			default: '',
		},
		border: {
			type: Boolean,
			default: false,
		},
		size: {
			type: String,
			default: '',
		},
		color: {
			type: String,
			default: '',
		},
		maxValue: {
			type: [Number, Boolean],
			default: 99,
		},
		counterClass: {
			type: String,
			default: '',
		},
	},
	computed: {
		counterValue(): string | number
		{
			if (this.value < this.maxValue)
			{
				return this.value;
			}

			return `${Number(this.maxValue)}+`;
		},
	},
	template: `
		<div :class="['ui-counter', counterClass, size, color, { 'ui-counter-border': border }]">
			<div class="ui-counter-inner">{{ counterValue }}</div>
		</div>
	`,
};

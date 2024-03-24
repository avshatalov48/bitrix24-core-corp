import { Type } from 'main.core';
import { BitrixVue } from 'ui.vue3';
import BaseInput from './base-input-wrapper';

export const Select = BitrixVue.cloneComponent(BaseInput, {
	props: {
		selectedValue: String,
		values: Object,
	},
	computed: {
		componentName(): string
		{
			return 'Select';
		},
		componentProps(): Object
		{
			return {
				values: this.preparedValues,
			};
		},
		preparedValues(): Array
		{
			if (!Type.isPlainObject(this.values))
			{
				return [];
			}
			const result = [];
			Object.keys(this.values).forEach((key) => {
				result.push({
					id: key,
					value: String(this.values[key]),
				});
			});

			return result;
		},
	},
	watch: {
		selectedValue(newValue): void
		{
			this.currentValue = newValue;
		},
	},
	methods: {
		getInitialValue(): string
		{
			return `${this.selectedValue}`;
		},
	},
});

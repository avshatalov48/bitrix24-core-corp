import { BitrixVue } from 'ui.vue3';
import BaseInput from './base-input-wrapper';

export const Input = BitrixVue.cloneComponent(BaseInput, {
	props: {
		placeholder: String,
	},
	computed: {
		componentName(): string
		{
			return 'Input';
		},
		componentProps(): Object
		{
			return {
				placeholder: this.placeholder,
			};
		},
	},
});

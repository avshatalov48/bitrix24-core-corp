import { BitrixVue } from 'ui.vue3';
import BaseInput from './base-input-wrapper';

export const Textarea = BitrixVue.cloneComponent(BaseInput, {
	props: {
		placeholder: String,
	},
	computed: {
		componentName(): string
		{
			return 'Textarea';
		},
		componentProps(): Object
		{
			return {
				placeholder: this.placeholder,
			};
		},
	},
});

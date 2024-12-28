import { BaseField } from './base-field';

export const DropdownField = {
	extends: BaseField,
	props: {
		options: {
			type: Array,
			required: true,
		},
	},
	// language=Vue
	template: `
		<div class="ui-form-row">
			<div class="ui-form-label">
				<div class="ui-ctl-label-text">{{ title }}</div>
			</div>
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select class="ui-ctl-element" v-model="value" @change="onInputChange($event.target.value)">
					<option v-for="option in options" :value="option.value" :selected="option.value === defaultValue">{{ option.title }}</option>
				</select>
			</div>
		</div>
	`,
};

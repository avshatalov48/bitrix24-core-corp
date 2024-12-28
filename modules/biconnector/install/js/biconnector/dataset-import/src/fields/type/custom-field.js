import { BaseField } from './base-field';

export const CustomField = {
	extends: BaseField,
	// language=Vue
	template: `
		<div class="ui-form-row">
			<div class="ui-form-label">
				<div class="ui-ctl-label-text">{{ title }}</div>
			</div>
			<div class="ui-ctl ui-ctl-w100">
				<slot name="field-content"></slot>
			</div>
		</div>
	`,
};

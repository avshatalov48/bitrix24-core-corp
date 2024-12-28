import { BaseField } from './base-field';

export const TextField = {
	extends: BaseField,
	// language=Vue
	template: `
		<div class="ui-form-row">
			<div class="ui-form-label">
				<div class="ui-ctl-label-text">{{ title }}</div>
			</div>
			<div class="ui-ctl ui-ctl-textarea dataset-import-textarea ui-ctl-w100 ui-ctl-no-resize">
				<textarea 
					class="ui-ctl-element dataset-import-textarea-element" 
					:placeholder="placeholder" 
					@input="onInputChange($event.target.value)"
					v-model="value"
				></textarea>
			</div>
		</div>
	`,
};

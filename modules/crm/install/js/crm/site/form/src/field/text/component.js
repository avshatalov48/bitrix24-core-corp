import * as ComponentString from '../string/component';

const FieldText = {
	mixins: [ComponentString.MixinString],
	template: `
		<div class="b24-form-control-container b24-form-control-icon-after">
			<textarea class="b24-form-control"
				:class="inputClasses"
				v-model="value"
				@blur="$emit('input-blur', this)"
				@focus="$emit('input-focus', this)"
			></textarea>
			<div class="b24-form-control-label">
				{{ label }} 
				<span v-show="field.required" class="b24-form-control-required">*</span>			
			</div>
			<div class="b24-form-icon-after b24-form-icon-remove"
				:title="field.messages.get('fieldRemove')"
				v-if="itemIndex > 0"
				@click="deleteItem"
			></div>
			<field-item-alert
				v-bind:field="field"
				v-bind:item="item"
			></field-item-alert>
		</div>
	`
};

export {
	FieldText
}
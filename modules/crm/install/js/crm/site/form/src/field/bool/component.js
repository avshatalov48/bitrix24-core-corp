import * as Mixins from '../base/components/mixins';

const FieldBool = {
	mixins: [Mixins.MixinField],
	template: `	
		<label class="b24-form-control-container"
			@click.capture="$emit('input-click', $event)"
		>
			<input type="checkbox" 
				v-model="field.item().selected"
				@blur="$emit('input-blur')"
				@focus="$emit('input-focus')"
			>
			<span class="b24-form-control-desc">{{ field.label }}</span>
			<span v-show="field.required" class="b24-form-control-required">*</span>
			<field-item-alert v-bind:field="field"></field-item-alert>
		</label>
	`,
};

export {
	FieldBool,
}
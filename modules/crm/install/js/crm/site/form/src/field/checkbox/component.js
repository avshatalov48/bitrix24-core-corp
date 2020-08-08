import * as Mixins from '../base/components/mixins';

const FieldCheckbox = {
	mixins: [Mixins.MixinField],
	template: `
		<div class="b24-form-control-container">
			<span class="b24-form-control-label">
				{{ field.label }} 
				<span v-show="field.required" class="b24-form-control-required">*</span>
			</span>

			<label class="b24-form-control"
				v-for="item in field.items"
				:class="{'b24-form-control-checked': item.selected}"
			>
				<input :type="field.type" 
					:value="item.value"
					v-model="selected"
					@blur="$emit('input-blur')"
					@focus="$emit('input-focus')"
				>
				<span class="b24-form-control-desc">{{ item.label }}</span>
			</label>
			<field-item-image-slider v-bind:field="field"></field-item-image-slider>
			<field-item-alert v-bind:field="field"></field-item-alert>
		</div>
	`,
};

export {
	FieldCheckbox,
}
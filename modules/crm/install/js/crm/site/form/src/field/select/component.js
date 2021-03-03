import * as Mixins from '../base/components/mixins';

const FieldSelect = {
	mixins: [Mixins.MixinField],
	template: `
		<div class="field-item">
			<label>
				<div class="b24-form-control-select-label">
					{{ field.label }} 
					<span v-show="field.required" class="b24-form-control-required">*</span>
				</div>
				<div>
					<select 
						v-model="selected"
						v-bind:multiple="field.multiple"
						@blur="$emit('input-blur', this)"
						@focus="$emit('input-focus', this)"
					>
						<option v-for="item in field.items" 
							v-bind:value="item.value"																
						>
							{{ item.label }}
						</option>
					</select>
				</div>
			</label>
			<field-item-image-slider v-bind:field="field"></field-item-image-slider>
			<field-item-alert v-bind:field="field"></field-item-alert>
		</div>
	`,
};

export {
	FieldSelect,
}
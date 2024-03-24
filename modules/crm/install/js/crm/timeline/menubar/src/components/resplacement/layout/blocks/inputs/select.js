import { Type } from 'main.core';

export default
{
	emits: ['update:modelValue'],
	props: {
		modelValue: String,
		values: Array,
		disabled: Boolean,
	},
	data(): Object
	{
		return {
			currentValue: this.getSelectedValue(this.modelValue),
		};
	},
	methods: {
		onChange(): void
		{
			this.$emit('update:modelValue', this.currentValue);
		},
		getSelectedValue(valueCandidate): string
		{
			if (!Type.isArray(this.values))
			{
				return '';
			}

			if (this.values.some((item) => item.id === valueCandidate))
			{
				return valueCandidate;
			}

			return this.values.length > 0 ? this.values[0].id : '';
		},
	},
	watch: {
		modelValue(newValue)
		{
			this.currentValue = this.getSelectedValue(newValue);
		},
	},
	template: `
		<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
			<div class="ui-ctl-after ui-ctl-icon-angle"></div>
			<select :disabled="disabled" v-model="currentValue" @change="onChange" class="ui-ctl-element">
				<option :value="option.id" :key="option.id" :selected="option.id===currentValue" v-for="option in values">{{ option.value }}</option>
			</select>
		</div>
	`,
};

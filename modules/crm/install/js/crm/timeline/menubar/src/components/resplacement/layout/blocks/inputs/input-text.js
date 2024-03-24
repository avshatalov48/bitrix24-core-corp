export default
{
	emits: ['update:modelValue'],
	props: {
		modelValue: String,
		placeholder: String,
		disabled: Boolean,
	},
	data(): Object
	{
		return {
			currentValue: this.modelValue,
		};
	},
	methods: {
		onChange(): void
		{
			this.$emit('update:modelValue', this.currentValue);
		},
	},
	watch: {
		modelValue(newValue)
		{
			this.currentValue = newValue;
		},
	},
	template: `
		<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
			<input :placeholder="placeholder" :disabled="disabled" v-model="currentValue" @input="onChange" type="text" class="ui-ctl-element" />
		</div>
	`,
};

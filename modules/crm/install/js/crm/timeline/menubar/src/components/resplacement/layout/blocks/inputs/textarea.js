import { Dom } from 'main.core';
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
	mounted()
	{
		this.adjustTextareaHeight();
	},
	methods: {
		onChange(): void
		{
			this.$emit('update:modelValue', this.currentValue);
			this.adjustTextareaHeight();
		},
		adjustTextareaHeight(): void
		{
			const textareaNode = this.$refs.textarea;
			this.$nextTick(() => {
				Dom.style(textareaNode, 'height', 0);

				let height = textareaNode.scrollHeight;

				if (height < 120)
				{
					height = 120;
				}

				if (height > 1000)
				{
					height = 1000;
				}

				height += 12;
				height += 'px';

				Dom.style(textareaNode, 'height', height);
				Dom.style(textareaNode.parentNode, 'height', height);
			});
		},
	},
	watch: {
		modelValue(newValue)
		{
			this.currentValue = newValue;
			this.$nextTick(() => {
				this.adjustTextareaHeight(this.$refs.textarea);
			});
		},
	},
	template: `
		<div class="ui-ctl ui-ctl-textarea ui-ctl-w100 ui-ctl-no-resize">
			<textarea ref="textarea" :placeholder="placeholder" :disabled="disabled" v-model="currentValue" @input="onChange" class="ui-ctl-element"></textarea>
		</div>
	`,
};

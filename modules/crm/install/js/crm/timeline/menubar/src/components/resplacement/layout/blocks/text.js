import TextColor from '../../enums/text-color';
import TextSize from '../../enums/text-size';
import { Text } from 'main.core';

export default {
	inheritAttrs: false,
	props: {
		value: String | Number,
		title: {
			type: String,
			required: false,
			default: '',
		},
		color: {
			type: String,
			required: false,
			default: '',
		},
		bold: {
			type: Boolean,
			required: false,
			default: false,
		},
		size: {
			type: String,
			required: false,
			default: 'md',
		},
		multiline: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		className(): Array {
			return [
				'crm-timeline__text-block',
				this.colorClassname,
				this.boldClassname,
				this.sizeClassname,
			];
		},
		colorClassname(): string {
			const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
			const color = TextColor[upperCaseColorProp] ?? '';

			return color ? `--color-${color}` : '';
		},

		boldClassname(): string {
			const weight = this.bold ? 'bold' : 'normal';

			return `--weight-${weight}`;
		},

		sizeClassname(): string {
			const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseWeightProp] ?? TextSize.SM;

			return `--size-${size}`;
		},
		encodedText(): string {
			let text = Text.encode(this.value);
			if (this.multiline)
			{
				text = text.replace(/\n/g, '<br />');
			}

			return text;
		},
	},
	template: `
		<span
			:title="title"
			:class="className"
			v-html="encodedText"
		></span>`,
};

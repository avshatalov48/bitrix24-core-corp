import { Text } from 'main.core';

import { TextColor } from '../enums/text-color';
import { TextWeight } from '../enums/text-weight';
import { TextSize } from '../enums/text-size';
import { TextDecoration } from '../enums/text-decoration';

export default {
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
		weight: {
			type: String,
			required: false,
			default: 'normal',
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
		decoration: {
			type: String,
			required: false,
			default: '',
		},
	},
	computed: {
		className(): Array
		{
			return [
				'crm-timeline__text-block',
				this.colorClassname,
				this.weightClassname,
				this.sizeClassname,
				this.decorationClassname,
			];
		},

		colorClassname(): string
		{
			const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
			const color = TextColor[upperCaseColorProp] ?? '';

			return `--color-${color}`;
		},

		weightClassname(): string
		{
			const upperCaseWeightProp = this.weight ? this.weight.toUpperCase() : '';
			const weight = TextWeight[upperCaseWeightProp] ?? TextWeight.NORMAL;

			return `--weight-${weight}`;
		},

		sizeClassname(): string
		{
			const upperCaseSizeProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseSizeProp] ?? TextSize.SM;

			return `--size-${size}`;
		},

		decorationClassname(): string
		{
			const upperCaseDecorationProp = this.decoration ? this.decoration.toUpperCase() : '';
			if (!upperCaseDecorationProp)
			{
				return '';
			}

			const decoration = TextDecoration[upperCaseDecorationProp] ?? TextDecoration.NONE;

			return `--decoration-${decoration}`;
		},

		encodedText(): string
		{
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
		></span>
	`,
};

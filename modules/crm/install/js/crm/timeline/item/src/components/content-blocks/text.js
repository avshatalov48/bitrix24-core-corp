import { TextColor } from '../enums/text-color';
import { TextWeight } from '../enums/text-weight';
import { TextSize } from '../enums/text-size';
import {Text} from "main.core";

export default {
	props: {
		value: String|Number,
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
	},
	computed: {
		className(): Array {
			return [
				'crm-timeline__text-block',
				this.colorClassname,
				this.weightClassname,
				this.sizeClassname,
			]
		},
		colorClassname(): string {
			const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
			const color = TextColor[upperCaseColorProp] ? TextColor[upperCaseColorProp] : '';
			return `--color-${color}`;
		},

		weightClassname(): string {
			const upperCaseWeightProp = this.weight ? this.weight.toUpperCase() : '';
			const weight = TextWeight[upperCaseWeightProp] ? TextWeight[upperCaseWeightProp] : TextWeight.NORMAL;
			return `--weight-${weight}`
		},

		sizeClassname(): string {
			const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseWeightProp] ? TextSize[upperCaseWeightProp] : TextSize.SM;
			return `--size-${size}`;
		},
		encodedText(): string {
			let text = Text.encode(this.value);
			if (this.multiline)
			{
				text = text.replace(/\n/g, '<br />');
			}

			return text;
		}
	},
	template: `
		<span
			:title="title"
			:class="className"
			v-html="encodedText"
		></span>`
};

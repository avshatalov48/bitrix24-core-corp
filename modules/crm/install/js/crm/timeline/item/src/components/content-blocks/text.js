import { TextColor } from '../enums/text-color';
import { TextWeight } from '../enums/text-weight';
import { TextSize } from '../enums/text-size';

export default {
	props: {
		value: String,
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
		}
	},
	computed: {
		className() {
			return [
				'crm-timeline__text-block',
				this.colorClassname,
				this.weightClassname,
				this.sizeClassname,
			]
		},
		colorClassname() {
			const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
			const color = TextColor[upperCaseColorProp] ? TextColor[upperCaseColorProp] : '';
			return `--color-${color}`;
		},

		weightClassname() {
			const upperCaseWeightProp = this.weight ? this.weight.toUpperCase() : '';
			const weight = TextWeight[upperCaseWeightProp] ? TextWeight[upperCaseWeightProp] : TextWeight.NORMAL;
			return `--weight-${weight}`
		},

		sizeClassname() {
			const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseWeightProp] ? TextSize[upperCaseWeightProp] : TextSize.SM;
			return `--size-${size}`;
		},
	},
	template: `
		<span
			:title="value"
			:class="className"
		>
			{{value}}
		</span>`
};

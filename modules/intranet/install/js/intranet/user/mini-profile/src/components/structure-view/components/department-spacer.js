export const DepartmentSpacer = {
	name: 'DepartmentSpacer',
	props: {
		value: {
			type: Number,
			required: true,
		},
		isVertical: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		style(): Object
		{
			const value = `${this.value}px`;

			if (!this.isVertical)
			{
				return {
					width: value,
					minWidth: value,
				};
			}

			return {
				height: value,
				minHeight: value,
			};
		},
	},
	template: `
		<div :style="style"/>
	`,
};

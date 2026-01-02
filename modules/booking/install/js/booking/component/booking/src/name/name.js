// @vue/component

import './name.css';

export const Name = {
	name: 'Name',
	props: {
		name: {
			type: String,
			default: '',
		},
		className: {
			type: [String, Object, Array],
			default: '',
		},
		dataAttributes: {
			type: Object,
			default: () => ({}),
		},
	},
	template: `
		<div
			class="booking--booking-base-name"
			:title="name"
			:class="className"
			v-bind="$props.dataAttributes"
		>
			{{ name }}
		</div>
	`,
};

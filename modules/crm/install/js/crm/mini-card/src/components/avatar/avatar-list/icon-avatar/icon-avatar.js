import { BitrixVueComponentProps } from 'ui.vue3';
import { BIcon } from 'ui.icon-set.api.vue';

import { Avatar } from '../../layout/avatar/avatar';

import 'ui.icon-set.crm';
import 'ui.icon-set.outline';

import './icon-avatar.css';

export const IconAvatar: BitrixVueComponentProps = {
	name: 'IconAvatar',

	components: {
		BIcon,
		Avatar,
	},

	props: {
		iconOptions: {
			type: Object,
			required: true,
		},
		bgColor: {
			type: String,
			default: null,
		},
	},

	computed: {
		containerStyles(): Object
		{
			const styleBgColor = this.styleBgColor();
			if (styleBgColor === null)
			{
				return {};
			}

			return {
				backgroundColor: styleBgColor,
			};
		},
	},

	methods: {
		styleBgColor(): ?string
		{
			if (!this.bgColor)
			{
				return null;
			}

			if (this.bgColor.startsWith('--'))
			{
				return `var(${this.bgColor})`;
			}

			return this.bgColor;
		},
	},

	template: `
		<Avatar class="--icon" :style="containerStyles">
			<BIcon v-bind="iconOptions" />
		</Avatar>
	`,
};

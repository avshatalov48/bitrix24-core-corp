import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import './add-background.css';

// @vue/component
export const AddBackground = {
	name: 'AddBackground',
	components: {
		BIcon,
	},
	props: {
		isActive: {
			type: Boolean,
			default: false,
		},
	},
	setup(): Object
	{
		return {
			Outline,
		};
	},
	data(): Object
	{
		return {
			isHovered: false,
		};
	},
	template: `
		<div>
			<div
				class="b24-add-background"
				@mouseenter="isHovered = true"
				@mouseleave="isHovered = false"
			></div>
			<div
				class="b24-add-background-button"
				:class="{ '--active': isHovered || isActive }"
			>
				<BIcon :name="Outline.PLUS_L"/>
			</div>
		</div>
	`,
};

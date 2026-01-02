import { BIcon, Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import './hover-pill.css';

// @vue/component
export const HoverPill = {
	components: {
		BIcon,
	},
	props: {
		withClear: {
			type: Boolean,
			default: false,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
		transparentHover: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['clear'],
	setup(): Object
	{
		return {
			Outline,
		};
	},
	template: `
		<div
			class="b24-hover-pill"
			:class="{ '--readonly': readonly, '--transparent': transparentHover }"
			tabindex="0"
		>
			<div class="b24-hover-pill-content">
				<slot/>
			</div>
			<div v-if="withClear" class="b24-hover-pill-remover" @click.capture.stop="$emit('clear')">
				<BIcon :name="Outline.CROSS_L"/>
			</div>
		</div>
	`,
};

import { BitrixVueComponentProps } from 'ui.vue3';

import './avatar.css';

export const Avatar: BitrixVueComponentProps = {
	name: 'Avatar',

	template: `
		<div class="crm-mini-card__avatar">
			<slot />
		</div>
	`,
};

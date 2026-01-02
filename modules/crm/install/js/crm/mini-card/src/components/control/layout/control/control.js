import { BitrixVueComponentProps } from 'ui.vue3';

import './control.css';

export const Control: BitrixVueComponentProps = {
	name: 'Control',
	template: `
		<div class="crm-mini-card__control">
			<slot />
		</div>
	`,
};

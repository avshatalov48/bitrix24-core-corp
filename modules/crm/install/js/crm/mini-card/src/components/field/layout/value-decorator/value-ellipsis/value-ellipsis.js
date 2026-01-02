import { BitrixVueComponentProps } from 'ui.vue3';

import './value-ellipsis.css';

export const ValueEllipsis: BitrixVueComponentProps = {
	name: 'ValueEllipsis',
	template: `
		<span class="crm-mini-card__value-ellipsis">
			<slot/>
		</span>
	`,
};

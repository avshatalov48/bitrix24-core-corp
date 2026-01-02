import { BitrixVueComponentProps } from 'ui.vue3';

import './field-title.css';
export const FieldTitle: BitrixVueComponentProps = {
	template: `
		<div class="crm-mini-card__field-title">
			<slot />
		</div>
	`,
};

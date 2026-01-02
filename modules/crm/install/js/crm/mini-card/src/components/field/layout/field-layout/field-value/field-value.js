import { BitrixVueComponentProps } from 'ui.vue3';

import './field-value.css';

export const FieldValue: BitrixVueComponentProps = {
	template: `
		<div class="crm-mini-card__field-value">
			<slot />
		</div>
	`,
};

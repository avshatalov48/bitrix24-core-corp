import { BitrixVueComponentProps } from 'ui.vue3';

import './field-value-list.css';
export const FieldValueList: BitrixVueComponentProps = {
	template: `
		<div class="crm-mini-card__field-value-list">
			<slot />
		</div>
	`,
};

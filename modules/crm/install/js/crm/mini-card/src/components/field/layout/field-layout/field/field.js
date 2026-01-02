import { BitrixVueComponentProps } from 'ui.vue3';

import './field.css';

export const Field: BitrixVueComponentProps = {
	template: `
		<div class="crm-mini-card__field">
			<slot />
		</div>
	`,
};

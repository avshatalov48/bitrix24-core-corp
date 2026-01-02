import { BitrixVueComponentProps } from 'ui.vue3';

import './footer-note.css';

export const FooterNote: BitrixVueComponentProps = {
	name: 'FooterNote',
	template: `
		<div class="crm-mini-card-content__footer-note">
			<slot />
		</div>
	`,
};

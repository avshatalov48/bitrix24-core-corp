import { BitrixVueComponentProps } from 'ui.vue3';

import { FooterNote } from '../../layout/footer-note/footer-note';

import './common-footer-note.css';

export const CommonFooterNote: BitrixVueComponentProps = {
	name: 'CommonFooterNote',

	components: {
		FooterNote,
	},

	props: {
		content: String,
	},

	template: `
		<FooterNote class="--common">
			{{ content }}
		</FooterNote>
	`,
};

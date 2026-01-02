import { BitrixVueComponentProps } from 'ui.vue3';

import './show-more.css';

export const ShowMore: BitrixVueComponentProps = {
	name: 'ShowMore',

	props: {
		count: {
			type: Number,
			required: true,
		},
	},

	computed: {
		showMoreTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_MINI_CARD_SHOW_MORE', {
				'#COUNT#': this.count,
			});
		},
	},

	template: `
		<span class="crm-mini-card__show-more">
			{{ showMoreTitle }}
		</span>
	`,
};

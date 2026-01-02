import { CrmButton } from 'booking.component.booking';
import { WaitListDealHelper } from 'booking.lib.deal-helper';
import type { WaitListItemModel } from 'booking.model.wait-list';

// @vue/component
export const WaitListItemCrmButton = {
	name: 'WaitListItemCrmButton',
	components: {
		CrmButton,
	},
	props: {
		/**
		 * @type {WaitListItemModel}
		 */
		waitListItem: {
			type: Object,
			required: true,
		},
	},
	setup(props): { dealHelper: WaitListDealHelper }
	{
		const dealHelper = new WaitListDealHelper(props.waitListItem.id);

		return {
			dealHelper,
		};
	},
	template: `
		<CrmButton
			:dealHelper
			:dataAttributes="{
				'data-wait-list-item-id': waitListItem.id,
				'data-element': 'wait-list-item-crm-button'
			}"
		/>
	`,
};

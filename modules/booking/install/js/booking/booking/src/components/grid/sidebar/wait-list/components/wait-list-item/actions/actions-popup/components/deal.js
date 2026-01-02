import { CrmEntity, Model } from 'booking.const';
import { WaitListDealHelper } from 'booking.lib.deal-helper';
import { Deal } from 'booking.component.actions-popup';
import type { DealData, WaitListItemModel } from 'booking.model.wait-list';

type WaitListItemDealData = {
	dealHelper: WaitListDealHelper;
}

// @vue/component
export const WaitListItemDeal = {
	name: 'WaitListItemDeal',
	components: {
		Deal,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	emits: ['freeze', 'unfreeze'],
	setup(props): WaitListItemDealData
	{
		const dealHelper = new WaitListDealHelper(props.waitListItemId);

		return {
			dealHelper,
		};
	},
	computed: {
		waitListItem(): WaitListItemModel
		{
			return this.$store.getters[`${Model.WaitList}/getById`](this.waitListItemId);
		},
		deal(): DealData | null
		{
			return this.waitListItem?.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null;
		},
	},
	template: `
		<Deal
			:deal="deal"
			:dealHelper="dealHelper"
			:dataId="waitListItemId"
			:dataAttributes="{
				'data-wait-list-item-id': waitListItemId,
			}"
			dataElementPrefix="wait-list"
			@freeze="$emit('freeze')"
			@unfreeze="$emit('unfreeze')"
		/>
	`,
};

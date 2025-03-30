import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.crm';

import { Model } from 'booking.const';
import { limit } from 'booking.lib.limit';
import { DealHelper } from 'booking.lib.deal-helper';
import './crm-button.css';

export const CrmButton = {
	props: {
		bookingId: [Number, String],
		required: true,
	},
	data(): Object
	{
		return {
			IconSet,
		};
	},
	created(): void
	{
		this.dealHelper = new DealHelper(this.bookingId);
	},
	computed: {
		hasDeal(): boolean
		{
			return this.dealHelper.hasDeal();
		},
		isFeatureEnabled(): boolean
		{
			return this.$store.getters[`${Model.Interface}/isFeatureEnabled`];
		},
	},
	methods: {
		onClick(): void
		{
			if (!this.isFeatureEnabled)
			{
				void limit.show();

				return;
			}

			if (this.hasDeal)
			{
				this.dealHelper.openDeal();
			}
			else
			{
				this.dealHelper.createDeal();
			}
		},
	},
	components: {
		Icon,
	},
	template: `
		<Icon
			:name="IconSet.CRM_LETTERS"
			class="booking-booking-booking-crm-button"
			:class="{'--no-deal': !hasDeal}"
			data-element="booking-crm-button"
			:data-booking-id="bookingId"
			@click="onClick"
		/>
	`,
};

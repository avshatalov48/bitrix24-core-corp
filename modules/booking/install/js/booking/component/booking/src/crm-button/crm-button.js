// @vue/component

import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.crm';

import { Model } from 'booking.const';
import { limit } from 'booking.lib.limit';
import { DealHelper } from 'booking.lib.deal-helper';
import './crm-button.css';

export const CrmButton = {
	name: 'CrmButton',
	components: {
		Icon,
	},
	props: {
		dealHelper: {
			type: DealHelper,
			required: true,
		},
		dataAttributes: {
			type: Object,
			default: () => ({}),
		},
	},
	setup(): Object
	{
		return {
			IconSet,
		};
	},
	computed: {
		isFeatureEnabled(): boolean
		{
			return this.$store.getters[`${Model.Interface}/isFeatureEnabled`];
		},
		hasDeal(): boolean
		{
			return this.dealHelper.hasDeal();
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
	template: `
		<Icon
			:name="IconSet.CRM_LETTERS"
			class="booking--booking-base-crm-button"
			:class="{'--no-deal': !hasDeal}"
			v-bind="$props.dataAttributes"
			@click="onClick"
		/>
	`,
};

import { BitrixVue } from 'ui.vue';
import { MixinResetPanel } from 'sale.payment-pay.mixins.payment-system';

BitrixVue.component('salescenter-payment_pay-components-payment_system-reset_panel', {
	mixins:[MixinResetPanel],
	computed:
	{
		localize()
		{
			return Object.freeze(
				BitrixVue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_')
			);
		},
	},
	// language=Vue
	template: `
		<div class="order-payment-buttons-container">
			<div class="order-basket-section-description py-3">
				{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_6 }}
			</div>
			<div class="order-basket-section-another-payment-button">
				<salescenter-payment_pay-components-payment_system-button @click="reset()">
					{{ localize.PAYMENT_PAY_PAYMENT_SYSTEM_COMPONENTS_7 }}
				</salescenter-payment_pay-components-payment_system-button>
			</div>
		</div>	
	`,
});

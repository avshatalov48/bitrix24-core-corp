import { BitrixVue } from 'ui.vue';
import { MixinPaymentInfoButton } from 'sale.payment-pay.mixins.payment-system';

BitrixVue.component('salescenter-payment_pay-components-payment_system-payment_info-button', {
	props:
	{
		loading: {
			type: Boolean,
			default: false,
			required: false,
		},
	},
	mixins:[MixinPaymentInfoButton],
	computed: {
		classes()
		{
			const classes = [
				'landing-block-node-button',
				'text-uppercase',
				'btn',
				'btn-xl',
				'pr-7',
				'pl-7',
				'u-btn-primary',
				'g-font-weight-700',
				'g-font-size-12',
				'g-rounded-50',
			];

			if (this.loading)
			{
				classes.push('loading');
			}

			return classes;
		},
	},
	// language=Vue
	template: `
		<button :class="classes" @click="onClick($event)">
			<slot></slot>
		</button>
	`,
});

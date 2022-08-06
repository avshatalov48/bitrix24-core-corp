import { BitrixVue } from 'ui.vue';
import { MixinPaySystemList } from 'sale.payment-pay.mixins.payment-system';

BitrixVue.component('salescenter-payment_pay-components-payment_system-pay_system_list', {
	props: {
		paySystems: {
			type: Array,
			default: [],
			required: false,
		},
		selectedPaySystem: {
			type: Number,
			default: null,
			required: false,
		},
		loading: {
			type: Boolean,
			default: false,
			required: false,
		},
		title: {
			type: String,
			default: null,
			required: false,
		},
	},
	mixins:[MixinPaySystemList],
	// language=Vue
	template: `
		<div>
			<div class="page-section-title" v-if="title">{{ title }}</div>
			<div class="order-payment-method-list">
				<slot>
					<salescenter-payment_pay-components-payment_system-pay_system_row 
						v-for="paySystem in paySystems"
						:loading="isItemLoading(paySystem.ID)"
						:name="paySystem.NAME"
						:logo="paySystem.LOGOTIP"
						:id="paySystem.ID"
						@click="startPayment($event)"
					/>
				</slot>
			</div>
            <slot name="user-consent"></slot>
		</div>
	`,
});
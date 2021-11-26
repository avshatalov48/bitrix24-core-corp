import {BitrixVue} from 'ui.vue';
import Button from './button';

export default {
	props: {
		loading: Boolean,
		name: String,
		logo: String,
		id: Number,
	},
	components: {
		Button,
	},
	methods: {
		onClick() {
			this.$emit('click', this.id);
		},
	},
	computed: {
		logoStyle() {
			const defaultLogo = '/bitrix/js/salescenter/payment-pay/payment-method/images/default_logo.png';
			const src = this.logo || defaultLogo;

			return `background-image: url("${BX.util.htmlspecialchars(src)}")`;
		},
		loc() {
			return BitrixVue.getFilteredPhrases('SPP_');
		},
	},
	// language=Vue
	template: `
		<div class="order-pay-method-item-container pay-mode" @click="onClick()">
			<div class="order-pay-method-item-logo-block">
				<div class="order-pay-method-logo" :style="logoStyle"></div>
			</div>
			<div class="order-pay-method-text-block">
				<div class="order-pay-method-text">{{ name }}</div>
			</div>
			<Button :loading="loading">{{ loc.SPP_PAY_BUTTON }}</Button>
		</div>
	`,
};

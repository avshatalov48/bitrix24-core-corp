import {BitrixVue} from 'ui.vue';

export default {
	props: {
		paySystems: {
			type: Array,
			default: [],
			required: false,
		},
	},
	data() {
		return {
			selectedPaySystem: null,
		};
	},
	computed: {
		loc() {
			return BitrixVue.getFilteredPhrases('SPP_');
		},
		selectedName() {
			return this.selectedPaySystem ? this.selectedPaySystem.NAME : '';
		},
		selectedDescription() {
			return this.selectedPaySystem ? BX.util.htmlspecialchars(this.selectedPaySystem.DESCRIPTION) : '';
		}
	},
	methods: {
		showInfo(paySystem) {
			this.selectedPaySystem = paySystem;
		},
		logoStyle(paySystem) {
			const defaultLogo = '/bitrix/js/salescenter/payment-pay/payment-method/images/default_logo.png';
			const src = paySystem.LOGOTIP || defaultLogo;

			return `background-image: url("${BX.util.htmlspecialchars(src)}")`;
		},
	},
	// language=Vue
	template: `
		<div>
			<div class="order-payment-method-list">
				<slot>
					<div class="order-pay-method-item-container info-mode" v-for="paySystem in paySystems">
						<div class="order-pay-method-item-logo-block">
							<div class="order-pay-method-logo" :style="logoStyle(paySystem)"></div>
						</div>
						<div class="order-pay-method-text-block">
							<div class="order-pay-method-text">{{ paySystem.NAME }}</div>
						</div>
						<div class="btn info-mode" @click="showInfo(paySystem)">{{ loc.SPP_INFO_BUTTON }}</div>
					</div>
				</slot>
			</div>
			<div class="order-payment-method-description" v-if="selectedPaySystem">
				<div class="order-payment-method-description-title">{{ selectedName }}</div>
				<div class="order-payment-method-description-text" v-html="selectedDescription"></div>
			</div>
		</div>
	`,
};
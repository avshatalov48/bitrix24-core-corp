import {BitrixVue} from 'ui.vue';
import Button from './button';

export default {
	components: {
		Button,
	},
	methods: {
		reset() {
			this.$emit('reset');
		},
	},
	computed: {
		loc() {
			return BitrixVue.getFilteredPhrases('SPP_');
		},
	},
	// language=Vue
	template: `
		<div class="order-payment-buttons-container">
			<div class="order-basket-section-description py-3">
				{{ loc.SPP_EMPTY_TEMPLATE_FOOTER }}
			</div>
			<Button @click="reset()">
				{{ loc.SPP_PAY_RELOAD_BUTTON_NEW }}
			</Button>
		</div>	
	`,
};

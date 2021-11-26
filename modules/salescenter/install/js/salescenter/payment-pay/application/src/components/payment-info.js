import {BitrixVue} from 'ui.vue';
import Check from './check';
import PaySystemCard from './payment-info/pay-system-small-card';
import Button from './payment-info/button';

export default {
	props: {
		paySystem: Object,
		title: String,
		sum: String,
		loading: Boolean,
		paid: Boolean,
		checks: Array,
	},
	components: {
		Check,
		PaySystemCard,
		Button,
	},
	methods: {
		onClick() {
			this.$emit('start-payment', this.paySystem.ID);
		},
	},
	computed: {
		loc() {
			return BitrixVue.getFilteredPhrases('SPP_');
		},
		totalSum() {
			return this.loc.SPP_SUM.replace('#SUM#', this.sum);
		},
	},
	// language=Vue
	template: `
		<div>
			<div class="order-payment-title" v-if="title">{{ title }}</div>
			<div class="order-payment-inner d-flex align-items-center justify-content-between">
				<PaySystemCard :name="paySystem.NAME" :logo="paySystem.LOGOTIP"/>
            	<div class="order-payment-status d-flex align-items-center" v-if="paid">
                	<div class="order-payment-status-ok"></div>
                	<div>{{ loc.SPP_PAID }}</div>
				</div>
				<div class="order-payment-price" v-html="totalSum"></div>
			</div>
			<hr v-if="checks.length > 0">
			<Check 
				v-for="check in checks" 
				:title="check.title" 
				:link="check.link" 
				:status="check.status"/>
			<hr v-if="!paid">
            <slot name="user-consent" v-if="!paid"></slot>
			<div class="order-payment-buttons-container" v-if="!paid">
				<Button
					:loading="loading"
					@click="onClick()">
					{{ loc.SPP_PAY_BUTTON }}
				</Button>
			</div>
		</div>
	`,
};
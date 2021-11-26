import PaySystemRow from './pay-system-row';

export default {
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
	components: {
		PaySystemRow,
	},
	methods: {
		isItemLoading(paySystemId) {
			return (this.selectedPaySystem === paySystemId) && this.loading;
		},
		startPayment(paySystemId) {
			this.$emit('start-payment', paySystemId);
		},
	},
	// language=Vue
	template: `
		<div>
			<div class="page-section-title" v-if="title">{{ title }}</div>
			<div class="order-payment-method-list">
				<slot>
					<PaySystemRow 
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
};
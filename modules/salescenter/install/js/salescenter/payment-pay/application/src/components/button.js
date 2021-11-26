export default {
	props: {
		loading: {
			type: Boolean,
			default: false,
			required: false,
		},
	},
	computed: {
		classes() {
			return {
				'order-payment-method-item-button': true,
				'btn': true,
				'btn-primary': true,
				'rounded-pill': true,
				'pay-mode': true,
				'order-payment-loader': this.loading,
			};
		},
	},
	methods: {
		onClick(event) {
			this.$emit('click', event);
		},
	},
	// language=Vue
	template: `
		<div :class="classes" @click="onClick($event)">
			<slot></slot>
		</div>
	`,
};

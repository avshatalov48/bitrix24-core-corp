export default {
	props: {
		logo: String,
		name: String,
	},
	template: `
		<div class="order-payment-operator">
			<img :src="logo" :alt="name" v-if="logo">
			<div class="order-payment-pay-system-name" v-else>{{ name }}</div>
		</div>
	`,
};
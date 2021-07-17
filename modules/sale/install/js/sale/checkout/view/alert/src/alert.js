import {Vue} from 'ui.vue';

Vue.component('sale-checkout-view-alert', {
	props: ['error'],
	// language=Vue
	template: `
		<div class="checkout-form-alert">
			<div class="checkout-form-alert-icon"></div>
			<span class="text-danger">{{this.error.message}}</span>
		</div>
	`
});
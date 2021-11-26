import {Vue} from 'ui.vue';
import './style.css'

Vue.component('salescenter-payment_pay-payment_method-list', {
	props: ['items'],
	data()
	{
		return {
			list: []
		}
	},
	computed:
	{
		localize() {
			return Object.freeze(
				Vue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_METHOD_'))
		}
	},
	methods:
	{
		showDescription(item)
		{
			item.SHOW_DESCRIPTION = item.SHOW_DESCRIPTION === 'Y' ? 'N':'Y';
		},

		isShow(item)
		{
			return item.SHOW_DESCRIPTION === 'Y'
		},

		beforeEnter: function (item)
		{
			item.style.opacity = 0;
			item.style.maxHeight = 0;
		},

		enter: function (item)
		{
			var delay = item.dataset.index * 150
			setTimeout(function () {
				item.style.opacity = 1;
				item.style.maxHeight = item.scrollHeight + 'px';
			}, delay)
		},

		afterEnter: function (item)
		{
			item.style.opacity = "";
			item.style.maxHeight = "";
		},

		beforeLeave: function (item)
		{
			item.style.opacity = 1;
			item.style.maxHeight = item.scrollHeight + 'px';
		},

		leave: function (item)
		{
			var delay = item.dataset.index * 150
			setTimeout(function () {
				item.style.opacity = 0;
				item.style.maxHeight = 0;
			}, delay)
		},

		getLogoSrc(item)
		{
			return (
				item.LOGOTIP
					? item.LOGOTIP
					: '/bitrix/js/salescenter/payment-pay/payment-method/images/default_logo.png'
			);
		}
	},
	// language=Vue
	template: `
		<div class="checkout-basket-section">
			<h2 class="landing-block-node-title h2 text-left g-mb-15 g-font-weight-500 g-font-size-20">{{localize.PAYMENT_PAY_PAYMENT_METHOD_1}}</h2>
			<div class="checkout-basket-pay-method-list">
				<div class="checkout-basket-pay-method-item-container" v-for="(item, index) in items">
					<div class="checkout-basket-pay-method-item-logo-block">
						<div class="checkout-basket-pay-method-logo" :style="'background-image: url(\\'' + getLogoSrc(item) + '\\')'"></div>
					</div>
					<div class="checkout-basket-pay-method-text-block">
						<div class="checkout-basket-pay-method-text">{{item.NAME}}</div>
					</div>
					<div class="checkout-basket-pay-method-btn-block">
						<button class="checkout-checkout-btn-info border btn btn-sm rounded-pill" @click='showDescription(item)'>{{localize.PAYMENT_PAY_PAYMENT_METHOD_2}}</button>
					</div>
                  	<transition 
						name="fade"
                        duration="300"
                        v-on:before-enter="beforeEnter"
                        v-on:enter="enter"
                        v-on:after-enter="afterEnter"
                        v-on:before-leave="beforeLeave"
                        v-on:leave="leave"
					>
						<div class="checkout-basket-pay-method-description" v-if="isShow(item)">{{item.DESCRIPTION}}</div>
				  	</transition>
				</div>
			</div>
		</div>
	`
});

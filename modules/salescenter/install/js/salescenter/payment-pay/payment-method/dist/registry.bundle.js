this.BX = this.BX || {};
this.BX.SalesCenter = this.BX.SalesCenter || {};
this.BX.SalesCenter['Payment-Pay'] = this.BX.SalesCenter['Payment-Pay'] || {};
(function (exports,ui_vue) {
	'use strict';

	ui_vue.Vue.component('salescenter-payment_pay-payment_method-list', {
	  props: ['items'],
	  data: function data() {
	    return {
	      list: []
	    };
	  },
	  computed: {
	    localize: function localize() {
	      return Object.freeze(ui_vue.Vue.getFilteredPhrases('PAYMENT_PAY_PAYMENT_METHOD_'));
	    }
	  },
	  methods: {
	    showDescription: function showDescription(item) {
	      item.SHOW_DESCRIPTION = item.SHOW_DESCRIPTION === 'Y' ? 'N' : 'Y';
	    },
	    isShow: function isShow(item) {
	      return item.SHOW_DESCRIPTION === 'Y';
	    },
	    beforeEnter: function beforeEnter(item) {
	      item.style.opacity = 0;
	      item.style.maxHeight = 0;
	    },
	    enter: function enter(item) {
	      var delay = item.dataset.index * 150;
	      setTimeout(function () {
	        item.style.opacity = 1;
	        item.style.maxHeight = item.scrollHeight + 'px';
	      }, delay);
	    },
	    afterEnter: function afterEnter(item) {
	      item.style.opacity = "";
	      item.style.maxHeight = "";
	    },
	    beforeLeave: function beforeLeave(item) {
	      item.style.opacity = 1;
	      item.style.maxHeight = item.scrollHeight + 'px';
	    },
	    leave: function leave(item) {
	      var delay = item.dataset.index * 150;
	      setTimeout(function () {
	        item.style.opacity = 0;
	        item.style.maxHeight = 0;
	      }, delay);
	    },
	    getLogoSrc: function getLogoSrc(item) {
	      return item.LOGOTIP ? item.LOGOTIP : '/bitrix/js/salescenter/payment-pay/payment-method/images/default_logo.png';
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"checkout-basket-section\">\n\t\t\t<h2 class=\"landing-block-node-title h2 text-left g-mb-15 g-font-weight-500 g-font-size-20\">{{localize.PAYMENT_PAY_PAYMENT_METHOD_1}}</h2>\n\t\t\t<div class=\"checkout-basket-pay-method-list\">\n\t\t\t\t<div class=\"checkout-basket-pay-method-item-container\" v-for=\"(item, index) in items\">\n\t\t\t\t\t<div class=\"checkout-basket-pay-method-item-logo-block\">\n\t\t\t\t\t\t<div class=\"checkout-basket-pay-method-logo\" :style=\"'background-image: url(\\'' + getLogoSrc(item) + '\\')'\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checkout-basket-pay-method-text-block\">\n\t\t\t\t\t\t<div class=\"checkout-basket-pay-method-text\">{{item.NAME}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"checkout-basket-pay-method-btn-block\">\n\t\t\t\t\t\t<button class=\"checkout-checkout-btn-info border btn btn-sm rounded-pill\" @click='showDescription(item)'>{{localize.PAYMENT_PAY_PAYMENT_METHOD_2}}</button>\n\t\t\t\t\t</div>\n                  \t<transition \n\t\t\t\t\t\tname=\"fade\"\n                        duration=\"300\"\n                        v-on:before-enter=\"beforeEnter\"\n                        v-on:enter=\"enter\"\n                        v-on:after-enter=\"afterEnter\"\n                        v-on:before-leave=\"beforeLeave\"\n                        v-on:leave=\"leave\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"checkout-basket-pay-method-description\" v-if=\"isShow(item)\">{{item.DESCRIPTION}}</div>\n\t\t\t\t  \t</transition>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

}((this.BX.SalesCenter['Payment-Pay']['Payment-Method'] = this.BX.SalesCenter['Payment-Pay']['Payment-Method'] || {}),BX));
//# sourceMappingURL=registry.bundle.js.map

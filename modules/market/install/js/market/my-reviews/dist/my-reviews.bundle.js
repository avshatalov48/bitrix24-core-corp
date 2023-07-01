this.BX = this.BX || {};
(function (exports,ui_vue3_pinia,ui_vue3,market_myReviewsComponent) {
	'use strict';

	class MyReviews {
	  constructor(options = {}) {
	    this.params = options.params;
	    this.result = options.result;
	    ui_vue3.BitrixVue.createApp({
	      name: 'Market',
	      components: {
	        MyReviewsComponent: market_myReviewsComponent.MyReviewsComponent
	      },
	      data: () => {
	        return {
	          params: this.params,
	          result: this.result
	        };
	      },
	      computed: {},
	      mounted() {},
	      methods: {},
	      template: `
				<MyReviewsComponent
					:params="params"
					:result="result"
				/>
			`
	    }).use(ui_vue3_pinia.createPinia()).mount('#market-wrapper-vue');
	  }
	}

	exports.MyReviews = MyReviews;

}((this.BX.Market = this.BX.Market || {}),BX.Vue3.Pinia,BX.Vue3,BX.Market));

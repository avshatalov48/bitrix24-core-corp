this.BX = this.BX || {};
(function (exports,ui_vue3_pinia,ui_vue3,market_detailComponent) {
	'use strict';

	class Detail {
	  constructor(options = {}) {
	    this.params = options.params;
	    this.result = options.result;
	    ui_vue3.BitrixVue.createApp({
	      name: 'Market',
	      components: {
	        DetailComponent: market_detailComponent.DetailComponent
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
				<DetailComponent
					:params="params"
					:result="result"
				/>
			`
	    }).use(ui_vue3_pinia.createPinia()).mount('#market-wrapper-vue');
	  }
	}

	exports.Detail = Detail;

}((this.BX.Market = this.BX.Market || {}),BX.Vue3.Pinia,BX.Vue3,BX.Market));

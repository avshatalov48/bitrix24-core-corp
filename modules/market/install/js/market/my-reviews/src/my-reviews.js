import {createPinia} from 'ui.vue3.pinia';
import {BitrixVue} from "ui.vue3";
import {MyReviewsComponent} from "market.my-reviews-component";

export class MyReviews
{
	constructor(options = {})
	{
		this.params = options.params;
		this.result = options.result;

		(BitrixVue.createApp({
			name: 'Market',
			components: {
				MyReviewsComponent,
			},
			data: () => {
				return {
					params: this.params,
					result: this.result,
				};
			},
			computed: {

			},
			mounted() {

			},
			methods: {

			},
			template: `
				<MyReviewsComponent
					:params="params"
					:result="result"
				/>
			`,
		})).use(createPinia()).mount('#market-wrapper-vue');
	}
}
import {createPinia} from 'ui.vue3.pinia';
import {BitrixVue} from "ui.vue3";
import {DetailComponent} from "market.detail-component";

export class Detail
{
	constructor(options = {})
	{
		this.params = options.params;
		this.result = options.result;

		(BitrixVue.createApp({
			name: 'Market',
			components: {
				DetailComponent,
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
				<DetailComponent
					:params="params"
					:result="result"
				/>
			`,
		})).use(createPinia()).mount('#market-wrapper-vue');
	}
}
import {BitrixVue} from 'ui.vue';

export default {
	props: {
		errors: Array,
	},
	computed: {
		flattenErrorsList() {
			const list = [];
			this.errors.map((errorType) => {
				errorType.map((error) => list.push(error));
			});
			return list;
		},
		loc() {
			return BitrixVue.getFilteredPhrases('SPP_');
		},
	},
	// language=Vue
	template: `
		<div>
			<div class="alert alert-danger">
				<slot name="errors-header">
					<div>{{ loc.SPP_INITIATE_PAY_ERROR_TEXT_HEADER }}</div>
				</slot>
				<div v-for="error in flattenErrorsList">{{ error }}</div>
				<slot name="errors-footer">
					<div>{{ loc.SPP_INITIATE_PAY_ERROR_TEXT_FOOTER }}</div>
				</slot>
			</div>
			<slot></slot>
		</div>
	`,
};
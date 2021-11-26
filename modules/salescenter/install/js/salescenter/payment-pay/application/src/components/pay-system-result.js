import {BitrixVue} from 'ui.vue';

export default {
	props: {
		html: {
			type: String,
			default: null,
			required: false,
		},
		fields: {
			type: Object,
			default: null,
			required: false,
		},
	},
	computed: {
		loc() {
			return BitrixVue.getFilteredPhrases('SPP_');
		},
	},
	mounted()
	{
		if (this.html)
		{
			BX.html(this.$refs.paySystemResultTemplate, this.html);
		}
	},
	// language=Vue
	template: `
		<div>
			<template v-if="html">
				<div ref="paySystemResultTemplate"></div>
				<slot></slot>
			</template>
			<template v-else>
				<div class="checkout-basket-section">
					<div class="page-section-title">{{ loc.SPP_EMPTY_TEMPLATE_TITLE }}</div>
					<div class="checkout-basket-personal-order-info" v-if="fields">
						<div class="checkout-basket-personal-order-info-item" v-if="fields.SUM_WITH_CURRENCY">
							<span>{{ loc.SPP_EMPTY_TEMPLATE_SUM_WITH_CURRENCY_FIELD }}</span> <strong v-html="fields.SUM_WITH_CURRENCY"></strong>
						</div>
						<div class="checkout-basket-personal-order-info-item" v-if="fields.PAY_SYSTEM_NAME">
							<span>{{ loc.SPP_EMPTY_TEMPLATE_PAY_SYSTEM_NAME_FIELD }}</span> <strong>{{ fields.PAY_SYSTEM_NAME }}</strong>
						</div>
					</div>
				</div>
			</template>
		</div>
	`,
};

import Text from './text';
import { CurrencyCore } from 'currency.currency-core';
import { Type } from 'main.core';

export default {
	props: {
		opportunity: Number,
		currencyId: String,
	},
	computed: {
		encodedText(): ?string
		{
			if (!Type.isNumber(this.opportunity) || !Type.isStringFilled(this.currencyId))
			{
				return null;
			}

			return CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
		}
	},
	extends: Text,
	template: `
		<span
			v-if="encodedText"
			:class="className"
			v-html="encodedText"
		></span>`
};

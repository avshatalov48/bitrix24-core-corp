import { CurrencyCore } from "currency.currency-core";
import { Type } from "main.core";

export default {
	props: {
		opportunity: Number,
		currencyId: String,
	},
	computed: {
		moneyHtml(): ?string
		{
			if (!Type.isNumber(this.opportunity) || !Type.isStringFilled(this.currencyId))
			{
				return null;
			}

			return CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
		}
	},
	template: `<span v-if="moneyHtml" v-html="moneyHtml"></span>`
};

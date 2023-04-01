import {InfoPopupContentBlock} from "./InfoPopupContentBlock";
import {CurrencyCore} from "currency.currency-core";
import {Type} from "main.core";

export const InfoPopupContentBlockMoney = {
	extends: InfoPopupContentBlock,
	computed: {
		opportunity()
		{
			return this.attributes?.opportunity;
		},
		currencyId()
		{
			return this.attributes?.currencyId;
		},
		encodedText(): ?string
		{
			if (!Type.isNumber(this.opportunity) || !Type.isStringFilled(this.currencyId))
			{
				return null;
			}

			return CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
		}
	},
	template: `
		<span
			v-if="encodedText"
			v-html="encodedText"
		></span>
	`
}

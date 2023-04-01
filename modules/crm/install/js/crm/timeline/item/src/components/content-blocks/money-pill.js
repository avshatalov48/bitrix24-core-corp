import { CurrencyCore } from 'currency.currency-core';
import { Type } from 'main.core';

export const MoneyPill = {
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
	template: `
		<div class="crm-timeline-card__money-pill">
			<span class="crm-timeline-card__money-pill_amount">
				<span v-if="moneyHtml" v-html="moneyHtml"></span>
			</span>
		</div>
	`
}

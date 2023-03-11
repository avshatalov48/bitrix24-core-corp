import Money from './money';

export const MoneyPill = {
	components: {
		Money,
	},
	props: {
		opportunity: Number,
		currencyId: String,
	},
	template: `
		<div class="crm-timeline-card__money-pill">
			<span class="crm-timeline-card__money-pill_amount">
				<money
					:opportunity="opportunity"
					:currency-id="currencyId"
				/>
			</span>
		</div>
	`
}
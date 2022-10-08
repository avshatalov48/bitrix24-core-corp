import {Type} from "main.core";
import {Action} from "../../action";

export const MarketPanel = {
	props: {
		text: String,
		detailsText: String,
		detailsTextAction: Object,

	},
	computed: {
		needShowDetailsText(): boolean
		{
			return Type.isStringFilled(this.detailsText);
		},
		href(): ?string
		{
			if (!this.detailsTextAction)
			{
				return null;
			}
			const action = new Action(this.detailsTextAction);
			if (action.isRedirect())
			{
				return action.getValue();
			}

			return null;
		},
	},
	methods: {
		executeAction(): void
		{
			if (this.detailsTextAction)
			{
				const action = new Action(this.detailsTextAction);
				action.execute(this);
			}
		}
	},
	template: `
		<div class="crm-timeline__card-bottom">
		<div class="crm-timeline__card-market">
			<div class="crm-timeline__card-market_container">
				<span class="crm-timeline__card-market_logo"></span>
				<span class="crm-timeline__card-market_text">{{ text }}</span>
				<a
					v-if="href && needShowDetailsText"
					:href="href"
					class="crm-timeline__card-market_more"
				>
					{{detailsText}}
				</a>
				<span
					v-if="!href && needShowDetailsText"
					@click="executeAction"
					class="crm-timeline__card-market_more"
				>
				{{detailsText}}
				</span>
			</div>
			<div class="crm-timeline__card-market_cross"><i></i></div>
		</div>
		</div>
	`
};

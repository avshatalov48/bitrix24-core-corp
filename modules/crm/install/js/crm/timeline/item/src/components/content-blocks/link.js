import {Action} from "../../action";

export default {
	props: {
		text: String,
		action: Object,
		bold: {
			type: Boolean,
			required: false,
			default: false,
		}
	},
	computed: {
		href(): ?string
		{
			if (!this.action)
			{
				return null;
			}
			const action = new Action(this.action);
			if (action.isRedirect())
			{
				return action.getValue();
			}

			return null;
		},

		className() {
			return {
				'crm-timeline__card_link': true,
				'--bold': this.bold,
			}
		},
	},
	methods: {
		executeAction(): void
		{
			if (this.action)
			{
				const action = new Action(this.action);
				action.execute(this);
			}
		}
	},

	template:
		`
			<a
				v-if="href"
				:href="href"
				:class="className"
			>
			{{text}}
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
			>
				{{text}}
			</span>
		`
};

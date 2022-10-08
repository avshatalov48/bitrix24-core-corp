import {Action} from "../../../action";

export const Title = {
	props: {
		title: String,
		action: Object,
	},
	inject: ['isLogMessage'],
	computed: {
		className(): Array {
			return [
				'crm-timeline__card-title', {
				'--light': this.isLogMessage,
				'--action': !!this.action,
				}
			]
		},

		href(): ?string {
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

	},
	methods: {
		executeAction(): void
		{
			if (!this.action)
			{
				return;
			}
			const action = new Action(this.action);
			action.execute(this);
		}
	},
	template: `
		<a
			v-if="href"
			:href="href"
			:class="className"
			tabindex="0"
			:title="title"
		>
			{{title}}
		</a>
		<span
			v-else
			@click="executeAction"
			:class="className"
			tabindex="0"
			:title="title"
		>
			{{title}}
		</span>`
};

import {Action} from "../../action";

export default {
	props: {
		text: String,
		action: Object,
		title: {
			type: String,
			required: false,
			default: '',
		},
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
		linkAttrs(): Object
		{
			if (!this.action)
			{
				return {};
			}
			const action = new Action(this.action);
			if (!action.isRedirect())
			{
				return {};
			}
			const attrs = {
				'href': action.getValue(),
			};
			const target = action.getActionParam('target');
			if (target)
			{
				attrs.target = target;
			}

			return attrs;
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
				v-bind="linkAttrs"
				:class="className"
				:title="title"
			>
			{{text}}
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
				:title="title"
			>
				{{text}}
			</span>
		`
};

import { Action } from '../../action';

export default {
	props: {
		action: Object,
		icon: {
			type: String,
			required: false,
			default: '',
		},
		title: String,
	},

	computed: {
		href(): ?String
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
				href: action.getValue(),
			};
			const target = action.getActionParam('target');
			if (target)
			{
				attrs.target = target;
			}

			return attrs;
		},

		className(): Array
		{
			return [
				'crm-timeline__line_of_text_blocks_button',
			];
		},

		iconClassName(): Array
		{
			if (!this.icon)
			{
				return [];
			}

			return [
				'crm-timeline__line_of_text_blocks_button_icon',
				`--code-${this.icon}`,
			];
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
		},
		addAlignRightClass(): void
		{
			this.$el.parentElement.classList.add('right-fixed-button');
		},
	},

	mounted()
	{
		this.addAlignRightClass();
	},

	template:
		`
			<a
				v-if="href"
				v-bind="linkAttrs"
				:class="className"
				:title="title"
			>{{text}}<span v-if="icon" :class="iconClassName"></span>
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
				:title="title"
			>{{text}}<span v-if="icon" :class="iconClassName"></span>
			</span>
		`,
};

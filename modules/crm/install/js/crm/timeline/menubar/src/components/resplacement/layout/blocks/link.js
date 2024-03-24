import { Action } from '../../action';
import TextSize from '../../enums/text-size';

export default {
	inheritAttrs: false,
	props: {
		text: String,
		action: Object,
		size: {
			type: String,
			required: false,
			default: 'md',
		},
		bold: {
			type: Boolean,
			required: false,
			default: false,
		},
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

			return {
				href: action.getValue(),
			};
		},

		className() {
			return [
				'crm-timeline__card_link',
				this.bold ? '--bold' : '',
				this.sizeClassname,
			];
		},
		sizeClassname(): string {
			const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseWeightProp] ?? TextSize.SM;

			return `--size-${size}`;
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
	},

	template:
		`
			<a
				v-if="href"
				v-bind="linkAttrs"
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
		`,
};

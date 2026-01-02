import { Action } from '../../action';
import { TextColor } from '../enums/text-color';
import { TextDecoration } from '../enums/text-decoration';
import { TextSize } from '../enums/text-size';

export default {
	props: {
		text: String,
		action: Object,
		title: {
			type: String,
			default: '',
		},
		color: {
			type: String,
			default: '',
		},
		bold: {
			type: Boolean,
			default: false,
		},
		size: {
			type: String,
			default: 'md',
		},
		decoration: {
			type: String,
			default: '',
		},
		icon: {
			type: String,
			default: '',
		},
		rowLimit: {
			type: Number,
			default: 0,
		},
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
				'crm-timeline__card_link',
				this.colorClassName,
				this.boldClassName,
				this.sizeClassname,
				this.decorationClassName,
				this.rowLimitClassName,
			];
		},

		colorClassName(): string
		{
			const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
			const color = TextColor[upperCaseColorProp] ?? '';

			return `--color-${color}`;
		},

		boldClassName(): string
		{
			return this.bold ? '--bold' : '';
		},

		sizeClassname(): string
		{
			const upperCaseSizeProp = this.size ? this.size.toUpperCase() : '';
			const size = TextSize[upperCaseSizeProp] ?? TextSize.SM;

			return `--size-${size}`;
		},

		decorationClassName(): string
		{
			const upperCaseDecorationProp = this.decoration ? this.decoration.toUpperCase() : '';
			if (!upperCaseDecorationProp)
			{
				return '';
			}

			const decoration = TextDecoration[upperCaseDecorationProp] ?? TextDecoration.NONE;

			return `--decoration-${decoration}`;
		},

		iconClassName(): Array
		{
			if (!this.icon)
			{
				return [];
			}

			return [
				'crm-timeline__card_link_icon',
				`--code-${this.icon}`,
			];
		},

		rowLimitClassName(): string
		{
			return this.rowLimit ? '--limit' : '';
		},

		rowLimitStyle(): Object
		{
			if (this.rowLimit && this.rowLimit > 0)
			{
				return {
					'-webkit-line-clamp': this.rowLimit,
				};
			}

			return {};
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
		`<a
			v-if="href"
			v-bind="linkAttrs"
			:class="className"
			:title="title"
			:style="rowLimitStyle"
		>{{text}}<span v-if="icon" :class="iconClassName"></span>
		</a>
		<span
			v-else
			@click="executeAction"
			:class="className"
			:title="title"
			:style="rowLimitStyle"
		>{{text}}<span v-if="icon" :class="iconClassName"></span>
		</span>`,
};

import { Dom, Text, Type } from 'main.core';
import { InputPopup } from './input-popup';

export const TodoEditorBlocksLink = {
	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		settings: {
			type: Object,
			required: true,
		},
		filledValues: {
			type: Object,
		},
		context: {
			type: Object,
			required: true,
		},
		isFocused: {
			type: Boolean,
		},
	},

	emits: [
		'close',
		'updateFilledValues',
	],

	data(): Object
	{
		const data = {
			link: null,
		};

		return this.getPreparedData(data);
	},

	mounted(): void
	{
		if (this.isFocused)
		{
			void this.$nextTick(this.onShowLinkPopup);
		}
	},

	beforeUnmount(): void
	{
		this.inputPopup?.destroy();
	},

	methods: {
		getId(): string
		{
			return 'link';
		},
		getPreparedData(data: Object): Object
		{
			const { filledValues } = this;

			if (Type.isStringFilled(filledValues?.link))
			{
				// eslint-disable-next-line no-param-reassign
				data.link = filledValues.link;
			}

			return data;
		},
		getExecutedData(): Object
		{
			const { link } = this;

			return {
				link,
			};
		},
		emitUpdateFilledValues(): void
		{
			let { filledValues } = this;
			const { link } = this;

			const newFilledValues = {
				link,
			};
			filledValues = { ...filledValues, ...newFilledValues };
			this.$emit('updateFilledValues', this.getId(), filledValues);
		},
		onLinkClick(): void
		{
			const a = document.createElement('a');
			a.href = this.link;
			a.target = '_blank';
			Dom.append(a, document.body);
			a.click();
			Dom.remove(a);
		},
		onShowLinkPopup(): void
		{
			if (Type.isNil(this.inputPopup))
			{
				this.inputPopup = new InputPopup({
					bindElement: this.$refs.link,
					title: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_LINK_BLOCK_POPUP_TITLE'),
					placeholder: 'https://',
					onSubmit: (value: string) => {
						this.setLink(value);
					},
				});
			}

			this.inputPopup.show();
			this.inputPopup.setValue(this.link);
		},
		setLink(value: string): void
		{
			this.link = value;
		},
	},

	computed: {
		encodedTitle(): string
		{
			return Text.encode(this.title);
		},
		iconStyles(): Object
		{
			if (!this.icon)
			{
				return {};
			}

			const path = `/bitrix/js/crm/activity/todo-editor-v2/images/${this.icon}`;

			return {
				background: `url('${encodeURI(Text.encode(path))}') center center`,
			};
		},
		actionTitle(): string
		{
			return this.hasLink ? this.changeTitle : this.addTitle;
		},
		changeTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_LINK_BLOCK_CHANGE_ACTION');
		},
		addTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_LINK_BLOCK_ADD_ACTION');
		},
		hasLink(): boolean
		{
			return Type.isStringFilled(this.link);
		},
	},

	created()
	{
		this.$watch(
			'link',
			this.emitUpdateFilledValues,
			{
				deep: true,
			},
		);
	},

	template: `
		<div class="crm-activity__todo-editor-v2_block-header --link">
			<span
				class="crm-activity__todo-editor-v2_block-header-icon"
				:style="iconStyles"
			></span>
			<span>{{ encodedTitle }}</span>
			<span 
				class="crm-activity__todo-editor-v2_block-header-data"
				@click="onLinkClick"
			>
				{{ link }}
			</span>
			<span
				@click="onShowLinkPopup"
				ref="link"
				class="crm-activity__todo-editor-v2_block-header-action"
			>
				{{ actionTitle }}
			</span>
			<div
				@click="$emit('close', id)"
				class="crm-activity__todo-editor-v2_block-header-close"
			></div>
		</div>
	`,
};

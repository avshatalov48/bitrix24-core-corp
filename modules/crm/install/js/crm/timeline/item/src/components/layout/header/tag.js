import { Label } from 'ui.label';
import { Dom, Type } from 'main.core';

import { Action } from '../../../action';
import { TagType } from '../../enums/tag-type';

export const Tag = {
	props: {
		title: {
			type: String,
			required: false,
			default: '',
		},
		hint: {
			type: String,
			required: false,
			default: '',
		},
		action: {
			type: Object,
			required: false,
			default: null,
		},
		type: {
			type: String,
			required: false,
			default: TagType.SECONDARY,
		},
		state: String,
	},
	computed: {
		className(): Object
		{
			return {
				'crm-timeline__card-status': true,
				'--clickable': !!this.action,
				'--hint': !!this.hint,
			};
		},

		tagTypeToLabelColorDict(): Object
		{
			return {
				[TagType.PRIMARY]: Label.Color.LIGHT_BLUE,
				[TagType.SECONDARY]: Label.Color.LIGHT,
				[TagType.LAVENDER]: Label.Color.LAVENDER,
				[TagType.SUCCESS]: Label.Color.LIGHT_GREEN,
				[TagType.WARNING]: Label.Color.LIGHT_YELLOW,
				[TagType.FAILURE]: Label.Color.LIGHT_RED,
			};
		},

		tagContainerRef(): HTMLDivElement
		{
			return this.$refs.tag;
		},
	},
	methods: {

		getLabelColorFromTagType(tagType)
		{
			const lowerCaseTagType = tagType ? tagType.toLowerCase() : '';
			const labelColor = this.tagTypeToLabelColorDict[lowerCaseTagType];

			return labelColor || Label.Color.LIGHT;
		},

		// eslint-disable-next-line consistent-return
		renderTag(tagOptions): HTMLElement | null
		{
			if (!tagOptions || !this.tagContainerRef)
			{
				return null;
			}

			const { title, type } = tagOptions;

			const uppercaseTitle = title && Type.isString(title) ? title.toUpperCase() : '';
			const label = new Label({
				text: uppercaseTitle,
				color: this.getLabelColorFromTagType(type),
				fill: true,
			});

			Dom.clean(this.tagContainerRef);
			Dom.append(label.render(), this.tagContainerRef);
		},

		executeAction(): void
		{
			if (!this.action)
			{
				return;
			}

			const action = new Action(this.action);
			action.execute(this);
		},
	},

	mounted(): void
	{
		this.renderTag({ title: this.title, type: this.type });
	},

	updated(): void
	{
		this.renderTag({ title: this.title, type: this.type });
	},

	template: `
		<div ref="tag" :title="hint" :class="className" @click="executeAction"></div>
	`,
};

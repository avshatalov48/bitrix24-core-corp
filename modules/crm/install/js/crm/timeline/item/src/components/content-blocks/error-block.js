import { Dom, Text, Type } from 'main.core';

import { ErrorType } from '../enums/error-type';


export const ErrorBlock = {
	props: {
		title: {
			type: String,
			required: true,
			default: '',
		},
		description: {
			type: String,
			required: true,
			default: '',
		},
		closable: {
			type: Boolean,
			required: true,
			default: false,
		},
		type: {
			type: String,
			default: '',
		},
	},

	data(): Object
	{
		return {
			isClosable: this.closable,
		};
	},

	computed: {
		iconClassname(): Object
		{
			return {
				'crm-timeline__error-block__header-icon': true,
				'--ai': this.type === ErrorType.AI,
			}
		},

		encodedTitle(): string
		{
			return Text.encode(this.title);
		},

		encodedDescription(): string
		{
			return Text.encode(this.description);
		},
	},

	methods: {
		closeBlock(): void
		{
			const blockEl = this.$refs.rootElement;
			if (Type.isDomNode(blockEl))
			{
				Dom.addClass(blockEl, '--hidden');
				setTimeout(() => {
					Dom.remove(blockEl);
				}, 700);
			}
		}
	},

	template: `
		<div ref="rootElement" class="crm-timeline__error-block_wrapper">
			<div class="crm-timeline__error-block">
				<div class="crm-timeline__error-block__header">
					<div :class="iconClassname"></div>
					<div
						class="crm-timeline__error-block__header-title"
						v-html="encodedTitle"
					></div>
					<button
						v-if="isClosable"
						@click="closeBlock"
						class="crm-timeline__error-block_close-btn"
					></button>
				</div>
				<div
					class="crm-timeline__error-block__description"
					v-html="encodedDescription"
				></div>
			</div>
		</div>
	`,
}

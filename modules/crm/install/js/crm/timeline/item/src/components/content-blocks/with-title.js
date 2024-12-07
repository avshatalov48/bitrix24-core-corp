import { Dom } from 'main.core';

export default {
	props: {
		title: String,
		inline: Boolean,
		wordWrap: Boolean,
		fixedWidth: Boolean,
		titleBottomPadding: {
			type: Number,
			required: false,
			default: 0,
		},
		contentBlock: Object,
	},
	computed: {
		className(): Object
		{
			return {
				'crm-timeline__card-container_info': true,
				'--inline': this.inline,
				'--word-wrap': this.wordWrap,
				'--fixed-width': this.fixedWidth,
			};
		},
		valueClassName(): Object
		{
			return {
				'crm-timeline__card-container_info-value': true,
			};
		},
	},

	methods: {
		isTitleCropped(): boolean
		{
			const titleElem: Element = this.$refs.title;

			return titleElem.scrollWidth > titleElem.clientWidth;
		},
	},

	mounted()
	{
		void this.$nextTick(() => {
			if (!this.$refs.title)
			{
				return;
			}

			if (this.isTitleCropped())
			{
				Dom.attr(this.$refs.title, 'title', this.title);
			}

			if (this.titleBottomPadding > 0)
			{
				Dom.style(this.$refs.title, 'padding-bottom', `${this.titleBottomPadding}px`);
			}
		});
	},

	template: `
		<div
			:class="className"
		>
			<div
				ref="title" 
				class="crm-timeline__card-container_info-title"
			>
				{{ title }}
			</div>
			<div 
				:class="valueClassName"
			>
				<component 
					:is="contentBlock.rendererName"
					v-bind="contentBlock.properties"
				/>
			</div>
		</div>
	`,
};

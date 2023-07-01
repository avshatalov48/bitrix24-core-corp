import {Dom} from "main.core";

export default {
	props: {
		title: String,
		inline: Boolean,
		wordWrap: Boolean,
		fixedWidth: Boolean,
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
			}
		}
	},
	methods: {
		isTitleCropped() {
			const titleElem: Element = this.$refs.title;

			return titleElem.scrollWidth > titleElem.clientWidth;
		},
	},
	mounted() {
		this.$nextTick(() => {
			if (this.isTitleCropped())
			{
				Dom.attr(this.$refs.title, 'title', this.title);
			}
		});
	},
	template:
		`
			<div :class="className">
				<div ref="title" class="crm-timeline__card-container_info-title">{{ title }}</div>
				<div class="crm-timeline__card-container_info-value">
					<component :is="contentBlock.rendererName" v-bind="contentBlock.properties"></component>
				</div>
			</div>
		`
};

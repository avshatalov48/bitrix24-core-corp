import { Dom } from 'main.core';
import BlockType from '../../enums/block-type';
import BlockWithTitleWidth from '../../enums/block-with-title-width';
import Text from './text';
import Link from './link';
import { LineOfTextBlocks } from './line-of-text-blocks';

export default {
	inheritAttrs: false,
	components: {
		Text,
		Link,
		LineOfTextBlocks,
	},
	props: {
		id: String,
		title: String,
		inline: Boolean,
		titleWidth: {
			type: String,
			required: false,
			default: BlockWithTitleWidth.MD,
		},
		block: Object,
	},
	computed: {
		className(): Object
		{
			return [
				'crm-timeline__card-container_info',
				'--word-wrap',
				this.widthClassname,
				this.inline ? '--inline' : '',
			];
		},
		widthClassname(): string {
			const width = BlockWithTitleWidth[this.titleWidth.toUpperCase()] ?? BlockWithTitleWidth.MD;

			return `--width-${width}`;
		},
		isValidBlock(): Boolean
		{
			return [BlockType.text, BlockType.link, BlockType.lineOfBlocks].includes(this.rendererName);
		},
		rendererName(): ?string
		{
			return BlockType[this.block?.type] ?? null;
		},
	},
	methods: {
		isTitleCropped(): Boolean
		{
			const titleElem: Element = this.$refs.title;

			return titleElem.scrollWidth > titleElem.clientWidth;
		},
	},
	mounted()
	{
		this.$nextTick(() => {
			if (this.isTitleCropped())
			{
				Dom.attr(this.$refs.title, 'title', this.title);
			}
		});
	},
	template:
		`
			<div :class="className" v-if="isValidBlock">
				<div ref="title" class="crm-timeline__card-container_info-title">{{ title }}</div>
				<div class="crm-timeline__card-container_info-value">
					<component :is="rendererName" v-bind="block.properties" :id="id"></component>
				</div>
			</div>
		`,
};

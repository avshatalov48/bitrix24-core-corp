import { Text, Type } from 'main.core';

export default {
	props: {
		blocks: Object,
		delimiter: String,
		button: Object,
	},
	mounted(): void
	{
		const blocks = this.$refs.blocks;
		this.visibleBlocks.forEach((block, index) => {
			if (Type.isDomNode(blocks[index].$el))
			{
				blocks[index].$el.setAttribute('data-id', block.id);
			}
			else
			{
				throw new Error(`Vue component "${block.rendererName}" was not found`);
			}
		});
	},
	methods: {
		isShowDelimiter(index: number, length: number): boolean
		{
			return (Type.isString(this.delimiter) && !this.isLastElement(index, length));
		},
		isLastElement(index: number, length: number): boolean
		{
			return index === length - 1;
		},
	},
	computed: {
		visibleBlocks(): Array
		{
			if (!Type.isObject(this.blocks))
			{
				return [];
			}

			const blocks = Object.keys(this.blocks)
				.map((id) => ({ id, ...this.blocks[id] }))
				.filter((item) => (item.scope !== 'mobile'));

			if (Type.isObject(this.button))
			{
				blocks.push({ id: 'button', ...this.button });
			}

			return blocks;
		},
		formattedDelimiter(): string
		{
			return Text.encode(this.delimiter).replace(' ', '&nbsp;');
		},
	},
	// language=Vue
	template: `
		<span class="crm-timeline-block-line-of-texts">
			<span
				v-for="(block, index) in visibleBlocks"
				:key="block.id"
			>
				<component 
					:is="block.rendererName"
					v-bind="block.properties"
					ref="blocks"
				/>
				<span v-if="isShowDelimiter(index, visibleBlocks.length)" v-html="formattedDelimiter"></span>
				<span v-else-if="!isLastElement(index, visibleBlocks.length)">&nbsp;</span>
			</span>
		</span>
	`,
};

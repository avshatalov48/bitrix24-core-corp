import {Type} from 'main.core';

export default {
	props: {
		blocks: Object,
	},
	mounted() {
		const blocks = this.$refs.blocks;
		this.visibleBlocks.forEach((block, index) => {
			if (Type.isDomNode(blocks[index].$el))
			{
				blocks[index].$el.setAttribute('data-id', block.id);
			}
			else
			{
				throw new Error('Vue component "' + block.rendererName + '" was not found');
			}
		});
	},
	computed: {
		visibleBlocks(): Array
		{
			return Object.keys(this.blocks)
				.map((id) => ({id, ...this.blocks[id]}))
				.filter((item) => (item.scope !== 'mobile'))
			;
		},
	},
	// language=Vue
	template: `
		<span class="crm-timeline-block-line-of-texts">
			<span
				v-for="(block) in visibleBlocks"
				:key="block.id"
			>
				<component :is="block.rendererName"
						   v-bind="block.properties"
						   ref="blocks"/>
			<span>&nbsp;</span>
			</span>
		</span>`
};

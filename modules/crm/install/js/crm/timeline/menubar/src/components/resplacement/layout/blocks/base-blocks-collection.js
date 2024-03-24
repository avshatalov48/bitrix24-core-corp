import BlockType from '../../enums/block-type';

export default {
	inheritAttrs: false,
	props: {
		blocks: Object,
	},
	computed: {
		allowedTypes(): Array
		{
			return Object.values(BlockType);
		},
		containerCssClass(): string
		{
			return '';
		},
		containerTagName(): string
		{
			return 'div';
		},
		itemCssClass(): string
		{
			return '';
		},
		itemTagName(): string
		{
			return 'div';
		},
		isInline(): Boolean
		{
			return false;
		},
	},
	methods: {
		setLayoutItemState(id: string, visible: ?Boolean, properties: ?Object): Boolean
		{
			return this.$refs.blocks.setLayoutItemState(id, visible, properties);
		},
	},
	// language=Vue
	template: `
		<BlocksCollection 
			:containerCssClass="containerCssClass"
			:containerTagName="containerTagName"
			:itemCssClass="itemCssClass"
			:itemTagName="itemTagName"
			ref="blocks"
			:blocks="blocks ?? {}" 
			:inline="true"
			:allowedTypes="allowedTypes"
		></BlocksCollection>`,
};

import { BitrixVue } from 'ui.vue3';
import BlockType from '../../enums/block-type';
import BaseBlocksCollection from './base-blocks-collection';

export const List = BitrixVue.cloneComponent(BaseBlocksCollection, {
	computed: {
		allowedTypes(): Array
		{
			return [BlockType.text, BlockType.link, BlockType.lineOfBlocks];
		},
		containerCssClass(): string
		{
			return 'crm-timeline-block-list';
		},
		itemCssClass(): string
		{
			return 'crm-timeline-block-list-item';
		},
	},
});

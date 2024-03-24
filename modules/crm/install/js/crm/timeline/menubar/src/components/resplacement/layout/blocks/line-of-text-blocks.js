import { BitrixVue } from 'ui.vue3';
import BlockType from '../../enums/block-type';
import BaseBlocksCollection from './base-blocks-collection';

export const LineOfTextBlocks = BitrixVue.cloneComponent(BaseBlocksCollection, {
	computed: {
		allowedTypes(): Array
		{
			return [BlockType.text, BlockType.link, BlockType.dropdownMenu];
		},
		containerCssClass(): string
		{
			return 'crm-timeline-block-line-of-texts';
		},
		containerTagName(): string
		{
			return 'span';
		},
		itemTagName(): string
		{
			return 'span';
		},
		isInline(): Boolean
		{
			return true;
		},
	},
});

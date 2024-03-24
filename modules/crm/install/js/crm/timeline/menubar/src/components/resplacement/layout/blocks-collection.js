import { Type } from 'main.core';
import BlockType from '../enums/block-type';

import Text from './blocks/text';
import Link from './blocks/link';
import WithTitle from './blocks/with-title';
import DropdownMenu from './blocks/dropdown-menu';
import { Input } from './blocks/text-input-wrapper';
import { Select } from './blocks/select-wrapper';
import { Textarea } from './blocks/textarea-wrapper';
import { LineOfTextBlocks } from './blocks/line-of-text-blocks';
import { List } from './blocks/list';
import { Section } from './blocks/section';

export default {
	components: {
		Text,
		Link,
		LineOfTextBlocks,
		DropdownMenu,
		Input,
		Select,
		Textarea,
		List,
		WithTitle,
		Section,
	},
	props: {
		containerTagName: {
			type: String,
			required: false,
			default: 'div',
		},
		containerCssClass: {
			type: String,
			required: false,
			default: '',
		},
		itemTagName: {
			type: String,
			required: false,
			default: 'div',
		},
		itemCssClass: {
			type: String,
			required: false,
			default: '',
		},
		inline: {
			type: Boolean,
			required: false,
			default: false,
		},
		allowedTypes: {
			type: Array,
			required: false,
			default: Object.values(BlockType),
		},
		blocks: Object,
	},
	data(): Object
	{
		return {
			currentBlocks: this.blocks,
			blockRefs: {},
		};
	},
	beforeUpdate() {
		this.blockRefs = {};
	},
	updated() {
		this.setDataIdAttribute();
	},
	mounted() {
		this.setDataIdAttribute();
	},
	watch: {
		blocks(newBlocks)
		{
			this.currentBlocks = newBlocks;
		},
	},
	methods:
	{
		saveRef(ref: Object, id: string): void
		{
			this.blockRefs[id] = ref;
		},
		setDataIdAttribute(): void
		{
			if (!this.blockRefs || this.visibleBlocks.length === 0)
			{
				return;
			}
			this.visibleBlocks.forEach((block, index) => {
				const blockId = block.id;
				const node = this.blockRefs[blockId]?.$el;
				if (Type.isElementNode(node))
				{
					node.setAttribute('data-id', blockId);
				}
			});
		},
		setLayoutItemState(id: string, visible: ?Boolean, properties: ?Object): Boolean
		{
			if (!Object.hasOwn(this.currentBlocks, id))
			{
				return Object.keys(this.currentBlocks).reduce((result, blockId) => {
					if (this.blockRefs[blockId] && Type.isFunction(this.blockRefs[blockId].setLayoutItemState))
					{
						return this.blockRefs[blockId].setLayoutItemState(id, visible, properties) || result;
					}

					return result;
				}, false);
			}

			if (Type.isPlainObject(properties))
			{
				this.currentBlocks[id].properties = {
					...this.currentBlocks[id].properties,
					...properties,
				};
			}

			if (Type.isBoolean(visible))
			{
				this.currentBlocks[id].visible = visible;
			}

			return true;
		},
		getIdByComponentInstance(componentInstance: Object): ?string
		{
			const id = Object.keys(this.blockRefs).find(
				(blockId) => this.blockRefs[blockId] === componentInstance,
			);

			return id || null;
		},
		getItemCssClassList(block: Object): Array
		{
			const list = [];

			if (this.itemCssClass)
			{
				list.push(this.itemCssClass);
			}

			if (!block.visible)
			{
				list.push('--hidden');
			}

			if (block.id === this.firstVisibleBlockId)
			{
				list.push('--first-visible');
			}

			if (block.id === this.lastVisibleBlockId)
			{
				list.push('--last-visible');
			}

			return list;
		}
	},
	computed: {
		visibleBlocks(): Array
		{
			if (!this.currentBlocks)
			{
				return [];
			}

			return Object.keys(this.currentBlocks)
				.map((id) => {
					const block = this.currentBlocks[id];
					const rendererName = BlockType[block.type] ?? null;
					const visible = (!Type.isBoolean(block.visible) || block.visible);

					return { id, rendererName, ...this.currentBlocks[id], visible };
				})
				.filter((item) => (this.allowedTypes.includes(item.rendererName)))
			;
		},
		firstVisibleBlockId(): ?String
		{
			const visibleBlocks = this.visibleBlocks.filter((item) => item.visible);
			if (!visibleBlocks.length)
			{
				return null;
			}

			return visibleBlocks[0].id;
		},
		lastVisibleBlockId(): ?String
		{
			const visibleBlocks = this.visibleBlocks.filter((item) => item.visible);
			if (!visibleBlocks.length)
			{
				return null;
			}

			return visibleBlocks[visibleBlocks.length - 1].id;
		},
	},
	// language=Vue
	template: `
		<component :is="containerTagName" :class="containerCssClass">
			<component :is="itemTagName"
				:class="getItemCssClassList(block)"
				v-for="(block) in visibleBlocks"
				:key="block.id"
			>
				<component :is="block.rendererName"
						   :id="block.id"
						   v-bind="block.properties"
						   :ref="(el) => this.saveRef(el, block.id)"
				/>
				<span v-if="inline">&nbsp;</span>
			</component>
		</component>`,
};

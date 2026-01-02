import { RichLoc } from 'ui.vue3.components.rich-loc';
import { TextMd } from 'ui.system.typography.vue';
import { BMenu, type MenuItemOptions, type MenuOptions } from 'ui.system.menu.vue';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { HoverPill } from 'tasks.v2.component.elements.hover-pill';
import { hrefClick } from 'tasks.v2.lib.href-click';
import type { CrmItemModel } from 'tasks.v2.model.crm-items';

import './crm.css';

// @vue/component
export const CrmItem = {
	components: {
		HoverPill,
		TextMd,
		RichLoc,
		BMenu,
	},
	props: {
		/**
		 * @type CrmItemModel
		 */
		item: {
			type: Object,
			required: true,
		},
		isEdit: {
			type: Boolean,
			required: true,
		},
		readonly: {
			type: Boolean,
			required: true,
		},
	},
	emits: ['edit', 'clear'],
	data(): Object
	{
		return {
			isMenuShown: false,
		};
	},
	computed: {

		menuOptions(): MenuOptions
		{
			return {
				id: `tasks-crm-menu-${this.item.id}`,
				bindElement: this.$refs.pill.$el,
				offsetTop: 8,
				items: this.menuItems,
				targetContainer: document.body,
			};
		},
		menuItems(): MenuItemOptions[]
		{
			return [
				{
					title: this.loc('TASKS_V2_CRM_OPEN'),
					icon: Outline.GO_TO_L,
					dataset: {
						id: 'tasks-crm-menu-open',
					},
					onClick: () => hrefClick(this.item.link),
				},
				{
					title: this.loc('TASKS_V2_CRM_EDIT'),
					icon: Outline.EDIT_L,
					dataset: {
						id: 'tasks-crm-menu-edit',
					},
					onClick: () => this.$emit('edit'),
				},
				{
					title: this.loc('TASKS_V2_CRM_UNLINK'),
					icon: Outline.CROSS_L,
					dataset: {
						id: 'tasks-crm-menu-unlink',
					},
					onClick: this.clear,
				},
			];
		},
	},
	methods: {
		prepareTitle(item: CrmItemModel): string
		{
			return this.loc('TASKS_V2_CRM_ENTITY_TITLE', {
				'#TYPE_NAME#': item.typeName,
				'#TITLE#': item.title,
			});
		},
		handleClick(): void
		{
			if (!this.isEdit)
			{
				hrefClick(this.item.link);

				return;
			}

			if (this.readonly)
			{
				return;
			}

			this.isMenuShown = true;
		},
		clear(): void
		{
			this.$emit('clear', this.item.id);
		},
	},
	template: `
		<div class="tasks-field-crm-item-container">
			<HoverPill
				class="tasks-field-crm-item"
				:withClear="!readonly && !isEdit"
				:readonly="readonly"
				ref="pill"
				@click.stop="handleClick"
				@clear="clear"
			>
				<TextMd class="tasks-field-crm-item-text">
					<RichLoc tag="span" :text="prepareTitle(item)" placeholder="[a]">
						<template #a="{ text }">
							<a @click.prevent>{{ text }}</a>
						</template>
					</RichLoc>
				</TextMd>
			</HoverPill>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`,
};

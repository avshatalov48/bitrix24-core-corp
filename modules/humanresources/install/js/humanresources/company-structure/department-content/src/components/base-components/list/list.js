import { Event, Runtime, Type } from 'main.core';
import { EmptyListItem } from './empty-list-item';
import { ListActionButton } from './list-action-button';

// eslint-disable-next-line no-unused-vars
import type { TabListDataTestIds } from './types';
import './styles/list.css';

export const TabList = {
	name: 'tabList',
	components: { ListActionButton, EmptyListItem },
	emits: ['tabListAction'],

	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		count: {
			type: Number,
			required: false,
		},
		menuItems: {
			type: Array,
			required: false,
			default: [],
		},
		listItems: {
			type: Array,
			required: true,
		},
		emptyItemTitle: {
			type: String,
			required: false,
		},
		emptyItemImageClass: {
			type: String,
			required: false,
		},
		hideEmptyItem: {
			type: Boolean,
			required: false,
			default: false,
		},
		withAddPermission: {
			type: Boolean,
			required: false,
			default: true,
		},
		/** @var { TabListDataTestIds } dataTestIds */
		dataTestIds: {
			type: Object,
			required: false,
			default: {},
		},
		isDropTarget: {
			type: Boolean,
			default: false,
		},
		listType: {
			type: String,
		},
	},

	data(): Object
	{
		return {
			placeholderIndex: null,
			boundHandleDragOverList: null,
		};
	},

	computed: {
		needToShowCount(): boolean
		{
			return Type.isNumber(this.count);
		},
	},

	watch: {
		isDropTarget(newValue, oldValue): void
		{
			if (newValue === oldValue)
			{
				return;
			}
			const itemsWrapper = this.$refs.itemsWrapper;
			if (!itemsWrapper)
			{
				return;
			}

			if (newValue)
			{
				this.boundHandleDragOverList = Runtime.throttle(this.handleDragOverList.bind(this), 10);
				Event.bind(itemsWrapper, 'mousemove', this.boundHandleDragOverList);

				if (this.listItems.length === 0)
				{
					this.placeholderIndex = 0;
				}
			}
			else
			{
				Event.unbind(itemsWrapper, 'mousemove', this.boundHandleDragOverList);
				this.boundHandleDragOverList = null;
				this.placeholderIndex = null;
			}
		},
	},

	methods: {
		onActionMenuItemClick(actionId: string): void
		{
			this.$emit('tabListAction', actionId);
		},
		handleDragOverList(event: MouseEvent): void
		{
			if (!this.isDropTarget || !this.$refs.itemsWrapper)
			{
				if (this.placeholderIndex !== null)
				{
					this.placeholderIndex = null;
				}

				return;
			}

			const itemsWrapper = this.$refs.itemsWrapper;
			const children = [...itemsWrapper.querySelectorAll('.hr-department-detail-content__user-container:not(.--dragging)')];
			const mouseY = event.clientY;

			if (children.length === 0)
			{
				if (this.placeholderIndex !== 0)
				{
					this.placeholderIndex = 0;
				}

				return;
			}

			let newIndex = children.length;

			for (const [i, child] of children.entries())
			{
				const rect = child.getBoundingClientRect();
				const childMidpointY = rect.top + rect.height / 2;

				if (mouseY < childMidpointY)
				{
					newIndex = i;
					break;
				}
			}

			if (newIndex !== this.placeholderIndex)
			{
				this.placeholderIndex = newIndex;
			}
		},
	},

	template: `
		<div
			class="hr-department-detail-content__tab-list_container"
			 :data-test-id="dataTestIds.containerDataTestId"
		>
			<div class="hr-department-detail-content__tab-list_header-container">
				<div class="hr-department-detail-content__tab-list_list-title">
					{{ title }}
					<span
						v-if="needToShowCount"
						class="hr-department-detail-content__tab-list_header-count"
						:data-test-id="dataTestIds.listCounterDataTestId"
					>
					{{ count }}
				</span>
				</div>
				<ListActionButton
					:id="id"
					:menuItems="menuItems"
					@tabListAction="onActionMenuItemClick"
					:dataTestIds="{buttonDataTestId: dataTestIds.listActionButtonDataTestId, containerDataTestId: dataTestIds.listActonMenuDataTestId}"
				/>
			</div>
			<div class="hr-department-detail-content__tab_list-container" ref="itemsWrapper">
				<div
					v-if="placeholderIndex === 0 && isDropTarget"
					class="hr-department-detail-content__drop-placeholder"
				></div>
				<template v-for="(item, index) in listItems" :key="item.id">
					<slot :item="item"
					/>
					<div
						v-if="placeholderIndex === (index + 1) && isDropTarget"
						class="hr-department-detail-content__drop-placeholder"
					></div>
				</template>
				<slot name="footer" />
				<EmptyListItem v-if="emptyItemTitle && emptyItemImageClass && !listItems.length && !hideEmptyItem"
					:title="emptyItemTitle"
					:imageClass="emptyItemImageClass"
					:withAddPermission="withAddPermission"
				/>
			</div>
		</div>
	`,
};

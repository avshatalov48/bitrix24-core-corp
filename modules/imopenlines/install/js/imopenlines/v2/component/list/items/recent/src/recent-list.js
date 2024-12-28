import { Core } from 'im.v2.application.core';
import { ListLoadingState as LoadingState } from 'im.v2.component.elements';
import { Utils } from 'im.v2.lib.utils';
import { StatusGroup } from 'imopenlines.v2.const';
import { RecentService } from 'imopenlines.v2.provider.service';

import { EmptyState } from './components/empty-state';
import { RecentGroup } from './components/recent-group';

import './css/recent-list.css';

import type { JsonObject } from 'main.core';
import type { ImolModelRecentItem, ImolModelSession } from 'imopenlines.v2.model';
import type { StatusGroupName } from 'imopenlines.v2.const';

type StatusGroupItemCollection = {
	[StatusGroupName]: ImolModelRecentItem[];
};

// @vue/component
export const RecentList = {
	name: 'RecentList',
	components: { EmptyState, RecentGroup, LoadingState },
	emits: ['chatClick'],
	data(): JsonObject
	{
		return {
			isLoading: false,
			isLoadingNextPage: false,
			firstPageLoaded: false,
		};
	},
	computed:
	{
		collection(): ImolModelRecentItem[]
		{
			return Core.getStore().getters['recentOpenLines/getOpenLinesCollection'];
		},
		collectionByGroups(): StatusGroupItemCollection
		{
			const groupsRecent: StatusGroupItemCollection = {
				[StatusGroup.new]: [],
				[StatusGroup.work]: [],
				[StatusGroup.answered]: [],
			};

			this.collection.forEach((item: ImolModelRecentItem) => {
				const recentItem = item;

				const statusName = this.getStatusByDialogId(recentItem.dialogId);

				groupsRecent[statusName].push(recentItem);
			});

			return groupsRecent;
		},
		sortedCollectionByGroups(): StatusGroupItemCollection
		{
			const sortedGroups = {};

			Object.entries(this.collectionByGroups).forEach(([groupName, items]) => {
				sortedGroups[groupName] = this.sortGroupItems(groupName, items);
			});

			return sortedGroups;
		},
		isEmptyCollection(): boolean
		{
			return this.collection.length === 0;
		},
	},
	async activated(): Promise
	{
		this.isLoading = true;
		await this.getRecentService().loadFirstPage();
		this.firstPageLoaded = true;
		this.isLoading = false;
	},
	methods:
	{
		async onScroll(event: Event)
		{
			if (!Utils.dom.isOneScreenRemaining(event.target) || !this.getRecentService().hasMoreItemsToLoad())
			{
				return;
			}

			this.isLoadingNextPage = true;
			await this.getRecentService().loadNextPage();
			this.isLoadingNextPage = false;
		},
		onClick(dialogId: string)
		{
			this.$emit('chatClick', dialogId);
		},
		getSessionByDialogId(dialogId: string): ?ImolModelSession
		{
			return this.$store.getters['recentOpenLines/getSession'](dialogId);
		},
		getStatusByDialogId(dialogId: string): StatusGroupName
		{
			const session = this.getSessionByDialogId(dialogId);

			return session ? session.status : StatusGroup.new;
		},
		sortGroupItems(groupName: StatusGroupName, items: ImolModelRecentItem[]): ImolModelRecentItem[]
		{
			if (groupName === StatusGroup.answered)
			{
				return this.sortItemsDesc(items);
			}

			return this.sortItemsAsc(items);
		},
		sortItemsAsc(items: ImolModelRecentItem[]): ImolModelRecentItem[]
		{
			return items.sort((a, z) => a.sessionId - z.sessionId);
		},
		sortItemsDesc(items: ImolModelRecentItem[]): ImolModelRecentItem[]
		{
			return items.sort((a, z) => {
				const dateA = this.messageDate(a.messageId);
				const dateZ = this.messageDate(z.messageId);

				return dateZ - dateA;
			});
		},
		messageDate(messageId: number): ?Date
		{
			const message = Core.getStore().getters['messages/getById'](messageId);

			return message ? message.date : null;
		},
		getRecentService(): RecentService
		{
			if (!this.service)
			{
				this.service = new RecentService();
			}

			return this.service;
		},
	},
	template: `
		<div class="bx-imol-list-recent__content">
			<LoadingState v-if="isLoading && !firstPageLoaded" />
			<div v-else @scroll="onScroll"  class="bx-imol-list-recent__scroll-container">
				<EmptyState v-if="isEmptyCollection" />
				<RecentGroup
					v-for="(groupItems, groupName) in sortedCollectionByGroups"
					:groupItems="groupItems"
					:groupName="groupName"
					:key="groupName"
					@recentClick="onClick"
				/>
				<LoadingState v-if="isLoadingNextPage" />
			</div>
		</div>
	`,
};

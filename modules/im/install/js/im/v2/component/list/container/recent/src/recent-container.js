import { EventEmitter } from 'main.core.events';
import { Event } from 'main.core';

import { RecentList } from 'im.v2.component.list.element-list.recent';
import { ChatSearchInput } from 'im.v2.component.search.chat-search-input';
import { SearchExperimental } from 'im.v2.component.search.search-experimental';
import { Layout, EventType } from 'im.v2.const';
import { Logger } from 'im.v2.lib.logger';
import { UnreadRecentService } from 'im.v2.provider.service';

import { HeaderMenu } from './components/header-menu';
import { CreateChatMenu } from './components/create-chat-menu/create-chat-menu';

import './css/recent-container.css';

import type { JsonObject } from 'main.core';

// @vue/component
export const RecentListContainer = {
	name: 'RecentListContainer',
	components: { HeaderMenu, CreateChatMenu, ChatSearchInput, RecentList, SearchExperimental },
	emits: ['selectEntity'],
	data(): JsonObject
	{
		return {
			searchMode: false,
			unreadOnlyMode: false,
			searchQuery: '',
			isSearchLoading: false,
		};
	},
	computed:
	{
		UnreadRecentService: () => UnreadRecentService,
	},
	created()
	{
		Logger.warn('List: Recent container created');

		EventEmitter.subscribe(EventType.recent.openSearch, this.onOpenSearch);
		Event.bind(document, 'mousedown', this.onDocumentClick);
	},
	beforeUnmount()
	{
		EventEmitter.unsubscribe(EventType.recent.openSearch, this.onOpenSearch);
		Event.unbind(document, 'mousedown', this.onDocumentClick);
	},
	methods:
	{
		onChatClick(dialogId)
		{
			this.$emit('selectEntity', { layoutName: Layout.chat.name, entityId: dialogId });
		},
		onOpenSearch()
		{
			this.searchMode = true;
		},
		onCloseSearch()
		{
			this.searchMode = false;
			this.searchQuery = '';
		},
		onUpdateSearch(query)
		{
			this.searchMode = true;
			this.searchQuery = query;
		},
		onDocumentClick(event: Event)
		{
			const clickOnRecentContainer = event.composedPath().includes(this.$refs['recent-container']);
			if (!clickOnRecentContainer)
			{
				EventEmitter.emit(EventType.search.close);
			}
		},
		onLoading(value: boolean)
		{
			this.isSearchLoading = value;
		},
	},
	template: `
				<div class="bx-im-list-container-recent__scope bx-im-list-container-recent__container" ref="recent-container">
					<div class="bx-im-list-container-recent__header_container">
						<HeaderMenu @showUnread="unreadOnlyMode = true" />
						<div class="bx-im-list-container-recent__search-input_container">
							<ChatSearchInput 
								:searchMode="searchMode" 
								:isLoading="isSearchLoading"
								@openSearch="onOpenSearch"
								@closeSearch="onCloseSearch"
								@updateSearch="onUpdateSearch"
							/>
						</div>
						<CreateChatMenu />
					</div>
					<div class="bx-im-list-container-recent__elements_container">
						<div class="bx-im-list-container-recent__elements">
							<SearchExperimental 
								v-show="searchMode" 
								:searchMode="searchMode"
								:withMyNotes="true"
								:searchQuery="searchQuery" 
								:searchConfig="{}"
								@loading="onLoading"
							/>
							<RecentList v-show="!searchMode && !unreadOnlyMode" @chatClick="onChatClick" key="recent" />
		<!--					<RecentList-->
		<!--						v-if="!searchMode && unreadOnlyMode"-->
		<!--						:recentService="UnreadRecentService.getInstance()"-->
		<!--						@chatClick="onChatClick"-->
		<!--						key="unread"-->
		<!--					/>-->
						</div>
					</div>
				</div>
	`,
};

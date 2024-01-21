import 'ui.design-tokens';
import 'main.polyfill.intersectionobserver';

import { BaseEvent } from 'main.core.events';

import { Core } from 'im.v2.application.core';
import { ChatType, Settings } from 'im.v2.const';
import { Utils } from 'im.v2.lib.utils';
import { RecentService } from 'im.v2.provider.service';
import { RecentLoadingState } from 'im.v2.component.elements';
import { RecentMenu } from 'im.v2.lib.menu';
import { DraftManager } from 'im.v2.lib.draft';
import { CreateChatManager } from 'im.v2.lib.create-chat';
import { Messenger } from 'im.public';

import { RecentItem } from './components/recent-item';
import { ActiveCall } from './components/active-call';
import { CreateChat } from './components/create-chat';
import { EmptyState } from './components/empty-state';

import { BroadcastManager } from './classes/broadcast-manager';
import { LikeManager } from './classes/like-manager';

import './css/recent-list.css';
import './css/recent-compact.css';
import './css/recent-context-menu.css';

import type { JsonObject } from 'main.core';
import type { ImModelRecentItem, ImModelCallItem } from 'im.v2.model';

// @vue/component
export const RecentList = {
	name: 'RecentList',
	components: { LoadingState: RecentLoadingState, RecentItem, ActiveCall, CreateChat, EmptyState },
	directives: {
		'recent-list-observer':
		{
			mounted(element, binding)
			{
				binding.instance.observer.observe(element);
			},
		},
	},
	props: {
		compactMode: {
			type: Boolean,
			default: false,
		},
		recentService: {
			type: Object,
			required: false,
			default(): null {
				return null;
			},
		},
	},
	emits: ['chatClick'],
	data(): JsonObject
	{
		return {
			isLoading: false,
			visibleElements: new Set(),
			listIsScrolled: false,
			isCreatingChat: false,
		};
	},
	computed:
	{
		collection(): ImModelRecentItem[]
		{
			return this.getRecentService().getCollection();
		},
		preparedItems(): ImModelRecentItem[]
		{
			const filteredCollection = this.collection.filter((item) => {
				if (!this.showBirthdays && item.options.birthdayPlaceholder)
				{
					return false;
				}

				const dialog = this.$store.getters['chats/get'](item.dialogId, true);
				const isUser = dialog.type === ChatType.user;
				const hasBirthday = isUser && this.showBirthdays && this.$store.getters['users/hasBirthday'](item.dialogId);

				const isInvited = item.options.defaultUserRecord === true;
				const needToShowInvited = this.showInvited || hasBirthday;
				if (isInvited && !needToShowInvited)
				{
					return false;
				}

				return true;
			});

			return [...filteredCollection].sort((a, b) => {
				const firstDate = this.$store.getters['recent/getMessageDate'](a.dialogId);
				const secondDate = this.$store.getters['recent/getMessageDate'](b.dialogId);

				return secondDate - firstDate;
			});
		},
		activeCalls(): ImModelCallItem[]
		{
			return this.$store.getters['recent/calls/get'];
		},
		pinnedItems(): ImModelRecentItem[]
		{
			return this.preparedItems.filter((item) => {
				return item.pinned === true;
			});
		},
		generalItems(): ImModelRecentItem[]
		{
			return this.preparedItems.filter((item) => {
				return item.pinned === false;
			});
		},
		showBirthdays(): boolean
		{
			return this.$store.getters['application/settings/get'](Settings.recent.showBirthday);
		},
		showInvited(): boolean
		{
			return this.$store.getters['application/settings/get'](Settings.recent.showInvited);
		},
		containerClasses(): Object<string, boolean>
		{
			return { '--compact': this.compactMode };
		},
	},
	created()
	{
		this.service = this.recentService ?? RecentService.getInstance();
		this.contextMenuManager = new RecentMenu();

		this.initBroadcastManager();
		this.initLikeManager();
		this.initObserver();
		this.initBirthdayCheck();
		this.initCreateChatManager();
		this.managePreloadedList();

		this.isLoading = true;
		const ignorePreloadedItems = !this.compactMode;
		// eslint-disable-next-line promise/catch-or-return
		this.getRecentService().loadFirstPage({ ignorePreloadedItems })
			.then(() => {
				this.isLoading = false;
				DraftManager.getInstance().initDraftHistory();
			});
	},
	beforeUnmount()
	{
		this.contextMenuManager.destroy();
		this.clearBirthdayCheck();
		this.destroyBroadcastManager();
		this.destroyLikeManager();
		this.destroyCreateChatManager();
	},
	methods:
	{
		onScroll(event)
		{
			this.listIsScrolled = event.target.scrollTop > 0;

			this.contextMenuManager.close();
			if (!this.oneScreenRemaining(event) || !this.getRecentService().hasMoreItemsToLoad)
			{
				return;
			}

			this.isLoading = true;
			// eslint-disable-next-line promise/catch-or-return
			this.getRecentService().loadNextPage().then(() => {
				this.isLoading = false;
			});
		},
		onClick(item, event)
		{
			if (this.compactMode)
			{
				Messenger.openChat(item.dialogId);

				return;
			}

			this.$emit('chatClick', item.dialogId);
		},
		onRightClick(item, event)
		{
			if (Utils.key.isCombination(event, 'Alt+Shift'))
			{
				return;
			}

			const context = {
				...item,
				compactMode: this.compactMode,
			};

			this.contextMenuManager.openMenu(context, event.currentTarget);

			event.preventDefault();
		},
		onCallClick({ item, $event })
		{
			this.onClick(item, $event);
		},
		onCallRightClick({ item, $event })
		{
			this.onRightClick(item, $event);
		},
		oneScreenRemaining(event): boolean
		{
			const bottomPointOfVisibleContent = event.target.scrollTop + event.target.clientHeight;
			const containerHeight = event.target.scrollHeight;
			const oneScreenHeight = event.target.clientHeight;

			return bottomPointOfVisibleContent >= containerHeight - oneScreenHeight;
		},
		initObserver()
		{
			this.observer = new IntersectionObserver(((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting && entry.intersectionRatio === 1)
					{
						this.visibleElements.add(entry.target.dataset.id);
					}
					else if (!entry.isIntersecting)
					{
						this.visibleElements.delete(entry.target.dataset.id);
					}
				});
			}), { threshold: [0, 1] });
		},
		initBroadcastManager()
		{
			this.onRecentListUpdate = (event) => {
				this.getRecentService().setPreloadedData(event.data);
			};
			this.broadcastManager = BroadcastManager.getInstance();
			this.broadcastManager.subscribe(BroadcastManager.events.recentListUpdate, this.onRecentListUpdate);
		},
		destroyBroadcastManager()
		{
			this.broadcastManager = BroadcastManager.getInstance();
			this.broadcastManager.unsubscribe(BroadcastManager.events.recentListUpdate, this.onRecentListUpdate);
		},
		initLikeManager()
		{
			this.likeManager = new LikeManager();
			this.likeManager.init();
		},
		destroyLikeManager()
		{
			this.likeManager.destroy();
		},
		initBirthdayCheck()
		{
			const fourHours = 60000 * 60 * 4;
			const day = 60000 * 60 * 24;
			this.birthdayCheckTimeout = setTimeout(() => {
				this.getRecentService().loadFirstPage();
				this.birthdayCheckInterval = setInterval(() => {
					this.getRecentService().loadFirstPage();
				}, day);
			}, Utils.date.getTimeToNextMidnight() + fourHours);
		},
		clearBirthdayCheck()
		{
			clearTimeout(this.birthdayCheckTimeout);
			clearInterval(this.birthdayCheckInterval);
		},
		initCreateChatManager()
		{
			if (CreateChatManager.getInstance().isCreating())
			{
				this.isCreatingChat = true;
			}

			this.onCreationStatusChange = (event: BaseEvent<boolean>) => {
				this.isCreatingChat = event.getData();
			};
			CreateChatManager.getInstance().subscribe(
				CreateChatManager.events.creationStatusChange,
				this.onCreationStatusChange,
			);
		},
		destroyCreateChatManager()
		{
			CreateChatManager.getInstance().unsubscribe(
				CreateChatManager.events.creationStatusChange,
				this.onCreationStatusChange,
			);
		},
		managePreloadedList()
		{
			const { preloadedList } = Core.getApplicationData();
			if (!preloadedList || !this.compactMode)
			{
				return;
			}

			this.getRecentService().setPreloadedData(preloadedList);
			this.broadcastManager.sendRecentList(preloadedList);
		},
		getRecentService(): RecentService
		{
			return this.service;
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-im-list-recent__scope bx-im-list-recent__container" :class="containerClasses">
			<div v-if="activeCalls.length > 0" class="bx-im-list-recent__calls_container" :class="{'--with-shadow': listIsScrolled}">
				<ActiveCall
					v-for="activeCall in activeCalls"
					:key="activeCall.dialogId"
					:item="activeCall"
					:compactMode="compactMode"
					@click="onCallClick"
				/>
			</div>
			<CreateChat v-if="isCreatingChat && !compactMode"></CreateChat>
			<div @scroll="onScroll" class="bx-im-list-recent__scroll-container">
				<div v-if="pinnedItems.length > 0" class="bx-im-list-recent__pinned_scope bx-im-list-recent__pinned_container">
					<RecentItem
						v-for="item in pinnedItems"
						:key="item.dialogId"
						:item="item"
						:compactMode="compactMode"
						:isVisibleOnScreen="visibleElements.has(item.dialogId)"
						v-recent-list-observer
						@click="onClick(item, $event)"
						@click.right="onRightClick(item, $event)"
					/>
				</div>
				<div class="bx-im-list-recent__general_container">
					<RecentItem
						v-for="item in generalItems"
						:key="item.dialogId"
						:item="item"
						:compactMode="compactMode"
						:isVisibleOnScreen="visibleElements.has(item.dialogId)"
						v-recent-list-observer
						@click="onClick(item, $event)"
						@click.right="onRightClick(item, $event)"
					/>
				</div>	
				<LoadingState v-if="isLoading" :compactMode="compactMode" />
				<EmptyState v-if="collection.length === 0" :compactMode="compactMode" />
			</div>
		</div>
	`,
};

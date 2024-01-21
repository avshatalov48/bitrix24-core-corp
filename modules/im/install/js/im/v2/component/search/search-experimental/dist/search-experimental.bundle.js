/* eslint-disable */
this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
this.BX.Messenger.v2 = this.BX.Messenger.v2 || {};
(function (exports,ui_designTokens,ui_fonts_opensans,im_public,im_v2_lib_utils,im_v2_lib_logger,im_v2_lib_localStorage,im_v2_provider_service,im_v2_lib_menu,im_v2_application_core,main_core_events,main_core,im_v2_const,im_v2_lib_dateFormatter,im_v2_lib_textHighlighter,im_v2_component_elements) {
	'use strict';

	class SearchContextMenu extends im_v2_lib_menu.RecentMenu {
	  getMenuItems() {
	    return [this.getOpenItem(), this.getCallItem(), this.getOpenProfileItem(), this.getChatsWithUserItem()];
	  }
	}

	// @vue/component
	const MyNotes = {
	  name: 'MyNotes',
	  emits: ['clickItem'],
	  computed: {
	    dialogId() {
	      return im_v2_application_core.Core.getUserId().toString();
	    },
	    name() {
	      return this.$Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_MY_NOTES');
	    }
	  },
	  created() {
	    this.contextMenuManager = new SearchContextMenu();
	  },
	  beforeUnmount() {
	    this.contextMenuManager.destroy();
	  },
	  methods: {
	    onClick(event) {
	      this.$emit('clickItem', {
	        dialogId: this.dialogId,
	        nativeEvent: event
	      });
	    }
	  },
	  template: `
		<div 
			class="bx-im-search-my-notes__container bx-im-search-my-notes__scope"
			@click="onClick" 
			@click.right.prevent
		>
			<div class="bx-im-search-my-notes__avatar"></div>
			<div class="bx-im-search-my-notes__title" :title="name">
				{{ name }}
			</div>
		</div>
	`
	};

	// @vue/component
	const CarouselUser = {
	  name: 'CarouselUser',
	  components: {
	    Avatar: im_v2_component_elements.Avatar
	  },
	  props: {
	    userId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['clickItem'],
	  computed: {
	    AvatarSize: () => im_v2_component_elements.AvatarSize,
	    dialogId() {
	      return this.userId.toString();
	    },
	    user() {
	      return this.$store.getters['users/get'](this.dialogId, true);
	    },
	    name() {
	      var _this$user$firstName;
	      return (_this$user$firstName = this.user.firstName) != null ? _this$user$firstName : this.user.name;
	    },
	    isExtranet() {
	      return this.user.extranet;
	    }
	  },
	  created() {
	    this.contextMenuManager = new SearchContextMenu();
	  },
	  beforeUnmount() {
	    this.contextMenuManager.destroy();
	  },
	  methods: {
	    onClick(event) {
	      this.$emit('clickItem', {
	        dialogId: this.dialogId,
	        nativeEvent: event
	      });
	    },
	    onRightClick(event) {
	      if (event.altKey && event.shiftKey) {
	        return;
	      }
	      const item = {
	        dialogId: this.dialogId
	      };
	      main_core_events.EventEmitter.emit(im_v2_const.EventType.search.openContextMenu, {
	        item,
	        nativeEvent: event
	      });
	    }
	  },
	  template: `
		<div 
			class="bx-im-carousel-user__container bx-im-carousel-user__scope"
			:class="{'--extranet': isExtranet}"
			@click="onClick" 
			@click.right.prevent="onRightClick"
		>
			<Avatar :dialogId="dialogId" :size="AvatarSize.XL" />
			<div class="bx-im-carousel-user__title" :title="name">
				{{ name }}
			</div>
		</div>
	`
	};

	const SHOW_USERS_LIMIT = 6;

	// @vue/component
	const RecentUsersCarousel = {
	  name: 'RecentUsersCarousel',
	  components: {
	    CarouselUser,
	    MyNotes
	  },
	  props: {
	    withMyNotes: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['clickItem'],
	  computed: {
	    users() {
	      const recentUsers = [];
	      this.$store.getters['recent/getSortedCollection'].forEach(recentItem => {
	        if (this.isChat(recentItem.dialogId)) {
	          return;
	        }
	        const user = this.$store.getters['users/get'](recentItem.dialogId, true);
	        if (user.bot || user.id === im_v2_application_core.Core.getUserId()) {
	          return;
	        }
	        recentUsers.push(user);
	      });
	      return recentUsers.map(user => user.id);
	    },
	    items() {
	      const limit = this.withMyNotes ? SHOW_USERS_LIMIT - 1 : SHOW_USERS_LIMIT;
	      return this.users.slice(0, limit);
	    },
	    currentUserId() {
	      return im_v2_application_core.Core.getUserId();
	    }
	  },
	  methods: {
	    isChat(dialogId) {
	      return dialogId.startsWith('chat');
	    }
	  },
	  template: `
		<div class="bx-im-recent-users-carousel__container bx-im-recent-users-carousel__scope">
			<div class="bx-im-recent-users-carousel__title-container">
				<span class="bx-im-recent-users-carousel__section-title">
					{{ $Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_SECTION_RECENT_CHATS') }}
				</span>
			</div>
			<div class="bx-im-recent-users-carousel__users-container">
				<MyNotes
					v-if="withMyNotes"
					@clickItem="$emit('clickItem', $event)"
				/>
				<CarouselUser
					v-for="userId in items"
					:key="userId"
					:userId="userId"
					@clickItem="$emit('clickItem', $event)"
				/>
			</div>
		</div>
	`
	};

	// @vue/component
	const SearchExperimentalItem = {
	  name: 'SearchExperimentalItem',
	  components: {
	    Avatar: im_v2_component_elements.Avatar,
	    ChatTitleWithHighlighting: im_v2_component_elements.ChatTitleWithHighlighting
	  },
	  props: {
	    dialogId: {
	      type: String,
	      required: true
	    },
	    withDate: {
	      type: Boolean,
	      default: false
	    },
	    query: {
	      type: String,
	      default: ''
	    }
	  },
	  emits: ['clickItem'],
	  computed: {
	    AvatarSize: () => im_v2_component_elements.AvatarSize,
	    user() {
	      return this.$store.getters['users/get'](this.dialogId, true);
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.dialogId, true);
	    },
	    recentItem() {
	      return this.$store.getters['recent/get'](this.dialogId);
	    },
	    isChat() {
	      return !this.isUser;
	    },
	    isUser() {
	      return this.dialog.type === im_v2_const.ChatType.user;
	    },
	    position() {
	      if (!this.isUser) {
	        return '';
	      }
	      return this.user.workPosition;
	    },
	    userItemText() {
	      if (!this.position) {
	        return this.loc('IM_SEARCH_EXPERIMENTAL_ITEM_USER_TYPE_GROUP_V2');
	      }
	      return im_v2_lib_textHighlighter.highlightText(main_core.Text.encode(this.position), this.query);
	    },
	    chatItemText() {
	      if (this.isFoundByUser) {
	        return `<span class="--highlight">${this.loc('IM_SEARCH_EXPERIMENTAL_ITEM_FOUND_BY_USER')}</span>`;
	      }
	      return this.loc('IM_SEARCH_EXPERIMENTAL_ITEM_CHAT_TYPE_GROUP_V2');
	    },
	    chatItemTextForTitle() {
	      if (this.isFoundByUser) {
	        return this.loc('IM_SEARCH_EXPERIMENTAL_ITEM_FOUND_BY_USER');
	      }
	      return this.loc('IM_SEARCH_EXPERIMENTAL_ITEM_CHAT_TYPE_GROUP_V2');
	    },
	    itemText() {
	      return this.isUser ? this.userItemText : this.chatItemText;
	    },
	    itemTextForTitle() {
	      return this.isUser ? this.position : this.chatItemTextForTitle;
	    },
	    formattedDate() {
	      if (!this.recentItem.message.date) {
	        return '';
	      }
	      return this.formatDate(this.recentItem.message.date);
	    },
	    isFoundByUser() {
	      const searchRecentItem = this.$store.getters['recent/search/get'](this.dialogId);
	      if (!searchRecentItem) {
	        return false;
	      }
	      return Boolean(searchRecentItem.foundByUser);
	    }
	  },
	  methods: {
	    onClick(event) {
	      this.$emit('clickItem', {
	        dialogId: this.dialogId,
	        nativeEvent: event
	      });
	    },
	    onRightClick(event) {
	      if (event.altKey && event.shiftKey) {
	        return;
	      }
	      const item = {
	        dialogId: this.dialogId
	      };
	      main_core_events.EventEmitter.emit(im_v2_const.EventType.search.openContextMenu, {
	        item,
	        nativeEvent: event
	      });
	    },
	    formatDate(date) {
	      return im_v2_lib_dateFormatter.DateFormatter.formatByTemplate(date, im_v2_lib_dateFormatter.DateTemplate.recent);
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div 
			@click="onClick" 
			@click.right.prevent="onRightClick" 
			class="bx-im-search-experimental-item__container bx-im-search-experimental-item__scope"
		>
			<div class="bx-im-search-experimental-item__avatar-container">
				<Avatar :dialogId="dialogId" :size="AvatarSize.XL" />
			</div>
			<div class="bx-im-search-experimental-item__content-container">
				<div class="bx-im-search-experimental-item__content_header">
					<ChatTitleWithHighlighting :dialogId="dialogId" :textToHighlight="query" />
					<div v-if="withDate && formattedDate.length > 0" class="bx-im-search-experimental-item__date">
						<span>{{ formattedDate }}</span>
					</div>
				</div>
				<div class="bx-im-search-experimental-item__item-text" :title="itemTextForTitle" v-html="itemText"></div>
			</div>
		</div>
	`
	};

	// @vue/component
	const LatestSearchResult = {
	  name: 'LatestSearchResult',
	  components: {
	    RecentUsersCarousel,
	    SearchExperimentalItem,
	    Loader: im_v2_component_elements.Loader
	  },
	  props: {
	    dialogIds: {
	      type: Array,
	      default: () => []
	    },
	    isLoading: {
	      type: Boolean,
	      default: false
	    },
	    withMyNotes: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['clickItem'],
	  computed: {
	    title() {
	      return this.$Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_SECTION_RECENT');
	    }
	  },
	  template: `
		<div class="bx-im-latest-search-result__scope">
			<RecentUsersCarousel :withMyNotes="withMyNotes" @clickItem="$emit('clickItem', $event)" />
			<div class="bx-im-latest-search-result__title">{{ title }}</div>
			<SearchExperimentalItem
				v-for="dialogId in dialogIds"
				:key="dialogId"
				:dialogId="dialogId"
				@clickItem="$emit('clickItem', $event)"
			/>
			<Loader v-if="isLoading" class="bx-im-latest-search-result__loader" />
		</div>
	`
	};

	// @vue/component
	const EmptyState = {
	  name: 'EmptyState',
	  computed: {
	    title() {
	      return this.$Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_RESULT_NOT_FOUND');
	    },
	    subTitle() {
	      return this.$Bitrix.Loc.getMessage('IM_SEARCH_EXPERIMENTAL_RESULT_NOT_FOUND_DESCRIPTION');
	    }
	  },
	  template: `
		<div class="bx-im-search-experimental-empty-state__container bx-im-search-experimental-empty-state__scope">
			<div class="bx-im-search-experimental-empty-state__icon"></div>
			<div class="bx-im-search-experimental-empty-state__title">
				{{ title }}
			</div>
			<div class="bx-im-search-experimental-empty-state__subtitle">
				{{ subTitle }}
			</div>
		</div>
	`
	};

	// @vue/component
	const SearchExperimentalResult = {
	  name: 'SearchExperimentalResult',
	  components: {
	    SearchExperimentalItem,
	    EmptyState,
	    Loader: im_v2_component_elements.Loader
	  },
	  props: {
	    dialogIds: {
	      type: Array,
	      default: () => []
	    },
	    isLoading: {
	      type: Boolean,
	      default: false
	    },
	    query: {
	      type: String,
	      default: ''
	    }
	  },
	  emits: ['clickItem'],
	  computed: {
	    isEmptyState() {
	      return this.dialogIds.length === 0;
	    }
	  },
	  template: `
		<div class="bx-im-search-experimental-result__scope">
			<SearchExperimentalItem
				v-for="dialogId in dialogIds"
				:key="dialogId"
				:dialogId="dialogId"
				:withDate="true"
				:query="query"
				@clickItem="$emit('clickItem', $event)"
			/>
			<EmptyState v-if="isEmptyState" />
		</div>
	`
	};

	// @vue/component
	const SearchExperimental = {
	  name: 'SearchExperimental',
	  components: {
	    ScrollWithGradient: im_v2_component_elements.ScrollWithGradient,
	    LatestSearchResult,
	    SearchExperimentalResult
	  },
	  props: {
	    searchQuery: {
	      type: String,
	      default: ''
	    },
	    searchMode: {
	      type: Boolean,
	      required: true
	    },
	    handleClickItem: {
	      type: Boolean,
	      default: true
	    },
	    withMyNotes: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      isRecentLoading: false,
	      isServerLoading: false,
	      queryWasDeleted: false,
	      currentServerQueries: 0,
	      result: {
	        recent: [],
	        usersAndChats: []
	      }
	    };
	  },
	  computed: {
	    cleanQuery() {
	      return this.searchQuery.trim().toLowerCase();
	    },
	    showLatestSearchResult() {
	      return this.cleanQuery.length === 0;
	    }
	  },
	  watch: {
	    cleanQuery(newQuery, previousQuery) {
	      if (newQuery.length > 0) {
	        this.queryWasDeleted = false;
	      }
	      if (newQuery.length === 0) {
	        this.searchService.clearSessionResult();
	      }
	      if (newQuery === previousQuery) {
	        return;
	      }
	      this.startSearch(newQuery);
	    },
	    isServerLoading(newValue) {
	      this.$emit('loading', newValue);
	    }
	  },
	  created() {
	    this.initSettings();
	    this.contextMenuManager = new SearchContextMenu();
	    this.findByParticipants = im_v2_lib_localStorage.LocalStorageManager.getInstance().get(im_v2_const.LocalStorageKey.findByParticipants, false);
	    this.searchService = new im_v2_provider_service.SearchService({
	      findByParticipants: this.findByParticipants
	    });
	    this.searchOnServerDelayed = main_core.Runtime.debounce(this.searchOnServer, 400, this);
	    main_core_events.EventEmitter.subscribe(im_v2_const.EventType.search.openContextMenu, this.onOpenContextMenu);
	    main_core_events.EventEmitter.subscribe(im_v2_const.EventType.dialog.errors.accessDenied, this.onDelete);
	    main_core_events.EventEmitter.subscribe(im_v2_const.EventType.search.keyPressed, this.onKeyPressed);
	    this.loadRecentSearchFromServer();
	  },
	  beforeUnmount() {
	    this.contextMenuManager.destroy();
	    main_core_events.EventEmitter.unsubscribe(im_v2_const.EventType.search.openContextMenu, this.onOpenContextMenu);
	    main_core_events.EventEmitter.unsubscribe(im_v2_const.EventType.dialog.errors.accessDenied, this.onDelete);
	    main_core_events.EventEmitter.unsubscribe(im_v2_const.EventType.search.keyPressed, this.onKeyPressed);
	  },
	  methods: {
	    loadRecentSearchFromServer() {
	      this.isRecentLoading = true;
	      this.searchService.loadLatestResults().then(recentItemsFromServer => {
	        this.result.recent = recentItemsFromServer;
	        this.isRecentLoading = false;
	      }).catch(error => {
	        im_v2_lib_logger.Logger.error('SearchExperimental: loadRecentSearchFromServer', error);
	      });
	    },
	    initSettings() {
	      const settings = main_core.Extension.getSettings('im.v2.component.search.search-result');
	      const defaultMinTokenSize = 3;
	      this.minTokenSize = settings.get('minTokenSize', defaultMinTokenSize);
	    },
	    startSearch(query) {
	      if (!this.findByParticipants && query.length > 0) {
	        this.searchService.searchLocal(query).then(dialogIds => {
	          if (query !== this.cleanQuery) {
	            return;
	          }
	          this.result.usersAndChats = this.searchService.sortByDate(dialogIds);
	        }).catch(error => {
	          im_v2_lib_logger.Logger.error('SearchExperimental: startSearch', error);
	        });
	      }
	      if (query.length >= this.minTokenSize) {
	        this.isServerLoading = true;
	        this.searchOnServerDelayed(query);
	      }
	      if (query.length === 0) {
	        this.cleanSearchResult();
	      }
	    },
	    cleanSearchResult() {
	      this.result.usersAndChats = [];
	    },
	    searchOnServer(query) {
	      this.currentServerQueries++;
	      this.searchService.searchOnServer(query).then(dialogIds => {
	        if (query !== this.cleanQuery) {
	          this.stopLoader();
	          return;
	        }
	        if (this.findByParticipants) {
	          this.result.usersAndChats = this.searchService.sortByDate(dialogIds);
	        } else {
	          const mergedItems = this.mergeResults(this.result.usersAndChats, dialogIds);
	          this.result.usersAndChats = this.searchService.sortByDate(mergedItems);
	        }
	      }).catch(error => {
	        console.error(error);
	      }).finally(() => {
	        this.currentServerQueries--;
	        this.stopLoader();
	      });
	    },
	    stopLoader() {
	      if (this.currentServerQueries > 0) {
	        return;
	      }
	      this.isServerLoading = false;
	    },
	    onOpenContextMenu(event) {
	      const {
	        item,
	        nativeEvent
	      } = event.getData();
	      const recentItem = this.$store.getters['recent/get'](item.dialogId);
	      if (im_v2_lib_utils.Utils.key.isAltOrOption(nativeEvent)) {
	        return;
	      }
	      this.contextMenuManager.openMenu(recentItem, nativeEvent.currentTarget);
	    },
	    onDelete({
	      data: eventData
	    }) {
	      const {
	        dialogId
	      } = eventData;
	      this.result.recent = this.result.recent.filter(recentItem => {
	        return recentItem !== dialogId;
	      });
	      this.result.usersAndChats = this.result.usersAndChats.filter(dialogIdFromSearch => {
	        return dialogIdFromSearch !== dialogId;
	      });
	    },
	    onScroll(event) {
	      this.$emit('scroll', event);
	      this.contextMenuManager.destroy();
	    },
	    onClickItem(event) {
	      const {
	        dialogId,
	        nativeEvent
	      } = event;
	      if (!this.searchMode) {
	        return;
	      }
	      this.searchService.addItemToRecent(dialogId).then(() => {
	        this.loadRecentSearchFromServer();
	      }).catch(error => {
	        im_v2_lib_logger.Logger.error('SearchExperimental.onClickItem: addItemToRecent', error);
	      });
	      im_public.Messenger.openChat(dialogId);
	      if (!this.handleClickItem) {
	        this.$emit('clickItem', event);
	        return;
	      }
	      if (!im_v2_lib_utils.Utils.key.isAltOrOption(nativeEvent)) {
	        main_core_events.EventEmitter.emit(im_v2_const.EventType.search.close);
	      }
	    },
	    onKeyPressed(event) {
	      const {
	        keyboardEvent
	      } = event.getData();
	      if (im_v2_lib_utils.Utils.key.isCombination(keyboardEvent, 'Enter')) {
	        this.onPressEnterKey(event);
	      }
	      if (im_v2_lib_utils.Utils.key.isCombination(keyboardEvent, 'Backspace')) {
	        this.onPressBackspaceKey();
	      }
	    },
	    onPressEnterKey(keyboardEvent) {
	      const firstItem = this.getFirstItemFromSearchResults();
	      if (!firstItem) {
	        return;
	      }
	      this.onClickItem({
	        dialogId: firstItem,
	        nativeEvent: keyboardEvent
	      });
	    },
	    onPressBackspaceKey() {
	      if (this.searchQuery.length > 0) {
	        this.queryWasDeleted = false;
	        return;
	      }
	      if (!this.queryWasDeleted) {
	        this.queryWasDeleted = true;
	        return;
	      }
	      if (this.queryWasDeleted) {
	        main_core_events.EventEmitter.emit(im_v2_const.EventType.search.close);
	      }
	    },
	    getFirstItemFromSearchResults() {
	      if (this.showLatestSearchResult && this.result.recent.length > 0) {
	        return this.result.recent[0];
	      }
	      if (this.result.usersAndChats.length > 0) {
	        return this.result.usersAndChats[0];
	      }
	      return null;
	    },
	    mergeResults(originalItems, newItems) {
	      newItems.forEach(newItem => {
	        if (!originalItems.includes(newItem)) {
	          originalItems.push(newItem);
	        }
	      });
	      return originalItems;
	    }
	  },
	  template: `
		<ScrollWithGradient :gradientHeight="28" :withShadow="false" @scroll="onScroll"> 
			<div class="bx-im-search-experimental__container bx-im-search-experimental__scope">
				<LatestSearchResult
					v-if="showLatestSearchResult"
					:dialogIds="result.recent"
					:isLoading="isRecentLoading"
					:withMyNotes="withMyNotes"
					@clickItem="onClickItem"
				/>
				<SearchExperimentalResult
					v-else
					:dialogIds="result.usersAndChats"
					:isLoading="isServerLoading"
					:query="cleanQuery"
					@clickItem="onClickItem"
				/>
			</div>
		</ScrollWithGradient> 
	`
	};

	exports.SearchExperimental = SearchExperimental;

}((this.BX.Messenger.v2.Component = this.BX.Messenger.v2.Component || {}),BX,BX,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Provider.Service,BX.Messenger.v2.Lib,BX.Messenger.v2.Application,BX.Event,BX,BX.Messenger.v2.Const,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Component.Elements));
//# sourceMappingURL=search-experimental.bundle.js.map

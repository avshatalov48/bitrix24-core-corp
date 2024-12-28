/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Component = this.BX.OpenLines.v2.Component || {};
(function (exports,im_v2_application_core,imopenlines_v2_const,imopenlines_v2_provider_service,im_v2_component_elements,im_v2_const,im_v2_lib_dateFormatter,im_v2_lib_utils,im_v2_lib_parser) {
	'use strict';

	// @vue/component
	const EmptyState = {
	  name: 'EmptyState',
	  computed: {
	    message() {
	      return this.loc('IMOL_LIST_RECENT_EMPTY_MESSAGE');
	    }
	  },
	  methods: {
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent-empty-state__container">
			<p class="bx-im-list-openlines-empty-state__text">
				{{ message }}
			</p>
		</div>
	`
	};

	// @vue/component
	const MessageText = {
	  name: 'MessageText',
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    recentItems() {
	      return this.item;
	    },
	    message() {
	      return this.$store.getters['messages/getById'](this.recentItems.messageId);
	    },
	    lastMessageAuthorAvatar() {
	      const authorDialog = this.$store.getters['chats/get'](this.message.authorId);
	      if (!authorDialog) {
	        return '';
	      }
	      return authorDialog.avatar;
	    },
	    lastMessageAuthorAvatarStyle() {
	      return {
	        backgroundImage: `url('${this.lastMessageAuthorAvatar}')`
	      };
	    },
	    formattedMessageText() {
	      if (this.message.isDeleted) {
	        return this.loc('IMOL_LIST_RECENT_DELETED_MESSAGE');
	      }
	      const SPLIT_INDEX = 27;
	      const formattedText = im_v2_lib_parser.Parser.purifyRecent(this.recentItems);
	      return im_v2_lib_utils.Utils.text.insertUnseenWhitespace(formattedText, SPLIT_INDEX);
	    }
	  },
	  methods: {
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent-item__message">
			<span class="bx-imol-list-recent-item__message_text-container">
				<template v-if="message.authorId">
					<span v-if="lastMessageAuthorAvatar" :style="lastMessageAuthorAvatarStyle" class="bx-imol-list-recent-item__message_author-icon --user"></span>
					<span v-else class="bx-imol-list-recent-item__message_author-icon --user --default"></span>
				</template>
				<span class="bx-imol-list-recent-item__message_text">{{ formattedMessageText }}</span>
			</span>
		</div>
	`
	};

	// @vue/component
	const ItemCounter = {
	  name: 'ItemCounter',
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    recentItem() {
	      return this.item;
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.item.dialogId);
	    },
	    openLinesCounter() {
	      return this.$store.getters['counters/getSpecificLinesCounter'](this.dialog.chatId);
	    },
	    totalCounter() {
	      return this.openLinesCounter;
	    },
	    formattedCounter() {
	      return this.formatCounter(this.totalCounter);
	    }
	  },
	  methods: {
	    formatCounter(counter) {
	      return counter > 99 ? '99+' : counter.toString();
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent-item__counter_wrap">
			<div class="bx-imol-list-recent-item__counter_container">
				<div v-if="formattedCounter > 0" class="bx-imol-list-recent-item__counter_number">
					{{ formattedCounter }}
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const RecentItem = {
	  name: 'RecentItem',
	  components: {
	    ChatTitle: im_v2_component_elements.ChatTitle,
	    ChatAvatar: im_v2_component_elements.ChatAvatar,
	    MessageText,
	    ItemCounter
	  },
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    AvatarSize: () => im_v2_component_elements.AvatarSize,
	    dialog() {
	      return this.$store.getters['chats/get'](this.item.dialogId, true);
	    },
	    layout() {
	      return this.$store.getters['application/getLayout'];
	    },
	    message() {
	      return this.$store.getters['messages/getById'](this.item.messageId);
	    },
	    recentItem() {
	      return this.item;
	    },
	    formattedDate() {
	      return this.message ? this.formatDate(this.message.date) : '';
	    },
	    isChatSelected() {
	      if (this.layout.name !== im_v2_const.Layout.openlinesV2.name) {
	        return false;
	      }
	      return this.layout.entityId === this.recentItem.dialogId;
	    },
	    wrapClasses() {
	      return {
	        '--selected': this.isChatSelected
	      };
	    }
	  },
	  methods: {
	    formatDate(date) {
	      return im_v2_lib_dateFormatter.DateFormatter.formatByTemplate(date, im_v2_lib_dateFormatter.DateTemplate.recent);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent__item" :class="wrapClasses">
			<div class="bx-imol-list-recent-item__main_content">
				<div class="bx-imol-list-recent-item__avatar_container">
					<div class="bx-imol-list-recent-item__avatar_content">
						<ChatAvatar
							:avatarDialogId="recentItem.dialogId"
							:contextDialogId="recentItem.dialogId"
							:size="AvatarSize.XL"
						/>
					</div>
				</div>
				<div class="bx-imol-list-recent-item__content_right">
					<div class="bx-imol-list-recent-item__content_header">
						<div class="bx-imol-list-recent-item__content_title">
							<ChatTitle :dialogId="recentItem.dialogId" />
						</div>
						<div class="bx-imol-list-recent-item__content_date">
							<span class="bx-imol-list-recent-item__content_date">{{ formattedDate }}</span>
						</div>
					</div>
					<div class="bx-imol-list-recent-item__content_bottom">
						<MessageText :item="recentItem" />
						<ItemCounter :item="recentItem" />
					</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const RecentGroup = {
	  name: 'RecentGroup',
	  components: {
	    RecentItem
	  },
	  props: {
	    groupItems: {
	      type: Array,
	      required: true
	    },
	    groupName: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['recentClick'],
	  computed: {
	    groupTitle() {
	      return this.loc(`IMOL_LIST_STATUS_MESSAGE_${this.groupName.toUpperCase()}`);
	    }
	  },
	  methods: {
	    onRecentClick(dialogId) {
	      this.$emit('recentClick', dialogId);
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent__group-item_container" v-if="groupItems.length !== 0">
			<span 
				class="bx-imol-list-recent__group_name" 
				:class="'bx-imol-list-recent__group_name_' + groupName.toLowerCase()"
			>
				{{ groupTitle }}
			</span>
			<RecentItem
				v-for="item in groupItems"
				:item="item"
				:key="item.dialogId"
				@click="onRecentClick(item.dialogId)"
			/>
		</div>
	`
	};

	// @vue/component
	const RecentList = {
	  name: 'RecentList',
	  components: {
	    EmptyState,
	    RecentGroup,
	    LoadingState: im_v2_component_elements.ListLoadingState
	  },
	  emits: ['chatClick'],
	  data() {
	    return {
	      isLoading: false,
	      isLoadingNextPage: false,
	      firstPageLoaded: false
	    };
	  },
	  computed: {
	    collection() {
	      return im_v2_application_core.Core.getStore().getters['recentOpenLines/getOpenLinesCollection'];
	    },
	    collectionByGroups() {
	      const groupsRecent = {
	        [imopenlines_v2_const.StatusGroup.new]: [],
	        [imopenlines_v2_const.StatusGroup.work]: [],
	        [imopenlines_v2_const.StatusGroup.answered]: []
	      };
	      this.collection.forEach(item => {
	        const recentItem = item;
	        const statusName = this.getStatusByDialogId(recentItem.dialogId);
	        groupsRecent[statusName].push(recentItem);
	      });
	      return groupsRecent;
	    },
	    sortedCollectionByGroups() {
	      const sortedGroups = {};
	      Object.entries(this.collectionByGroups).forEach(([groupName, items]) => {
	        sortedGroups[groupName] = this.sortGroupItems(groupName, items);
	      });
	      return sortedGroups;
	    },
	    isEmptyCollection() {
	      return this.collection.length === 0;
	    }
	  },
	  async activated() {
	    this.isLoading = true;
	    await this.getRecentService().loadFirstPage();
	    this.firstPageLoaded = true;
	    this.isLoading = false;
	  },
	  methods: {
	    async onScroll(event) {
	      if (!im_v2_lib_utils.Utils.dom.isOneScreenRemaining(event.target) || !this.getRecentService().hasMoreItemsToLoad()) {
	        return;
	      }
	      this.isLoadingNextPage = true;
	      await this.getRecentService().loadNextPage();
	      this.isLoadingNextPage = false;
	    },
	    onClick(dialogId) {
	      this.$emit('chatClick', dialogId);
	    },
	    getSessionByDialogId(dialogId) {
	      return this.$store.getters['recentOpenLines/getSession'](dialogId);
	    },
	    getStatusByDialogId(dialogId) {
	      const session = this.getSessionByDialogId(dialogId);
	      return session ? session.status : imopenlines_v2_const.StatusGroup.new;
	    },
	    sortGroupItems(groupName, items) {
	      if (groupName === imopenlines_v2_const.StatusGroup.answered) {
	        return this.sortItemsDesc(items);
	      }
	      return this.sortItemsAsc(items);
	    },
	    sortItemsAsc(items) {
	      return items.sort((a, z) => a.sessionId - z.sessionId);
	    },
	    sortItemsDesc(items) {
	      return items.sort((a, z) => {
	        const dateA = this.messageDate(a.messageId);
	        const dateZ = this.messageDate(z.messageId);
	        return dateZ - dateA;
	      });
	    },
	    messageDate(messageId) {
	      const message = im_v2_application_core.Core.getStore().getters['messages/getById'](messageId);
	      return message ? message.date : null;
	    },
	    getRecentService() {
	      if (!this.service) {
	        this.service = new imopenlines_v2_provider_service.RecentService();
	      }
	      return this.service;
	    }
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
	`
	};

	exports.RecentList = RecentList;

}((this.BX.OpenLines.v2.Component.List = this.BX.OpenLines.v2.Component.List || {}),BX.Messenger.v2.Application,BX.OpenLines.v2.Const,BX.OpenLines.v2.Provider.Service,BX.Messenger.v2.Component.Elements,BX.Messenger.v2.Const,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib));
//# sourceMappingURL=recent-list.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Component = this.BX.OpenLines.v2.Component || {};
(function (exports,im_v2_application_core,imopenlines_v2_provider_service,imopenlines_v2_lib_sessionStatus,im_v2_component_elements,im_v2_lib_dateFormatter,im_v2_lib_utils) {
	'use strict';

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
	      return this.$store.getters['recentOpenlines/getMessage'](this.recentItems.dialogId);
	    },
	    formattedMessageText() {
	      const SPLIT_INDEX = 27;
	      return im_v2_lib_utils.Utils.text.insertUnseenWhitespace(this.message.text, SPLIT_INDEX);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent-item__text_date">
			<span class="bx-imol-list-recent-item__message_text">{{formattedMessageText}}</span>
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
	      return this.$store.getters['chats/get'](this.recentItem.dialogId, true);
	    },
	    totalCounter() {
	      return this.openLinesCounter;
	    },
	    openLinesCounter() {
	      return this.$store.getters['counters/getSpecificLinesCounter'](this.dialog.chatId);
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
	    recentItem() {
	      return this.item;
	    },
	    AvatarSize: () => im_v2_component_elements.AvatarSize,
	    message() {
	      return this.$store.getters['recentOpenlines/getMessage'](this.item.dialogId);
	    },
	    formattedDate() {
	      return this.formatDate(this.message.date);
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.item.dialogId, true);
	    }
	  },
	  methods: {
	    formatDate(date) {
	      return im_v2_lib_dateFormatter.DateFormatter.formatByTemplate(date, im_v2_lib_dateFormatter.DateTemplate.recent);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent__item">
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
							<span class="bx-imol-list-recent-item__content_date">{{formattedDate}}</span>
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
	  component: {
	    RecentItem
	  },
	  emits: ['chatClick'],
	  props: {
	    group: {
	      required: true
	    },
	    key: {
	      required: true
	    }
	  },
	  methods: {
	    onClick(dialogId) {
	      this.$emit('chatClick', dialogId);
	    }
	  },
	  template: `
		<div>
			<div v-if="group.length !== 0">
				<span class="bx-imol-list-recent__group_name" :class="'bx-imol-list-recent__group_name_' + key">{{key}}</span>
				<RecentItem
					v-for="item in group"
					@click="onClick(item.dialogId)"
					:item="item"
				/>
			</div>
		</div>
	`
	};

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
				{{message}}
			</p>
		</div>
	`
	};

	// @vue/component

	const RecentList = {
	  name: 'RecentList',
	  components: {
	    EmptyState,
	    RecentGroup
	  },
	  emits: ['chatClick'],
	  computed: {
	    collection() {
	      return im_v2_application_core.Core.getStore().getters['recentOpenlines/getOpenlinesCollection'];
	    },
	    groups() {
	      return this.setGroups();
	    },
	    isEmptyCollection() {
	      return this.collection.length === 0;
	    }
	  },
	  async created() {
	    await this.getOpenlinesService().requestItems();
	  },
	  methods: {
	    onClick(dialogId) {
	      this.$emit('chatClick', dialogId);
	    },
	    getOpenlinesService() {
	      this.service = new imopenlines_v2_provider_service.RecentService();
	      return this.service;
	    },
	    getSession(dialogId) {
	      return this.$store.getters['recentOpenlines/getSession'](dialogId);
	    },
	    getStatus(dialogId) {
	      const session = this.getSession(dialogId);
	      if (session) {
	        return session.status;
	      }
	      return 'NEW';
	    },
	    isSessionStatusAvailable(statusName) {
	      return imopenlines_v2_lib_sessionStatus.SessionManager.isSessionAvailable(statusName);
	    },
	    setGroups() {
	      const groups = {
	        new: [],
	        work: [],
	        answered: []
	      };
	      this.collection.forEach(item => {
	        const status = this.getStatus(item.dialogId);
	        if (this.isSessionStatusAvailable(imopenlines_v2_lib_sessionStatus.SessionStatus.NEW)[status] || status === 'NEW') {
	          groups.new.push(item);
	          return;
	        }
	        if (this.isSessionStatusAvailable(imopenlines_v2_lib_sessionStatus.SessionStatus.WORK)[status]) {
	          groups.work.push(item);
	          return;
	        }
	        if (this.isSessionStatusAvailable(imopenlines_v2_lib_sessionStatus.SessionStatus.ANSWERED)[status]) {
	          groups.answered.push(item);
	        }
	      });
	      return groups;
	    },
	    setTitleGroup(group) {
	      return this.loc(`IMOL_LIST_OPENLINE_STATUS_MESSAGE_${group.toUpperCase()}`);
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div class="bx-imol-list-recent__content">
			<EmptyState v-if="isEmptyCollection" />
			<RecentGroup v-for="(group, key) in groups" :group="group" :key="key"/>
		</div>
	`
	};

	exports.RecentList = RecentList;

}((this.BX.OpenLines.v2.Component.List = this.BX.OpenLines.v2.Component.List || {}),BX.Messenger.v2.Application,BX.OpenLines.v2.Provider.Service,BX.OpenLines.v2.Lib,BX.Messenger.v2.Component.Elements,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib));
//# sourceMappingURL=recent.bundle.js.map

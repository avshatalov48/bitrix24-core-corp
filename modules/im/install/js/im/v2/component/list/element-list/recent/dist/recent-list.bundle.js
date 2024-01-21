/* eslint-disable */
this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
this.BX.Messenger.v2 = this.BX.Messenger.v2 || {};
this.BX.Messenger.v2.Component = this.BX.Messenger.v2.Component || {};
(function (exports,ui_designTokens,main_polyfill_intersectionobserver,im_v2_provider_service,im_v2_lib_menu,im_v2_lib_draft,main_popup,im_v2_lib_slider,im_public,main_date,im_v2_lib_parser,im_v2_lib_dateFormatter,im_v2_lib_call,im_v2_lib_createChat,im_v2_lib_layout,im_v2_component_elements,main_core,im_v2_lib_utils,main_core_events,im_v2_application_core,im_v2_const) {
	'use strict';

	// @vue/component
	const NewUserPopup = {
	  name: 'NewUserPopup',
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    text: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['click', 'close'],
	  mounted() {
	    // BX.MessengerProxy.playNewUserSound();
	    this.setCloseTimer(5000);
	    this.onClosePopupHandler = this.onClosePopup.bind(this);
	    // EventEmitter.subscribe(EventType.dialog.closePopup, this.onClosePopupHandler);
	  },

	  beforeUnmount() {
	    // EventEmitter.unsubscribe(EventType.dialog.closePopup, this.onClosePopupHandler);
	  },
	  methods: {
	    onClick() {
	      this.$emit('click');
	      this.$emit('close');
	    },
	    onMouseOver() {
	      clearTimeout(this.closeTimeout);
	    },
	    onMouseLeave() {
	      this.setCloseTimer(2000);
	    },
	    setCloseTimer(time) {
	      this.closeTimeout = setTimeout(() => {
	        this.$emit('close');
	      }, time);
	    },
	    onClosePopup() {
	      this.$emit('close');
	    }
	  },
	  // language=Vue
	  template: `
<!--		<Transition name="bx-im-recent-new-user-popup">-->
			<div @click="onClick" @mouseover="onMouseOver" @mouseleave="onMouseLeave" class="bx-im-new-user-popup__container">
				<div class="bx-im-new-user-popup__title">{{ title }}</div>
				<div class="bx-im-new-user-popup__text">{{ text }}</div>
			</div>
<!--		</Transition>-->
	`
	};

	// @vue/component
	const MessageText = {
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  computed: {
	    recentItem() {
	      return this.item;
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.recentItem.dialogId, true);
	    },
	    user() {
	      return this.$store.getters['users/get'](this.recentItem.dialogId, true);
	    },
	    needsBirthdayPlaceholder() {
	      if (!this.isUser) {
	        return false;
	      }
	      return this.$store.getters['recent/needsBirthdayPlaceholder'](this.recentItem.dialogId);
	    },
	    needsVacationPlaceholder() {
	      if (!this.isUser) {
	        return false;
	      }
	      return this.$store.getters['recent/needsVacationPlaceholder'](this.recentItem.dialogId);
	    },
	    showLastMessage() {
	      return this.$store.getters['application/settings/get'](im_v2_const.Settings.recent.showLastMessage);
	    },
	    hiddenMessageText() {
	      if (this.isUser) {
	        return this.$store.getters['users/getPosition'](this.recentItem.dialogId);
	      }
	      return this.$Bitrix.Loc.getMessage('IM_LIST_RECENT_CHAT_TYPE_GROUP_V2');
	    },
	    isLastMessageAuthor() {
	      if (!this.recentItem.message) {
	        return false;
	      }
	      return this.recentItem.message.senderId === im_v2_application_core.Core.getUserId();
	    },
	    lastMessageAuthorAvatar() {
	      const authorDialog = this.$store.getters['chats/get'](this.recentItem.message.senderId);
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
	    messageText() {
	      const formattedText = im_v2_lib_parser.Parser.purifyRecent(this.recentItem);
	      if (!formattedText) {
	        return this.isUser ? this.$store.getters['users/getPosition'](this.recentItem.dialogId) : this.hiddenMessageText;
	      }
	      return formattedText;
	    },
	    formattedMessageText() {
	      const SPLIT_INDEX = 27;
	      return im_v2_lib_utils.Utils.text.insertUnseenWhitespace(this.messageText, SPLIT_INDEX);
	    },
	    preparedDraftContent() {
	      const phrase = this.loc('IM_LIST_RECENT_MESSAGE_DRAFT_2');
	      const PLACEHOLDER_LENGTH = '#TEXT#'.length;
	      const prefix = phrase.slice(0, -PLACEHOLDER_LENGTH);
	      return `
				<span class="bx-im-list-recent-item__message_draft-prefix">${prefix}</span>
				<span class="bx-im-list-recent-item__message_text_content">${this.formattedDraftText}</span>
			`;
	    },
	    formattedDraftText() {
	      return im_v2_lib_parser.Parser.purify({
	        text: this.recentItem.draft.text,
	        showIconIfEmptyText: false
	      });
	    },
	    formattedVacationEndDate() {
	      return main_date.DateTimeFormat.format('d.m.Y', this.user.absent);
	    },
	    isUser() {
	      return this.dialog.type === im_v2_const.ChatType.user;
	    },
	    isChat() {
	      return !this.isUser;
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<div class="bx-im-list-recent-item__message_container">
			<span class="bx-im-list-recent-item__message_text">
				<span v-if="recentItem.draft.text && dialog.counter === 0" v-html="preparedDraftContent"></span>
				<div v-else-if="recentItem.invitation.isActive" class="bx-im-list-recent-item__balloon_container --invitation">
					<div class="bx-im-list-recent-item__balloon">{{ loc('IM_LIST_RECENT_INVITATION_NOT_ACCEPTED') }}</div>
				</div>
				<div v-else-if="needsBirthdayPlaceholder" class="bx-im-list-recent-item__balloon_container --birthday">
					<div class="bx-im-list-recent-item__balloon">{{ loc('IM_LIST_RECENT_BIRTHDAY') }}</div>
				</div>
				<div v-else-if="needsVacationPlaceholder" class="bx-im-list-recent-item__balloon_container --vacation">
					<div class="bx-im-list-recent-item__balloon">
						{{ loc('IM_LIST_RECENT_VACATION', {'#VACATION_END_DATE#': formattedVacationEndDate}) }}
					</div>
				</div>
				<template v-else-if="!showLastMessage">
					{{ hiddenMessageText }}
				</template>
				<template v-else>
					<span v-if="isLastMessageAuthor" class="bx-im-list-recent-item__message_author-icon --self"></span>
					<template v-else-if="isChat && recentItem.message.senderId">
						<span v-if="lastMessageAuthorAvatar" :style="lastMessageAuthorAvatarStyle" class="bx-im-list-recent-item__message_author-icon --user"></span>
						<span v-else class="bx-im-list-recent-item__message_author-icon --user --default"></span>
					</template>
					<span class="bx-im-list-recent-item__message_text_content">{{ formattedMessageText }}</span>
				</template>
			</span>
		</div>
	`
	};

	const StatusIcon = {
	  none: '',
	  like: 'like',
	  sending: 'sending',
	  sent: 'sent',
	  viewed: 'viewed'
	};

	// @vue/component
	const MessageStatus = {
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  computed: {
	    recentItem() {
	      return this.item;
	    },
	    user() {
	      return this.$store.getters['users/get'](this.recentItem.dialogId, true);
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.recentItem.dialogId, true);
	    },
	    messageStatus() {
	      if (this.recentItem.message.sending) {
	        return im_v2_const.OwnMessageStatus.sending;
	      }
	      if (this.recentItem.message.status === im_v2_const.MessageStatus.delivered) {
	        return im_v2_const.OwnMessageStatus.viewed;
	      }
	      return im_v2_const.OwnMessageStatus.sent;
	    },
	    statusIcon() {
	      if (!this.isLastMessageAuthor || this.isBot || this.needsBirthdayPlaceholder || this.hasDraft) {
	        return StatusIcon.none;
	      }
	      if (this.isSelfChat) {
	        return StatusIcon.none;
	      }
	      if (this.recentItem.liked) {
	        return StatusIcon.like;
	      }
	      return this.messageStatus;
	    },
	    isLastMessageAuthor() {
	      if (!this.recentItem.message) {
	        return false;
	      }
	      return this.recentItem.message.senderId === im_v2_application_core.Core.getUserId();
	    },
	    isSelfChat() {
	      return this.isUser && this.user.id === im_v2_application_core.Core.getUserId();
	    },
	    isUser() {
	      return this.dialog.type === im_v2_const.ChatType.user;
	    },
	    isBot() {
	      if (this.isUser) {
	        return this.user.bot;
	      }
	      return false;
	    },
	    hasDraft() {
	      return Boolean(this.recentItem.draft.text);
	    },
	    needsBirthdayPlaceholder() {
	      if (!this.isUser) {
	        return false;
	      }
	      return this.$store.getters['recent/needsBirthdayPlaceholder'](this.recentItem.dialogId);
	    }
	  },
	  template: `
		<div class="bx-im-list-recent-item__status-icon" :class="'--' + statusIcon"></div>
	`
	};

	const NEW_USER_POPUP_ID = 'im-new-user-popup';

	// @vue/component
	const RecentItem = {
	  name: 'RecentItem',
	  components: {
	    Avatar: im_v2_component_elements.Avatar,
	    ChatTitle: im_v2_component_elements.ChatTitle,
	    NewUserPopup,
	    MessageText,
	    MessageStatus
	  },
	  props: {
	    item: {
	      type: Object,
	      required: true
	    },
	    compactMode: {
	      type: Boolean,
	      default: false
	    },
	    isVisibleOnScreen: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data() {
	    return {
	      showNewUserPopup: false
	    };
	  },
	  computed: {
	    AvatarSize: () => im_v2_component_elements.AvatarSize,
	    recentItem() {
	      return this.item;
	    },
	    formattedDate() {
	      if (this.needsBirthdayPlaceholder) {
	        return this.$Bitrix.Loc.getMessage('IM_LIST_RECENT_BIRTHDAY_DATE');
	      }
	      return this.formatDate(this.recentItem.message.date);
	    },
	    formattedCounter() {
	      return this.dialog.counter > 99 ? '99+' : this.dialog.counter.toString();
	    },
	    user() {
	      return this.$store.getters['users/get'](this.recentItem.dialogId, true);
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.recentItem.dialogId, true);
	    },
	    layout() {
	      return this.$store.getters['application/getLayout'];
	    },
	    isUser() {
	      return this.dialog.type === im_v2_const.ChatType.user;
	    },
	    isChat() {
	      return !this.isUser;
	    },
	    isSelfChat() {
	      return this.isUser && this.user.id === im_v2_application_core.Core.getUserId();
	    },
	    isChatSelected() {
	      if (this.layout.name !== im_v2_const.Layout.chat.name) {
	        return false;
	      }
	      return this.layout.entityId === this.recentItem.dialogId;
	    },
	    isChatMuted() {
	      if (this.isUser) {
	        return false;
	      }
	      const isMuted = this.dialog.muteList.find(element => {
	        return element === im_v2_application_core.Core.getUserId();
	      });
	      return !!isMuted;
	    },
	    isSomeoneTyping() {
	      return this.dialog.writingList.length > 0;
	    },
	    needsBirthdayPlaceholder() {
	      if (!this.isUser) {
	        return false;
	      }
	      return this.$store.getters['recent/needsBirthdayPlaceholder'](this.recentItem.dialogId);
	    },
	    showBirthdays() {
	      return this.$store.getters['application/settings/get'](im_v2_const.Settings.recent.showBirthday);
	    },
	    showLastMessage() {
	      return this.$store.getters['application/settings/get'](im_v2_const.Settings.recent.showLastMessage);
	    },
	    showCounterContainer() {
	      return !this.needsBirthdayPlaceholder && !this.invitation.isActive;
	    },
	    showPinnedIcon() {
	      return this.recentItem.pinned && this.dialog.counter === 0 && !this.recentItem.unread;
	    },
	    showUnreadWithoutCounter() {
	      return this.recentItem.unread && this.dialog.counter === 0;
	    },
	    showUnreadWithCounter() {
	      return this.recentItem.unread && this.dialog.counter > 0;
	    },
	    showCounter() {
	      return !this.recentItem.unread && this.dialog.counter > 0 && !this.isSelfChat;
	    },
	    invitation() {
	      return this.recentItem.invitation;
	    },
	    newUserPopupContainer() {
	      return `#popup-window-content-${NEW_USER_POPUP_ID}-${this.recentItem.dialogId}`;
	    },
	    wrapClasses() {
	      return {
	        '--pinned': this.recentItem.pinned,
	        '--selected': !this.compactMode && this.isChatSelected
	      };
	    },
	    itemClasses() {
	      return {
	        '--no-text': !this.showLastMessage
	      };
	    },
	    compactItemClasses() {
	      return {
	        '--no-counter': this.dialog.counter === 0
	      };
	    }
	  },
	  watch: {
	    invitation(newValue, oldValue) {
	      if (!this.compactMode) {
	        return false;
	      }

	      // invitation accepted, user logged in
	      if (oldValue.isActive === true && newValue.isActive === false) {
	        this.openNewUserPopup();
	      }
	    }
	  },
	  methods: {
	    openNewUserPopup() {
	      if (!this.isVisibleOnScreen || im_v2_lib_slider.MessengerSlider.getInstance().isOpened()) {
	        return false;
	      }
	      this.newUserPopup = this.getNewUserPopup();
	      this.newUserPopup.show();
	      this.showNewUserPopup = true;
	      this.$nextTick(() => {
	        this.newUserPopup.setOffset({
	          offsetTop: -this.newUserPopup.popupContainer.offsetHeight + 1,
	          offsetLeft: -this.newUserPopup.popupContainer.offsetWidth + 13
	        });
	        this.newUserPopup.adjustPosition();
	      });
	    },
	    getNewUserPopup() {
	      return main_popup.PopupManager.create({
	        id: `${NEW_USER_POPUP_ID}-${this.recentItem.dialogId}`,
	        bindElement: this.$refs.container,
	        bindOptions: {
	          forceBindPosition: true
	        },
	        className: `bx-${NEW_USER_POPUP_ID}`,
	        cacheable: false,
	        animation: {
	          showClassName: 'bx-im-new-user-popup__animation_show',
	          closeClassName: 'bx-im-new-user-popup__animation_hide',
	          closeAnimationType: 'animation'
	        }
	      });
	    },
	    onNewUserPopupClick() {
	      im_public.Messenger.openChat(this.recentItem.dialogId);
	    },
	    onNewUserPopupClose() {
	      this.newUserPopup.close();
	      this.newUserPopup = null;
	      this.showNewUserPopup = false;
	    },
	    formatDate(date) {
	      return im_v2_lib_dateFormatter.DateFormatter.formatByTemplate(date, im_v2_lib_dateFormatter.DateTemplate.recent);
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  // language=Vue
	  template: `
		<div :data-id="recentItem.dialogId" :class="wrapClasses" class="bx-im-list-recent-item__wrap">
			<div v-if="!compactMode" :class="itemClasses" class="bx-im-list-recent-item__container">
				<div class="bx-im-list-recent-item__avatar_container">
					<div v-if="invitation.isActive" class="bx-im-list-recent-item__avatar_invitation"></div>
					<div v-else class="bx-im-list-recent-item__avatar_content">
						<Avatar :dialogId="recentItem.dialogId" :size="AvatarSize.XL" :withStatus="!isSomeoneTyping" :withSpecialTypeIcon="!isSomeoneTyping" />
						<div v-if="isSomeoneTyping" class="bx-im-list-recent-item__avatar_typing"></div>
					</div>
				</div>
				<div class="bx-im-list-recent-item__content_container">
					<div class="bx-im-list-recent-item__content_header">
						<ChatTitle :dialogId="recentItem.dialogId" :withMute="true" />
						<div class="bx-im-list-recent-item__date">
							<MessageStatus :item="item" />
							<span>{{ formattedDate }}</span>
						</div>
					</div>
					<div class="bx-im-list-recent-item__content_bottom">
						<MessageText :item="recentItem" />
						<div v-if="showCounterContainer" :class="{'--extended': dialog.counter > 99, '--withUnread': recentItem.unread}" class="bx-im-list-recent-item__counter_wrap">
							<div class="bx-im-list-recent-item__counter_container">
								<div v-if="showPinnedIcon" class="bx-im-list-recent-item__pinned-icon"></div>
								<div v-else-if="showUnreadWithoutCounter" :class="{'--muted': isChatMuted}"  class="bx-im-list-recent-item__counter_number --no-counter"></div>
								<div v-else-if="showUnreadWithCounter" :class="{'--muted': isChatMuted}"  class="bx-im-list-recent-item__counter_number --with-counter">
									{{ formattedCounter }}
								</div>
								<div v-else-if="showCounter" :class="{'--muted': isChatMuted}" class="bx-im-list-recent-item__counter_number">
									{{ formattedCounter }}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div v-if="compactMode" :class="compactItemClasses" class="bx-im-list-recent-item__container" ref="container">
				<div class="bx-im-list-recent-item__avatar_container">
					<div v-if="invitation.isActive" class="bx-im-list-recent-item__avatar_invitation"></div>
					<Avatar v-else :dialogId="recentItem.dialogId" :size="AvatarSize.M" :withStatus="false" :withSpecialTypes="false" />
					<div v-if="dialog.counter > 0" :class="{'--muted': isChatMuted}" class="bx-im-list-recent-item__avatar_counter">
						{{ formattedCounter }}
					</div>
				</div>
				<Teleport v-if="showNewUserPopup" :to="newUserPopupContainer">
					<NewUserPopup
						:title="dialog.name"
						:text="loc('IM_LIST_RECENT_NEW_USER_POPUP_TEXT')"
						@click="onNewUserPopupClick"
						@close="onNewUserPopupClose"
					/>
				</Teleport>
			</div>
		</div>
	`
	};

	// @vue/component
	const ActiveCall = {
	  name: 'ActiveCall',
	  components: {
	    Avatar: im_v2_component_elements.Avatar,
	    ChatTitle: im_v2_component_elements.ChatTitle,
	    MessengerButton: im_v2_component_elements.Button
	  },
	  props: {
	    item: {
	      type: Object,
	      required: true
	    },
	    compactMode: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['click'],
	  computed: {
	    RecentCallStatus: () => im_v2_const.RecentCallStatus,
	    AvatarSize: () => im_v2_component_elements.AvatarSize,
	    ButtonSize: () => im_v2_component_elements.ButtonSize,
	    ButtonColor: () => im_v2_component_elements.ButtonColor,
	    ButtonIcon: () => im_v2_component_elements.ButtonIcon,
	    activeCall() {
	      return this.item;
	    },
	    preparedName() {
	      return main_core.Text.decode(this.activeCall.name);
	    },
	    anotherDeviceColorScheme() {
	      return {
	        backgroundColor: 'transparent',
	        borderColor: '#bbde4d',
	        iconColor: '#525c69',
	        textColor: '#525c69',
	        hoverColor: 'transparent'
	      };
	    },
	    isTabWithActiveCall() {
	      return this.$store.getters['recent/calls/hasActiveCall']() && !!this.getCallManager().hasCurrentCall();
	    },
	    hasJoined() {
	      return this.activeCall.state === im_v2_const.RecentCallStatus.joined;
	    }
	  },
	  methods: {
	    onJoinClick() {
	      this.getCallManager().joinCall(this.activeCall.call.id);
	    },
	    onLeaveCallClick() {
	      this.getCallManager().leaveCurrentCall();
	    },
	    onClick(event) {
	      const recentItem = this.$store.getters['recent/get'](this.activeCall.dialogId);
	      if (!recentItem) {
	        return;
	      }
	      this.$emit('click', {
	        item: recentItem,
	        $event: event
	      });
	    },
	    returnToCall() {
	      if (this.activeCall.state !== im_v2_const.RecentCallStatus.joined) {
	        return;
	      }
	      this.getCallManager().unfoldCurrentCall();
	    },
	    getCallManager() {
	      return im_v2_lib_call.CallManager.getInstance();
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div :data-id="activeCall.dialogId" class="bx-im-list-recent-item__wrap">
			<div v-if="!compactMode" @click="onClick" class="bx-im-list-recent-item__container bx-im-list-recent-active-call__container">
				<div class="bx-im-list-recent-item__avatar_container">
					<Avatar :dialogId="activeCall.dialogId" :size="AvatarSize.XL" />
				</div>
				<div class="bx-im-list-recent-item__content_container">
					<div class="bx-im-list-recent-active-call__title_container">
						<ChatTitle :text="preparedName" />
						<div class="bx-im-list-recent-active-call__title_icon"></div>
					</div>
					<div v-if="!hasJoined" class="bx-im-list-recent-active-call__actions_container">
						<div class="bx-im-list-recent-active-call__actions_item --join">
							<MessengerButton @click.stop="onJoinClick" :size="ButtonSize.M" :color="ButtonColor.Success" :isRounded="true" :text="loc('IM_LIST_RECENT_ACTIVE_CALL_JOIN')" />
						</div>
					</div>
					<div v-else-if="hasJoined && isTabWithActiveCall" class="bx-im-list-recent-active-call__actions_container">
						<div class="bx-im-list-recent-active-call__actions_item --return">
							<MessengerButton @click.stop="returnToCall" :size="ButtonSize.M" :color="ButtonColor.Success" :isRounded="true" :text="loc('IM_LIST_RECENT_ACTIVE_CALL_RETURN')" />
						</div>
					</div>
					<div v-else-if="hasJoined && !isTabWithActiveCall" class="bx-im-list-recent-active-call__actions_container">
						<div class="bx-im-list-recent-active-call__actions_item --another-device">
							<MessengerButton :size="ButtonSize.M" :customColorScheme="anotherDeviceColorScheme" :isRounded="true" :text="loc('IM_LIST_RECENT_ACTIVE_CALL_ANOTHER_DEVICE')" />
						</div>
					</div>
				</div>
			</div>
			<div v-if="compactMode" @click="onClick" class="bx-im-list-recent-item__container bx-im-list-recent-active-call__container">
				<div class="bx-im-list-recent-item__avatar_container">
					<Avatar :dialogId="activeCall.dialogId" :size="AvatarSize.M" :withStatus="false" :withSpecialTypes="false" />
					<div class="bx-im-list-recent-active-call__icon" :class="'--' + activeCall.state"></div>
				</div>
			</div>
		</div>
	`
	};

	const DefaultTitleByChatType = {
	  [im_v2_const.ChatType.chat]: main_core.Loc.getMessage('IM_LIST_RECENT_CREATE_CHAT_DEFAULT_TITLE'),
	  [im_v2_const.ChatType.videoconf]: main_core.Loc.getMessage('IM_LIST_RECENT_CREATE_CONFERENCE_DEFAULT_TITLE')
	};

	// @vue/component
	const CreateChat = {
	  data() {
	    return {
	      chatTitle: '',
	      chatAvatarFile: '',
	      chatType: ''
	    };
	  },
	  computed: {
	    chatCreationIsOpened() {
	      const {
	        name: currentLayoutName
	      } = this.$store.getters['application/getLayout'];
	      return currentLayoutName === im_v2_const.Layout.createChat.name;
	    },
	    preparedTitle() {
	      if (this.chatTitle === '') {
	        return DefaultTitleByChatType[this.chatType];
	      }
	      return this.chatTitle;
	    },
	    preparedAvatar() {
	      if (!this.chatAvatarFile) {
	        return null;
	      }
	      return URL.createObjectURL(this.chatAvatarFile);
	    }
	  },
	  created() {
	    const existingTitle = im_v2_lib_createChat.CreateChatManager.getInstance().getChatTitle();
	    if (existingTitle) {
	      this.chatTitle = existingTitle;
	    }
	    const existingAvatar = im_v2_lib_createChat.CreateChatManager.getInstance().getChatAvatar();
	    if (existingAvatar) {
	      this.chatAvatarFile = existingAvatar;
	    }
	    this.chatType = im_v2_lib_createChat.CreateChatManager.getInstance().getChatType();
	    im_v2_lib_createChat.CreateChatManager.getInstance().subscribe(im_v2_lib_createChat.CreateChatManager.events.titleChange, event => {
	      this.chatTitle = event.getData();
	    });
	    im_v2_lib_createChat.CreateChatManager.getInstance().subscribe(im_v2_lib_createChat.CreateChatManager.events.avatarChange, event => {
	      this.chatAvatarFile = event.getData();
	    });
	    im_v2_lib_createChat.CreateChatManager.getInstance().subscribe(im_v2_lib_createChat.CreateChatManager.events.chatTypeChange, event => {
	      this.chatType = event.getData();
	    });
	  },
	  methods: {
	    onClick() {
	      void im_v2_lib_layout.LayoutManager.getInstance().setLayout({
	        name: im_v2_const.Layout.createChat.name,
	        entityId: this.chatType
	      });
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div class="bx-im-list-recent-create-chat__container">
			<div class="bx-im-list-recent-item__wrap" :class="{'--selected': chatCreationIsOpened}" @click="onClick">
				<div class="bx-im-list-recent-item__container">
					<div class="bx-im-list-recent-item__avatar_container">
						<div v-if="!preparedAvatar" class="bx-im-list-recent-create-chat__avatar --default"></div>
						<img v-else class="bx-im-list-recent-create-chat__avatar --image" :src="preparedAvatar" :alt="chatTitle" />
					</div>
					<div class="bx-im-list-recent-item__content_container">
						<div class="bx-im-list-recent-item__content_header">
							<div class="bx-im-list-recent-create-chat__header">
								{{ preparedTitle }}
							</div>
						</div>
						<div class="bx-im-list-recent-item__content_bottom">
							<div class="bx-im-list-recent-item__message_container">
								{{ loc('IM_LIST_RECENT_CREATE_CHAT_SUBTITLE') }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const EmptyState = {
	  name: 'EmptyState',
	  components: {
	    MessengerButton: im_v2_component_elements.Button
	  },
	  props: {
	    compactMode: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  computed: {
	    ButtonSize: () => im_v2_component_elements.ButtonSize,
	    ButtonColor: () => im_v2_component_elements.ButtonColor,
	    inviteUsersLink() {
	      const AJAX_PATH = '/bitrix/services/main/ajax.php';
	      const COMPONENT_NAME = 'bitrix:intranet.invitation';
	      const ACTION_NAME = 'getSliderContent';
	      const params = new URLSearchParams({
	        action: ACTION_NAME,
	        site_id: im_v2_application_core.Core.getSiteId(),
	        c: COMPONENT_NAME,
	        mode: 'ajax'
	      });
	      return `${AJAX_PATH}?${params.toString()}`;
	    }
	  },
	  methods: {
	    onInviteUsersClick() {
	      BX.SidePanel.Instance.open(this.inviteUsersLink);
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<div v-if="!compactMode" class="bx-im-list-recent-empty-state__container">
			<div class="bx-im-list-recent-empty-state__image"></div>
			<div class="bx-im-list-recent-empty-state__title">{{ loc('IM_LIST_RECENT_EMPTY_STATE_TITLE') }}</div>
			<div class="bx-im-list-recent-empty-state__subtitle">{{ loc('IM_LIST_RECENT_EMPTY_STATE_SUBTITLE') }}</div>
			<div class="bx-im-list-recent-empty-state__button">
				<MessengerButton
					:size="ButtonSize.L"
					:isRounded="true"
					:text="loc('IM_LIST_RECENT_EMPTY_STATE_INVITE_USERS')"
					@click="onInviteUsersClick"
				/>
			</div>
		</div>
		<div v-else class="bx-im-list-recent__empty">
			{{ loc('IM_LIST_RECENT_EMPTY') }}
		</div>
	`
	};

	class BroadcastManager extends main_core_events.EventEmitter {
	  static getInstance() {
	    if (!this.instance) {
	      this.instance = new this();
	    }
	    return this.instance;
	  }
	  constructor() {
	    super();
	    this.setEventNamespace(BroadcastManager.eventNamespace);
	    this.init();
	  }
	  isSupported() {
	    return !main_core.Type.isUndefined(window.BroadcastChannel) && !im_v2_lib_utils.Utils.platform.isBitrixDesktop();
	  }
	  init() {
	    if (!this.isSupported()) {
	      return;
	    }
	    this.channel = new BroadcastChannel(BroadcastManager.channelName);
	    this.channel.addEventListener('message', ({
	      data: {
	        type,
	        data
	      }
	    }) => {
	      this.emit(type, data);
	    });
	  }
	  sendRecentList(recentData) {
	    if (!this.isSupported()) {
	      return;
	    }
	    this.channel.postMessage({
	      type: BroadcastManager.events.recentListUpdate,
	      data: recentData
	    });
	  }
	}
	BroadcastManager.instance = null;
	BroadcastManager.channelName = 'im-recent';
	BroadcastManager.eventNamespace = 'BX.Messenger.v2.Recent.BroadcastManager';
	BroadcastManager.events = {
	  recentListUpdate: 'recentListUpdate'
	};

	class LikeManager {
	  constructor() {
	    this.store = im_v2_application_core.Core.getStore();
	  }
	  init() {
	    this.onDialogInitedHandler = this.onDialogInited.bind(this);
	    main_core_events.EventEmitter.subscribe(im_v2_const.EventType.dialog.onDialogInited, this.onDialogInitedHandler);
	  }
	  destroy() {
	    main_core_events.EventEmitter.unsubscribe(im_v2_const.EventType.dialog.onDialogInited, this.onDialogInitedHandler);
	  }
	  onDialogInited(event) {
	    const {
	      dialogId
	    } = event.getData();
	    const recentItem = this.store.getters['recent/get'](dialogId);
	    if (!recentItem || !recentItem.liked) {
	      return;
	    }
	    this.store.dispatch('recent/like', {
	      id: dialogId,
	      liked: false
	    });
	  }
	}

	// @vue/component
	const RecentList = {
	  name: 'RecentList',
	  components: {
	    LoadingState: im_v2_component_elements.RecentLoadingState,
	    RecentItem,
	    ActiveCall,
	    CreateChat,
	    EmptyState
	  },
	  directives: {
	    'recent-list-observer': {
	      mounted(element, binding) {
	        binding.instance.observer.observe(element);
	      }
	    }
	  },
	  props: {
	    compactMode: {
	      type: Boolean,
	      default: false
	    },
	    recentService: {
	      type: Object,
	      required: false,
	      default() {
	        return null;
	      }
	    }
	  },
	  emits: ['chatClick'],
	  data() {
	    return {
	      isLoading: false,
	      visibleElements: new Set(),
	      listIsScrolled: false,
	      isCreatingChat: false
	    };
	  },
	  computed: {
	    collection() {
	      return this.getRecentService().getCollection();
	    },
	    preparedItems() {
	      const filteredCollection = this.collection.filter(item => {
	        if (!this.showBirthdays && item.options.birthdayPlaceholder) {
	          return false;
	        }
	        const dialog = this.$store.getters['chats/get'](item.dialogId, true);
	        const isUser = dialog.type === im_v2_const.ChatType.user;
	        const hasBirthday = isUser && this.showBirthdays && this.$store.getters['users/hasBirthday'](item.dialogId);
	        const isInvited = item.options.defaultUserRecord === true;
	        const needToShowInvited = this.showInvited || hasBirthday;
	        if (isInvited && !needToShowInvited) {
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
	    activeCalls() {
	      return this.$store.getters['recent/calls/get'];
	    },
	    pinnedItems() {
	      return this.preparedItems.filter(item => {
	        return item.pinned === true;
	      });
	    },
	    generalItems() {
	      return this.preparedItems.filter(item => {
	        return item.pinned === false;
	      });
	    },
	    showBirthdays() {
	      return this.$store.getters['application/settings/get'](im_v2_const.Settings.recent.showBirthday);
	    },
	    showInvited() {
	      return this.$store.getters['application/settings/get'](im_v2_const.Settings.recent.showInvited);
	    },
	    containerClasses() {
	      return {
	        '--compact': this.compactMode
	      };
	    }
	  },
	  created() {
	    var _this$recentService;
	    this.service = (_this$recentService = this.recentService) != null ? _this$recentService : im_v2_provider_service.RecentService.getInstance();
	    this.contextMenuManager = new im_v2_lib_menu.RecentMenu();
	    this.initBroadcastManager();
	    this.initLikeManager();
	    this.initObserver();
	    this.initBirthdayCheck();
	    this.initCreateChatManager();
	    this.managePreloadedList();
	    this.isLoading = true;
	    const ignorePreloadedItems = !this.compactMode;
	    // eslint-disable-next-line promise/catch-or-return
	    this.getRecentService().loadFirstPage({
	      ignorePreloadedItems
	    }).then(() => {
	      this.isLoading = false;
	      im_v2_lib_draft.DraftManager.getInstance().initDraftHistory();
	    });
	  },
	  beforeUnmount() {
	    this.contextMenuManager.destroy();
	    this.clearBirthdayCheck();
	    this.destroyBroadcastManager();
	    this.destroyLikeManager();
	    this.destroyCreateChatManager();
	  },
	  methods: {
	    onScroll(event) {
	      this.listIsScrolled = event.target.scrollTop > 0;
	      this.contextMenuManager.close();
	      if (!this.oneScreenRemaining(event) || !this.getRecentService().hasMoreItemsToLoad) {
	        return;
	      }
	      this.isLoading = true;
	      // eslint-disable-next-line promise/catch-or-return
	      this.getRecentService().loadNextPage().then(() => {
	        this.isLoading = false;
	      });
	    },
	    onClick(item, event) {
	      if (this.compactMode) {
	        im_public.Messenger.openChat(item.dialogId);
	        return;
	      }
	      this.$emit('chatClick', item.dialogId);
	    },
	    onRightClick(item, event) {
	      if (im_v2_lib_utils.Utils.key.isCombination(event, 'Alt+Shift')) {
	        return;
	      }
	      const context = {
	        ...item,
	        compactMode: this.compactMode
	      };
	      this.contextMenuManager.openMenu(context, event.currentTarget);
	      event.preventDefault();
	    },
	    onCallClick({
	      item,
	      $event
	    }) {
	      this.onClick(item, $event);
	    },
	    onCallRightClick({
	      item,
	      $event
	    }) {
	      this.onRightClick(item, $event);
	    },
	    oneScreenRemaining(event) {
	      const bottomPointOfVisibleContent = event.target.scrollTop + event.target.clientHeight;
	      const containerHeight = event.target.scrollHeight;
	      const oneScreenHeight = event.target.clientHeight;
	      return bottomPointOfVisibleContent >= containerHeight - oneScreenHeight;
	    },
	    initObserver() {
	      this.observer = new IntersectionObserver(entries => {
	        entries.forEach(entry => {
	          if (entry.isIntersecting && entry.intersectionRatio === 1) {
	            this.visibleElements.add(entry.target.dataset.id);
	          } else if (!entry.isIntersecting) {
	            this.visibleElements.delete(entry.target.dataset.id);
	          }
	        });
	      }, {
	        threshold: [0, 1]
	      });
	    },
	    initBroadcastManager() {
	      this.onRecentListUpdate = event => {
	        this.getRecentService().setPreloadedData(event.data);
	      };
	      this.broadcastManager = BroadcastManager.getInstance();
	      this.broadcastManager.subscribe(BroadcastManager.events.recentListUpdate, this.onRecentListUpdate);
	    },
	    destroyBroadcastManager() {
	      this.broadcastManager = BroadcastManager.getInstance();
	      this.broadcastManager.unsubscribe(BroadcastManager.events.recentListUpdate, this.onRecentListUpdate);
	    },
	    initLikeManager() {
	      this.likeManager = new LikeManager();
	      this.likeManager.init();
	    },
	    destroyLikeManager() {
	      this.likeManager.destroy();
	    },
	    initBirthdayCheck() {
	      const fourHours = 60000 * 60 * 4;
	      const day = 60000 * 60 * 24;
	      this.birthdayCheckTimeout = setTimeout(() => {
	        this.getRecentService().loadFirstPage();
	        this.birthdayCheckInterval = setInterval(() => {
	          this.getRecentService().loadFirstPage();
	        }, day);
	      }, im_v2_lib_utils.Utils.date.getTimeToNextMidnight() + fourHours);
	    },
	    clearBirthdayCheck() {
	      clearTimeout(this.birthdayCheckTimeout);
	      clearInterval(this.birthdayCheckInterval);
	    },
	    initCreateChatManager() {
	      if (im_v2_lib_createChat.CreateChatManager.getInstance().isCreating()) {
	        this.isCreatingChat = true;
	      }
	      this.onCreationStatusChange = event => {
	        this.isCreatingChat = event.getData();
	      };
	      im_v2_lib_createChat.CreateChatManager.getInstance().subscribe(im_v2_lib_createChat.CreateChatManager.events.creationStatusChange, this.onCreationStatusChange);
	    },
	    destroyCreateChatManager() {
	      im_v2_lib_createChat.CreateChatManager.getInstance().unsubscribe(im_v2_lib_createChat.CreateChatManager.events.creationStatusChange, this.onCreationStatusChange);
	    },
	    managePreloadedList() {
	      const {
	        preloadedList
	      } = im_v2_application_core.Core.getApplicationData();
	      if (!preloadedList || !this.compactMode) {
	        return;
	      }
	      this.getRecentService().setPreloadedData(preloadedList);
	      this.broadcastManager.sendRecentList(preloadedList);
	    },
	    getRecentService() {
	      return this.service;
	    },
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
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
	`
	};

	exports.RecentList = RecentList;

}((this.BX.Messenger.v2.Component.List = this.BX.Messenger.v2.Component.List || {}),BX,BX,BX.Messenger.v2.Provider.Service,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Main,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Main,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Component.Elements,BX,BX.Messenger.v2.Lib,BX.Event,BX.Messenger.v2.Application,BX.Messenger.v2.Const));
//# sourceMappingURL=recent-list.bundle.js.map

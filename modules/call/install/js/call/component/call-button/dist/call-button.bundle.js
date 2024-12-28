/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports,main_core_events,im_v2_lib_localStorage,im_v2_lib_promo,im_public,im_v2_application_core,im_v2_const,im_v2_lib_permission,im_v2_lib_menu,im_v2_lib_call,im_v2_lib_rest,im_v2_lib_feature,call_lib_analytics,call_const,call_component_elements,main_core,call_core,ui_vue3_directives_hint) {
	'use strict';

	let _ = t => t,
	  _t;
	var _getDelimiter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDelimiter");
	var _getVideoCallItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getVideoCallItem");
	var _getAudioCallItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAudioCallItem");
	var _analyticsOnStartCallClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analyticsOnStartCallClick");
	var _getPersonalPhoneItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPersonalPhoneItem");
	var _getWorkPhoneItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWorkPhoneItem");
	var _getInnerPhoneItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getInnerPhoneItem");
	var _getZoomItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getZoomItem");
	var _getUserPhoneHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUserPhoneHtml");
	var _isCallAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCallAvailable");
	var _getUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUser");
	var _isUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUser");
	var _requestCreateZoomConference = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("requestCreateZoomConference");
	class CallMenu extends im_v2_lib_menu.BaseMenu {
	  constructor() {
	    super();
	    Object.defineProperty(this, _requestCreateZoomConference, {
	      value: _requestCreateZoomConference2
	    });
	    Object.defineProperty(this, _isUser, {
	      value: _isUser2
	    });
	    Object.defineProperty(this, _getUser, {
	      value: _getUser2
	    });
	    Object.defineProperty(this, _isCallAvailable, {
	      value: _isCallAvailable2
	    });
	    Object.defineProperty(this, _getUserPhoneHtml, {
	      value: _getUserPhoneHtml2
	    });
	    Object.defineProperty(this, _getZoomItem, {
	      value: _getZoomItem2
	    });
	    Object.defineProperty(this, _getInnerPhoneItem, {
	      value: _getInnerPhoneItem2
	    });
	    Object.defineProperty(this, _getWorkPhoneItem, {
	      value: _getWorkPhoneItem2
	    });
	    Object.defineProperty(this, _getPersonalPhoneItem, {
	      value: _getPersonalPhoneItem2
	    });
	    Object.defineProperty(this, _analyticsOnStartCallClick, {
	      value: _analyticsOnStartCallClick2
	    });
	    Object.defineProperty(this, _getAudioCallItem, {
	      value: _getAudioCallItem2
	    });
	    Object.defineProperty(this, _getVideoCallItem, {
	      value: _getVideoCallItem2
	    });
	    Object.defineProperty(this, _getDelimiter, {
	      value: _getDelimiter2
	    });
	    this.id = 'bx-im-chat-header-call-menu';
	  }
	  getMenuOptions() {
	    return {
	      ...super.getMenuOptions(),
	      className: this.getMenuClassName(),
	      angle: true,
	      offsetLeft: 4,
	      offsetTop: 5
	    };
	  }
	  getMenuClassName() {
	    return 'bx-im-messenger__scope bx-call-chat-header-call-button__scope';
	  }
	  getMenuItems() {
	    return [babelHelpers.classPrivateFieldLooseBase(this, _getVideoCallItem)[_getVideoCallItem](), babelHelpers.classPrivateFieldLooseBase(this, _getAudioCallItem)[_getAudioCallItem](), babelHelpers.classPrivateFieldLooseBase(this, _getZoomItem)[_getZoomItem](), babelHelpers.classPrivateFieldLooseBase(this, _getDelimiter)[_getDelimiter](), babelHelpers.classPrivateFieldLooseBase(this, _getPersonalPhoneItem)[_getPersonalPhoneItem](), babelHelpers.classPrivateFieldLooseBase(this, _getWorkPhoneItem)[_getWorkPhoneItem](), babelHelpers.classPrivateFieldLooseBase(this, _getInnerPhoneItem)[_getInnerPhoneItem]()];
	  }
	}
	function _getDelimiter2() {
	  return {
	    delimiter: true
	  };
	}
	function _getVideoCallItem2() {
	  const isAvailable = babelHelpers.classPrivateFieldLooseBase(this, _isCallAvailable)[_isCallAvailable](this.context.dialogId);
	  return {
	    text: main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_VIDEOCALL'),
	    onclick: () => {
	      if (!isAvailable) {
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _analyticsOnStartCallClick)[_analyticsOnStartCallClick](call_const.CallTypes.video.id);
	      call_const.CallTypes.video.start(this.context.dialogId);
	      this.emit(CallMenu.events.onMenuItemClick, call_const.CallTypes.video);
	      this.menuInstance.close();
	    },
	    disabled: !isAvailable
	  };
	}
	function _getAudioCallItem2() {
	  const isAvailable = babelHelpers.classPrivateFieldLooseBase(this, _isCallAvailable)[_isCallAvailable](this.context.dialogId);
	  return {
	    text: main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_AUDIO'),
	    onclick: () => {
	      if (!isAvailable) {
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _analyticsOnStartCallClick)[_analyticsOnStartCallClick](call_const.CallTypes.audio.id);
	      call_const.CallTypes.audio.start(this.context.dialogId);
	      this.emit(CallMenu.events.onMenuItemClick, call_const.CallTypes.audio);
	      this.menuInstance.close();
	    },
	    disabled: !isAvailable
	  };
	}
	function _analyticsOnStartCallClick2(callType) {
	  call_lib_analytics.Analytics.getInstance().onContextMenuStartCallClick({
	    context: this.context,
	    callType
	  });
	}
	function _getPersonalPhoneItem2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isUser)[_isUser]()) {
	    return null;
	  }
	  const {
	    phones
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getUser)[_getUser]();
	  if (!phones.personalMobile) {
	    return null;
	  }
	  const title = main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_PERSONAL_PHONE');
	  return {
	    className: 'menu-popup-no-icon bx-call-chat-header-call-button-menu__item',
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getUserPhoneHtml)[_getUserPhoneHtml](title, phones.personalMobile),
	    onclick: () => {
	      im_public.Messenger.startPhoneCall(phones.personalMobile);
	      this.menuInstance.close();
	    }
	  };
	}
	function _getWorkPhoneItem2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isUser)[_isUser]()) {
	    return null;
	  }
	  const {
	    phones
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getUser)[_getUser]();
	  if (!phones.workPhone) {
	    return null;
	  }
	  const title = main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_WORK_PHONE');
	  return {
	    className: 'menu-popup-no-icon bx-call-chat-header-call-button-menu__item',
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getUserPhoneHtml)[_getUserPhoneHtml](title, phones.workPhone),
	    onclick: () => {
	      im_public.Messenger.startPhoneCall(phones.workPhone);
	      this.menuInstance.close();
	    }
	  };
	}
	function _getInnerPhoneItem2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isUser)[_isUser]()) {
	    return null;
	  }
	  const {
	    phones
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getUser)[_getUser]();
	  if (!phones.innerPhone) {
	    return null;
	  }
	  const title = main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_INNER_PHONE_MSGVER_1');
	  return {
	    className: 'menu-popup-no-icon bx-call-chat-header-call-button-menu__item',
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getUserPhoneHtml)[_getUserPhoneHtml](title, phones.innerPhone),
	    onclick: () => {
	      im_public.Messenger.startPhoneCall(phones.innerPhone);
	      this.menuInstance.close();
	    }
	  };
	}
	function _getZoomItem2() {
	  const isActive = im_v2_lib_feature.FeatureManager.isFeatureAvailable(im_v2_lib_feature.Feature.zoomActive);
	  if (!isActive) {
	    return null;
	  }
	  const classNames = ['bx-call-chat-header-call-button-menu__zoom', 'menu-popup-no-icon'];
	  const isFeatureAvailable = im_v2_lib_feature.FeatureManager.isFeatureAvailable(im_v2_lib_feature.Feature.zoomAvailable);
	  if (!isFeatureAvailable) {
	    classNames.push('--disabled');
	  }
	  return {
	    className: classNames.join(' '),
	    text: main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_ZOOM'),
	    onclick: () => {
	      if (!isFeatureAvailable) {
	        BX.UI.InfoHelper.show('limit_video_conference_zoom');
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _requestCreateZoomConference)[_requestCreateZoomConference](this.context.dialogId);
	      this.menuInstance.close();
	    }
	  };
	}
	function _getUserPhoneHtml2(title, phoneNumber) {
	  return main_core.Tag.render(_t || (_t = _`
			<span class="bx-call-chat-header-call-button-menu__phone_container">
				<span class="bx-call-chat-header-call-button-menu__phone_title">${0}</span>
				<span class="bx-call-chat-header-call-button-menu__phone_number">${0}</span>
			</span>
		`), title, phoneNumber);
	}
	function _isCallAvailable2(dialogId) {
	  if (im_v2_lib_call.CallManager.getInstance().hasActiveCurrentCall(dialogId)) {
	    return true;
	  }
	  if (im_v2_lib_call.CallManager.getInstance().hasActiveAnotherCall()) {
	    return false;
	  }
	  const chatCanBeCalled = im_v2_lib_call.CallManager.getInstance().chatCanBeCalled(dialogId);
	  const chatIsAllowedToCall = im_v2_lib_permission.PermissionManager.getInstance().canPerformActionByRole(im_v2_const.ActionByRole.call, dialogId);
	  return chatCanBeCalled && chatIsAllowedToCall;
	}
	function _getUser2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isUser)[_isUser]()) {
	    return null;
	  }
	  return im_v2_application_core.Core.getStore().getters['users/get'](this.context.dialogId);
	}
	function _isUser2() {
	  return this.context.type === im_v2_const.ChatType.user;
	}
	function _requestCreateZoomConference2(dialogId) {
	  im_v2_lib_rest.runAction(im_v2_const.RestMethod.imV2CallZoomCreate, {
	    data: {
	      dialogId
	    }
	  }).catch(errors => {
	    let errorText = main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_ZOOM_CREATE_ERROR');
	    const notConnected = errors.some(error => error.code === 'ZOOM_CONNECTED_ERROR');
	    if (notConnected) {
	      const userProfileUri = `/company/personal/user/${im_v2_application_core.Core.getUserId()}/social_services/`;
	      errorText = main_core.Loc.getMessage('CALL_CONTENT_CHAT_HEADER_CALL_MENU_ZOOM_CONNECT_ERROR').replace('#HREF_START#', `<a href=${userProfileUri}>`).replace('#HREF_END#', '</>');
	    }
	    BX.UI.Notification.Center.notify({
	      content: errorText
	    });
	  });
	}
	CallMenu.events = {
	  onMenuItemClick: 'onMenuItemClick'
	};

	// @vue/component
	const CallButtonTitle = {
	  name: 'CallButtonTitle',
	  props: {
	    text: {
	      type: String,
	      required: true
	    },
	    compactMode: {
	      type: Boolean,
	      default: false
	    },
	    copilotMode: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    callButtonIconClasses() {
	      return ['bx-call-chat-header-call-button__icon', ...(this.copilotMode ? ['--copilot'] : []), ...(this.compactMode ? ['--compact'] : [])];
	    }
	  },
	  template: `
		<div :class="callButtonIconClasses"></div>
		<div v-if="!compactMode" class="bx-call-chat-header-call-button__text">
			{{ text }}
		</div>
	`
	};

	// @vue/component
	const CallButtonPromo = {
	  name: 'CallButtonPromo',
	  components: {
	    CallPopupContainer: call_component_elements.CallPopupContainer
	  },
	  emits: ['close'],
	  props: {
	    bindElement: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      isAIConcertAccepted: call_core.CallAI.agreementAccepted
	    };
	  },
	  computed: {
	    getId() {
	      return 'call-ai-user-list-popup';
	    },
	    config() {
	      return {
	        width: 380,
	        padding: 0,
	        overlay: false,
	        autoHide: false,
	        closeByEsc: false,
	        angle: {
	          offset: main_core.Dom.getPosition(this.bindElement).width / 2,
	          position: 'top'
	        },
	        closeIcon: true,
	        bindElement: this.bindElement,
	        offsetTop: 28
	      };
	    },
	    callButtonPromoText() {
	      return this.isAIConcertAccepted ? this.loc('CALL_CHAT_HEADER_BUTTON_PROMO_TEXT') : this.loc('CALL_CHAT_HEADER_BUTTON_PROMO_TEXT_WITHOUT_TOS');
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return main_core.Loc.getMessage(phraseCode, replacements);
	    }
	  },
	  template: `
		<CallPopupContainer
			:config="config"
			@close="$emit('close')"
			:id="getId"
		>
			<div class='bx-call-chat-header-call-button__promo-container'>
                <div class='bx-call-chat-header-call-button__promo-title'>
                    {{ loc('CALL_CHAT_HEADER_BUTTON_PROMO_TITLE') }}
                </div>
                <div class='bx-call-chat-header-call-button__promo-text'>
                    {{ callButtonPromoText }}
                </div>
			</div>
		</CallPopupContainer>
	`
	};

	const RING_COUNT = 3;

	// @vue/component
	const PulseAnimation = {
	  name: 'PulseAnimation',
	  props: {
	    showPulse: {
	      type: Boolean,
	      default: true
	    },
	    isConference: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    rings() {
	      if (!this.showPulse) {
	        return [];
	      }
	      return Array.from({
	        length: RING_COUNT
	      });
	    }
	  },
	  template: `
		<div class="bx-call-pulse-animation__container">
			<slot />
			<div v-for="ring in rings" class="bx-call-pulse-animation__ring" :class="{'--conference': isConference}"></div>
		</div>
	`
	};

	// @vue/component
	const CallButton = {
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    CallButtonTitle,
	    CallButtonPromo,
	    PulseAnimation
	  },
	  props: {
	    dialog: {
	      type: Object,
	      required: true
	    },
	    compactMode: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      lastCallType: '',
	      copilotMinUserLimit: call_core.CallAI.recordingMinUsers,
	      isCopilotActive: call_core.CallAI.serviceEnabled,
	      isTariffAvailable: call_core.CallAI.tariffAvailable,
	      showPromo: false,
	      showPromoTimer: null,
	      promoId: 'call:copilot-call-button:29102024:all'
	    };
	  },
	  computed: {
	    dialogId() {
	      return this.dialog.dialogId;
	    },
	    chatId() {
	      return this.dialog.chatId;
	    },
	    userCount() {
	      return this.dialog.userCounter;
	    },
	    isConference() {
	      return this.dialog.type === im_v2_const.ChatType.videoconf;
	    },
	    callButtonText() {
	      const locCode = call_const.CallTypes[this.lastCallType].locCode;
	      return this.loc(locCode);
	    },
	    hasActiveCurrentCall() {
	      return im_v2_lib_call.CallManager.getInstance().hasActiveCurrentCall(this.dialogId);
	    },
	    hasActiveAnotherCall() {
	      return im_v2_lib_call.CallManager.getInstance().hasActiveAnotherCall(this.dialogId);
	    },
	    isActive() {
	      if (this.hasActiveCurrentCall) {
	        return true;
	      }
	      if (this.hasActiveAnotherCall) {
	        return false;
	      }
	      return im_v2_lib_call.CallManager.getInstance().chatCanBeCalled(this.dialogId);
	    },
	    userLimit() {
	      return im_v2_lib_call.CallManager.getInstance().getCallUserLimit();
	    },
	    isChatUserLimitExceeded() {
	      return im_v2_lib_call.CallManager.getInstance().isChatUserLimitExceeded(this.dialogId);
	    },
	    shouldShowMenu() {
	      return this.isActive;
	    },
	    hintContent() {
	      if (!this.isChatUserLimitExceeded) {
	        return null;
	      }
	      return {
	        text: this.loc('IM_LIB_CALL_USER_LIMIT_EXCEEDED_TOOLTIP', {
	          '#USER_LIMIT#': this.userLimit
	        }),
	        popupOptions: {
	          bindOptions: {
	            position: 'bottom'
	          },
	          angle: {
	            position: 'top'
	          },
	          targetContainer: document.body,
	          offsetLeft: 63,
	          offsetTop: 0
	        }
	      };
	    },
	    isCopilotCall() {
	      return this.isCopilotActive && this.userCount >= this.copilotMinUserLimit && !this.isConference && this.isTariffAvailable;
	    },
	    callButtonContainerClasses() {
	      return ['bx-call-chat-header-call-button__scope', 'bx-call-chat-header-call-button__container', ...(this.isConference ? ['--conference'] : []), ...(this.isCopilotCall ? ['--copilot'] : []), ...(!this.isActive ? ['--disabled'] : [])];
	    },
	    canShowPromo() {
	      return this.isCopilotCall && im_v2_lib_promo.PromoManager.getInstance().needToShow(this.promoId) && !this.hasActiveCurrentCall && this.isActive;
	    }
	  },
	  created() {
	    this.lastCallType = this.getLastCallChoice();
	    this.subscribeToMenuItemClick();
	    main_core_events.EventEmitter.subscribe('BX.Call.View:onShow', this.onShowCallView);
	  },
	  mounted() {
	    this.showPromoTimer = setTimeout(() => {
	      this.showPromo = this.canShowPromo;
	    }, 20000);
	  },
	  beforeUnmount() {
	    this.clearShowPromoTimer();
	    this.showPromo = false;
	    main_core_events.EventEmitter.unsubscribe('BX.Call.View:onShow', this.onShowCallView);
	  },
	  methods: {
	    startVideoCall() {
	      if (!this.isActive) {
	        return;
	      }
	      im_public.Messenger.startVideoCall(this.dialogId);
	    },
	    subscribeToMenuItemClick() {
	      this.getCallMenu().subscribe(CallMenu.events.onMenuItemClick, event => {
	        const {
	          id: callTypeId
	        } = event.getData();
	        this.saveLastCallChoice(callTypeId);
	      });
	    },
	    onShowCallView() {
	      this.onClosePromo();
	    },
	    onButtonClick() {
	      if (!this.isActive) {
	        return;
	      }
	      if (this.isCopilotCall) {
	        this.onClosePromo();
	      }
	      call_lib_analytics.Analytics.getInstance().onChatHeaderStartCallClick({
	        dialog: this.dialog,
	        callType: this.lastCallType
	      });
	      call_const.CallTypes[this.lastCallType].start(this.dialogId);
	    },
	    onMenuClick() {
	      if (!this.shouldShowMenu) {
	        return;
	      }
	      this.getCallMenu().openMenu(this.dialog, this.$refs.menu);
	    },
	    onStartConferenceClick() {
	      if (!this.isActive) {
	        return;
	      }
	      if (this.isCopilotCall) {
	        this.onClosePromo();
	      }
	      call_lib_analytics.Analytics.getInstance().onStartConferenceClick({
	        chatId: this.chatId
	      });
	      im_public.Messenger.openConference({
	        code: this.dialog.public.code
	      });
	    },
	    getLastCallChoice() {
	      return im_v2_lib_localStorage.LocalStorageManager.getInstance().get(im_v2_const.LocalStorageKey.lastCallType, call_const.CallTypes.video.id);
	    },
	    saveLastCallChoice(callTypeId) {
	      this.lastCallType = callTypeId;
	      im_v2_lib_localStorage.LocalStorageManager.getInstance().set(im_v2_const.LocalStorageKey.lastCallType, callTypeId);
	    },
	    getCallMenu() {
	      if (!this.callMenu) {
	        this.callMenu = new CallMenu();
	      }
	      return this.callMenu;
	    },
	    loc(phraseCode, replacements = {}) {
	      return main_core.Loc.getMessage(phraseCode, replacements);
	    },
	    onClosePromo() {
	      this.clearShowPromoTimer();
	      this.showPromo = false;
	      im_v2_lib_promo.PromoManager.getInstance().markAsWatched(this.promoId);
	    },
	    clearShowPromoTimer() {
	      clearTimeout(this.showPromoTimer);
	      this.showPromoTimer = null;
	    }
	  },
	  template: `
		<PulseAnimation :showPulse="showPromo" :isConference="isConference">
			<div
				v-if="isConference"
				:class="callButtonContainerClasses"
				@click="onStartConferenceClick"
				ref="call-button"
			>
				<CallButtonTitle :compactMode="compactMode" :copilotMode="isCopilotCall" :text="loc('IM_CONTENT_CHAT_HEADER_START_CONFERENCE')" />
			</div>
			<div
				v-else
				:class="callButtonContainerClasses"
				v-hint="hintContent"
				@click="onButtonClick"
				ref="call-button"
			>
				<CallButtonTitle :compactMode="compactMode" :copilotMode="isCopilotCall" :text="callButtonText" />
				<div class="bx-call-chat-header-call-button__separator"></div>
				<div class="bx-call-chat-header-call-button__chevron_container" @click.stop="onMenuClick">
					<div class="bx-call-chat-header-call-button__chevron" ref="menu"></div>
				</div>
			</div>
			<CallButtonPromo
				v-if="showPromo"
				:bindElement="$refs['call-button']"
				@close="onClosePromo"
			/>
		</PulseAnimation>
	`
	};

	exports.CallButton = CallButton;

}((this.BX.Call.Component = this.BX.Call.Component || {}),BX.Event,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Application,BX.Messenger.v2.Const,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Call.Lib,BX.Call.Const,BX.Call.Component.Elements,BX,BX.Call,BX.Vue3.Directives));
//# sourceMappingURL=call-button.bundle.js.map

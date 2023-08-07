this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
(function (exports,immobile_chat_application_core,main_date,mobile_pull_client,im_model,im_provider_rest,im_lib_localstorage,pull_component_status,ui_fonts_opensans,ui_vue,im_component_dialog,im_view_quotepanel,ui_vue_vuex,ui_vue_components_smiles,im_lib_utils,main_core_events,im_const,im_eventHandler,im_lib_logger,im_lib_timer) {
	'use strict';

	/**
	 * Bitrix Mobile Dialog
	 * Dialog Pull commands (Pull Command Handler)
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2020 Bitrix
	 */

	var MobileImCommandHandler = /*#__PURE__*/function () {
	  babelHelpers.createClass(MobileImCommandHandler, null, [{
	    key: "create",
	    value: function create() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return new this(params);
	    }
	  }]);
	  function MobileImCommandHandler() {
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, MobileImCommandHandler);
	    this.controller = params.controller;
	    this.store = params.store;
	    this.dialog = params.dialog;
	  }
	  babelHelpers.createClass(MobileImCommandHandler, [{
	    key: "getModuleId",
	    value: function getModuleId() {
	      return 'im';
	    }
	  }, {
	    key: "handleUserInvite",
	    value: function handleUserInvite(params, extra, command) {
	      var _this = this;
	      if (!params.invited) {
	        setTimeout(function () {
	          _this.dialog.redrawHeader();
	        }, 100);
	      }
	    }
	  }, {
	    key: "handleMessage",
	    value: function handleMessage(params, extra, command) {
	      var currentHeaderName = BXMobileApp.UI.Page.TopBar.title.params.text;
	      var senderId = params.message.senderId;
	      if (params.users[senderId].name !== currentHeaderName) {
	        this.dialog.redrawHeader();
	      }
	    }
	  }, {
	    key: "handleGeneralChatAccess",
	    value: function handleGeneralChatAccess() {
	      app.closeController();
	    }
	  }, {
	    key: "handleChatUserLeave",
	    value: function handleChatUserLeave(params) {
	      if (params.userId === this.controller.application.getUserId() && params.dialogId === this.controller.application.getDialogId()) {
	        app.closeController();
	      }
	    }
	  }]);
	  return MobileImCommandHandler;
	}();

	/**
	 * Bitrix Mobile Dialog
	 * Dialog Rest answers (Rest Answer Handler)
	 *
	 * @package bitrix
	 * @subpackage mobile
	 * @copyright 2001-2019 Bitrix
	 */
	var MobileRestAnswerHandler = /*#__PURE__*/function (_BaseRestHandler) {
	  babelHelpers.inherits(MobileRestAnswerHandler, _BaseRestHandler);
	  function MobileRestAnswerHandler(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, MobileRestAnswerHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MobileRestAnswerHandler).call(this, params));
	    if (babelHelpers["typeof"](params.context) === 'object' && params.context) {
	      _this.context = params.context;
	    }
	    return _this;
	  }
	  babelHelpers.createClass(MobileRestAnswerHandler, [{
	    key: "handleImCallGetCallLimitsSuccess",
	    value: function handleImCallGetCallLimitsSuccess(data) {
	      this.store.commit('application/set', {
	        call: {
	          serverEnabled: data.callServerEnabled,
	          maxParticipants: data.maxParticipants
	        }
	      });
	    }
	  }, {
	    key: "handleImChatGetSuccess",
	    value: function handleImChatGetSuccess(data) {
	      this.store.commit('application/set', {
	        dialog: {
	          chatId: data.id,
	          dialogId: data.dialog_id,
	          diskFolderId: data.disk_folder_id
	        }
	      });
	      if (data.restrictions) {
	        this.store.dispatch('dialogues/update', {
	          dialogId: data.dialog_id,
	          fields: data.restrictions
	        });
	      }
	    }
	  }, {
	    key: "handleImChatGetError",
	    value: function handleImChatGetError(error) {
	      if (error.ex.error === 'ACCESS_ERROR') {
	        BXMobileApp.Events.postToComponent('chatdialog::access::error', [], 'im.messenger');
	        app.closeController();
	      }
	    }
	  }, {
	    key: "handleMobileBrowserConstGetSuccess",
	    value: function handleMobileBrowserConstGetSuccess(data) {
	      this.store.commit('application/set', {
	        disk: {
	          enabled: true,
	          maxFileSize: data.phpUploadMaxFilesize
	        }
	      });
	      this.context.addLocalize(data);
	      BX.message(data);
	      im_lib_localstorage.LocalStorage.set(this.controller.getSiteId(), 0, 'serverVariables', data || {});
	    }
	  }, {
	    key: "handleImDialogMessagesGetInitSuccess",
	    value: function handleImDialogMessagesGetInitSuccess() {
	      // EventEmitter.emit(EventType.dialog.readVisibleMessages, {chatId: this.controller.application.getChatId()});
	    }
	  }, {
	    key: "handleImMessageAddSuccess",
	    value: function handleImMessageAddSuccess(messageId, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }, {
	    key: "handleImMessageAddError",
	    value: function handleImMessageAddError(error, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }, {
	    key: "handleImDiskFileCommitSuccess",
	    value: function handleImDiskFileCommitSuccess(result, message) {
	      this.context.messagesQueue = this.context.messagesQueue.filter(function (el) {
	        return el.id !== message.id;
	      });
	    }
	  }]);
	  return MobileRestAnswerHandler;
	}(im_provider_rest.BaseRestHandler);

	var LoadingStatus = {
	  template: "\n\t\t<div class=\"bx-mobilechat-loading-window\">\n\t\t\t<svg class=\"bx-mobilechat-loading-circular\" viewBox=\"25 25 50 50\">\n\t\t\t\t<circle class=\"bx-mobilechat-loading-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t\t<circle class=\"bx-mobilechat-loading-inner-path\" cx=\"50\" cy=\"50\" r=\"20\" fill=\"none\" stroke-miterlimit=\"10\"/>\n\t\t\t</svg>\n\t\t\t<h3 class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_LOADING')}}</h3>\n\t\t</div>\n\t"
	};

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ErrorStatus = {
	  computed: _objectSpread({}, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    }
	  })),
	  template: "\n\t\t<div class=\"bx-mobilechat-body\" key=\"error-body\">\n\t\t\t<div class=\"bx-mobilechat-warning-window\">\n\t\t\t\t<div class=\"bx-mobilechat-warning-icon\"></div>\n\t\t\t\t<template v-if=\"application.error.description\"> \n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg\" v-html=\"application.error.description\"></div>\n\t\t\t\t</template> \n\t\t\t\t<template v-else>\n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-warning-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_ERROR_TITLE')}}</div>\n\t\t\t\t\t<div class=\"bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_ERROR_DESC')}}</div>\n\t\t\t\t</template> \n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var EmptyStatus = {
	  template: "\n\t\t<div class=\"bx-mobilechat-loading-window\">\n\t\t\t<h3 class=\"bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg\">{{$Bitrix.Loc.getMessage('MOBILE_CHAT_EMPTY')}}</h3>\n\t\t</div>\n\t"
	};

	var MobileSmiles = {
	  methods: {
	    onSelectSmile: function onSelectSmile(event) {
	      this.$emit('selectSmile', event);
	    },
	    onSelectSet: function onSelectSet(event) {
	      this.$emit('selectSet', event);
	    },
	    hideSmiles: function hideSmiles() {
	      this.$emit('hideSmiles');
	    }
	  },
	  template: "\n\t\t<transition enter-active-class=\"bx-livechat-consent-window-show\" leave-active-class=\"bx-livechat-form-close\">\n\t\t\t<div class=\"bx-messenger-alert-box bx-livechat-alert-box-zero-padding bx-livechat-form-show\" key=\"vote\">\n\t\t\t\t<div class=\"bx-livechat-alert-close\" @click=\"hideSmiles\"></div>\n\t\t\t\t<div class=\"bx-messenger-smiles-box\">\n\t\t\t\t\t<bx-smiles\n\t\t\t\t\t\t@selectSmile=\"onSelectSmile\"\n\t\t\t\t\t\t@selectSet=\"onSelectSet\"\n\t\t\t\t\t/>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</transition>\n\t"
	};

	var MobileQuoteHandler = /*#__PURE__*/function (_QuoteHandler) {
	  babelHelpers.inherits(MobileQuoteHandler, _QuoteHandler);
	  function MobileQuoteHandler() {
	    babelHelpers.classCallCheck(this, MobileQuoteHandler);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MobileQuoteHandler).apply(this, arguments));
	  }
	  babelHelpers.createClass(MobileQuoteHandler, [{
	    key: "quoteMessage",
	    value: function quoteMessage(messageId) {
	      var _this = this;
	      this.store.dispatch('dialogues/update', {
	        dialogId: this.getDialogId(),
	        fields: {
	          quoteId: messageId
	        }
	      }).then(function () {
	        if (_this.store.state.application.mobile.keyboardShow) {
	          return;
	        }
	        main_core_events.EventEmitter.emit(im_const.EventType.mobile.textarea.setFocus);
	        setTimeout(function () {
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this.getChatId(),
	            duration: 300,
	            cancelIfScrollChange: false,
	            force: true
	          });
	        }, 300);
	      });
	    }
	  }, {
	    key: "clearQuote",
	    value: function clearQuote() {
	      main_core_events.EventEmitter.emit(im_const.EventType.mobile.textarea.setText, {
	        text: ''
	      });
	      this.store.dispatch('dialogues/update', {
	        dialogId: this.getDialogId(),
	        fields: {
	          quoteId: 0,
	          editId: 0
	        }
	      });
	    }
	  }, {
	    key: "getChatId",
	    value: function getChatId() {
	      return this.store.state.application.dialog.chatId;
	    }
	  }]);
	  return MobileQuoteHandler;
	}(im_eventHandler.QuoteHandler);

	var MobileReactionHandler = /*#__PURE__*/function (_ReactionHandler) {
	  babelHelpers.inherits(MobileReactionHandler, _ReactionHandler);
	  function MobileReactionHandler($Bitrix) {
	    var _this;
	    babelHelpers.classCallCheck(this, MobileReactionHandler);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MobileReactionHandler).call(this, $Bitrix));
	    _this.loc = $Bitrix.Loc.messages;
	    return _this;
	  }
	  babelHelpers.createClass(MobileReactionHandler, [{
	    key: "reactToMessage",
	    value: function reactToMessage(messageId, reaction) {
	      var action = reaction.action || im_eventHandler.ReactionHandler.actions.auto;
	      if (action !== im_eventHandler.ReactionHandler.actions.auto) {
	        action = action === im_eventHandler.ReactionHandler.actions.set ? im_eventHandler.ReactionHandler.actions.plus : im_eventHandler.ReactionHandler.actions.minus;
	      }
	      var eventParameters = ['reactMessage', "reactMessage|".concat(messageId), {
	        messageId: messageId,
	        action: action
	      }, false, 1000];
	      BXMobileApp.Events.postToComponent('chatbackground::task::action', eventParameters, 'background');
	      if (reaction.action === im_eventHandler.ReactionHandler.actions.set) {
	        setTimeout(function () {
	          return app.exec('callVibration');
	        }, 200);
	      }
	    }
	  }, {
	    key: "openMessageReactionList",
	    value: function openMessageReactionList(id, reactions) {
	      if (!im_lib_utils.Utils.dialog.isChatId(this.getDialogId())) {
	        return;
	      }
	      var users = [];
	      Object.keys(reactions).forEach(function (reaction) {
	        users = [].concat(babelHelpers.toConsumableArray(users), babelHelpers.toConsumableArray(reactions[reaction]));
	      });
	      main_core_events.EventEmitter.emit(im_const.EventType.mobile.openUserList, {
	        users: users,
	        title: this.loc['MOBILE_MESSAGE_LIST_LIKE']
	      });
	    }
	  }, {
	    key: "onSetMessageReaction",
	    value: function onSetMessageReaction(_ref) {
	      var data = _ref.data;
	      this.reactToMessage(data.message.id, data.reaction);
	    }
	  }, {
	    key: "getDialogId",
	    value: function getDialogId() {
	      return this.store.state.application.dialog.dialogId;
	    }
	  }]);
	  return MobileReactionHandler;
	}(im_eventHandler.ReactionHandler);

	var MobileReadingHandler = /*#__PURE__*/function (_ReadingHandler) {
	  babelHelpers.inherits(MobileReadingHandler, _ReadingHandler);
	  function MobileReadingHandler() {
	    babelHelpers.classCallCheck(this, MobileReadingHandler);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MobileReadingHandler).apply(this, arguments));
	  }
	  babelHelpers.createClass(MobileReadingHandler, [{
	    key: "onReadMessage",
	    value: function onReadMessage(_ref) {
	      var _this = this;
	      var _ref$data = _ref.data,
	        _ref$data$id = _ref$data.id,
	        id = _ref$data$id === void 0 ? null : _ref$data$id,
	        _ref$data$skipAjax = _ref$data.skipAjax,
	        skipAjax = _ref$data$skipAjax === void 0 ? false : _ref$data$skipAjax;
	      return this.readMessage(id, true, true).then(function (messageData) {
	        if (messageData.lastId <= 0 || skipAjax) {
	          return;
	        }
	        _this.addTaskToReadMessage(messageData);
	      });
	    }
	  }, {
	    key: "processMessagesToRead",
	    value: function processMessagesToRead(chatId) {
	      var _this2 = this;
	      var lastMessageToRead = this.getMaxMessageIdFromQueue(chatId);
	      var dialogId = this.getDialogId();
	      delete this.messagesToRead[chatId];
	      if (lastMessageToRead <= 0) {
	        return Promise.resolve({
	          dialogId: dialogId,
	          lastId: lastMessageToRead
	        });
	      }
	      return new Promise(function (resolve, reject) {
	        _this2.readMessageOnClient(chatId, lastMessageToRead).then(function (readResult) {
	          return _this2.decreaseChatCounter(chatId, readResult.count);
	        }).then(function () {
	          resolve({
	            dialogId: dialogId,
	            lastId: lastMessageToRead
	          });
	        })["catch"](function (error) {
	          im_lib_logger.Logger.error('Reading messages error', error);
	          reject();
	        });
	      });
	    }
	  }, {
	    key: "addTaskToReadMessage",
	    value: function addTaskToReadMessage(messageData) {
	      BXMobileApp.Events.postToComponent('chatbackground::task::action', ['readMessage', "readMessage|".concat(messageData.dialogId), messageData, false, 200], 'background');
	    }
	  }]);
	  return MobileReadingHandler;
	}(im_eventHandler.ReadingHandler);

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	/**
	 * @notice Do not clone this component! It is under development.
	 */
	ui_vue.BitrixVue.component('bx-mobile-im-component-dialog', {
	  components: {
	    LoadingStatus: LoadingStatus,
	    ErrorStatus: ErrorStatus,
	    EmptyStatus: EmptyStatus,
	    MobileSmiles: MobileSmiles
	  },
	  data: function data() {
	    return {
	      dialogState: 'none'
	    };
	  },
	  computed: _objectSpread$1({
	    EventType: function EventType() {
	      return im_const.EventType;
	    },
	    localize: function localize() {
	      return ui_vue.BitrixVue.getFilteredPhrases(['MOBILE_CHAT_', 'IM_UTILS_'], this.$root.$bitrixMessages);
	    },
	    widgetClassName: function widgetClassName() {
	      var className = [];
	      className.push('bx-mobile');
	      if (im_lib_utils.Utils.platform.isIos()) {
	        className.push('bx-mobile-ios');
	      } else {
	        className.push('bx-mobile-android');
	      }
	      return className.join(' ');
	    },
	    quotePanelData: function quotePanelData() {
	      var result = {
	        id: 0,
	        title: '',
	        description: '',
	        color: ''
	      };
	      if (!this.isMessageLoaded || !this.dialog.quoteId) {
	        return result;
	      }
	      var message = this.$store.getters['messages/getMessage'](this.dialog.chatId, this.dialog.quoteId);
	      if (!message) {
	        return result;
	      }
	      var user = this.$store.getters['users/get'](message.authorId);
	      var files = this.$store.getters['files/getList'](this.dialog.chatId);
	      var editId = this.$store.getters['dialogues/getEditId'](this.dialog.dialogId);
	      return {
	        id: this.dialog.quoteId,
	        title: editId ? this.$Bitrix.Loc.getMessage('MOBILE_CHAT_EDIT_TITLE') : message.params.NAME ? message.params.NAME : user ? user.name : '',
	        color: user ? user.color : '',
	        description: im_lib_utils.Utils.text.purify(message.text, message.params, files, this.$Bitrix.Loc.getMessages())
	      };
	    },
	    isDialog: function isDialog() {
	      return im_lib_utils.Utils.dialog.isChatId(this.dialog.dialogId);
	    },
	    isGestureQuoteSupported: function isGestureQuoteSupported() {
	      if (this.dialog && this.dialog.type === 'announcement' && !this.dialog.managerList.includes(this.application.common.userId)) {
	        return false;
	      }
	      return ChatPerformance.isGestureQuoteSupported();
	    },
	    isDarkBackground: function isDarkBackground() {
	      return this.application.options.darkBackground;
	    },
	    isMessageLoaded: function isMessageLoaded() {
	      var _this = this;
	      var timeout = ChatPerformance.getDialogShowTimeout();
	      var result = this.messageCollection && this.messageCollection.length > 0;
	      if (result) {
	        if (timeout > 0) {
	          clearTimeout(this.dialogStateTimeout);
	          this.dialogStateTimeout = setTimeout(function () {
	            _this.dialogState = 'show';
	          }, timeout);
	        } else {
	          this.dialogState = 'show';
	        }
	      } else if (this.dialog && this.dialog.init) {
	        if (timeout > 0) {
	          clearTimeout(this.dialogStateTimeout);
	          this.dialogStateTimeout = setTimeout(function () {
	            _this.dialogState = 'empty';
	          }, timeout);
	        } else {
	          this.dialogState = 'empty';
	        }
	      } else {
	        this.dialogState = 'loading';
	      }
	      return result;
	    },
	    dialog: function dialog() {
	      var dialog = this.$store.getters['dialogues/get'](this.application.dialog.dialogId);
	      return dialog || this.$store.getters['dialogues/getBlank']();
	    },
	    chatId: function chatId() {
	      if (this.application) {
	        return this.application.dialog.chatId;
	      }
	    },
	    diskFolderId: function diskFolderId() {
	      return this.application.dialog.diskFolderId;
	    },
	    isDialogShowingMessages: function isDialogShowingMessages() {
	      var messagesNotEmpty = this.messageCollection && this.messageCollection.length > 0;
	      if (messagesNotEmpty) {
	        this.dialogState = im_const.DialogState.show;
	      } else if (this.dialog && this.dialog.init) {
	        this.dialogState = im_const.DialogState.empty;
	      } else {
	        this.dialogState = im_const.DialogState.loading;
	      }
	      return messagesNotEmpty;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    },
	    messageCollection: function messageCollection(state) {
	      return state.messages.collection[state.application.dialog.chatId];
	    }
	  })),
	  created: function created() {
	    this.timer = new im_lib_timer.Timer();
	    this.initEventHandlers();
	    this.subscribeToEvents();
	  },
	  beforeDestroy: function beforeDestroy() {
	    this.unsubscribeEvents();
	    this.destroyHandlers();
	  },
	  methods: {
	    initEventHandlers: function initEventHandlers() {
	      this.quoteHandler = new MobileQuoteHandler(this.$Bitrix);
	      this.reactionHandler = new MobileReactionHandler(this.$Bitrix);
	      this.readingHandler = new MobileReadingHandler(this.$Bitrix);
	    },
	    destroyHandlers: function destroyHandlers() {
	      this.quoteHandler.destroy();
	      this.reactionHandler.destroy();
	      this.readingHandler.destroy();
	    },
	    subscribeToEvents: function subscribeToEvents() {
	      main_core_events.EventEmitter.subscribe(im_const.EventType.mobile.textarea.setText, this.onSetText);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.mobile.textarea.setFocus, this.onSetFocus);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.mobile.openUserList, this.onOpenUserList);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnUploadCancel, this.onClickOnUploadCancel);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnMessageRetry, this.onClickOnMessageRetry);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnDialog, this.onClickOnDialog);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnChatTeaser, this.onClickOnChatTeaser);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnKeyboardButton, this.onClickOnKeyboardButton);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnReadList, this.onClickOnReadList);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnMessageMenu, this.onClickOnMessageMenu);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.doubleClickOnMessage, this.onDoubleClickOnMessage);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnUserName, this.onClickOnUserName);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnMention, this.onClickOnMention);
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.clickOnCommand, this.onClickOnCommand);
	    },
	    unsubscribeEvents: function unsubscribeEvents() {
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.mobile.textarea.setText, this.onSetText);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.mobile.textarea.setFocus, this.onSetFocus);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.mobile.openUserList, this.onOpenUserList);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnUploadCancel, this.onClickOnUploadCancel);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnMessageRetry, this.onClickOnMessageRetry);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnDialog, this.onClickOnDialog);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnChatTeaser, this.onClickOnChatTeaser);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnKeyboardButton, this.onClickOnKeyboardButton);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnReadList, this.onClickOnReadList);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnMessageMenu, this.onClickOnMessageMenu);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.doubleClickOnMessage, this.onDoubleClickOnMessage);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnUserName, this.onClickOnUserName);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnMention, this.onClickOnMention);
	      main_core_events.EventEmitter.unsubscribe(im_const.EventType.dialog.clickOnCommand, this.onClickOnCommand);
	    },
	    getApplication: function getApplication() {
	      return this.$Bitrix.Application.get();
	    },
	    logEvent: function logEvent(name) {
	      for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	        params[_key - 1] = arguments[_key];
	      }
	      im_lib_logger.Logger.info.apply(im_lib_logger.Logger, [name].concat(params));
	    },
	    onDialogRequestHistory: function onDialogRequestHistory(event) {
	      this.getApplication().getDialogHistory(event.lastId);
	    },
	    onDialogRequestUnread: function onDialogRequestUnread(event) {
	      this.getApplication().getDialogUnread(event.lastId);
	    },
	    onClickOnUserName: function onClickOnUserName(_ref) {
	      var event = _ref.data;
	      this.getApplication().replyToUser(event.user.id, event.user);
	    },
	    onClickOnUploadCancel: function onClickOnUploadCancel(_ref2) {
	      var event = _ref2.data;
	      this.getApplication().cancelUploadFile(event.file.id);
	    },
	    onClickOnCommand: function onClickOnCommand(_ref3) {
	      var event = _ref3.data;
	      if (event.type === 'put') {
	        this.getApplication().insertText({
	          text: event.value + ' '
	        });
	      } else if (event.type === 'send') {
	        this.getApplication().addMessage(event.value);
	      } else {
	        im_lib_logger.Logger.warn('Unprocessed command', event);
	      }
	    },
	    onClickOnMention: function onClickOnMention(_ref4) {
	      var event = _ref4.data;
	      if (event.type === 'USER') {
	        this.getApplication().openDialog(event.value);
	      } else if (event.type === 'CHAT') {
	        this.getApplication().openDialog(event.value);
	      } else if (event.type === 'CALL') {
	        this.getApplication().openPhoneMenu(event.value);
	      }
	    },
	    onClickOnMessageMenu: function onClickOnMessageMenu(_ref5) {
	      var event = _ref5.data;
	      im_lib_logger.Logger.warn('Message menu:', event);
	      this.getApplication().openMessageMenu(event.message);
	    },
	    onClickOnMessageRetry: function onClickOnMessageRetry(_ref6) {
	      var event = _ref6.data;
	      im_lib_logger.Logger.warn('Message retry:', event);
	      this.getApplication().retrySendMessage(event.message);
	    },
	    onDoubleClickOnMessage: function onDoubleClickOnMessage(_ref7) {
	      var event = _ref7.data;
	      im_lib_logger.Logger.warn('Message double click:', event);
	      main_core_events.EventEmitter.emit('ui:reaction:press', {
	        id: 'message' + event.message.id
	      });
	    },
	    onClickOnReadList: function onClickOnReadList(_ref8) {
	      var event = _ref8.data;
	      this.getApplication().openReadedList(event.list);
	    },
	    onSetFocus: function onSetFocus() {
	      this.getApplication().setTextFocus();
	    },
	    onSetText: function onSetText(_ref9) {
	      var event = _ref9.data;
	      this.getApplication().setText(event.text);
	    },
	    onClickOnKeyboardButton: function onClickOnKeyboardButton(_ref10) {
	      var _this2 = this;
	      var event = _ref10.data;
	      if (event.action === 'ACTION') {
	        var _event$params = event.params,
	          dialogId = _event$params.dialogId,
	          messageId = _event$params.messageId,
	          botId = _event$params.botId,
	          action = _event$params.action,
	          value = _event$params.value;
	        if (action === 'SEND') {
	          this.getApplication().addMessage(value);
	          setTimeout(function () {
	            return main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	              chatId: _this2.chatId,
	              duration: 300,
	              cancelIfScrollChange: false
	            });
	          }, 300);
	        } else if (action === 'PUT') {
	          this.getApplication().insertText({
	            text: value + ' '
	          });
	        } else if (action === 'CALL') {
	          this.getApplication().openPhoneMenu(value);
	        } else if (action === 'COPY') {
	          app.exec("copyToClipboard", {
	            text: value
	          });
	          new BXMobileApp.UI.NotificationBar({
	            message: BX.message("MOBILE_MESSAGE_MENU_COPY_SUCCESS"),
	            color: "#af000000",
	            textColor: "#ffffff",
	            groupId: "clipboard",
	            maxLines: 1,
	            align: "center",
	            isGlobal: true,
	            useCloseButton: true,
	            autoHideTimeout: 1500,
	            hideOnTap: true
	          }, "copy").show();
	        } else if (action === 'DIALOG') {
	          this.getApplication().openDialog(value);
	        }
	        return true;
	      }
	      if (event.action === 'COMMAND') {
	        var _event$params2 = event.params,
	          _dialogId = _event$params2.dialogId,
	          _messageId = _event$params2.messageId,
	          _botId = _event$params2.botId,
	          command = _event$params2.command,
	          params = _event$params2.params;
	        this.$Bitrix.RestClient.get().callMethod(im_const.RestMethod.imMessageCommand, {
	          'MESSAGE_ID': _messageId,
	          'DIALOG_ID': _dialogId,
	          'BOT_ID': _botId,
	          'COMMAND': command,
	          'COMMAND_PARAMS': params
	        });
	        return true;
	      }
	      return false;
	    },
	    onClickOnChatTeaser: function onClickOnChatTeaser(_ref11) {
	      var _this3 = this;
	      var event = _ref11.data;
	      this.$Bitrix.Data.get('controller').application.joinParentChat(event.message.id, 'chat' + event.message.params.CHAT_ID).then(function (dialogId) {
	        _this3.getApplication().openDialog(dialogId);
	      })["catch"](function () {});
	    },
	    onClickOnDialog: function onClickOnDialog(_ref12) {
	      var event = _ref12.data;
	    } //this.getApplication().controller.hideSmiles();
	    ,
	    onSmilesSelectSmile: function onSmilesSelectSmile(event) {
	      console.warn('Smile selected:', event);
	      this.getApplication().insertText({
	        text: event.text
	      });
	    },
	    onSmilesSelectSet: function onSmilesSelectSet() {
	      console.warn('Set selected');
	      this.getApplication().setTextFocus();
	    },
	    onHideSmiles: function onHideSmiles() {
	      //this.getApplication().controller.hideSmiles();
	      this.getApplication().setTextFocus();
	    },
	    onOpenUserList: function onOpenUserList(_ref13) {
	      var event = _ref13.data;
	      this.getApplication().openUserList(event);
	    },
	    getController: function getController() {
	      return this.$Bitrix.Data.get('controller');
	    },
	    getApplicationController: function getApplicationController() {
	      return this.getController().application;
	    },
	    getRestClient: function getRestClient() {
	      return this.$Bitrix.RestClient.get();
	    },
	    getCurrentUser: function getCurrentUser() {
	      return this.$store.getters['users/get'](this.application.common.userId, true);
	    },
	    executeRestAnswer: function executeRestAnswer(method, queryResult, extra) {
	      this.getController().executeRestAnswer(method, queryResult, extra);
	    },
	    isUnreadMessagesLoaded: function isUnreadMessagesLoaded() {
	      if (!this.dialog) {
	        return true;
	      }
	      if (this.dialog.lastMessageId <= 0) {
	        return true;
	      }
	      if (!this.messageCollection || this.messageCollection.length <= 0) {
	        return true;
	      }
	      var lastElementId = 0;
	      for (var index = this.messageCollection.length - 1; index >= 0; index--) {
	        var lastElement = this.messageCollection[index];
	        if (typeof lastElement.id === "number") {
	          lastElementId = lastElement.id;
	          break;
	        }
	      }
	      return lastElementId >= this.dialog.lastMessageId;
	    }
	  },
	  watch: {
	    dialogState: function dialogState(state) {
	      this.getApplication().changeDialogState(state);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div :class=\"widgetClassName\" :data-message-loaded=\"isMessageLoaded\">\n\t\t\t<bx-im-component-dialog\n\t\t\t\t:userId=\"application.common.userId\"\n\t\t\t\t:dialogId=\"application.dialog.dialogId\"\n\t\t\t\t:enableReadMessages=\"application.dialog.enableReadMessages\"\n\t\t\t\t:enableReactions=\"true\"\n\t\t\t\t:enableDateActions=\"false\"\n\t\t\t\t:enableCreateContent=\"false\"\n\t\t\t\t:enableGestureQuote=\"application.options.quoteEnable\"\n\t\t\t\t:enableGestureQuoteFromRight=\"application.options.quoteFromRight\"\n\t\t\t\t:enableGestureMenu=\"true\"\n\t\t\t\t:showMessageUserName=\"isDialog\"\n\t\t\t\t:showMessageAvatar=\"isDialog\"\n\t\t\t\t:showMessageMenu=\"false\"\n\t\t\t\t:skipDataRequest=\"true\"\n\t\t\t />\n\t\t\t<template v-if=\"application.options.showSmiles\">\n\t\t\t\t<MobileSmiles @selectSmile=\"onSmilesSelectSmile\" @selectSet=\"onSmilesSelectSet\" @hideSmiles=\"onHideSmiles\" />\n\t\t\t</template>\n\t\t</div>\n\t"
	}, {
	  immutable: true
	});

	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var STORAGE_PREFIX = 'chatBackgroundQueue';
	var FILES_STORAGE_NAME = 'uploadTasks';
	var MESSAGES_STORAGE_NAME = 'tasks';
	var NO_INTERNET_CONNECTION_ERROR_CODE = -2;
	var HTTP_OK_STATUS_CODE = 200;
	var MobileDialogApplication = /*#__PURE__*/function () {
	  /* region 01. Initialize and store data */

	  function MobileDialogApplication() {
	    var _this = this;
	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, MobileDialogApplication);
	    this.inited = false;
	    this.initPromise = new BX.Promise();
	    this.params = params;
	    this.template = null;
	    this.rootNode = this.params.node || document.createElement('div');
	    this.eventBus = new ui_vue.VueVendorV2();
	    this.timer = new im_lib_timer.Timer();
	    this.messagesQueue = [];
	    this.windowFocused = true;
	    window.imDialogUploadTasks = [];
	    window.imDialogMessagesTasks = [];
	    this.messagesSet = false;

	    // alert('Pause: open console for debug');

	    this.initCore().then(function () {
	      return _this.subscribeToEvents();
	    }).then(function () {
	      return _this.initComponentParams();
	    }).then(function (result) {
	      return _this.initLangAdditional(result);
	    }).then(function (result) {
	      return _this.initMobileEntity(result);
	    }).then(function (result) {
	      return _this.initMobileSettings(result);
	    }).then(function () {
	      return _this.initComponent();
	    }).then(function () {
	      return _this.initEnvironment();
	    }).then(function () {
	      return _this.initMobileEnvironment();
	    }).then(function () {
	      return _this.initUnsentStorage();
	    }).then(function () {
	      return _this.initPullClient();
	    }).then(function () {
	      return _this.initComplete();
	    });
	  }
	  babelHelpers.createClass(MobileDialogApplication, [{
	    key: "initCore",
	    value: function initCore() {
	      var _this2 = this;
	      return new Promise(function (resolve, reject) {
	        immobile_chat_application_core.Core.ready().then(function (controller) {
	          _this2.controller = controller;
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "subscribeToEvents",
	    value: function subscribeToEvents() {
	      main_core_events.EventEmitter.subscribe(im_const.EventType.dialog.messagesSet, this.onMessagesSet.bind(this));
	    }
	  }, {
	    key: "initComponentParams",
	    value: function initComponentParams() {
	      return BX.componentParameters.init();
	    }
	  }, {
	    key: "initLangAdditional",
	    value: function initLangAdditional(data) {
	      var langAdditional = data.LANG_ADDITIONAL || {};
	      console.log('0. initLangAdditional', langAdditional);
	      return new Promise(function (resolve, reject) {
	        if (data.LANG_ADDITIONAL) {
	          Object.keys(langAdditional).forEach(function (code) {
	            if (typeof langAdditional[code] !== 'string') {
	              return;
	            }
	            BX.message[code] = langAdditional[code];
	          });
	        }
	        resolve(data);
	      });
	    }
	  }, {
	    key: "initMobileEntity",
	    value: function initMobileEntity(data) {
	      var _this3 = this;
	      console.log('1. initMobileEntity');
	      return new Promise(function (resolve, reject) {
	        if (data.DIALOG_ENTITY) {
	          data.DIALOG_ENTITY = JSON.parse(data.DIALOG_ENTITY);
	          if (data.DIALOG_TYPE === 'user') {
	            _this3.controller.getStore().dispatch('users/set', data.DIALOG_ENTITY).then(function () {
	              resolve(data);
	            });
	          } else if (data.DIALOG_TYPE === 'chat') {
	            _this3.controller.getStore().dispatch('dialogues/set', data.DIALOG_ENTITY).then(function () {
	              resolve(data);
	            });
	          }
	        } else {
	          resolve(data);
	        }
	      });
	    }
	  }, {
	    key: "initMobileSettings",
	    value: function initMobileSettings(data) {
	      var _this4 = this;
	      console.log('2. initMobileSettings');

	      // todo change to dynamic storage (LocalStorage web, PageParams for mobile)
	      var serverVariables = im_lib_localstorage.LocalStorage.get(this.controller.getSiteId(), 0, 'serverVariables', false);
	      if (serverVariables) {
	        this.addLocalize(serverVariables);
	      }
	      this.storedEvents = data.STORED_EVENTS || [];
	      return new Promise(function (resolve, reject) {
	        ApplicationStorage.getObject('settings.chat', {
	          quoteEnable: ChatPerformance.isGestureQuoteSupported(),
	          quoteFromRight: false,
	          autoplayVideo: ChatPerformance.isAutoPlayVideoSupported(),
	          backgroundType: 'LIGHT_GRAY'
	        }).then(function (options) {
	          _this4.controller.getStore().dispatch('application/set', {
	            dialog: {
	              dialogId: data.DIALOG_ID
	            },
	            options: {
	              quoteEnable: options.quoteEnable,
	              quoteFromRight: options.quoteFromRight,
	              autoplayVideo: options.autoplayVideo,
	              darkBackground: ChatDialogBackground && ChatDialogBackground[options.backgroundType] && ChatDialogBackground[options.backgroundType].dark
	            }
	          }).then(function () {
	            return resolve();
	          });
	        });
	      });
	    }
	  }, {
	    key: "initComponent",
	    value: function initComponent() {
	      var _this5 = this;
	      console.log('3. initComponent');
	      this.controller.application.setPrepareFilesBeforeSaveFunction(this.prepareFileData.bind(this));
	      this.controller.addRestAnswerHandler(MobileRestAnswerHandler.create({
	        store: this.controller.getStore(),
	        controller: this.controller,
	        context: this
	      }));
	      var dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());
	      if (dialog) {
	        this.controller.getStore().commit('application/set', {
	          dialog: {
	            chatId: dialog.chatId,
	            diskFolderId: dialog.diskFolderId || 0
	          }
	        });
	      }
	      return this.controller.createVue(this, {
	        el: this.rootNode,
	        template: '<bx-mobile-im-component-dialog/>'
	      }).then(function (vue) {
	        _this5.template = vue;
	        return new Promise(function (resolve, reject) {
	          return resolve();
	        });
	      });
	    }
	  }, {
	    key: "initEnvironment",
	    value: function initEnvironment() {
	      console.log('4. initEnvironment');
	      this.setTextareaMessage = im_lib_utils.Utils.debounce(this.controller.application.setTextareaMessage, 300, this.controller.application);
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initMobileEnvironment",
	    value: function initMobileEnvironment() {
	      var _this6 = this;
	      console.log('5. initMobileEnvironment');
	      BXMobileApp.UI.Page.Scroll.setEnabled(false);
	      BXMobileApp.UI.Page.captureKeyboardEvents(true);
	      BX.addCustomEvent('onKeyboardWillShow', function () {
	        // EventEmitter.emit(EventType.dialog.beforeMobileKeyboard);
	        _this6.controller.getStore().dispatch('application/set', {
	          mobile: {
	            keyboardShow: true
	          }
	        });
	      });
	      BX.addCustomEvent('onKeyboardDidShow', function () {
	        console.log('Keyboard was showed');
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this6.controller.application.getChatId(),
	          duration: 300,
	          cancelIfScrollChange: true
	        });
	      });
	      BX.addCustomEvent('onKeyboardWillHide', function () {
	        clearInterval(_this6.keyboardOpeningInterval);
	        _this6.controller.getStore().dispatch('application/set', {
	          mobile: {
	            keyboardShow: false
	          }
	        });
	      });
	      var checkWindowFocused = function checkWindowFocused() {
	        BXMobileApp.UI.Page.isVisible({
	          callback: function callback(data) {
	            _this6.windowFocused = data.status === 'visible';
	            if (_this6.windowFocused) {
	              ui_vue.Vue.event.$emit('bitrixmobile:controller:focus');
	            } else {
	              ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	            }
	          }
	        });
	      };
	      BXMobileApp.addCustomEvent('CallEvents::viewOpened', function () {
	        console.warn('CallView show - disable read message');
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	      });
	      BXMobileApp.addCustomEvent('CallEvents::viewClosed', function () {
	        console.warn('CallView hide - enable read message');
	        checkWindowFocused();
	      });
	      BX.addCustomEvent('onAppActive', function () {
	        checkWindowFocused();
	        BXMobileApp.UI.Page.isVisible({
	          callback: function callback(data) {
	            if (data.status !== 'visible') {
	              return false;
	            }
	            _this6.getDialogUnread().then(function () {
	              _this6.processSendMessages();
	              main_core_events.EventEmitter.emit(im_const.EventType.dialog.readVisibleMessages, {
	                chatId: _this6.controller.application.getChatId()
	              });
	              main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollOnStart, {
	                chatId: _this6.controller.application.getChatId()
	              });
	            })["catch"](function () {
	              _this6.processSendMessages();
	            });
	          }
	        });
	      });
	      BX.addCustomEvent('onAppPaused', function () {
	        _this6.windowFocused = false;
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	        // app.closeController();z
	      });

	      BX.addCustomEvent('onOpenPageAfter', checkWindowFocused);
	      BX.addCustomEvent('onHidePageBefore', function () {
	        _this6.windowFocused = false;
	        ui_vue.Vue.event.$emit('bitrixmobile:controller:blur');
	      });
	      BXMobileApp.addCustomEvent('chatbackground::task::status::success', function (params) {
	        var action = params.taskId.toString().split('|')[0];
	        _this6.executeBackgroundTaskSuccess(action, params);
	      });
	      BXMobileApp.addCustomEvent('chatbackground::task::status::failure', function (params) {
	        var action = params.taskId.toString().split('|')[0];
	        _this6.executeBackgroundTaskFailure(action, params);
	      });
	      BXMobileApp.addCustomEvent('chatrecent::push::get', function (params) {
	        mobile_pull_client.PULL.emit({
	          type: mobile_pull_client.PullClient.SubscriptionType.Server,
	          moduleId: _this6.pullMobileHandler.getModuleId(),
	          data: {
	            command: 'messageAdd',
	            params: _objectSpread$2(_objectSpread$2({}, params), {}, {
	              optionImportant: true
	            })
	          }
	        });
	      });
	      BXMobileApp.UI.Page.TextPanel.getText(function (initialText) {
	        BXMobileApp.UI.Page.TextPanel.setParams(_this6.getKeyboardParams({
	          text: initialText
	        }));
	      });
	      this.changeChatKeyboardStatus();
	      BX.MobileUploadProvider.setListener(this.executeUploaderEvent.bind(this));
	      this.fileUpdateProgress = im_lib_utils.Utils.throttle(function (chatId, fileId, progress, size) {
	        _this6.controller.getStore().dispatch('files/update', {
	          chatId: chatId,
	          id: fileId,
	          fields: {
	            status: im_const.FileStatus.upload,
	            size: size,
	            progress: progress
	          }
	        });
	      }, 500);
	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        this.chatShowUserCounter = false;
	        setTimeout(function () {
	          _this6.chatShowUserCounter = true;
	          _this6.redrawHeader();
	        }, 1500);
	      } else {
	        this.userShowWorkPosition = true;
	        setTimeout(function () {
	          _this6.userShowWorkPosition = false;
	          _this6.redrawHeader();
	        }, 1500);
	        setInterval(function () {
	          _this6.redrawHeader();
	        }, 60000);
	      }
	      this.redrawHeader();
	      this.widgetCache = new ChatWidgetCache(this.controller.getUserId(), this.controller.getLanguageId());
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initPullClient",
	    value: function initPullClient() {
	      var _this7 = this;
	      console.log('7. initPullClient');
	      if (this.storedEvents && this.storedEvents.length > 0 && this.controller.application.isUnreadMessagesLoaded()) {
	        // sort events and get first 50 (to match unread messages cache size)
	        this.storedEvents = this.storedEvents.sort(function (a, b) {
	          return a.message.id - b.message.id;
	        });
	        this.storedEvents = this.storedEvents.slice(0, 50);
	        setTimeout(function () {
	          _this7.storedEvents = _this7.storedEvents.filter(function (event) {
	            BX.onCustomEvent('chatrecent::push::get', [event]);
	            return false;
	          });
	          // scroll to first push message in dialog before load all messages from server
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this7.controller.application.getChatId(),
	            duration: 300,
	            cancelIfScrollChange: true
	          });
	        }, 50);
	      }
	      mobile_pull_client.PULL.subscribe(this.pullMobileHandler = new MobileImCommandHandler({
	        store: this.controller.getStore(),
	        controller: this.controller,
	        dialog: this
	      }));
	      mobile_pull_client.PULL.subscribe({
	        type: mobile_pull_client.PullClient.SubscriptionType.Status,
	        callback: this.eventStatusInteraction.bind(this)
	      });
	      if (!im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        mobile_pull_client.PULL.subscribe({
	          type: mobile_pull_client.PullClient.SubscriptionType.Online,
	          callback: this.eventOnlineInteraction.bind(this)
	        });
	      }
	      return new Promise(function (resolve, reject) {
	        return resolve();
	      });
	    }
	  }, {
	    key: "initComplete",
	    value: function initComplete() {
	      var _this8 = this;
	      console.log('8. initComplete');
	      this.controller.getStore().subscribe(function (mutation) {
	        return _this8.eventStoreInteraction(mutation);
	      });
	      this.inited = true;
	      this.initPromise.resolve(this);
	      BXMobileApp.Events.postToComponent('chatdialog::init::complete', [{
	        dialogId: this.controller.application.getDialogId()
	      }, true], 'im.recent');
	      BXMobileApp.Events.postToComponent('chatdialog::init::complete', [{
	        dialogId: this.controller.application.getDialogId()
	      }, true], 'im.messenger');
	      return this.requestData();
	    }
	  }, {
	    key: "cancelUnsentFile",
	    value: function cancelUnsentFile(fileId) {
	      var taskId = "imDialogFileUpload|".concat(fileId);
	      BX.MobileUploadProvider.cancelTasks([taskId]);
	      window.imDialogUploadTasks = window.imDialogUploadTasks.filter(function (entry) {
	        return taskId !== entry.taskId;
	      });
	    }
	  }, {
	    key: "initUnsentStorage",
	    value: function initUnsentStorage() {
	      var _this9 = this;
	      console.log('6. initUnsentStorage');
	      return new Promise(function (resolve) {
	        var filesPromise = _this9.loadUnsentFiles();
	        var messagesPromise = _this9.loadUnsentMessages();
	        Promise.all([filesPromise, messagesPromise]).then(resolve);
	      });
	    }
	  }, {
	    key: "loadUnsentMessages",
	    value: function loadUnsentMessages() {
	      var userId = this.controller.application.getUserId();
	      var dialogId = this.controller.application.getDialogId();
	      var storageId = "".concat(STORAGE_PREFIX, "_").concat(userId);
	      return ApplicationStorage.getObject(MESSAGES_STORAGE_NAME, {}, storageId).then(function (tasks) {
	        for (var queueType in tasks) {
	          if (queueType === dialogId) {
	            tasks[queueType].forEach(function (task) {
	              if (dialogId === task.extra.dialogId) {
	                window.imDialogMessagesTasks.push(task);
	              }
	            });
	          }
	        }
	      });
	    }
	  }, {
	    key: "loadUnsentFiles",
	    value: function loadUnsentFiles() {
	      var userId = this.controller.application.getUserId();
	      var dialogId = this.controller.application.getDialogId();
	      var storageId = "".concat(STORAGE_PREFIX, "_").concat(userId);
	      return ApplicationStorage.getObject(FILES_STORAGE_NAME, {}, storageId).then(function (result) {
	        Object.values(result).forEach(function (task) {
	          if (typeof task.eventData.file !== 'undefined' && dialogId === task.eventData.file.params.dialogId) {
	            window.imDialogUploadTasks.push(task);
	          }
	        });

	        // we need it to show a progress bar for the uploading
	        if (window.imDialogUploadTasks.length > 0) {
	          BX.MobileUploadProvider.registerTaskLoaders(window.imDialogUploadTasks);
	        }
	      });
	    }
	  }, {
	    key: "ready",
	    value: function ready() {
	      if (this.inited) {
	        var promise = new BX.Promise();
	        promise.resolve(this);
	        return promise;
	      }
	      return this.initPromise;
	    }
	  }, {
	    key: "requestData",
	    value: function requestData() {
	      var _this10 = this;
	      console.log('-> requestData');
	      if (this.requestDataSend) {
	        return this.requestDataSend;
	      }
	      this.timer.start('data', 'load', 0.5, function () {
	        console.warn('ChatDialog.requestData: slow connection show progress icon');
	        app.titleAction('setParams', {
	          useProgress: true,
	          useLetterImage: false
	        });
	      });
	      this.requestDataSend = new Promise(function (resolve, reject) {
	        var _query;
	        var query = (_query = {}, babelHelpers.defineProperty(_query, im_const.RestMethodHandler.mobileBrowserConstGet, [im_const.RestMethod.mobileBrowserConstGet, {}]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	          dialog_id: _this10.controller.application.getDialogId()
	        }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imDialogMessagesGetInit, [im_const.RestMethod.imDialogMessagesGet, {
	          dialog_id: _this10.controller.application.getDialogId(),
	          limit: _this10.controller.application.getRequestMessageLimit(),
	          convert_text: 'Y'
	        }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imRecentUnread, [im_const.RestMethod.imRecentUnread, {
	          dialog_id: _this10.controller.application.getDialogId(),
	          action: 'N'
	        }]), babelHelpers.defineProperty(_query, im_const.RestMethodHandler.imCallGetCallLimits, [im_const.RestMethod.imCallGetCallLimits, {}]), _query);
	        if (im_lib_utils.Utils.dialog.isChatId(_this10.controller.application.getDialogId())) {
	          query[im_const.RestMethodHandler.imUserGet] = [im_const.RestMethod.imUserGet, {}];
	        } else {
	          query[im_const.RestMethodHandler.imUserListGet] = [im_const.RestMethod.imUserListGet, {
	            id: [_this10.controller.application.getUserId(), _this10.controller.application.getDialogId()]
	          }];
	        }
	        _this10.controller.restClient.callBatch(query, function (response) {
	          if (!response) {
	            _this10.requestDataSend = null;
	            _this10.setError('EMPTY_RESPONSE', 'Server returned an empty response.');
	            resolve();
	            return false;
	          }
	          console.log('<-- requestData', response);
	          var constGet = response[im_const.RestMethodHandler.mobileBrowserConstGet];
	          if (constGet.error()) {
	            console.warn('Error load dialog', constGet.error().ex.error, constGet.error().ex.error_description);
	            console.warn('Try connect...');
	            setTimeout(function () {
	              return _this10.requestData();
	            }, 5000);
	          } else {
	            _this10.controller.executeRestAnswer(im_const.RestMethodHandler.mobileBrowserConstGet, constGet);
	          }
	          var callLimits = response[im_const.RestMethodHandler.imCallGetCallLimits];
	          if (callLimits && !callLimits.error()) {
	            _this10.controller.executeRestAnswer(im_const.RestMethodHandler.imCallGetCallLimits, callLimits);
	          }
	          var userGet = response[im_const.RestMethodHandler.imUserGet];
	          if (userGet && !userGet.error()) {
	            _this10.controller.executeRestAnswer(im_const.RestMethodHandler.imUserGet, userGet);
	          }
	          var userListGet = response[im_const.RestMethodHandler.imUserListGet];
	          if (userListGet && !userListGet.error()) {
	            _this10.controller.executeRestAnswer(im_const.RestMethodHandler.imUserListGet, userListGet);
	          }
	          var chatGetResult = response[im_const.RestMethodHandler.imChatGet];
	          _this10.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);
	          _this10.redrawHeader();
	          var dialogMessagesGetResult = response[im_const.RestMethodHandler.imDialogMessagesGetInit];
	          if (dialogMessagesGetResult.error()) ; else {
	            app.titleAction('setParams', {
	              useProgress: false,
	              useLetterImage: true
	            });
	            _this10.timer.stop('data', 'load', true);
	            _this10.controller.getStore().dispatch('dialogues/saveDialog', {
	              dialogId: _this10.controller.application.getDialogId(),
	              chatId: _this10.controller.application.getChatId()
	            });
	            if (_this10.controller.pullBaseHandler) {
	              _this10.controller.pullBaseHandler.option.skip = false;
	            }
	            _this10.controller.getStore().dispatch('application/set', {
	              dialog: {
	                enableReadMessages: true
	              }
	            }).then(function () {
	              _this10.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGetInit, dialogMessagesGetResult);
	            });
	            _this10.processSendMessages();
	          }
	          _this10.requestDataSend = null;
	          resolve();
	        }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	          name: 'mobile.im.dialog',
	          dialog: _this10.controller.application.getDialogData()
	        }));
	      });
	      return this.requestDataSend;
	    }
	  }, {
	    key: "executeUploaderEvent",
	    value: function executeUploaderEvent(eventName, eventData, taskId) {
	      if (eventName !== BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS) {
	        console.log('ChatDialog.disk.eventRouter:', eventName, taskId, eventData);
	      }
	      switch (eventName) {
	        case BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS:
	          {
	            if (eventData.percent > 95) {
	              eventData.percent = 95;
	            }
	            this.fileUpdateProgress(eventData.file.params.chatId, eventData.file.params.file.id, eventData.percent, eventData.byteTotal);
	            break;
	          }
	        case BX.MobileUploaderConst.FILE_CREATED:
	          {
	            if (eventData.result.status === 'error') {
	              this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	              console.error('File upload error', eventData.result.errors[0].message);
	            } else {
	              this.controller.getStore().dispatch('files/update', {
	                chatId: eventData.file.params.chatId,
	                id: eventData.file.params.file.id,
	                fields: {
	                  status: im_const.FileStatus.wait,
	                  progress: 95
	                }
	              });
	            }
	            break;
	          }
	        case 'onimdiskmessageaddsuccess':
	          {
	            console.info('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_SUCCESS:', eventData, taskId);
	            var file = eventData.result.FILES["upload".concat(eventData.result.DISK_ID[0])];
	            this.controller.getStore().dispatch('files/update', {
	              chatId: eventData.file.params.chatId,
	              id: eventData.file.params.file.id,
	              fields: {
	                status: im_const.FileStatus.upload,
	                progress: 100,
	                id: file.id,
	                size: file.size,
	                urlDownload: file.urlDownload,
	                urlPreview: file.urlPreview,
	                urlShow: file.urlShow
	              }
	            });
	            break;
	          }
	        case 'onimdiskmessageaddfail':
	          {
	            console.error('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_FAIL:', eventData, taskId);
	            this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	            break;
	          }
	        case BX.MobileUploaderConst.TASK_CANCELLED:
	        case BX.MobileUploaderConst.TASK_NOT_FOUND:
	          {
	            this.cancelUploadFile(eventData.file.params.file.id);
	            break;
	          }
	        case BX.MobileUploaderConst.FILE_CREATED_FAILED:
	        case BX.MobileUploaderConst.FILE_UPLOAD_FAILED:
	        case BX.MobileUploaderConst.FILE_READ_ERROR:
	        case BX.MobileUploaderConst.TASK_STARTED_FAILED:
	          {
	            var _eventData$error, _eventData$error$erro;
	            console.error('ChatDialog.disk.eventRouter:', eventName, eventData, taskId);
	            // show file error only if it is not internet connection error
	            if ((eventData === null || eventData === void 0 ? void 0 : (_eventData$error = eventData.error) === null || _eventData$error === void 0 ? void 0 : (_eventData$error$erro = _eventData$error.error) === null || _eventData$error$erro === void 0 ? void 0 : _eventData$error$erro.code) !== NO_INTERNET_CONNECTION_ERROR_CODE) {
	              this.fileError(eventData.file.params.chatId, eventData.file.params.file.id, eventData.file.params.id);
	            }
	            break;
	          }
	        // No default
	      }

	      return true;
	    }
	  }, {
	    key: "prepareFileData",
	    value: function prepareFileData(files) {
	      var prepareFunction = function prepareFunction(file) {
	        if (file.urlPreview && file.urlPreview.startsWith('file://')) {
	          file.urlPreview = "bx".concat(file.urlPreview);
	        }
	        if (file.urlShow && file.urlShow.startsWith('file://')) {
	          file.urlShow = "bx".concat(file.urlShow);
	        }
	        if (file.type !== im_const.FileType.image) {
	          return file;
	        }
	        if (file.urlPreview) {
	          if (file.urlPreview.startsWith('/')) {
	            file.urlPreview = currentDomain + file.urlPreview;
	          }
	          file.urlPreview = file.urlPreview.replace('http://', 'bxhttp://').replace('https://', 'bxhttps://');
	        }
	        if (file.urlShow) {
	          if (file.urlShow.startsWith('/')) {
	            file.urlShow = currentDomain + file.urlShow;
	          }
	          file.urlShow = file.urlShow.replace('http://', 'bxhttp://').replace('https://', 'bxhttps://');
	        }
	        return file;
	      };
	      return Array.isArray(files) ? files.map(function (file) {
	        return prepareFunction(file);
	      }) : prepareFunction(files);
	    }
	    /* endregion 01. Initialize and store data */
	    /* region 02. Mobile environment methods */
	  }, {
	    key: "redrawHeader",
	    value: function redrawHeader() {
	      var _this11 = this;
	      var headerProperties;
	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        headerProperties = this.getChatHeaderParams();
	        this.changeChatKeyboardStatus();
	      } else {
	        headerProperties = this.getUserHeaderParams();
	      }
	      if (!headerProperties) {
	        return false;
	      }
	      this.setHeaderButtons();
	      if (!this.headerMenuInited) {
	        BXMobileApp.UI.Page.TopBar.title.params.useLetterImage = true;
	        BXMobileApp.UI.Page.TopBar.title.setCallback(function () {
	          return _this11.openHeaderMenu();
	        });
	        this.headerMenuInited = true;
	      }
	      if (headerProperties.name) {
	        BXMobileApp.UI.Page.TopBar.title.setText(headerProperties.name);
	      }
	      if (headerProperties.desc) {
	        BXMobileApp.UI.Page.TopBar.title.setDetailText(headerProperties.desc);
	      }
	      if (headerProperties.avatar) {
	        BXMobileApp.UI.Page.TopBar.title.setImage(headerProperties.avatar);
	      } else if (headerProperties.color) {
	        // BXMobileApp.UI.Page.TopBar.title.setImageColor(dialog.color);
	        BXMobileApp.UI.Page.TopBar.title.params.imageColor = headerProperties.color;
	      }
	      return true;
	    }
	  }, {
	    key: "setIosInset",
	    value: function setIosInset() {
	      if (!im_lib_utils.Utils.platform.isIos() || Application.getApiVersion() <= 39) {
	        return false;
	      }
	      var getScrollElement = function getScrollElement() {
	        return document.getElementsByClassName('bx-im-dialog-list')[0];
	      };
	      var setTopInset = function setTopInset(scrollElement) {
	        scrollElement.style.paddingTop = "".concat(window.safeAreaInsets.top, "px");
	      };
	      var onScrollChange = function onScrollChange() {
	        var scrollElement = getScrollElement();
	        if (!scrollElement) {
	          return false;
	        }
	        if (scrollElement.scrollTop <= window.safeAreaInsets.top) {
	          setTopInset(scrollElement);
	          scrollElement.removeEventListener('scroll', onScrollChange);
	        }
	      };
	      if (this.iosInsetEventSetted) {
	        return true;
	      }
	      this.iosInsetEventSetted = true;
	      var onInsetsChanged = im_lib_utils.Utils.debounce(function () {
	        var scrollElement = getScrollElement();
	        if (!scrollElement) {
	          return false;
	        }
	        if (window.safeAreaInsets && scrollElement.scrollTop <= window.safeAreaInsets.top) {
	          setTopInset(scrollElement);
	        } else {
	          scrollElement.removeEventListener('scroll', onScrollChange);
	          scrollElement.addEventListener('scroll', onScrollChange);
	        }
	      }, 100);
	      BXMobileApp.addCustomEvent('onInsetsChanged', onInsetsChanged);
	      setTimeout(onInsetsChanged, 1000);
	      return true;
	    }
	  }, {
	    key: "getUserHeaderParams",
	    value: function getUserHeaderParams() {
	      var user = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId());
	      if (!user || !user.init) {
	        return false;
	      }
	      var result = {
	        name: null,
	        desc: null,
	        avatar: null,
	        color: null
	      };
	      if (user.avatar) {
	        result.avatar = user.avatar;
	      } else {
	        result.color = user.color;
	      }
	      result.name = user.name;
	      var showLastDate = false;
	      if (!this.userShowWorkPosition && user.lastActivityDate) {
	        showLastDate = im_lib_utils.Utils.user.getLastDateText(user, this.getLocalize());
	      }
	      if (showLastDate) {
	        result.desc = showLastDate;
	      } else if (user.extranet) {
	        result.desc = this.getLocalize('IM_LIST_EXTRANET');
	      } else if (user.workPosition) {
	        result.desc = user.workPosition;
	      } else {
	        result.desc = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_USER');
	      }
	      return result;
	    }
	  }, {
	    key: "getChatHeaderParams",
	    value: function getChatHeaderParams() {
	      var dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());
	      if (!dialog || !dialog.init) {
	        return false;
	      }
	      var result = {
	        name: null,
	        desc: null,
	        avatar: null,
	        color: null
	      };
	      if (dialog.avatar) {
	        result.avatar = dialog.avatar;
	      } else {
	        result.color = dialog.color;
	      }
	      if (dialog.entityType === 'GENERAL') {
	        result.avatar = encodeURI("".concat(this.controller.getHost(), "/bitrix/mobileapp/immobile/components/im/messenger/images/avatar_general_x3.png"));
	      }
	      result.name = dialog.name;
	      var chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_TYPE_CHAT_NEW');
	      if (this.chatShowUserCounter && this.getLocalize().MOBILE_HEADER_MENU_CHAT_USER_COUNT) {
	        chatTypeTitle = this.getLocalize('MOBILE_HEADER_MENU_CHAT_USER_COUNT').replace('#COUNT#', dialog.userCounter);
	      } else if (this.getLocalize()["MOBILE_HEADER_MENU_CHAT_TYPE_".concat(dialog.type.toUpperCase(), "_NEW")]) {
	        chatTypeTitle = this.getLocalize("MOBILE_HEADER_MENU_CHAT_TYPE_".concat(dialog.type.toUpperCase(), "_NEW"));
	      }
	      result.desc = chatTypeTitle;
	      if (dialog.entityType === 'SUPPORT24_QUESTION') {
	        result.avatar = encodeURI("".concat(this.controller.getHost(), "/bitrix/mobileapp/immobile/components/im/messenger/images/avatar_24_question_x3.png"));
	        result.desc = '';
	      }
	      console.warn(result);
	      return result;
	    }
	  }, {
	    key: "changeChatKeyboardStatus",
	    value: function changeChatKeyboardStatus() {
	      var dialog = this.controller.getStore().getters['dialogues/get'](this.controller.application.getDialogId());
	      if (!dialog || !dialog.init) {
	        BXMobileApp.UI.Page.TextPanel.show();
	        return true;
	      }
	      var keyboardShow = true;
	      if (dialog.type === 'announcement' && !dialog.managerList.includes(this.controller.application.getUserId())) {
	        keyboardShow = false;
	      } else if (dialog.restrictions.send === false) {
	        keyboardShow = false;
	      }
	      if (typeof this.keyboardShowFlag !== 'undefined' && (this.keyboardShowFlag && keyboardShow || !this.keyboardShowFlag && !keyboardShow)) {
	        return this.keyboardShowFlag;
	      }
	      if (keyboardShow) {
	        BXMobileApp.UI.Page.TextPanel.show();
	        this.keyboardShowFlag = true;
	      } else {
	        BXMobileApp.UI.Page.TextPanel.hide();
	        this.keyboardShowFlag = false;
	      }
	      return this.keyboardShowFlag;
	    }
	  }, {
	    key: "setHeaderButtons",
	    value: function setHeaderButtons() {
	      var _this12 = this;
	      if (this.callMenuSetted) {
	        return true;
	      }
	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        var dialogData = this.controller.application.getDialogData();
	        if (!dialogData.init) {
	          return false;
	        }
	        var isAvailableChatCall = Application.getApiVersion() >= 36;
	        var maxParticipants = this.controller.application.getData().call.maxParticipants;
	        if (dialogData.userCounter > maxParticipants || !isAvailableChatCall || dialogData.entityType === 'VIDEOCONF' && dialogData.entityData1 === 'BROADCAST') {
	          if (dialogData.type !== im_const.DialogType.call && dialogData.restrictions.extend) {
	            app.exec('setRightButtons', {
	              items: [{
	                type: 'user_plus',
	                callback: function callback() {
	                  fabric.Answers.sendCustomEvent('vueChatAddUserButton', {});
	                  _this12.openAddUserDialog();
	                }
	              }]
	            });
	          } else {
	            app.exec('setRightButtons', {
	              items: []
	            });
	          }
	          this.callMenuSetted = true;
	          return true;
	        }
	        if (!dialogData.restrictions.call) {
	          app.exec('setRightButtons', {
	            items: []
	          });
	          this.callMenuSetted = true;
	          return true;
	        }
	      } else {
	        var userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);
	        if (!userData.init) {
	          return false;
	        }
	        if (!userData || userData.bot || userData.network || this.controller.application.getUserId() === parseInt(this.controller.application.getDialogId())) {
	          app.exec('setRightButtons', {
	            items: []
	          });
	          this.callMenuSetted = true;
	          return true;
	        }
	      }
	      app.exec('setRightButtons', {
	        items: [{
	          type: 'call_audio',
	          callback: function callback() {
	            if (im_lib_utils.Utils.dialog.isChatId(_this12.controller.application.getDialogId())) {
	              BXMobileApp.Events.postToComponent('onCallInvite', {
	                dialogId: _this12.controller.application.getDialogId(),
	                video: false,
	                chatData: _this12.controller.application.getDialogData()
	              }, 'calls');
	            } else {
	              var _userData = _this12.controller.getStore().getters['users/get'](_this12.controller.application.getDialogId(), true);
	              BXMobileApp.Events.postToComponent('onCallInvite', {
	                userId: _this12.controller.application.getDialogId(),
	                video: false,
	                userData: babelHelpers.defineProperty({}, _userData.id, _userData)
	              }, 'calls');
	            }
	          }
	        }, {
	          type: 'call_video',
	          badgeCode: 'call_video',
	          callback: function callback() {
	            fabric.Answers.sendCustomEvent('vueChatCallVideoButton', {});
	            if (im_lib_utils.Utils.dialog.isChatId(_this12.controller.application.getDialogId())) {
	              BXMobileApp.Events.postToComponent('onCallInvite', {
	                dialogId: _this12.controller.application.getDialogId(),
	                video: true,
	                chatData: _this12.controller.application.getDialogData()
	              }, 'calls');
	            } else {
	              BXMobileApp.Events.postToComponent('onCallInvite', {
	                dialogId: _this12.controller.application.getDialogId(),
	                video: true,
	                userData: babelHelpers.defineProperty({}, _this12.controller.application.getDialogId(), _this12.controller.getStore().getters['users/get'](_this12.controller.application.getDialogId(), true))
	              }, 'calls');
	            }
	          }
	        }]
	      });
	      this.callMenuSetted = true;
	      return true;
	    }
	  }, {
	    key: "openUserList",
	    value: function openUserList() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var _params$users = params.users,
	        users = _params$users === void 0 ? false : _params$users,
	        _params$title = params.title,
	        title = _params$title === void 0 ? '' : _params$title,
	        _params$listType = params.listType,
	        listType = _params$listType === void 0 ? 'LIST' : _params$listType,
	        _params$backdrop = params.backdrop,
	        backdrop = _params$backdrop === void 0 ? true : _params$backdrop;
	      var settings = {
	        title: title,
	        objectName: 'ChatUserListInterface'
	      };
	      if (backdrop) {
	        settings.backdrop = {};
	      }
	      app.exec('openComponent', {
	        name: 'JSStackComponent',
	        componentCode: 'im.dialog.list',
	        scriptPath: "/mobileapp/jn/im:im.chat.user.list/?version=".concat(BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0')),
	        params: {
	          DIALOG_ID: this.controller.application.getDialogId(),
	          DIALOG_OWNER_ID: this.controller.application.getDialogData().ownerId,
	          USER_ID: this.controller.application.getUserId(),
	          LIST_TYPE: listType,
	          USERS: users,
	          IS_BACKDROP: true
	        },
	        rootWidget: {
	          name: 'list',
	          settings: settings
	        }
	      }, false);
	    }
	  }, {
	    key: "openCallMenu",
	    value: function openCallMenu() {
	      var _this13 = this;
	      fabric.Answers.sendCustomEvent('vueChatCallAudioButton', {});
	      var userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);
	      if (userData.phones.personalMobile || userData.phones.workPhone || userData.phones.personalPhone || userData.phones.innerPhone) {
	        BackdropMenu.create("im.dialog.menu.call|".concat(this.controller.application.getDialogId())).setItems([BackdropMenuItem.create('audio').setTitle(this.getLocalize('MOBILE_HEADER_MENU_AUDIO_CALL')), BackdropMenuItem.create('personalMobile').setTitle(userData.phones.personalMobile).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_MOBILE')).skip(!userData.phones.personalMobile), BackdropMenuItem.create('workPhone').setTitle(userData.phones.workPhone).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_WORK')).skip(!userData.phones.workPhone), BackdropMenuItem.create('personalPhone').setTitle(userData.phones.personalPhone).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_PHONE')).skip(!userData.phones.personalPhone), BackdropMenuItem.create('innerPhone').setTitle(userData.phones.innerPhone).setSubTitle(this.getLocalize('MOBILE_MENU_CALL_PHONE')).skip(!userData.phones.innerPhone)]).setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0')).setEventListener(function (name, params, user, backdrop) {
	          if (name !== 'selected') {
	            return false;
	          }
	          if (params.id === 'audio') {
	            BXMobileApp.Events.postToComponent('onCallInvite', {
	              userId: _this13.controller.application.getDialogId(),
	              video: false,
	              userData: babelHelpers.defineProperty({}, user.id, user)
	            }, 'calls');
	          } else if (params.id === 'innerPhone') {
	            BX.MobileTools.phoneTo(user.phones[params.id], {
	              callMethod: 'telephony'
	            });
	          } else {
	            BX.MobileTools.phoneTo(user.phones[params.id], {
	              callMethod: 'device'
	            });

	            // items options
	            // .setType(BackdropMenuItemType.menu)
	            // .disableClose(BX.MobileTools.canUseTelephony())
	            // if (!BX.MobileTools.canUseTelephony())
	            // {
	            // 	BX.MobileTools.phoneTo(user.phones[params.id], {callMethod: 'device'});
	            // 	return false
	            // }
	            //
	            // let subMenu = BackdropMenu
	            // 	.create('im.dialog.menu.call.submenu|'+this.controller.application.getDialogId())
	            // 	.setItems([
	            // 		BackdropMenuItem.create('number')
	            // 			.setType(BackdropMenuItemType.info)
	            // 			.setTitle(this.getLocalize("MOBILE_MENU_CALL_TO")
	            // 			.replace('#PHONE_NUMBER#', user.phones[params.id]))
	            // 			.setHeight(50)
	            // 			.setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setFontStyle('bold')))
	            // 			.setDisabled(),
	            // 		BackdropMenuItem.create('telephony').setTitle(this.getLocalize("MOBILE_CALL_BY_B24")),
	            // 		BackdropMenuItem.create('device').setTitle(this.getLocalize("MOBILE_CALL_BY_MOBILE")),
	            // 	])
	            // 	.setEventListener((name, params, options, backdrop) =>
	            // 	{
	            // 		if (name !== 'selected')
	            // 		{
	            // 			return false;
	            // 		}
	            // 		BX.MobileTools.phoneTo(options.phone, {callMethod: params.id});
	            // 	})
	            // 	.setCustomParams({phone: user.phones[params.id]})
	            // ;
	            // backdrop.showSubMenu(subMenu);
	          }
	        }).setCustomParams(userData).show();
	      } else {
	        BXMobileApp.Events.postToComponent('onCallInvite', {
	          userId: this.controller.application.getDialogId(),
	          video: false,
	          userData: babelHelpers.defineProperty({}, this.controller.application.getDialogId(), userData)
	        }, 'calls');
	      }
	    }
	  }, {
	    key: "leaveChat",
	    value: function leaveChat() {
	      var _this14 = this;
	      var confirm = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      if (!confirm) {
	        app.confirm({
	          title: this.getLocalize('MOBILE_HEADER_MENU_LEAVE_CONFIRM'),
	          text: '',
	          buttons: [this.getLocalize('MOBILE_HEADER_MENU_LEAVE_YES'), this.getLocalize('MOBILE_HEADER_MENU_LEAVE_NO')],
	          callback: function callback(button) {
	            if (button === 1) {
	              _this14.leaveChat(true);
	            }
	          }
	        });
	        return true;
	      }
	      var dialogId = this.controller.application.getDialogId();
	      this.controller.restClient.callMethod(im_const.RestMethod.imChatLeave, {
	        DIALOG_ID: dialogId
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imChatLeave,
	        dialog: this.controller.application.getDialogData(dialogId)
	      })).then(function (response) {
	        app.closeController();
	      });
	    }
	  }, {
	    key: "openAddUserDialog",
	    value: function openAddUserDialog() {
	      var listUsers = this.getItemsForAddUserDialog();
	      app.exec('openComponent', {
	        name: 'JSStackComponent',
	        componentCode: 'im.chat.user.selector',
	        scriptPath: "/mobileapp/jn/im:im.chat.user.selector/?version=".concat(BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0')),
	        params: {
	          DIALOG_ID: this.controller.application.getDialogId(),
	          USER_ID: this.controller.application.getUserId(),
	          LIST_USERS: listUsers,
	          LIST_DEPARTMENTS: [],
	          SKIP_LIST: [],
	          SEARCH_MIN_SIZE: BX.componentParameters.get('SEARCH_MIN_TOKEN_SIZE', 3)
	        },
	        rootWidget: {
	          name: 'chat.recipients',
	          settings: {
	            objectName: 'ChatUserSelectorInterface',
	            title: BX.message('MOBILE_HEADER_MENU_USER_ADD'),
	            limit: 100,
	            items: listUsers.map(function (element) {
	              return ChatDataConverter.getListElementByUser(element);
	            }),
	            scopes: [{
	              title: BX.message('MOBILE_SCOPE_USERS'),
	              id: 'user'
	            }, {
	              title: BX.message('MOBILE_SCOPE_DEPARTMENTS'),
	              id: 'department'
	            }],
	            backdrop: {
	              showOnTop: true
	            }
	          }
	        }
	      }, false);
	    }
	  }, {
	    key: "getItemsForAddUserDialog",
	    value: function getItemsForAddUserDialog() {
	      var items = [];
	      var itemsIndex = {};
	      if (this.widgetCache.recentList.length > 0) {
	        this.widgetCache.recentList.map(function (element) {
	          if (!element || itemsIndex[element.id]) {
	            return false;
	          }
	          if (element.type !== 'user') {
	            return false;
	          }
	          if (element.user.network || element.user.connector) {
	            return false;
	          }
	          items.push(element.user);
	          itemsIndex[element.id] = true;
	          return true;
	        });
	      }
	      this.widgetCache.colleaguesList.map(function (element) {
	        if (!element || itemsIndex[element.id]) {
	          return false;
	        }
	        if (element.network || element.connector) {
	          return false;
	        }
	        items.push(element);
	        itemsIndex[element.id] = true;
	      });
	      this.widgetCache.lastSearchList.map(function (element) {
	        if (!element || itemsIndex[element.id]) {
	          return false;
	        }
	        if (!element) {
	          return false;
	        }
	        if (element.type !== 'user') {
	          return false;
	        }
	        if (element.user.network || element.user.connector) {
	          return false;
	        }
	        items.push(element.user);
	        itemsIndex[element.id] = true;
	        return true;
	      });

	      /*
	      let skipList = ChatMessengerCommon.getChatUsers();
	      if (skipList.indexOf(this.base.userId) === -1)
	      {
	      	skipList.push(this.base.userId)
	      }
	      items = items.filter((element) => skipList.indexOf(element.id) === -1);
	      */

	      return items;
	    }
	    /* endregion 02. Mobile environment methods */
	    /* region 02. Push & Pull */
	  }, {
	    key: "eventStoreInteraction",
	    value: function eventStoreInteraction(data) {
	      var _this15 = this;
	      if (data.type === 'dialogues/update' && data.payload && data.payload.fields) {
	        if (data.payload.dialogId !== this.controller.application.getDialogId()) {
	          return;
	        }
	        if (typeof data.payload.fields.name !== 'undefined' || typeof data.payload.fields.userCounter !== 'undefined') {
	          if (typeof data.payload.fields.userCounter !== 'undefined') {
	            this.callMenuSetted = false;
	          }
	          this.redrawHeader();
	        }
	        if (typeof data.payload.fields.counter !== 'undefined' && typeof data.payload.dialogId !== 'undefined') {
	          BXMobileApp.Events.postToComponent('chatdialog::counter::change', [{
	            dialogId: data.payload.dialogId,
	            counter: data.payload.fields.counter
	          }, true], 'im.recent');
	          BXMobileApp.Events.postToComponent('chatdialog::counter::change', [{
	            dialogId: data.payload.dialogId,
	            counter: data.payload.fields.counter
	          }, true], 'im.messenger');
	        }
	      } else if (data.type === 'dialogues/set') {
	        data.payload.forEach(function (dialog) {
	          if (dialog.dialogId !== _this15.controller.application.getDialogId()) {
	            return;
	          }
	          BXMobileApp.Events.postToComponent('chatdialog::counter::change', [{
	            dialogId: dialog.dialogId,
	            counter: dialog.counter
	          }, true], 'im.recent');
	          BXMobileApp.Events.postToComponent('chatdialog::counter::change', [{
	            dialogId: dialog.dialogId,
	            counter: dialog.counter
	          }, true], 'im.messenger');
	        });
	      }
	    }
	  }, {
	    key: "eventStatusInteraction",
	    value: function eventStatusInteraction(data) {
	      var _this16 = this;
	      if (data.status === mobile_pull_client.PullClient.PullStatus.Online) {
	        // restart background tasks (messages and files) to resend files after we got connection again
	        if (this.messagesSet) {
	          BXMobileApp.Events.postToComponent('chatbackground::task::restart', [], 'background');
	          BXMobileApp.Events.postToComponent('chatuploader::task::restart', [], 'background');
	        }
	        if (this.pullRequestMessage) {
	          this.controller.pullBaseHandler.option.skip = true;
	          this.getDialogUnread().then(function () {
	            _this16.controller.pullBaseHandler.option.skip = false;
	            _this16.processSendMessages();
	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.readVisibleMessages, {
	              chatId: _this16.controller.application.getChatId()
	            });
	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollOnStart, {
	              chatId: _this16.controller.application.getChatId()
	            });
	          })["catch"](function () {
	            _this16.controller.pullBaseHandler.option.skip = false;
	            _this16.processSendMessages();
	          });
	          this.pullRequestMessage = false;
	        } else {
	          main_core_events.EventEmitter.emit(im_const.EventType.dialog.readMessage);
	          this.processSendMessages();
	        }
	      } else if (data.status === mobile_pull_client.PullClient.PullStatus.Offline) {
	        this.pullRequestMessage = true;
	      }
	    }
	  }, {
	    key: "eventOnlineInteraction",
	    value: function eventOnlineInteraction(data) {
	      if (data.command === 'list' || data.command === 'userStatus') {
	        for (var userId in data.params.users) {
	          if (!data.params.users.hasOwnProperty(userId)) {
	            continue;
	          }
	          this.controller.getStore().dispatch('users/update', {
	            id: data.params.users[userId].id,
	            fields: data.params.users[userId]
	          });
	          if (userId.toString() === this.controller.application.getDialogId()) {
	            this.redrawHeader();
	          }
	        }
	      }
	    } /* endregion 02. Push & Pull */
	  }, {
	    key: "getKeyboardParams",
	    value: function getKeyboardParams() {
	      var _this17 = this;
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var dialogData = this.controller.application.getDialogData();
	      var initialText = dialogData ? dialogData.textareaMessage : '';
	      initialText = initialText || params.text;
	      var siteDir = this.getLocalize('SITE_DIR');
	      return {
	        text: initialText,
	        placeholder: this.getLocalize('MOBILE_CHAT_PANEL_PLACEHOLDER'),
	        smileButton: {},
	        useImageButton: true,
	        useAudioMessages: true,
	        mentionDataSource: {
	          outsection: 'NO',
	          url: "".concat(siteDir, "mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots")
	        },
	        attachFileSettings: {
	          previewMaxWidth: 640,
	          previewMaxHeight: 640,
	          resize: {
	            targetWidth: -1,
	            targetHeight: -1,
	            sourceType: 1,
	            encodingType: 0,
	            mediaType: 2,
	            allowsEdit: false,
	            saveToPhotoAlbum: true,
	            popoverOptions: false,
	            cameraDirection: 0
	          },
	          sendFileSeparately: true,
	          showAttachedFiles: true,
	          editingMediaFiles: false,
	          maxAttachedFilesCount: 100
	        },
	        attachButton: {
	          items: [{
	            id: 'disk',
	            name: this.getLocalize('MOBILE_CHAT_PANEL_UPLOAD_DISK'),
	            dataSource: {
	              multiple: false,
	              url: "".concat(siteDir, "mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=").concat(this.controller.application.getUserId()),
	              TABLE_SETTINGS: {
	                searchField: true,
	                showtitle: true,
	                modal: true,
	                name: this.getLocalize('MOBILE_CHAT_PANEL_UPLOAD_DISK_FILES')
	              }
	            }
	          }, {
	            id: 'mediateka',
	            name: this.getLocalize('MOBILE_CHAT_PANEL_UPLOAD_GALLERY')
	          }, {
	            id: 'camera',
	            name: this.getLocalize('MOBILE_CHAT_PANEL_UPLOAD_CAMERA')
	          }]
	        },
	        action: function action(data) {
	          if (typeof data === 'string') {
	            data = {
	              text: data,
	              attachedFiles: []
	            };
	          }
	          var text = data.text.toString().trim();
	          var attachedFiles = Array.isArray(data.attachedFiles) ? data.attachedFiles : [];
	          if (attachedFiles.length <= 0) {
	            _this17.clearText();
	            _this17.hideSmiles();
	            var editId = _this17.controller.getStore().getters['dialogues/getEditId'](_this17.controller.application.getDialogId());
	            if (editId) {
	              _this17.updateMessage(editId, text);
	            } else {
	              _this17.addMessage(text);
	            }
	          } else {
	            attachedFiles.forEach(function (file) {
	              // disk
	              if (typeof file.dataAttributes !== 'undefined') {
	                fabric.Answers.sendCustomEvent('vueChatFileDisk', {});
	                return _this17.uploadFile({
	                  source: 'disk',
	                  name: file.name,
	                  type: file.type.toString().toLowerCase(),
	                  preview: !file.dataAttributes.URL || !file.dataAttributes.URL.PREVIEW ? null : {
	                    url: file.dataAttributes.URL.PREVIEW,
	                    width: file.dataAttributes.URL.PREVIEW.match(/(width=(\d+))/i)[2],
	                    height: file.dataAttributes.URL.PREVIEW.match(/(height=(\d+))/i)[2]
	                  },
	                  uploadLink: parseInt(file.dataAttributes.ID)
	                });
	              }

	              // audio
	              if (file.type === 'audio/mp4') {
	                fabric.Answers.sendCustomEvent('vueChatFileAudio', {});
	                return _this17.uploadFile({
	                  source: 'audio',
	                  name: "mobile_audio_".concat(new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-'), ".mp3"),
	                  type: 'mp3',
	                  preview: null,
	                  uploadLink: file.url
	                });
	              }
	              var filename = file.name;
	              var fileType = im_model.FilesModel.getType(file.name);
	              if (fileType === im_const.FileType.video) {
	                fabric.Answers.sendCustomEvent('vueChatFileVideo', {});
	              } else if (fileType === im_const.FileType.image) {
	                fabric.Answers.sendCustomEvent('vueChatFileImage', {});
	              } else {
	                fabric.Answers.sendCustomEvent('vueChatFileOther', {});
	              }
	              if (fileType === im_const.FileType.image || fileType === im_const.FileType.video) {
	                var extension = file.name.split('.').slice(-1)[0].toLowerCase();
	                if (file.type === 'image/heic') {
	                  extension = 'jpg';
	                }
	                filename = "mobile_file_".concat(new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-'), ".").concat(extension);
	              }

	              // file
	              return _this17.uploadFile({
	                source: 'gallery',
	                name: filename,
	                type: file.type.toString().toLowerCase(),
	                preview: file.previewUrl ? {
	                  url: file.previewUrl,
	                  width: file.previewWidth,
	                  height: file.previewHeight
	                } : null,
	                uploadLink: file.url
	              });
	            });
	          }
	        },
	        callback: function callback(data) {
	          console.log('Textpanel callback', data);
	          if (!data.event) {
	            return false;
	          }
	          if (data.event === 'onKeyPress') {
	            var text = data.text.toString();
	            if (text.trim().length > 2) {
	              _this17.controller.application.startWriting();
	            }
	            if (text.length === 0) {
	              _this17.setTextareaMessage({
	                message: ''
	              });
	              _this17.controller.application.stopWriting();
	            } else {
	              _this17.setTextareaMessage({
	                message: text
	              });
	            }
	          } else if (data.event === 'onSmileSelect') {
	            _this17.controller.showSmiles();
	          } else if (Application.getPlatform() !== 'android') {
	            if (data.event === 'getFocus') {
	              if (im_lib_utils.Utils.platform.isIos() && im_lib_utils.Utils.platform.getIosVersion() > 12) {
	                main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	                  chatId: _this17.controller.application.getChatId(),
	                  duration: 300,
	                  cancelIfScrollChange: true
	                });
	              }
	            } else if (data.event === 'removeFocus') ;
	          }
	        }
	      };
	    } /* region 04. Rest methods */
	  }, {
	    key: "addMessage",
	    value: function addMessage(text) {
	      var _this18 = this;
	      var file = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var messageUuid = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
	      if (!text && !file) {
	        return false;
	      }
	      var uuid = messageUuid || ChatUtils.getUuidv4();
	      var quoteId = this.controller.getStore().getters['dialogues/getQuoteId'](this.controller.application.getDialogId());
	      if (quoteId) {
	        var quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), quoteId);
	        if (quoteMessage) {
	          var user = null;
	          if (quoteMessage.authorId) {
	            user = this.controller.getStore().getters['users/get'](quoteMessage.authorId);
	          }
	          var files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());
	          var message = [];
	          message.push('-'.repeat(54));
	          message.push("".concat(user && user.name ? user.name : this.getLocalize('MOBILE_CHAT_SYSTEM_MESSAGE'), " [").concat(im_lib_utils.Utils.date.format(quoteMessage.date, null, this.getLocalize()), "]"));
	          message.push(im_lib_utils.Utils.text.quote(quoteMessage.text, quoteMessage.params, files, this.getLocalize()));
	          message.push('-'.repeat(54), text);
	          text = message.join('\n');
	          this.quoteMessageClear();
	        }
	      }
	      console.warn('addMessage', text, file, uuid);
	      if (!this.controller.application.isUnreadMessagesLoaded()) {
	        this.sendMessage({
	          id: uuid,
	          chatId: this.controller.application.getChatId(),
	          dialogId: this.controller.application.getDialogId(),
	          text: text,
	          file: file
	        });
	        this.processSendMessages();
	        return true;
	      }
	      this.controller.getStore().commit('application/increaseDialogExtraCount');
	      var params = {};
	      if (file) {
	        params.FILE_ID = [file.id];
	      }
	      this.controller.getStore().dispatch('messages/add', {
	        id: uuid,
	        chatId: this.controller.application.getChatId(),
	        authorId: this.controller.application.getUserId(),
	        text: text,
	        params: params,
	        sending: !file
	      }).then(function (messageId) {
	        main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	          chatId: _this18.controller.application.getChatId(),
	          cancelIfScrollChange: true
	        });
	        _this18.messagesQueue.push({
	          id: messageId,
	          chatId: _this18.controller.application.getChatId(),
	          dialogId: _this18.controller.application.getDialogId(),
	          text: text,
	          file: file,
	          sending: false
	        });
	        if (_this18.controller.application.getChatId()) {
	          _this18.processSendMessages();
	        } else {
	          _this18.requestData();
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "uploadFile",
	    value: function uploadFile(file) {
	      var _this19 = this;
	      var text = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      if (!file) {
	        return false;
	      }
	      var fileMessageUuid = ChatUtils.getUuidv4();
	      console.warn('addFile', file, text, fileMessageUuid);
	      if (!this.controller.application.isUnreadMessagesLoaded()) {
	        this.addMessage(text, {
	          id: 0,
	          source: file
	        }, fileMessageUuid);
	        return true;
	      }
	      this.controller.getStore().dispatch('files/add', this.controller.application.prepareFilesBeforeSave({
	        chatId: this.controller.application.getChatId(),
	        authorId: this.controller.application.getUserId(),
	        name: file.name,
	        type: im_model.FilesModel.getType(file.name),
	        extension: file.name.split('.').splice(-1)[0],
	        size: 0,
	        image: file.preview ? {
	          width: file.preview.width,
	          height: file.preview.height
	        } : false,
	        status: file.source === 'disk' ? im_const.FileStatus.wait : im_const.FileStatus.upload,
	        progress: 0,
	        authorName: this.controller.application.getCurrentUser().name,
	        urlPreview: file.preview ? file.preview.url : ''
	      })).then(function (fileId) {
	        return _this19.addMessage(text, _objectSpread$2({
	          id: fileId
	        }, file), fileMessageUuid);
	      });
	      return true;
	    }
	  }, {
	    key: "cancelUploadFile",
	    value: function cancelUploadFile(fileId) {
	      var _this20 = this;
	      this.cancelUnsentFile(fileId);
	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });
	      if (!element) {
	        var messages = this.controller.getStore().getters['messages/get'](this.controller.application.getChatId());
	        var messageToDelete = messages.find(function (element) {
	          return element.params.FILE_ID && element.params.FILE_ID.includes(fileId);
	        });
	        if (messageToDelete) {
	          element = {
	            id: messageToDelete.id,
	            chatId: messageToDelete.chatId,
	            file: {
	              id: messageToDelete.params.FILE_ID[0]
	            }
	          };
	        }
	      }
	      if (element) {
	        BX.MobileUploadProvider.cancelTasks(["imDialogFileUpload|".concat(fileId)]);
	        this.controller.getStore().dispatch('messages/delete', {
	          chatId: element.chatId,
	          id: element.id
	        }).then(function () {
	          _this20.controller.getStore().dispatch('files/delete', {
	            chatId: element.chatId,
	            id: element.file.id
	          });
	          _this20.messagesQueue = _this20.messagesQueue.filter(function (el) {
	            return el.id !== element.id;
	          });
	        });
	      }
	    }
	  }, {
	    key: "retryUploadFile",
	    value: function retryUploadFile(fileId) {
	      var _this21 = this;
	      var element = this.messagesQueue.find(function (element) {
	        return element.file && element.file.id === fileId;
	      });
	      if (!element) {
	        return false;
	      }
	      this.controller.getStore().dispatch('messages/actionStart', {
	        chatId: element.chatId,
	        id: element.id
	      }).then(function () {
	        _this21.controller.getStore().dispatch('files/update', {
	          chatId: element.chatId,
	          id: element.file.id,
	          fields: {
	            status: im_const.FileStatus.upload,
	            progress: 0
	          }
	        });
	      });
	      element.sending = false;
	      this.processSendMessages();
	      return true;
	    }
	  }, {
	    key: "processSendMessages",
	    value: function processSendMessages() {
	      var _this22 = this;
	      this.messagesQueue.filter(function (element) {
	        return !element.sending;
	      }).forEach(function (element) {
	        element.sending = true;
	        if (element.file) {
	          if (element.file.source === 'disk') {
	            _this22.fileCommit({
	              chatId: element.chatId,
	              dialogId: element.dialogId,
	              diskId: element.file.uploadLink,
	              messageText: element.text,
	              messageId: element.id,
	              fileId: element.file.id,
	              fileType: im_model.FilesModel.getType(element.file.name)
	            }, element);
	          } else if (_this22.controller.application.getDiskFolderId()) {
	            _this22.sendMessageWithFile(element);
	          } else {
	            element.sending = false;
	            _this22.requestDiskFolderId();
	          }
	        } else {
	          element.sending = true;
	          _this22.sendMessage(element);
	        }
	      });
	      return true;
	    }
	  }, {
	    key: "processMarkReadMessages",
	    value: function processMarkReadMessages() {
	      this.controller.application.readMessageExecute(this.controller.application.getChatId(), true);
	      return true;
	    }
	  }, {
	    key: "sendMessage",
	    value: function sendMessage(message) {
	      message.text = message.text.replace(/^(-{21}\n)/gm, "".concat('-'.repeat(54), "\n"));
	      this.controller.application.stopWriting(message.dialogId);
	      window.imDialogMessagesTasks.push({
	        taskId: "sendMessage|".concat(message.id)
	      });
	      BXMobileApp.Events.postToComponent('chatbackground::task::add', ["sendMessage|".concat(message.id), [im_const.RestMethod.imMessageAdd, {
	        TEMPLATE_ID: message.id,
	        DIALOG_ID: message.dialogId,
	        MESSAGE: message.text
	      }], message], 'background');
	    }
	  }, {
	    key: "sendMessageWithFile",
	    value: function sendMessageWithFile(message) {
	      var fileType = im_model.FilesModel.getType(message.file.name);
	      var fileExtension = message.file.name.toString().toLowerCase().split('.').splice(-1)[0];
	      var attachPreviewFile = fileType !== im_const.FileType.image && message.file.preview;
	      var needConvert = fileType === im_const.FileType.image && message.file.type !== 'image/gif' || fileType === im_const.FileType.video;
	      window.imDialogUploadTasks.push({
	        taskId: "imDialogFileUpload|".concat(message.file.id)
	      });
	      BX.MobileUploadProvider.addTasks([{
	        url: message.file.uploadLink,
	        params: message,
	        name: message.file.name,
	        type: fileExtension,
	        mimeType: fileType === im_const.FileType.audio ? 'audio/mp4' : null,
	        resize: needConvert ? {
	          quality: 80,
	          width: 1920,
	          height: 1080
	        } : null,
	        previewUrl: attachPreviewFile ? message.file.preview.url : '',
	        folderId: this.controller.application.getDiskFolderId(),
	        taskId: "imDialogFileUpload|".concat(message.file.id),
	        onDestroyEventName: 'onimdiskmessageaddsuccess'
	      }]);
	    }
	  }, {
	    key: "fileError",
	    value: function fileError(chatId, fileId) {
	      var messageId = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
	      this.controller.getStore().dispatch('files/update', {
	        chatId: chatId,
	        id: fileId,
	        fields: {
	          status: im_const.FileStatus.error,
	          progress: 0
	        }
	      });
	      if (messageId) {
	        this.controller.getStore().dispatch('messages/actionError', {
	          chatId: chatId,
	          id: messageId,
	          retry: true
	        });
	      }
	    }
	  }, {
	    key: "fileCommit",
	    value: function fileCommit(params, message) {
	      var queryParams = {
	        chat_id: params.chatId,
	        message: params.messageText,
	        template_id: params.messageId ? params.messageId : 0,
	        file_template_id: params.fileId ? params.fileId : 0
	      };
	      if (params.uploadId) {
	        queryParams.upload_id = params.uploadId;
	      } else if (params.diskId) {
	        queryParams.disk_id = params.diskId;
	      }
	      BXMobileApp.Events.postToComponent('chatbackground::task::add', ["uploadFileFromDisk|".concat(message.id), [im_const.RestMethod.imDiskFileCommit, queryParams], message], 'background');
	    }
	  }, {
	    key: "requestDiskFolderId",
	    value: function requestDiskFolderId() {
	      var _this23 = this;
	      if (this.flagRequestDiskFolderIdSended || this.controller.application.getDiskFolderId()) {
	        return true;
	      }
	      this.flagRequestDiskFolderIdSended = true;
	      this.controller.restClient.callMethod(im_const.RestMethod.imDiskFolderGet, {
	        chat_id: this.controller.application.getChatId()
	      }).then(function (response) {
	        _this23.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, response);
	        _this23.flagRequestDiskFolderIdSended = false;
	        _this23.processSendMessages();
	      })["catch"](function (error) {
	        _this23.flagRequestDiskFolderIdSended = false;
	        _this23.controller.executeRestAnswer(im_const.RestMethodHandler.imDiskFolderGet, error);
	      });
	      return true;
	    }
	  }, {
	    key: "getDialogHistory",
	    value: function getDialogHistory(lastId) {
	      var _this24 = this;
	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.application.getRequestMessageLimit();
	      this.controller.restClient.callMethod(im_const.RestMethod.imDialogMessagesGet, {
	        CHAT_ID: this.controller.application.getChatId(),
	        LAST_ID: lastId,
	        LIMIT: limit,
	        CONVERT_TEXT: 'Y'
	      }).then(function (result) {
	        _this24.controller.executeRestAnswer(im_const.RestMethodHandler.imDialogMessagesGet, result);
	        // this.controller.application.emit(EventType.dialog.requestHistoryResult, {count: result.data().messages.length});
	      })["catch"](function (result) {
	        // this.controller.emit(EventType.dialog.requestHistoryResult, {error: result.error().ex});
	      });
	    }
	  }, {
	    key: "getDialogUnread",
	    value: function getDialogUnread(lastId) {
	      var _this25 = this;
	      var limit = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : this.controller.application.getRequestMessageLimit();
	      if (this.promiseGetDialogUnreadWait) {
	        return this.promiseGetDialogUnread;
	      }
	      this.promiseGetDialogUnread = new BX.Promise();
	      this.promiseGetDialogUnreadWait = true;
	      if (!lastId) {
	        lastId = this.controller.getStore().getters['messages/getLastId'](this.controller.application.getChatId());
	      }
	      if (!lastId) {
	        // this.controller.application.emit(EventType.dialog.requestUnreadResult, {error: {error: 'LAST_ID_EMPTY', error_description: 'LastId is empty.'}});

	        this.promiseGetDialogUnread.reject();
	        this.promiseGetDialogUnreadWait = false;
	        return this.promiseGetDialogUnread;
	      }
	      main_core_events.EventEmitter.emitAsync(im_const.EventType.dialog.readMessage, {
	        id: lastId,
	        skipAjax: true
	      }).then(function () {
	        var _query2;
	        _this25.timer.start('data', 'load', 0.5, function () {
	          console.warn('ChatDialog.requestData: slow connection show progress icon');
	          app.titleAction('setParams', {
	            useProgress: true,
	            useLetterImage: false
	          });
	        });
	        var query = (_query2 = {}, babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogRead, [im_const.RestMethod.imDialogRead, {
	          dialog_id: _this25.controller.application.getDialogId(),
	          message_id: lastId
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imChatGet, [im_const.RestMethod.imChatGet, {
	          dialog_id: _this25.controller.application.getDialogId()
	        }]), babelHelpers.defineProperty(_query2, im_const.RestMethodHandler.imDialogMessagesGetUnread, [im_const.RestMethod.imDialogMessagesGet, {
	          chat_id: _this25.controller.application.getChatId(),
	          first_id: lastId,
	          limit: limit,
	          convert_text: 'Y'
	        }]), _query2);
	        _this25.controller.restClient.callBatch(query, function (response) {
	          if (!response) {
	            _this25.promiseGetDialogUnread.reject();
	            _this25.promiseGetDialogUnreadWait = false;
	            return false;
	          }
	          var chatGetResult = response[im_const.RestMethodHandler.imChatGet];
	          if (!chatGetResult.error()) {
	            _this25.controller.executeRestAnswer(im_const.RestMethodHandler.imChatGet, chatGetResult);
	          }
	          var dialogMessageUnread = response[im_const.RestMethodHandler.imDialogMessagesGetUnread];
	          if (dialogMessageUnread.error()) {
	            _this25.promiseGetDialogUnread.reject();
	            _this25.promiseGetDialogUnreadWait = false;
	            return false;
	          }
	          dialogMessageUnread = dialogMessageUnread.data();
	          _this25.controller.getStore().dispatch('users/set', dialogMessageUnread.users);
	          _this25.controller.getStore().dispatch('files/set', _this25.controller.application.prepareFilesBeforeSave(dialogMessageUnread.files));
	          _this25.controller.getStore().dispatch('messages/setAfter', dialogMessageUnread.messages).then(function () {
	            app.titleAction('setParams', {
	              useProgress: false,
	              useLetterImage: true
	            });
	            _this25.timer.stop('data', 'load', true);
	            _this25.promiseGetDialogUnread.fulfill(response);
	            _this25.promiseGetDialogUnreadWait = false;
	            return true;
	          });
	        }, false, false, im_lib_utils.Utils.getLogTrackingParams({
	          name: im_const.RestMethodHandler.imDialogMessagesGetUnread,
	          dialog: _this25.controller.application.getDialogData()
	        }));
	      });
	      return this.promiseGetDialogUnread;
	    }
	  }, {
	    key: "retrySendMessage",
	    value: function retrySendMessage(message) {
	      var element = this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      });
	      if (element) {
	        if (element.file && element.file.id) {
	          this.retryUploadFile(element.file.id);
	        }
	        return false;
	      }
	      this.messagesQueue.push({
	        id: message.id,
	        chatId: this.controller.application.getChatId(),
	        dialogId: this.controller.application.getDialogId(),
	        text: message.text,
	        sending: false
	      });
	      this.controller.application.setSendingMessageFlag(message.id);
	      this.processSendMessages();
	    }
	  }, {
	    key: "openProfile",
	    value: function openProfile(userId) {
	      BXMobileApp.Events.postToComponent('onUserProfileOpen', [userId, {
	        backdrop: true
	      }], 'communication');
	    }
	  }, {
	    key: "openDialog",
	    value: function openDialog(dialogId) {
	      BXMobileApp.Events.postToComponent('onOpenDialog', [{
	        dialogId: dialogId
	      }, true], 'im.recent');
	      BXMobileApp.Events.postToComponent('ImMobile.Messenger.Dialog:open', [{
	        dialogId: dialogId
	      }], 'im.messenger');
	    }
	  }, {
	    key: "openPhoneMenu",
	    value: function openPhoneMenu(number) {
	      BX.MobileTools.phoneTo(number);
	    }
	  }, {
	    key: "openMessageMenu",
	    value: function openMessageMenu(message) {
	      var _this26 = this;
	      if (this.messagesQueue.find(function (el) {
	        return el.id === message.id;
	      })) {
	        return false;
	      }
	      this.controller.getStore().dispatch('messages/update', {
	        id: message.id,
	        chatId: message.chatId,
	        fields: {
	          blink: true
	        }
	      });
	      var currentUser = this.controller.application.getCurrentUser();
	      var dialog = this.controller.application.getDialogData();
	      var messageUser = message.authorId > 0 ? this.controller.getStore().getters['users/get'](message.authorId, true) : null;
	      this.messageMenuInstance = BackdropMenu.create("im.dialog.menu.mess|".concat(this.controller.application.getDialogId())).setTestId('im-dialog-menu-mess').setItems([BackdropMenuItem.create('reply').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_REPLY')).setIcon(BackdropMenuIcon.reply).skip(function (message) {
	        var dialog = _this26.controller.application.getDialogData();
	        if (dialog.type === 'announcement' && !dialog.managerList.includes(_this26.controller.application.getUserId())) {
	          return true;
	        }
	        return !message.authorId || message.authorId === _this26.controller.application.getUserId();
	      }), BackdropMenuItem.create('copy').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_COPY')).setIcon(BackdropMenuIcon.copy).skip(function (message) {
	        return message.params.IS_DELETED === 'Y';
	      }), BackdropMenuItem.create('quote').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_QUOTE')).setIcon(BackdropMenuIcon.quote).skip(function (message) {
	        var dialog = _this26.controller.application.getDialogData();
	        if (dialog.type === 'announcement' && !dialog.managerList.includes(_this26.controller.application.getUserId())) {
	          return true;
	        }
	        return message.params.IS_DELETED === 'Y';
	      }), BackdropMenuItem.create('unread').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_UNREAD')).setIcon(BackdropMenuIcon.unread).skip(function (message) {
	        return message.authorId === _this26.controller.application.getUserId() || message.unread;
	      }), BackdropMenuItem.create('read').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_READ')).setIcon(BackdropMenuIcon.checked).skip(function (message) {
	        return !message.unread;
	      }), BackdropMenuItem.create('edit').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_EDIT')).setIcon(BackdropMenuIcon.edit).skip(function (message) {
	        return message.authorId !== _this26.controller.application.getUserId() || message.params.IS_DELETED === 'Y';
	      }), BackdropMenuItem.create('share').setType(BackdropMenuItemType.menu).setIcon(BackdropMenuIcon.circle_plus).setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_SHARE_MENU')).disableClose().skip(currentUser.extranet || dialog.type === 'announcement'), BackdropMenuItem.create('profile').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_PROFILE')).setIcon(BackdropMenuIcon.user).skip(function (message) {
	        if (message.authorId <= 0 || !messageUser) {
	          return true;
	        }
	        if (!im_lib_utils.Utils.dialog.isChatId(_this26.controller.application.getDialogId())) {
	          return true;
	        }
	        if (message.authorId === _this26.controller.application.getUserId()) {
	          return true;
	        }
	        if (messageUser.externalAuthId === 'imconnector' || messageUser.externalAuthId === 'call') {
	          return true;
	        }
	        return false;
	      }), BackdropMenuItem.create('delete').setTitle(this.getLocalize('MOBILE_MESSAGE_MENU_DELETE')).setStyles(BackdropMenuStyle.create().setFont(WidgetListItemFont.create().setColor('#c50000'))).setIcon(BackdropMenuIcon.trash).skip(function (message) {
	        return message.authorId !== _this26.controller.application.getUserId() || message.params.IS_DELETED === 'Y';
	      })]).setVersion(BX.componentParameters.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0')).setEventListener(function (name, params, message, backdrop) {
	        if (name === 'destroyed') {
	          ui_vue.Vue.event.$emit('bitrixmobile:controller:focus');
	        }
	        if (name !== 'selected') {
	          return false;
	        }
	        switch (params.id) {
	          case 'reply':
	            {
	              _this26.replyToUser(message.authorId);
	              break;
	            }
	          case 'copy':
	            {
	              _this26.copyMessage(message.id);
	              break;
	            }
	          case 'quote':
	            {
	              _this26.quoteMessageClear();
	              _this26.quoteMessage(message.id);
	              break;
	            }
	          case 'edit':
	            {
	              _this26.quoteMessageClear();
	              _this26.editMessage(message.id);
	              break;
	            }
	          case 'delete':
	            {
	              _this26.deleteMessage(message.id);
	              break;
	            }
	          case 'unread':
	            {
	              _this26.unreadMessage(message.id);
	              break;
	            }
	          case 'read':
	            {
	              main_core_events.EventEmitter.emit(im_const.EventType.dialog.readMessage, {
	                id: message.id
	              });
	              break;
	            }
	          case 'share':
	            {
	              var _dialog = _this26.controller.application.getDialogData();
	              var subMenu = BackdropMenu.create("im.dialog.menu.mess.submenu|".concat(_this26.controller.application.getDialogId())).setTestId('im-dialog-menu-mess-submenu-share').setItems([BackdropMenuItem.create('share_task').setIcon(BackdropMenuIcon.task).setTitle(_this26.getLocalize('MOBILE_MESSAGE_MENU_SHARE_TASK')), BackdropMenuItem.create('share_post').setIcon(BackdropMenuIcon.lifefeed).setTitle(_this26.getLocalize('MOBILE_MESSAGE_MENU_SHARE_POST_NEWS')), BackdropMenuItem.create('share_chat').setIcon(BackdropMenuIcon.chat).setTitle(_this26.getLocalize('MOBILE_MESSAGE_MENU_SHARE_CHAT'))]).setEventListener(function (name, params, options, backdrop) {
	                if (name !== 'selected') {
	                  return false;
	                }
	                switch (params.id) {
	                  case 'share_task':
	                    {
	                      _this26.shareMessage(message.id, 'TASK');
	                      break;
	                    }
	                  case 'share_post':
	                    {
	                      _this26.shareMessage(message.id, 'POST');
	                      break;
	                    }
	                  case 'share_chat':
	                    {
	                      _this26.shareMessage(message.id, 'CHAT');
	                      break;
	                    }
	                  // No default
	                }
	              });

	              backdrop.showSubMenu(subMenu);
	              break;
	            }
	          case 'profile':
	            {
	              _this26.openProfile(message.authorId);
	              break;
	            }
	          default:
	            {
	              console.warn('BackdropMenuItem is not implemented', params);
	            }
	        }
	      });
	      this.messageMenuInstance.setCustomParams(message).show();
	      fabric.Answers.sendCustomEvent('vueChatOpenDropdown', {});
	    }
	  }, {
	    key: "openHeaderMenu",
	    value: function openHeaderMenu() {
	      var _this27 = this;
	      fabric.Answers.sendCustomEvent('vueChatOpenHeaderMenu', {});
	      if (!this.headerMenu) {
	        this.headerMenu = HeaderMenu.create().setUseNavigationBarColor().setEventListener(function (name, params, customParams) {
	          if (name !== 'selected') {
	            return false;
	          }
	          switch (params.id) {
	            case 'profile':
	              {
	                _this27.openProfile(_this27.controller.application.getDialogId());
	                break;
	              }
	            case 'user_list':
	              {
	                _this27.openUserList({
	                  listType: 'USERS',
	                  title: _this27.getLocalize('MOBILE_HEADER_MENU_USER_LIST'),
	                  backdrop: true
	                });
	                break;
	              }
	            case 'user_add':
	              {
	                _this27.openAddUserDialog();
	                break;
	              }
	            case 'leave':
	              {
	                _this27.leaveChat();
	                break;
	              }
	            case 'notify':
	              {
	                _this27.controller.application.muteDialog();
	                break;
	              }
	            case 'call_chat_call':
	              {
	                BX.MobileTools.phoneTo(_this27.controller.application.getDialogData().entityId);
	                break;
	              }
	            case 'goto_crm':
	              {
	                var crmData = _this27.controller.application.getDialogCrmData();
	                var openWidget = BX.MobileTools.resolveOpenFunction("/crm/".concat(crmData.entityType, "/details/").concat(crmData.entityId, "/"));
	                if (openWidget) {
	                  openWidget();
	                }
	                break;
	              }
	            case 'reload':
	              {
	                new BXMobileApp.UI.NotificationBar({
	                  message: _this27.getLocalize('MOBILE_HEADER_MENU_RELOAD_WAIT'),
	                  color: '#d920b0ff',
	                  textColor: '#ffffff',
	                  groupId: 'refresh',
	                  useLoader: true,
	                  maxLines: 1,
	                  align: 'center',
	                  hideOnTap: true
	                }, 'copy').show();
	                _this27.controller.getStoreBuilder().clearDatabase();
	                reload();
	                break;
	              }
	            // No default
	          }
	        });
	      }

	      if (im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        var dialogData = this.controller.application.getDialogData();
	        var notifyToggleText = this.controller.application.isDialogMuted() ? this.getLocalize('MOBILE_HEADER_MENU_NOTIFY_ENABLE') : this.getLocalize('MOBILE_HEADER_MENU_NOTIFY_DISABLE');
	        var notifyToggleIcon = this.controller.application.isDialogMuted() ? HeaderMenuIcon.notify_off : HeaderMenuIcon.notify;
	        var gotoCrmLocalize = '';
	        if (dialogData.type === im_const.DialogType.call || dialogData.type === im_const.DialogType.crm) {
	          var crmData = this.controller.application.getDialogCrmData();
	          if (crmData.enabled) {
	            switch (crmData.entityType) {
	              case im_const.DialogCrmType.lead:
	                {
	                  gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_LEAD');
	                  break;
	                }
	              case im_const.DialogCrmType.company:
	                {
	                  gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_COMPANY');
	                  break;
	                }
	              case im_const.DialogCrmType.contact:
	                {
	                  gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_CONTACT');
	                  break;
	                }
	              case im_const.DialogCrmType.deal:
	                {
	                  gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM_DEAL');
	                  break;
	                }
	              default:
	                {
	                  gotoCrmLocalize = this.getLocalize('MOBILE_GOTO_CRM');
	                }
	            }
	          }
	        }
	        if (dialogData.type === im_const.DialogType.call) {
	          this.headerMenu.setItems([HeaderMenuItem.create('call_chat_call').setTitle(this.getLocalize('MOBILE_HEADER_MENU_AUDIO_CALL')).setIcon(HeaderMenuIcon.phone).skip(dialogData.entityId === 'UNIFY_CALL_CHAT'), HeaderMenuItem.create('goto_crm').setTitle(gotoCrmLocalize).setIcon(HeaderMenuIcon.lifefeed).skip(dialogData.entityId === 'UNIFY_CALL_CHAT' || !gotoCrmLocalize), HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload)]);
	        } else {
	          var items = [HeaderMenuItem.create('notify').setTitle(notifyToggleText).setIcon(notifyToggleIcon).skip(!dialogData.restrictions.mute), HeaderMenuItem.create('user_list').setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_LIST')).setIcon(HeaderMenuIcon.user).skip(!dialogData.restrictions.userList), HeaderMenuItem.create('user_add').setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_ADD')).setIcon(HeaderMenuIcon.user_plus).skip(!dialogData.restrictions.extend), HeaderMenuItem.create('leave').setTitle(this.getLocalize('MOBILE_HEADER_MENU_LEAVE')).setIcon(HeaderMenuIcon.cross).skip(!dialogData.restrictions.leave), HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload)];
	          items.push(HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload));
	          if (dialogData.type === im_const.DialogType.crm && gotoCrmLocalize) {
	            items.unshift(HeaderMenuItem.create('goto_crm').setTitle(gotoCrmLocalize).setIcon(HeaderMenuIcon.lifefeed));
	          }
	          this.headerMenu.setItems(items);
	        }
	      } else {
	        var shouldSkipUserAdd = false;
	        var userData = this.controller.getStore().getters['users/get'](this.controller.application.getDialogId(), true);
	        if (userData.bot && userData.externalAuthId === 'support24') {
	          shouldSkipUserAdd = true;
	        }
	        this.headerMenu.setItems([HeaderMenuItem.create('profile').setTitle(this.getLocalize('MOBILE_HEADER_MENU_PROFILE')).setIcon('user').skip(function () {
	          if (im_lib_utils.Utils.dialog.isChatId(_this27.controller.application.getDialogId())) {
	            return true;
	          }
	          var userData = _this27.controller.getStore().getters['users/get'](_this27.controller.application.getDialogId(), true);
	          if (userData.bot) {
	            return true;
	          }
	          return false;
	        }), HeaderMenuItem.create('user_add').setTitle(this.getLocalize('MOBILE_HEADER_MENU_USER_ADD')).setIcon(HeaderMenuIcon.user_plus).skip(shouldSkipUserAdd), HeaderMenuItem.create('reload').setTitle(this.getLocalize('MOBILE_HEADER_MENU_RELOAD')).setIcon(HeaderMenuIcon.reload)]);
	      }
	      this.headerMenu.show(true);
	    }
	  }, {
	    key: "shareMessage",
	    value: function shareMessage(messageId, type) {
	      if (!this.controller.isOnline()) {
	        return false;
	      }
	      return this.controller.application.shareMessage(messageId, type);
	    }
	  }, {
	    key: "unreadMessage",
	    value: function unreadMessage(messageId) {
	      if (!this.controller.isOnline()) {
	        return false;
	      }
	      return this.controller.application.unreadMessage(messageId);
	    }
	  }, {
	    key: "openReadedList",
	    value: function openReadedList(list) {
	      if (!im_lib_utils.Utils.dialog.isChatId(this.controller.application.getDialogId())) {
	        return false;
	      }
	      if (!list || list.length <= 1) {
	        return false;
	      }
	      this.openUserList({
	        users: list.map(function (element) {
	          return element.userId;
	        }),
	        title: this.getLocalize('MOBILE_MESSAGE_LIST_VIEW')
	      });
	    }
	  }, {
	    key: "replyToUser",
	    value: function replyToUser(userId) {
	      var userData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      if (!this.controller.isOnline()) {
	        return false;
	      }
	      if (!userData) {
	        userData = this.controller.getStore().getters['users/get'](userId);
	      }
	      return this.insertText({
	        text: "[USER=".concat(userId, "]").concat(userData.firstName, "[/USER] ")
	      });
	    }
	  }, {
	    key: "copyMessage",
	    value: function copyMessage(id) {
	      var quoteMessage = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
	      var text = '';
	      if (quoteMessage.params.FILE_ID && quoteMessage.params.FILE_ID.length > 0) {
	        text = quoteMessage.params.FILE_ID.map(function (fileId) {
	          return "[DISK=".concat(fileId, "]");
	        }).join(' ');
	      }
	      if (quoteMessage.text) {
	        if (text) {
	          text += '\n';
	        }
	        text += quoteMessage.text.replace(/^(-{54}\n)/gm, "".concat('-'.repeat(21), "\n"));
	      }
	      text = text.replace(/\[url](.*?)\[\/url]/gi, function (whole, link) {
	        return link;
	      });
	      app.exec('copyToClipboard', {
	        text: text
	      });
	      new BXMobileApp.UI.NotificationBar({
	        message: BX.message('MOBILE_MESSAGE_MENU_COPY_SUCCESS'),
	        color: '#af000000',
	        textColor: '#ffffff',
	        groupId: 'clipboard',
	        maxLines: 1,
	        align: 'center',
	        isGlobal: true,
	        useCloseButton: true,
	        autoHideTimeout: 1500,
	        hideOnTap: true
	      }, 'copy').show();
	    }
	  }, {
	    key: "quoteMessage",
	    value: function quoteMessage(id) {
	      var _this28 = this;
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: id
	        }
	      }).then(function () {
	        if (!_this28.controller.getStore().state.application.mobile.keyboardShow) {
	          _this28.setTextFocus();
	          setTimeout(function () {
	            main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	              chatId: _this28.controller.application.getChatId(),
	              duration: 300,
	              cancelIfScrollChange: false,
	              force: true
	            });
	          }, 300);
	        }
	      });
	    }
	  }, {
	    key: "quoteMessageClear",
	    value: function quoteMessageClear() {
	      this.setText('');
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: 0,
	          editId: 0
	        }
	      });
	    }
	  }, {
	    key: "editMessage",
	    value: function editMessage(id) {
	      var _this29 = this;
	      var message = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          quoteId: id,
	          editId: id
	        }
	      }).then(function () {
	        setTimeout(function () {
	          return main_core_events.EventEmitter.emit(im_const.EventType.dialog.scrollToBottom, {
	            chatId: _this29.controller.application.getChatId(),
	            duration: 300,
	            cancelIfScrollChange: false,
	            force: true
	          });
	        }, 300);
	        _this29.setTextFocus();
	      });
	      this.setText(message.text);
	    }
	  }, {
	    key: "updateMessage",
	    value: function updateMessage(id, text) {
	      this.quoteMessageClear();
	      this.controller.getStore().dispatch('dialogues/update', {
	        dialogId: this.controller.application.getDialogId(),
	        fields: {
	          editId: 0
	        }
	      });
	      this.editMessageSend(id, text);
	    }
	  }, {
	    key: "editMessageSend",
	    value: function editMessageSend(id, text) {
	      this.controller.restClient.callMethod(im_const.RestMethod.imMessageUpdate, {
	        MESSAGE_ID: id,
	        MESSAGE: text
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageUpdate,
	        data: {
	          timMessageType: 'text'
	        },
	        dialog: this.controller.application.getDialogData()
	      }));
	    }
	  }, {
	    key: "deleteMessage",
	    value: function deleteMessage(id) {
	      var _this30 = this;
	      var message = this.controller.getStore().getters['messages/getMessage'](this.controller.application.getChatId(), id);
	      var files = this.controller.getStore().getters['files/getList'](this.controller.application.getChatId());
	      var messageText = im_lib_utils.Utils.text.purify(message.text, message.params, files, this.getLocalize());
	      messageText = messageText.length > 50 ? "".concat(messageText.slice(0, 47), "...") : messageText;
	      app.confirm({
	        title: this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_CONFIRM'),
	        text: messageText ? "\"".concat(messageText, "\"") : '',
	        buttons: [this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_YES'), this.getLocalize('MOBILE_MESSAGE_MENU_DELETE_NO')],
	        callback: function callback(button) {
	          if (button === 1) {
	            _this30.quoteMessageClear();
	            _this30.deleteMessageSend(id);
	          }
	        }
	      });
	    }
	  }, {
	    key: "deleteMessageSend",
	    value: function deleteMessageSend(id) {
	      this.controller.restClient.callMethod(im_const.RestMethod.imMessageDelete, {
	        MESSAGE_ID: id
	      }, null, null, im_lib_utils.Utils.getLogTrackingParams({
	        name: im_const.RestMethod.imMessageDelete,
	        data: {},
	        dialog: this.controller.application.getDialogData(this.controller.application.getDialogId())
	      }));
	    }
	  }, {
	    key: "insertText",
	    value: function insertText(params) {
	      var _this31 = this;
	      BXMobileApp.UI.Page.TextPanel.getText(function (text) {
	        text = text.toString().trim();
	        text = text ? "".concat(text, " ").concat(params.text) : params.text;
	        _this31.setText(text);
	        _this31.setTextFocus();
	      });
	    }
	  }, {
	    key: "setText",
	    value: function setText() {
	      var text = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      text = text.toString();
	      if (text) {
	        BXMobileApp.UI.Page.TextPanel.setText(text);
	      } else {
	        BXMobileApp.UI.Page.TextPanel.clear();
	      }
	      this.setTextareaMessage({
	        message: text
	      });
	      console.log('Set new text in textarea', text || '-- empty --');
	    }
	  }, {
	    key: "clearText",
	    value: function clearText() {
	      this.setText();
	    }
	  }, {
	    key: "setTextFocus",
	    value: function setTextFocus() {
	      if (!this.controller.getStore().state.application.mobile.keyboardShow) {
	        BXMobileApp.UI.Page.TextPanel.focus();
	      }
	    }
	  }, {
	    key: "isBackground",
	    value: function isBackground() {
	      if ((typeof BXMobileAppContext === "undefined" ? "undefined" : babelHelpers["typeof"](BXMobileAppContext)) !== 'object') {
	        return false;
	      }
	      if (typeof BXMobileAppContext.isAppActive === 'function' && !BXMobileAppContext.isAppActive()) {
	        return true;
	      }
	      if (typeof BXMobileAppContext.isBackground === 'function') {
	        return BXMobileAppContext.isBackground();
	      }
	      return false;
	    }
	  }, {
	    key: "hideSmiles",
	    value: function hideSmiles() {
	      // this.controller.hideSmiles();
	    }
	  }, {
	    key: "changeDialogState",
	    value: function changeDialogState(state) {
	      console.log("changeDialogState -> ".concat(state));
	      if (state === 'show') {
	        this.setIosInset();
	      }
	    }
	    /* endregion 05. Templates and template interaction */
	    /* region 05. Interaction and utils */
	  }, {
	    key: "executeBackgroundTaskSuccess",
	    value: function executeBackgroundTaskSuccess(action, _data) {
	      var successObject = {
	        error: function error() {
	          return false;
	        },
	        data: function data() {
	          return _data.result;
	        }
	      };
	      console.log('Dialog.executeBackgroundTaskSuccess', action, _data);
	      switch (action) {
	        case 'sendMessage':
	          {
	            this.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, successObject, _data.extra);
	            break;
	          }
	        case 'readMessage':
	          {
	            this.processMarkReadMessages();
	            break;
	          }
	        case 'uploadFileFromDisk':
	          {
	            this.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, successObject, _data.extra);
	            break;
	          }
	        // No default
	      }
	    }
	  }, {
	    key: "executeBackgroundTaskFailure",
	    value: function executeBackgroundTaskFailure(action, data) {
	      var errorObject = {
	        error: function error() {
	          return {
	            error: data.code,
	            error_description: data.text,
	            ex: {
	              status: data.status
	            }
	          };
	        },
	        data: function data() {
	          return false;
	        }
	      };
	      console.log('Dialog.executeBackgroundTaskFailure', action, data);

	      // Handle an error only for API error when server status is 200.
	      // Otherwise we don't want to draw errors, because background queue will resend messages.
	      if (data.status === HTTP_OK_STATUS_CODE && (action === 'sendMessage' || action === 'uploadFileFromDisk')) {
	        this.controller.executeRestAnswer(im_const.RestMethodHandler.imMessageAdd, errorObject, data.extra);
	      }
	    }
	    /* endregion 05. Interaction and utils */
	    /* region 06. Interaction and utils */
	  }, {
	    key: "setError",
	    value: function setError() {
	      var code = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var description = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      console.error("MobileChat.error: ".concat(code, " (").concat(description, ")"));
	      var localizeDescription = '';
	      if (code.endsWith('LOCALIZED')) {
	        localizeDescription = description;
	      }
	      this.controller.getStore().commit('application/set', {
	        error: {
	          active: true,
	          code: code,
	          description: localizeDescription
	        }
	      });
	    }
	  }, {
	    key: "clearError",
	    value: function clearError() {
	      this.controller.getStore().commit('application/set', {
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      });
	    }
	  }, {
	    key: "addLocalize",
	    value: function addLocalize(phrases) {
	      return this.controller.addLocalize(phrases);
	    }
	  }, {
	    key: "getLocalize",
	    value: function getLocalize(name) {
	      return this.controller.getLocalize(name);
	    }
	    /* endregion 06. Interaction and utils */
	    /* region 07. Event handlers */
	  }, {
	    key: "onMessagesSet",
	    value: function onMessagesSet() {
	      this.messagesSet = true;
	      BXMobileApp.Events.postToComponent('chatbackground::task::restart', [], 'background');
	      BXMobileApp.Events.postToComponent('chatuploader::task::restart', [], 'background');
	    } /* endregion 07. Event handlers */
	  }]);
	  return MobileDialogApplication;
	}();

	exports.MobileDialogApplication = MobileDialogApplication;

}((this.BX.Messenger.Application = this.BX.Messenger.Application || {}),BX.Messenger.Application,BX.Main,BX,BX.Messenger.Model,BX.Messenger.Provider.Rest,BX.Messenger.Lib,window,BX,BX,BX.Messenger,BX,BX,window,BX.Messenger.Lib,BX.Event,BX.Messenger.Const,BX.Messenger.EventHandler,BX.Messenger.Lib,BX.Messenger.Lib));
//# sourceMappingURL=dialog.bundle.js.map

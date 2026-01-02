/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
this.BX.AI.CopilotChat = this.BX.AI.CopilotChat || {};
(function (exports,ai_copilotChat_ui,main_core_events,main_core,pull_client) {
	'use strict';

	var _scenarioCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scenarioCode");
	var _entityType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _chatId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatId");
	var _initChatExtraOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initChatExtraOptions");
	var _initPull = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPull");
	var _handleNewMessageEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleNewMessageEvent");
	var _handleInputStatusChangedEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputStatusChangedEvent");
	class CopilotChatApi extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$initChatExtr;
	    super(options);
	    Object.defineProperty(this, _handleInputStatusChangedEvent, {
	      value: _handleInputStatusChangedEvent2
	    });
	    Object.defineProperty(this, _handleNewMessageEvent, {
	      value: _handleNewMessageEvent2
	    });
	    Object.defineProperty(this, _initPull, {
	      value: _initPull2
	    });
	    Object.defineProperty(this, _scenarioCode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _chatId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initChatExtraOptions, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotChatAPI');
	    babelHelpers.classPrivateFieldLooseBase(this, _scenarioCode)[_scenarioCode] = options.scenarioCode;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType] = options.entityType;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = options.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId] = options.chatId;
	    babelHelpers.classPrivateFieldLooseBase(this, _initChatExtraOptions)[_initChatExtraOptions] = (_options$initChatExtr = options.initChatExtraOptions) != null ? _options$initChatExtr : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _initPull)[_initPull]();
	  }
	  async initChatData() {
	    const data = {
	      scenarioCode: babelHelpers.classPrivateFieldLooseBase(this, _scenarioCode)[_scenarioCode],
	      entityType: babelHelpers.classPrivateFieldLooseBase(this, _entityType)[_entityType],
	      entityId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	      parameters: babelHelpers.classPrivateFieldLooseBase(this, _initChatExtraOptions)[_initChatExtraOptions]
	    };
	    if (babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId]) {
	      data.chatId = babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId];
	    }
	    const result = await main_core.ajax.runAction('ai.chat.init', {
	      data
	    });
	    data.chatId = result.data.chat.id;
	    this.emit(CopilotChatApiEvents.INIT_CHAT, data);
	    babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId] = result.data.chat.id;
	    return result.data;
	  }
	  async sendMessage(messageData) {
	    const data = {
	      messageData,
	      scenarioCode: babelHelpers.classPrivateFieldLooseBase(this, _scenarioCode)[_scenarioCode],
	      chatId: babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId]
	    };
	    const result = await main_core.ajax.runAction('ai.chat.sendMessage', {
	      data
	    });
	    return result.data;
	  }
	  async loadMessages(offsetMessageId) {
	    var _result$data$messages;
	    const result = await main_core.ajax.runAction('ai.chat.getMessages', {
	      data: {
	        chatId: babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId],
	        offsetMessageId,
	        limit: 20
	      }
	    });
	    return (_result$data$messages = result.data.messages) != null ? _result$data$messages : [];
	  }
	}
	async function _initPull2() {
	  try {
	    await pull_client.PULL.start();
	    pull_client.PULL.subscribe({
	      type: pull_client.PullClient.SubscriptionType.Server,
	      moduleId: 'ai',
	      callback: data => {
	        if (data.command === CopilotChatApiPullEvents.NEW_MESSAGE) {
	          babelHelpers.classPrivateFieldLooseBase(this, _handleNewMessageEvent)[_handleNewMessageEvent](data.params);
	        } else if (data.command === CopilotChatApiPullEvents.INPUT_STATUS_CHANGED) {
	          babelHelpers.classPrivateFieldLooseBase(this, _handleInputStatusChangedEvent)[_handleInputStatusChangedEvent](data.params);
	        }
	      }
	    });
	  } catch (e) {
	    console.error(e);
	  }
	}
	function _handleNewMessageEvent2(data) {
	  if (data.message.chatId !== babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId]) {
	    return;
	  }
	  this.emit(CopilotChatApiEvents.NEW_MESSAGE, data);
	}
	function _handleInputStatusChangedEvent2(data) {
	  this.emit(CopilotChatApiEvents.INPUT_STATUS_CHANGED, data);
	}
	const CopilotChatApiEvents = {
	  INIT_CHAT: 'initChat',
	  NEW_MESSAGE: 'newMessage',
	  INPUT_STATUS_CHANGED: 'InputStatusChangedEvent'
	};
	const CopilotChatApiPullEvents = Object.freeze({
	  NEW_MESSAGE: 'newMessage',
	  INPUT_STATUS_CHANGED: 'InputStatusChanged'
	});

	var _chatInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatInstance");
	var _scenarioCode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scenarioCode");
	var _entityType$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityType");
	var _entityId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _chatOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatOptions");
	var _chatAPI = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatAPI");
	var _isHistoryFetched = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isHistoryFetched");
	var _isLoadAllMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLoadAllMessages");
	var _initChatInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initChatInstance");
	var _loadOldMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadOldMessages");
	var _initChat = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initChat");
	var _addChatMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addChatMessage");
	var _sendMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendMessage");
	var _validateOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateOptions");
	class CopilotChat extends main_core_events.EventEmitter {
	  constructor(_options) {
	    var _options$initChatExtr;
	    super(_options);
	    Object.defineProperty(this, _validateOptions, {
	      value: _validateOptions2
	    });
	    Object.defineProperty(this, _sendMessage, {
	      value: _sendMessage2
	    });
	    Object.defineProperty(this, _addChatMessage, {
	      value: _addChatMessage2
	    });
	    Object.defineProperty(this, _initChat, {
	      value: _initChat2
	    });
	    Object.defineProperty(this, _loadOldMessages, {
	      value: _loadOldMessages2
	    });
	    Object.defineProperty(this, _initChatInstance, {
	      value: _initChatInstance2
	    });
	    Object.defineProperty(this, _chatInstance, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _scenarioCode$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityType$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _chatOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _chatAPI, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isHistoryFetched, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isLoadAllMessages, {
	      writable: true,
	      value: false
	    });
	    this.setEventNamespace('AI.CopilotChat');
	    babelHelpers.classPrivateFieldLooseBase(this, _validateOptions)[_validateOptions](_options);
	    babelHelpers.classPrivateFieldLooseBase(this, _scenarioCode$1)[_scenarioCode$1] = _options.scenarioCode;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityType$1)[_entityType$1] = _options.entityType;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1] = _options.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _chatOptions)[_chatOptions] = _options.chatOptions;
	    babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI] = new CopilotChatApi({
	      chatId: _options.chatId || null,
	      scenarioCode: babelHelpers.classPrivateFieldLooseBase(this, _scenarioCode$1)[_scenarioCode$1],
	      entityType: babelHelpers.classPrivateFieldLooseBase(this, _entityType$1)[_entityType$1],
	      entityId: babelHelpers.classPrivateFieldLooseBase(this, _entityId$1)[_entityId$1],
	      initChatExtraOptions: (_options$initChatExtr = _options.initChatExtraOptions) != null ? _options$initChatExtr : {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _initChatInstance)[_initChatInstance]();
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].show();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isHistoryFetched)[_isHistoryFetched] === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initChat)[_initChat]();
	    }
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance]) == null ? void 0 : _babelHelpers$classPr.hide();
	  }
	  isShown() {
	    var _babelHelpers$classPr2;
	    return Boolean((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance]) == null ? void 0 : _babelHelpers$classPr2.isShown());
	  }
	}
	function _initChatInstance2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance] = new ai_copilotChat_ui.CopilotChat({
	    showCopilotWarningMessage: true,
	    useChatStatus: true,
	    scrollToTheEndAfterFirstShow: true,
	    ...babelHelpers.classPrivateFieldLooseBase(this, _chatOptions)[_chatOptions]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.ADD_USER_MESSAGE, async event => {
	    const message = event.getData().message;
	    babelHelpers.classPrivateFieldLooseBase(this, _sendMessage)[_sendMessage](message);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.RETRY_LOAD_HISTORY, async event => {
	    babelHelpers.classPrivateFieldLooseBase(this, _initChat)[_initChat]();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.MESSAGES_SCROLL_TOP, async () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].isOldMessagesLoading() || babelHelpers.classPrivateFieldLooseBase(this, _isLoadAllMessages)[_isLoadAllMessages]) {
	      return;
	    }
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadOldMessages)[_loadOldMessages]();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.RETRY_SEND_MESSAGE, async event => {
	    const message = babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].getMessageById(event.getData().messageId);
	    babelHelpers.classPrivateFieldLooseBase(this, _sendMessage)[_sendMessage](message);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.REMOVE_MESSAGE, event => {
	    const messageId = event.getData().messageId;
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].removeMessage(messageId);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.CLICK_ON_MESSAGE_BUTTON, async event => {
	    const messageId = event.getData().messageId;
	    const button = event.getData().button;
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].addUserMessage({
	      id: -parseInt(Math.random() * 1000, 10),
	      content: button.text,
	      type: 'ButtonClicked',
	      params: {
	        messageId,
	        buttonId: button.id
	      }
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].subscribe(CopilotChatApiEvents.INIT_CHAT, event => {
	    this.emit(CopilotChatEvents.INIT_CHAT, event);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].subscribe(CopilotChatApiEvents.NEW_MESSAGE, event => {
	    this.emit(CopilotChatEvents.NEW_MESSAGE, event);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].subscribe(CopilotChatApiEvents.NEW_MESSAGE, event => {
	    const message = event.getData().message;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].isMessageInList(message.id)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _addChatMessage)[_addChatMessage](message);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].subscribe(CopilotChatApiEvents.INPUT_STATUS_CHANGED, event => {
	    const {
	      status
	    } = event.getData();
	    switch (status) {
	      case 'Lock':
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].disableInput();
	          babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setCopilotWritingStatus(false);
	          break;
	        }
	      case 'Unlock':
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].enableInput();
	          babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setCopilotWritingStatus(false);
	          break;
	        }
	      case 'Writing':
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setCopilotWritingStatus(true);
	          babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].disableInput();
	          break;
	        }
	      default:
	        {
	          console.warn('AI.CopilotChat.Core: Unknown input status', status);
	        }
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].subscribe(ai_copilotChat_ui.CopilotChatEvents.MESSAGES_SCROLL_TOP, () => {
	    // todo make loading messages
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance];
	}
	async function _loadOldMessages2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].startLoadingOldMessages();
	  const messages = await babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].loadMessages(babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].getFirstMessageId());
	  if (messages.length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _isLoadAllMessages)[_isLoadAllMessages] = true;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].finishLoadingOldMessages();
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].unshiftMessages(messages);
	}
	async function _initChat2() {
	  try {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].isShown()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].hideLoadHistoryError();
	      babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].showLoader();
	    }
	    const data = await babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].initChatData();
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setUserAvatar(data.userPhoto);
	    const messages = data.messages;
	    messages.forEach(message => {
	      babelHelpers.classPrivateFieldLooseBase(this, _addChatMessage)[_addChatMessage](message, false);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isHistoryFetched)[_isHistoryFetched] = true;
	  } catch (e) {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].showLoadHistoryError();
	    console.error(e);
	  } finally {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].hideLoader();
	  }
	}
	function _addChatMessage2(message) {
	  var _message$params;
	  const chatMessage = {
	    authorId: message.authorId,
	    content: message.content,
	    type: message.type,
	    status: ai_copilotChat_ui.CopilotChatMessageStatus.DEPART,
	    id: message.id,
	    params: (_message$params = message.params) != null ? _message$params : []
	  };
	  if (message.isSystem === true) {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].addSystemMessage(chatMessage, false);
	  } else if (message.authorId === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].addBotMessage(chatMessage, false);
	  } else if (message.authorId > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].addUserMessage(chatMessage, false);
	  }
	}
	async function _sendMessage2(message) {
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setMessageStatusDepart(message.id);
	  babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].disableInput();
	  try {
	    var _message$params$messa, _message$params2, _message$params$butto, _message$params3;
	    const data = await babelHelpers.classPrivateFieldLooseBase(this, _chatAPI)[_chatAPI].sendMessage({
	      content: message.content,
	      messageId: (_message$params$messa = (_message$params2 = message.params) == null ? void 0 : _message$params2.messageId) != null ? _message$params$messa : null,
	      buttonId: (_message$params$butto = (_message$params3 = message.params) == null ? void 0 : _message$params3.buttonId) != null ? _message$params$butto : null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setMessageId(message.id, data.message.id);
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setMessageDate(data.message.id, data.message.dateCreate);
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setMessageStatusDelivered(data.message.id);
	  } catch {
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].setMessageStatusError(message.id);
	    babelHelpers.classPrivateFieldLooseBase(this, _chatInstance)[_chatInstance].enableInput();
	  }
	}
	function _validateOptions2(options = {}) {
	  const scenarioCode = options.scenarioCode;
	  const entityType = options.entityType;
	  const entityId = options.entityId;
	  if (main_core.Type.isStringFilled(scenarioCode) === false) {
	    throw new TypeError('scenarioCode option is required and must be the string');
	  }
	  if (main_core.Type.isStringFilled(entityType) === false) {
	    throw new TypeError('entityType option is required and must be the string');
	  }
	  if (main_core.Type.isStringFilled(entityId) === false) {
	    throw new TypeError('entityId option is required and must be the string');
	  }
	}
	const CopilotChatEvents = {
	  INIT_CHAT: 'initChat',
	  NEW_MESSAGE: 'newMessage'
	};

	exports.CopilotChat = CopilotChat;
	exports.CopilotChatEvents = CopilotChatEvents;

}((this.BX.AI.CopilotChat.Core = this.BX.AI.CopilotChat.Core || {}),BX.AI.CopilotChat.UI,BX.Event,BX,BX));
//# sourceMappingURL=copilot-chat.bundle.js.map

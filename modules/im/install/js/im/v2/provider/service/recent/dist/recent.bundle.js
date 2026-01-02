/* eslint-disable */
this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
this.BX.Messenger.v2 = this.BX.Messenger.v2 || {};
(function (exports,im_v2_lib_logger,im_v2_lib_copilot,im_v2_lib_layout,main_core,im_v2_application_core,im_v2_const) {
	'use strict';

	var _restResult = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("restResult");
	var _withBirthdays = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("withBirthdays");
	var _users = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("users");
	var _chats = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chats");
	var _messages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("messages");
	var _files = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("files");
	var _recentItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recentItems");
	var _extractUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractUser");
	var _extractChat = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractChat");
	var _extractMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractMessage");
	var _extractRecentItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractRecentItem");
	var _extractBirthdayItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractBirthdayItems");
	var _prepareGroupChat = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareGroupChat");
	var _prepareChatForUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareChatForUser");
	var _prepareChatForAdditionalUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareChatForAdditionalUser");
	var _getBirthdayPlaceholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBirthdayPlaceholder");
	var _mergeFileIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mergeFileIds");
	class RecentDataExtractor {
	  constructor(params) {
	    Object.defineProperty(this, _mergeFileIds, {
	      value: _mergeFileIds2
	    });
	    Object.defineProperty(this, _getBirthdayPlaceholder, {
	      value: _getBirthdayPlaceholder2
	    });
	    Object.defineProperty(this, _prepareChatForAdditionalUser, {
	      value: _prepareChatForAdditionalUser2
	    });
	    Object.defineProperty(this, _prepareChatForUser, {
	      value: _prepareChatForUser2
	    });
	    Object.defineProperty(this, _prepareGroupChat, {
	      value: _prepareGroupChat2
	    });
	    Object.defineProperty(this, _extractBirthdayItems, {
	      value: _extractBirthdayItems2
	    });
	    Object.defineProperty(this, _extractRecentItem, {
	      value: _extractRecentItem2
	    });
	    Object.defineProperty(this, _extractMessage, {
	      value: _extractMessage2
	    });
	    Object.defineProperty(this, _extractChat, {
	      value: _extractChat2
	    });
	    Object.defineProperty(this, _extractUser, {
	      value: _extractUser2
	    });
	    Object.defineProperty(this, _restResult, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _withBirthdays, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _users, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _chats, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _messages, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _files, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _recentItems, {
	      writable: true,
	      value: {}
	    });
	    const {
	      rawData,
	      withBirthdays = true
	    } = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _withBirthdays)[_withBirthdays] = withBirthdays;
	    babelHelpers.classPrivateFieldLooseBase(this, _restResult)[_restResult] = rawData;
	  }
	  getItems() {
	    const {
	      items = [],
	      copilot,
	      messagesAutoDeleteConfigs
	    } = babelHelpers.classPrivateFieldLooseBase(this, _restResult)[_restResult];
	    items.forEach(item => {
	      babelHelpers.classPrivateFieldLooseBase(this, _extractUser)[_extractUser](item);
	      babelHelpers.classPrivateFieldLooseBase(this, _extractChat)[_extractChat](item);
	      babelHelpers.classPrivateFieldLooseBase(this, _extractMessage)[_extractMessage](item);
	      babelHelpers.classPrivateFieldLooseBase(this, _extractRecentItem)[_extractRecentItem](item);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _extractBirthdayItems)[_extractBirthdayItems]();
	    return {
	      users: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _users)[_users]),
	      chats: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats]),
	      messages: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages]),
	      files: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _files)[_files]),
	      recentItems: Object.values(babelHelpers.classPrivateFieldLooseBase(this, _recentItems)[_recentItems]),
	      copilot,
	      messagesAutoDeleteConfigs
	    };
	  }
	}
	function _extractUser2(item) {
	  var _item$user;
	  if ((_item$user = item.user) != null && _item$user.id && !babelHelpers.classPrivateFieldLooseBase(this, _users)[_users][item.user.id]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _users)[_users][item.user.id] = item.user;
	  }
	}
	function _extractChat2(item) {
	  if (item.type === im_v2_const.ChatType.chat) {
	    babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats][item.id] = babelHelpers.classPrivateFieldLooseBase(this, _prepareGroupChat)[_prepareGroupChat](item);
	    if (item.user.id && !babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats][item.user.id]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats][item.user.id] = babelHelpers.classPrivateFieldLooseBase(this, _prepareChatForAdditionalUser)[_prepareChatForAdditionalUser](item.user);
	    }
	  } else if (item.type === im_v2_const.ChatType.user) {
	    const existingRecentItem = im_v2_application_core.Core.getStore().getters['recent/get'](item.user.id);
	    // we should not update real chat with "default" chat data
	    if (!existingRecentItem || !item.options.default_user_record) {
	      babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats][item.user.id] = babelHelpers.classPrivateFieldLooseBase(this, _prepareChatForUser)[_prepareChatForUser](item);
	    }
	  }
	}
	function _extractMessage2(item) {
	  const message = item.message;
	  if (!message) {
	    return;
	  }
	  if (message.id === 0) {
	    message.id = `${im_v2_const.FakeMessagePrefix}-${item.id}`;
	  }
	  let viewedByOthers = false;
	  if (message.status === im_v2_const.MessageStatus.delivered) {
	    viewedByOthers = true;
	  }
	  const existingMessage = im_v2_application_core.Core.getStore().getters['messages/getById'](message.id);
	  // recent has shortened attach format, we should not rewrite attach if model has it
	  if (main_core.Type.isArrayFilled(existingMessage == null ? void 0 : existingMessage.attach)) {
	    delete message.attach;
	  }
	  if (main_core.Type.isPlainObject(message.file)) {
	    const file = message.file;
	    if (existingMessage) {
	      // recent doesn't know about several files in one message,
	      // we should not rewrite message files, so we merge it.
	      message.files = babelHelpers.classPrivateFieldLooseBase(this, _mergeFileIds)[_mergeFileIds](existingMessage, file.id);
	    } else {
	      message.files = [file.id];
	    }
	    const existingFile = im_v2_application_core.Core.getStore().getters['files/get'](file.id);
	    // recent has shortened file format, we should not rewrite file if model has it
	    if (!existingFile) {
	      babelHelpers.classPrivateFieldLooseBase(this, _files)[_files][file.id] = file;
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages][message.id] = {
	    ...message,
	    viewedByOthers
	  };
	}
	function _extractRecentItem2(item) {
	  var _item$message$id, _item$message;
	  const messageId = (_item$message$id = (_item$message = item.message) == null ? void 0 : _item$message.id) != null ? _item$message$id : 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _recentItems)[_recentItems][item.id] = {
	    ...item,
	    messageId
	  };
	}
	function _extractBirthdayItems2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _withBirthdays)[_withBirthdays]) {
	    return;
	  }
	  const {
	    birthdayList = []
	  } = babelHelpers.classPrivateFieldLooseBase(this, _restResult)[_restResult];
	  birthdayList.forEach(item => {
	    if (im_v2_application_core.Core.getUserId() === item.id) {
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _users)[_users][item.id]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _users)[_users][item.id] = item;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats][item.id]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _chats)[_chats][item.id] = babelHelpers.classPrivateFieldLooseBase(this, _prepareChatForAdditionalUser)[_prepareChatForAdditionalUser](item);
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _recentItems)[_recentItems][item.id]) {
	      const messageId = `${im_v2_const.FakeMessagePrefix}-${item.id}`;
	      babelHelpers.classPrivateFieldLooseBase(this, _recentItems)[_recentItems][item.id] = {
	        ...babelHelpers.classPrivateFieldLooseBase(this, _getBirthdayPlaceholder)[_getBirthdayPlaceholder](item),
	        messageId
	      };
	      babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages][messageId] = {
	        id: messageId
	      };
	    }
	  });
	}
	function _prepareGroupChat2(item) {
	  return {
	    ...item.chat,
	    counter: item.counter,
	    dialogId: item.id
	  };
	}
	function _prepareChatForUser2(item) {
	  return {
	    chatId: item.chat_id,
	    avatar: item.user.avatar,
	    color: item.user.color,
	    dialogId: item.id,
	    name: item.user.name,
	    type: im_v2_const.ChatType.user,
	    counter: item.counter,
	    role: im_v2_const.UserRole.member,
	    backgroundId: item.chat.background_id,
	    textFieldEnabled: item.chat.text_field_enabled
	  };
	}
	function _prepareChatForAdditionalUser2(user) {
	  return {
	    dialogId: user.id,
	    avatar: user.avatar,
	    color: user.color,
	    name: user.name,
	    type: im_v2_const.ChatType.user,
	    role: im_v2_const.UserRole.member
	  };
	}
	function _getBirthdayPlaceholder2(item) {
	  return {
	    id: item.id,
	    isBirthdayPlaceholder: true
	  };
	}
	function _mergeFileIds2(existingMessage, fileId) {
	  const existingMessageFilesIds = existingMessage.files.map(id => {
	    return Number.parseInt(id, 10);
	  });
	  const setOfFileIds = new Set([...existingMessageFilesIds, fileId]);
	  return [...setOfFileIds];
	}

	class RecentService {
	  constructor() {
	    this.dataIsPreloaded = false;
	    this.firstPageIsLoaded = false;
	    this.itemsPerPage = 50;
	    this.isLoading = false;
	    this.pagesLoaded = 0;
	    this.hasMoreItemsToLoad = true;
	    this.lastMessageDate = null;
	  }
	  static getInstance() {
	    if (!this.instance) {
	      this.instance = new this();
	    }
	    return this.instance;
	  }

	  // region public
	  getCollection() {
	    return im_v2_application_core.Core.getStore().getters['recent/getRecentCollection'];
	  }
	  async loadFirstPage({
	    ignorePreloadedItems = false
	  } = {}) {
	    if (this.dataIsPreloaded && !ignorePreloadedItems) {
	      im_v2_lib_logger.Logger.warn('Im.RecentList: first page was preloaded');
	      return Promise.resolve();
	    }
	    this.isLoading = true;
	    const result = await this.requestItems({
	      firstPage: true
	    });
	    this.firstPageIsLoaded = true;
	    return result;
	  }
	  loadNextPage() {
	    if (this.isLoading || !this.hasMoreItemsToLoad) {
	      return Promise.resolve();
	    }
	    this.isLoading = true;
	    return this.requestItems();
	  }
	  setPreloadedData(params) {
	    im_v2_lib_logger.Logger.warn('Im.RecentList: setting preloaded data', params);
	    const {
	      items,
	      hasMore
	    } = params;
	    this.lastMessageDate = this.getLastMessageDate(items);
	    if (!hasMore) {
	      this.hasMoreItemsToLoad = false;
	    }
	    this.dataIsPreloaded = true;
	    void this.updateModels(params);
	  }
	  hideChat(dialogId) {
	    im_v2_lib_logger.Logger.warn('Im.RecentList: hide chat', dialogId);
	    const recentItem = im_v2_application_core.Core.getStore().getters['recent/get'](dialogId);
	    if (!recentItem) {
	      return;
	    }
	    void im_v2_application_core.Core.getStore().dispatch('recent/delete', {
	      id: dialogId
	    });
	    const chatIsOpened = im_v2_application_core.Core.getStore().getters['application/isChatOpen'](dialogId);
	    if (chatIsOpened) {
	      im_v2_lib_layout.LayoutManager.getInstance().clearCurrentLayoutEntityId();
	      void im_v2_lib_layout.LayoutManager.getInstance().deleteLastOpenedElementById(dialogId);
	    }
	    im_v2_application_core.Core.getRestClient().callMethod(im_v2_const.RestMethod.imRecentHide, {
	      DIALOG_ID: dialogId
	    }).catch(result => {
	      console.error('Im.RecentList: hide chat error', result.error());
	    });
	  }
	  // endregion public

	  async requestItems({
	    firstPage = false
	  } = {}) {
	    const queryParams = this.getQueryParams(firstPage);
	    const result = await im_v2_application_core.Core.getRestClient().callMethod(this.getQueryMethod(), queryParams).catch(errorResult => {
	      console.error('Im.RecentList: page request error', errorResult.error());
	    });
	    this.pagesLoaded++;
	    im_v2_lib_logger.Logger.warn(`Im.RecentList: ${firstPage ? 'First' : this.pagesLoaded} page request result`, result.data());
	    const {
	      items,
	      hasMore
	    } = result.data();
	    this.lastMessageDate = this.getLastMessageDate(items);
	    if (!hasMore) {
	      this.hasMoreItemsToLoad = false;
	    }
	    this.isLoading = false;
	    return this.updateModels(result.data());
	  }
	  getQueryMethod() {
	    return im_v2_const.RestMethod.imRecentList;
	  }
	  getQueryParams(firstPage) {
	    return {
	      SKIP_OPENLINES: 'Y',
	      LIMIT: this.itemsPerPage,
	      LAST_MESSAGE_DATE: firstPage ? null : this.lastMessageDate,
	      GET_ORIGINAL_TEXT: 'Y',
	      PARSE_TEXT: 'Y'
	    };
	  }
	  getModelSaveMethod() {
	    return 'recent/setRecent';
	  }
	  updateModels(rawData) {
	    const extractor = new RecentDataExtractor({
	      rawData,
	      ...this.getExtractorOptions()
	    });
	    const extractedItems = extractor.getItems();
	    const {
	      users,
	      chats,
	      messages,
	      files,
	      recentItems,
	      copilot,
	      messagesAutoDeleteConfigs
	    } = extractedItems;
	    im_v2_lib_logger.Logger.warn('RecentService: prepared data for models', extractedItems);
	    const usersPromise = im_v2_application_core.Core.getStore().dispatch('users/set', users);
	    const dialoguesPromise = im_v2_application_core.Core.getStore().dispatch('chats/set', chats);
	    const autoDeletePromise = im_v2_application_core.Core.getStore().dispatch('chats/autoDelete/set', messagesAutoDeleteConfigs);
	    const messagesPromise = im_v2_application_core.Core.getStore().dispatch('messages/store', messages);
	    const filesPromise = im_v2_application_core.Core.getStore().dispatch('files/set', files);
	    const recentPromise = im_v2_application_core.Core.getStore().dispatch(this.getModelSaveMethod(), recentItems);
	    const copilotManager = new im_v2_lib_copilot.CopilotManager();
	    const copilotPromise = copilotManager.handleRecentListResponse(copilot);
	    return Promise.all([usersPromise, dialoguesPromise, messagesPromise, filesPromise, recentPromise, copilotPromise, autoDeletePromise]);
	  }
	  getLastMessageDate(items) {
	    if (items.length === 0) {
	      return '';
	    }
	    return items.slice(-1)[0].message.date;
	  }
	  getExtractorOptions() {
	    return {};
	  }
	}
	RecentService.instance = null;

	exports.RecentService = RecentService;

}((this.BX.Messenger.v2.Service = this.BX.Messenger.v2.Service || {}),BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX,BX.Messenger.v2.Application,BX.Messenger.v2.Const));
//# sourceMappingURL=recent.bundle.js.map

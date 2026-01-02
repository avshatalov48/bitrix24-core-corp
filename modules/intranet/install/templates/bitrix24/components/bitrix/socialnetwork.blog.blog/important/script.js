/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_loader,main_core_events) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _widgetContainerId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("widgetContainerId");
	var _messages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("messages");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _postInfo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("postInfo");
	var _url = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("url");
	var _pageSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageSettings");
	var _activeMessageIndex = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activeMessageIndex");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _loaderOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaderOverlay");
	var _lockButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lockButtons");
	var _needLoadNewMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needLoadNewMessages");
	var _loadNewMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadNewMessages");
	var _fetchNewMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchNewMessages");
	var _renderMessages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderMessages");
	var _renderMessageElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderMessageElement");
	var _handleClickOnNextBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleClickOnNextBtn");
	var _showNextMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNextMessage");
	var _showPrevMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showPrevMessage");
	var _readMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("readMessage");
	var _setPageSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setPageSettings");
	var _hasNextMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasNextMessage");
	var _getNextMessageIndex = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNextMessageIndex");
	var _hasPrevMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hasPrevMessage");
	var _getPrevMessageIndex = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPrevMessageIndex");
	var _removeMessageFromList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeMessageFromList");
	var _getActiveMessageElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getActiveMessageElement");
	var _getMessageById = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMessageById");
	var _getMessagesListContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMessagesListContainer");
	var _getReadMessageButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getReadMessageButton");
	var _getWidgetContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetContainer");
	var _updateCurrentMessageNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCurrentMessageNumber");
	var _updateTotalMessagesNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateTotalMessagesNumber");
	var _getTotalMessagesNumberContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTotalMessagesNumberContainer");
	var _getActiveMessageIndex = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getActiveMessageIndex");
	var _getPrevMessageButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPrevMessageButton");
	var _getNextMessageButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNextMessageButton");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _enableNextButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableNextButton");
	var _disableNextButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableNextButton");
	var _enablePrevButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enablePrevButton");
	var _disablePrevButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disablePrevButton");
	class ImportantMessagesWidget {
	  constructor(_options2) {
	    Object.defineProperty(this, _disablePrevButton, {
	      value: _disablePrevButton2
	    });
	    Object.defineProperty(this, _enablePrevButton, {
	      value: _enablePrevButton2
	    });
	    Object.defineProperty(this, _disableNextButton, {
	      value: _disableNextButton2
	    });
	    Object.defineProperty(this, _enableNextButton, {
	      value: _enableNextButton2
	    });
	    Object.defineProperty(this, _hideLoader, {
	      value: _hideLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _getNextMessageButton, {
	      value: _getNextMessageButton2
	    });
	    Object.defineProperty(this, _getPrevMessageButton, {
	      value: _getPrevMessageButton2
	    });
	    Object.defineProperty(this, _getActiveMessageIndex, {
	      value: _getActiveMessageIndex2
	    });
	    Object.defineProperty(this, _getTotalMessagesNumberContainer, {
	      value: _getTotalMessagesNumberContainer2
	    });
	    Object.defineProperty(this, _updateTotalMessagesNumber, {
	      value: _updateTotalMessagesNumber2
	    });
	    Object.defineProperty(this, _updateCurrentMessageNumber, {
	      value: _updateCurrentMessageNumber2
	    });
	    Object.defineProperty(this, _getWidgetContainer, {
	      value: _getWidgetContainer2
	    });
	    Object.defineProperty(this, _getReadMessageButton, {
	      value: _getReadMessageButton2
	    });
	    Object.defineProperty(this, _getMessagesListContainer, {
	      value: _getMessagesListContainer2
	    });
	    Object.defineProperty(this, _getMessageById, {
	      value: _getMessageById2
	    });
	    Object.defineProperty(this, _getActiveMessageElement, {
	      value: _getActiveMessageElement2
	    });
	    Object.defineProperty(this, _removeMessageFromList, {
	      value: _removeMessageFromList2
	    });
	    Object.defineProperty(this, _getPrevMessageIndex, {
	      value: _getPrevMessageIndex2
	    });
	    Object.defineProperty(this, _hasPrevMessage, {
	      value: _hasPrevMessage2
	    });
	    Object.defineProperty(this, _getNextMessageIndex, {
	      value: _getNextMessageIndex2
	    });
	    Object.defineProperty(this, _hasNextMessage, {
	      value: _hasNextMessage2
	    });
	    Object.defineProperty(this, _setPageSettings, {
	      value: _setPageSettings2
	    });
	    Object.defineProperty(this, _readMessage, {
	      value: _readMessage2
	    });
	    Object.defineProperty(this, _showPrevMessage, {
	      value: _showPrevMessage2
	    });
	    Object.defineProperty(this, _showNextMessage, {
	      value: _showNextMessage2
	    });
	    Object.defineProperty(this, _handleClickOnNextBtn, {
	      value: _handleClickOnNextBtn2
	    });
	    Object.defineProperty(this, _renderMessageElement, {
	      value: _renderMessageElement2
	    });
	    Object.defineProperty(this, _renderMessages, {
	      value: _renderMessages2
	    });
	    Object.defineProperty(this, _fetchNewMessages, {
	      value: _fetchNewMessages2
	    });
	    Object.defineProperty(this, _loadNewMessages, {
	      value: _loadNewMessages2
	    });
	    Object.defineProperty(this, _needLoadNewMessages, {
	      value: _needLoadNewMessages2
	    });
	    Object.defineProperty(this, _widgetContainerId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _messages, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _postInfo, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _url, {
	      writable: true,
	      value: ''
	    });
	    Object.defineProperty(this, _pageSettings, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _activeMessageIndex, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loaderOverlay, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _lockButtons, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _widgetContainerId)[_widgetContainerId] = _options2.widgetContainerId;
	    babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages] = _options2.messages;
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = _options2.options;
	    babelHelpers.classPrivateFieldLooseBase(this, _postInfo)[_postInfo] = _options2.postInfo;
	    babelHelpers.classPrivateFieldLooseBase(this, _url)[_url] = _options2.url;
	    babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings] = _options2.pageSettings;
	    babelHelpers.classPrivateFieldLooseBase(this, _postInfo)[_postInfo].AJAX_POST = 'Y';
	    this.init();
	  }
	  init() {
	    babelHelpers.classPrivateFieldLooseBase(this, _renderMessages)[_renderMessages]();
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getNextMessageButton)[_getNextMessageButton](), 'click', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons]) {
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _handleClickOnNextBtn)[_handleClickOnNextBtn]().then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons] = false;
	        this.updateNavigation();
	      }).catch(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons] = false;
	      });
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getPrevMessageButton)[_getPrevMessageButton](), 'click', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons]) {
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _showPrevMessage)[_showPrevMessage]().then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons] = false;
	        this.updateNavigation();
	      }).catch(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _lockButtons)[_lockButtons] = false;
	      });
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getReadMessageButton)[_getReadMessageButton](), 'click', () => {
	      const activeMessage = this.getActiveMessage();
	      babelHelpers.classPrivateFieldLooseBase(this, _readMessage)[_readMessage](activeMessage.id);
	    });
	    main_core_events.EventEmitter.subscribe('onImportantPostRead', event => {
	      const [messageId] = event.getData();
	      babelHelpers.classPrivateFieldLooseBase(this, _removeMessageFromList)[_removeMessageFromList](messageId);
	    });
	    const loadNewMessages = main_core.Runtime.debounce(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadNewMessages)[_loadNewMessages]().then(() => {
	        this.updateNavigation();
	        this.show();
	      }).catch(() => {
	        // fail silently
	      });
	    }, 6000);
	    main_core_events.EventEmitter.subscribe('onPullEvent-main', event => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length > 0) {
	        return;
	      }
	      const [command, params] = event.getCompatData();
	      if (command === 'user_counter' && params[main_core.Loc.getMessage('SITE_ID')] && params[main_core.Loc.getMessage('SITE_ID')]['BLOG_POST_IMPORTANT']) {
	        loadNewMessages();
	      }
	    });
	    this.updateNavigation();
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length === 0) {
	      return;
	    }
	    const activeMessage = this.getActiveMessage();
	    if (!activeMessage) {
	      const message = babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages][babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex]] || null;
	      const element = babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().querySelector(`[data-message-id="${message == null ? void 0 : message.id}"]`);
	      if (element) {
	        main_core.Dom.addClass(element, '--active');
	      }
	    }
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetContainer)[_getWidgetContainer](), ['--hidden', '--hiding']);
	  }
	  hide() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetContainer)[_getWidgetContainer](), '--hiding');
	    main_core.Event.bindOnce(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetContainer)[_getWidgetContainer](), 'transitionend', () => {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetContainer)[_getWidgetContainer](), '--hiding');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getWidgetContainer)[_getWidgetContainer](), '--hidden');
	    });
	  }
	  getActiveMessage() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages][babelHelpers.classPrivateFieldLooseBase(this, _getActiveMessageIndex)[_getActiveMessageIndex]()] || null;
	  }
	  updateNavigation() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] >= babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavRecordCount - 1) {
	      babelHelpers.classPrivateFieldLooseBase(this, _disableNextButton)[_disableNextButton]();
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavRecordCount > 1) {
	      babelHelpers.classPrivateFieldLooseBase(this, _enableNextButton)[_enableNextButton]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _disableNextButton)[_disableNextButton]();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] === 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _disablePrevButton)[_disablePrevButton]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _enablePrevButton)[_enablePrevButton]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _updateCurrentMessageNumber)[_updateCurrentMessageNumber]();
	    babelHelpers.classPrivateFieldLooseBase(this, _updateTotalMessagesNumber)[_updateTotalMessagesNumber]();
	  }
	}
	function _needLoadNewMessages2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].bDescPageNumbering === true && babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavPageNomer > 1 || babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length < babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavRecordCount;
	}
	async function _loadNewMessages2() {
	  const data = await babelHelpers.classPrivateFieldLooseBase(this, _fetchNewMessages)[_fetchNewMessages]();
	  const messages = data.messages;
	  babelHelpers.classPrivateFieldLooseBase(this, _setPageSettings)[_setPageSettings](data.pageSettings);
	  for (const message of messages) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getMessageById)[_getMessageById](message.id)) {
	      continue;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].push(message);
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderMessageElement)[_renderMessageElement](message, false), babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]());
	  }
	}
	function _fetchNewMessages2() {
	  return new Promise((resolve, reject) => {
	    const request = babelHelpers.classPrivateFieldLooseBase(this, _postInfo)[_postInfo];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].bDescPageNumbering) {
	      babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].iNumPage = babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavPageNomer - 1;
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].iNumPage = babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavPageNomer + 1;
	    }
	    request.page_settings = babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings];
	    request.sessid = BX.bitrix_sessid();
	    main_core.ajax({
	      method: 'POST',
	      processData: true,
	      url: babelHelpers.classPrivateFieldLooseBase(this, _url)[_url],
	      data: request,
	      onsuccess: response => {
	        const data = JSON.parse(response);
	        resolve({
	          messages: data.data,
	          pageSettings: data.page_settings
	        });
	      },
	      onfailure: error => {
	        reject(error);
	      }
	    });
	  });
	}
	function _renderMessages2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].forEach((message, index) => {
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderMessageElement)[_renderMessageElement](message, index === 0), babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]());
	  });
	}
	function _renderMessageElement2(messageData, isActive) {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="sidebar-imp-mess ${0}" data-message-id="${0}">
				<a href="${0}" class="sidebar-imp-mess-wrap">
					<div class="sidebar-imp-mess-avatar-block">
						<div class="sidebar-imp-mess-author-avatar ui-icon ui-icon-common-user"><i ${0}></i></div>
					</div>
					<div class="sidebar-imp-mess-info">
						<div class="sidebar-imp-mess-title">${0}</div>
						<div class="sidebar-imp-mess-text">${0}</div>
					</div>
				</a>
			</div>
		`), isActive ? '--active' : '', messageData.id, messageData.post_url, messageData.author_avatar, messageData.author_name, messageData.post_text);
	}
	async function _handleClickOnNextBtn2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _hasNextMessage)[_hasNextMessage]() && babelHelpers.classPrivateFieldLooseBase(this, _needLoadNewMessages)[_needLoadNewMessages]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadNewMessages)[_loadNewMessages]();
	    babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _showNextMessage)[_showNextMessage]().then(() => {
	    this.updateNavigation();
	  }).catch(() => {});
	}
	async function _showNextMessage2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _hasNextMessage)[_hasNextMessage]()) {
	    return Promise.resolve();
	  }
	  return new Promise((resolve, reject) => {
	    const activeMessage = babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().querySelector('.sidebar-imp-mess.--active');
	    const nextMessage = activeMessage.nextElementSibling || babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().firstElementChild;
	    babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] = babelHelpers.classPrivateFieldLooseBase(this, _getNextMessageIndex)[_getNextMessageIndex]();
	    main_core.Dom.addClass(activeMessage, '--slide-out-left');
	    main_core.Dom.addClass(nextMessage, '--active');
	    main_core.Dom.addClass(nextMessage, '--slide-in-right');
	    main_core.Event.bindOnce(activeMessage, 'animationend', () => {
	      main_core.Dom.removeClass(activeMessage, '--active');
	      main_core.Dom.removeClass(activeMessage, '--slide-out-left');
	      main_core.Dom.removeClass(nextMessage, '--slide-in-right');
	      resolve();
	    });
	  });
	}
	function _showPrevMessage2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _hasPrevMessage)[_hasPrevMessage]()) {
	    return Promise.resolve();
	  }
	  return new Promise((resolve, reject) => {
	    const activeMessage = babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().querySelector('.sidebar-imp-mess.--active');
	    const prevMessage = activeMessage.previousElementSibling || babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().lastElementChild;
	    babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] = babelHelpers.classPrivateFieldLooseBase(this, _getPrevMessageIndex)[_getPrevMessageIndex]();
	    main_core.Dom.addClass(activeMessage, '--slide-out-right');
	    main_core.Dom.addClass(prevMessage, '--active');
	    main_core.Dom.addClass(prevMessage, '--slide-in-left');
	    main_core.Event.bindOnce(activeMessage, 'animationend', () => {
	      main_core.Dom.removeClass(activeMessage, '--active');
	      main_core.Dom.removeClass(activeMessage, '--slide-out-right');
	      main_core.Dom.removeClass(prevMessage, '--slide-in-left');
	      resolve();
	    });
	  });
	}
	async function _readMessage2(messageId) {
	  const data = babelHelpers.classPrivateFieldLooseBase(this, _getMessageById)[_getMessageById](messageId);
	  const options = [];
	  for (const option of babelHelpers.classPrivateFieldLooseBase(this, _options)[_options]) {
	    options.push({
	      post_id: data.id,
	      name: option.name,
	      value: option.value
	    });
	  }
	  let request = babelHelpers.classPrivateFieldLooseBase(this, _postInfo)[_postInfo];
	  request.options = options;
	  request.page_settings = babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings];
	  request.sessid = BX.bitrix_sessid();
	  request = main_core.ajax.prepareData(request);
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _hasNextMessage)[_hasNextMessage]() && babelHelpers.classPrivateFieldLooseBase(this, _needLoadNewMessages)[_needLoadNewMessages]()) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadNewMessages)[_loadNewMessages]();
	  }
	  main_core.ajax({
	    method: 'GET',
	    url: babelHelpers.classPrivateFieldLooseBase(this, _url)[_url] + (babelHelpers.classPrivateFieldLooseBase(this, _url)[_url].includes('?') ? '&' : '?') + request,
	    onsuccess: response => {
	      const data = JSON.parse(response);
	      babelHelpers.classPrivateFieldLooseBase(this, _removeMessageFromList)[_removeMessageFromList](messageId, data.page_settings);
	      babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	    },
	    onfailure: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	    }
	  });
	}
	function _setPageSettings2(pageSettings) {
	  babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings] = pageSettings;
	}
	function _hasNextMessage2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] + 1 <= babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length - 1;
	}
	function _getNextMessageIndex2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] + 1 > babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length - 1 ? 0 : babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] + 1;
	}
	function _hasPrevMessage2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] - 1 >= 0;
	}
	function _getPrevMessageIndex2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] - 1 < 0 ? babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length - 1 : babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] - 1;
	}
	function _removeMessageFromList2(messageId, pageSettings = null) {
	  const finalize = () => {
	    const activeMessage = this.getActiveMessage();
	    main_core.Dom.remove(messageElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages] = babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].filter(message => message.id !== messageId);
	    babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] = Math.max(0, babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].findIndex(message => message.id === activeMessage.id));
	    if (pageSettings === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setPageSettings)[_setPageSettings]({
	        ...babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings],
	        NavRecordCount: Math.max(0, babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavRecordCount - 1)
	      });
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _setPageSettings)[_setPageSettings](pageSettings);
	    }
	    this.updateNavigation();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].length === 0) {
	      this.hide();
	    }
	  };
	  const messageElement = babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().querySelector(`[data-message-id="${messageId}"]`);
	  const activeMessageElement = babelHelpers.classPrivateFieldLooseBase(this, _getActiveMessageElement)[_getActiveMessageElement]();
	  if (messageElement === activeMessageElement) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _hasNextMessage)[_hasNextMessage]()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showNextMessage)[_showNextMessage]().then(finalize).catch(() => {});
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _hasPrevMessage)[_hasPrevMessage]()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showPrevMessage)[_showPrevMessage]().then(finalize).catch(() => {});
	    } else {
	      finalize();
	    }
	  } else {
	    finalize();
	  }
	}
	function _getActiveMessageElement2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().querySelector('.--active');
	}
	function _getMessageById2(messageId) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].find(message => message.id === messageId);
	}
	function _getMessagesListContainer2() {
	  return document.getElementById('sidebar-imp-mess-list');
	}
	function _getReadMessageButton2() {
	  return document.getElementById('sidebar-imp-mess-read-button');
	}
	function _getWidgetContainer2() {
	  return document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _widgetContainerId)[_widgetContainerId]);
	}
	function _updateCurrentMessageNumber2() {
	  const currentMessageNumberContainer = document.getElementById('sidebar-imp-mess-current-mess-number');
	  currentMessageNumberContainer.textContent = babelHelpers.classPrivateFieldLooseBase(this, _activeMessageIndex)[_activeMessageIndex] + 1;
	}
	function _updateTotalMessagesNumber2() {
	  const totalMessagesNumberContainer = babelHelpers.classPrivateFieldLooseBase(this, _getTotalMessagesNumberContainer)[_getTotalMessagesNumberContainer]();
	  totalMessagesNumberContainer.textContent = babelHelpers.classPrivateFieldLooseBase(this, _pageSettings)[_pageSettings].NavRecordCount;
	}
	function _getTotalMessagesNumberContainer2() {
	  return document.getElementById('sidebar-imp-mess-total');
	}
	function _getActiveMessageIndex2() {
	  return [...babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().children].indexOf(babelHelpers.classPrivateFieldLooseBase(this, _getMessagesListContainer)[_getMessagesListContainer]().querySelector('.--active'));
	}
	function _getPrevMessageButton2() {
	  return document.getElementById('sidebar-imp-mess-prev');
	}
	function _getNextMessageButton2() {
	  return document.getElementById('sidebar-imp-mess-next');
	}
	function _showLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay] = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sidebar-widget-content-overlay"></div>
		`));
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay], {
	    position: 'absolute',
	    top: 0,
	    left: 0,
	    width: '100%',
	    height: '100%',
	    background: 'rgba(255, 255, 255, 0.7)'
	  });
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay], babelHelpers.classPrivateFieldLooseBase(this, _getWidgetContainer)[_getWidgetContainer]());
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      size: 60,
	      target: babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay],
	      color: '#0154C8'
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show();
	}
	function _hideLoader2() {
	  var _babelHelpers$classPr;
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader]) == null ? void 0 : _babelHelpers$classPr.hide();
	  main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay]);
	}
	function _enableNextButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getNextMessageButton)[_getNextMessageButton]().removeAttribute('disabled');
	}
	function _disableNextButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getNextMessageButton)[_getNextMessageButton]().setAttribute('disabled', true);
	}
	function _enablePrevButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getPrevMessageButton)[_getPrevMessageButton]().removeAttribute('disabled');
	}
	function _disablePrevButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getPrevMessageButton)[_getPrevMessageButton]().setAttribute('disabled', true);
	}

	exports.ImportantMessagesWidget = ImportantMessagesWidget;

}((this.BX.Intranet = this.BX.Intranet || {}),BX,BX,BX.Event));
//# sourceMappingURL=script.js.map

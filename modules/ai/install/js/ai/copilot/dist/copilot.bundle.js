/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ai_engine,ui_designTokens,ui_iconSet_editor,ui_iconSet_crm,ui_label,main_loader,ai_speechConverter,ui_hint,ui_iconSet_main,ui_iconSet_actions,ui_feedback_form,ui_lottie,ai_copilot,ai_copilot_copilotTextController,main_core_events,main_popup,ui_iconSet_api_core,main_core,ai_ajaxErrorHandler) {
	'use strict';

	async function checkCopilotAgreement(options) {
	  try {
	    const copilotAgreement = await initCopilotAgreement(options);
	    return copilotAgreement.checkAgreement();
	  } catch (e) {
	    console.error(e);
	    return true;
	  }
	}
	async function initCopilotAgreement(options) {
	  try {
	    const {
	      CopilotAgreement
	    } = await main_core.Runtime.loadExtension('ai.copilot-agreement');
	    return new CopilotAgreement(options);
	  } catch (e) {
	    console.error(e);
	    return null;
	  }
	}

	const Categories = Object.freeze({
	  text: 'text_operations',
	  image: 'image_operations',
	  readonly: 'read_operations',
	  promptSaving: 'prompt_saving'
	});
	const Types = Object.freeze({
	  textNew: 'create_new',
	  textReply: 'reply_context',
	  textEdit: 'edit_context',
	  imageNew: 'create_image'
	});
	const ContextSubSections = Object.freeze({
	  fromText: 'from_text',
	  fromAudio: 'audio_used',
	  fromTextAndAudio: 'from_text+audio_used'
	});
	const ContextElements = Object.freeze({
	  editorButton: 'editor_button',
	  spaceButton: 'space_button',
	  popupButton: 'popup_button',
	  readonlyCommon: 'common',
	  readonlyQuote: 'quote'
	});
	const Events = Object.freeze({
	  open: 'open',
	  generate: 'generate',
	  success: 'success',
	  error: 'error',
	  saveResult: 'save',
	  cancelResult: 'cancel',
	  editResult: 'edit',
	  copyResult: 'copy_text',
	  openPromptsLibrary: 'open_list'
	});
	var _tool = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tool");
	var _category = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _event = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("event");
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _c_section = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("c_section");
	var _c_sub_section = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("c_sub_section");
	var _c_element = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("c_element");
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _setCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setCategory");
	var _setType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setType");
	var _setContextSubSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setContextSubSection");
	var _setContextElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setContextElement");
	var _sendData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendData");
	var _getData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getData");
	class CopilotAnalytics {
	  constructor() {
	    Object.defineProperty(this, _getData, {
	      value: _getData2
	    });
	    Object.defineProperty(this, _sendData, {
	      value: _sendData2
	    });
	    Object.defineProperty(this, _setContextElement, {
	      value: _setContextElement2
	    });
	    Object.defineProperty(this, _setContextSubSection, {
	      value: _setContextSubSection2
	    });
	    Object.defineProperty(this, _setType, {
	      value: _setType2
	    });
	    Object.defineProperty(this, _setCategory, {
	      value: _setCategory2
	    });
	    Object.defineProperty(this, _tool, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _category, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _event, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _c_section, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _c_sub_section, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _c_element, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool] = 'AI';
	    babelHelpers.classPrivateFieldLooseBase(this, _category)[_category] = '';
	  }
	  getCategory() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _category)[_category];
	  }
	  getType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _type)[_type];
	  }
	  getCSection() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _c_section)[_c_section];
	  }
	  getCSubSection() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _c_sub_section)[_c_sub_section];
	  }
	  getCElement() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _c_element)[_c_element];
	  }

	  // region Set category
	  setCategoryText() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setCategory)[_setCategory](Categories.text);
	  }
	  setCategoryReadonly() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setCategory)[_setCategory](Categories.readonly);
	  }
	  setCategoryImage() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setCategory)[_setCategory](Categories.image);
	  }
	  setCategoryPromptSaving() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setCategory)[_setCategory](Categories.promptSaving);
	  }
	  // endregion

	  // region Set type
	  setTypeTextNew() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setType)[_setType](Types.textNew);
	  }
	  setTypeTextReply() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setType)[_setType](Types.textReply);
	  }
	  setTypeTextEdit() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setType)[_setType](Types.textEdit);
	  }
	  setTypeImageNew() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setType)[_setType](Types.imageNew);
	  }
	  // endregion

	  // region Set c_section
	  setContextSection(cSection) {
	    if (cSection.length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _c_section)[_c_section] = cSection;
	    }
	    return this;
	  }
	  // endregion

	  // region Set c_sub_section
	  setContextTypeFromText() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextSubSection)[_setContextSubSection](ContextSubSections.fromText);
	  }
	  setContextTypeFromAudio() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextSubSection)[_setContextSubSection](ContextSubSections.fromAudio);
	  }
	  setContextTypeFromTextAndAudio() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextSubSection)[_setContextSubSection](ContextSubSections.fromTextAndAudio);
	  }
	  // endregion

	  // region Set c_element
	  setContextElementEditorButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextElement)[_setContextElement](ContextElements.editorButton);
	  }
	  setContextElementSpaceButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextElement)[_setContextElement](ContextElements.spaceButton);
	  }
	  setContextElementReadonlyCommon() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextElement)[_setContextElement](ContextElements.readonlyCommon);
	  }
	  setContextElementReadonlyQuote() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextElement)[_setContextElement](ContextElements.readonlyQuote);
	  }
	  setContextElementPopupButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _setContextElement)[_setContextElement](ContextElements.popupButton);
	  }
	  // endregion

	  // region Set params
	  setP1(name, value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][0] = {
	      name,
	      value
	    };
	    return this;
	  }
	  setP2(name, value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][1] = {
	      name,
	      value
	    };
	    return this;
	  }
	  setP3(name, value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][2] = {
	      name,
	      value
	    };
	    return this;
	  }
	  setP4(name, value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][3] = {
	      name,
	      value
	    };
	    return this;
	  }
	  setP5(name, value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][4] = {
	      name,
	      value
	    };
	    return this;
	  }

	  // endregion

	  // region Set event and Send
	  sendEventOpen(status) {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.open;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData](status);
	  }
	  sendEventGenerate() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.generate;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventSuccess() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.success;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventError() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.error;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventSave() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.saveResult;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventCancel() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.cancelResult;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventOpenPromptLibrary() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.openPromptsLibrary;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventCopyResult() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.copyResult;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }
	  sendEventEditResult() {
	    babelHelpers.classPrivateFieldLooseBase(this, _event)[_event] = Events.editResult;
	    return babelHelpers.classPrivateFieldLooseBase(this, _sendData)[_sendData]();
	  }

	  // endregion
	}
	function _setCategory2(category) {
	  if (Object.values(Categories).includes(category)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _category)[_category] = category;
	  }
	  return this;
	}
	function _setType2(type) {
	  if (Object.values(Types).includes(type)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = type;
	  }
	  return this;
	}
	function _setContextSubSection2(subSection) {
	  if (Object.values(ContextSubSections).includes(subSection)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _c_sub_section)[_c_sub_section] = subSection;
	  }
	  return this;
	}
	function _setContextElement2(cElement) {
	  if (Object.values(ContextElements).includes(cElement)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _c_element)[_c_element] = cElement;
	  }
	  return this;
	}
	function _sendData2(status) {
	  let data = babelHelpers.classPrivateFieldLooseBase(this, _getData)[_getData]();
	  if (!data) {
	    return;
	  }
	  if (status) {
	    data = {
	      ...data,
	      status
	    };
	  }
	  main_core.Runtime.loadExtension('ui.analytics').then(({
	    sendData
	  }) => {
	    sendData(data);
	  }).catch(() => {
	    console.error("AI: Copilot: can't load ui.analytics");
	  });
	}
	function _getData2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool] || !babelHelpers.classPrivateFieldLooseBase(this, _category)[_category] || !babelHelpers.classPrivateFieldLooseBase(this, _event)[_event]) {
	    return null;
	  }
	  const data = {
	    tool: babelHelpers.classPrivateFieldLooseBase(this, _tool)[_tool],
	    category: babelHelpers.classPrivateFieldLooseBase(this, _category)[_category],
	    event: babelHelpers.classPrivateFieldLooseBase(this, _event)[_event]
	  };
	  if (babelHelpers.classPrivateFieldLooseBase(this, _type)[_type]) {
	    data.type = babelHelpers.classPrivateFieldLooseBase(this, _type)[_type];
	  }

	  // non required
	  if (babelHelpers.classPrivateFieldLooseBase(this, _c_section)[_c_section]) {
	    data.c_section = babelHelpers.classPrivateFieldLooseBase(this, _c_section)[_c_section];
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _c_sub_section)[_c_sub_section]) {
	    data.c_sub_section = babelHelpers.classPrivateFieldLooseBase(this, _c_sub_section)[_c_sub_section];
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _c_element)[_c_element]) {
	    data.c_element = babelHelpers.classPrivateFieldLooseBase(this, _c_element)[_c_element];
	  }

	  // params
	  if (babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] && main_core.Type.isArray(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].forEach((param, index) => {
	      if (!(param != null && param.value) || !(param != null && param.name)) {
	        return;
	      }
	      const {
	        name,
	        value
	      } = param;
	      const key = `p${index + 1}`;
	      data[key] = `${main_core.Text.toCamelCase(name)}_${main_core.Text.toCamelCase(value)}`;
	    });
	  }
	  return data;
	}

	const highlightedMenuItemClassname = '--highlight';
	const KeyboardMenuEvents = Object.freeze({
	  highlightMenuItem: 'highlightMenuItem',
	  clearHighlight: 'clearHighlight'
	});
	var _menu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _openMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openMenu");
	var _highlightedMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightedMenuItem");
	var _clearHighlightAfterType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearHighlightAfterType");
	var _highlightFirstItemAfterShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightFirstItemAfterShow");
	var _canGoOutFromTop = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canGoOutFromTop");
	var _keyDownHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("keyDownHandler");
	var _menuItemMouseEnterHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemMouseEnterHandler");
	var _menuItemMouseLeaveHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemMouseLeaveHandler");
	var _menuItemSubmenuOnShowHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemSubmenuOnShowHandler");
	var _menuItemSubmenuOnCloseHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemSubmenuOnCloseHandler");
	var _observeMenuChanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observeMenuChanges");
	var _handleKeyDown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleKeyDown");
	var _handleEnterKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleEnterKey");
	var _handleArrowUpKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleArrowUpKey");
	var _handleArrowDownKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleArrowDownKey");
	var _handleArrowLeftKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleArrowLeftKey");
	var _handleArrowRightKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleArrowRightKey");
	var _showActiveItemSubmenuIfExist = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showActiveItemSubmenuIfExist");
	var _handleMenuItemEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemEvents");
	var _unsubscribeMenuItemEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsubscribeMenuItemEvents");
	var _handleMenuItemMouseEnter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemMouseEnter");
	var _handleMenuItemMouseLeave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemMouseLeave");
	var _handleMenuItemSubmenuOnShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemSubmenuOnShow");
	var _handleMenuItemSubmenuOnClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemSubmenuOnClose");
	var _highlightMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightMenuItem");
	var _getMenuItemBeforeHighlighted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemBeforeHighlighted");
	var _getMenuItemAfterHighlighted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemAfterHighlighted");
	var _getHighlightedMenuItemPosition = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHighlightedMenuItemPosition");
	var _clearMenuItemHighlight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearMenuItemHighlight");
	var _clearHighlight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clearHighlight");
	var _scrollToActiveElem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollToActiveElem");
	class KeyboardMenu extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _scrollToActiveElem, {
	      value: _scrollToActiveElem2
	    });
	    Object.defineProperty(this, _clearHighlight, {
	      value: _clearHighlight2
	    });
	    Object.defineProperty(this, _clearMenuItemHighlight, {
	      value: _clearMenuItemHighlight2
	    });
	    Object.defineProperty(this, _getHighlightedMenuItemPosition, {
	      value: _getHighlightedMenuItemPosition2
	    });
	    Object.defineProperty(this, _getMenuItemAfterHighlighted, {
	      value: _getMenuItemAfterHighlighted2
	    });
	    Object.defineProperty(this, _getMenuItemBeforeHighlighted, {
	      value: _getMenuItemBeforeHighlighted2
	    });
	    Object.defineProperty(this, _highlightMenuItem, {
	      value: _highlightMenuItem2
	    });
	    Object.defineProperty(this, _handleMenuItemSubmenuOnClose, {
	      value: _handleMenuItemSubmenuOnClose2
	    });
	    Object.defineProperty(this, _handleMenuItemSubmenuOnShow, {
	      value: _handleMenuItemSubmenuOnShow2
	    });
	    Object.defineProperty(this, _handleMenuItemMouseLeave, {
	      value: _handleMenuItemMouseLeave2
	    });
	    Object.defineProperty(this, _handleMenuItemMouseEnter, {
	      value: _handleMenuItemMouseEnter2
	    });
	    Object.defineProperty(this, _unsubscribeMenuItemEvents, {
	      value: _unsubscribeMenuItemEvents2
	    });
	    Object.defineProperty(this, _handleMenuItemEvents, {
	      value: _handleMenuItemEvents2
	    });
	    Object.defineProperty(this, _showActiveItemSubmenuIfExist, {
	      value: _showActiveItemSubmenuIfExist2
	    });
	    Object.defineProperty(this, _handleArrowRightKey, {
	      value: _handleArrowRightKey2
	    });
	    Object.defineProperty(this, _handleArrowLeftKey, {
	      value: _handleArrowLeftKey2
	    });
	    Object.defineProperty(this, _handleArrowDownKey, {
	      value: _handleArrowDownKey2
	    });
	    Object.defineProperty(this, _handleArrowUpKey, {
	      value: _handleArrowUpKey2
	    });
	    Object.defineProperty(this, _handleEnterKey, {
	      value: _handleEnterKey2
	    });
	    Object.defineProperty(this, _handleKeyDown, {
	      value: _handleKeyDown2
	    });
	    Object.defineProperty(this, _observeMenuChanges, {
	      value: _observeMenuChanges2
	    });
	    Object.defineProperty(this, _menu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _openMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _highlightedMenuItem, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _clearHighlightAfterType, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _highlightFirstItemAfterShow, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _canGoOutFromTop, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _keyDownHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuItemMouseEnterHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuItemMouseLeaveHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuItemSubmenuOnShowHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuItemSubmenuOnCloseHandler, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI:KeyboardMenu');
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu] = options.menu;
	    babelHelpers.classPrivateFieldLooseBase(this, _clearHighlightAfterType)[_clearHighlightAfterType] = options.clearHighlightAfterType;
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightFirstItemAfterShow)[_highlightFirstItemAfterShow] = options.highlightFirstItemAfterShow;
	    babelHelpers.classPrivateFieldLooseBase(this, _canGoOutFromTop)[_canGoOutFromTop] = options.canGoOutFromTop;
	    babelHelpers.classPrivateFieldLooseBase(this, _keyDownHandler)[_keyDownHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleKeyDown)[_handleKeyDown].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItemMouseEnterHandler)[_menuItemMouseEnterHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemMouseEnter)[_handleMenuItemMouseEnter].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItemMouseLeaveHandler)[_menuItemMouseLeaveHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemMouseLeave)[_handleMenuItemMouseLeave].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItemSubmenuOnShowHandler)[_menuItemSubmenuOnShowHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemSubmenuOnShow)[_handleMenuItemSubmenuOnShow].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItemSubmenuOnCloseHandler)[_menuItemSubmenuOnCloseHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemSubmenuOnClose)[_handleMenuItemSubmenuOnClose].bind(this);
	    const handleMenuItemEvents = babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemEvents)[_handleMenuItemEvents].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getPopupWindow().subscribeFromOptions({
	      onAfterShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu] = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu];
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getPopupWindow().getPopupContainer(), '--keyboard-control');
	        if (babelHelpers.classPrivateFieldLooseBase(this, _highlightFirstItemAfterShow)[_highlightFirstItemAfterShow]) {
	          this.highlightFirstItem();
	          main_core.Event.bind(document, 'keydown', babelHelpers.classPrivateFieldLooseBase(this, _keyDownHandler)[_keyDownHandler]);
	        }
	      },
	      onAfterClose: () => {
	        main_core.Event.unbind(document, 'keydown', babelHelpers.classPrivateFieldLooseBase(this, _keyDownHandler)[_keyDownHandler]);
	        babelHelpers.classPrivateFieldLooseBase(this, _clearHighlight)[_clearHighlight]();
	      },
	      onPopupFirstShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMenuItems().forEach(menuItem => {
	          handleMenuItemEvents(menuItem);
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _observeMenuChanges)[_observeMenuChanges]();
	      }
	    });
	  }
	  enableArrows() {
	    main_core.Event.bind(document, 'keydown', babelHelpers.classPrivateFieldLooseBase(this, _keyDownHandler)[_keyDownHandler]);
	  }
	  disableArrows() {
	    main_core.Event.unbind(document, 'keydown', babelHelpers.classPrivateFieldLooseBase(this, _keyDownHandler)[_keyDownHandler]);
	  }
	  getMenu() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu];
	  }
	  highlightFirstItem() {
	    const firstNotDelimiterItem = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMenuItems().find(menuItem => {
	      return menuItem.delimiter !== true;
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightMenuItem)[_highlightMenuItem](firstNotDelimiterItem);
	  }
	}
	function _observeMenuChanges2() {
	  const observer = new MutationObserver(mutationsList => {
	    mutationsList.some(mutation => {
	      if (mutation.type === 'childList') {
	        babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMenuItems().forEach(menuItem => {
	          babelHelpers.classPrivateFieldLooseBase(this, _unsubscribeMenuItemEvents)[_unsubscribeMenuItemEvents](menuItem);
	          babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemEvents)[_handleMenuItemEvents](menuItem);
	        });
	        return true;
	      }
	      return false;
	    });
	  });
	  const config = {
	    childList: true,
	    subtree: true
	  };
	  observer.observe(babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMenuContainer(), config);
	}
	function _handleKeyDown2(e) {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu]) != null && (_babelHelpers$classPr2 = _babelHelpers$classPr.getPopupWindow()) != null && _babelHelpers$classPr2.isShown() && babelHelpers.classPrivateFieldLooseBase(this, _menu)[_menu].getMenuItems().length > 0) {
	    switch (e.key) {
	      case 'Enter':
	        return babelHelpers.classPrivateFieldLooseBase(this, _handleEnterKey)[_handleEnterKey]();
	      case 'ArrowUp':
	        return babelHelpers.classPrivateFieldLooseBase(this, _handleArrowUpKey)[_handleArrowUpKey](e);
	      case 'ArrowDown':
	        return babelHelpers.classPrivateFieldLooseBase(this, _handleArrowDownKey)[_handleArrowDownKey](e);
	      case 'ArrowRight':
	        return babelHelpers.classPrivateFieldLooseBase(this, _handleArrowRightKey)[_handleArrowRightKey](e);
	      case 'ArrowLeft':
	        return babelHelpers.classPrivateFieldLooseBase(this, _handleArrowLeftKey)[_handleArrowLeftKey](e);
	      default:
	        if (babelHelpers.classPrivateFieldLooseBase(this, _clearHighlightAfterType)[_clearHighlightAfterType]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _clearHighlight)[_clearHighlight](true);
	        }
	    }
	  }
	}
	function _handleEnterKey2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem] && babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].href) {
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getContainer().click();
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem] && main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].onclick)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].onclick(null, babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]);
	  }
	}
	function _handleArrowUpKey2(event) {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  event.preventDefault();
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) == null ? void 0 : _babelHelpers$classPr3.closeSubMenu();
	  const prevMenuItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemBeforeHighlighted)[_getMenuItemBeforeHighlighted]();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _canGoOutFromTop)[_canGoOutFromTop] && prevMenuItem === null && ((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) == null ? void 0 : _babelHelpers$classPr4.getMenuWindow().getParentMenuItem()) === null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _clearHighlight)[_clearHighlight]();
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _highlightMenuItem)[_highlightMenuItem](prevMenuItem);
	}
	function _handleArrowDownKey2(event) {
	  var _babelHelpers$classPr5;
	  event.preventDefault();
	  (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) == null ? void 0 : _babelHelpers$classPr5.closeSubMenu();
	  const nextMenuItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemAfterHighlighted)[_getMenuItemAfterHighlighted]();
	  babelHelpers.classPrivateFieldLooseBase(this, _highlightMenuItem)[_highlightMenuItem](nextMenuItem);
	}
	function _handleArrowLeftKey2(event) {
	  event.preventDefault();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) {
	    return;
	  }
	  const parentMenuItem = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getMenuWindow().getParentMenuItem();
	  if (parentMenuItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightMenuItem)[_highlightMenuItem](parentMenuItem);
	    parentMenuItem.closeSubMenu();
	  }
	}
	function _handleArrowRightKey2(event) {
	  event.preventDefault();
	  babelHelpers.classPrivateFieldLooseBase(this, _showActiveItemSubmenuIfExist)[_showActiveItemSubmenuIfExist]();
	}
	function _showActiveItemSubmenuIfExist2() {
	  var _babelHelpers$classPr6;
	  if ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) != null && _babelHelpers$classPr6.hasSubMenu()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].showSubMenu();
	    const subMenu = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getSubMenu();
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightMenuItem)[_highlightMenuItem](subMenu.getMenuItems()[0]);
	  }
	}
	function _handleMenuItemEvents2(menuItem) {
	  menuItem.subscribe('onMouseEnter', babelHelpers.classPrivateFieldLooseBase(this, _menuItemMouseEnterHandler)[_menuItemMouseEnterHandler]);
	  menuItem.subscribe('onMouseLeave', babelHelpers.classPrivateFieldLooseBase(this, _menuItemMouseLeaveHandler)[_menuItemMouseLeaveHandler]);
	  menuItem.subscribe('SubMenu:onShow', babelHelpers.classPrivateFieldLooseBase(this, _menuItemSubmenuOnShowHandler)[_menuItemSubmenuOnShowHandler]);
	  menuItem.subscribe('SubMenu:onClose', babelHelpers.classPrivateFieldLooseBase(this, _menuItemSubmenuOnCloseHandler)[_menuItemSubmenuOnCloseHandler]);
	}
	function _unsubscribeMenuItemEvents2(menuItem) {
	  menuItem.unsubscribe('onMouseEnter', babelHelpers.classPrivateFieldLooseBase(this, _menuItemMouseEnterHandler)[_menuItemMouseEnterHandler]);
	  menuItem.unsubscribe('onMouseLeave', babelHelpers.classPrivateFieldLooseBase(this, _menuItemMouseLeaveHandler)[_menuItemMouseLeaveHandler]);
	  menuItem.unsubscribe('SubMenu:onShow', babelHelpers.classPrivateFieldLooseBase(this, _menuItemSubmenuOnShowHandler)[_menuItemSubmenuOnShowHandler]);
	  menuItem.unsubscribe('SubMenu:onClose', babelHelpers.classPrivateFieldLooseBase(this, _menuItemSubmenuOnCloseHandler)[_menuItemSubmenuOnCloseHandler]);
	}
	function _handleMenuItemMouseEnter2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _highlightMenuItem)[_highlightMenuItem](event.getTarget());
	  main_core.Event.bind(document, 'keydown', babelHelpers.classPrivateFieldLooseBase(this, _keyDownHandler)[_keyDownHandler]);
	}
	function _handleMenuItemMouseLeave2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _clearHighlight)[_clearHighlight]();
	}
	function _handleMenuItemSubmenuOnShow2(event) {
	  const eventMenuItem = event.getTarget();
	  babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu] = eventMenuItem.getSubMenu();
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu].getPopupWindow().getPopupContainer(), '--keyboard-control');
	  eventMenuItem.getSubMenu().getMenuItems().forEach(subMenuItem => {
	    babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemEvents)[_handleMenuItemEvents](subMenuItem);
	  });
	}
	function _handleMenuItemSubmenuOnClose2(event) {
	  const eventMenuItem = event.getTarget();
	  babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu] = eventMenuItem.getMenuWindow();
	}
	function _highlightMenuItem2(menuItem) {
	  var _babelHelpers$classPr7;
	  if (!menuItem) {
	    return;
	  }
	  main_core.Dom.removeClass((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) == null ? void 0 : _babelHelpers$classPr7.getContainer(), highlightedMenuItemClassname);
	  main_core.Dom.addClass(menuItem == null ? void 0 : menuItem.getContainer(), highlightedMenuItemClassname);
	  babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem] = menuItem;
	  babelHelpers.classPrivateFieldLooseBase(this, _scrollToActiveElem)[_scrollToActiveElem]();
	  this.emit(KeyboardMenuEvents.highlightMenuItem);
	}
	function _getMenuItemBeforeHighlighted2() {
	  var _menuItems3;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem] === null) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu].getMenuItems()[0];
	  }
	  const highlightedMenuItemPosition = babelHelpers.classPrivateFieldLooseBase(this, _getHighlightedMenuItemPosition)[_getHighlightedMenuItemPosition]();
	  const menuItems = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getMenuWindow().getMenuItems();
	  if ((_menuItems3 = menuItems[highlightedMenuItemPosition - 1]) != null && _menuItems3.delimiter) {
	    return menuItems[highlightedMenuItemPosition - 2] || null;
	  }
	  return menuItems[highlightedMenuItemPosition - 1] || null;
	}
	function _getMenuItemAfterHighlighted2() {
	  var _babelHelpers$classPr9, _babelHelpers$classPr10, _menuItems4;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem] === null) {
	    var _babelHelpers$classPr8;
	    const menuItems = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _openMenu)[_openMenu]) == null ? void 0 : _babelHelpers$classPr8.getMenuItems();
	    return menuItems.find(menuItem => menuItem.delimiter === false);
	  }
	  const highlightedMenuItemPosition = babelHelpers.classPrivateFieldLooseBase(this, _getHighlightedMenuItemPosition)[_getHighlightedMenuItemPosition]();
	  const menuItems = (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) == null ? void 0 : (_babelHelpers$classPr10 = _babelHelpers$classPr9.getMenuWindow()) == null ? void 0 : _babelHelpers$classPr10.getMenuItems();
	  if ((_menuItems4 = menuItems[highlightedMenuItemPosition + 1]) != null && _menuItems4.delimiter) {
	    return menuItems[highlightedMenuItemPosition + 2] || null;
	  }
	  return menuItems[highlightedMenuItemPosition + 1] || null;
	}
	function _getHighlightedMenuItemPosition2() {
	  const menu = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getMenuWindow();
	  return menu.getMenuItemPosition(babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getId());
	}
	function _clearMenuItemHighlight2(menuItem) {
	  main_core.Dom.removeClass(menuItem == null ? void 0 : menuItem.getContainer(), highlightedMenuItemClassname);
	}
	function _clearHighlight2(closeSubmenu = false) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _clearMenuItemHighlight)[_clearMenuItemHighlight](babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem]);
	  if (closeSubmenu && babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getMenuWindow().getParentMenuItem()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getMenuWindow().getParentMenuItem().closeSubMenu();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem] = null;
	  this.emit(KeyboardMenuEvents.clearHighlight);
	}
	function _scrollToActiveElem2() {
	  const menuItem = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem];
	  const menuWrapper = babelHelpers.classPrivateFieldLooseBase(this, _highlightedMenuItem)[_highlightedMenuItem].getMenuWindow().getPopupWindow().getContentContainer();
	  const relativePosition = main_core.Dom.getRelativePosition(menuWrapper, menuItem.getContainer());
	  if (-relativePosition.y < 0) {
	    menuWrapper.scrollTop -= menuWrapper.offsetHeight;
	  } else if (-relativePosition.y + 10 > relativePosition.height) {
	    menuWrapper.scrollTop += menuWrapper.offsetHeight;
	  }
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	const CopilotMenuEvents$$1 = Object.freeze({
	  select: 'select',
	  open: 'open',
	  close: 'close',
	  clearHighlight: 'clearHighlight',
	  highlightMenuItem: 'highlightMenuItem'
	});
	var _keyboardMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("keyboardMenu");
	var _menuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItems");
	var _cacheable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cacheable");
	var _keyboardControlOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("keyboardControlOptions");
	var _forceTop = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("forceTop");
	var _autoHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("autoHide");
	var _angle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("angle");
	var _bordered = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bordered");
	var _roleInfo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleInfo");
	var _currentRole = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentRole");
	var _roleInfoContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("roleInfoContainer");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _removeMenuItemsExceptRoleItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeMenuItemsExceptRoleItem");
	var _addMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addMenuItems");
	var _closeAllSubmenus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeAllSubmenus");
	var _getMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenu");
	var _initKeyboardMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initKeyboardMenu");
	var _isMenuVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMenuVisible");
	var _scrollForMenuVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollForMenuVisibility");
	var _getMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItems");
	var _getMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItem");
	var _isSeparatorMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSeparatorMenuItem");
	var _getAbilityMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAbilityMenuItem");
	var _renderFavouriteLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFavouriteLabel");
	var _getRoleMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRoleMenuItem");
	var _getRoleMenuItemHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRoleMenuItemHtml");
	var _renderAbilityMenuItemIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderAbilityMenuItemIcon");
	var _getCheckIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCheckIcon");
	var _getMenuItemClassname = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemClassname");
	var _handleMenuItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemClick");
	var _showMenuItemLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showMenuItemLoader");
	var _destroyMenuItemLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroyMenuItemLoader");
	var _getSectionSeparatorMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSectionSeparatorMenuItem");
	var _renderSeparatorMenuItemNewLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSeparatorMenuItemNewLabel");
	var _renderSeparatorMenuItemLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSeparatorMenuItemLabel");
	var _initRoleInfoFromOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initRoleInfoFromOptions");
	class CopilotMenu$$1 extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$cacheable, _options$bordered;
	    super(options);
	    Object.defineProperty(this, _initRoleInfoFromOptions, {
	      value: _initRoleInfoFromOptions2
	    });
	    Object.defineProperty(this, _renderSeparatorMenuItemLabel, {
	      value: _renderSeparatorMenuItemLabel2
	    });
	    Object.defineProperty(this, _renderSeparatorMenuItemNewLabel, {
	      value: _renderSeparatorMenuItemNewLabel2
	    });
	    Object.defineProperty(this, _getSectionSeparatorMenuItem, {
	      value: _getSectionSeparatorMenuItem2
	    });
	    Object.defineProperty(this, _destroyMenuItemLoader, {
	      value: _destroyMenuItemLoader2
	    });
	    Object.defineProperty(this, _showMenuItemLoader, {
	      value: _showMenuItemLoader2
	    });
	    Object.defineProperty(this, _handleMenuItemClick, {
	      value: _handleMenuItemClick2
	    });
	    Object.defineProperty(this, _getMenuItemClassname, {
	      value: _getMenuItemClassname2
	    });
	    Object.defineProperty(this, _getCheckIcon, {
	      value: _getCheckIcon2
	    });
	    Object.defineProperty(this, _renderAbilityMenuItemIcon, {
	      value: _renderAbilityMenuItemIcon2
	    });
	    Object.defineProperty(this, _getRoleMenuItemHtml, {
	      value: _getRoleMenuItemHtml2
	    });
	    Object.defineProperty(this, _getRoleMenuItem, {
	      value: _getRoleMenuItem2
	    });
	    Object.defineProperty(this, _renderFavouriteLabel, {
	      value: _renderFavouriteLabel2
	    });
	    Object.defineProperty(this, _getAbilityMenuItem, {
	      value: _getAbilityMenuItem2
	    });
	    Object.defineProperty(this, _isSeparatorMenuItem, {
	      value: _isSeparatorMenuItem2
	    });
	    Object.defineProperty(this, _getMenuItem, {
	      value: _getMenuItem2
	    });
	    Object.defineProperty(this, _getMenuItems, {
	      value: _getMenuItems2
	    });
	    Object.defineProperty(this, _scrollForMenuVisibility, {
	      value: _scrollForMenuVisibility2
	    });
	    Object.defineProperty(this, _isMenuVisible, {
	      value: _isMenuVisible2
	    });
	    Object.defineProperty(this, _initKeyboardMenu, {
	      value: _initKeyboardMenu2
	    });
	    Object.defineProperty(this, _getMenu, {
	      value: _getMenu2
	    });
	    Object.defineProperty(this, _closeAllSubmenus, {
	      value: _closeAllSubmenus2
	    });
	    Object.defineProperty(this, _addMenuItems, {
	      value: _addMenuItems2
	    });
	    Object.defineProperty(this, _removeMenuItemsExceptRoleItem, {
	      value: _removeMenuItemsExceptRoleItem2
	    });
	    Object.defineProperty(this, _keyboardMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menuItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cacheable, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _keyboardControlOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _forceTop, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _autoHide, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _angle, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bordered, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _roleInfo, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentRole, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _roleInfoContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.Copilot.Menu');
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItems)[_menuItems] = options.items;
	    babelHelpers.classPrivateFieldLooseBase(this, _cacheable)[_cacheable] = (_options$cacheable = options.cacheable) != null ? _options$cacheable : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _forceTop)[_forceTop] = options.forceTop === undefined ? babelHelpers.classPrivateFieldLooseBase(this, _forceTop)[_forceTop] : options.forceTop === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _autoHide)[_autoHide] = options.autoHide === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _angle)[_angle] = options.angle;
	    babelHelpers.classPrivateFieldLooseBase(this, _bordered)[_bordered] = (_options$bordered = options.bordered) != null ? _options$bordered : babelHelpers.classPrivateFieldLooseBase(this, _bordered)[_bordered];
	    babelHelpers.classPrivateFieldLooseBase(this, _initRoleInfoFromOptions)[_initRoleInfoFromOptions](options.roleInfo);
	    if (options.keyboardControlOptions) {
	      babelHelpers.classPrivateFieldLooseBase(this, _keyboardControlOptions)[_keyboardControlOptions] = options.keyboardControlOptions;
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _keyboardControlOptions)[_keyboardControlOptions] = {
	        canGoOutFromTop: true,
	        highlightFirstItemAfterShow: false,
	        clearHighlightAfterType: false
	      };
	    }
	  }
	  open() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().show();
	    this.adjustPosition();
	    this.emit(CopilotMenuEvents$$1.open);
	  }
	  show() {
	    var _this$getPopup, _this$getPopup2, _this$getPopup3;
	    main_core.Dom.style((_this$getPopup = this.getPopup()) == null ? void 0 : _this$getPopup.getPopupContainer(), 'border', null);
	    (_this$getPopup2 = this.getPopup()) == null ? void 0 : _this$getPopup2.setMaxWidth(null);
	    (_this$getPopup3 = this.getPopup()) == null ? void 0 : _this$getPopup3.setMinWidth(258);
	    this.adjustPosition();
	    this.enableArrowsKey();
	  }
	  close() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().close();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeAllSubmenus)[_closeAllSubmenus]();
	    this.emit(CopilotMenuEvents$$1.close);
	  }
	  hide() {
	    var _this$getPopup4, _this$getPopup5, _this$getPopup6;
	    main_core.Dom.style((_this$getPopup4 = this.getPopup()) == null ? void 0 : _this$getPopup4.getPopupContainer(), 'border', 'none');
	    (_this$getPopup5 = this.getPopup()) == null ? void 0 : _this$getPopup5.setMaxWidth(0);
	    (_this$getPopup6 = this.getPopup()) == null ? void 0 : _this$getPopup6.setMinWidth(0);
	    this.adjustPosition();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeAllSubmenus)[_closeAllSubmenus]();
	    this.disableArrowsKey();
	  }
	  contains(target) {
	    for (const menuItem of babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItems()) {
	      var _menuItem$getSubMenu, _itemPopup$getPopupCo;
	      const itemPopup = (_menuItem$getSubMenu = menuItem.getSubMenu()) == null ? void 0 : _menuItem$getSubMenu.getPopupWindow();
	      if (itemPopup != null && (_itemPopup$getPopupCo = itemPopup.getPopupContainer()) != null && _itemPopup$getPopupCo.contains(target)) {
	        return true;
	      }
	    }
	    return this.getPopup().getPopupContainer().contains(target);
	  }
	  isShown() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _keyboardMenu)[_keyboardMenu]) == null ? void 0 : (_babelHelpers$classPr2 = _babelHelpers$classPr.getMenu()) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.getPopupWindow()) == null ? void 0 : _babelHelpers$classPr3.isShown();
	  }
	  setBindElement(bindElement, offset) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().setBindElement(bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().setOffset({
	      offsetLeft: offset == null ? void 0 : offset.left,
	      offsetTop: offset == null ? void 0 : offset.top
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().adjustPosition();
	  }
	  getPopup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow();
	  }
	  adjustPosition() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().adjustPosition({
	      forceBindPosition: true,
	      forceTop: babelHelpers.classPrivateFieldLooseBase(this, _forceTop)[_forceTop]
	    });
	  }
	  replaceMenuItemSubmenu(newCopilotMenuItem) {
	    const menuItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItems().find(currentMenuItem => {
	      return newCopilotMenuItem.code === currentMenuItem.getId();
	    });
	    menuItem.destroySubMenu();
	    // eslint-disable-next-line no-underscore-dangle,@bitrix24/bitrix24-rules/no-pseudo-private
	    menuItem._items = babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](newCopilotMenuItem.children, true);
	    menuItem.addSubMenu(babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](newCopilotMenuItem.children, true));
	  }
	  enableArrowsKey() {
	    var _babelHelpers$classPr4;
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _keyboardMenu)[_keyboardMenu]) == null ? void 0 : _babelHelpers$classPr4.enableArrows();
	  }
	  disableArrowsKey() {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _keyboardMenu)[_keyboardMenu]) == null ? void 0 : _babelHelpers$classPr5.disableArrows();
	  }
	  markMenuItemSelected(menuItemId) {
	    const menuItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItem(menuItemId);
	    const menuItemInnerContainer = menuItem.getContainer().querySelector('.ai__copilot-menu_item');
	    main_core.Dom.addClass(menuItemInnerContainer, '--selected');
	  }
	  unmarkMenuItemSelected(menuItemId) {
	    const menuItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItem(menuItemId);
	    const menuItemInnerContainer = menuItem.getContainer().querySelector('.ai__copilot-menu_item');
	    main_core.Dom.removeClass(menuItemInnerContainer, '--selected');
	  }
	  updateRoleInfo(role) {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole].avatar = role.avatar;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole].name = role.name;
	  }
	  setItemIsFavourite(itemCode, isFavourite) {
	    var _babelHelpers$classPr6;
	    const itemContainer = (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItem(itemCode)) == null ? void 0 : _babelHelpers$classPr6.getContainer();
	    if (!itemContainer) {
	      return;
	    }
	    const favouriteLabelWrapper = itemContainer.querySelector('.ai__copilot-menu_item-favourite');
	    if (!favouriteLabelWrapper) {
	      return;
	    }
	    main_core.Dom.replace(favouriteLabelWrapper, babelHelpers.classPrivateFieldLooseBase(this, _renderFavouriteLabel)[_renderFavouriteLabel](itemCode, isFavourite));
	  }
	  insertItemBefore(itemCode, insertedItem) {
	    const menuItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenuItem)[_getMenuItem](insertedItem, false);
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().addMenuItem(menuItem, itemCode);
	  }
	  insertItemAfterRole(insertedItem) {
	    const roleItemPosition = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItemPosition('role-item');
	    const menuItemAfterRoleItem = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItems()[roleItemPosition + 1];
	    this.insertItemBefore(menuItemAfterRoleItem.getId(), insertedItem);
	  }
	  insertItemAfter(itemCode, insertedItem) {
	    const menuItemAfterTarget = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItems()[babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItemPosition(itemCode) + 1];
	    this.insertItemBefore(menuItemAfterTarget.getId(), insertedItem);
	  }
	  removeItem(itemCode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().removeMenuItem(itemCode);
	  }
	  setLoader() {
	    var _babelHelpers$classPr7;
	    const popupContainer = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow()) == null ? void 0 : _babelHelpers$classPr7.getPopupContainer();
	    if (!popupContainer) {
	      return;
	    }
	    const fade = main_core.Tag.render(_t || (_t = _`<div class="ai__copilot-menu-popup_fade"></div>`));
	    main_core.Dom.append(fade, popupContainer);
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      size: 55,
	      target: popupContainer,
	      color: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary') || '#8e52ec'
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show();
	  }
	  removeLoader() {
	    var _babelHelpers$classPr8;
	    const popupContainer = (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow()) == null ? void 0 : _babelHelpers$classPr8.getPopupContainer();
	    if (!popupContainer) {
	      return;
	    }
	    const fade = popupContainer.querySelector('.ai__copilot-menu-popup_fade');
	    main_core.Dom.remove(fade);
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = null;
	  }
	  updateMenuItemsExceptRoleItem(copilotMenuItems) {
	    babelHelpers.classPrivateFieldLooseBase(this, _removeMenuItemsExceptRoleItem)[_removeMenuItemsExceptRoleItem]();
	    babelHelpers.classPrivateFieldLooseBase(this, _addMenuItems)[_addMenuItems](copilotMenuItems);
	  }
	}
	function _removeMenuItemsExceptRoleItem2() {
	  const menuItems = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItems();
	  menuItems.forEach(currentMenuItem => {
	    const id = currentMenuItem.getId();
	    if (id === 'role-item') {
	      return;
	    }
	    requestAnimationFrame(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().removeMenuItem(id);
	    });
	  });
	}
	function _addMenuItems2(copilotMenuItems) {
	  const newMenuItems = babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](copilotMenuItems);
	  newMenuItems.forEach(newMenuItem => {
	    requestAnimationFrame(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().addMenuItem(newMenuItem);
	    });
	  });
	}
	function _closeAllSubmenus2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getMenuItems().forEach(menuItem => {
	    menuItem.closeSubMenu();
	  });
	}
	function _getMenu2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _keyboardMenu)[_keyboardMenu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initKeyboardMenu)[_initKeyboardMenu]();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _keyboardMenu)[_keyboardMenu].getMenu();
	}
	function _initKeyboardMenu2() {
	  const menu = new main_popup.Menu({
	    minWidth: 258,
	    maxHeight: 372,
	    angle: babelHelpers.classPrivateFieldLooseBase(this, _angle)[_angle],
	    closeByEsc: false,
	    closeIcon: false,
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](babelHelpers.classPrivateFieldLooseBase(this, _menuItems)[_menuItems]),
	    toFrontOnShow: true,
	    autoHide: babelHelpers.classPrivateFieldLooseBase(this, _autoHide)[_autoHide],
	    className: `ai__copilot-scope ai__copilot-menu-popup ${babelHelpers.classPrivateFieldLooseBase(this, _bordered)[_bordered] ? '--bordered' : ''}`,
	    cacheable: babelHelpers.classPrivateFieldLooseBase(this, _cacheable)[_cacheable],
	    events: {
	      onPopupClose: popup => {
	        this.emit(CopilotMenuEvents$$1.close);
	        main_core.Dom.style(popup.getPopupContainer(), 'border', 'none');
	      },
	      onPopupAfterClose: popup => {
	        main_core.Dom.style(popup.getPopupContainer(), 'border', null);
	      },
	      onPopupShow: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _forceTop)[_forceTop] && babelHelpers.classPrivateFieldLooseBase(this, _isMenuVisible)[_isMenuVisible]() === false) {
	          babelHelpers.classPrivateFieldLooseBase(this, _scrollForMenuVisibility)[_scrollForMenuVisibility]();
	        }
	      }
	    }
	  });
	  const keyBoardMenu = new KeyboardMenu({
	    menu,
	    ...babelHelpers.classPrivateFieldLooseBase(this, _keyboardControlOptions)[_keyboardControlOptions]
	  });
	  keyBoardMenu.subscribe(KeyboardMenuEvents.clearHighlight, () => {
	    this.emit(CopilotMenuEvents$$1.clearHighlight);
	  });
	  keyBoardMenu.subscribe(KeyboardMenuEvents.highlightMenuItem, () => {
	    this.emit(CopilotMenuEvents$$1.highlightMenuItem);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _keyboardMenu)[_keyboardMenu] = keyBoardMenu;
	}
	function _isMenuVisible2() {
	  const popupContainer = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().getPopupContainer();
	  const popupContainerPosition = popupContainer.getBoundingClientRect();
	  return popupContainerPosition.bottom < window.innerHeight;
	}
	function _scrollForMenuVisibility2() {
	  const popupContainer = babelHelpers.classPrivateFieldLooseBase(this, _getMenu)[_getMenu]().getPopupWindow().getPopupContainer();
	  const popupContainerPosition = main_core.Dom.getPosition(popupContainer);
	  window.scrollTo({
	    top: popupContainerPosition.bottom + 20 - window.innerHeight,
	    behavior: 'smooth'
	  });
	  if (popupContainerPosition.bottom > document.body.scrollHeight) {
	    main_core.Dom.style(document.body, 'min-height', `${popupContainerPosition.bottom}px`);
	  }
	}
	function _getMenuItems2(items, isSubmenu = false) {
	  if (!items) {
	    return [];
	  }
	  const menuItems = items.map(item => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getMenuItem)[_getMenuItem](item, isSubmenu);
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _roleInfo)[_roleInfo] && isSubmenu === false) {
	    menuItems.unshift(babelHelpers.classPrivateFieldLooseBase(this, _getRoleMenuItem)[_getRoleMenuItem]());
	  }
	  return menuItems;
	}
	function _getMenuItem2(item, isSubmenuItem) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isSeparatorMenuItem)[_isSeparatorMenuItem](item) ? babelHelpers.classPrivateFieldLooseBase(this, _getSectionSeparatorMenuItem)[_getSectionSeparatorMenuItem](item) : babelHelpers.classPrivateFieldLooseBase(this, _getAbilityMenuItem)[_getAbilityMenuItem](item, isSubmenuItem);
	}
	function _isSeparatorMenuItem2(menuItem) {
	  return menuItem.separator;
	}
	function _getAbilityMenuItem2(item, isSubmenuItem = false) {
	  const iconElem = babelHelpers.classPrivateFieldLooseBase(this, _renderAbilityMenuItemIcon)[_renderAbilityMenuItemIcon](item);
	  const checkIcon = babelHelpers.classPrivateFieldLooseBase(this, _getCheckIcon)[_getCheckIcon]();
	  const menuIcon = item.icon ? main_core.Tag.render(_t2 || (_t2 = _`<div class="ai__copilot-menu_item-icon">${0}</div>`), iconElem) : null;
	  const label = item.labelText ? new ui_label.Label({
	    text: item.labelText,
	    color: ui_label.LabelColor.PRIMARY,
	    fill: true,
	    size: ui_label.LabelSize.SM
	  }).render() : null;
	  const labelWrapper = label ? main_core.Tag.render(`<div>${label}</div>`) : null;
	  const favouriteLabel = main_core.Type.isBoolean(item.isFavourite) ? babelHelpers.classPrivateFieldLooseBase(this, _renderFavouriteLabel)[_renderFavouriteLabel](item.code, item.isFavourite) : null;
	  const html = main_core.Tag.render(_t3 || (_t3 = _`
			<div class="${0}">
				<div class="ai__copilot-menu_item-left">
					${0}
					<div class="ai__copilot-menu_item-text">${0}</div>
				</div>
				<div class="ai__copilot-menu_item-right">
					${0}
					<div class="ai__copilot-menu_item-check">
						${0}
					</div>
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemClassname)[_getMenuItemClassname](item, isSubmenuItem, item.selected), menuIcon, main_core.Text.encode(item.text), favouriteLabel, checkIcon.render(), labelWrapper);
	  return {
	    html,
	    id: item.id || '',
	    text: item.text,
	    href: item.href,
	    className: `menu-popup-no-icon ${item.arrow ? 'menu-popup-item-submenu' : ''}`,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemClick)[_handleMenuItemClick](item.command).bind(this),
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](item.children, true),
	    cacheable: false,
	    disabled: item.disabled
	  };
	}
	function _renderFavouriteLabel2(promptCode, isFavourite = false) {
	  const favouriteIcon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.BOOKMARK_1,
	    size: 24
	  });
	  const iconWrapperClassname = `ai__copilot-menu_item-favourite ${isFavourite ? '--is-favourite' : ''}`;
	  const title = isFavourite ? main_core.Loc.getMessage('AI_COPILOT_REMOVE_PROMPT_FROM_FAVOURITE') : main_core.Loc.getMessage('AI_COPILOT_ADD_PROMPT_TO_FAVOURITE');
	  const wrapper = main_core.Tag.render(_t4 || (_t4 = _`
			<div title="${0}" class="${0}">
				${0}
			</div>
		`), title, iconWrapperClassname, favouriteIcon.render());
	  main_core.bind(wrapper, 'click', event => {
	    event.preventDefault();
	    event.stopImmediatePropagation();
	    const newIsFavourite = !isFavourite;
	    this.emit('set-favourite', {
	      promptCode,
	      isFavourite: newIsFavourite
	    });
	  });
	  return wrapper;
	}
	function _getRoleMenuItem2() {
	  return {
	    id: 'role-item',
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getRoleMenuItemHtml)[_getRoleMenuItemHtml](),
	    className: `menu-popup-no-icon ${babelHelpers.classPrivateFieldLooseBase(this, _roleInfo)[_roleInfo].onclick ? 'menu-popup-item-submenu' : ''} --role-item`,
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemClick)[_handleMenuItemClick](babelHelpers.classPrivateFieldLooseBase(this, _roleInfo)[_roleInfo].onclick).bind(this)
	  };
	}
	function _getRoleMenuItemHtml2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer];
	  }
	  const {
	    name,
	    avatar
	  } = babelHelpers.classPrivateFieldLooseBase(this, _roleInfo)[_roleInfo].role;
	  const subtitle = babelHelpers.classPrivateFieldLooseBase(this, _roleInfo)[_roleInfo].subtitle;
	  babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer] = main_core.Tag.render(_t5 || (_t5 = _`
			<div class="ai__copilot-menu_item">
				<div class="ai__copilot-menu_role">
					<div class="ai__copilot-menu_role-left">
						<img class="ai__copilot-menu_role-avatar" src="${0}" alt="">
					</div>
					<div class="ai__copilot-menu_role-right">
						<span
							class="ai__copilot-menu_role-title"
							title="${0}"
						>
							${0}
						</span>
						<span class="ai__copilot-menu_role-subtitle">${0}</span>
					</div>
				</div>
			</div>
		`), avatar.small, name, name, subtitle);
	  return babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer];
	}
	function _renderAbilityMenuItemIcon2(item) {
	  let iconElem = null;
	  if (item.icon) {
	    try {
	      const icon = new ui_iconSet_api_core.Icon({
	        size: 24,
	        icon: item.icon || undefined
	      });
	      iconElem = icon.render();
	    } catch {
	      iconElem = null;
	    }
	  }
	  return iconElem;
	}
	function _getCheckIcon2() {
	  const checkIconColor = getComputedStyle(document.body).getPropertyValue('--ui-color-link-primary-base');
	  return new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.CHECK,
	    size: 18,
	    color: checkIconColor
	  });
	}
	function _getMenuItemClassname2(item, isSubMenuItem) {
	  let classNames = ['ai__copilot-menu_item'];
	  if (isSubMenuItem) {
	    classNames = [...classNames, '--no-icon'];
	  }
	  if (item.notHighlight) {
	    classNames = [...classNames, '--system'];
	  }
	  if (item.highlightText) {
	    classNames = [...classNames, '--highlight-text'];
	  }
	  if (item.selected) {
	    classNames = [...classNames, '--selected'];
	  }
	  if (item.isShowFavouriteIconOnHover) {
	    classNames = [...classNames, '--favourite-icon-on-hover'];
	  }
	  return classNames.join(' ');
	}
	function _handleMenuItemClick2(command) {
	  return async (event, menuItem) => {
	    var _menuItem$getMenuWind, _menuItem$getMenuWind2;
	    if (menuItem != null && menuItem.hasSubMenu()) {
	      return;
	    }
	    (_menuItem$getMenuWind = menuItem.getMenuWindow()) == null ? void 0 : (_menuItem$getMenuWind2 = _menuItem$getMenuWind.getParentMenuItem()) == null ? void 0 : _menuItem$getMenuWind2.closeSubMenu();
	    if (menuItem.href) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _showMenuItemLoader)[_showMenuItemLoader](menuItem);
	    if (main_core.Type.isFunction(command)) {
	      await command(event, menuItem, this);
	    } else {
	      await (command == null ? void 0 : command.execute());
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyMenuItemLoader)[_destroyMenuItemLoader](menuItem);
	  };
	}
	function _showMenuItemLoader2(menuItem) {
	  const loaderSize = 18;
	  const loaderColor = getComputedStyle(document.body.querySelector('.ai__copilot-scope')).getPropertyValue('--ai__copilot_color-main');
	  const loaderWrapper = main_core.Tag.render(_t6 || (_t6 = _`<div class="ai__copilot-menu_item-loader"></div>`));
	  const menuItemContent = menuItem.getContainer().querySelector('.ai__copilot-menu_item-right');
	  main_core.Dom.addClass(menuItem.getContainer(), 'menu-popup-item-loading');
	  main_core.Dom.append(loaderWrapper, menuItemContent);
	  const loader = new main_loader.Loader({
	    size: loaderSize,
	    target: loaderWrapper,
	    color: loaderColor
	  });
	  loader.show();
	}
	function _destroyMenuItemLoader2(menuItem) {
	  const loaderWrapper = menuItem.getContainer().querySelector('.ai__copilot-menu_item-loader');
	  main_core.Dom.removeClass(menuItem.getContainer(), 'menu-popup-item-loading');
	  main_core.Dom.remove(loaderWrapper);
	}
	function _getSectionSeparatorMenuItem2(item) {
	  return {
	    id: item.code || item.title || '',
	    text: item.title,
	    title: item.title,
	    delimiter: true,
	    html: item.title ? `
					<span>${item.title}</span>
					${item.isNew ? babelHelpers.classPrivateFieldLooseBase(this, _renderSeparatorMenuItemNewLabel)[_renderSeparatorMenuItemNewLabel]().outerHTML : ''}
				` : undefined
	  };
	}
	function _renderSeparatorMenuItemNewLabel2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _renderSeparatorMenuItemLabel)[_renderSeparatorMenuItemLabel](main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_LABEL_NEW'));
	}
	function _renderSeparatorMenuItemLabel2(text) {
	  const newLabel = new ui_label.Label({
	    text,
	    color: ui_label.Label.Color.PRIMARY,
	    size: ui_label.Label.Size.SM,
	    fill: true
	  });
	  return main_core.Tag.render(_t7 || (_t7 = _`<span class="ai__copilot-menu_delimiter-label">${0}</span>`), newLabel.render().outerHTML);
	}
	function _initRoleInfoFromOptions2(roleInfoOption) {
	  if (roleInfoOption) {
	    babelHelpers.classPrivateFieldLooseBase(this, _roleInfo)[_roleInfo] = roleInfoOption;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole] = new Proxy(roleInfoOption.role, {
	      set: (target, p, newValue) => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer] && p === 'name') {
	          const nameContainer = babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer].querySelector('.ai__copilot-menu_role-title');
	          main_core.Dom.attr(nameContainer, 'title', newValue);
	          nameContainer.innerText = newValue;
	        }
	        if (babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer] && p === 'avatar') {
	          const avatarImg = babelHelpers.classPrivateFieldLooseBase(this, _roleInfoContainer)[_roleInfoContainer].querySelector('.ai__copilot-menu_role-avatar');
	          avatarImg.src = newValue.small;
	        }
	        return Reflect.set(target, p, newValue);
	      }
	    });
	  }
	}

	class CopilotMenuCommand {
	  execute() {
	    throw new Error('You must implement this method.');
	  }
	}

	class BaseMenuItem extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$children;
	    super();
	    this.id = '';
	    this.setEventNamespace('AI.CopilotMenuItem');
	    if (options.id) {
	      this.id = options.id;
	    }
	    this.code = options.code;
	    this.text = options.text;
	    this.icon = options.icon;
	    this.href = options.href;
	    this.children = (_options$children = options.children) != null ? _options$children : [];
	    this.onClick = options.onClick;
	    this.disabled = options.disabled;
	  }
	  getOptions() {
	    return {
	      id: this.id,
	      code: this.code,
	      text: this.text,
	      icon: this.icon,
	      href: this.href,
	      command: this.onClick,
	      disabled: this.disabled,
	      children: this.children.map(childrenMenuItem => {
	        if (childrenMenuItem instanceof BaseMenuItem) {
	          return childrenMenuItem.getOptions();
	        }
	        return childrenMenuItem;
	      })
	    };
	  }
	}

	let _$1 = t => t,
	  _t$1;
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _rawResult = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rawResult");
	class CopilotResult {
	  constructor() {
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _rawResult, {
	      writable: true,
	      value: void 0
	    });
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="ai__copilot-result"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _rawResult)[_rawResult] = '';
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  addResult(result) {
	    babelHelpers.classPrivateFieldLooseBase(this, _rawResult)[_rawResult] = result;
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].innerHTML += String(main_core.Text.encode(result).replaceAll(/(\r\n|\r|\n)/g, '<br>'));
	  }
	  clearResult() {
	    babelHelpers.classPrivateFieldLooseBase(this, _rawResult)[_rawResult] = '';
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].innerHTML = '';
	  }
	  getResult() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _rawResult)[_rawResult];
	  }
	}

	let _$2 = t => t,
	  _t$2;
	const initPopup = options => {
	  const target = options.target;
	  const text = options.text;
	  return new main_popup.Popup({
	    content: main_core.Tag.render(_t$2 || (_t$2 = _$2`<div style="padding-right: 20px;">${0}</div>`), text),
	    darkMode: true,
	    borderRadius: '4px',
	    animation: 'fading-slide',
	    autoHide: true,
	    events: {
	      onPopupShow: getPopupShowEventHandler(target)
	    }
	  });
	};
	const getPopupShowEventHandler = target => {
	  return popup => {
	    const targetPos = main_core.Dom.getPosition(target);
	    const popupPos = main_core.Dom.getPosition(popup.getPopupContainer());
	    const angleOffset = main_popup.Popup.getOption('angleLeftOffset');
	    popup.setAngle({
	      offset: popupPos.width / 2 - targetPos.width / 2 - 4
	    });
	    popup.setBindElement({
	      left: targetPos.left - popupPos.width / 2 + targetPos.width / 2 + angleOffset,
	      top: targetPos.bottom
	    });
	    popup.adjustPosition({
	      forceBindPosition: true,
	      forceLeft: true,
	      forceTop: true
	    });
	  };
	};
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	class CopilotHint {
	  constructor(options) {
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = initPopup(options);
	  }
	  static addHintOnTargetHover(options) {
	    const popup = initPopup(options);
	    const target = options.target;
	    main_core.Event.bind(target, 'mouseenter', () => {
	      popup.show();
	    });
	    main_core.Event.bind(target, 'mouseleave', () => {
	      popup.close();
	    });
	  }
	  show() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.show();
	  }
	  hide() {
	    var _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr2.close();
	  }
	  isShown() {
	    var _babelHelpers$classPr3;
	    return Boolean((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr3.isShown());
	  }
	}

	function createRangeWithPosition(node, targetPosition) {
	  const range = document.createRange();
	  range.selectNode(node);
	  range.setStart(node, 0);
	  let pos = 0;
	  const stack = [node];
	  while (stack.length > 0) {
	    const current = stack.pop();
	    if (current.nodeType === Node.TEXT_NODE) {
	      const len = current.textContent.length;
	      if (pos + len >= targetPosition) {
	        range.setStart(current, targetPosition - pos);
	        range.setEnd(current, targetPosition - pos);
	        return range;
	      }
	      pos += len;
	    } else if (current.childNodes && current.childNodes.length > 0) {
	      for (let i = current.childNodes.length - 1; i >= 0; i--) {
	        stack.push(current.childNodes[i]);
	      }
	    }
	  }
	  range.setStart(node, node.childNodes.length);
	  range.setEnd(node, node.childNodes.length);
	  return range;
	}
	function setCursorPosition(node, targetPosition) {
	  const range = createRangeWithPosition(node, targetPosition);
	  const selection = window.getSelection();
	  selection.removeAllRanges();
	  selection.addRange(range);
	}
	function getCursorPosition(node) {
	  const selection = window.getSelection();
	  const range = selection.getRangeAt(0);
	  const clonedRange = range.cloneRange();
	  clonedRange.selectNodeContents(node);
	  clonedRange.setEnd(range.endContainer, range.endOffset);
	  return clonedRange.toString().length;
	}

	let _$3 = t => t,
	  _t$3;
	var _container$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _observeRemovingStrongTagAfterDeletingBracket = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observeRemovingStrongTagAfterDeletingBracket");
	var _handlePasteEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handlePasteEvent");
	var _handleInputEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputEvent");
	class CopilotInputFieldTextarea extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _handleInputEvent, {
	      value: _handleInputEvent2
	    });
	    Object.defineProperty(this, _handlePasteEvent, {
	      value: _handlePasteEvent2
	    });
	    Object.defineProperty(this, _observeRemovingStrongTagAfterDeletingBracket, {
	      value: _observeRemovingStrongTagAfterDeletingBracket2
	    });
	    Object.defineProperty(this, _container$1, {
	      writable: true,
	      value: void 0
	    });
	    this.value = '';
	    this.setEventNamespace('AI.CopilotInputFieldTextarea');
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1];
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] = main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<div
				class="ai__copilot_input"
				contenteditable="true"></div>
		`));
	    const observer = new MutationObserver(babelHelpers.classPrivateFieldLooseBase(this, _observeRemovingStrongTagAfterDeletingBracket)[_observeRemovingStrongTagAfterDeletingBracket].bind(this));
	    observer.observe(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], {
	      childList: true,
	      subtree: true,
	      characterDataOldValue: true
	    });
	    main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], 'input', babelHelpers.classPrivateFieldLooseBase(this, _handleInputEvent)[_handleInputEvent].bind(this));
	    main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], 'paste', babelHelpers.classPrivateFieldLooseBase(this, _handlePasteEvent)[_handlePasteEvent].bind(this));
	    main_core.bind(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], 'focus', e => {
	      this.emit('focus');
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1];
	  }
	  set value(text) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1].innerText = text;
	    }
	  }
	  get value() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1].innerText;
	  }
	  get disabled() {
	    var _babelHelpers$classPr;
	    return ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]) == null ? void 0 : _babelHelpers$classPr.getAttribute('contenteditable')) === 'false';
	  }
	  set disabled(disabled) {
	    if (disabled === false) {
	      main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], 'contenteditable', true);
	    } else {
	      main_core.Dom.attr(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], 'contenteditable', false);
	    }
	  }
	  focus(setCursorAtStart) {
	    var _babelHelpers$classPr2;
	    const cursorPosition = setCursorAtStart ? 0 : 999;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]) == null ? void 0 : _babelHelpers$classPr2.focus();
	    this.setCursorPosition(cursorPosition);
	  }
	  getComputedStyle() {
	    return getComputedStyle(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]);
	  }
	  addClass(className) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], className);
	  }
	  removeClass(className) {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], className);
	  }
	  setStyle(prop, value) {
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], prop, value);
	  }
	  get scrollHeight() {
	    var _babelHelpers$classPr3;
	    return ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]) == null ? void 0 : _babelHelpers$classPr3.scrollHeight) || 0;
	  }
	  isCursorInTheEnd() {
	    const pos = this.getCursorPosition();
	    const contentLength = babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1].innerText.length;
	    return pos >= contentLength;
	  }
	  getCursorPosition() {
	    return getCursorPosition(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]);
	  }
	  setCursorPosition(position) {
	    setCursorPosition(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1], position);
	  }
	  setHtmlContent(html) {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1].innerHTML = html;
	    this.focus();
	    this.emit('input', this.value);
	  }
	}
	function _observeRemovingStrongTagAfterDeletingBracket2(mutations) {
	  for (const mutation of mutations) {
	    var _mutation$target$pare;
	    if (((_mutation$target$pare = mutation.target.parentElement) == null ? void 0 : _mutation$target$pare.tagName) !== 'STRONG') {
	      continue;
	    }
	    const nodeText = mutation.target.nodeValue || '';
	    const openBracketPosition = [...nodeText].indexOf('[');
	    const closeBracketPosition = [...nodeText].indexOf(']');
	    if (closeBracketPosition === -1 || openBracketPosition === -1) {
	      const pos = this.getCursorPosition();
	      mutation.target.parentElement.replaceWith(...mutation.target.parentElement.childNodes);
	      this.setCursorPosition(pos);
	    }
	  }
	}
	function _handlePasteEvent2(e) {
	  e.preventDefault();
	  const text = e.clipboardData.getData('text/plain');
	  document.execCommand('insertText', false, text);
	}
	function _handleInputEvent2(e) {
	  var _selection$anchorNode;
	  if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteWordBackward' || e.inputType === 'deleteContentForward') {
	    this.emit('input', this.value);
	    return;
	  }
	  const selection = window.getSelection();
	  const cursorPosition = this.getCursorPosition();
	  if (((_selection$anchorNode = selection.anchorNode.parentElement) == null ? void 0 : _selection$anchorNode.tagName) === 'STRONG' && selection.focusOffset === selection.anchorNode.length && selection.anchorNode.textContent.at(0) === '[') {
	    let nextNode = selection.anchorNode.parentElement.nextSibling;
	    if (!nextNode || nextNode.nodeName === 'BR') {
	      nextNode = document.createTextNode(' ');
	      main_core.Dom.insertAfter(nextNode, selection.anchorNode.parentElement);
	    }
	    selection.anchorNode.textContent = selection.anchorNode.textContent.slice(0, -e.data.length);
	    nextNode.textContent = e.data + nextNode.textContent;
	    this.setCursorPosition(cursorPosition);
	    e.preventDefault();
	    e.stopPropagation();
	    return;
	  }
	  this.emit('input', this.value);
	}

	var nm = "18";
	var v = "5.9.6";
	var fr = 60;
	var ip = 0;
	var op = 539;
	var w = 210;
	var h = 210;
	var ddd = 0;
	var markers = [];
	var assets = [{
	  nm: "[FRAME] 18 - Null / left-star - Null / left-star / right-star - Null / right-star / round - Null / round / Ellipse 2 - Null / Ellipse 2 - Stroke",
	  fr: 60,
	  id: "lo8h9rmzawnjrpxsxm9",
	  layers: [{
	    ty: 3,
	    ddd: 0,
	    ind: 4,
	    hd: false,
	    nm: "18 - Null",
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 5,
	    hd: false,
	    nm: "left-star - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [37.7244, 37.5291]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [95.78874400000001, 95.16759099999999]
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      s: {
	        a: 0,
	        k: [101, 101]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 6,
	    hd: false,
	    nm: "left-star",
	    parent: 5,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 1,
	          k: [{
	            t: 25.89,
	            s: [{
	              c: true,
	              v: [[39.4485, 0.9376], [36.0004, 0.9376], [29.4602, 18.5122], [18.5993, 29.3117], [0.9248, 35.8149], [0.9248, 39.2435], [18.5994, 45.7467], [29.4603, 56.5462], [36.0005, 74.1208], [39.4486, 74.1208], [45.9888, 56.5462], [56.8497, 45.7467], [74.5243, 39.2435], [74.5243, 35.8149], [56.8498, 29.3117], [45.9889, 18.5122], [39.4485, 0.9376]],
	              i: [[0, 0], [0.5922, -1.5914], [0, 0], [5.0318, -1.8514], [0, 0], [-1.6004, -0.5889], [0, 0], [-1.8619, -5.0033], [0, 0], [-0.5922, 1.5914], [0, 0], [-5.0318, 1.8514], [0, 0], [1.6004, 0.5889], [0, 0], [1.8619, 5.0033], [0, 0]],
	              o: [[-0.5922199999999975, -1.5914], [0, 0], [-1.8619200000000014, 5.003299999999999], [0, 0], [-1.60045, 0.58887], [0, 0], [5.031760000000002, 1.851390000000002], [0, 0], [0.5922199999999975, 1.591399999999993], [0, 0], [1.8619199999999978, -5.003300000000003], [0, 0], [1.600449999999995, -0.58887], [0, 0], [-5.031770000000002, -1.8513899999999985], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0.3],
	              y: [0]
	            },
	            i: {
	              x: [1],
	              y: [1]
	            }
	          }, {
	            t: 145.224,
	            s: [{
	              c: true,
	              v: [[38.4952, 21.1678], [36.9535, 21.1678], [34.0294, 29.026], [29.1734, 33.8548], [21.271, 36.7626], [21.271, 38.2956], [29.1734, 41.2034], [34.0294, 46.0322], [36.9536, 53.8904], [38.4953, 53.8904], [41.4194, 46.0322], [46.2754, 41.2034], [54.1778, 38.2956], [54.1778, 36.7626], [46.2754, 33.8548], [41.4194, 29.026], [38.4952, 21.1678]],
	              i: [[0, 0], [0.2648, -0.7116], [0, 0], [2.2497, -0.8278], [0, 0], [-0.7156, -0.2633], [0, 0], [-0.8325, -2.2371], [0, 0], [-0.2648, 0.7116], [0, 0], [-2.2497, 0.8278], [0, 0], [0.7156, 0.2633], [0, 0], [0.8325, 2.2371], [0, 0]],
	              o: [[-0.26478999999999786, -0.7115700000000018], [0, 0], [-0.8324799999999968, 2.2371499999999997], [0, 0], [-0.7155699999999996, 0.263300000000001], [0, 0], [2.2497299999999996, 0.8278200000000027], [0, 0], [0.26478999999999786, 0.7115700000000018], [0, 0], [0.8324799999999968, -2.2371499999999997], [0, 0], [0.7155699999999996, -0.263300000000001], [0, 0], [-2.2497299999999996, -0.8278200000000027], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0.3],
	              y: [0]
	            },
	            i: {
	              x: [1],
	              y: [1]
	            }
	          }, {
	            t: 245.88,
	            s: [{
	              c: true,
	              v: [[39.3124, 3.8283], [36.1365, 3.8283], [30.1127, 20.0145], [20.1092, 29.9608], [3.83, 35.9502], [3.83, 39.1079], [20.1092, 45.0973], [30.1127, 55.0436], [36.1366, 71.2297], [39.3125, 71.2297], [45.3363, 55.0435], [55.3398, 45.0972], [71.619, 39.1078], [71.619, 35.9501], [55.3398, 29.9607], [45.3363, 20.0144], [39.3124, 3.8283]],
	              i: [[0, 0], [0.5455, -1.4657], [0, 0], [4.6345, -1.7051], [0, 0], [-1.4741, -0.5423], [0, 0], [-1.7149, -4.608], [0, 0], [-0.5455, 1.4657], [0, 0], [-4.6345, 1.7051], [0, 0], [1.4741, 0.5423], [0, 0], [1.7149, 4.608], [0, 0]],
	              o: [[-0.5454700000000017, -1.4656800000000003], [0, 0], [-1.714929999999999, 4.608029999999999], [0, 0], [-1.4741, 0.542349999999999], [0, 0], [4.634520000000002, 1.705129999999997], [0, 0], [0.5454700000000017, 1.465680000000006], [0, 0], [1.7149300000000025, -4.608029999999999], [0, 0], [1.4740999999999929, -0.542349999999999], [0, 0], [-4.634520000000002, -1.7051300000000005], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0.3],
	              y: [0]
	            },
	            i: {
	              x: [1],
	              y: [1]
	            }
	          }, {
	            t: 356.178,
	            s: [{
	              c: true,
	              v: [[38.4728, 21.6471], [36.9761, 21.6471], [34.1372, 29.2751], [29.4227, 33.9624], [21.7506, 36.785], [21.7506, 38.2731], [29.4228, 41.0957], [34.1373, 45.7831], [36.9763, 53.4111], [38.4731, 53.4111], [41.3121, 45.7831], [46.0266, 41.0958], [53.6988, 38.2732], [53.6988, 36.7851], [46.0266, 33.9625], [41.3121, 29.2751], [38.4728, 21.6471]],
	              i: [[0, 0], [0.2571, -0.6907], [0, 0], [2.1842, -0.8036], [0, 0], [-0.6947, -0.2556], [0, 0], [-0.8082, -2.1716], [0, 0], [-0.2571, 0.6907], [0, 0], [-2.1842, 0.8036], [0, 0], [0.6947, 0.2556], [0, 0], [0.8082, 2.1716], [0, 0]],
	              o: [[-0.2570699999999988, -0.6907199999999989], [0, 0], [-0.8082199999999986, 2.1716000000000015], [0, 0], [-0.6947200000000002, 0.255589999999998], [0, 0], [2.1841800000000013, 0.8035700000000006], [0, 0], [0.2570699999999988, 0.6907199999999989], [0, 0], [0.8082199999999986, -2.171599999999998], [0, 0], [0.6947199999999967, -0.255589999999998], [0, 0], [-2.184179999999998, -0.8035700000000006], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0.3],
	              y: [0]
	            },
	            i: {
	              x: [1],
	              y: [1]
	            }
	          }, {
	            t: 474.312,
	            s: [{
	              c: true,
	              v: [[39.4485, 0.9397], [36.0004, 0.9397], [29.4602, 18.5132], [18.5993, 29.3121], [0.9248, 35.8149], [0.9248, 39.2433], [18.5994, 45.7461], [29.4603, 56.545], [36.0005, 74.1185], [39.4486, 74.1185], [45.9888, 56.545], [56.8497, 45.7462], [74.5243, 39.2434], [74.5243, 35.815], [56.8498, 29.3122], [45.9889, 18.5133], [39.4485, 0.9397]],
	              i: [[0, 0], [0.5922, -1.5913], [0, 0], [5.0318, -1.8513], [0, 0], [-1.6004, -0.5888], [0, 0], [-1.8619, -5.003], [0, 0], [-0.5922, 1.5913], [0, 0], [-5.0318, 1.8513], [0, 0], [1.6004, 0.5888], [0, 0], [1.8619, 5.003], [0, 0]],
	              o: [[-0.5922199999999975, -1.59131], [0, 0], [-1.8619200000000014, 5.00301], [0, 0], [-1.60045, 0.5888399999999976], [0, 0], [5.031760000000002, 1.8512800000000027], [0, 0], [0.5922199999999975, 1.591310000000007], [0, 0], [1.8619199999999978, -5.003], [0, 0], [1.600449999999995, -0.5888399999999976], [0, 0], [-5.031770000000002, -1.8512799999999991], [0, 0], [0, 0]]
	            }]
	          }]
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [1, 1, 1, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 2
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 7,
	    hd: false,
	    nm: "right-star - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [21.7973, 21.6844]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [128.4094, 121.8898]
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 8,
	    hd: false,
	    nm: "right-star",
	    parent: 7,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 1,
	          k: [{
	            t: 66.27,
	            s: [{
	              c: true,
	              v: [[22.6367, 3.8608], [20.958, 3.8608], [17.774, 12.4213], [12.4865, 17.6817], [3.8818, 20.8494], [3.8818, 22.5195], [12.4865, 25.6872], [17.774, 30.9476], [20.958, 39.5081], [22.6367, 39.5081], [25.8207, 30.9476], [31.1083, 25.6872], [39.713, 22.5195], [39.713, 20.8495], [31.1083, 17.6818], [25.8208, 12.4214], [22.6367, 3.8608]],
	              i: [[0, 0], [0.2883, -0.7752], [0, 0], [2.4497, -0.9018], [0, 0], [-0.7792, -0.2868], [0, 0], [-0.9065, -2.4371], [0, 0], [-0.2883, 0.7752], [0, 0], [-2.4497, 0.9018], [0, 0], [0.7792, 0.2868], [0, 0], [0.9065, 2.4371], [0, 0]],
	              o: [[-0.2883199999999988, -0.7751600000000001], [0, 0], [-0.9064599999999992, 2.4370899999999995], [0, 0], [-0.7791700000000001, 0.28684000000000154], [0, 0], [2.4496800000000007, 0.9018100000000011], [0, 0], [0.2883199999999988, 0.7751700000000028], [0, 0], [0.9064599999999992, -2.4370900000000013], [0, 0], [0.7791599999999974, -0.28684000000000154], [0, 0], [-2.4496800000000007, -0.9018100000000011], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0],
	              y: [0]
	            },
	            i: {
	              x: [0],
	              y: [1]
	            }
	          }, {
	            t: 156.072,
	            s: [{
	              c: true,
	              v: [[23.2285, -8.7091], [20.3661, -8.7091], [14.9369, 5.8887], [5.9209, 14.8589], [-8.7513, 20.2606], [-8.7513, 23.1084], [5.9209, 28.5101], [14.9369, 37.4804], [20.3661, 52.0782], [23.2285, 52.0782], [28.6577, 37.4805], [37.6737, 28.5103], [52.3459, 23.1086], [52.3459, 20.2608], [37.6737, 14.8591], [28.6577, 5.8889], [23.2285, -8.7091]],
	              i: [[0, 0], [0.4916, -1.3218], [0, 0], [4.177, -1.5378], [0, 0], [-1.3286, -0.4891], [0, 0], [-1.5456, -4.1558], [0, 0], [-0.4916, 1.3218], [0, 0], [-4.177, 1.5378], [0, 0], [1.3286, 0.4891], [0, 0], [1.5456, 4.1558], [0, 0]],
	              o: [[-0.49162000000000106, -1.32184], [0, 0], [-1.5456400000000006, 4.155840000000001], [0, 0], [-1.3285900000000002, 0.4891299999999994], [0, 0], [4.17703, 1.5378000000000007], [0, 0], [0.49162000000000106, 1.3218499999999977], [0, 0], [1.5456399999999988, -4.155839999999998], [0, 0], [1.3285699999999991, -0.4891299999999994], [0, 0], [-4.177030000000002, -1.5378000000000007], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0],
	              y: [0]
	            },
	            i: {
	              x: [0],
	              y: [1]
	            }
	          }, {
	            t: 283.854,
	            s: [{
	              c: true,
	              v: [[22.5686, 5.3037], [21.026, 5.3037], [18.1001, 13.1712], [13.2413, 18.0057], [5.3343, 20.9169], [5.3343, 22.4517], [13.2413, 25.3629], [18.1001, 30.1974], [21.026, 38.0649], [22.5686, 38.0649], [25.4945, 30.1974], [30.3533, 25.3629], [38.2603, 22.4517], [38.2603, 20.9169], [30.3533, 18.0057], [25.4945, 13.1712], [22.5686, 5.3037]],
	              i: [[0, 0], [0.2649, -0.7124], [0, 0], [2.251, -0.8288], [0, 0], [-0.716, -0.2636], [0, 0], [-0.833, -2.2398], [0, 0], [-0.2649, 0.7124], [0, 0], [-2.251, 0.8288], [0, 0], [0.716, 0.2636], [0, 0], [0.833, 2.2398], [0, 0]],
	              o: [[-0.2649399999999993, -0.7124100000000002], [0, 0], [-0.8329599999999999, 2.2398000000000007], [0, 0], [-0.7159899999999997, 0.2636199999999995], [0, 0], [2.251050000000001, 0.8288000000000011], [0, 0], [0.2649399999999993, 0.7124099999999984], [0, 0], [0.8329599999999999, -2.239799999999999], [0, 0], [0.7159800000000018, -0.2636199999999995], [0, 0], [-2.2510499999999993, -0.8288000000000011], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0],
	              y: [0]
	            },
	            i: {
	              x: [0],
	              y: [1]
	            }
	          }, {
	            t: 379.08,
	            s: [{
	              c: true,
	              v: [[23.233, -8.8011], [20.3615, -8.8011], [14.9151, 5.8409], [5.8706, 14.8383], [-8.8481, 20.2563], [-8.8481, 23.1128], [5.8706, 28.5308], [14.9152, 37.5282], [20.3616, 52.1701], [23.2331, 52.1701], [28.6795, 37.5282], [37.7241, 28.5308], [52.4428, 23.1128], [52.4428, 20.2563], [37.7241, 14.8383], [28.6795, 5.8409], [23.233, -8.8011]],
	              i: [[0, 0], [0.4932, -1.3258], [0, 0], [4.1903, -1.5425], [0, 0], [-1.3328, -0.4906], [0, 0], [-1.5505, -4.1684], [0, 0], [-0.4932, 1.3259], [0, 0], [-4.1903, 1.5425], [0, 0], [1.3328, 0.4906], [0, 0], [1.5505, 4.1684], [0, 0]],
	              o: [[-0.49317999999999884, -1.3258399999999995], [0, 0], [-1.5505399999999998, 4.16842], [0, 0], [-1.3328000000000007, 0.4906100000000002], [0, 0], [4.19027, 1.5424499999999988], [0, 0], [0.49317999999999884, 1.3258500000000026], [0, 0], [1.5505400000000016, -4.168419999999998], [0, 0], [1.3327799999999996, -0.4906100000000002], [0, 0], [-4.190269999999998, -1.5424500000000005], [0, 0], [0, 0]]
	            }],
	            o: {
	              x: [0],
	              y: [0]
	            },
	            i: {
	              x: [0],
	              y: [1]
	            }
	          }, {
	            t: 472.04999999999995,
	            s: [{
	              c: true,
	              v: [[22.6367, 3.8608], [20.958, 3.8608], [17.774, 12.4213], [12.4865, 17.6817], [3.8818, 20.8494], [3.8818, 22.5195], [12.4865, 25.6872], [17.774, 30.9476], [20.958, 39.5081], [22.6367, 39.5081], [25.8207, 30.9476], [31.1083, 25.6872], [39.713, 22.5195], [39.713, 20.8495], [31.1083, 17.6818], [25.8208, 12.4214], [22.6367, 3.8608]],
	              i: [[0, 0], [0.2883, -0.7752], [0, 0], [2.4497, -0.9018], [0, 0], [-0.7792, -0.2868], [0, 0], [-0.9065, -2.4371], [0, 0], [-0.2883, 0.7752], [0, 0], [-2.4497, 0.9018], [0, 0], [0.7792, 0.2868], [0, 0], [0.9065, 2.4371], [0, 0]],
	              o: [[-0.2883199999999988, -0.7751600000000001], [0, 0], [-0.9064599999999992, 2.4370899999999995], [0, 0], [-0.7791700000000001, 0.28684000000000154], [0, 0], [2.4496800000000007, 0.9018100000000011], [0, 0], [0.2883199999999988, 0.7751700000000028], [0, 0], [0.9064599999999992, -2.4370900000000013], [0, 0], [0.7791599999999974, -0.28684000000000154], [0, 0], [-2.4496800000000007, -0.9018100000000011], [0, 0], [0, 0]]
	            }]
	          }]
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [1, 1, 1, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 2
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 9,
	    hd: false,
	    nm: "round - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [42.3225, 39.1459]
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1
	  }, {
	    ty: 4,
	    ddd: 0,
	    ind: 10,
	    hd: false,
	    nm: "round",
	    parent: 9,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[65.2256, 129.7757], [130.4512, 64.8878], [65.2256, -0.0001], [0, 64.8878], [65.2256, 129.7757], [65.2256, 129.7757]],
	            i: [[0, 0], [0, 35.8366], [36.0231, 0], [0, -35.8366], [-36.0231, 0], [0, 0]],
	            o: [[36.023129999999995, 0], [0, -35.83657], [-36.02312, 0], [0, 35.836569999999995], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 100
	        },
	        c: {
	          a: 0,
	          k: [0.5764705882352941, 0.3568627450980392, 0.9254901960784314, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 2
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }, {
	    ty: 3,
	    ddd: 0,
	    ind: 11,
	    hd: false,
	    nm: "Ellipse 2 - Null",
	    parent: 4,
	    ks: {
	      a: {
	        a: 0,
	        k: [91.5, 91.5]
	      },
	      o: {
	        a: 0,
	        k: 100
	      },
	      p: {
	        a: 0,
	        k: [106.27800025906815, 104.90813283170486]
	      },
	      r: {
	        a: 1,
	        k: [{
	          t: 107.532,
	          s: [-179],
	          o: {
	            x: [0.5071],
	            y: [0.0048]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 227.298,
	          s: [-70],
	          o: {
	            x: [0.5071],
	            y: [0.0048]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 360.204,
	          s: [-104.87],
	          o: {
	            x: [0.5071],
	            y: [0.0048]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 442.95000000000005,
	          s: [17.67],
	          o: {
	            x: [0.5071],
	            y: [0.0048]
	          },
	          i: {
	            x: [0.15],
	            y: [1]
	          }
	        }, {
	          t: 537,
	          s: [181]
	        }]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1
	  }, {
	    ddd: 0,
	    ind: 12,
	    hd: false,
	    nm: "Ellipse 2 - Stroke",
	    parent: 11,
	    ks: {
	      a: {
	        a: 0,
	        k: [0, 0]
	      },
	      p: {
	        a: 0,
	        k: [0, 0]
	      },
	      s: {
	        a: 0,
	        k: [100, 100]
	      },
	      sk: {
	        a: 0,
	        k: 0
	      },
	      sa: {
	        a: 0,
	        k: 0
	      },
	      r: {
	        a: 0,
	        k: 0
	      },
	      o: {
	        a: 0,
	        k: 100
	      }
	    },
	    st: 0,
	    ip: 0,
	    op: 540,
	    bm: 0,
	    sr: 1,
	    ty: 4,
	    shapes: [{
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "sh",
	        nm: "Path",
	        hd: false,
	        ks: {
	          a: 0,
	          k: {
	            c: true,
	            v: [[183, 91.5], [91.5, 183], [0, 91.5], [91.5, 0], [183, 91.5], [183, 91.5]],
	            i: [[0, 0], [50.5355, 0], [0, 50.5355], [-50.5355, 0], [0, -50.5355], [0, 0]],
	            o: [[0, 50.53550000000001], [-50.5355, 0], [0, -50.5355], [50.53550000000001, 0], [0, 0], [0, 0]]
	          }
	        }
	      }, {
	        ty: "st",
	        o: {
	          a: 0,
	          k: 100
	        },
	        w: {
	          a: 0,
	          k: 22
	        },
	        c: {
	          a: 0,
	          k: [0.5764705882352941, 0.3568627450980392, 0.9254901960784314, 1]
	        },
	        ml: 4,
	        lc: 2,
	        lj: 2,
	        nm: "Stroke",
	        hd: false,
	        d: [{
	          n: "o",
	          nm: "Offset",
	          v: {
	            a: 0,
	            k: 60
	          }
	        }, {
	          n: "d",
	          nm: "Dash",
	          v: {
	            a: 1,
	            k: [{
	              t: 6.702,
	              s: [277],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 155.184,
	              s: [133.4122],
	              o: {
	                x: [0.5],
	                y: [0.35]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 226.644,
	              s: [133.4122],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 294.36600000000004,
	              s: [133],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 460.524,
	              s: [155],
	              o: {
	                x: [0.42],
	                y: [0]
	              },
	              i: {
	                x: [0.58],
	                y: [1]
	              }
	            }, {
	              t: 531.972,
	              s: [277]
	            }]
	          }
	        }, {
	          n: "g",
	          nm: "Gap",
	          v: {
	            a: 1,
	            k: [{
	              t: 6.6,
	              s: [90],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 117.576,
	              s: [80],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 226.644,
	              s: [86],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 320.694,
	              s: [56],
	              o: {
	                x: [0.5],
	                y: [0.35]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 399.072,
	              s: [56],
	              o: {
	                x: [0.5071],
	                y: [0.0048]
	              },
	              i: {
	                x: [0.15],
	                y: [1]
	              }
	            }, {
	              t: 507.528,
	              s: [90]
	            }]
	          }
	        }]
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }, {
	      ty: "tm",
	      s: {
	        a: 0,
	        k: 0
	      },
	      e: {
	        a: 0,
	        k: 100
	      },
	      o: {
	        a: 0,
	        k: 0
	      },
	      m: 1
	    }, {
	      ty: "gr",
	      nm: "Group",
	      hd: false,
	      np: 3,
	      it: [{
	        ty: "rc",
	        nm: "Rectangle",
	        hd: false,
	        p: {
	          a: 0,
	          k: [102.5, 102.5]
	        },
	        s: {
	          a: 0,
	          k: [410, 410]
	        },
	        r: {
	          a: 0,
	          k: 0
	        }
	      }, {
	        ty: "fl",
	        o: {
	          a: 0,
	          k: 0
	        },
	        c: {
	          a: 0,
	          k: [0, 1, 0, 1]
	        },
	        nm: "Fill",
	        hd: false,
	        r: 1
	      }, {
	        ty: "tr",
	        a: {
	          a: 0,
	          k: [0, 0]
	        },
	        p: {
	          a: 0,
	          k: [0, 0]
	        },
	        s: {
	          a: 0,
	          k: [100, 100]
	        },
	        sk: {
	          a: 0,
	          k: 0
	        },
	        sa: {
	          a: 0,
	          k: 0
	        },
	        r: {
	          a: 0,
	          k: 0
	        },
	        o: {
	          a: 0,
	          k: 100
	        }
	      }]
	    }]
	  }]
	}];
	var layers = [{
	  ddd: 0,
	  ind: 1,
	  ty: 0,
	  nm: "18",
	  refId: "lo8h9rmzawnjrpxsxm9",
	  sr: 1,
	  ks: {
	    a: {
	      a: 0,
	      k: [0, 0]
	    },
	    p: {
	      a: 0,
	      k: [0, 0]
	    },
	    s: {
	      a: 0,
	      k: [100, 100]
	    },
	    sk: {
	      a: 0,
	      k: 0
	    },
	    sa: {
	      a: 0,
	      k: 0
	    },
	    r: {
	      a: 0,
	      k: 0
	    },
	    o: {
	      a: 0,
	      k: 100
	    }
	  },
	  ao: 0,
	  w: 210,
	  h: 210,
	  ip: 0,
	  op: 540,
	  st: 0,
	  hd: false,
	  bm: 0
	}];
	var meta = {
	  a: "",
	  d: "",
	  tc: "",
	  g: "Aninix"
	};
	var copilotLottieIcon = {
	  nm: nm,
	  v: v,
	  fr: fr,
	  ip: ip,
	  op: op,
	  w: w,
	  h: h,
	  ddd: ddd,
	  markers: markers,
	  assets: assets,
	  layers: layers,
	  meta: meta
	};

	let _$4 = t => t,
	  _t$4,
	  _t2$1,
	  _t3$1;
	var _container$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _stopRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stopRecordingButton");
	var _startRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startRecordingButton");
	var _disabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disabled");
	var _initContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initContainer");
	var _renderStartRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStartRecordingButton");
	var _renderStopRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStopRecordingButton");
	var _enableStartRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableStartRecordingButton");
	var _disableStartRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableStartRecordingButton");
	var _enableStopRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableStopRecordingButton");
	var _disableStopRecordingButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableStopRecordingButton");
	class CopilotVoiceInputBtn extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _disableStopRecordingButton, {
	      value: _disableStopRecordingButton2
	    });
	    Object.defineProperty(this, _enableStopRecordingButton, {
	      value: _enableStopRecordingButton2
	    });
	    Object.defineProperty(this, _disableStartRecordingButton, {
	      value: _disableStartRecordingButton2
	    });
	    Object.defineProperty(this, _enableStartRecordingButton, {
	      value: _enableStartRecordingButton2
	    });
	    Object.defineProperty(this, _renderStopRecordingButton, {
	      value: _renderStopRecordingButton2
	    });
	    Object.defineProperty(this, _renderStartRecordingButton, {
	      value: _renderStartRecordingButton2
	    });
	    Object.defineProperty(this, _initContainer, {
	      value: _initContainer2
	    });
	    Object.defineProperty(this, _container$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _stopRecordingButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _startRecordingButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _disabled, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI:Copilot:VoiceButton');
	    babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2] = null;
	  }
	  start() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2], '--recording');
	  }
	  stop() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2], '--recording');
	  }
	  enable() {
	    babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _enableStartRecordingButton)[_enableStartRecordingButton]();
	    babelHelpers.classPrivateFieldLooseBase(this, _enableStopRecordingButton)[_enableStopRecordingButton]();
	  }
	  disable() {
	    babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled] = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _disableStartRecordingButton)[_disableStartRecordingButton]();
	    babelHelpers.classPrivateFieldLooseBase(this, _disableStopRecordingButton)[_disableStopRecordingButton]();
	  }
	  isDisabled() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled];
	  }
	  getContainer() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initContainer)[_initContainer]();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2];
	  }
	  render() {
	    return this.getContainer();
	  }
	}
	function _initContainer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2] = main_core.Tag.render(_t$4 || (_t$4 = _$4`
			<div class="ai__copilot-voice-input-btn-container">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderStartRecordingButton)[_renderStartRecordingButton](), babelHelpers.classPrivateFieldLooseBase(this, _renderStopRecordingButton)[_renderStopRecordingButton]());
	}
	function _renderStartRecordingButton2() {
	  const microphoneIcon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.MICROPHONE_ON,
	    size: 20
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton] = main_core.Tag.render(_t2$1 || (_t2$1 = _$4`
			<button
				class="ai__copilot-voice-input-btn --start"
			>
				${0}
			</button>
		`), microphoneIcon.render());
	  babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton].disabled = babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled];
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton], 'click', () => {
	    this.emit('start');
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton];
	}
	function _renderStopRecordingButton2() {
	  const stopIcon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Actions.STOP,
	    size: 17,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-on-primary')
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton] = main_core.Tag.render(_t3$1 || (_t3$1 = _$4`
			<button
				class="ai__copilot-voice-input-btn --stop"
			>
				${0}
			</button>
		`), stopIcon.render());
	  babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton].disabled = babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled];
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton], 'click', () => {
	    this.emit('stop');
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton];
	}
	function _enableStartRecordingButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton].disabled = false;
	  }
	}
	function _disableStartRecordingButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _startRecordingButton)[_startRecordingButton].disabled = true;
	  }
	}
	function _enableStopRecordingButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton].disabled = false;
	  }
	}
	function _disableStopRecordingButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _stopRecordingButton)[_stopRecordingButton].disabled = true;
	  }
	}

	let _$5 = t => t,
	  _t$5,
	  _t2$2,
	  _t3$2,
	  _t4$1,
	  _t5$1;
	var _errors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errors");
	var _detailedErrorInfoPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("detailedErrorInfoPopup");
	var _initDetailerErrorInfoPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDetailerErrorInfoPopup");
	var _getDetailedErrorInfoPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDetailedErrorInfoPopupContent");
	class CopilotInputError {
	  constructor(options) {
	    Object.defineProperty(this, _getDetailedErrorInfoPopupContent, {
	      value: _getDetailedErrorInfoPopupContent2
	    });
	    Object.defineProperty(this, _initDetailerErrorInfoPopup, {
	      value: _initDetailerErrorInfoPopup2
	    });
	    Object.defineProperty(this, _errors, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _detailedErrorInfoPopup, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors] = options.errors;
	  }
	  render() {
	    var _babelHelpers$classPr;
	    let message = babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors][babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors].length - 1].message;
	    const code = babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors][babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors].length - 1].code;
	    let detailBlock = null;
	    if (((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors][0]) == null ? void 0 : _babelHelpers$classPr.code) === 'AI_ENGINE_ERROR_OTHER') {
	      var _babelHelpers$classPr2, _babelHelpers$classPr3;
	      message = main_core.Loc.getMessage('AI_COPILOT_ERROR_OTHER');
	      message = message.replace('[feedback_form]', '<span class="ai__copilot_input-field-error-detail">');
	      message = message.replace('[/feedback_form]', '</span>');
	      detailBlock = main_core.Tag.render(_t$5 || (_t$5 = _$5`
				<div class="ai__copilot_input-field-error">
					${0}
				</div>
			`), message);
	      main_core.Event.bind(detailBlock.getElementsByClassName('ai__copilot_input-field-error-detail')[0], 'click', (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors][0]) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.customData) == null ? void 0 : _babelHelpers$classPr3.clickHandler);
	    } else if (top.BX && top.BX.Helper && code === 'AI_ENGINE_ERROR_PROVIDER') {
	      message = main_core.Loc.getMessage('AI_COPILOT_ERROR_PROVIDER');
	      message = message.replace('[link]', '<span class="ai__copilot_input-field-error-detail">');
	      message = message.replace('[/link]', '</span>');
	      detailBlock = main_core.Tag.render(_t2$2 || (_t2$2 = _$5`
				<div class="ai__copilot_input-field-error">
					${0}
				</div>
			`), message);
	      main_core.Event.bind(detailBlock.getElementsByClassName('ai__copilot_input-field-error-detail')[0], 'click', e => {
	        top.BX.Helper.show('redirect=detail&code=20267044');
	      });
	    } else {
	      detailBlock = main_core.Tag.render(_t3$2 || (_t3$2 = _$5`
				<div class="ai__copilot_input-field-error">
					${0}
				</div>
			`), message);
	    }
	    return main_core.Tag.render(_t4$1 || (_t4$1 = _$5`
			<div class="ai__copilot_input-field-error">
				<span class="ai__copilot_input-field-error-title">
					${0}
				</span>
			</div>
		`), detailBlock);
	  }
	  setErrors(errors) {
	    babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors] = errors;
	  }
	  getErrors() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors];
	  }
	}
	function _initDetailerErrorInfoPopup2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _detailedErrorInfoPopup)[_detailedErrorInfoPopup]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _detailedErrorInfoPopup)[_detailedErrorInfoPopup] = new main_popup.Popup({
	    id: 'ai__copilot_error-popup',
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getDetailedErrorInfoPopupContent)[_getDetailedErrorInfoPopupContent](),
	    darkMode: true,
	    maxWidth: 300,
	    autoHide: true,
	    closeByEsc: true,
	    closeIcon: true,
	    cacheable: true
	  });
	}
	function _getDetailedErrorInfoPopupContent2() {
	  return main_core.Tag.render(_t5$1 || (_t5$1 = _$5`
			<div class="ai__copilot_error-popup-content">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors][babelHelpers.classPrivateFieldLooseBase(this, _errors)[_errors].length - 1].message);
	}

	let _$6 = t => t,
	  _t$6;
	const CopilotInputPlaceholderEvents = Object.freeze({});
	var _container$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _readonly = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("readonly");
	var _useForImages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useForImages");
	var _updatePlaceholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updatePlaceholder");
	var _getPlaceholderText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPlaceholderText");
	class CopilotInputPlaceholder extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _getPlaceholderText, {
	      value: _getPlaceholderText2
	    });
	    Object.defineProperty(this, _updatePlaceholder, {
	      value: _updatePlaceholder2
	    });
	    Object.defineProperty(this, _container$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _readonly, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _useForImages, {
	      writable: true,
	      value: false
	    });
	    this.setEventNamespace('AI.Copilot.InputPlaceholder');
	    babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] = options.readonly === true;
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3] = this.getContainer();
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3];
	  }
	  getContainer() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3]) {
	      const placeholderText = babelHelpers.classPrivateFieldLooseBase(this, _getPlaceholderText)[_getPlaceholderText]();
	      babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3] = main_core.Tag.render(_t$6 || (_t$6 = _$6`
				<div class="ai_copilot_placeholder">
					<span>${0}</span>
				</div>
			`), placeholderText);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3];
	  }
	  setUseForImages(useForImages) {
	    babelHelpers.classPrivateFieldLooseBase(this, _useForImages)[_useForImages] = useForImages;
	    babelHelpers.classPrivateFieldLooseBase(this, _updatePlaceholder)[_updatePlaceholder]();
	  }
	}
	function _updatePlaceholder2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3].querySelector('span').innerText = babelHelpers.classPrivateFieldLooseBase(this, _getPlaceholderText)[_getPlaceholderText]();
	}
	function _getPlaceholderText2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	    return main_core.Loc.getMessage('AI_COPILOT_SELECT_COMMAND_BELOW');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _useForImages)[_useForImages]) {
	    return main_core.Loc.getMessage('AI_COPILOT_IMAGE_INPUT_START_PLACEHOLDER');
	  }
	  return main_core.Loc.getMessage('AI_COPILOT_INPUT_START_PLACEHOLDER');
	}

	let _$7 = t => t,
	  _t$7,
	  _t2$3,
	  _t3$3;
	const CopilotSubmitBtnEvents = Object.freeze({
	  submit: 'submit'
	});
	var _submitBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("submitBtn");
	var _container$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _renderHotKeyTag = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHotKeyTag");
	var _renderSubmitBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSubmitBtn");
	class CopilotSubmitBtn extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _renderSubmitBtn, {
	      value: _renderSubmitBtn2
	    });
	    Object.defineProperty(this, _renderHotKeyTag, {
	      value: _renderHotKeyTag2
	    });
	    Object.defineProperty(this, _submitBtn, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container$4, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI:Copilot:SubmitBtn');
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4] = main_core.Tag.render(_t$7 || (_t$7 = _$7`
			<div
				class="ai__copilot_input-submit-btn-container"
			>
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderHotKeyTag)[_renderHotKeyTag](), babelHelpers.classPrivateFieldLooseBase(this, _renderSubmitBtn)[_renderSubmitBtn]());
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4];
	  }
	  disable() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4], '--disabled');
	    babelHelpers.classPrivateFieldLooseBase(this, _submitBtn)[_submitBtn].disabled = true;
	  }
	  enable() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4], '--disabled');
	    babelHelpers.classPrivateFieldLooseBase(this, _submitBtn)[_submitBtn].disabled = false;
	  }
	}
	function _renderHotKeyTag2() {
	  const hotKeyIcon = new ui_iconSet_api_core.Icon({
	    size: 20,
	    icon: ui_iconSet_api_core.Actions.ARROW_TOP_2
	  });
	  return main_core.Tag.render(_t2$3 || (_t2$3 = _$7`
			<div class="ai__copilot_input-submit-hotkey">
				<div class="ai__copilot_input-submit-hotkey-icon">
					${0}
				</div>
				<div class="ai__copilot_input-submit-hotkey-text">Enter</div>
			</div>
		`), hotKeyIcon.render());
	}
	function _renderSubmitBtn2() {
	  const btnIcon = new ui_iconSet_api_core.Icon({
	    size: 18,
	    icon: ui_iconSet_api_core.Actions.ARROW_TOP,
	    color: '#fff'
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _submitBtn)[_submitBtn] = main_core.Tag.render(_t3$3 || (_t3$3 = _$7`
			<button class="ai__copilot_input-submit-btn">
				${0}
			</button>
		`), btnIcon.render());
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _submitBtn)[_submitBtn], 'click', e => {
	    this.emit(CopilotSubmitBtnEvents.submit);
	    e.preventDefault();
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _submitBtn)[_submitBtn];
	}

	let _$8 = t => t,
	  _t$8,
	  _t2$4,
	  _t3$4,
	  _t4$2,
	  _t5$2,
	  _t6$1,
	  _t7$1;
	const CopilotInputEvents = Object.freeze({
	  submit: 'submit',
	  cancelLoading: 'cancelLoading',
	  focus: 'focus',
	  input: 'input',
	  goOutFromBottom: 'goOutFromBottom',
	  startRecording: 'startRecording',
	  stopRecording: 'stopRecording',
	  adjustHeight: 'adjustHeight',
	  containerClick: 'containerClick'
	});
	var _textarea = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("textarea");
	var _container$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _isLoading = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isLoading");
	var _placeholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("placeholder");
	var _loaderTextContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaderTextContainer");
	var _errorContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorContainer");
	var _inputError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputError");
	var _textareaOldValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("textareaOldValue");
	var _disableEnterAndArrows = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableEnterAndArrows");
	var _copilotLottieAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotLottieAnimation");
	var _lottieIconContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lottieIconContainer");
	var _speechConverter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("speechConverter");
	var _submitBtn$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("submitBtn");
	var _voiceButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("voiceButton");
	var _readonly$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("readonly");
	var _useForImages$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useForImages");
	var _isGoOutFromBottomEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isGoOutFromBottomEnabled");
	var _usedVoiceRecord = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("usedVoiceRecord");
	var _usedTextInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("usedTextInput");
	var _setErrorIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setErrorIcon");
	var _setInputIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setInputIcon");
	var _setIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setIcon");
	var _getIconContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIconContainer");
	var _renderLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLoader");
	var _renderErrorContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderErrorContainer");
	var _renderTextArea = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderTextArea");
	var _handleKeyDownEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleKeyDownEvent");
	var _isArrowKey = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isArrowKey");
	var _handleEnterKeyDownEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleEnterKeyDownEvent");
	var _handleArrowKeyDownEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleArrowKeyDownEvent");
	var _isCursorInTextareaEnd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCursorInTextareaEnd");
	var _renderPlaceholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPlaceholder");
	var _updateContainerClassname = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateContainerClassname");
	var _renderInputIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderInputIcon");
	var _getLottieIconContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLottieIconContainer");
	var _renderErrorIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderErrorIcon");
	var _renderSubmitButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderSubmitButton");
	var _initVoiceButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initVoiceButton");
	var _initDisabledVoiceButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDisabledVoiceButton");
	var _initEnabledVoiceButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initEnabledVoiceButton");
	var _initSubmitButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initSubmitButton");
	var _initSpeechConverter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initSpeechConverter");
	var _handleSpeechConverterStartEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSpeechConverterStartEvent");
	var _handleSpeechConverterErrorEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSpeechConverterErrorEvent");
	var _showErrorHintForVoiceButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showErrorHintForVoiceButton");
	var _handleSpeechConverterResultEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSpeechConverterResultEvent");
	var _handleSpeechConverterStopEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSpeechConverterStopEvent");
	var _setTextareaValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTextareaValue");
	var _adjustTextareaHeight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustTextareaHeight");
	class CopilotInput extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    super(options);
	    Object.defineProperty(this, _adjustTextareaHeight, {
	      value: _adjustTextareaHeight2
	    });
	    Object.defineProperty(this, _setTextareaValue, {
	      value: _setTextareaValue2
	    });
	    Object.defineProperty(this, _handleSpeechConverterStopEvent, {
	      value: _handleSpeechConverterStopEvent2
	    });
	    Object.defineProperty(this, _handleSpeechConverterResultEvent, {
	      value: _handleSpeechConverterResultEvent2
	    });
	    Object.defineProperty(this, _showErrorHintForVoiceButton, {
	      value: _showErrorHintForVoiceButton2
	    });
	    Object.defineProperty(this, _handleSpeechConverterErrorEvent, {
	      value: _handleSpeechConverterErrorEvent2
	    });
	    Object.defineProperty(this, _handleSpeechConverterStartEvent, {
	      value: _handleSpeechConverterStartEvent2
	    });
	    Object.defineProperty(this, _initSpeechConverter, {
	      value: _initSpeechConverter2
	    });
	    Object.defineProperty(this, _initSubmitButton, {
	      value: _initSubmitButton2
	    });
	    Object.defineProperty(this, _initEnabledVoiceButton, {
	      value: _initEnabledVoiceButton2
	    });
	    Object.defineProperty(this, _initDisabledVoiceButton, {
	      value: _initDisabledVoiceButton2
	    });
	    Object.defineProperty(this, _initVoiceButton, {
	      value: _initVoiceButton2
	    });
	    Object.defineProperty(this, _renderSubmitButton, {
	      value: _renderSubmitButton2
	    });
	    Object.defineProperty(this, _renderErrorIcon, {
	      value: _renderErrorIcon2
	    });
	    Object.defineProperty(this, _getLottieIconContainer, {
	      value: _getLottieIconContainer2
	    });
	    Object.defineProperty(this, _renderInputIcon, {
	      value: _renderInputIcon2
	    });
	    Object.defineProperty(this, _updateContainerClassname, {
	      value: _updateContainerClassname2
	    });
	    Object.defineProperty(this, _renderPlaceholder, {
	      value: _renderPlaceholder2
	    });
	    Object.defineProperty(this, _isCursorInTextareaEnd, {
	      value: _isCursorInTextareaEnd2
	    });
	    Object.defineProperty(this, _handleArrowKeyDownEvent, {
	      value: _handleArrowKeyDownEvent2
	    });
	    Object.defineProperty(this, _handleEnterKeyDownEvent, {
	      value: _handleEnterKeyDownEvent2
	    });
	    Object.defineProperty(this, _isArrowKey, {
	      value: _isArrowKey2
	    });
	    Object.defineProperty(this, _handleKeyDownEvent, {
	      value: _handleKeyDownEvent2
	    });
	    Object.defineProperty(this, _renderTextArea, {
	      value: _renderTextArea2
	    });
	    Object.defineProperty(this, _renderErrorContainer, {
	      value: _renderErrorContainer2
	    });
	    Object.defineProperty(this, _renderLoader, {
	      value: _renderLoader2
	    });
	    Object.defineProperty(this, _getIconContainer, {
	      value: _getIconContainer2
	    });
	    Object.defineProperty(this, _setIcon, {
	      value: _setIcon2
	    });
	    Object.defineProperty(this, _setInputIcon, {
	      value: _setInputIcon2
	    });
	    Object.defineProperty(this, _setErrorIcon, {
	      value: _setErrorIcon2
	    });
	    Object.defineProperty(this, _textarea, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isLoading, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _placeholder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loaderTextContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _errorContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputError, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _textareaOldValue, {
	      writable: true,
	      value: ''
	    });
	    Object.defineProperty(this, _disableEnterAndArrows, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _copilotLottieAnimation, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _lottieIconContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _speechConverter, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _submitBtn$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _voiceButton, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _readonly$1, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _useForImages$1, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isGoOutFromBottomEnabled, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _usedVoiceRecord, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _usedTextInput, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _readonly$1)[_readonly$1] = options.readonly === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotLottieAnimation)[_copilotLottieAnimation] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _usedVoiceRecord)[_usedVoiceRecord] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _usedTextInput)[_usedTextInput] = false;
	    this.setEventNamespace('AI.Copilot.Input');
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5] = main_core.Tag.render(_t$8 || (_t$8 = _$8`
			<div class="ai__copilot_input-field">
				<div ref="icon" class="ai__copilot_input-field-icon">
					${0}
				</div>
				${0}
				<div class="ai__copilot_input-field-content">
					${0}
					${0}
					${0}
				</div>
				${0}
				<div class="ai__copilot_input-field-baas-point"></div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderInputIcon)[_renderInputIcon](), babelHelpers.classPrivateFieldLooseBase(this, _renderLoader)[_renderLoader](), babelHelpers.classPrivateFieldLooseBase(this, _renderTextArea)[_renderTextArea](), babelHelpers.classPrivateFieldLooseBase(this, _renderPlaceholder)[_renderPlaceholder](), babelHelpers.classPrivateFieldLooseBase(this, _renderErrorContainer)[_renderErrorContainer](), babelHelpers.classPrivateFieldLooseBase(this, _renderSubmitButton)[_renderSubmitButton]());
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].root, 'click', () => {
	      this.emit(CopilotInputEvents.containerClick);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _updateContainerClassname)[_updateContainerClassname]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].root;
	  }
	  usedTextInput() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _usedTextInput)[_usedTextInput];
	  }
	  usedVoiceRecord() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _usedVoiceRecord)[_usedVoiceRecord];
	  }
	  setValue(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setTextareaValue)[_setTextareaValue](value);
	  }
	  getValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].value;
	  }
	  focus(setCursorAtStart) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].focus(setCursorAtStart);
	      babelHelpers.classPrivateFieldLooseBase(this, _disableEnterAndArrows)[_disableEnterAndArrows] = false;
	    }
	  }
	  getContainer() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5]) == null ? void 0 : _babelHelpers$classPr.root;
	  }
	  clear() {
	    var _babelHelpers$classPr2;
	    babelHelpers.classPrivateFieldLooseBase(this, _setTextareaValue)[_setTextareaValue]('', false);
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton]) == null ? void 0 : _babelHelpers$classPr2.enable();
	  }
	  startGenerating() {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotLottieAnimation)[_copilotLottieAnimation].play();
	    this.clearErrors();
	    this.enable();
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading] = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _setTextareaValue)[_setTextareaValue]('', false);
	    main_core.Dom.addClass(this.getContainer(), '--loading');
	    main_core.Dom.removeClass(this.getContainer(), '--error');
	  }
	  finishGenerating() {
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _setTextareaValue)[_setTextareaValue](babelHelpers.classPrivateFieldLooseBase(this, _textareaOldValue)[_textareaOldValue], false);
	    main_core.Dom.removeClass(this.getContainer(), '--loading');
	    setTimeout(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotLottieAnimation)[_copilotLottieAnimation].stop();
	    }, 550);
	  }
	  stopRecording() {
	    var _babelHelpers$classPr3;
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter]) == null ? void 0 : _babelHelpers$classPr3.stop();
	  }
	  setErrors(errors) {
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError].setErrors(errors);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError] = new CopilotInputError({
	        errors
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _setErrorIcon)[_setErrorIcon]();
	    main_core.Dom.addClass(this.getContainer(), '--error');
	    const content = babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError].render();
	    main_core.Dom.append(content, babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]);
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = true;
	    requestAnimationFrame(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _adjustTextareaHeight)[_adjustTextareaHeight]();
	    });
	  }
	  adjustHeight() {
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustTextareaHeight)[_adjustTextareaHeight]();
	  }
	  clearErrors() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError] && babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError].getErrors().length > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError].setErrors([]);
	      main_core.Dom.removeClass(this.getContainer(), '--error');
	      babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _setInputIcon)[_setInputIcon]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustTextareaHeight)[_adjustTextareaHeight]();
	  }
	  enableEnterAndArrows() {
	    babelHelpers.classPrivateFieldLooseBase(this, _disableEnterAndArrows)[_disableEnterAndArrows] = false;
	  }
	  disableEnterAndArrows() {
	    babelHelpers.classPrivateFieldLooseBase(this, _disableEnterAndArrows)[_disableEnterAndArrows] = true;
	  }
	  disable() {
	    main_core.Dom.addClass(this.getContainer(), '--disabled');
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = true;
	    main_core.Dom.style(this.getContainer(), 'opacity', 0.7);
	    this.disableEnterAndArrows();
	  }
	  enable() {
	    main_core.Dom.removeClass(this.getContainer(), '--disabled');
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = false;
	    main_core.Dom.style(this.getContainer(), 'opacity', 1);
	    this.enableEnterAndArrows();
	  }
	  isDisabled() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled;
	  }
	  setUseForImages(useForImages) {
	    babelHelpers.classPrivateFieldLooseBase(this, _useForImages$1)[_useForImages$1] = useForImages;
	    babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder].setUseForImages(useForImages);
	    if (useForImages) {
	      babelHelpers.classPrivateFieldLooseBase(this, _isGoOutFromBottomEnabled)[_isGoOutFromBottomEnabled] = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _loaderTextContainer)[_loaderTextContainer].innerText = main_core.Loc.getMessage('AI_COPILOT_INPUT_IMAGE_LOADER_TEXT');
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _isGoOutFromBottomEnabled)[_isGoOutFromBottomEnabled] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _loaderTextContainer)[_loaderTextContainer].innerText = main_core.Loc.getMessage('AI_COPILOT_INPUT_LOADER_TEXT');
	    }
	  }
	  setHtmlContent(html) {
	    let htmlWithReplaced = main_core.Text.encode(html);
	    htmlWithReplaced = htmlWithReplaced.replaceAll('[', '<strong>[');
	    htmlWithReplaced = htmlWithReplaced.replaceAll(']', ']</strong>');
	    htmlWithReplaced = htmlWithReplaced.replaceAll('\n', '<br />');
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].setHtmlContent(htmlWithReplaced);
	  }
	}
	function _setErrorIcon2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _setIcon)[_setIcon](babelHelpers.classPrivateFieldLooseBase(this, _renderErrorIcon)[_renderErrorIcon]());
	}
	function _setInputIcon2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _setIcon)[_setIcon](babelHelpers.classPrivateFieldLooseBase(this, _renderInputIcon)[_renderInputIcon]());
	}
	function _setIcon2(icon) {
	  if (icon.className === babelHelpers.classPrivateFieldLooseBase(this, _getIconContainer)[_getIconContainer]().firstElementChild.className) {
	    return;
	  }
	  main_core.Event.bindOnce(babelHelpers.classPrivateFieldLooseBase(this, _getIconContainer)[_getIconContainer](), 'transitionend', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _getIconContainer)[_getIconContainer]().innerHTML = '';
	    main_core.Dom.append(icon, babelHelpers.classPrivateFieldLooseBase(this, _getIconContainer)[_getIconContainer]());
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getIconContainer)[_getIconContainer](), 'opacity', 1);
	  });
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getIconContainer)[_getIconContainer](), 'opacity', 0);
	}
	function _getIconContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].icon;
	}
	function _renderLoader2() {
	  const cancelBtn = main_core.Tag.render(_t2$4 || (_t2$4 = _$8`
			<button class="ai__copilot_loader-cancel-btn">
				${0}
			</button>
		`), main_core.Loc.getMessage('AI_COPILOT_INPUT_LOADER_CANCEL'));
	  main_core.Event.bind(cancelBtn, 'click', () => {
	    this.emit(CopilotInputEvents.cancelLoading);
	  });
	  const loader = main_core.Tag.render(_t3$4 || (_t3$4 = _$8`
			<div class="ai__copilot_loader">
				<div class="ai__copilot_loader-left">
					<div ref="loaderText" class="ai__copilot_loader-text">${0}</div>
					<div class="ai__copilot_loader-dot dot-flashing"></div>
				</div>
				${0}
			</div>
		`), main_core.Loc.getMessage('AI_COPILOT_INPUT_LOADER_TEXT'), cancelBtn);
	  babelHelpers.classPrivateFieldLooseBase(this, _loaderTextContainer)[_loaderTextContainer] = loader.loaderText;
	  return loader.root;
	}
	function _renderErrorContainer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer] = main_core.Tag.render(_t4$2 || (_t4$2 = _$8`
			<div class="ai__copilot_input-field-error-container"></div>
		`));
	  return babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer];
	}
	function _renderTextArea2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea] = new CopilotInputFieldTextarea({});
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].subscribe('focus', () => {
	    this.emit(CopilotInputEvents.focus);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].subscribe('input', e => {
	    const value = e.getData();
	    babelHelpers.classPrivateFieldLooseBase(this, _setTextareaValue)[_setTextareaValue](value);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter] && babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].isRecording() === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _usedTextInput)[_usedTextInput] = true;
	      if (value) {
	        babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].disable();
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].enable();
	      }
	    }
	  });
	  const textAreaContainer = babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].render();
	  main_core.Event.bind(textAreaContainer, 'keydown', babelHelpers.classPrivateFieldLooseBase(this, _handleKeyDownEvent)[_handleKeyDownEvent].bind(this));
	  const observer = new MutationObserver(mutations => {
	    mutations.forEach(mutation => {
	      if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled === true) {
	          main_core.Dom.style(textAreaContainer, 'z-index', -1);
	        } else {
	          main_core.Dom.style(textAreaContainer, 'z-index', 1);
	        }
	      }
	    });
	  });
	  observer.observe(textAreaContainer, {
	    attributes: true
	  });
	  return textAreaContainer;
	}
	function _handleKeyDownEvent2(e) {
	  if ((e.key === 'Enter' || babelHelpers.classPrivateFieldLooseBase(this, _isArrowKey)[_isArrowKey](e.key)) && babelHelpers.classPrivateFieldLooseBase(this, _disableEnterAndArrows)[_disableEnterAndArrows]) {
	    e.preventDefault();
	    return false;
	  }
	  if (e.key === 'Enter') {
	    return babelHelpers.classPrivateFieldLooseBase(this, _handleEnterKeyDownEvent)[_handleEnterKeyDownEvent](e);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isArrowKey)[_isArrowKey](e.key)) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _handleArrowKeyDownEvent)[_handleArrowKeyDownEvent](e);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _disableEnterAndArrows)[_disableEnterAndArrows] = false;
	  return true;
	}
	function _isArrowKey2(key) {
	  return key === 'ArrowDown' || key === 'ArrowUp' || key === 'ArrowLeft' || key === 'ArrowRight';
	}
	function _handleEnterKeyDownEvent2(e) {
	  if (e.key === 'Enter' && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.repeat && !babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading] && !babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled) {
	    this.emit(CopilotInputEvents.submit);
	    e.preventDefault();
	    return false;
	  }
	  return true;
	}
	function _handleArrowKeyDownEvent2(e) {
	  if (e.key === 'ArrowDown' && babelHelpers.classPrivateFieldLooseBase(this, _isCursorInTextareaEnd)[_isCursorInTextareaEnd]() && babelHelpers.classPrivateFieldLooseBase(this, _isGoOutFromBottomEnabled)[_isGoOutFromBottomEnabled]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _disableEnterAndArrows)[_disableEnterAndArrows] = true;
	    this.emit(CopilotInputEvents.goOutFromBottom);
	    return false;
	  }
	  return true;
	}
	function _isCursorInTextareaEnd2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].isCursorInTheEnd();
	}
	function _renderPlaceholder2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder] = new CopilotInputPlaceholder({
	    readonly: babelHelpers.classPrivateFieldLooseBase(this, _readonly$1)[_readonly$1],
	    useForImages: babelHelpers.classPrivateFieldLooseBase(this, _useForImages$1)[_useForImages$1]
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder].getContainer(), 'click', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _readonly$1)[_readonly$1] === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].focus();
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder].render();
	}
	function _updateContainerClassname2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].value.length === 0) {
	    main_core.Dom.addClass(this.getContainer(), '--show-placeholder');
	  } else {
	    main_core.Dom.removeClass(this.getContainer(), '--show-placeholder');
	  }
	}
	function _renderInputIcon2() {
	  return main_core.Tag.render(_t5$2 || (_t5$2 = _$8`
			<div class="" style="width: 24px; height: 24px; position: relative;">
				<div class="ai__copilot_static-icon-wrapper">
					<div class="ai__copilot_static-icon"></div>
				</div>
				<div class="ai__copilot_loading-icon-wrapper">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getLottieIconContainer)[_getLottieIconContainer]());
	}
	function _getLottieIconContainer2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer]) {
	    const size = 21;
	    babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer] = main_core.Tag.render(_t6$1 || (_t6$1 = _$8`
				<div class="" style="width: ${0}px; height: ${0}px;"></div>
			`), size, size);
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotLottieAnimation)[_copilotLottieAnimation] = ui_lottie.Lottie.loadAnimation({
	      container: babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer],
	      renderer: 'svg',
	      animationData: copilotLottieIcon,
	      autoplay: false
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _lottieIconContainer)[_lottieIconContainer];
	}
	function _renderErrorIcon2() {
	  const icon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.WARNING,
	    size: 24
	  });
	  return icon.render();
	}
	function _renderSubmitButton2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _readonly$1)[_readonly$1]) {
	    return null;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _initVoiceButton)[_initVoiceButton]();
	  babelHelpers.classPrivateFieldLooseBase(this, _initSubmitButton)[_initSubmitButton]();
	  return main_core.Tag.render(_t7$1 || (_t7$1 = _$8`
			<div class="ai__copilot_input-submit-block">
				${0}
				<div class="ai__copilot_input-submit-block-voice-btn">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _submitBtn$1)[_submitBtn$1].render(), babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].render());
	}
	function _initVoiceButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton] = new CopilotVoiceInputBtn();
	  if (ai_speechConverter.SpeechConverter.isBrowserSupport() === false) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initDisabledVoiceButton)[_initDisabledVoiceButton]();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _initEnabledVoiceButton)[_initEnabledVoiceButton]();
	  }
	}
	function _initDisabledVoiceButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].disable();
	  CopilotHint.addHintOnTargetHover({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].getContainer(),
	    text: main_core.Loc.getMessage('AI_COPILOT_VOICE_INPUT_NOT_SUPPORT')
	  });
	}
	function _initEnabledVoiceButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _initSpeechConverter)[_initSpeechConverter]();
	  main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].getContainer(), 'click', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].isDisabled() || babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].isRecording()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].start();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].subscribe('stop', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].stop();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].value) {
	      babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].disable();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _usedVoiceRecord)[_usedVoiceRecord] = true;
	  });
	}
	function _initSubmitButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _submitBtn$1)[_submitBtn$1] = new CopilotSubmitBtn();
	  babelHelpers.classPrivateFieldLooseBase(this, _submitBtn$1)[_submitBtn$1].subscribe(CopilotSubmitBtnEvents.submit, () => {
	    this.emit(CopilotInputEvents.submit);
	  });
	}
	function _initSpeechConverter2() {
	  if (ai_speechConverter.SpeechConverter.isBrowserSupport() === false) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter] = new ai_speechConverter.SpeechConverter();
	  babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].subscribe(ai_speechConverter.speechConverterEvents.start, babelHelpers.classPrivateFieldLooseBase(this, _handleSpeechConverterStartEvent)[_handleSpeechConverterStartEvent].bind(this));
	  babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].subscribe(ai_speechConverter.speechConverterEvents.error, babelHelpers.classPrivateFieldLooseBase(this, _handleSpeechConverterErrorEvent)[_handleSpeechConverterErrorEvent].bind(this));
	  babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].subscribe(ai_speechConverter.speechConverterEvents.result, babelHelpers.classPrivateFieldLooseBase(this, _handleSpeechConverterResultEvent)[_handleSpeechConverterResultEvent].bind(this));
	  babelHelpers.classPrivateFieldLooseBase(this, _speechConverter)[_speechConverter].subscribe(ai_speechConverter.speechConverterEvents.stop, babelHelpers.classPrivateFieldLooseBase(this, _handleSpeechConverterStopEvent)[_handleSpeechConverterStopEvent].bind(this));
	}
	function _handleSpeechConverterStartEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].start();
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = true;
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].addClass('--recording');
	  this.emit(CopilotInputEvents.startRecording);
	  babelHelpers.classPrivateFieldLooseBase(this, _submitBtn$1)[_submitBtn$1].disable();
	}
	function _handleSpeechConverterErrorEvent2(e) {
	  const {
	    error
	  } = e.getData();
	  if (error === 'aborted') {
	    return;
	  }
	  if (error === 'not-allowed') {
	    babelHelpers.classPrivateFieldLooseBase(this, _showErrorHintForVoiceButton)[_showErrorHintForVoiceButton](main_core.Loc.getMessage('AI_COPILOT_VOICE_INPUT_MICRO_NOT_ALLOWED'));
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _showErrorHintForVoiceButton)[_showErrorHintForVoiceButton](main_core.Loc.getMessage('AI_COPILOT_VOICE_INPUT_UNKNOWN_ERROR'));
	  }
	}
	function _showErrorHintForVoiceButton2(text) {
	  const errorHint = new CopilotHint({
	    text,
	    target: babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].getContainer()
	  });
	  errorHint.show();
	  setTimeout(() => {
	    errorHint.hide();
	  }, 1500);
	}
	function _handleSpeechConverterResultEvent2(e) {
	  this.setValue(e.getData().text);
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].value = e.getData().text;
	}
	function _handleSpeechConverterStopEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].removeClass('--recording');
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].disabled = false;
	  babelHelpers.classPrivateFieldLooseBase(this, _voiceButton)[_voiceButton].stop();
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].focus();
	  this.emit(CopilotInputEvents.stopRecording);
	  babelHelpers.classPrivateFieldLooseBase(this, _submitBtn$1)[_submitBtn$1].enable();
	}
	function _setTextareaValue2(value, emitEvent = true) {
	  babelHelpers.classPrivateFieldLooseBase(this, _textareaOldValue)[_textareaOldValue] = babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].value;
	  babelHelpers.classPrivateFieldLooseBase(this, _adjustTextareaHeight)[_adjustTextareaHeight]();
	  babelHelpers.classPrivateFieldLooseBase(this, _updateContainerClassname)[_updateContainerClassname]();
	  if (emitEvent) {
	    this.emit(CopilotInputEvents.input, new main_core_events.BaseEvent({
	      data: value
	    }));
	  }
	}
	function _adjustTextareaHeight2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].setStyle('height', 'auto');
	  const textAreaPaddingBottom = parseInt(babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].getComputedStyle().getPropertyValue('padding-bottom'), 10);
	  const errorFieldHeight = main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]).height;
	  const placeholderHeight = main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder].getContainer()).height;
	  const textAreaHeight = babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].scrollHeight;
	  const hasTextAreaMoreThanOneRow = textAreaHeight - textAreaPaddingBottom > 40;
	  if (hasTextAreaMoreThanOneRow) {
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].addClass('--with-padding-bottom');
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].removeClass('--with-padding-bottom');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isLoading)[_isLoading]) {
	    const loaderHeight = main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _loaderTextContainer)[_loaderTextContainer]).height;
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].setStyle('height', `${loaderHeight}px`);
	  } else {
	    let newTextAreaHeight = babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError] && babelHelpers.classPrivateFieldLooseBase(this, _inputError)[_inputError].getErrors().length > 0 ? errorFieldHeight : babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].scrollHeight;
	    if (placeholderHeight > newTextAreaHeight && !babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].value) {
	      newTextAreaHeight = placeholderHeight;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _textarea)[_textarea].setStyle('height', `${newTextAreaHeight}px`);
	  }
	  this.emit(CopilotInputEvents.adjustHeight);
	}

	let _$9 = t => t,
	  _t$9,
	  _t2$5;
	var _container$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _renderReadMoreLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderReadMoreLink");
	class CopilotWarningResultField {
	  constructor() {
	    Object.defineProperty(this, _renderReadMoreLink, {
	      value: _renderReadMoreLink2
	    });
	    Object.defineProperty(this, _container$6, {
	      writable: true,
	      value: null
	    });
	  }
	  render(expanded = false) {
	    const warningIcon = new ui_iconSet_api_core.Icon({
	      color: getComputedStyle(document.body).getPropertyValue('--ui-color-base-40'),
	      size: 22,
	      icon: ui_iconSet_api_core.Main.WARNING
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6] = main_core.Tag.render(_t$9 || (_t$9 = _$9`
			<div class="ai__copilot_waning-field ${0}">
				<span class="ai__copilot_waning-field-icon">
					${0}
				</span>
				<span class="ai__copilot_waning-field-text">
					${0}
				</span>
				${0}
			</div>
		`), expanded ? '--expanded' : '', warningIcon.render(), main_core.Loc.getMessage('AI_COPILOT_RESULT_WARNING'), babelHelpers.classPrivateFieldLooseBase(this, _renderReadMoreLink)[_renderReadMoreLink]());
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6];
	  }
	  getInfoSliderContainer() {
	    var _top$BX$Helper$getSli;
	    return (_top$BX$Helper$getSli = top.BX.Helper.getSlider()) == null ? void 0 : _top$BX$Helper$getSli.getContainer();
	  }
	  expand() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6], '--expanded');
	  }
	  collapse() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6], '--expanded');
	  }
	}
	function _renderReadMoreLink2() {
	  const link = main_core.Tag.render(_t2$5 || (_t2$5 = _$9`
			<span class="ai__copilot_waning-field-link">
				${0}
			</span>
		`), main_core.Loc.getMessage('AI_COPILOT_RESULT_WARNING_MORE'));
	  main_core.Event.bind(link, 'click', () => {
	    const articleCode = 20412666;
	    if (top.BX && top.BX.Helper) {
	      top.BX.Helper.show(`redirect=detail&code=${articleCode}`);
	    }
	  });
	  return link;
	}

	let _$a = t => t,
	  _t$a;
	const CopilotMode = Object.freeze({
	  TEXT: 'text',
	  IMAGE: 'image',
	  TEXT_AND_IMAGE: 'text-and-image'
	});
	const CopilotEvents = {
	  START_INIT: 'start-init',
	  FINISH_INIT: 'finish-init',
	  FAILED_INIT: 'failed-init',
	  HIDE: 'hide',
	  IMAGE_SAVE: 'save-image',
	  TEXT_SAVE: 'save',
	  IMAGE_PLACE_ABOVE: 'place-image-above',
	  IMAGE_PLACE_UNDER: 'place-image-under',
	  IMAGE_CANCEL: 'cancel-image',
	  TEXT_CANCEL: 'cancel',
	  IMAGE_COMPLETION_RESULT: 'image-completion-result',
	  TEXT_COMPLETION_RESULT: 'text-completion-result',
	  TEXT_PLACE_BELOW: 'add_below'
	};
	const cache = new main_core.Cache.MemoryCache();
	async function loadExtensionWrapper(extensionName) {
	  return cache.remember(extensionName, () => {
	    return main_core.Runtime.loadExtension(extensionName);
	  });
	}
	var _copilotPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotPopup");
	var _inputField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	var _resultField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultField");
	var _engine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _preventAutoHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preventAutoHide");
	var _autoHide$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("autoHide");
	var _container$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _warningField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("warningField");
	var _copilotBanner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotBanner");
	var _copilotAgreementWasApplied = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotAgreementWasApplied");
	var _copilotImageController = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotImageController");
	var _copilotTextController = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotTextController");
	var _readonly$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("readonly");
	var _category$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _selectedText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedText");
	var _context = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _useText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useText");
	var _useImage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useImage");
	var _showResultInCopilot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showResultInCopilot");
	var _windowResizeHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("windowResizeHandler");
	var _staticEulaRestrictCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("staticEulaRestrictCallback");
	var _getBaasPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBaasPopup");
	var _initCopilotPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initCopilotPopup");
	var _showCopilotAfterCopilotBanner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCopilotAfterCopilotBanner");
	var _initEngine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initEngine");
	var _initCopilotImageController = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initCopilotImageController");
	var _initCopilotTextController = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initCopilotTextController");
	var _initCopilotTextControllerMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initCopilotTextControllerMenu");
	var _getOpenMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOpenMenu");
	var _mouseDownHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mouseDownHandler");
	var _autoHideHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("autoHideHandler");
	var _hideMenus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideMenus");
	var _adjustMenus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustMenus");
	var _getAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAnalytics");
	var _initAnalyticForText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initAnalyticForText");
	var _initAnalyticForReadOnly = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initAnalyticForReadOnly");
	var _showCopilotAfterApplyAgreement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCopilotAfterApplyAgreement");
	class Copilot extends main_core_events.EventEmitter {
	  /**
	   * If function returns TRUE - ai using is restricted,
	   * If FALSE - ai using is available
	   * @returns {Promise<boolean>}
	   */
	  static async checkEulaRestrict() {
	    var _babelHelpers$classPr;
	    const Feature = await loadExtensionWrapper('bitrix24.license.feature');
	    if (!(Feature != null && Feature.Feature)) {
	      return false;
	    }
	    const isRestrictionCheckInProgress = main_core.Type.isFunction((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback]) == null ? void 0 : _babelHelpers$classPr.then);
	    const isRestrictionNotChecked = babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback] === null;
	    if (isRestrictionNotChecked || isRestrictionCheckInProgress) {
	      try {
	        if (isRestrictionNotChecked) {
	          babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback] = Feature.Feature.checkEulaRestrictions('ai_available_by_version');
	        }
	        await babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback];
	        babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback] = false;
	        return false;
	      } catch (err) {
	        if (err.callback) {
	          babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback] = err.callback;
	          return true;
	        }
	        console.error(err);
	        return false;
	      }
	    }
	    return main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback]);
	  }
	  constructor(_options) {
	    var _options$autoHide;
	    super(_options);
	    Object.defineProperty(this, _showCopilotAfterApplyAgreement, {
	      value: _showCopilotAfterApplyAgreement2
	    });
	    Object.defineProperty(this, _initAnalyticForReadOnly, {
	      value: _initAnalyticForReadOnly2
	    });
	    Object.defineProperty(this, _initAnalyticForText, {
	      value: _initAnalyticForText2
	    });
	    Object.defineProperty(this, _getAnalytics, {
	      value: _getAnalytics2
	    });
	    Object.defineProperty(this, _adjustMenus, {
	      value: _adjustMenus2
	    });
	    Object.defineProperty(this, _hideMenus, {
	      value: _hideMenus2
	    });
	    Object.defineProperty(this, _autoHideHandler, {
	      value: _autoHideHandler2
	    });
	    Object.defineProperty(this, _mouseDownHandler, {
	      value: _mouseDownHandler2
	    });
	    Object.defineProperty(this, _getOpenMenu, {
	      value: _getOpenMenu2
	    });
	    Object.defineProperty(this, _initCopilotTextControllerMenu, {
	      value: _initCopilotTextControllerMenu2
	    });
	    Object.defineProperty(this, _initCopilotTextController, {
	      value: _initCopilotTextController2
	    });
	    Object.defineProperty(this, _initCopilotImageController, {
	      value: _initCopilotImageController2
	    });
	    Object.defineProperty(this, _initEngine, {
	      value: _initEngine2
	    });
	    Object.defineProperty(this, _showCopilotAfterCopilotBanner, {
	      value: _showCopilotAfterCopilotBanner2
	    });
	    Object.defineProperty(this, _initCopilotPopup, {
	      value: _initCopilotPopup2
	    });
	    Object.defineProperty(this, _getBaasPopup, {
	      value: _getBaasPopup2
	    });
	    Object.defineProperty(this, _copilotPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preventAutoHide, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _autoHide$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container$7, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _warningField, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _copilotBanner, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotAgreementWasApplied, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotImageController, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotTextController, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _readonly$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _category$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedText, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _context, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useText, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useImage, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _showResultInCopilot, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _windowResizeHandler, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.Copilot');
	    babelHelpers.classPrivateFieldLooseBase(this, _category$1)[_category$1] = _options.category;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedText)[_selectedText] = _options.selectedText;
	    babelHelpers.classPrivateFieldLooseBase(this, _readonly$2)[_readonly$2] = _options.readonly === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = _options.context;
	    babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText] = main_core.Type.isBoolean(_options.useText) ? _options.useText : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _useImage)[_useImage] = _options.useImage === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _showResultInCopilot)[_showResultInCopilot] = _options.showResultInCopilot;
	    babelHelpers.classPrivateFieldLooseBase(this, _initEngine)[_initEngine]({
	      category: _options.category,
	      contextId: _options.contextId,
	      moduleId: _options.moduleId,
	      contextParameters: _options.contextParameters,
	      extraMarkers: _options.extraMarkers
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField] = new CopilotInput({
	      readonly: _options.readonly === true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField] = new CopilotResult();
	    babelHelpers.classPrivateFieldLooseBase(this, _warningField)[_warningField] = new CopilotWarningResultField();
	    babelHelpers.classPrivateFieldLooseBase(this, _autoHide$1)[_autoHide$1] = (_options$autoHide = _options.autoHide) != null ? _options$autoHide : false;
	    babelHelpers.classPrivateFieldLooseBase(this, _preventAutoHide)[_preventAutoHide] = main_core.Type.isFunction(_options.preventAutoHide) ? _options.preventAutoHide : () => false;
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7] = main_core.Tag.render(_t$a || (_t$a = _$a`
			<div class="ai__copilot ai__copilot-scope">
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField].render(), babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].render(), babelHelpers.classPrivateFieldLooseBase(this, _warningField)[_warningField].render());
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7];
	  }
	  async init() {
	    this.emit(CopilotEvents.START_INIT);
	    try {
	      if (main_core.Extension.getSettings('ai.copilot').isRestrictByEula) {
	        await Copilot.checkEulaRestrict();
	      }
	      if (babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText]) {
	        await babelHelpers.classPrivateFieldLooseBase(this, _initCopilotTextController)[_initCopilotTextController]({
	          readonly: babelHelpers.classPrivateFieldLooseBase(this, _readonly$2)[_readonly$2],
	          category: babelHelpers.classPrivateFieldLooseBase(this, _category$1)[_category$1],
	          selectedText: babelHelpers.classPrivateFieldLooseBase(this, _selectedText)[_selectedText],
	          context: babelHelpers.classPrivateFieldLooseBase(this, _context)[_context],
	          addImageMenuItem: babelHelpers.classPrivateFieldLooseBase(this, _useImage)[_useImage]
	        });
	        await babelHelpers.classPrivateFieldLooseBase(this, _initCopilotTextControllerMenu)[_initCopilotTextControllerMenu]();
	        if (babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].isFirstLaunch()) {
	          const {
	            AppsInstallerBanner
	          } = await main_core.Runtime.loadExtension('ai.copilot-banner');
	          babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner] = new AppsInstallerBanner({});
	        }
	      } else {
	        await babelHelpers.classPrivateFieldLooseBase(this, _initCopilotImageController)[_initCopilotImageController]();
	      }
	      this.emit(CopilotEvents.FINISH_INIT);
	    } catch (err) {
	      console.error(err);
	      this.emit(CopilotEvents.FAILED_INIT);
	    }
	  }
	  show(options) {
	    var _babelHelpers$classPr2;
	    if (this.isInitFinished() === false) {
	      console.error('AI.Copilot: The copilot cannot be opened until initialization is complete.');
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController] && babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].isPromptsLoaded() === false) {
	      console.error('AI.Copilot: Prompts were not loaded!');
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showCopilotAfterCopilotBanner)[_showCopilotAfterCopilotBanner](options);
	      return;
	    }
	    if (main_core.Extension.getSettings('ai.copilot').get('isShowAgreementPopup') && !babelHelpers.classPrivateFieldLooseBase(this, _copilotAgreementWasApplied)[_copilotAgreementWasApplied]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showCopilotAfterApplyAgreement)[_showCopilotAfterApplyAgreement](options);
	      return;
	    }
	    if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback])) {
	      babelHelpers.classPrivateFieldLooseBase(Copilot, _staticEulaRestrictCallback)[_staticEulaRestrictCallback]();
	      babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics)[_getAnalytics](true).sendEventOpen('error_agreement');
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) {
	      main_core.Event.bind(document, 'mousedown', babelHelpers.classPrivateFieldLooseBase(this, _mouseDownHandler)[_mouseDownHandler].bind(this));
	    }
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr2.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _initCopilotPopup)[_initCopilotPopup]({
	      width: options.width,
	      bindElement: options.bindElement
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].show();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText]) {
	      var _babelHelpers$classPr3, _babelHelpers$classPr4;
	      (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr3.setCopilotContainer(babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7]);
	      (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr4.start();
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].setUseForImages(false);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].setCopilotContainer(babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7]);
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].start();
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].setUseForImages(true);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics)[_getAnalytics](true).sendEventOpen('success');
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].focus();
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustMenus)[_adjustMenus]();
	  }
	  hide() {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr5.close();
	  }
	  isShown() {
	    var _babelHelpers$classPr6, _babelHelpers$classPr7;
	    return (_babelHelpers$classPr6 = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr7.isShown()) != null ? _babelHelpers$classPr6 : false;
	  }
	  isInitFinished() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText]) {
	      var _babelHelpers$classPr8;
	      return Boolean((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr8.isInitFinished());
	    }
	    return true;
	  }
	  adjustWidth(width) {
	    var _babelHelpers$classPr9;
	    (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr9.setWidth(width);
	  }
	  adjust(options) {
	    var _babelHelpers$classPr10;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup] || (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) != null && _babelHelpers$classPr10.isDestroyed()) {
	      return;
	    }
	    if (options.hide) {
	      var _babelHelpers$classPr11, _babelHelpers$classPr12, _babelHelpers$classPr13;
	      (_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr11.setMaxWidth(0);
	      (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr12.setMinWidth(0);
	      babelHelpers.classPrivateFieldLooseBase(this, _hideMenus)[_hideMenus]();
	      this.adjustPosition(options.position);
	      (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _getBaasPopup)[_getBaasPopup]()) == null ? void 0 : _babelHelpers$classPr13.close();
	    } else {
	      var _babelHelpers$classPr14, _babelHelpers$classPr15, _babelHelpers$classPr16, _babelHelpers$classPr17, _babelHelpers$classPr18;
	      (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr14.setMaxWidth(null);
	      (_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) == null ? void 0 : _babelHelpers$classPr15.setMinWidth(null);
	      this.adjustPosition(options.position);
	      babelHelpers.classPrivateFieldLooseBase(this, _adjustMenus)[_adjustMenus]();
	      (_babelHelpers$classPr16 = babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController]) == null ? void 0 : _babelHelpers$classPr16.showMenu();
	      (_babelHelpers$classPr17 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr17.showMenu();
	      (_babelHelpers$classPr18 = babelHelpers.classPrivateFieldLooseBase(this, _getBaasPopup)[_getBaasPopup]()) == null ? void 0 : _babelHelpers$classPr18.adjustPosition();
	    }
	  }
	  adjustPosition(position) {
	    var _babelHelpers$classPr19;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup]) {
	      return;
	    }
	    if (position) {
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].setBindElement(position);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].adjustPosition({
	      forceBindPosition: true
	    });
	    (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _getBaasPopup)[_getBaasPopup]()) == null ? void 0 : _babelHelpers$classPr19.adjustPosition({
	      forceBindPosition: true,
	      forceTop: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustMenus)[_adjustMenus]();
	  }
	  setSelectedText(text) {
	    var _babelHelpers$classPr20;
	    (_babelHelpers$classPr20 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr20.setSelectedText(text);
	  }
	  setContext(text) {
	    var _babelHelpers$classPr21;
	    (_babelHelpers$classPr21 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr21.setContext(text);
	  }
	  setContextParameters(contextParameters) {
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setContextParameters(contextParameters);
	  }
	  setExtraMarkers(extraMarkers) {
	    var _babelHelpers$classPr22, _babelHelpers$classPr23;
	    const extraMarkersWithoutSystemMarkers = {
	      ...extraMarkers,
	      original_message: undefined,
	      user_message: undefined
	    };
	    const payload = ((_babelHelpers$classPr22 = babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine]) == null ? void 0 : _babelHelpers$classPr22.getPayload()) || new ai_engine.Text();
	    payload.setMarkers({
	      ...payload.getMarkers(),
	      ...extraMarkersWithoutSystemMarkers
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setPayload(payload);
	    (_babelHelpers$classPr23 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr23.setExtraMarkers(extraMarkers);
	  }
	  getPosition() {
	    var _babelHelpers$classPr24;
	    return {
	      inputField: main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].getPopupContainer()),
	      menu: main_core.Dom.getPosition((_babelHelpers$classPr24 = babelHelpers.classPrivateFieldLooseBase(this, _getOpenMenu)[_getOpenMenu]()) == null ? void 0 : _babelHelpers$classPr24.getPopupContainer())
	    };
	  }
	}
	function _getBaasPopup2() {
	  return main_popup.PopupManager.getPopups().find(popup => popup.getId().includes('baas'));
	}
	function _initCopilotPopup2(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup] = new main_popup.Popup({
	    className: 'ai__copilot_input-popup',
	    bindElement: options.bindElement,
	    content: this.render(),
	    padding: 0,
	    width: options.width,
	    contentNoPaddings: true,
	    borderRadius: '0px',
	    autoHide: babelHelpers.classPrivateFieldLooseBase(this, _autoHide$1)[_autoHide$1],
	    closeByEsc: true,
	    cacheable: false,
	    autoHideHandler: event => babelHelpers.classPrivateFieldLooseBase(this, _autoHideHandler)[_autoHideHandler](event),
	    events: {
	      onPopupClose: () => {
	        var _babelHelpers$classPr25, _babelHelpers$classPr26, _babelHelpers$classPr27;
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7], '--error');
	        (_babelHelpers$classPr25 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr25.finish();
	        (_babelHelpers$classPr26 = babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController]) == null ? void 0 : _babelHelpers$classPr26.finish();
	        babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].stopRecording();
	        (_babelHelpers$classPr27 = babelHelpers.classPrivateFieldLooseBase(this, _getBaasPopup)[_getBaasPopup]()) == null ? void 0 : _babelHelpers$classPr27.close();
	        this.emit(CopilotEvents.HIDE);
	        main_core.Event.unbind(window, 'resize', babelHelpers.classPrivateFieldLooseBase(this, _windowResizeHandler)[_windowResizeHandler]);
	      },
	      onPopupShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _windowResizeHandler)[_windowResizeHandler] = () => babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].adjustHeight();
	        main_core.Event.bind(window, 'resize', babelHelpers.classPrivateFieldLooseBase(this, _windowResizeHandler)[_windowResizeHandler]);
	        babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].clearErrors();
	      }
	    }
	  });
	}
	function _showCopilotAfterCopilotBanner2(showCopilotOptions) {
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].show();
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner].subscribe('action-finish-success', () => {
	    Copilot.showBanner = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotBanner)[_copilotBanner] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setBannerLaunched();
	    setTimeout(() => {
	      this.show(showCopilotOptions);
	    }, 300);
	  });
	}
	function _initEngine2(initEngineOptions) {
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine] = new ai_engine.Engine();
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setModuleId(initEngineOptions.moduleId).setContextId(initEngineOptions.contextId).setContextParameters(initEngineOptions.contextParameters).setParameters({
	    promptCategory: initEngineOptions.category
	  });
	  this.setExtraMarkers(initEngineOptions.extraMarkers);
	}
	async function _initCopilotImageController2() {
	  const {
	    CopilotImageController: ImageController
	  } = await loadExtensionWrapper('ai.copilot.copilot-image-controller');
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController] = new ImageController({
	    inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField],
	    engine: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine],
	    copilotContainer: babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7],
	    copilotInputEvents: CopilotInputEvents,
	    copilotMenu: CopilotMenu$$1,
	    popupWithoutBackBtn: babelHelpers.classPrivateFieldLooseBase(this, _useImage)[_useImage] && babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText] === false,
	    useInsertAboveAndUnderMenuItems: babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText],
	    analytics: babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics)[_getAnalytics](true)
	  });
	  await babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].init();
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('back', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].finish();
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].start();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].setUseForImages(false);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('close', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].finish();
	    this.hide();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('save', event => {
	    this.emit(CopilotEvents.IMAGE_SAVE, new main_core_events.BaseEvent({
	      data: {
	        imageUrl: event.getData().imageUrl
	      }
	    }));
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('place-above', () => {
	    this.emit(CopilotEvents.IMAGE_PLACE_ABOVE);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('place-under', () => {
	    this.emit(CopilotEvents.IMAGE_PLACE_UNDER);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('cancel', () => {
	    this.emit(CopilotEvents.IMAGE_CANCEL);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].subscribe('completion-result', event => {
	    this.emit(CopilotEvents.IMAGE_COMPLETION_RESULT, new main_core_events.BaseEvent({
	      data: {
	        imageUrl: event.getData().imageUrl
	      }
	    }));
	  });
	}
	async function _initCopilotTextController2(options) {
	  const {
	    CopilotTextController: TextController
	  } = await main_core.Runtime.loadExtension('ai.copilot.copilot-text-controller');
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController] = new TextController({
	    engine: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine],
	    inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField],
	    readonly: options.readonly,
	    category: options.category,
	    selectedText: options.selectedText,
	    context: options.context,
	    addImageMenuItem: options.addImageMenuItem,
	    warningField: babelHelpers.classPrivateFieldLooseBase(this, _warningField)[_warningField],
	    resultField: babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField],
	    copilotInputEvents: CopilotInputEvents,
	    copilotMenu: CopilotMenu$$1,
	    copilotMenuEvents: CopilotMenuEvents$$1,
	    analytics: babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics)[_getAnalytics](),
	    showResultInCopilot: babelHelpers.classPrivateFieldLooseBase(this, _showResultInCopilot)[_showResultInCopilot]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('aiResult', event => {
	    this.emit('aiResult', {
	      result: event.getData().result
	    });
	    this.emit(CopilotEvents.TEXT_COMPLETION_RESULT, {
	      result: event.getData().result
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('prompt-master-show', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].setClosingByEsc(false);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('prompt-master-destroy', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].setClosingByEsc(true);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('close', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].finish();
	    this.hide();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('save', event => {
	    this.emit(CopilotEvents.TEXT_SAVE, {
	      ...event.getData()
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('add_below', event => {
	    this.emit(CopilotEvents.TEXT_PLACE_BELOW, {
	      ...event.getData()
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('show-image-configurator', async () => {
	    await babelHelpers.classPrivateFieldLooseBase(this, _initCopilotImageController)[_initCopilotImageController]();
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].finish();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].setUseForImages(true);
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController].start();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].focus();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].subscribe('cancel', () => {
	    this.emit(CopilotEvents.TEXT_CANCEL);
	  });
	}
	async function _initCopilotTextControllerMenu2() {
	  await babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].init();
	}
	function _getOpenMenu2() {
	  var _babelHelpers$classPr28, _babelHelpers$classPr29, _babelHelpers$classPr30;
	  return ((_babelHelpers$classPr28 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : (_babelHelpers$classPr29 = _babelHelpers$classPr28.getOpenMenu()) == null ? void 0 : _babelHelpers$classPr29.getPopup()) || ((_babelHelpers$classPr30 = babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController]) == null ? void 0 : _babelHelpers$classPr30.getOpenMenuPopup());
	}
	function _mouseDownHandler2(event) {
	  var _babelHelpers$classPr31;
	  this.wasMouseDownOnSelf = (_babelHelpers$classPr31 = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].getPopupContainer()) == null ? void 0 : _babelHelpers$classPr31.contains(event.target);
	}
	function _autoHideHandler2(event) {
	  var _babelHelpers$classPr32, _babelHelpers$classPr33, _babelHelpers$classPr34, _babelHelpers$classPr35;
	  const target = event.target;
	  const isSelf = babelHelpers.classPrivateFieldLooseBase(this, _copilotPopup)[_copilotPopup].getPopupContainer().contains(target);
	  const isWarningFieldInfoSlider = (_babelHelpers$classPr32 = babelHelpers.classPrivateFieldLooseBase(this, _warningField)[_warningField].getInfoSliderContainer()) == null ? void 0 : _babelHelpers$classPr32.contains(target);
	  const preventAutoHide = babelHelpers.classPrivateFieldLooseBase(this, _preventAutoHide)[_preventAutoHide](event);
	  const isClickOnSlider = Boolean(event.target.closest('.side-panel'));
	  const isClickOnRolesDialog = Boolean(event.target.closest('.ai_roles-dialog_popup'));
	  const isClickOnPromptMasterPopup = Boolean(event.target.closest('.ai__prompt-master-popup'));
	  const isClickOnOverlay = Boolean(event.target.closest('.popup-window-overlay'));
	  const isClickOnAnotherPopup = Boolean(event.target.closest('.popup-window'));
	  const isClickOnBaasPopup = Boolean((_babelHelpers$classPr33 = babelHelpers.classPrivateFieldLooseBase(this, _getBaasPopup)[_getBaasPopup]()) == null ? void 0 : _babelHelpers$classPr33.getPopupContainer().contains(target));
	  const isClickOnNotificationBalloon = Boolean(event.target.closest('.ui-notification-balloon'));
	  const shouldBeHidden = !isSelf && !((_babelHelpers$classPr34 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) != null && _babelHelpers$classPr34.isContainsElem(target)) && !((_babelHelpers$classPr35 = babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController]) != null && _babelHelpers$classPr35.isContainsTarget(target)) && !preventAutoHide && !this.wasMouseDownOnSelf && !isWarningFieldInfoSlider && !isClickOnSlider && !isClickOnRolesDialog && !isClickOnPromptMasterPopup && !isClickOnAnotherPopup && !isClickOnBaasPopup && !isClickOnOverlay && !isClickOnNotificationBalloon;
	  if (shouldBeHidden) {
	    this.hide();
	  }
	  this.wasMouseDownOnSelf = false;
	  return false;
	}
	function _hideMenus2() {
	  var _babelHelpers$classPr36, _babelHelpers$classPr37;
	  (_babelHelpers$classPr36 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr36.hideAllMenus();
	  (_babelHelpers$classPr37 = babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController]) == null ? void 0 : _babelHelpers$classPr37.hideAllMenus();
	}
	function _adjustMenus2() {
	  var _babelHelpers$classPr38, _babelHelpers$classPr39;
	  (_babelHelpers$classPr38 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) == null ? void 0 : _babelHelpers$classPr38.adjustMenusPosition();
	  (_babelHelpers$classPr39 = babelHelpers.classPrivateFieldLooseBase(this, _copilotImageController)[_copilotImageController]) == null ? void 0 : _babelHelpers$classPr39.adjustMenusPosition();
	}
	function _getAnalytics2(withReset = false) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] || withReset) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = new CopilotAnalytics();
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextSection(babelHelpers.classPrivateFieldLooseBase(this, _category$1)[_category$1]);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _useText)[_useText]) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _readonly$2)[_readonly$2]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _initAnalyticForReadOnly)[_initAnalyticForReadOnly]();
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _initAnalyticForText)[_initAnalyticForText]();
	      }
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _useImage)[_useImage]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setCategoryImage();
	    }
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics];
	}
	function _initAnalyticForText2() {
	  var _babelHelpers$classPr40, _babelHelpers$classPr41;
	  babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setCategoryText();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) {
	    return;
	  }
	  if (!((_babelHelpers$classPr40 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].getSelectedText()) != null && _babelHelpers$classPr40.trim()) && !((_babelHelpers$classPr41 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].getContext()) != null && _babelHelpers$classPr41.trim())) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeTextNew();
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].getSelectedText()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeTextEdit();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeTextReply();
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].getSelectedText()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementPopupButton();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementSpaceButton();
	  }
	}
	function _initAnalyticForReadOnly2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setCategoryReadonly();
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController]) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _copilotTextController)[_copilotTextController].getSelectedText()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementReadonlyQuote();
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementReadonlyCommon();
	  }
	}
	async function _showCopilotAfterApplyAgreement2(showCopilotOptions) {
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotAgreementWasApplied)[_copilotAgreementWasApplied] = await checkCopilotAgreement({
	    moduleId: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getModuleId(),
	    contextId: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getContextId(),
	    events: {
	      onAccept: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _copilotAgreementWasApplied)[_copilotAgreementWasApplied] = true;
	        this.show(showCopilotOptions);
	      },
	      onCancel: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _copilotAgreementWasApplied)[_copilotAgreementWasApplied] = false;
	        babelHelpers.classPrivateFieldLooseBase(this, _copilotAgreementWasApplied)[_copilotAgreementWasApplied] = undefined;
	      }
	    }
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _copilotAgreementWasApplied)[_copilotAgreementWasApplied]) {
	    this.show(showCopilotOptions);
	  }
	}
	Object.defineProperty(Copilot, _staticEulaRestrictCallback, {
	  writable: true,
	  value: null
	});
	Copilot.showBanner = null;

	class BaseCommand {
	  constructor(options) {
	    this.copilotTextController = options == null ? void 0 : options.copilotTextController;
	  }
	}

	var _copilotContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");
	var _inputField$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");

	var _inputField$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	var _copilotContainer$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");

	var _commandCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commandCode");

	var _commandCode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commandCode");
	var _prompts = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prompts");

	var _category$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _isBeforeGeneration = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBeforeGeneration");
	var _openFeedbackForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openFeedbackForm");
	class OpenFeedbackFormCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _openFeedbackForm, {
	      value: _openFeedbackForm2
	    });
	    Object.defineProperty(this, _category$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isBeforeGeneration, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2] = options.category;
	    babelHelpers.classPrivateFieldLooseBase(this, _isBeforeGeneration)[_isBeforeGeneration] = options.isBeforeGeneration;
	  }
	  async execute() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _openFeedbackForm)[_openFeedbackForm]();
	  }
	}
	async function _openFeedbackForm2() {
	  var _data, _data$context_message, _data2, _data$author_message, _data3;
	  const senderPagePreset = `${babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]},${babelHelpers.classPrivateFieldLooseBase(this, _isBeforeGeneration)[_isBeforeGeneration] ? 'before' : 'after'}`;
	  let data = null;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isBeforeGeneration)[_isBeforeGeneration] === false) {
	    data = await this.copilotTextController.getDataForFeedbackForm();
	  }
	  const contextMessages = ((_data = data) == null ? void 0 : (_data$context_message = _data.context_messages) == null ? void 0 : _data$context_message.length) > 0 ? JSON.stringify((_data2 = data) == null ? void 0 : _data2.context_messages) : undefined;
	  const authorMessage = (_data$author_message = (_data3 = data) == null ? void 0 : _data3.author_message) != null ? _data$author_message : undefined;
	  const formIdNumber = Math.round(Math.random() * 1000);
	  main_core.Runtime.loadExtension(['ui.feedback.form']).then(() => {
	    var _data4, _data4$prompt, _data5, _data6, _data7, _data7$current_result, _data8, _data8$current_result;
	    BX.UI.Feedback.Form.open({
	      id: `ai.copilot.feedback-${formIdNumber}`,
	      forms: [{
	        zones: ['es'],
	        id: 684,
	        lang: 'es',
	        sec: 'svvq1x'
	      }, {
	        zones: ['en'],
	        id: 686,
	        lang: 'en',
	        sec: 'tjwodz'
	      }, {
	        zones: ['de'],
	        id: 688,
	        lang: 'de',
	        sec: 'nrwksg'
	      }, {
	        zones: ['com.br'],
	        id: 690,
	        lang: 'com.br',
	        sec: 'kpte6m'
	      }, {
	        zones: ['ru', 'by', 'kz'],
	        id: 692,
	        lang: 'ru',
	        sec: 'jbujn0'
	      }],
	      presets: {
	        sender_page: senderPagePreset,
	        prompt_code: (_data4 = data) == null ? void 0 : (_data4$prompt = _data4.prompt) == null ? void 0 : _data4$prompt.code,
	        user_message: (_data5 = data) == null ? void 0 : _data5.user_message,
	        original_message: (_data6 = data) == null ? void 0 : _data6.original_message,
	        author_message: authorMessage,
	        context_messages: contextMessages,
	        last_result0: (_data7 = data) == null ? void 0 : (_data7$current_result = _data7.current_result) == null ? void 0 : _data7$current_result[1],
	        language: main_core.Loc.getMessage('LANGUAGE_ID'),
	        cp_answer: (_data8 = data) == null ? void 0 : (_data8$current_result = _data8.current_result) == null ? void 0 : _data8$current_result[0]
	      }
	    });
	  }).catch(err => {
	    console.err(err);
	  });
	}

	var _engineCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engineCode");

	let _$b = t => t,
	  _t$b,
	  _t2$6;
	const CopilotContextMenuResultPopupEvents = {
	  SAVE: 'save',
	  CANCEL: 'cancel',
	  CHANGE_REQUEST: 'change-request',
	  CLOSE: 'close'
	};
	var _popup$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _bindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _resultContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultContainer");
	var _resultText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultText");
	var _resultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultMenu");
	var _additionalResultMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("additionalResultMenuItems");
	var _engine$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _analytics$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _initResultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initResultMenu");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _renderResultContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderResultContainer");
	class CopilotContextMenuResultPopup extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _renderResultContainer, {
	      value: _renderResultContainer2
	    });
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _initResultMenu, {
	      value: _initResultMenu2
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _popup$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _bindElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultText, {
	      writable: true,
	      value: ''
	    });
	    Object.defineProperty(this, _resultMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _additionalResultMenuItems, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _engine$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics$1, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotReadonly:ResultPopup');
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = options.bindElement;
	    babelHelpers.classPrivateFieldLooseBase(this, _additionalResultMenuItems)[_additionalResultMenuItems] = options.additionalResultMenuItems.map(menuItem => {
	      return {
	        ...menuItem,
	        command: () => {
	          menuItem.command(babelHelpers.classPrivateFieldLooseBase(this, _resultText)[_resultText]);
	        }
	      };
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics$1)[_analytics$1] = options.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _engine$1)[_engine$1] = options.engine;
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	      babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1].setOffset({
	        offsetTop: 6
	      });
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initResultMenu)[_initResultMenu]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1].show();
	    babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1].getPopupContainer(), {
	      top: 4
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].open();
	  }
	  isShown() {
	    var _babelHelpers$classPr;
	    return Boolean((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1]) == null ? void 0 : _babelHelpers$classPr.isShown());
	  }
	  adjustPosition() {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1]) == null ? void 0 : _babelHelpers$classPr2.adjustPosition();
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr3.adjustPosition();
	  }
	  destroy() {
	    var _babelHelpers$classPr4, _babelHelpers$classPr5;
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1]) == null ? void 0 : _babelHelpers$classPr4.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1] = null;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr5.close();
	    babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer] = null;
	  }
	  setBindElement(bindElement) {
	    var _babelHelpers$classPr6;
	    (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1]) == null ? void 0 : _babelHelpers$classPr6.setBindElement(bindElement);
	    this.adjustPosition();
	  }
	  getResult() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _resultText)[_resultText];
	  }
	  setResult(text) {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultText)[_resultText] = text;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer].innerText = text;
	    }
	  }
	}
	function _initPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _popup$1)[_popup$1] = new main_popup.Popup({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement],
	    cacheable: false,
	    className: 'ai__copilot-scope ai__copilot-context-menu__result-popup',
	    width: 530,
	    closeIcon: true,
	    closeIconSize: 'large',
	    events: {
	      onPopupShow: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer].scrollHeight > babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer].offsetHeight) {
	          main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer], '--with-scroll');
	        }
	      },
	      onPopupClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].close();
	        this.emit(CopilotContextMenuResultPopupEvents.CLOSE);
	      },
	      onPopupAfterClose: () => {
	        this.destroy();
	      }
	    }
	  });
	}
	function _initResultMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu] = new ai_copilot.CopilotMenu({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer],
	    cacheable: false,
	    forceTop: false,
	    items: [new ai_copilot_copilotTextController.ChangeRequestMenuItem({
	      icon: null,
	      onClick: () => {
	        this.emit(CopilotContextMenuResultPopupEvents.CHANGE_REQUEST);
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics$1)[_analytics$1].sendEventCancel();
	      }
	    }).getOptions(), {
	      separator: true
	    }, new ai_copilot_copilotTextController.CopyResultMenuItem({
	      getText: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics$1)[_analytics$1].sendEventCopyResult();
	        return babelHelpers.classPrivateFieldLooseBase(this, _resultText)[_resultText];
	      }
	    }).getOptions(), ...babelHelpers.classPrivateFieldLooseBase(this, _additionalResultMenuItems)[_additionalResultMenuItems], {
	      separator: true
	    }, new ai_copilot_copilotTextController.FeedbackMenuItem({
	      icon: null,
	      isBeforeGeneration: false,
	      engine: babelHelpers.classPrivateFieldLooseBase(this, _engine$1)[_engine$1]
	    }).getOptions()]
	  });
	}
	function _renderPopupContent2() {
	  const warningField = new CopilotWarningResultField();
	  return main_core.Tag.render(_t$b || (_t$b = _$b`
			<div class="ai__copilot-context-menu__result-popup-content">
				${0}
				<div class="ai__copilot-context-menu_result-popup-warning">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderResultContainer)[_renderResultContainer](), warningField.render(true));
	}
	function _renderResultContainer2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer] = main_core.Tag.render(_t2$6 || (_t2$6 = _$b`
			<div class="ai__copilot-context-menu__result-popup-text">${0}</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _resultText)[_resultText]);
	  return babelHelpers.classPrivateFieldLooseBase(this, _resultContainer)[_resultContainer];
	}

	let _$c = t => t,
	  _t$c,
	  _t2$7,
	  _t3$5;
	const CopilotContextMenuLoaderEvents = {
	  CANCEL: 'cancel'
	};
	var _popup$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _bindElement$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _lottieLoaderIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lottieLoaderIcon");
	var _initPopup$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	class CopilotContextMenuLoader extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _initPopup$1, {
	      value: _initPopup2$1
	    });
	    Object.defineProperty(this, _popup$2, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _bindElement$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _lottieLoaderIcon, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotContextMenu:Loader');
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement$1)[_bindElement$1] = options.bindElement;
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initPopup$1)[_initPopup$1]();
	      babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2].setOffset({
	        offsetTop: 6
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2].show();
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2] = null;
	  }
	  isShown() {
	    var _babelHelpers$classPr;
	    return Boolean((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2]) == null ? void 0 : _babelHelpers$classPr.isShown());
	  }
	  adjustPosition() {
	    var _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2]) == null ? void 0 : _babelHelpers$classPr2.adjustPosition();
	  }
	  setBindElement(bindElement) {
	    var _babelHelpers$classPr3;
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2]) == null ? void 0 : _babelHelpers$classPr3.setBindElement(bindElement);
	    this.adjustPosition();
	  }
	}
	function _initPopup2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _popup$2)[_popup$2] = new main_popup.Popup({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$1)[_bindElement$1],
	    cacheable: false,
	    minWidth: 282,
	    minHeight: 42,
	    padding: 6,
	    className: 'ai__copilot-scope ai__copilot-context-menu_loader-popup',
	    events: {
	      onPopupShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _lottieLoaderIcon)[_lottieLoaderIcon].play();
	      },
	      onPopupClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _lottieLoaderIcon)[_lottieLoaderIcon].stop();
	      }
	    }
	  });
	}
	function _getPopupContent2() {
	  const size = 21.5;
	  const loaderIcon = main_core.Tag.render(_t$c || (_t$c = _$c`
			<div class="" style="width: ${0}px; height: ${0}px;"></div>
		`), size, size);
	  babelHelpers.classPrivateFieldLooseBase(this, _lottieLoaderIcon)[_lottieLoaderIcon] = ui_lottie.Lottie.loadAnimation({
	    container: loaderIcon,
	    renderer: 'svg',
	    animationData: copilotLottieIcon,
	    autoplay: false
	  });
	  const cancelBtn = main_core.Tag.render(_t2$7 || (_t2$7 = _$c`
			<button style="opacity: 1;" class="ai__copilot_loader-cancel-btn">
				${0}
			</button>
		`), main_core.Loc.getMessage('AI_COPILOT_INPUT_LOADER_CANCEL'));
	  main_core.Event.bind(cancelBtn, 'click', () => {
	    this.emit(CopilotContextMenuLoaderEvents.CANCEL);
	  });
	  return main_core.Tag.render(_t3$5 || (_t3$5 = _$c`
			<div class="ai__copilot-context-menu-loader-content">
				<div class="ai__copilot_loader-left">
					<div class="ai__copilot-context-menu-loader-icon">
						${0}
					</div>
					<div class="ai__copilot-context-menu-loader-text-with-dots">
						<span class="ai__copilot_loader-text">${0}</span>
						<div class="ai__copilot-context-menu-loader_dots">
							<div class="dot-flashing"></div>
						</div>
					</div>
				</div>
				${0}
			</div>
		`), loaderIcon, main_core.Loc.getMessage('AI_COPILOT_INPUT_LOADER_TEXT'), cancelBtn);
	}

	let _$d = t => t,
	  _t$d;
	const CopilotContextMenuErrorPopupEvents = {
	  CANCEL: 'cancel',
	  REPEAT: 'repeat',
	  CHANGE_REQUEST: 'change-request'
	};
	var _error = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("error");
	var _bindElement$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _popup$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _menu$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menu");
	var _errorField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorField");
	var _initPopup$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _getPopupContent$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _showMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showMenu");
	var _hideMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideMenu");
	var _initMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initMenu");
	var _getMenuItems$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItems");
	class CopilotContextMenuErrorPopup extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _getMenuItems$1, {
	      value: _getMenuItems2$1
	    });
	    Object.defineProperty(this, _initMenu, {
	      value: _initMenu2
	    });
	    Object.defineProperty(this, _hideMenu, {
	      value: _hideMenu2
	    });
	    Object.defineProperty(this, _showMenu, {
	      value: _showMenu2
	    });
	    Object.defineProperty(this, _getPopupContent$1, {
	      value: _getPopupContent2$1
	    });
	    Object.defineProperty(this, _initPopup$2, {
	      value: _initPopup2$2
	    });
	    Object.defineProperty(this, _error, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bindElement$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _menu$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _errorField, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotContextMenu:ErrorPopup');
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement$2)[_bindElement$2] = options.bindElement;
	    babelHelpers.classPrivateFieldLooseBase(this, _error)[_error] = options.error;
	    babelHelpers.classPrivateFieldLooseBase(this, _errorField)[_errorField] = new CopilotInputError({
	      errors: [babelHelpers.classPrivateFieldLooseBase(this, _error)[_error]]
	    });
	  }
	  setError(error) {
	    babelHelpers.classPrivateFieldLooseBase(this, _error)[_error] = error;
	    babelHelpers.classPrivateFieldLooseBase(this, _errorField)[_errorField].setErrors([babelHelpers.classPrivateFieldLooseBase(this, _error)[_error]]);
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initPopup$2)[_initPopup$2]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3].show();
	  }
	  destroy() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3]) == null ? void 0 : _babelHelpers$classPr.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3] = null;
	  }
	  isShown() {
	    var _babelHelpers$classPr2;
	    return Boolean((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3]) == null ? void 0 : _babelHelpers$classPr2.isShown());
	  }
	  adjustPosition() {
	    var _babelHelpers$classPr3, _babelHelpers$classPr4;
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3]) == null ? void 0 : _babelHelpers$classPr3.adjustPosition();
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1]) == null ? void 0 : _babelHelpers$classPr4.adjustPosition();
	  }
	  setBindElement(bindElement) {
	    var _babelHelpers$classPr5;
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3]) == null ? void 0 : _babelHelpers$classPr5.setBindElement(bindElement);
	    this.adjustPosition();
	  }
	}
	function _initPopup2$2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3] = new main_popup.Popup({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$2)[_bindElement$2],
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent$1)[_getPopupContent$1](),
	    className: 'ai__copilot-scope ai__copilot-context-menu_error-popup',
	    maxWidth: 600,
	    minHeight: 42,
	    padding: 6,
	    events: {
	      onAfterPopupShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showMenu)[_showMenu]();
	      },
	      onPopupClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _hideMenu)[_hideMenu]();
	      },
	      onPopupDestroy: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _hideMenu)[_hideMenu]();
	      }
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3].setOffset({
	    offsetTop: 6
	  });
	}
	function _getPopupContent2$1() {
	  const icon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main.WARNING,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-text-alert'),
	    size: 24
	  });
	  return main_core.Tag.render(_t$d || (_t$d = _$d`
			<div class="ai__copilot-context-menu-error-content">
				<div class="ai__copilot-context-menu-error-content-icon">
					${0}
				</div>
				<div class="ai__copilot-context-menu-error-content-text">
					${0}
				</div>
			</div>
		`), icon.render(), babelHelpers.classPrivateFieldLooseBase(this, _errorField)[_errorField].render());
	}
	function _showMenu2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initMenu)[_initMenu]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3].getPopupContainer(), {
	    top: 6
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].open();
	  babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1].show();
	}
	function _hideMenu2() {
	  var _babelHelpers$classPr6;
	  (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1]) == null ? void 0 : _babelHelpers$classPr6.close();
	  babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] = null;
	}
	function _initMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _menu$1)[_menu$1] = new ai_copilot.CopilotMenu({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _popup$3)[_popup$3].getPopupContainer(),
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems$1)[_getMenuItems$1](),
	    cacheable: false,
	    forceTop: false
	  });
	}
	function _getMenuItems2$1() {
	  return [new ai_copilot_copilotTextController.RepeatCopilotMenuItem({
	    icon: null,
	    onClick: () => {
	      this.emit(CopilotContextMenuErrorPopupEvents.REPEAT);
	    }
	  }).getOptions(), new ai_copilot_copilotTextController.ChangeRequestMenuItem({
	    icon: null,
	    onClick: () => {
	      this.emit(CopilotContextMenuErrorPopupEvents.CHANGE_REQUEST);
	    }
	  }).getOptions(), new ai_copilot_copilotTextController.CancelCopilotMenuItem({
	    icon: null,
	    onClick: () => {
	      this.emit(CopilotContextMenuErrorPopupEvents.CANCEL);
	    }
	  }).getOptions()];
	}

	var _staticEulaRestrictCallback$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("staticEulaRestrictCallback");
	class CopilotEula {
	  static async init() {
	    var _babelHelpers$classPr;
	    const Feature = await loadExtensionWrapper('bitrix24.license.feature');
	    if (!(Feature != null && Feature.Feature)) {
	      return false;
	    }
	    const isRestrictionCheckInProgress = main_core.Type.isFunction((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1]) == null ? void 0 : _babelHelpers$classPr.then);
	    const isRestrictionNotChecked = babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1] === null;
	    if (isRestrictionNotChecked || isRestrictionCheckInProgress) {
	      try {
	        if (isRestrictionNotChecked) {
	          babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1] = Feature.Feature.checkEulaRestrictions('ai_available_by_version');
	        }
	        await babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1];
	        babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1] = false;
	        return false;
	      } catch (err) {
	        if (err.callback) {
	          babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1] = err.callback;
	          return true;
	        }
	        console.error(err);
	        return false;
	      }
	    }
	    return main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1]);
	  }
	  static checkRestricted() {
	    if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1])) {
	      babelHelpers.classPrivateFieldLooseBase(CopilotEula, _staticEulaRestrictCallback$1)[_staticEulaRestrictCallback$1]();
	      return true;
	    }
	    return false;
	  }
	}
	Object.defineProperty(CopilotEula, _staticEulaRestrictCallback$1, {
	  writable: true,
	  value: void 0
	});

	var _bindElement$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _copilotTextControllerEngine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotTextControllerEngine");
	var _context$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _selectedText$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedText");
	var _generalMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("generalMenu");
	var _resultPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultPopup");
	var _loaderPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaderPopup");
	var _errorPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorPopup");
	var _extraResultMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extraResultMenuItems");
	var _angle$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("angle");
	var _initEngineOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initEngineOptions");
	var _copilotBanner$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotBanner");
	var _isNeedToShowCopilotBanner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isNeedToShowCopilotBanner");
	var _isShowCopilotAgreementPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isShowCopilotAgreementPopup");
	var _showAfterCopilotBanner = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showAfterCopilotBanner");
	var _completions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("completions");
	var _handleCompletionsError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCompletionsError");
	var _getAnalyticParametersForCompletions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAnalyticParametersForCompletions");
	var _initEngine$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initEngine");
	var _getGeneralMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getGeneralMenuItems");
	var _getPromptMenuItemFromPrompt = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromptMenuItemFromPrompt");
	var _getProviderMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProviderMenuItems");
	var _showResultPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showResultPopup");
	var _setResultPopupText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setResultPopupText");
	var _initResultPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initResultPopup");
	var _initLoaderPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initLoaderPopup");
	var _showLoaderPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoaderPopup");
	var _hideLoaderPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoaderPopup");
	var _showGeneralMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showGeneralMenu");
	var _destroyGeneralMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroyGeneralMenu");
	var _initGeneralMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initGeneralMenu");
	var _getGeneralMenuAngleOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getGeneralMenuAngleOptions");
	var _showErrorPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showErrorPopup");
	var _destroyErrorPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("destroyErrorPopup");
	var _initErrorPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initErrorPopup");
	var _validateOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateOptions");
	var _validateContextOption = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateContextOption");
	var _getAnalytics$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAnalytics");
	class CopilotContextMenu extends main_core_events.EventEmitter {
	  constructor(_options) {
	    var _options$extraResultM;
	    super(_options);
	    Object.defineProperty(this, _getAnalytics$1, {
	      value: _getAnalytics2$1
	    });
	    Object.defineProperty(this, _validateContextOption, {
	      value: _validateContextOption2
	    });
	    Object.defineProperty(this, _validateOptions, {
	      value: _validateOptions2
	    });
	    Object.defineProperty(this, _initErrorPopup, {
	      value: _initErrorPopup2
	    });
	    Object.defineProperty(this, _destroyErrorPopup, {
	      value: _destroyErrorPopup2
	    });
	    Object.defineProperty(this, _showErrorPopup, {
	      value: _showErrorPopup2
	    });
	    Object.defineProperty(this, _getGeneralMenuAngleOptions, {
	      value: _getGeneralMenuAngleOptions2
	    });
	    Object.defineProperty(this, _initGeneralMenu, {
	      value: _initGeneralMenu2
	    });
	    Object.defineProperty(this, _destroyGeneralMenu, {
	      value: _destroyGeneralMenu2
	    });
	    Object.defineProperty(this, _showGeneralMenu, {
	      value: _showGeneralMenu2
	    });
	    Object.defineProperty(this, _hideLoaderPopup, {
	      value: _hideLoaderPopup2
	    });
	    Object.defineProperty(this, _showLoaderPopup, {
	      value: _showLoaderPopup2
	    });
	    Object.defineProperty(this, _initLoaderPopup, {
	      value: _initLoaderPopup2
	    });
	    Object.defineProperty(this, _initResultPopup, {
	      value: _initResultPopup2
	    });
	    Object.defineProperty(this, _setResultPopupText, {
	      value: _setResultPopupText2
	    });
	    Object.defineProperty(this, _showResultPopup, {
	      value: _showResultPopup2
	    });
	    Object.defineProperty(this, _getProviderMenuItems, {
	      value: _getProviderMenuItems2
	    });
	    Object.defineProperty(this, _getPromptMenuItemFromPrompt, {
	      value: _getPromptMenuItemFromPrompt2
	    });
	    Object.defineProperty(this, _getGeneralMenuItems, {
	      value: _getGeneralMenuItems2
	    });
	    Object.defineProperty(this, _initEngine$1, {
	      value: _initEngine2$1
	    });
	    Object.defineProperty(this, _getAnalyticParametersForCompletions, {
	      value: _getAnalyticParametersForCompletions2
	    });
	    Object.defineProperty(this, _handleCompletionsError, {
	      value: _handleCompletionsError2
	    });
	    Object.defineProperty(this, _completions, {
	      value: _completions2
	    });
	    Object.defineProperty(this, _showAfterCopilotBanner, {
	      value: _showAfterCopilotBanner2
	    });
	    Object.defineProperty(this, _isShowCopilotAgreementPopup, {
	      value: _isShowCopilotAgreementPopup2
	    });
	    Object.defineProperty(this, _isNeedToShowCopilotBanner, {
	      value: _isNeedToShowCopilotBanner2
	    });
	    Object.defineProperty(this, _bindElement$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotTextControllerEngine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _context$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedText$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _generalMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loaderPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _errorPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _extraResultMenuItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _angle$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initEngineOptions, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotReadonly');
	    babelHelpers.classPrivateFieldLooseBase(this, _validateOptions)[_validateOptions](_options);
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3] = _options.bindElement;
	    babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] = _options.context || '';
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] = _options.selectedText || '';
	    babelHelpers.classPrivateFieldLooseBase(this, _extraResultMenuItems)[_extraResultMenuItems] = (_options$extraResultM = _options.extraResultMenuItems) != null ? _options$extraResultM : [];
	    babelHelpers.classPrivateFieldLooseBase(this, _angle$1)[_angle$1] = _options.angle === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _initEngineOptions)[_initEngineOptions] = {
	      moduleId: _options.moduleId,
	      contextId: _options.contextId,
	      category: _options.category,
	      contextParameters: _options.contextParameters
	    };
	  }
	  async init() {
	    if (main_core.Extension.getSettings('ai.copilot').isRestrictByEula) {
	      await CopilotEula.init();
	    }
	    try {
	      await babelHelpers.classPrivateFieldLooseBase(this, _initEngine$1)[_initEngine$1](babelHelpers.classPrivateFieldLooseBase(this, _initEngineOptions)[_initEngineOptions]);
	      babelHelpers.classPrivateFieldLooseBase(this, _initGeneralMenu)[_initGeneralMenu]();
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isNeedToShowCopilotBanner)[_isNeedToShowCopilotBanner]()) {
	        const {
	          AppsInstallerBanner
	        } = await main_core.Runtime.loadExtension('ai.copilot-banner');
	        babelHelpers.classPrivateFieldLooseBase(CopilotContextMenu, _copilotBanner$1)[_copilotBanner$1] = new AppsInstallerBanner({});
	      }
	    } catch (e) {
	      console.error('Init error', e);
	      throw e;
	    }
	  }
	  getResultText() {
	    var _babelHelpers$classPr;
	    return ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) == null ? void 0 : _babelHelpers$classPr.getResult()) || null;
	  }
	  show() {
	    const isRestrictedByEula = CopilotEula.checkRestricted();
	    if (isRestrictedByEula) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics$1)[_getAnalytics$1]().sendEventOpen('error_agreement');
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(CopilotContextMenu, _copilotBanner$1)[_copilotBanner$1]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showAfterCopilotBanner)[_showAfterCopilotBanner]();
	      return;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isShowCopilotAgreementPopup)[_isShowCopilotAgreementPopup]()) {
	      const moduleId = babelHelpers.classPrivateFieldLooseBase(this, _initEngineOptions)[_initEngineOptions].moduleId;
	      const contextId = babelHelpers.classPrivateFieldLooseBase(this, _initEngineOptions)[_initEngineOptions].contextId;

	      // eslint-disable-next-line promise/catch-or-return
	      checkCopilotAgreement({
	        moduleId,
	        contextId,
	        events: {
	          onAccept: () => {
	            babelHelpers.classPrivateFieldLooseBase(this, _showGeneralMenu)[_showGeneralMenu]();
	            babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics$1)[_getAnalytics$1]().sendEventOpen('success');
	          }
	        }
	      }).then(isAccepted => {
	        if (isAccepted) {
	          babelHelpers.classPrivateFieldLooseBase(this, _showGeneralMenu)[_showGeneralMenu]();
	          babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics$1)[_getAnalytics$1]().sendEventOpen('success');
	        }
	      });
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _showGeneralMenu)[_showGeneralMenu]();
	    babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics$1)[_getAnalytics$1]().sendEventOpen('success');
	  }
	  hide() {
	    var _babelHelpers$classPr2;
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyGeneralMenu)[_destroyGeneralMenu]();
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) == null ? void 0 : _babelHelpers$classPr2.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyErrorPopup)[_destroyErrorPopup]();
	  }
	  isShown() {
	    var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	    return ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr3.isShown()) || ((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) == null ? void 0 : _babelHelpers$classPr4.isShown()) || ((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup]) == null ? void 0 : _babelHelpers$classPr5.isShown()) || ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup]) == null ? void 0 : _babelHelpers$classPr6.isShown());
	  }
	  adjustPosition() {
	    var _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10;
	    (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr7.adjustPosition();
	    (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup]) == null ? void 0 : _babelHelpers$classPr8.adjustPosition();
	    (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup]) == null ? void 0 : _babelHelpers$classPr9.adjustPosition();
	    (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) == null ? void 0 : _babelHelpers$classPr10.adjustPosition();
	  }
	  setContext(context) {
	    var _babelHelpers$classPr11;
	    babelHelpers.classPrivateFieldLooseBase(this, _validateContextOption)[_validateContextOption](context);
	    babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] = context || '';
	    (_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine]) == null ? void 0 : _babelHelpers$classPr11.setContext(babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1]);
	  }
	  setSelectedText(selectedText) {
	    var _babelHelpers$classPr12;
	    babelHelpers.classPrivateFieldLooseBase(this, _validateContextOption)[_validateContextOption](selectedText);
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] = selectedText || '';
	    (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine]) == null ? void 0 : _babelHelpers$classPr12.setContext(babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1]);
	  }
	  setBindElement(bindElement) {
	    var _babelHelpers$classPr13, _babelHelpers$classPr14, _babelHelpers$classPr15, _babelHelpers$classPr16;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3] = bindElement;
	    (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr13.setBindElement(bindElement);
	    (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup]) == null ? void 0 : _babelHelpers$classPr14.setBindElement(bindElement);
	    (_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) == null ? void 0 : _babelHelpers$classPr15.setBindElement(bindElement);
	    (_babelHelpers$classPr16 = babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup]) == null ? void 0 : _babelHelpers$classPr16.setBindElement(bindElement);
	  }
	}
	function _isNeedToShowCopilotBanner2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].isCopilotFirstLaunch() && babelHelpers.classPrivateFieldLooseBase(CopilotContextMenu, _copilotBanner$1)[_copilotBanner$1] === undefined;
	}
	function _isShowCopilotAgreementPopup2() {
	  var _Extension$getSetting;
	  return (_Extension$getSetting = main_core.Extension.getSettings('ai.copilot').isShowAgreementPopup) != null ? _Extension$getSetting : false;
	}
	function _showAfterCopilotBanner2() {
	  babelHelpers.classPrivateFieldLooseBase(CopilotContextMenu, _copilotBanner$1)[_copilotBanner$1].show();
	  babelHelpers.classPrivateFieldLooseBase(CopilotContextMenu, _copilotBanner$1)[_copilotBanner$1].subscribe('action-finish-success', () => {
	    babelHelpers.classPrivateFieldLooseBase(CopilotContextMenu, _copilotBanner$1)[_copilotBanner$1] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].setCopilotBannerLaunchedFlag();
	    setTimeout(() => {
	      this.show();
	    }, 300);
	  });
	}
	async function _completions2() {
	  try {
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyGeneralMenu)[_destroyGeneralMenu]();
	    babelHelpers.classPrivateFieldLooseBase(this, _showLoaderPopup)[_showLoaderPopup]();
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].setAnalyticParameters(babelHelpers.classPrivateFieldLooseBase(this, _getAnalyticParametersForCompletions)[_getAnalyticParametersForCompletions]());
	    const result = await babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].completions();
	    if (result) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setResultPopupText)[_setResultPopupText](result);
	      babelHelpers.classPrivateFieldLooseBase(this, _hideLoaderPopup)[_hideLoaderPopup]();
	      babelHelpers.classPrivateFieldLooseBase(this, _showResultPopup)[_showResultPopup]();
	    }
	  } catch (e) {
	    babelHelpers.classPrivateFieldLooseBase(this, _handleCompletionsError)[_handleCompletionsError](e);
	  }
	}
	function _handleCompletionsError2(error) {
	  babelHelpers.classPrivateFieldLooseBase(this, _hideLoaderPopup)[_hideLoaderPopup]();
	  const code = error.getCode();
	  switch (code) {
	    case 'AI_ENGINE_ERROR_OTHER':
	      {
	        error.setMessage(main_core.Loc.getMessage('AI_COPILOT_ERROR_OTHER'));
	        const command = new OpenFeedbackFormCommand({
	          category: babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getCategory(),
	          isBeforeGeneration: false,
	          copilotTextController: babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine]
	        });
	        error.setCustomData({
	          clickHandler: () => command.execute(),
	          clickableText: main_core.Loc.getMessage('AI_COPILOT_ERROR_CONTACT_US')
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _showErrorPopup)[_showErrorPopup](error);
	        break;
	      }
	    case 'LIMIT_IS_EXCEEDED_BAAS':
	      {
	        babelHelpers.classPrivateFieldLooseBase(this, _showErrorPopup)[_showErrorPopup](error);
	        break;
	      }
	    case 'LIMIT_IS_EXCEEDED_MONTHLY':
	    case 'LIMIT_IS_EXCEEDED_DAILY':
	    case 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF':
	      {
	        this.hide();
	        break;
	      }
	    default:
	      {
	        if (main_core.Type.isStringFilled(error.getCode()) === false) {
	          error.setCode('undefined');
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _showErrorPopup)[_showErrorPopup](error);
	      }
	  }
	  requestAnimationFrame(() => {
	    ai_ajaxErrorHandler.AjaxErrorHandler.handleTextGenerateError({
	      baasOptions: {
	        bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3],
	        context: babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getContextId(),
	        useAngle: true
	      },
	      errorCode: error.getCode()
	    });
	  });
	}
	function _getAnalyticParametersForCompletions2() {
	  const analytics = babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics$1)[_getAnalytics$1]();
	  return {
	    category: analytics.getCategory(),
	    type: analytics.getType(),
	    c_sub_section: analytics.getCSubSection(),
	    c_element: analytics.getCElement()
	  };
	}
	async function _initEngine2$1(initEngineOptions) {
	  const {
	    CopilotTextControllerEngine
	  } = await main_core.Runtime.loadExtension('ai.copilot.copilot-text-controller');
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine] = new CopilotTextControllerEngine({
	    moduleId: initEngineOptions.moduleId,
	    contextParameters: initEngineOptions.contextParameters,
	    contextId: initEngineOptions.contextId,
	    category: initEngineOptions.category
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].setContext(babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] || babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1]);
	  await babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].init();
	}
	function _getGeneralMenuItems2() {
	  const promptsWithoutZeroPrompt = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getPrompts().slice(1);
	  const promptsMenuItems = promptsWithoutZeroPrompt.map(prompt => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getPromptMenuItemFromPrompt)[_getPromptMenuItemFromPrompt](prompt);
	  });
	  return [...promptsMenuItems, {
	    separator: true
	  }, new ai_copilot_copilotTextController.OpenCopilotMenuItem({
	    children: babelHelpers.classPrivateFieldLooseBase(this, _getProviderMenuItems)[_getProviderMenuItems]()
	  }).getOptions(), new ai_copilot_copilotTextController.AboutCopilotMenuItem().getOptions(), new ai_copilot_copilotTextController.FeedbackMenuItem({
	    engine: babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine],
	    isBeforeGeneration: true
	  }).getOptions()];
	}
	function _getPromptMenuItemFromPrompt2(prompt) {
	  const promptChildren = prompt.children || [];
	  const promptMenuItem = new BaseMenuItem({
	    code: prompt.code,
	    text: prompt.title,
	    icon: prompt.icon,
	    onClick: () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].setCommandCode(prompt.code);
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].setContext(babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] || babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1]);
	      babelHelpers.classPrivateFieldLooseBase(this, _completions)[_completions]();
	    },
	    children: promptChildren.map(childPrompt => {
	      return babelHelpers.classPrivateFieldLooseBase(this, _getPromptMenuItemFromPrompt)[_getPromptMenuItemFromPrompt](childPrompt);
	    })
	  });
	  if (prompt.separator) {
	    return {
	      separator: true,
	      section: prompt.section,
	      title: prompt.title
	    };
	  }
	  return promptMenuItem.getOptions();
	}
	function _getProviderMenuItems2() {
	  var _babelHelpers$classPr17;
	  const providers = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getEngines().map(engine => {
	    return new ai_copilot_copilotTextController.ProviderMenuItem({
	      code: engine.code,
	      text: engine.title,
	      selected: babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getSelectedEngineCode() === engine.code,
	      onClick: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].setSelectedEngineCode(engine.code);
	        babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].replaceMenuItemSubmenu(new ai_copilot_copilotTextController.OpenCopilotMenuItem({
	          children: babelHelpers.classPrivateFieldLooseBase(this, _getProviderMenuItems)[_getProviderMenuItems]()
	        }).getOptions());
	      }
	    });
	  });
	  const result = [...providers, {
	    separator: true
	  }, new ai_copilot_copilotTextController.ConnectModelMenuItem(), new ai_copilot_copilotTextController.MarketMenuItem()];
	  if ((_babelHelpers$classPr17 = babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getPermissions()) != null && _babelHelpers$classPr17.can_edit_settings) {
	    result.push(new ai_copilot_copilotTextController.SettingsMenuItem());
	  }
	  return result;
	}
	function _showResultPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initResultPopup)[_initResultPopup]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].show();
	}
	function _setResultPopupText2(text) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initResultPopup)[_initResultPopup]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].setResult(text);
	}
	function _initResultPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup] = new CopilotContextMenuResultPopup({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3],
	    additionalResultMenuItems: babelHelpers.classPrivateFieldLooseBase(this, _extraResultMenuItems)[_extraResultMenuItems],
	    engine: babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine],
	    analytics: babelHelpers.classPrivateFieldLooseBase(this, _getAnalytics$1)[_getAnalytics$1]()
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].subscribe(CopilotContextMenuResultPopupEvents.SAVE, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _destroyGeneralMenu)[_destroyGeneralMenu]();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].subscribe(CopilotContextMenuResultPopupEvents.CANCEL, () => {
	    this.hide();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].subscribe(CopilotContextMenuResultPopupEvents.CHANGE_REQUEST, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultPopup)[_resultPopup].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _showGeneralMenu)[_showGeneralMenu]();
	  });
	}
	function _initLoaderPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup] = new CopilotContextMenuLoader({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup].subscribe(CopilotContextMenuLoaderEvents.CANCEL, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].cancelCompletion();
	    babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _showGeneralMenu)[_showGeneralMenu]();
	  });
	}
	function _showLoaderPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initLoaderPopup)[_initLoaderPopup]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup].show();
	}
	function _hideLoaderPopup2() {
	  var _babelHelpers$classPr18;
	  (_babelHelpers$classPr18 = babelHelpers.classPrivateFieldLooseBase(this, _loaderPopup)[_loaderPopup]) == null ? void 0 : _babelHelpers$classPr18.destroy();
	}
	function _showGeneralMenu2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initGeneralMenu)[_initGeneralMenu]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3]);
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].adjustPosition();
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].open();
	}
	function _destroyGeneralMenu2() {
	  var _babelHelpers$classPr19;
	  (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr19.close();
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu] = null;
	}
	function _initGeneralMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu] = new CopilotMenu$$1({
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getGeneralMenuItems)[_getGeneralMenuItems](),
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3],
	    cacheable: false,
	    autoHide: true,
	    forceTop: false,
	    angle: babelHelpers.classPrivateFieldLooseBase(this, _getGeneralMenuAngleOptions)[_getGeneralMenuAngleOptions](),
	    bordered: false
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].subscribe(CopilotMenuEvents$$1.close, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu] = null;
	  });
	}
	function _getGeneralMenuAngleOptions2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _angle$1)[_angle$1]) {
	    return null;
	  }
	  if (main_core.Type.isElementNode(babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3])) {
	    return {
	      offset: main_core.Dom.getPosition(babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3]).width,
	      position: 'top'
	    };
	  }
	  if (main_core.Type.isObject(babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3])) {
	    return {
	      position: 'top'
	    };
	  }
	  return null;
	}
	function _showErrorPopup2(error) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initErrorPopup)[_initErrorPopup](error);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].setError(error);
	  babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].show();
	}
	function _destroyErrorPopup2() {
	  var _babelHelpers$classPr20;
	  (_babelHelpers$classPr20 = babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup]) == null ? void 0 : _babelHelpers$classPr20.destroy();
	}
	function _initErrorPopup2(error) {
	  babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup] = new CopilotContextMenuErrorPopup({
	    error,
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement$3)[_bindElement$3]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].subscribe(CopilotContextMenuErrorPopupEvents.CANCEL, () => {
	    this.hide();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].subscribe(CopilotContextMenuErrorPopupEvents.CHANGE_REQUEST, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _showGeneralMenu)[_showGeneralMenu]();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].subscribe(CopilotContextMenuErrorPopupEvents.REPEAT, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _errorPopup)[_errorPopup].destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _completions)[_completions]();
	  });
	}
	function _validateOptions2(options) {
	  if (main_core.Type.isObject(options) === false) {
	    throw new main_core.BaseError('AI.CopilotContextMenu: options is required for constructor.');
	  }
	  if (main_core.Type.isStringFilled(options.moduleId) === false) {
	    throw new main_core.BaseError('AI.CopilotContextMenu: moduleId is required option and must be string.');
	  }
	  if (main_core.Type.isStringFilled(options.category) === false) {
	    throw new main_core.BaseError('AI.CopilotContextMenu: category is required option and must be string.');
	  }
	  if (main_core.Type.isStringFilled(options.contextId) === false) {
	    throw new main_core.BaseError('AI.CopilotContextMenu: contextId is required option and must be string');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _validateContextOption)[_validateContextOption](options.context);
	  if (options.angle && main_core.Type.isBoolean(options.angle) === false) {
	    throw new main_core.BaseError('AI.CopilotContextMenu: angle option must be boolean');
	  }
	}
	function _validateContextOption2(context) {
	  if (context && main_core.Type.isString(context) === false) {
	    throw new main_core.BaseError('AI.CopilotContextMenu: context option must be string');
	  }
	}
	function _getAnalytics2$1() {
	  const analytics = new CopilotAnalytics().setCategoryReadonly().setP1('prompt', babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getCommandCode()).setP2('provider', babelHelpers.classPrivateFieldLooseBase(this, _copilotTextControllerEngine)[_copilotTextControllerEngine].getSelectedEngineCode());
	  if (babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1]) {
	    analytics.setContextElementReadonlyCommon();
	  } else {
	    analytics.setContextElementReadonlyQuote();
	  }
	  return analytics;
	}
	Object.defineProperty(CopilotContextMenu, _copilotBanner$1, {
	  writable: true,
	  value: void 0
	});

	exports.Copilot = Copilot;
	exports.CopilotMode = CopilotMode;
	exports.CopilotEvents = CopilotEvents;
	exports.CopilotInput = CopilotInput;
	exports.CopilotInputEvents = CopilotInputEvents;
	exports.CopilotMenu = CopilotMenu$$1;
	exports.CopilotMenuEvents = CopilotMenuEvents$$1;
	exports.CopilotMenuCommand = CopilotMenuCommand;
	exports.CopilotResult = CopilotResult;
	exports.CopilotContextMenu = CopilotContextMenu;

}((this.BX.AI = this.BX.AI || {}),BX.AI,BX,BX,BX,BX.UI,BX,BX.AI,BX,BX,BX,BX.UI.Feedback,BX.UI,BX.AI,BX.AI,BX.Event,BX.Main,BX.UI.IconSet,BX,BX.AI));
//# sourceMappingURL=copilot.bundle.js.map

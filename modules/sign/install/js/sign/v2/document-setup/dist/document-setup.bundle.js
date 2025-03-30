/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,main_core_events,sign_v2_blankSelector,sign_v2_api,sign_v2_signSettings,ui_buttons,ui_alerts) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _notificationContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("notificationContainer");
	var _changeDomainWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeDomainWarningContainer");
	var _scenarioType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scenarioType");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _uids = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uids");
	var _documentMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentMode");
	var _chatId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatId");
	var _initNotifications = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initNotifications");
	var _isChatCreated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isChatCreated");
	var _appendChatWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendChatWarningContainer");
	var _register = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("register");
	var _changeDocumentBlank = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeDocumentBlank");
	var _getPages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPages");
	var _convertToBase = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("convertToBase64");
	var _setDocumentData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setDocumentData");
	var _processPages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("processPages");
	var _appendUnsecuredSchemeWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendUnsecuredSchemeWarningContainer");
	var _appendChangeDomainWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendChangeDomainWarningContainer");
	var _getWarning = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWarning");
	var _removeChangeDomainWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeChangeDomainWarningContainer");
	var _appendEdoWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendEdoWarningContainer");
	class DocumentSetup extends main_core_events.EventEmitter {
	  constructor(blankSelectorConfig) {
	    super();
	    Object.defineProperty(this, _appendEdoWarningContainer, {
	      value: _appendEdoWarningContainer2
	    });
	    Object.defineProperty(this, _removeChangeDomainWarningContainer, {
	      value: _removeChangeDomainWarningContainer2
	    });
	    Object.defineProperty(this, _getWarning, {
	      value: _getWarning2
	    });
	    Object.defineProperty(this, _appendChangeDomainWarningContainer, {
	      value: _appendChangeDomainWarningContainer2
	    });
	    Object.defineProperty(this, _appendUnsecuredSchemeWarningContainer, {
	      value: _appendUnsecuredSchemeWarningContainer2
	    });
	    Object.defineProperty(this, _processPages, {
	      value: _processPages2
	    });
	    Object.defineProperty(this, _setDocumentData, {
	      value: _setDocumentData2
	    });
	    Object.defineProperty(this, _convertToBase, {
	      value: _convertToBase2
	    });
	    Object.defineProperty(this, _getPages, {
	      value: _getPages2
	    });
	    Object.defineProperty(this, _changeDocumentBlank, {
	      value: _changeDocumentBlank2
	    });
	    Object.defineProperty(this, _register, {
	      value: _register2
	    });
	    Object.defineProperty(this, _appendChatWarningContainer, {
	      value: _appendChatWarningContainer2
	    });
	    Object.defineProperty(this, _isChatCreated, {
	      value: _isChatCreated2
	    });
	    Object.defineProperty(this, _initNotifications, {
	      value: _initNotifications2
	    });
	    Object.defineProperty(this, _notificationContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _changeDomainWarningContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _scenarioType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _uids, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentMode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _chatId, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.DocumentSetup');
	    this.blankSelector = new sign_v2_blankSelector.BlankSelector({
	      ...blankSelectorConfig,
	      events: {
	        toggleSelection: ({
	          data
	        }) => {
	          this.emit('toggleActivity', {
	            ...data,
	            blankSelector: this.blankSelector
	          });
	        },
	        addFile: ({
	          data
	        }) => {
	          this.emit('addFile', {
	            ready: this.blankSelector.isFilesReadyForUpload()
	          });
	        },
	        removeFile: () => this.emit('removeFile', {
	          ready: this.blankSelector.isFilesReadyForUpload()
	        }),
	        clearFiles: () => this.emit('clearFiles')
	      }
	    });
	    const {
	      type,
	      portalConfig: _portalConfig,
	      documentMode,
	      chatId: _chatId2
	    } = blankSelectorConfig;
	    this.setupData = null;
	    this.blankIsNotSelected = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _scenarioType)[_scenarioType] = type;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _uids)[_uids] = new Map();
	    babelHelpers.classPrivateFieldLooseBase(this, _documentMode)[_documentMode] = documentMode;
	    babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId] = _chatId2;
	    this.initLayout();
	    babelHelpers.classPrivateFieldLooseBase(this, _initNotifications)[_initNotifications](_portalConfig);
	  }
	  initLayout() {
	    this.layout = main_core.Tag.render(_t || (_t = _`
			<div class="sign-document-setup">
				<p class="sign-document-setup__add-title">
					${0}
				</p>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE'), this.blankSelector.getLayout());
	  }
	  isSameBlankSelected() {
	    var _this$setupData;
	    const {
	      selectedBlankId
	    } = this.blankSelector;
	    const {
	      blankId: lastBlankId
	    } = (_this$setupData = this.setupData) != null ? _this$setupData : {};
	    return selectedBlankId > 0 && lastBlankId === selectedBlankId;
	  }
	  handleError(blankId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setDocumentData)[_setDocumentData](null);
	    this.blankSelector.resetSelectedBlank();
	    this.blankSelector.deleteBlank(blankId);
	  }
	  loadBlocks(uid) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadBlocksByDocument(uid);
	  }
	  async setup(uid, isTemplateMode = false) {
	    if (this.isSameBlankSelected()) {
	      this.setupData = {
	        ...this.setupData,
	        isTemplate: true
	      };
	      return;
	    }
	    const {
	      selectedBlankId
	    } = this.blankSelector;
	    let blankId = 0;
	    try {
	      if (uid) {
	        const [loadedData, blocks] = await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadDocument(uid), this.loadBlocks(uid)]);
	        babelHelpers.classPrivateFieldLooseBase(this, _setDocumentData)[_setDocumentData]({
	          ...loadedData,
	          blocks,
	          isTemplate: true
	        });
	        const {
	          blankId
	        } = loadedData;
	        if (!this.blankSelector.hasBlank(blankId)) {
	          await this.blankSelector.loadBlankById(blankId);
	        }
	        this.blankSelector.selectBlank(blankId);
	      } else {
	        var _this$setupData2;
	        this.ready = false;
	        const isBlankChanged = selectedBlankId || this.blankSelector.isFilesReadyForUpload();
	        blankId = selectedBlankId || (await this.blankSelector.createBlank());
	        if (!blankId) {
	          this.blankIsNotSelected = true;
	          return;
	        }
	        let documentUid = (_this$setupData2 = this.setupData) == null ? void 0 : _this$setupData2.uid;
	        let documentTemplateUid = null;
	        if (isBlankChanged && isTemplateMode && documentUid) {
	          const {
	            templateUid
	          } = await babelHelpers.classPrivateFieldLooseBase(this, _changeDocumentBlank)[_changeDocumentBlank](documentUid, blankId);
	          documentTemplateUid = templateUid;
	        } else {
	          const isRegistered = babelHelpers.classPrivateFieldLooseBase(this, _uids)[_uids].has(blankId);
	          const {
	            uid,
	            templateUid
	          } = isRegistered ? await babelHelpers.classPrivateFieldLooseBase(this, _changeDocumentBlank)[_changeDocumentBlank](babelHelpers.classPrivateFieldLooseBase(this, _uids)[_uids].get(blankId), blankId) : await babelHelpers.classPrivateFieldLooseBase(this, _register)[_register](blankId, isTemplateMode, babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId]);
	          documentUid = uid;
	          documentTemplateUid = templateUid;
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _uids)[_uids].set(blankId, documentUid);
	        await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].upload(documentUid);
	        const [loadedData, blocks] = await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadDocument(documentUid), babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadBlocksByDocument(documentUid)]);
	        const isTemplate = Boolean(selectedBlankId);
	        babelHelpers.classPrivateFieldLooseBase(this, _setDocumentData)[_setDocumentData]({
	          ...loadedData,
	          blocks,
	          isTemplate,
	          templateUid: documentTemplateUid
	        });
	      }
	    } catch {
	      this.handleError(blankId);
	    }
	    this.ready = true;
	  }
	  async waitForPagesList(documentData, cb, preparedPages = false) {
	    let interval = 0;
	    let isPagesReady = false;
	    const requestTime = 10 * 1000;
	    const {
	      uid,
	      blankId,
	      isTemplate
	    } = documentData;
	    if (!isTemplate) {
	      this.blankSelector.selectBlank(blankId);
	    }
	    this.emit('toggleActivity', {
	      selected: false
	    });
	    const promises = [new Promise(resolve => {
	      var _BX$PULL;
	      (_BX$PULL = BX.PULL) == null ? void 0 : _BX$PULL.subscribe({
	        moduleId: 'sign',
	        command: 'blankIsReady',
	        callback: result => {
	          if (!isPagesReady && result != null && result.pages && (result == null ? void 0 : result.uid) === uid) {
	            resolve(result == null ? void 0 : result.pages);
	          }
	        }
	      });
	    }), new Promise(resolve => {
	      interval = setInterval(async () => {
	        if (isPagesReady) {
	          clearInterval(interval);
	          return;
	        }
	        const urls = await babelHelpers.classPrivateFieldLooseBase(this, _getPages)[_getPages](uid);
	        if (urls.length > 0) {
	          resolve(urls);
	        }
	      }, requestTime);
	    })];
	    if (preparedPages) {
	      promises.push(new Promise(resolve => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getPages)[_getPages](uid).then(urls => {
	          if (urls.length > 0) {
	            resolve(urls);
	          }
	        });
	      }));
	    }
	    const urls = await Promise.race(promises);
	    if (!isTemplate) {
	      const blank = this.blankSelector.getBlank(blankId);
	      blank.setPreview(urls[0].url);
	    }
	    clearInterval(interval);
	    isPagesReady = true;
	    await babelHelpers.classPrivateFieldLooseBase(this, _processPages)[_processPages](urls, cb);
	  }
	  set ready(isReady) {
	    if (isReady) {
	      main_core.Dom.removeClass(this.layout, '--pending');
	    } else {
	      main_core.Dom.addClass(this.layout, '--pending');
	    }
	  }
	  isTemplateMode() {
	    return sign_v2_signSettings.isTemplateMode(babelHelpers.classPrivateFieldLooseBase(this, _documentMode)[_documentMode]);
	  }
	  deleteDocumentFromList(blankId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _uids)[_uids].delete(blankId);
	  }
	}
	function _initNotifications2(portalConfig) {
	  babelHelpers.classPrivateFieldLooseBase(this, _notificationContainer)[_notificationContainer] = main_core.Tag.render(_t2 || (_t2 = _`<div></div>`));
	  const buttonsContainer = this.layout.querySelector('.sign-blank-selector__tile-widget');
	  main_core.Dom.insertBefore(babelHelpers.classPrivateFieldLooseBase(this, _notificationContainer)[_notificationContainer], buttonsContainer);
	  const {
	    isDomainChanged,
	    isEdoRegion,
	    isUnsecuredScheme
	  } = portalConfig;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isChatCreated)[_isChatCreated]() && babelHelpers.classPrivateFieldLooseBase(this, _scenarioType)[_scenarioType] !== 'b2e') {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendChatWarningContainer)[_appendChatWarningContainer]();
	  }
	  if (isDomainChanged) {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendChangeDomainWarningContainer)[_appendChangeDomainWarningContainer]();
	  }
	  if (isUnsecuredScheme) {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendUnsecuredSchemeWarningContainer)[_appendUnsecuredSchemeWarningContainer]();
	  }
	  if (isEdoRegion && babelHelpers.classPrivateFieldLooseBase(this, _scenarioType)[_scenarioType] !== 'b2e') {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendEdoWarningContainer)[_appendEdoWarningContainer]();
	  }
	}
	function _isChatCreated2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _chatId)[_chatId] > 0;
	}
	function _appendChatWarningContainer2() {
	  const text = `<div>${main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_COLLAB_WARNING')}</div>`;
	  const warning = babelHelpers.classPrivateFieldLooseBase(this, _getWarning)[_getWarning]();
	  warning.setText(text);
	  main_core.Dom.append(warning.getContainer(), babelHelpers.classPrivateFieldLooseBase(this, _notificationContainer)[_notificationContainer]);
	}
	async function _register2(blankId, isTemplateMode = false, chatId = 0) {
	  const data = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].register(blankId, babelHelpers.classPrivateFieldLooseBase(this, _scenarioType)[_scenarioType], isTemplateMode, chatId);
	  return data != null ? data : {};
	}
	async function _changeDocumentBlank2(uid, blankId) {
	  const data = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeDocument(uid, blankId);
	  return data != null ? data : {};
	}
	async function _getPages2(uid) {
	  var _data$pages;
	  const data = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getPages(uid);
	  return (_data$pages = data.pages) != null ? _data$pages : [];
	}
	async function _convertToBase2(pages) {
	  const promises = pages.map(async page => {
	    const data = await fetch(page.url);
	    const blob = await data.blob();
	    const fileReader = new FileReader();
	    await new Promise(resolve => {
	      main_core.Event.bindOnce(fileReader, 'loadend', resolve);
	      fileReader.readAsDataURL(blob);
	    });
	    return fileReader.result;
	  });
	  return Promise.all(promises);
	}
	function _setDocumentData2(setupData) {
	  this.setupData = setupData;
	  this.blankSelector.clearFiles({
	    removeFromServer: false
	  });
	}
	async function _processPages2(urls, cb) {
	  let startIndex = 0;
	  const pagesCount = 3;
	  while (startIndex < urls.length) {
	    const sliced = urls.slice(startIndex, startIndex + pagesCount);
	    const convertedPages = await babelHelpers.classPrivateFieldLooseBase(this, _convertToBase)[_convertToBase](sliced);
	    this.emit('toggleActivity', {
	      selected: true
	    });
	    startIndex += pagesCount;
	    cb(convertedPages, urls.length);
	  }
	}
	function _appendUnsecuredSchemeWarningContainer2() {
	  const text = `<div>${main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_USE_UNSECURED_SCHEME_WARNING')}</div>`;
	  const warning = babelHelpers.classPrivateFieldLooseBase(this, _getWarning)[_getWarning]();
	  warning.setText(text);
	  main_core.Dom.append(warning.getContainer(), babelHelpers.classPrivateFieldLooseBase(this, _notificationContainer)[_notificationContainer]);
	}
	function _appendChangeDomainWarningContainer2() {
	  const domainChangeButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_REFRESH_DOMAIN_BUTTON_TEXT'),
	    color: ui_buttons.Button.Color.LINK,
	    onclick: () => babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeDomain().then(() => babelHelpers.classPrivateFieldLooseBase(this, _removeChangeDomainWarningContainer)[_removeChangeDomainWarningContainer]()),
	    className: 'sign-document-setup__change-domain-button',
	    size: ui_buttons.Button.Size.EXTRA_SMALL
	  });
	  const text = `<p>${main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_CHANGE_DOMAIN_WARNING')}</p>`;
	  const warning = babelHelpers.classPrivateFieldLooseBase(this, _getWarning)[_getWarning]();
	  warning.setText(text);
	  babelHelpers.classPrivateFieldLooseBase(this, _changeDomainWarningContainer)[_changeDomainWarningContainer] = warning.getContainer();
	  main_core.Dom.append(domainChangeButton.getContainer(), babelHelpers.classPrivateFieldLooseBase(this, _changeDomainWarningContainer)[_changeDomainWarningContainer]);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _changeDomainWarningContainer)[_changeDomainWarningContainer], babelHelpers.classPrivateFieldLooseBase(this, _notificationContainer)[_notificationContainer]);
	}
	function _getWarning2() {
	  return new ui_alerts.Alert({
	    size: ui_alerts.Alert.Size.MD,
	    color: ui_alerts.Alert.Color.WARNING,
	    icon: ui_alerts.Alert.Icon.DANGER,
	    customClass: 'sign-document-setup__change-domain-wrapper'
	  });
	}
	function _removeChangeDomainWarningContainer2() {
	  main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _changeDomainWarningContainer)[_changeDomainWarningContainer]);
	}
	function _appendEdoWarningContainer2() {
	  const text = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_EDO_TEXT', {
	    '[helpdesklink]': '<a class="sign-document-setup__helpdesk-article" href="javascript:top.BX.Helper.show(\'redirect=detail&code=18453372\');">',
	    '[/helpdesklink]': '</a>'
	  });
	  const alert = babelHelpers.classPrivateFieldLooseBase(this, _getWarning)[_getWarning]();
	  alert.setColor(ui_alerts.Alert.Color.PRIMARY);
	  alert.setIcon(ui_alerts.Alert.Icon.INFO);
	  alert.setText(text);
	  main_core.Dom.append(alert.getContainer(), babelHelpers.classPrivateFieldLooseBase(this, _notificationContainer)[_notificationContainer]);
	}

	exports.DocumentSetup = DocumentSetup;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.Event,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX.UI,BX.UI));
//# sourceMappingURL=document-setup.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,sign_featureStorage,sign_type,sign_v2_api,sign_v2_b2e_documentCounters,sign_v2_b2e_signDropdown,sign_v2_documentSetup,sign_v2_helper,main_core,main_core_cache,main_core_events,main_popup,ui_buttons) {
	'use strict';

	var reuploadIcon = "/bitrix/js/sign/v2/b2e/document-setup/dist/images/components/reupload-files-popup/icon.svg";

	let _ = t => t,
	  _t;
	const popupTypes = Object.freeze({
	  upload: 'upload',
	  select: 'select'
	});
	var _instances = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instances");
	var _isUploadPopupCompleted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isUploadPopupCompleted");
	var _isSelectBlankPopupCompleted = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSelectBlankPopupCompleted");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _createPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopupContent");
	var _createSelectPopupButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createSelectPopupButtons");
	var _getSelectBlankPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectBlankPopup");
	var _createUploadPopupButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createUploadPopupButtons");
	var _getUploadPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUploadPopup");
	class NewBlankForTemplatePopupManager extends main_core_events.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _getUploadPopup, {
	      value: _getUploadPopup2
	    });
	    Object.defineProperty(this, _createUploadPopupButtons, {
	      value: _createUploadPopupButtons2
	    });
	    Object.defineProperty(this, _getSelectBlankPopup, {
	      value: _getSelectBlankPopup2
	    });
	    Object.defineProperty(this, _createSelectPopupButtons, {
	      value: _createSelectPopupButtons2
	    });
	    Object.defineProperty(this, _createPopupContent, {
	      value: _createPopupContent2
	    });
	    this.events = {
	      uploadPopup: {
	        onConfirm: 'onUploadFilePopupConfirm',
	        onCancel: 'onUploadFilePopupCancel'
	      },
	      selectBlankPopup: {
	        onConfirm: 'onSelectBlankPopupConfirm',
	        onCancel: 'onSelectBlankPopupCancel'
	      }
	    };
	    Object.defineProperty(this, _isUploadPopupCompleted, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isSelectBlankPopupCompleted, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.B2e.DocumentSetup.ReuploadFilesPopup');
	  }
	  static getOrCreateForObject(object) {
	    if (!babelHelpers.classPrivateFieldLooseBase(NewBlankForTemplatePopupManager, _instances)[_instances].has(object)) {
	      babelHelpers.classPrivateFieldLooseBase(NewBlankForTemplatePopupManager, _instances)[_instances].set(object, new NewBlankForTemplatePopupManager());
	    }
	    return babelHelpers.classPrivateFieldLooseBase(NewBlankForTemplatePopupManager, _instances)[_instances].get(object);
	  }
	  showSelectBlankPopup() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getSelectBlankPopup)[_getSelectBlankPopup]().show();
	  }
	  showUploadPopup() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getUploadPopup)[_getUploadPopup]().show();
	  }
	  isUploadPopupCompletedOnce() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isUploadPopupCompleted)[_isUploadPopupCompleted];
	  }
	  isSelectBlankPopupCompletedOnce() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isSelectBlankPopupCompleted)[_isSelectBlankPopupCompleted];
	  }
	}
	function _createPopupContent2(popupType) {
	  const message = popupType === 'upload' ? main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_UPLOAD_DESCRIPTION') : main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_SELECT_DESCRIPTION');
	  return main_core.Tag.render(_t || (_t = _`
			<div class="sign-b2e-document-setup__reupload__wrapper">
				<div class="sign-b2e-document-setup__reupload__icon-container">
					<img class="sign-b2e-document-setup__reupload__icon" src="${0}" alt="Icon"/>
				</div>
				<div class="sign-b2e-document-setup__reupload__text">
					<div class="sign-b2e-document-setup__reupload__title">
						${0}
					</div>
					<div class="sign-b2e-document-setup__reupload__description">
						${0}
					</div>
				</div>
			</div>
		`), reuploadIcon, main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_TITLE'), message);
	}
	function _createSelectPopupButtons2() {
	  const confirmButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_SELECT_CONFIRM'),
	    color: ui_buttons.Button.Color.PRIMARY,
	    round: true,
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getSelectBlankPopup)[_getSelectBlankPopup]().close();
	        babelHelpers.classPrivateFieldLooseBase(this, _isSelectBlankPopupCompleted)[_isSelectBlankPopupCompleted] = true;
	        this.emit(this.events.selectBlankPopup.onConfirm);
	      }
	    }
	  });
	  const cancelButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_CANCEL'),
	    color: ui_buttons.Button.Color.LIGHT_BORDER,
	    round: true,
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getSelectBlankPopup)[_getSelectBlankPopup]().close();
	        this.emit(this.events.selectBlankPopup.onCancel);
	      }
	    }
	  });
	  return [confirmButton, cancelButton];
	}
	function _getSelectBlankPopup2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('selectBlankPopup', () => new main_popup.Popup({
	    disableScroll: true,
	    className: 'sign-b2e-document-setup__reupload__popup',
	    content: babelHelpers.classPrivateFieldLooseBase(this, _createPopupContent)[_createPopupContent](popupTypes.select),
	    width: 386,
	    overlay: true,
	    closeByEsc: false,
	    buttons: babelHelpers.classPrivateFieldLooseBase(this, _createSelectPopupButtons)[_createSelectPopupButtons]()
	  }));
	}
	function _createUploadPopupButtons2() {
	  const confirmButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_UPLOAD_CONFIRM'),
	    color: ui_buttons.Button.Color.PRIMARY,
	    round: true,
	    closeByEsc: false,
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getUploadPopup)[_getUploadPopup]().close();
	        babelHelpers.classPrivateFieldLooseBase(this, _isUploadPopupCompleted)[_isUploadPopupCompleted] = true;
	        this.emit(this.events.uploadPopup.onConfirm);
	      }
	    }
	  });
	  const cancelButton = new ui_buttons.Button({
	    text: main_core.Loc.getMessage('SIGN_V2_B2E_DOCUMENT_SETUP_COMPONENTS_POPUP_CANCEL'),
	    color: ui_buttons.Button.Color.LIGHT_BORDER,
	    round: true,
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getUploadPopup)[_getUploadPopup]().close();
	        this.emit(this.events.uploadPopup.onCancel);
	      }
	    }
	  });
	  return [confirmButton, cancelButton];
	}
	function _getUploadPopup2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('uploadPopup', () => new main_popup.Popup({
	    disableScroll: true,
	    className: 'sign-b2e-document-setup__reupload__popup',
	    content: babelHelpers.classPrivateFieldLooseBase(this, _createPopupContent)[_createPopupContent](popupTypes.upload),
	    width: 386,
	    overlay: true,
	    buttons: babelHelpers.classPrivateFieldLooseBase(this, _createUploadPopupButtons)[_createUploadPopupButtons]()
	  }));
	}
	Object.defineProperty(NewBlankForTemplatePopupManager, _instances, {
	  writable: true,
	  value: new WeakMap()
	});

	let _$1 = t => t,
	  _t$1,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12,
	  _t13,
	  _t14,
	  _t15,
	  _t16;
	const HelpdeskCodes = Object.freeze({
	  HowToWorkWithTemplates: '23174934'
	});
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _region = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("region");
	var _senderDocumentTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("senderDocumentTypes");
	var _documentSenderTypeDropdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentSenderTypeDropdown");
	var _documentTitleInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentTitleInput");
	var _b2eDocumentLimitCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("b2eDocumentLimitCount");
	var _currentEditedId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentEditedId");
	var _currentEditButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentEditButton");
	var _currentEditBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentEditBlock");
	var _isOpenedFromRobot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isOpenedFromRobot");
	var _isOpenedFromTemplateFolder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isOpenedFromTemplateFolder");
	var _isOpenedAsFolder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isOpenedAsFolder");
	var _initiatedByType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initiatedByType");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _getSenderDocumentTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSenderDocumentTypes");
	var _subscribeOnEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnEvents");
	var _onBlankSelectorAddFile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBlankSelectorAddFile");
	var _onBlankSelectorToggleSelection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBlankSelectorToggleSelection");
	var _onBlankSelectorBeforeAddFileSuccessfully = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBlankSelectorBeforeAddFileSuccessfully");
	var _initDocumentSenderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDocumentSenderType");
	var _getDocumentSenderTypeLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentSenderTypeLayout");
	var _shouldHideSenderTypeLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldHideSenderTypeLayout");
	var _getHelpLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHelpLink");
	var _getDocumentTitleLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTitleLayout");
	var _getAddDocumentLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAddDocumentLayout");
	var _getAddDocumentButtonText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAddDocumentButtonText");
	var _setDocumentLimitNoticeText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setDocumentLimitNoticeText");
	var _setAddDocumentNoticeText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setAddDocumentNoticeText");
	var _onClickAddDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickAddDocument");
	var _createDocumentBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDocumentBlock");
	var _getDocumentHintLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentHintLayout");
	var _onClickDeleteDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickDeleteDocument");
	var _onClickEditDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickEditDocument");
	var _sendDocumentSenderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentSenderType");
	var _getDocumentSenderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentSenderType");
	var _validateInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateInput");
	var _disableDocumentInputs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableDocumentInputs");
	class DocumentSetup extends sign_v2_documentSetup.DocumentSetup {
	  constructor(blankSelectorConfig) {
	    super(blankSelectorConfig);
	    Object.defineProperty(this, _disableDocumentInputs, {
	      value: _disableDocumentInputs2
	    });
	    Object.defineProperty(this, _validateInput, {
	      value: _validateInput2
	    });
	    Object.defineProperty(this, _getDocumentSenderType, {
	      value: _getDocumentSenderType2
	    });
	    Object.defineProperty(this, _sendDocumentSenderType, {
	      value: _sendDocumentSenderType2
	    });
	    Object.defineProperty(this, _onClickEditDocument, {
	      value: _onClickEditDocument2
	    });
	    Object.defineProperty(this, _onClickDeleteDocument, {
	      value: _onClickDeleteDocument2
	    });
	    Object.defineProperty(this, _getDocumentHintLayout, {
	      value: _getDocumentHintLayout2
	    });
	    Object.defineProperty(this, _createDocumentBlock, {
	      value: _createDocumentBlock2
	    });
	    Object.defineProperty(this, _onClickAddDocument, {
	      value: _onClickAddDocument2
	    });
	    Object.defineProperty(this, _setAddDocumentNoticeText, {
	      value: _setAddDocumentNoticeText2
	    });
	    Object.defineProperty(this, _setDocumentLimitNoticeText, {
	      value: _setDocumentLimitNoticeText2
	    });
	    Object.defineProperty(this, _getAddDocumentButtonText, {
	      value: _getAddDocumentButtonText2
	    });
	    Object.defineProperty(this, _getAddDocumentLayout, {
	      value: _getAddDocumentLayout2
	    });
	    Object.defineProperty(this, _getDocumentTitleLayout, {
	      value: _getDocumentTitleLayout2
	    });
	    Object.defineProperty(this, _getHelpLink, {
	      value: _getHelpLink2
	    });
	    Object.defineProperty(this, _shouldHideSenderTypeLayout, {
	      value: _shouldHideSenderTypeLayout2
	    });
	    Object.defineProperty(this, _getDocumentSenderTypeLayout, {
	      value: _getDocumentSenderTypeLayout2
	    });
	    Object.defineProperty(this, _initDocumentSenderType, {
	      value: _initDocumentSenderType2
	    });
	    Object.defineProperty(this, _onBlankSelectorBeforeAddFileSuccessfully, {
	      value: _onBlankSelectorBeforeAddFileSuccessfully2
	    });
	    Object.defineProperty(this, _onBlankSelectorToggleSelection, {
	      value: _onBlankSelectorToggleSelection2
	    });
	    Object.defineProperty(this, _onBlankSelectorAddFile, {
	      value: _onBlankSelectorAddFile2
	    });
	    Object.defineProperty(this, _subscribeOnEvents, {
	      value: _subscribeOnEvents2
	    });
	    Object.defineProperty(this, _getSenderDocumentTypes, {
	      value: _getSenderDocumentTypes2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _region, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _senderDocumentTypes, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentSenderTypeDropdown, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentTitleInput, {
	      writable: true,
	      value: void 0
	    });
	    this.documentCounters = null;
	    Object.defineProperty(this, _b2eDocumentLimitCount, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentEditedId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentEditButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentEditBlock, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isOpenedFromRobot, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isOpenedFromTemplateFolder, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isOpenedAsFolder, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _initiatedByType, {
	      writable: true,
	      value: void 0
	    });
	    const {
	      region,
	      b2eDocumentLimitCount,
	      isOpenedFromRobot,
	      isOpenedFromTemplateFolder,
	      isOpenedAsFolder,
	      initiatedByType
	    } = blankSelectorConfig;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] = region;
	    babelHelpers.classPrivateFieldLooseBase(this, _b2eDocumentLimitCount)[_b2eDocumentLimitCount] = b2eDocumentLimitCount;
	    this.editMode = false;
	    this.onClickShowHintPopup = this.showHintPopup.bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _isOpenedFromRobot)[_isOpenedFromRobot] = isOpenedFromRobot;
	    babelHelpers.classPrivateFieldLooseBase(this, _isOpenedFromTemplateFolder)[_isOpenedFromTemplateFolder] = isOpenedFromTemplateFolder;
	    babelHelpers.classPrivateFieldLooseBase(this, _isOpenedAsFolder)[_isOpenedAsFolder] = isOpenedAsFolder;
	    babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes] = babelHelpers.classPrivateFieldLooseBase(this, _getSenderDocumentTypes)[_getSenderDocumentTypes]();
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput] = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<input
				type="text"
				class="ui-ctl-element"
				maxlength="255"
				oninput="${0}"
			/>
		`), ({
	      target
	    }) => this.setDocumentTitle(target.value));
	    babelHelpers.classPrivateFieldLooseBase(this, _initiatedByType)[_initiatedByType] = initiatedByType;
	    babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentInputs)[_disableDocumentInputs]();
	    this.disableAddButton();
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  isRuRegion() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] === 'ru';
	  }
	  getAddDocumentButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('addDocumentButton', () => {
	      return main_core.Tag.render(_t2 || (_t2 = _$1`
				<button type="button" class="sign-b2e-document-setup__add-button" onclick="${0}">
					${0}
				</button>
			`), babelHelpers.classPrivateFieldLooseBase(this, _onClickAddDocument)[_onClickAddDocument].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _getAddDocumentButtonText)[_getAddDocumentButtonText]());
	    });
	  }
	  getAddDocumentNotice() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('addDocumentNotice', () => {
	      return main_core.Tag.render(_t3 || (_t3 = _$1`
				<p class="sign-wizard__notice">${0}</p>
			`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_NOTICE'));
	    });
	  }
	  switchAddDocumentButtonLoadingState(loading) {
	    if (loading) {
	      main_core.Dom.addClass(this.getAddDocumentButton(), 'ui-btn-wait');
	    } else {
	      main_core.Dom.removeClass(this.getAddDocumentButton(), 'ui-btn-wait');
	    }
	  }
	  disableAddButton() {
	    main_core.Dom.addClass(this.getAddDocumentButton(), '--disabled');
	  }
	  enableAddButton() {
	    main_core.Dom.removeClass(this.getAddDocumentButton(), '--disabled');
	  }
	  toggleDeleteBtnLoadingState(deleteButton) {
	    main_core.Dom.toggleClass(deleteButton, 'ui-btn-wait');
	  }
	  renderDocumentBlock(documentData) {
	    if (!documentData) {
	      return;
	    }
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _createDocumentBlock)[_createDocumentBlock](documentData), this.headerLayout);
	  }
	  updateDocumentBlock(id) {
	    const editedBlock = this.layout.querySelector(`[data-id="document-id-${id}"]`);
	    const titleNode = editedBlock.querySelector('.sign-b2e-document-setup__document-block_title');
	    titleNode.textContent = babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].title;
	  }
	  replaceDocumentBlock(oldDocument, newDocument) {
	    const editedBlock = this.layout.querySelector(`[data-id="document-id-${oldDocument.id}"]`);
	    main_core.Dom.replace(editedBlock, babelHelpers.classPrivateFieldLooseBase(this, _createDocumentBlock)[_createDocumentBlock](newDocument));
	  }
	  toggleEditMode(id, editButton) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _currentEditedId)[_currentEditedId] !== id) {
	      this.resetEditMode();
	    }
	    const documentBlock = editButton.closest(`[data-id="document-id-${id}"]`);
	    main_core.Dom.toggleClass(documentBlock, '--edit');
	    if (this.editMode) {
	      // eslint-disable-next-line no-param-reassign
	      editButton.textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_EDIT_BUTTON');
	      babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentInputs)[_disableDocumentInputs]();
	      this.disableAddButton();
	      this.editMode = false;
	    } else {
	      // eslint-disable-next-line no-param-reassign
	      editButton.textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_CANCEL_BUTTON');
	      this.editMode = true;
	      this.enableDocumentInputs();
	      this.enableAddButton();
	      babelHelpers.classPrivateFieldLooseBase(this, _currentEditedId)[_currentEditedId] = id;
	      babelHelpers.classPrivateFieldLooseBase(this, _currentEditButton)[_currentEditButton] = editButton;
	      babelHelpers.classPrivateFieldLooseBase(this, _currentEditBlock)[_currentEditBlock] = documentBlock;
	    }
	  }
	  resetEditMode() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _currentEditedId)[_currentEditedId]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _currentEditButton)[_currentEditButton].textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_EDIT_BUTTON');
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _currentEditBlock)[_currentEditBlock], '--edit');
	    this.editMode = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentEditButton)[_currentEditButton] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentEditBlock)[_currentEditBlock] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentEditedId)[_currentEditedId] = null;
	  }
	  getHeaderLayout() {
	    const headerText = this.isTemplateMode() ? main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TEMPLATE_HEADER') : main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_HEADER');
	    this.headerLayout = main_core.Tag.render(_t4 || (_t4 = _$1`
			<h1 class="sign-b2e-settings__header">${0}</h1>
		`), headerText);
	    return this.headerLayout;
	  }
	  setDocumentTitle(title = '') {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].value = title;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].title = title;
	  }
	  setDocumentSenderType(initiatedByType) {
	    if (!this.isTemplateMode() || !this.isSenderTypeAvailable()) {
	      return;
	    }
	    const senderType = babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes].includes(initiatedByType) ? initiatedByType : 'employee';
	    babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].selectItem(senderType);
	  }
	  initLayout() {
	    this.layout = main_core.Tag.render(_t5 || (_t5 = _$1`
			<div class="sign-document-setup">
				${0}
				${0}
			</div>
		`), this.getHeaderLayout(), this.getDocumentSectionLayout());
	  }
	  getDocumentSectionLayout() {
	    if (!this.documentSectionLayout) {
	      this.documentSectionLayout = main_core.Tag.render(_t6 || (_t6 = _$1`
				<div class="sign-b2e-settings__item">
					${0}
				</div>
			`), this.getDocumentSectionInnerLayout());
	      this.createHintPopup();
	    }
	    return this.documentSectionLayout;
	  }
	  getDocumentSectionInnerLayout() {
	    const itemTitleText = this.isTemplateMode() ? main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TEMPLATE_TITLE') : main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE');
	    this.documentSectionInnerLayout = main_core.Tag.render(_t7 || (_t7 = _$1`
			<div class="sign-b2e-settings__item-inner">
				<p class="sign-b2e-settings__item_title">
					${0}
				</p>
				${0}
			</div>
		`), itemTitleText, this.blankSelector.getLayout());
	    return this.documentSectionInnerLayout;
	  }
	  createHintPopup() {
	    this.hintPopup = new main_popup.Popup({
	      content: main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_LIMIT_POPUP'),
	      autoHide: true,
	      darkMode: true
	    });
	  }
	  setAvailabilityDocumentSection(isAvailable) {
	    if (isAvailable) {
	      main_core.Dom.removeClass(this.documentSectionInnerLayout, '--disabled');
	      main_core.Event.unbind(this.documentSectionLayout, 'click', this.onClickShowHintPopup);
	      this.hintPopup.close();
	      return;
	    }
	    main_core.Dom.addClass(this.documentSectionInnerLayout, '--disabled');
	    main_core.Event.bind(this.documentSectionLayout, 'click', this.onClickShowHintPopup);
	  }
	  showHintPopup(event) {
	    this.hintPopup.setBindElement(event);
	    this.hintPopup.show();
	  }
	  async setup(uid) {
	    try {
	      const copyBlocksFromPreviousBlank = this.isEditActionMode() && this.isTemplateMode() && this.blankSelector.isFilesReadyForUpload();
	      await super.setup(uid, this.isTemplateMode(), copyBlocksFromPreviousBlank, babelHelpers.classPrivateFieldLooseBase(this, _getDocumentSenderType)[_getDocumentSenderType]());
	      if (!this.setupData || this.blankIsNotSelected) {
	        this.ready = true;
	        return;
	      }
	      if (uid) {
	        const {
	          title,
	          initiatedByType
	        } = this.setupData;
	        this.setDocumentTitle(title);
	        this.setDocumentSenderType(initiatedByType);
	        return;
	      }
	      this.ready = false;
	      this.setupData = await this.updateDocumentData(this.setupData);
	    } catch {
	      const {
	        blankId
	      } = this.setupData;
	      this.handleError(blankId);
	    }
	    this.ready = true;
	  }
	  async updateDocumentData(documentData) {
	    if (!documentData) {
	      return;
	    }
	    await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentSenderType)[_sendDocumentSenderType](documentData.uid)]);
	    const {
	      value: title
	    } = babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput];
	    const {
	      templateUid
	    } = this.setupData;
	    const modifyDocumentTitleResponse = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyTitle(documentData.uid, title);
	    const {
	      blankTitle
	    } = modifyDocumentTitleResponse;
	    if (blankTitle) {
	      const {
	        blankId
	      } = documentData;
	      this.blankSelector.modifyBlankTitle(blankId, blankTitle);
	    }
	    return {
	      ...documentData,
	      title,
	      templateUid
	    };
	  }
	  validate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _validateInput)[_validateInput](babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput]);
	  }
	  isSenderTypeAvailable() {
	    const settings = main_core.Extension.getSettings('sign.v2.b2e.document-setup');
	    return settings.get('isSenderTypeAvailable');
	  }
	  resetDocument() {
	    this.blankSelector.resetSelectedBlank();
	    this.setDocumentTitle('');
	    this.isFileAdded = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentInputs)[_disableDocumentInputs]();
	    this.disableAddButton();
	  }
	  enableDocumentInputs() {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].disabled = false;
	    this.blankIsNotSelected = false;
	  }
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _initDocumentSenderType)[_initDocumentSenderType]();
	  const title = this.isTemplateMode() ? main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_TEMPLATE_HEAD_LABEL') : main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HEAD_LABEL');
	  const titleLayout = main_core.Tag.render(_t8 || (_t8 = _$1`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${0}
				</p>
				${0}
			</div>
		`), title, babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTitleLayout)[_getDocumentTitleLayout]());
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getDocumentSenderTypeLayout)[_getDocumentSenderTypeLayout](), this.layout);
	  main_core.Dom.append(titleLayout, this.layout);
	  if (!this.isTemplateMode() && sign_featureStorage.FeatureStorage.isGroupSendingEnabled()) {
	    this.documentCounters = new sign_v2_b2e_documentCounters.DocumentCounters({
	      documentCountersLimit: babelHelpers.classPrivateFieldLooseBase(this, _b2eDocumentLimitCount)[_b2eDocumentLimitCount]
	    });
	    main_core.Dom.append(this.documentCounters.getLayout(), this.layout);
	    const addDocumentLayout = babelHelpers.classPrivateFieldLooseBase(this, _getAddDocumentLayout)[_getAddDocumentLayout]();
	    main_core.Dom.append(addDocumentLayout, this.layout);
	  }
	  sign_v2_helper.Hint.create(this.layout);
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnEvents)[_subscribeOnEvents]();
	}
	function _getSenderDocumentTypes2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isOpenedFromTemplateFolder)[_isOpenedFromTemplateFolder] || babelHelpers.classPrivateFieldLooseBase(this, _isOpenedAsFolder)[_isOpenedAsFolder]) {
	    return [sign_type.DocumentInitiated.company];
	  }
	  return Object.values(sign_type.DocumentInitiated);
	}
	function _subscribeOnEvents2() {
	  const blankSelector = this.blankSelector;
	  blankSelector.subscribe(blankSelector.events.toggleSelection, babelHelpers.classPrivateFieldLooseBase(this, _onBlankSelectorToggleSelection)[_onBlankSelectorToggleSelection].bind(this));
	  blankSelector.subscribe(blankSelector.events.addFile, babelHelpers.classPrivateFieldLooseBase(this, _onBlankSelectorAddFile)[_onBlankSelectorAddFile].bind(this));
	  blankSelector.subscribe(blankSelector.events.beforeAddFileSuccessfully, babelHelpers.classPrivateFieldLooseBase(this, _onBlankSelectorBeforeAddFileSuccessfully)[_onBlankSelectorBeforeAddFileSuccessfully].bind(this));
	  if (!this.isTemplateMode() && sign_featureStorage.FeatureStorage.isGroupSendingEnabled()) {
	    this.documentCounters.subscribe('limitNotExceeded', () => {
	      this.enableAddButton();
	      babelHelpers.classPrivateFieldLooseBase(this, _setAddDocumentNoticeText)[_setAddDocumentNoticeText]();
	      this.emit('documentsLimitNotExceeded');
	    });
	    this.documentCounters.subscribe('limitExceeded', () => {
	      this.disableAddButton();
	      babelHelpers.classPrivateFieldLooseBase(this, _setDocumentLimitNoticeText)[_setDocumentLimitNoticeText]();
	      this.emit('documentsLimitExceeded');
	    });
	  }
	}
	function _onBlankSelectorAddFile2(event) {
	  const data = event.getData();
	  this.isFileAdded = true;
	  this.enableDocumentInputs();
	  this.enableAddButton();
	  if (!this.isTemplateMode() || !this.isEditActionMode()) {
	    this.setDocumentTitle(data.title);
	  }
	  const popupManager = NewBlankForTemplatePopupManager.getOrCreateForObject(this);
	  // Document title will update after popup confirmation
	  if (popupManager.isUploadPopupCompletedOnce()) {
	    this.setDocumentTitle(data.title);
	  }
	}
	function _onBlankSelectorToggleSelection2(event) {
	  if (this.blankIsNotSelected && this.editMode) {
	    return;
	  }
	  const data = event.getData();
	  const handleEvent = () => {
	    this.setDocumentTitle(data.title);
	    if (data.selected) {
	      this.enableDocumentInputs();
	      this.enableAddButton();
	    }
	  };
	  if (!this.isEditActionMode() || !this.isTemplateMode()) {
	    handleEvent();
	    return;
	  }
	  const popupManager = NewBlankForTemplatePopupManager.getOrCreateForObject(this);
	  if (popupManager.isSelectBlankPopupCompletedOnce()) {
	    handleEvent();
	    return;
	  }
	  const extra = data.extra;
	  if (!data.selected || extra != null && extra.isInitial || extra != null && extra.skipSelectPopupShow) {
	    return;
	  }
	  const previousSelectedBlankId = data.previousSelectedBlankId;
	  popupManager.showSelectBlankPopup();
	  popupManager.subscribeOnce(popupManager.events.selectBlankPopup.onConfirm, handleEvent);
	  popupManager.subscribeOnce(popupManager.events.selectBlankPopup.onCancel, () => {
	    popupManager.unsubscribe(popupManager.events.selectBlankPopup.onConfirm, handleEvent);
	    if (main_core.Type.isNumber(previousSelectedBlankId) && previousSelectedBlankId > 0) {
	      this.blankSelector.selectBlank(previousSelectedBlankId, {
	        skipSelectPopupShow: true
	      });
	    }
	  });
	}
	function _onBlankSelectorBeforeAddFileSuccessfully2(event) {
	  if (!this.isTemplateMode() || !this.isEditActionMode()) {
	    return;
	  }
	  const newBlankPopupManager = NewBlankForTemplatePopupManager.getOrCreateForObject(this);
	  const lastSelectedBlank = this.blankSelector.selectedBlankId;
	  if (newBlankPopupManager.isUploadPopupCompletedOnce()) {
	    return;
	  }
	  newBlankPopupManager.showUploadPopup();
	  const onCancelListener = () => {
	    this.blankSelector.clearFiles();
	    this.blankSelector.selectBlank(lastSelectedBlank, {
	      skipSelectPopupShow: true
	    });
	  };
	  const onConfirmListener = () => {
	    this.blankSelector.getBlank();
	    const title = this.blankSelector.getUploadedFileName(0);
	    if (main_core.Type.isStringFilled(title)) {
	      this.setDocumentTitle(title);
	    }
	  };
	  const unsubscribeAnotherListenerDecorator = (listener, unsubscribedListener) => {
	    return event => {
	      listener(event);
	      newBlankPopupManager.unsubscribe(newBlankPopupManager.events.uploadPopup.onCancel, unsubscribedListener);
	      newBlankPopupManager.unsubscribe(newBlankPopupManager.events.uploadPopup.onConfirm, unsubscribedListener);
	    };
	  };
	  newBlankPopupManager.subscribeOnce(newBlankPopupManager.events.uploadPopup.onCancel, unsubscribeAnotherListenerDecorator(onCancelListener, onConfirmListener));
	  newBlankPopupManager.subscribeOnce(newBlankPopupManager.events.uploadPopup.onConfirm, unsubscribeAnotherListenerDecorator(onConfirmListener, onCancelListener));
	}
	function _initDocumentSenderType2() {
	  var _babelHelpers$classPr;
	  if (!this.isTemplateMode() || !this.isSenderTypeAvailable()) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown] = new sign_v2_b2e_signDropdown.SignDropdown({
	    tabs: [{
	      id: 'b2e-document-sender-types',
	      title: ' '
	    }],
	    entities: [{
	      id: 'b2e-document-sender-type',
	      searchFields: [{
	        name: 'caption',
	        system: true
	      }]
	    }],
	    className: 'sign-b2e-document-setup__sender-type-selector',
	    withCaption: true,
	    isEnableSearch: false,
	    height: 110,
	    width: 350
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes].forEach(item => {
	    if (main_core.Type.isStringFilled(item)) {
	      const langPhraseCode = `SIGN_DOCUMENT_SETUP_SENDER_TYPE_${item.toUpperCase()}`;
	      babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].addItem({
	        id: item,
	        title: main_core.Loc.getMessage(langPhraseCode),
	        entityId: 'b2e-document-sender-type',
	        tabs: 'b2e-document-sender-types',
	        deselectable: false
	      });
	    }
	  });
	  const selectedKey = babelHelpers.classPrivateFieldLooseBase(this, _isOpenedFromRobot)[_isOpenedFromRobot] ? 1 : 0;
	  const selectedItem = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes][selectedKey]) != null ? _babelHelpers$classPr : null;
	  if (selectedItem) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].selectItem(selectedItem);
	  }
	}
	function _getDocumentSenderTypeLayout2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _shouldHideSenderTypeLayout)[_shouldHideSenderTypeLayout]()) {
	    return null;
	  }
	  return main_core.Tag.render(_t9 || (_t9 = _$1`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					<span>${0}</span>
				</p>
				${0}
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_SENDER_TYPE_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _getHelpLink)[_getHelpLink]());
	}
	function _shouldHideSenderTypeLayout2() {
	  return !this.isTemplateMode() || !this.isSenderTypeAvailable() || babelHelpers.classPrivateFieldLooseBase(this, _isOpenedFromRobot)[_isOpenedFromRobot] || babelHelpers.classPrivateFieldLooseBase(this, _isOpenedFromTemplateFolder)[_isOpenedFromTemplateFolder] || babelHelpers.classPrivateFieldLooseBase(this, _isOpenedAsFolder)[_isOpenedAsFolder];
	}
	function _getHelpLink2() {
	  return sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_SENDER_TYPE_HELP_LINK'), HelpdeskCodes.HowToWorkWithTemplates, 'detail', ['ui-link']);
	}
	function _getDocumentTitleLayout2() {
	  return main_core.Tag.render(_t10 || (_t10 = _$1`
			<div>
				<div class="sign-b2e-document-setup__title-item --full">
					<div class="ui-ctl ui-ctl-textbox">
						${0}
					</div>
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput], babelHelpers.classPrivateFieldLooseBase(this, _getDocumentHintLayout)[_getDocumentHintLayout]());
	}
	function _getAddDocumentLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('addDocumentLayout', () => {
	    return main_core.Tag.render(_t11 || (_t11 = _$1`
				<div class="sign-b2e-settings__item --add">
					<div class="sign-b2e-settings__item_title">
						<span>${0}</span>
						${0}
					</div>
					${0}
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT'), this.documentCounters.getLayout(), this.getAddDocumentButton(), this.getAddDocumentNotice());
	  });
	}
	function _getAddDocumentButtonText2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('addDocumentButtonText', () => {
	    return main_core.Tag.render(_t12 || (_t12 = _$1`
				<span class="sign-b2e-document-setup__add-button_text">${0}</span>
			`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_ANOTHER_DOCUMENT'));
	  });
	}
	function _setDocumentLimitNoticeText2() {
	  main_core.Dom.addClass(this.getAddDocumentNotice(), '--warning');
	  this.getAddDocumentNotice().textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_GROUP_LIMIT_NOTICE', {
	    '%limit%': babelHelpers.classPrivateFieldLooseBase(this, _b2eDocumentLimitCount)[_b2eDocumentLimitCount]
	  });
	}
	function _setAddDocumentNoticeText2() {
	  main_core.Dom.removeClass(this.getAddDocumentNotice(), '--warning');
	  this.getAddDocumentNotice().textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_NOTICE');
	}
	function _onClickAddDocument2() {
	  this.emit('addDocument');
	}
	function _createDocumentBlock2(documentData) {
	  const deleteButton = main_core.Tag.render(_t13 || (_t13 = _$1`
			<button class="sign-b2e-document-setup__document-block_delete" type="button"></button>
		`));
	  const editButton = main_core.Tag.render(_t14 || (_t14 = _$1`
			<button class="ui-btn ui-btn-round ui-btn-sm ui-btn-light-border" type="button">
				${0}
			</button>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_EDIT_BUTTON'));
	  main_core.Event.bind(deleteButton, 'click', event => {
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickDeleteDocument)[_onClickDeleteDocument](documentData, event);
	  });
	  main_core.Event.bind(editButton, 'click', event => {
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickEditDocument)[_onClickEditDocument](documentData, event);
	  });
	  return main_core.Tag.render(_t15 || (_t15 = _$1`
			<div class="sign-b2e-document-setup__document-block" data-id="document-id-${0}">
				<div class="sign-b2e-document-setup__document-block_inner">
					<div class="sign-b2e-document-setup__document-block_title">${0}</div>
				</div>
				<div class="sign-b2e-document-setup__document-block_btn">
					${0}
					${0}
				</div>
				<div class="sign-b2e-document-setup__document-block_hint">
					${0}
				</div>
			</div>
		`), documentData.id, documentData.title, editButton, deleteButton, main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_HINT'));
	}
	function _getDocumentHintLayout2() {
	  if (this.isTemplateMode()) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('documentHintLayout', () => {
	    return main_core.Tag.render(_t16 || (_t16 = _$1`
				<p class="sign-b2e-document-setup__title-text">
					${0}
				</p>
			`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HINT'));
	  });
	}
	function _onClickDeleteDocument2(documentData, event) {
	  this.setupData = null;
	  const {
	    id,
	    uid,
	    blankId
	  } = documentData;
	  const deleteButton = event.target;
	  this.toggleDeleteBtnLoadingState(deleteButton);
	  this.emit('deleteDocument', {
	    id,
	    uid,
	    blankId,
	    deleteButton
	  });
	}
	function _onClickEditDocument2(documentData, event) {
	  const {
	    id,
	    uid
	  } = documentData;
	  this.toggleEditMode(id, event.target);
	  this.emit('editDocument', {
	    uid
	  });
	}
	function _sendDocumentSenderType2(uid) {
	  if (!this.isTemplateMode() || !this.isSenderTypeAvailable()) {
	    return Promise.resolve();
	  }
	  const senderType = babelHelpers.classPrivateFieldLooseBase(this, _getDocumentSenderType)[_getDocumentSenderType]();
	  this.setupData.initiatedByType = senderType;
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeSenderDocumentType(uid, senderType);
	}
	function _getDocumentSenderType2() {
	  if (!this.isTemplateMode()) {
	    return null;
	  }
	  if (!this.isSenderTypeAvailable()) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _initiatedByType)[_initiatedByType];
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].getSelectedId();
	}
	function _validateInput2(input) {
	  if (!input) {
	    return true;
	  }
	  const {
	    parentNode,
	    value
	  } = input;
	  if (value.trim() !== '') {
	    main_core.Dom.removeClass(parentNode, 'ui-ctl-warning');
	    return true;
	  }
	  main_core.Dom.addClass(parentNode, 'ui-ctl-warning');
	  input.focus();
	  return false;
	}
	function _disableDocumentInputs2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].disabled = true;
	  this.blankIsNotSelected = true;
	}

	exports.DocumentSetup = DocumentSetup;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Sign,BX.Sign,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign.V2,BX,BX.Cache,BX.Event,BX.Main,BX.UI));
//# sourceMappingURL=document-setup.bundle.js.map

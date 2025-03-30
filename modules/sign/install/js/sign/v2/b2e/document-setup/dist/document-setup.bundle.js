/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_popup,sign_v2_api,sign_v2_b2e_signDropdown,sign_featureStorage,sign_v2_b2e_documentCounters,sign_type,sign_v2_documentSetup,sign_v2_helper,sign_v2_signSettings,main_core,main_date) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _documentDateField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentDateField");
	var _selectedDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedDate");
	var _formatDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatDate");
	var _getDateField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDateField");
	var _selectDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectDate");
	class DateSelector {
	  constructor() {
	    Object.defineProperty(this, _selectDate, {
	      value: _selectDate2
	    });
	    Object.defineProperty(this, _getDateField, {
	      value: _getDateField2
	    });
	    Object.defineProperty(this, _formatDate, {
	      value: _formatDate2
	    });
	    Object.defineProperty(this, _documentDateField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedDate, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField] = babelHelpers.classPrivateFieldLooseBase(this, _getDateField)[_getDateField]();
	    const _date = new Date();
	    babelHelpers.classPrivateFieldLooseBase(this, _selectDate)[_selectDate](_date);
	    this.setDateInCalendar(_date);
	  }
	  setDateInCalendar(date) {
	    const formattedDate = babelHelpers.classPrivateFieldLooseBase(this, _formatDate)[_formatDate](date, 'DAY_MONTH_FORMAT');
	    const dateTextNode = babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField].firstElementChild;
	    dateTextNode.textContent = formattedDate;
	    dateTextNode.title = formattedDate;
	  }
	  getLayout() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="sign-b2e-document-setup__date-selector">
				<span class="sign-b2e-document-setup__date-selector_label">
					${0}
				</span>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DATE_LABEL'), babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField]);
	  }
	  getSelectedDate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate];
	  }
	}
	function _formatDate2(date, formatType) {
	  const template = main_date.DateTimeFormat.getFormat(formatType);
	  return main_date.DateTimeFormat.format(template, date);
	}
	function _getDateField2() {
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<div
				class="sign-b2e-document-setup__date-selector_field"
				onclick="${0}"
			>
				<span class="sign-b2e-document-setup__date-selector_field-text"></span>
			</div>
		`), () => {
	    BX.calendar({
	      node: babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField],
	      field: babelHelpers.classPrivateFieldLooseBase(this, _documentDateField)[_documentDateField],
	      bTime: false,
	      callback_after: date => {
	        babelHelpers.classPrivateFieldLooseBase(this, _selectDate)[_selectDate](date);
	        this.setDateInCalendar(date);
	      }
	    });
	  });
	}
	function _selectDate2(date) {
	  const formattedDate = babelHelpers.classPrivateFieldLooseBase(this, _formatDate)[_formatDate](date, 'SHORT_DATE_FORMAT');
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedDate)[_selectedDate] = formattedDate;
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
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
	  _t16,
	  _t17,
	  _t18,
	  _t19,
	  _t20;
	const HelpdeskCodes = Object.freeze({
	  HowToWorkWithTemplates: '23174934'
	});
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _region = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("region");
	var _regionDocumentTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("regionDocumentTypes");
	var _senderDocumentTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("senderDocumentTypes");
	var _documentTypeDropdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentTypeDropdown");
	var _documentSenderTypeDropdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentSenderTypeDropdown");
	var _documentNumberInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentNumberInput");
	var _documentTitleInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentTitleInput");
	var _dateSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dateSelector");
	var _b2eDocumentLimitCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("b2eDocumentLimitCount");
	var _currentEditedId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentEditedId");
	var _currentEditButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentEditButton");
	var _currentEditBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentEditBlock");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _isDocumentTypeVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDocumentTypeVisible");
	var _initDocumentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDocumentType");
	var _getDocumentTypeLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTypeLayout");
	var _initDocumentSenderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDocumentSenderType");
	var _getDocumentSenderTypeLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentSenderTypeLayout");
	var _getHelpLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getHelpLink");
	var _getDocumentNumberLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentNumberLayout");
	var _getDocumentTitleLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTitleLayout");
	var _getAddDocumentLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAddDocumentLayout");
	var _getAddDocumentButtonText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAddDocumentButtonText");
	var _setDocumentLimitNoticeText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setDocumentLimitNoticeText");
	var _setAddDocumentNoticeText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setAddDocumentNoticeText");
	var _onClickAddDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickAddDocument");
	var _createDocumentBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDocumentBlock");
	var _getDocumentTypeDropdownLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTypeDropdownLayout");
	var _getDocumentHintLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentHintLayout");
	var _onClickDeleteDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickDeleteDocument");
	var _onClickEditDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickEditDocument");
	var _getDocumentTitleFullClass = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTitleFullClass");
	var _sendDocumentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentType");
	var _sendDocumentSenderType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentSenderType");
	var _sendDocumentNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentNumber");
	var _sendDocumentDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentDate");
	var _validateInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateInput");
	var _enableDocumentInputs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableDocumentInputs");
	var _disableDocumentInputs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableDocumentInputs");
	class DocumentSetup extends sign_v2_documentSetup.DocumentSetup {
	  constructor(blankSelectorConfig) {
	    super(blankSelectorConfig);
	    Object.defineProperty(this, _disableDocumentInputs, {
	      value: _disableDocumentInputs2
	    });
	    Object.defineProperty(this, _enableDocumentInputs, {
	      value: _enableDocumentInputs2
	    });
	    Object.defineProperty(this, _validateInput, {
	      value: _validateInput2
	    });
	    Object.defineProperty(this, _sendDocumentDate, {
	      value: _sendDocumentDate2
	    });
	    Object.defineProperty(this, _sendDocumentNumber, {
	      value: _sendDocumentNumber2
	    });
	    Object.defineProperty(this, _sendDocumentSenderType, {
	      value: _sendDocumentSenderType2
	    });
	    Object.defineProperty(this, _sendDocumentType, {
	      value: _sendDocumentType2
	    });
	    Object.defineProperty(this, _getDocumentTitleFullClass, {
	      value: _getDocumentTitleFullClass2
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
	    Object.defineProperty(this, _getDocumentTypeDropdownLayout, {
	      value: _getDocumentTypeDropdownLayout2
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
	    Object.defineProperty(this, _getDocumentNumberLayout, {
	      value: _getDocumentNumberLayout2
	    });
	    Object.defineProperty(this, _getHelpLink, {
	      value: _getHelpLink2
	    });
	    Object.defineProperty(this, _getDocumentSenderTypeLayout, {
	      value: _getDocumentSenderTypeLayout2
	    });
	    Object.defineProperty(this, _initDocumentSenderType, {
	      value: _initDocumentSenderType2
	    });
	    Object.defineProperty(this, _getDocumentTypeLayout, {
	      value: _getDocumentTypeLayout2
	    });
	    Object.defineProperty(this, _initDocumentType, {
	      value: _initDocumentType2
	    });
	    Object.defineProperty(this, _isDocumentTypeVisible, {
	      value: _isDocumentTypeVisible2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _region, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _regionDocumentTypes, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _senderDocumentTypes, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentTypeDropdown, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentSenderTypeDropdown, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentNumberInput, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _documentTitleInput, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dateSelector, {
	      writable: true,
	      value: null
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
	    const {
	      region,
	      regionDocumentTypes,
	      documentMode,
	      b2eDocumentLimitCount
	    } = blankSelectorConfig;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] = region;
	    babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes] = regionDocumentTypes;
	    babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes] = Object.values(sign_type.DocumentInitiated);
	    babelHelpers.classPrivateFieldLooseBase(this, _b2eDocumentLimitCount)[_b2eDocumentLimitCount] = b2eDocumentLimitCount;
	    this.editMode = false;
	    this.onClickShowHintPopup = this.showHintPopup.bind(this);
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
	    if (this.isRuRegion() && !sign_v2_signSettings.isTemplateMode(documentMode)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput] = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`<input type="text" class="ui-ctl-element" maxlength="255" />`));
	      babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector] = new DateSelector();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentInputs)[_disableDocumentInputs]();
	    this.disableAddButton();
	    this.blankSelector.subscribe('toggleSelection', ({
	      data
	    }) => {
	      this.setDocumentTitle(data.title);
	      if (data.selected) {
	        babelHelpers.classPrivateFieldLooseBase(this, _enableDocumentInputs)[_enableDocumentInputs]();
	        this.enableAddButton();
	      }
	    });
	    this.blankSelector.subscribe('addFile', ({
	      data
	    }) => {
	      this.isFileAdded = true;
	      this.setDocumentTitle(data.title);
	      babelHelpers.classPrivateFieldLooseBase(this, _enableDocumentInputs)[_enableDocumentInputs]();
	      this.enableAddButton();
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  isRuRegion() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] === 'ru';
	  }
	  getAddDocumentButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('addDocumentButton', () => {
	      return main_core.Tag.render(_t3 || (_t3 = _$1`
				<button type="button" class="sign-b2e-document-setup__add-button" onclick="${0}">
					${0}
				</button>
			`), babelHelpers.classPrivateFieldLooseBase(this, _onClickAddDocument)[_onClickAddDocument].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _getAddDocumentButtonText)[_getAddDocumentButtonText]());
	    });
	  }
	  getAddDocumentNotice() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('addDocumentNotice', () => {
	      return main_core.Tag.render(_t4 || (_t4 = _$1`
				<p class="sign-b2e-document-setup__add-notice">${0}</p>
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
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	      const infoNode = editedBlock.querySelector('.sign-b2e-document-setup__document-block_info');
	      infoNode.textContent = babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].getSelectedCaption();
	    }
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
	      babelHelpers.classPrivateFieldLooseBase(this, _enableDocumentInputs)[_enableDocumentInputs]();
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
	    this.headerLayout = main_core.Tag.render(_t5 || (_t5 = _$1`
			<h1 class="sign-b2e-settings__header">${0}</h1>
		`), headerText);
	    return this.headerLayout;
	  }
	  setDocumentNumber(number) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput].value = number;
	  }
	  setDocumentTitle(title = '') {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].value = title;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].title = title;
	  }
	  setDocumentType(regionDocumentType = '') {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	      return;
	    }
	    const isDocumentTypeExist = babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes].some(item => item.code === regionDocumentType);
	    const documentType = isDocumentTypeExist ? regionDocumentType : babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes][0].code;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].selectItem(documentType);
	  }
	  setDocumentSenderType(initiatedByType) {
	    if (!this.isTemplateMode() || !this.isSenderTypeAvailable()) {
	      return;
	    }
	    const senderType = babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes].includes(initiatedByType) ? initiatedByType : 'employee';
	    babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].selectItem(senderType);
	  }
	  initLayout() {
	    this.layout = main_core.Tag.render(_t6 || (_t6 = _$1`
			<div class="sign-document-setup">
				${0}
				${0}
			</div>
		`), this.getHeaderLayout(), this.getDocumentSectionLayout());
	  }
	  getDocumentSectionLayout() {
	    if (!this.documentSectionLayout) {
	      this.documentSectionLayout = main_core.Tag.render(_t7 || (_t7 = _$1`
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
	    this.documentSectionInnerLayout = main_core.Tag.render(_t8 || (_t8 = _$1`
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
	      await super.setup(uid, this.isTemplateMode());
	      if (!this.setupData || this.blankIsNotSelected) {
	        this.ready = true;
	        return;
	      }
	      if (uid) {
	        const {
	          title,
	          externalId,
	          externalDateCreate,
	          initiatedByType,
	          regionDocumentType
	        } = this.setupData;
	        this.setDocumentTitle(title);
	        this.setDocumentSenderType(initiatedByType);
	        this.setDocumentType(regionDocumentType);
	        if (this.isRuRegion() && !this.isTemplateMode()) {
	          this.setDocumentNumber(externalId);
	          babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector].setDateInCalendar(new Date(externalDateCreate));
	        }
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
	    var _babelHelpers$classPr;
	    if (!documentData) {
	      return;
	    }
	    await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentType)[_sendDocumentType](documentData.uid), babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentSenderType)[_sendDocumentSenderType](documentData.uid), babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentNumber)[_sendDocumentNumber](documentData.uid), babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentDate)[_sendDocumentDate](documentData.uid)]);
	    const {
	      value: title
	    } = babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput];
	    const {
	      templateUid
	    } = this.setupData;
	    const externalId = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]) == null ? void 0 : _babelHelpers$classPr.value;
	    let regionDocumentType = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	      regionDocumentType = babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].getSelectedId();
	    }
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
	    documentData = {
	      ...documentData,
	      title,
	      externalId,
	      regionDocumentType,
	      templateUid
	    };
	    return documentData;
	  }
	  validate() {
	    const isValidTitle = babelHelpers.classPrivateFieldLooseBase(this, _validateInput)[_validateInput](babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput]);
	    const isValidNumber = babelHelpers.classPrivateFieldLooseBase(this, _validateInput)[_validateInput](babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]);
	    return isValidTitle && isValidNumber;
	  }
	  isSenderTypeAvailable() {
	    const settings = main_core.Extension.getSettings('sign.v2.b2e.document-setup');
	    return settings.get('isSenderTypeAvailable');
	  }
	  resetDocument() {
	    this.blankSelector.resetSelectedBlank();
	    this.setDocumentTitle('');
	    if (this.isRuRegion()) {
	      this.setDocumentNumber('');
	    }
	    this.isFileAdded = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _disableDocumentInputs)[_disableDocumentInputs]();
	    this.disableAddButton();
	  }
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _initDocumentType)[_initDocumentType]();
	  babelHelpers.classPrivateFieldLooseBase(this, _initDocumentSenderType)[_initDocumentSenderType]();
	  const documentTypeLayout = babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTypeLayout)[_getDocumentTypeLayout]();
	  const title = this.isTemplateMode() ? main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_TEMPLATE_HEAD_LABEL') : main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HEAD_LABEL');
	  const titleLayout = main_core.Tag.render(_t9 || (_t9 = _$1`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${0}
				</p>
				${0}
			</div>
		`), title, babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTitleLayout)[_getDocumentTitleLayout]());
	  main_core.Dom.append(documentTypeLayout, this.layout);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getDocumentSenderTypeLayout)[_getDocumentSenderTypeLayout](), this.layout);
	  main_core.Dom.append(titleLayout, this.layout);
	  if (!this.isTemplateMode() && sign_featureStorage.FeatureStorage.isGroupSendingEnabled()) {
	    this.documentCounters = new sign_v2_b2e_documentCounters.DocumentCounters({
	      documentCountersLimit: babelHelpers.classPrivateFieldLooseBase(this, _b2eDocumentLimitCount)[_b2eDocumentLimitCount]
	    });
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
	    main_core.Dom.append(this.documentCounters.getLayout(), this.layout);
	    const addDocumentLayout = babelHelpers.classPrivateFieldLooseBase(this, _getAddDocumentLayout)[_getAddDocumentLayout]();
	    main_core.Dom.append(addDocumentLayout, this.layout);
	  }
	  sign_v2_helper.Hint.create(this.layout);
	}
	function _isDocumentTypeVisible2() {
	  var _babelHelpers$classPr2;
	  return (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes]) == null ? void 0 : _babelHelpers$classPr2.length;
	}
	function _initDocumentType2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown] = new sign_v2_b2e_signDropdown.SignDropdown({
	    tabs: [{
	      id: 'b2e-document-codes',
	      title: ' '
	    }],
	    entities: [{
	      id: 'b2e-document-code',
	      searchFields: [{
	        name: 'caption',
	        system: true
	      }]
	    }],
	    className: 'sign-b2e-document-setup__type-selector',
	    withCaption: true,
	    isEnableSearch: true
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes].forEach(item => {
	    if (main_core.Type.isPlainObject(item) && main_core.Type.isStringFilled(item.code) && main_core.Type.isStringFilled(item.description)) {
	      const {
	        code,
	        description
	      } = item;
	      babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].addItem({
	        id: code,
	        title: code,
	        caption: `(${description})`,
	        entityId: 'b2e-document-code',
	        tabs: 'b2e-document-codes',
	        deselectable: false
	      });
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].selectItem(babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes][0].code);
	}
	function _getDocumentTypeLayout2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	    return null;
	  }
	  return main_core.Tag.render(_t10 || (_t10 = _$1`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					<span>${0}</span>
					<span
						data-hint="${0}"
					></span>
				</p>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TYPE'), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TYPE_HINT'), babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].getLayout());
	}
	function _initDocumentSenderType2() {
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
	  babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].selectItem(babelHelpers.classPrivateFieldLooseBase(this, _senderDocumentTypes)[_senderDocumentTypes][0]);
	}
	function _getDocumentSenderTypeLayout2() {
	  if (!this.isTemplateMode() || !this.isSenderTypeAvailable()) {
	    return null;
	  }
	  return main_core.Tag.render(_t11 || (_t11 = _$1`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					<span>${0}</span>
				</p>
				${0}
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_SENDER_TYPE_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _getHelpLink)[_getHelpLink]());
	}
	function _getHelpLink2() {
	  return sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_SENDER_TYPE_HELP_LINK'), HelpdeskCodes.HowToWorkWithTemplates, 'detail', ['ui-link']);
	}
	function _getDocumentNumberLayout2() {
	  if (!this.isRuRegion() || this.isTemplateMode()) {
	    return null;
	  }
	  return main_core.Tag.render(_t12 || (_t12 = _$1`
			<div class="sign-b2e-document-setup__title-item --num">
				<p class="sign-b2e-document-setup__title-text">
					<span>${0}</span>
					<span
						data-hint="${0}"
					></span>
				</p>
				<div class="ui-ctl ui-ctl-textbox">
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_NUM_LABEL'), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_NUM_LABEL_HINT'), babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]);
	}
	function _getDocumentTitleLayout2() {
	  var _babelHelpers$classPr3;
	  return main_core.Tag.render(_t13 || (_t13 = _$1`
			<div>
				<div class="sign-b2e-document-setup__title-item ${0}">
					<p class="sign-b2e-document-setup__title-text">
						${0}
					</p>
					<div class="ui-ctl ui-ctl-textbox">
						${0}
					</div>
				</div>
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTitleFullClass)[_getDocumentTitleFullClass](), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_LABEL'), babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput], babelHelpers.classPrivateFieldLooseBase(this, _getDocumentNumberLayout)[_getDocumentNumberLayout](), babelHelpers.classPrivateFieldLooseBase(this, _getDocumentHintLayout)[_getDocumentHintLayout](), (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector]) == null ? void 0 : _babelHelpers$classPr3.getLayout());
	}
	function _getAddDocumentLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('addDocumentLayout', () => {
	    return main_core.Tag.render(_t14 || (_t14 = _$1`
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
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('addDocumentButtonText', () => {
	    return main_core.Tag.render(_t15 || (_t15 = _$1`
				<span class="sign-b2e-document-setup__add-button_text">${0}</span>
			`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_ANOTHER_DOCUMENT'));
	  });
	}
	function _setDocumentLimitNoticeText2() {
	  main_core.Dom.addClass(this.getAddDocumentNotice(), '--warning');
	  this.getAddDocumentNotice().textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_DOCUMENT_LIMIT_NOTICE');
	}
	function _setAddDocumentNoticeText2() {
	  main_core.Dom.removeClass(this.getAddDocumentNotice(), '--warning');
	  this.getAddDocumentNotice().textContent = main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_NOTICE');
	}
	function _onClickAddDocument2() {
	  this.emit('addDocument');
	}
	function _createDocumentBlock2(documentData) {
	  const deleteButton = main_core.Tag.render(_t16 || (_t16 = _$1`
			<button class="sign-b2e-document-setup__document-block_delete" type="button"></button>
		`));
	  const editButton = main_core.Tag.render(_t17 || (_t17 = _$1`
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
	  const documentTypeDropdownLayout = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]() ? babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTypeDropdownLayout)[_getDocumentTypeDropdownLayout]() : '';
	  return main_core.Tag.render(_t18 || (_t18 = _$1`
			<div class="sign-b2e-document-setup__document-block" data-id="document-id-${0}">
				<div class="sign-b2e-document-setup__document-block_inner">
					<div class="sign-b2e-document-setup__document-block_title">${0}</div>
					${0}
				</div>
				<div class="sign-b2e-document-setup__document-block_btn">
					${0}
					${0}
				</div>
				<div class="sign-b2e-document-setup__document-block_hint">
					${0}
				</div>
			</div>
		`), documentData.id, documentData.title, documentTypeDropdownLayout, editButton, deleteButton, main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_DOCUMENT_HINT'));
	}
	function _getDocumentTypeDropdownLayout2() {
	  return main_core.Tag.render(_t19 || (_t19 = _$1`
			<div class="sign-b2e-document-setup__document-block_info">${0}</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].getSelectedCaption());
	}
	function _getDocumentHintLayout2() {
	  if (this.isTemplateMode()) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('documentHintLayout', () => {
	    return main_core.Tag.render(_t20 || (_t20 = _$1`
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
	function _getDocumentTitleFullClass2() {
	  if (this.isTemplateMode()) {
	    return '--full';
	  }
	  return this.isRuRegion() ? '' : '--full';
	}
	function _sendDocumentType2(uid) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	    return Promise.resolve();
	  }
	  const type = babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].getSelectedId();
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeRegionDocumentType(uid, type);
	}
	function _sendDocumentSenderType2(uid) {
	  if (!this.isTemplateMode() || !this.isSenderTypeAvailable()) {
	    return Promise.resolve();
	  }
	  const senderType = babelHelpers.classPrivateFieldLooseBase(this, _documentSenderTypeDropdown)[_documentSenderTypeDropdown].getSelectedId();
	  this.setupData.initiatedByType = senderType;
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeSenderDocumentType(uid, senderType);
	}
	function _sendDocumentNumber2(uid) {
	  if (!this.isRuRegion() || this.isTemplateMode()) {
	    return Promise.resolve();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeExternalId(uid, babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput].value);
	}
	function _sendDocumentDate2(uid) {
	  if (!this.isRuRegion() || this.isTemplateMode()) {
	    return Promise.resolve();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeExternalDate(uid, babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector].getSelectedDate());
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
	function _enableDocumentInputs2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].disabled = false;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput].disabled = false;
	  }
	  this.blankIsNotSelected = false;
	}
	function _disableDocumentInputs2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].disabled = true;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput].disabled = true;
	  }
	  this.blankIsNotSelected = true;
	}

	exports.DocumentSetup = DocumentSetup;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Main,BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign,BX.Sign.V2.B2e,BX.Sign,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX,BX.Main));
//# sourceMappingURL=document-setup.bundle.js.map

/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,sign_v2_api,sign_v2_b2e_signDropdown,sign_v2_documentSetup,sign_v2_helper,sign_v2_signSettings,main_core,main_date) {
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
	  _t8;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _region = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("region");
	var _regionDocumentTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("regionDocumentTypes");
	var _documentTypeDropdown = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentTypeDropdown");
	var _documentNumberInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentNumberInput");
	var _documentTitleInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentTitleInput");
	var _dateSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dateSelector");
	var _documentMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentMode");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _isDocumentTypeVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDocumentTypeVisible");
	var _isRuRegion = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRuRegion");
	var _initDocumentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initDocumentType");
	var _getDocumentTypeLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTypeLayout");
	var _getDocumentNumberLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentNumberLayout");
	var _getDocumentTitleLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTitleLayout");
	var _getDocumentHintLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentHintLayout");
	var _getDocumentTitleFullClass = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDocumentTitleFullClass");
	var _sendDocumentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentType");
	var _sendDocumentNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentNumber");
	var _sendDocumentDate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendDocumentDate");
	var _setDocumentNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setDocumentNumber");
	var _validateInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateInput");
	var _isTemplateMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTemplateMode");
	class DocumentSetup extends sign_v2_documentSetup.DocumentSetup {
	  constructor(blankSelectorConfig) {
	    super(blankSelectorConfig);
	    Object.defineProperty(this, _isTemplateMode, {
	      value: _isTemplateMode2
	    });
	    Object.defineProperty(this, _validateInput, {
	      value: _validateInput2
	    });
	    Object.defineProperty(this, _setDocumentNumber, {
	      value: _setDocumentNumber2
	    });
	    Object.defineProperty(this, _sendDocumentDate, {
	      value: _sendDocumentDate2
	    });
	    Object.defineProperty(this, _sendDocumentNumber, {
	      value: _sendDocumentNumber2
	    });
	    Object.defineProperty(this, _sendDocumentType, {
	      value: _sendDocumentType2
	    });
	    Object.defineProperty(this, _getDocumentTitleFullClass, {
	      value: _getDocumentTitleFullClass2
	    });
	    Object.defineProperty(this, _getDocumentHintLayout, {
	      value: _getDocumentHintLayout2
	    });
	    Object.defineProperty(this, _getDocumentTitleLayout, {
	      value: _getDocumentTitleLayout2
	    });
	    Object.defineProperty(this, _getDocumentNumberLayout, {
	      value: _getDocumentNumberLayout2
	    });
	    Object.defineProperty(this, _getDocumentTypeLayout, {
	      value: _getDocumentTypeLayout2
	    });
	    Object.defineProperty(this, _initDocumentType, {
	      value: _initDocumentType2
	    });
	    Object.defineProperty(this, _isRuRegion, {
	      value: _isRuRegion2
	    });
	    Object.defineProperty(this, _isDocumentTypeVisible, {
	      value: _isDocumentTypeVisible2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
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
	    Object.defineProperty(this, _documentTypeDropdown, {
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
	    Object.defineProperty(this, _documentMode, {
	      writable: true,
	      value: void 0
	    });
	    const {
	      region,
	      regionDocumentTypes,
	      documentMode
	    } = blankSelectorConfig;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] = region;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentMode)[_documentMode] = documentMode;
	    babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes] = regionDocumentTypes;
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
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isRuRegion)[_isRuRegion]() && !sign_v2_signSettings.isTemplateMode(documentMode)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput] = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`<input type="text" class="ui-ctl-element" maxlength="255" />`));
	      babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector] = new DateSelector();
	    }
	    this.blankSelector.subscribe('toggleSelection', ({
	      data
	    }) => {
	      this.setDocumentTitle(data.title);
	    });
	    this.blankSelector.subscribe('addFile', ({
	      data
	    }) => {
	      this.setDocumentTitle(data.title);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]();
	  }
	  setDocumentTitle(title = '') {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].value = title;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput].title = title;
	  }
	  initLayout() {
	    this.layout = main_core.Tag.render(_t3 || (_t3 = _$1`
			<div class="sign-document-setup">
				<h1 class="sign-b2e-settings__header">${0}</h1>
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${0}
					</p>
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_HEADER'), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_ADD_TITLE'), this.blankSelector.getLayout());
	  }
	  async setup(uid) {
	    try {
	      var _babelHelpers$classPr;
	      await super.setup(uid, babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]());
	      if (!this.setupData) {
	        return;
	      }
	      if (uid) {
	        const {
	          title,
	          externalId,
	          externalDateCreate
	        } = this.setupData;
	        this.setDocumentTitle(title);
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isRuRegion)[_isRuRegion]() && !babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]()) {
	          babelHelpers.classPrivateFieldLooseBase(this, _setDocumentNumber)[_setDocumentNumber](externalId);
	          babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector].setDateInCalendar(new Date(externalDateCreate));
	        }
	        return;
	      }
	      const {
	        uid: documentUid,
	        templateUid
	      } = this.setupData;
	      const {
	        value: title
	      } = babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput];
	      const externalId = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]) == null ? void 0 : _babelHelpers$classPr.value;
	      this.ready = false;
	      await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentType)[_sendDocumentType](documentUid), babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentNumber)[_sendDocumentNumber](documentUid), babelHelpers.classPrivateFieldLooseBase(this, _sendDocumentDate)[_sendDocumentDate](documentUid)]);
	      const modifyDocumentTitleResponse = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyTitle(documentUid, title);
	      const {
	        blankTitle
	      } = modifyDocumentTitleResponse;
	      if (blankTitle) {
	        const {
	          blankId
	        } = this.setupData;
	        this.blankSelector.modifyBlankTitle(blankId, blankTitle);
	      }
	      this.setupData = {
	        ...this.setupData,
	        title,
	        externalId,
	        templateUid
	      };
	    } catch {
	      const {
	        blankId
	      } = this.setupData;
	      this.handleError(blankId);
	    }
	    this.ready = true;
	  }
	  validate() {
	    const isValidTitle = babelHelpers.classPrivateFieldLooseBase(this, _validateInput)[_validateInput](babelHelpers.classPrivateFieldLooseBase(this, _documentTitleInput)[_documentTitleInput]);
	    const isValidNumber = babelHelpers.classPrivateFieldLooseBase(this, _validateInput)[_validateInput](babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput]);
	    return isValidTitle && isValidNumber;
	  }
	}
	function _init2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _initDocumentType)[_initDocumentType]();
	  const documentTypeLayout = babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTypeLayout)[_getDocumentTypeLayout]();
	  const title = babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]() ? main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_TEMPLATE_HEAD_LABEL') : main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HEAD_LABEL');
	  const titleLayout = main_core.Tag.render(_t4 || (_t4 = _$1`
			<div class="sign-b2e-settings__item">
				<p class="sign-b2e-settings__item_title">
					${0}
				</p>
				${0}
			</div>
		`), title, babelHelpers.classPrivateFieldLooseBase(this, _getDocumentTitleLayout)[_getDocumentTitleLayout]());
	  main_core.Dom.append(documentTypeLayout, this.layout);
	  main_core.Dom.append(titleLayout, this.layout);
	  sign_v2_helper.Hint.create(this.layout);
	}
	function _isDocumentTypeVisible2() {
	  var _babelHelpers$classPr2;
	  return (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _regionDocumentTypes)[_regionDocumentTypes]) == null ? void 0 : _babelHelpers$classPr2.length;
	}
	function _isRuRegion2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] === 'ru';
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
	    withCaption: true
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
	  return main_core.Tag.render(_t5 || (_t5 = _$1`
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
	function _getDocumentNumberLayout2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRuRegion)[_isRuRegion]() || babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]()) {
	    return null;
	  }
	  return main_core.Tag.render(_t6 || (_t6 = _$1`
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
	  return main_core.Tag.render(_t7 || (_t7 = _$1`
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
	function _getDocumentHintLayout2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]()) {
	    return null;
	  }
	  return main_core.Tag.render(_t8 || (_t8 = _$1`
			<p class="sign-b2e-document-setup__title-text">
				${0}
			</p>
		`), main_core.Loc.getMessage('SIGN_DOCUMENT_SETUP_TITLE_HINT'));
	}
	function _getDocumentTitleFullClass2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]()) {
	    return '--full';
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _isRuRegion)[_isRuRegion]() ? '' : '--full';
	}
	function _sendDocumentType2(uid) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isDocumentTypeVisible)[_isDocumentTypeVisible]()) {
	    return Promise.resolve();
	  }
	  const type = babelHelpers.classPrivateFieldLooseBase(this, _documentTypeDropdown)[_documentTypeDropdown].getSelectedId();
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeRegionDocumentType(uid, type);
	}
	function _sendDocumentNumber2(uid) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRuRegion)[_isRuRegion]() || babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]()) {
	    return Promise.resolve();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeExternalId(uid, babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput].value);
	}
	function _sendDocumentDate2(uid) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRuRegion)[_isRuRegion]() || babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]()) {
	    return Promise.resolve();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].changeExternalDate(uid, babelHelpers.classPrivateFieldLooseBase(this, _dateSelector)[_dateSelector].getSelectedDate());
	}
	function _setDocumentNumber2(number) {
	  babelHelpers.classPrivateFieldLooseBase(this, _documentNumberInput)[_documentNumberInput].value = number;
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
	function _isTemplateMode2() {
	  return sign_v2_signSettings.isTemplateMode(babelHelpers.classPrivateFieldLooseBase(this, _documentMode)[_documentMode]);
	}

	exports.DocumentSetup = DocumentSetup;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Sign.V2,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign.V2,BX.Sign.V2,BX,BX.Main));
//# sourceMappingURL=document-setup.bundle.js.map

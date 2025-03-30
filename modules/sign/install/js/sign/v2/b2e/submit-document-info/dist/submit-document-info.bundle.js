/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core_cache,main_core_events,sign_v2_api,sign_type,ui_forms,sign_v2_b2e_signLink,main_core,ui_datePicker,ui_formElements_view) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _datepicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("datepicker");
	var _inputNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputNode");
	var _renderInputNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderInputNode");
	class DatePickerField extends ui_formElements_view.BaseField {
	  constructor(params) {
	    super(params);
	    Object.defineProperty(this, _renderInputNode, {
	      value: _renderInputNode2
	    });
	    Object.defineProperty(this, _datepicker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputNode, {
	      writable: true,
	      value: void 0
	    });
	    this.defaultValue = main_core.Type.isStringFilled(params.value) ? params.value : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _datepicker)[_datepicker] = new ui_datePicker.DatePicker({
	      type: 'date',
	      inputField: this.getInputNode(),
	      targetNode: this.getInputNode()
	    });
	  }
	  getValue() {
	    return this.getInputNode().value;
	  }
	  getInputNode() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _inputNode))[_inputNode]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_inputNode] = babelHelpers.classPrivateFieldLooseBase(this, _renderInputNode)[_renderInputNode]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode];
	  }
	  renderContentField() {
	    const lockElement = !this.isEnable ? this.renderLockElement() : null;
	    return main_core.Tag.render(_t || (_t = _`
			<div id="${0}" class="ui-section__field-selector">
				<div class="ui-section__field-container">
					<div class="ui-section__field-label_box">
						<label for="${0}" class="ui-section__field-label">
							${0}
						</label> 
						${0}
					</div>  
					<div class="ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-after-icon ${0}">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						${0}
					</div>
					${0}
				</div>
				<div class="ui-section__hint">
					${0}
				</div>
			</div>
		`), this.getId(), this.getName(), this.getLabel(), lockElement, this.inputDefaultWidth ? '' : 'ui-ctl-w100', this.getInputNode(), this.renderErrors(), this.hintTitle);
	  }
	}
	function _renderInputNode2() {
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<input
				value="${0}" 
				name="${0}" 
				type="text" 
				class="ui-ctl-element --readonly" 
				readonly
			>
		`), main_core.Text.encode(this.defaultValue), main_core.Text.encode(this.getName()));
	}

	var readyToSendImage = "/bitrix/js/sign/v2/b2e/submit-document-info/dist/images/ready-to-send-state-image.svg";

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9;
	function sleep(ms) {
	  return new Promise(resolve => {
	    setTimeout(resolve, ms);
	  });
	}
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _layoutCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutCache");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _fieldFormId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fieldFormId");
	var _uiFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uiFields");
	var _getProgressLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProgressLayout");
	var _showProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showProgress");
	var _hideProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideProgress");
	var _onProgressClosePageBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProgressClosePageBtnClick");
	var _openSigningSliderAndCloseCurrent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openSigningSliderAndCloseCurrent");
	var _getFieldsLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsLayout");
	var _getOrCreateFieldLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOrCreateFieldLayout");
	var _getFieldLayoutCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldLayoutCallback");
	var _getFieldValues = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldValues");
	var _isFieldsValid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFieldsValid");
	var _getFieldByUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldByUid");
	var _findFieldByUidRecursive = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findFieldByUidRecursive");
	var _getSelectorItemsWithEmpty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectorItemsWithEmpty");
	class SubmitDocumentInfo extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getSelectorItemsWithEmpty, {
	      value: _getSelectorItemsWithEmpty2
	    });
	    Object.defineProperty(this, _findFieldByUidRecursive, {
	      value: _findFieldByUidRecursive2
	    });
	    Object.defineProperty(this, _getFieldByUid, {
	      value: _getFieldByUid2
	    });
	    Object.defineProperty(this, _isFieldsValid, {
	      value: _isFieldsValid2
	    });
	    Object.defineProperty(this, _getFieldValues, {
	      value: _getFieldValues2
	    });
	    Object.defineProperty(this, _getFieldLayoutCallback, {
	      value: _getFieldLayoutCallback2
	    });
	    Object.defineProperty(this, _getOrCreateFieldLayout, {
	      value: _getOrCreateFieldLayout2
	    });
	    Object.defineProperty(this, _getFieldsLayout, {
	      value: _getFieldsLayout2
	    });
	    Object.defineProperty(this, _openSigningSliderAndCloseCurrent, {
	      value: _openSigningSliderAndCloseCurrent2
	    });
	    Object.defineProperty(this, _onProgressClosePageBtnClick, {
	      value: _onProgressClosePageBtnClick2
	    });
	    Object.defineProperty(this, _hideProgress, {
	      value: _hideProgress2
	    });
	    Object.defineProperty(this, _showProgress, {
	      value: _showProgress2
	    });
	    Object.defineProperty(this, _getProgressLayout, {
	      value: _getProgressLayout2
	    });
	    this.events = Object.freeze({
	      onProgressClosePageBtnClick: 'onProgressClosePageBtnClick',
	      documentSendedSuccessFully: 'documentSendedSuccessFully'
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _layoutCache, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    Object.defineProperty(this, _fieldFormId, {
	      writable: true,
	      value: 'sign-b2e-employee-fields-form'
	    });
	    Object.defineProperty(this, _uiFields, {
	      writable: true,
	      value: []
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.SubmitDocumentInfo');
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('layout', () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].fields.length === 0) {
	        return main_core.Tag.render(_t$1 || (_t$1 = _$1`
						<div class="sign-submit-document-info-center-container">
							<div class="sign-submit-document-info-center-icon">
								<img src="${0}" alt="">
							</div>
							<p class="sign-submit-document-info-center-title">
								${0}
							</p>
							<p class="sign-submit-document-info-center-description">
								${0}
							</p>
							<form id="${0}"></form>
						</div>
					`), readyToSendImage, main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_READY_TO_SEND_TITLE'), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_READY_TO_SEND_DESCRIPTION', {
	          '#TITLE#': main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].template.title)
	        }), babelHelpers.classPrivateFieldLooseBase(this, _fieldFormId)[_fieldFormId]);
	      }
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
					<div class="sign-b2e-submit-document-info">
						<h1 class="sign-b2e-settings__header">${0}</h1>
						<div class="sign-b2e-settings__item">
							<p class="sign-b2e-settings__item_title">
								${0}
							</p>
							<form id="${0}">
								${0}
							</form>
						</div>
					</div>
				`), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_HEAD'), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_DESCRIPTION'), babelHelpers.classPrivateFieldLooseBase(this, _fieldFormId)[_fieldFormId], babelHelpers.classPrivateFieldLooseBase(this, _getFieldsLayout)[_getFieldsLayout]());
	    });
	  }
	  async sendForSign() {
	    var _BX$PULL;
	    const currentSidePanel = BX.SidePanel.Instance.getTopSlider();
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isFieldsValid)[_isFieldsValid]()) {
	      return false;
	    }
	    let employeeMember = null;
	    let document = null;
	    main_core_events.EventEmitter.emit('BX.Sign.SignSettingsEmployee:onBeforeTemplateSend');
	    try {
	      const sendResult = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.send(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].template.uid, babelHelpers.classPrivateFieldLooseBase(this, _getFieldValues)[_getFieldValues]());
	      employeeMember = sendResult.employeeMember;
	      document = sendResult.document;
	    } catch (e) {
	      console.error(e);
	      return false;
	    } finally {
	      main_core_events.EventEmitter.emit('BX.Sign.SignSettingsEmployee:onAfterTemplateSend');
	    }
	    const {
	      uid: memberUid,
	      id: memberId
	    } = employeeMember;
	    this.emit(this.events.documentSendedSuccessFully, {
	      document
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _showProgress)[_showProgress]();
	    let pending = true;
	    let openSigningSliderAfterPending = true;
	    const signLink = new sign_v2_b2e_signLink.SignLink({
	      memberId
	    });
	    main_core_events.EventEmitter.subscribeOnce(currentSidePanel, 'SidePanel.Slider:onCloseStart', () => {
	      pending = false;
	      openSigningSliderAfterPending = false;
	    });
	    (_BX$PULL = BX.PULL) == null ? void 0 : _BX$PULL.subscribe({
	      moduleId: 'sign',
	      command: 'memberInvitedToSign',
	      callback: async params => {
	        if (params.member.id !== memberId || !pending || !openSigningSliderAfterPending) {
	          return;
	        }
	        pending = false;
	        await babelHelpers.classPrivateFieldLooseBase(this, _openSigningSliderAndCloseCurrent)[_openSigningSliderAndCloseCurrent](signLink);
	      }
	    });
	    do {
	      await sleep(5000);
	      if (!openSigningSliderAfterPending) {
	        return true;
	      }
	      if (!pending) {
	        break;
	      }
	      let status = null;
	      try {
	        status = (await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getMember(memberUid)).status;
	      } catch (e) {
	        console.error(e);
	        babelHelpers.classPrivateFieldLooseBase(this, _hideProgress)[_hideProgress]();
	        return false;
	      }
	      if (status === sign_type.MemberStatus.ready || status === sign_type.MemberStatus.stoppableReady) {
	        pending = false;
	      }
	    } while (pending);
	    if (openSigningSliderAfterPending) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _openSigningSliderAndCloseCurrent)[_openSigningSliderAndCloseCurrent](signLink);
	    }
	    return true;
	  }
	}
	function _getProgressLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('progressLayout', () => main_core.Tag.render(_t3 || (_t3 = _$1`
				<div class="sign-b2e-submit-document-info__progress">
					<div class="sign-b2e-submit-document-info__progress_icon"></div>
					<h2 class="sign-b2e-submit-document-info__progress_head">
						${0}
					</h2>
					<p class="sign-b2e-submit-document-info__progress_description">
						${0}
					</p>
					<button
						class="ui-btn ui-btn-round ui-btn-light-border"
						onclick="${0}"
					>
						${0}
					</button>
				</div>
			`), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_PROGRESS_HEAD'), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_PROGRESS_DESCRIPTION'), () => babelHelpers.classPrivateFieldLooseBase(this, _onProgressClosePageBtnClick)[_onProgressClosePageBtnClick](), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_PROGRESS_CLOSE')));
	}
	function _showProgress2() {
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout](), this.getLayout());
	}
	function _hideProgress2() {
	  main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout]());
	}
	function _onProgressClosePageBtnClick2() {
	  this.emit(this.event.onProgressClosePageBtnClick);
	  BX.SidePanel.Instance.close();
	}
	async function _openSigningSliderAndCloseCurrent2(signLink) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('openSigningSliderAndCloseCurrent', async () => {
	    const currentSidePanel = BX.SidePanel.Instance.getTopSlider();
	    // load signing data before close current slider
	    await signLink.preloadData();
	    if (main_core.Type.isNull(currentSidePanel)) {
	      signLink.openSlider({
	        events: {}
	      });
	    } else {
	      currentSidePanel.close(false, () => signLink.openSlider({
	        events: {}
	      }));
	    }
	  });
	}
	function _getFieldsLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].fields.map(field => babelHelpers.classPrivateFieldLooseBase(this, _getOrCreateFieldLayout)[_getOrCreateFieldLayout](field));
	}
	function _getOrCreateFieldLayout2(field) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember(`fieldLayout.${field.uid}`, () => {
	    const fieldsLayoutCallbackByType = babelHelpers.classPrivateFieldLooseBase(this, _getFieldLayoutCallback)[_getFieldLayoutCallback](field);
	    if (main_core.Type.isNull(fieldsLayoutCallbackByType)) {
	      throw new TypeError(`Unknown field type: ${field.type}`);
	    }
	    return fieldsLayoutCallbackByType(field);
	  });
	}
	function _getFieldLayoutCallback2(field) {
	  var _fieldsLayoutCallback;
	  const label = `
			<span>
				${main_core.Text.encode(field.name)} 
				${field.required ? '<span class="sign-b2e-submit-document-info__field_required">*</span>' : ''}
			</span>
		`;
	  const fieldsLayoutCallbackByType = {
	    date: () => {
	      const datePickerField = new DatePickerField({
	        label,
	        inputName: field.uid,
	        value: field.value
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _uiFields)[_uiFields].push(datePickerField);
	      return main_core.Tag.render(_t4 || (_t4 = _$1`
					<div class="sign-b2e-submit-document-info__field">
						${0}
					</div>
				`), datePickerField.render());
	    },
	    string: () => {
	      const fieldInput = new ui_formElements_view.TextInput({
	        label,
	        inputName: field.uid,
	        value: field.value
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _uiFields)[_uiFields].push(fieldInput);
	      return main_core.Tag.render(_t5 || (_t5 = _$1`
					<div class="sign-b2e-submit-document-info__field">
						${0}
					</div>
				`), fieldInput.render());
	    },
	    list: () => {
	      const selector = new ui_formElements_view.Selector({
	        label,
	        name: field.uid,
	        inputName: field.uid,
	        items: babelHelpers.classPrivateFieldLooseBase(this, _getSelectorItemsWithEmpty)[_getSelectorItemsWithEmpty](field)
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _uiFields)[_uiFields].push(selector);
	      return main_core.Tag.render(_t6 || (_t6 = _$1`
					<div class="sign-b2e-submit-document-info__field">
						${0}
					</div>
				`), selector.render());
	    },
	    // @TODO address picker
	    address: () => main_core.Tag.render(_t7 || (_t7 = _$1`
				<div class="sign-b2e-submit-document-info__field">
					<span class="sign-b2e-submit-document-info__label">
						${0}
					</span>
					<div class="sign-b2e-submit-document-info__subfields">
						${0}
					</div>
				</div>
			`), main_core.Text.encode(field.name), field.subfields.map(subfield => main_core.Tag.render(_t8 || (_t8 = _$1`
							<div>${0}</div>
						`), babelHelpers.classPrivateFieldLooseBase(this, _getOrCreateFieldLayout)[_getOrCreateFieldLayout](subfield))))
	  };
	  const defaultLayout = () => main_core.Tag.render(_t9 || (_t9 = _$1`<div></div>`));
	  return (_fieldsLayoutCallback = fieldsLayoutCallbackByType[field.type]) != null ? _fieldsLayoutCallback : defaultLayout;
	}
	function _getFieldValues2() {
	  const form = document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _fieldFormId)[_fieldFormId]);
	  const formData = new FormData(form);
	  const fieldValues = [];
	  formData.forEach((value, name) => {
	    fieldValues.push({
	      name,
	      value
	    });
	  });
	  return fieldValues;
	}
	function _isFieldsValid2() {
	  let errorCount = 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _uiFields)[_uiFields].forEach(domField => {
	    var _domField$getValue;
	    domField.cleanError();
	    const templateField = babelHelpers.classPrivateFieldLooseBase(this, _getFieldByUid)[_getFieldByUid](domField.getName());
	    if (!templateField) {
	      return;
	    }
	    if (templateField.required && ((_domField$getValue = domField.getValue()) == null ? void 0 : _domField$getValue.trim()) === '') {
	      domField.setErrors([]);
	      errorCount += 1;
	    }
	  });
	  return errorCount === 0;
	}
	function _getFieldByUid2(uid) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _findFieldByUidRecursive)[_findFieldByUidRecursive](uid, babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].fields);
	}
	function _findFieldByUidRecursive2(uid, fields) {
	  for (const field of fields) {
	    if (field.uid === uid) {
	      return field;
	    }
	    if (field.subfields) {
	      const subfield = babelHelpers.classPrivateFieldLooseBase(this, _findFieldByUidRecursive)[_findFieldByUidRecursive](uid, field.subfields);
	      if (subfield) {
	        return subfield;
	      }
	    }
	  }
	  return null;
	}
	function _getSelectorItemsWithEmpty2(field) {
	  const items = [];
	  if (!field.items.some(item => item.code === field.value)) {
	    items.push({
	      value: '',
	      name: '',
	      selected: true,
	      hidden: true,
	      disabled: true
	    });
	  }
	  field.items.forEach(item => {
	    items.push({
	      value: main_core.Text.encode(item.code),
	      name: main_core.Text.encode(item.label),
	      selected: item.code === field.value
	    });
	  });
	  return items;
	}

	exports.SubmitDocumentInfo = SubmitDocumentInfo;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Cache,BX.Event,BX.Sign.V2,BX.Sign,BX,BX.Sign.V2.B2e,BX,BX.UI.DatePicker,BX.UI.FormElements));
//# sourceMappingURL=submit-document-info.bundle.js.map

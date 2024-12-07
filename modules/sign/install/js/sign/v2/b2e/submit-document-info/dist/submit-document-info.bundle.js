/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_cache,main_core_events,main_date,sign_v2_api,ui_forms,sign_v2_b2e_signLink) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10;
	function sleep(ms) {
	  return new Promise(resolve => {
	    setTimeout(resolve, ms);
	  });
	}
	var _layoutCache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutCache");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _getCompanyLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyLayout");
	var _getProgressLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProgressLayout");
	var _showProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showProgress");
	var _hideProgress = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideProgress");
	var _onProgressClosePageBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onProgressClosePageBtnClick");
	var _openSigningSliderAndCloseCurrent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openSigningSliderAndCloseCurrent");
	var _getFieldsLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsLayout");
	var _getOrCreateFieldLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOrCreateFieldLayout");
	var _getFieldLayoutCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldLayoutCallback");
	var _onDateFieldClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDateFieldClick");
	var _formatDateToUserFormat = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatDateToUserFormat");
	class SubmitDocumentInfo {
	  constructor(options) {
	    Object.defineProperty(this, _formatDateToUserFormat, {
	      value: _formatDateToUserFormat2
	    });
	    Object.defineProperty(this, _onDateFieldClick, {
	      value: _onDateFieldClick2
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
	    Object.defineProperty(this, _getCompanyLayout, {
	      value: _getCompanyLayout2
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
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('layout', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _hideProgress)[_hideProgress]();
	      return main_core.Tag.render(_t || (_t = _`
					<div class="sign-b2e-submit-document-info">
						<h1 class="sign-b2e-settings__header">${0}</h1>
						<div class="sign-b2e-settings__item">
							<p class="sign-b2e-settings__item_title">
								${0}
							</p>
							${0}
						</div>
						<div class="sign-b2e-settings__item">
							<p class="sign-b2e-settings__item_title">
								${0}
							</p>
							${0}
						</div>
						${0}
					</div>
				`), main_core.Loc.getMessage('SIGN_START_PROCESS_HEAD'), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_COMPANY'), babelHelpers.classPrivateFieldLooseBase(this, _getCompanyLayout)[_getCompanyLayout](), main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_INFO_DESCRIPTION'), babelHelpers.classPrivateFieldLooseBase(this, _getFieldsLayout)[_getFieldsLayout](), babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout]());
	    });
	  }
	  async sendForSign() {
	    var _BX$PULL;
	    const {
	      employeeMember
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.send(babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].template.uid);
	    const {
	      uid: memberUid,
	      id: memberId
	    } = employeeMember;
	    const currentSidePanel = BX.SidePanel.Instance.getTopSlider();
	    babelHelpers.classPrivateFieldLooseBase(this, _showProgress)[_showProgress]();
	    let pending = true;
	    let openSigningSliderAfterPending = true;
	    const signLink = new sign_v2_b2e_signLink.SignLink({
	      memberId
	    });
	    main_core_events.EventEmitter.subscribe('SidePanel.Slider:onCloseStart', () => {
	      pending = false;
	      openSigningSliderAfterPending = false;
	    });
	    (_BX$PULL = BX.PULL) == null ? void 0 : _BX$PULL.subscribe({
	      moduleId: 'sign',
	      command: 'memberInvitedToSign',
	      callback: async params => {
	        if (params.member.id !== memberId && pending && openSigningSliderAfterPending) {
	          return;
	        }
	        pending = false;
	        openSigningSliderAfterPending = false;
	        await babelHelpers.classPrivateFieldLooseBase(this, _openSigningSliderAndCloseCurrent)[_openSigningSliderAndCloseCurrent](signLink, currentSidePanel);
	      }
	    });
	    do {
	      await sleep(5000);
	      if (!openSigningSliderAfterPending) {
	        return true;
	      }
	      const {
	        status
	      } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getMember(memberUid);
	      if (status === sign_v2_api.MemberStatus.ready || status === sign_v2_api.MemberStatus.stoppableReady) {
	        pending = false;
	      }
	    } while (pending);
	    if (openSigningSliderAfterPending) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _openSigningSliderAndCloseCurrent)[_openSigningSliderAndCloseCurrent](signLink, currentSidePanel);
	    }
	    return true;
	  }
	}
	function _getCompanyLayout2() {
	  const {
	    name,
	    taxId
	  } = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].company;
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-b2e-submit-document-info__company">
				<div class="sign-b2e-submit-document-info__company_summary">
					<p class="sign-b2e-submit-document-info__company_name">
						${0}
					</p>
					<p class="sign-b2e-submit-document-info__company_tax">
						${0}
					</p>
				</div>
			</div>
		`), name, main_core.Loc.getMessage('SIGN_SUBMIT_DOCUMENT_COMPANY_TAX', {
	    '#TAX_ID#': taxId
	  }));
	}
	function _getProgressLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _layoutCache)[_layoutCache].remember('progressLayout', () => main_core.Tag.render(_t3 || (_t3 = _`
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
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout](), '--hidden');
	}
	function _hideProgress2() {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout](), '--hidden');
	}
	function _onProgressClosePageBtnClick2() {
	  BX.SidePanel.Instance.close();
	}
	async function _openSigningSliderAndCloseCurrent2(signLink, currentSidePanel) {
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
	  const now = new Date();
	  const fieldName = main_core.Tag.render(_t4 || (_t4 = _`
			<span class="sign-b2e-submit-document-info__label">
				${0}
			</span>
		`), field.name);
	  const fieldsLayoutCallbackByType = {
	    date: () => main_core.Tag.render(_t5 || (_t5 = _`
				<div class="sign-b2e-submit-document-info__field">
					${0}
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-date" onclick="${0}">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						<div class="ui-ctl-element sign-b2e-submit-document-info__field-date__value">${0}</div>
					</div>
				</div>
			`), fieldName, () => babelHelpers.classPrivateFieldLooseBase(this, _onDateFieldClick)[_onDateFieldClick](field), babelHelpers.classPrivateFieldLooseBase(this, _formatDateToUserFormat)[_formatDateToUserFormat](now)),
	    string: () => main_core.Tag.render(_t6 || (_t6 = _`
				<div class="sign-b2e-submit-document-info__field">
					${0}
					<div class="ui-ctl ui-ctl-textbox">
						<input type="text" class="ui-ctl-element">
					</div>
				</div>
			`), fieldName),
	    list: () => main_core.Tag.render(_t7 || (_t7 = _`
				<div class="sign-b2e-submit-document-info__field">
					${0}
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element">
							${0}
						</select>
					</div>
				</div>
			`), fieldName, field.items.map(item => main_core.Tag.render(_t8 || (_t8 = _`
								<option value="${0}">${0}</option>
							`), item.code, item.label))),
	    number: () => main_core.Tag.render(_t9 || (_t9 = _`
				<div class="sign-b2e-submit-document-info__field">
					${0}
					<div class="ui-ctl ui-ctl-textbox">
						<input type="number" class="ui-ctl-element">
					</div>
				</div>
			`), fieldName)
	  };
	  const defaultLayout = () => main_core.Tag.render(_t10 || (_t10 = _`<div></div>`));
	  return (_fieldsLayoutCallback = fieldsLayoutCallbackByType[field.type]) != null ? _fieldsLayoutCallback : defaultLayout;
	}
	function _onDateFieldClick2(field) {
	  BX.calendar({
	    node: babelHelpers.classPrivateFieldLooseBase(this, _getOrCreateFieldLayout)[_getOrCreateFieldLayout](field),
	    field: babelHelpers.classPrivateFieldLooseBase(this, _getOrCreateFieldLayout)[_getOrCreateFieldLayout](field),
	    bTime: false,
	    callback_after: date => {
	      const dateFieldValue = babelHelpers.classPrivateFieldLooseBase(this, _getOrCreateFieldLayout)[_getOrCreateFieldLayout](field).querySelector('.sign-b2e-submit-document-info__field-date__value');
	      if (dateFieldValue) {
	        dateFieldValue.textContent = babelHelpers.classPrivateFieldLooseBase(this, _formatDateToUserFormat)[_formatDateToUserFormat](date);
	      }
	    }
	  });
	}
	function _formatDateToUserFormat2(date) {
	  return main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('FORMAT_DATE'), date);
	}

	exports.SubmitDocumentInfo = SubmitDocumentInfo;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Cache,BX.Event,BX.Main,BX.Sign.V2,BX,BX.Sign.V2.B2e));
//# sourceMappingURL=submit-document-info.bundle.js.map

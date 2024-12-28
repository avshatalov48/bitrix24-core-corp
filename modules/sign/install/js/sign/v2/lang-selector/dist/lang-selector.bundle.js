/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,ui_buttons,sign_v2_api) {
	'use strict';

	let _ = t => t,
	  _t;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _langs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("langs");
	var _langButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("langButton");
	var _region = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("region");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _getLanguageItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLanguageItems");
	var _getLanguageButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLanguageButton");
	var _changeLang = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeLang");
	class LangSelector {
	  constructor(region, langs) {
	    Object.defineProperty(this, _changeLang, {
	      value: _changeLang2
	    });
	    Object.defineProperty(this, _getLanguageButton, {
	      value: _getLanguageButton2
	    });
	    Object.defineProperty(this, _getLanguageItems, {
	      value: _getLanguageItems2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _langs, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _langButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _region, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _region)[_region] = region;
	    babelHelpers.classPrivateFieldLooseBase(this, _langs)[_langs] = langs;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = '';
	    babelHelpers.classPrivateFieldLooseBase(this, _langButton)[_langButton] = babelHelpers.classPrivateFieldLooseBase(this, _getLanguageButton)[_getLanguageButton]();
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }
	  getLayout() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="sign-lang-selector">
				<span class="sign-lang-selector__label">
					${0}
				</span>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_BLANK_LANGUAGE_SELECTOR_LABEL'), babelHelpers.classPrivateFieldLooseBase(this, _langButton)[_langButton].getContainer());
	  }
	  setDocumentUid(uid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = uid;
	  }
	}
	function _getLanguageItems2() {
	  const onItemClick = function (event) {
	    const id = event.currentTarget.getAttribute('data-lang-id');
	    babelHelpers.classPrivateFieldLooseBase(this, _langButton)[_langButton].menuWindow.close();
	    babelHelpers.classPrivateFieldLooseBase(this, _changeLang)[_changeLang](id);
	    babelHelpers.classPrivateFieldLooseBase(this, _langButton)[_langButton].setText(babelHelpers.classPrivateFieldLooseBase(this, _langs)[_langs][id].NAME);
	  }.bind(this);
	  return Object.entries(babelHelpers.classPrivateFieldLooseBase(this, _langs)[_langs]).map(lang => {
	    return {
	      text: lang[1].NAME,
	      onclick: onItemClick,
	      dataset: {
	        langId: lang[0]
	      }
	    };
	  });
	}
	function _getLanguageButton2() {
	  var _babelHelpers$classPr;
	  return new ui_buttons.Button({
	    text: ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _langs)[_langs][babelHelpers.classPrivateFieldLooseBase(this, _region)[_region]]) == null ? void 0 : _babelHelpers$classPr.NAME) || main_core.Loc.getMessage('SIGN_BLANK_LANGUAGE_SELECTOR_BUTTON_TITLE'),
	    dropdown: true,
	    closeByEsc: true,
	    autoHide: true,
	    autoClose: true,
	    color: BX.UI.Button.Color.LIGHT,
	    size: BX.UI.Button.Size.SMALL,
	    menu: {
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getLanguageItems)[_getLanguageItems]()
	    },
	    className: 'sign-lang-selector__language-button'
	  });
	}
	async function _changeLang2(langId) {
	  await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].modifyLanguageId(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid], langId);
	}

	exports.LangSelector = LangSelector;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX.UI,BX.Sign.V2));
//# sourceMappingURL=lang-selector.bundle.js.map
